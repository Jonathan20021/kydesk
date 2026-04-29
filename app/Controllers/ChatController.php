<?php
namespace App\Controllers;

use App\Core\Controller;

class ChatController extends Controller
{
    /* ──────────────── Agent Inbox (autenticado) ──────────────── */

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.view');

        $convos = $this->db->all(
            "SELECT c.*, u.name AS assigned_name, w.name AS widget_name,
                    (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id) AS msg_count,
                    (SELECT body FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message
             FROM chat_conversations c
             LEFT JOIN users u ON u.id = c.assigned_to
             LEFT JOIN chat_widgets w ON w.id = c.widget_id
             WHERE c.tenant_id = ?
             ORDER BY c.last_message_at DESC, c.started_at DESC LIMIT 100",
            [$tenant->id]
        );

        $stats = [
            'open' => (int)$this->db->val("SELECT COUNT(*) FROM chat_conversations WHERE tenant_id=? AND status IN ('open','assigned')", [$tenant->id]),
            'closed_today' => (int)$this->db->val("SELECT COUNT(*) FROM chat_conversations WHERE tenant_id=? AND DATE(closed_at) = CURDATE()", [$tenant->id]),
            'total' => (int)$this->db->val('SELECT COUNT(*) FROM chat_conversations WHERE tenant_id=?', [$tenant->id]),
        ];

        $widgets = $this->db->all('SELECT * FROM chat_widgets WHERE tenant_id = ? ORDER BY id', [$tenant->id]);
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');

        $this->render('chat/index', [
            'title' => 'Live Chat',
            'convos' => $convos,
            'stats' => $stats,
            'widgets' => $widgets,
            'appUrl' => $appUrl,
        ]);
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.view');

        $id = (int)$params['id'];
        $convo = $this->db->one('SELECT * FROM chat_conversations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$convo) { $this->redirect('/t/' . $tenant->slug . '/chat'); }

        $messages = $this->db->all('SELECT m.*, u.name AS user_name FROM chat_messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.conversation_id = ? ORDER BY m.created_at ASC', [$id]);
        // Mark as seen
        $this->db->run('UPDATE chat_messages SET is_seen = 1 WHERE conversation_id = ? AND sender_type = "visitor"', [$id]);

        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tenant->id]);
        $widget = $this->db->one('SELECT * FROM chat_widgets WHERE id = ?', [(int)$convo['widget_id']]);

        $this->render('chat/show', [
            'title' => 'Chat #' . $id,
            'convo' => $convo,
            'messages' => $messages,
            'users' => $users,
            'widget' => $widget,
            'aiAvailable' => \App\Core\Plan::has($tenant, 'ai_assist'),
        ]);
    }

    public function reply(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.reply');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $convo = $this->db->one('SELECT * FROM chat_conversations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$convo) { $this->json(['ok'=>false, 'error'=>'Not found'], 404); }

        $body = trim((string)$this->input('body',''));
        if ($body === '') { $this->json(['ok'=>false, 'error'=>'Body required'], 400); }

        $this->db->insert('chat_messages', [
            'tenant_id' => $tenant->id,
            'conversation_id' => $id,
            'sender_type' => 'agent',
            'user_id' => $this->auth->userId(),
            'body' => $body,
        ]);
        $this->db->update('chat_conversations', [
            'last_message_at' => date('Y-m-d H:i:s'),
            'status' => 'assigned',
            'assigned_to' => $convo['assigned_to'] ?: $this->auth->userId(),
            'ai_takeover' => 0,
        ], 'id = :id', ['id' => $id]);

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if ($isAjax) $this->json(['ok'=>true]);
        else $this->redirect('/t/' . $tenant->slug . '/chat/' . $id);
    }

    /**
     * Activa o desactiva la respuesta IA automática para esta conversación.
     * Sobrescribe el modo del widget — útil cuando el agente quiere derivar a la IA
     * mientras se ocupa de otra cosa, o quiere retomar el control.
     */
    public function aiToggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.reply');
        $this->validateCsrf();

        if (!\App\Core\Plan::has($tenant, 'ai_assist')) {
            $this->session->flash('error', 'IA no disponible en tu plan.');
            $this->redirect('/t/' . $tenant->slug . '/chat/' . (int)$params['id']);
        }

        $id = (int)$params['id'];
        $convo  = $this->db->one('SELECT * FROM chat_conversations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$convo) { $this->redirect('/t/' . $tenant->slug . '/chat'); }
        $widget = $this->db->one('SELECT * FROM chat_widgets WHERE id=?', [(int)$convo['widget_id']]);

        $enable = (int)($this->input('enable', 0)) === 1;

        if ($enable) {
            // Verificación de cuota / asignación antes de prometer al agente que la IA va a responder.
            // No miramos widget.ai_fallback_mode acá porque el takeover es una acción explícita —
            // sobrescribe el modo automático del widget.
            $guard = \App\Core\ChatAi::guard(\App\Core\Tenant::find($tenant->id));
            if (!$guard['ok']) {
                $this->session->flash('error', 'IA no disponible: ' . $guard['error']);
                $this->redirect('/t/' . $tenant->slug . '/chat/' . $id);
            }

            // Reset turnos + limpiar escalación previa para dar a la IA un budget fresco.
            $this->db->run(
                'UPDATE chat_conversations
                    SET ai_takeover = 1,
                        ai_turns = 0,
                        ai_escalated_at = NULL,
                        last_message_at = ?
                  WHERE id = ? AND tenant_id = ?',
                [date('Y-m-d H:i:s'), $id, $tenant->id]
            );
            $persona = trim((string)($widget['ai_persona_name'] ?? '')) ?: 'el asistente IA';
            $this->db->insert('chat_messages', [
                'tenant_id'       => $tenant->id,
                'conversation_id' => $id,
                'sender_type'     => 'system',
                'user_id'         => null,
                'is_ai'           => 0,
                'body'            => "Te conectamos con $persona. Si necesitás un agente humano, podés pedirlo en cualquier momento.",
            ]);
            $this->logAudit($tenant->id, 'chat.ai.takeover_on', 'chat_conversation', $id, ['by' => $this->auth->userId()]);
            $this->session->flash('success', 'IA activada para esta conversación.');
        } else {
            $this->db->run(
                'UPDATE chat_conversations
                    SET ai_takeover = 0,
                        assigned_to = COALESCE(assigned_to, ?),
                        status = CASE WHEN status = "open" THEN "assigned" ELSE status END,
                        last_message_at = ?
                  WHERE id = ? AND tenant_id = ?',
                [$this->auth->userId(), date('Y-m-d H:i:s'), $id, $tenant->id]
            );
            $this->db->insert('chat_messages', [
                'tenant_id'       => $tenant->id,
                'conversation_id' => $id,
                'sender_type'     => 'system',
                'user_id'         => null,
                'is_ai'           => 0,
                'body'            => 'Un agente humano se incorporó a la conversación.',
            ]);
            $this->logAudit($tenant->id, 'chat.ai.takeover_off', 'chat_conversation', $id, ['by' => $this->auth->userId()]);
            $this->session->flash('success', 'Tomaste el control de la conversación.');
        }

        $this->redirect('/t/' . $tenant->slug . '/chat/' . $id);
    }

    protected function logAudit(int $tenantId, string $action, string $entity, int $entityId, array $meta): void
    {
        try {
            $this->db->insert('audit_logs', [
                'tenant_id' => $tenantId,
                'user_id'   => $this->auth->userId(),
                'action'    => substr($action, 0, 100),
                'entity'    => $entity,
                'entity_id' => $entityId,
                'meta'      => json_encode($meta),
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) { /* swallow */ }
    }

    public function close(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.reply');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('chat_conversations', [
            'status' => 'closed',
            'closed_at' => date('Y-m-d H:i:s'),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/chat');
    }

    public function convertToTicket(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.reply');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $convo = $this->db->one('SELECT * FROM chat_conversations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$convo) { $this->redirect('/t/' . $tenant->slug . '/chat'); }
        if ($convo['ticket_id']) { $this->redirect('/t/' . $tenant->slug . '/tickets/' . $convo['ticket_id']); }

        $messages = $this->db->all('SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC', [$id]);
        $description = "Conversación de chat:\n\n";
        foreach ($messages as $m) {
            $who = $m['sender_type'] === 'visitor' ? ($convo['visitor_name'] ?: 'Visitante') : 'Agente';
            $description .= "[$who · " . $m['created_at'] . "] " . $m['body'] . "\n\n";
        }

        $code = 'TKT-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $token = bin2hex(random_bytes(16));
        $ticketId = $this->db->insert('tickets', [
            'tenant_id' => $tenant->id,
            'code' => $code,
            'subject' => 'Chat con ' . ($convo['visitor_name'] ?: $convo['visitor_email'] ?: 'Visitante'),
            'description' => $description,
            'priority' => 'medium',
            'status' => 'open',
            'channel' => 'chat',
            'requester_name' => $convo['visitor_name'] ?: 'Visitante',
            'requester_email' => $convo['visitor_email'] ?: null,
            'assigned_to' => $convo['assigned_to'] ?: $this->auth->userId(),
            'public_token' => $token,
        ]);
        $this->db->update('chat_conversations', ['ticket_id' => $ticketId], 'id = :id', ['id' => $id]);
        $this->session->flash('success', 'Convertido a ticket.');
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $ticketId);
    }

    /* ──────────────── Widget config ──────────────── */

    public function widgetConfig(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.config');
        $widgets = $this->db->all('SELECT * FROM chat_widgets WHERE tenant_id = ? ORDER BY id', [$tenant->id]);
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
        $this->render('chat/widgets', [
            'title' => 'Configurar Widget',
            'widgets' => $widgets,
            'appUrl' => $appUrl,
        ]);
    }

    public function widgetUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.config');
        $this->validateCsrf();
        $id = (int)$params['id'];

        $aiMode = (string)$this->input('ai_fallback_mode', 'off');
        if (!in_array($aiMode, ['off','no_agent','always'], true)) $aiMode = 'off';
        $maxTurns = (int)$this->input('ai_max_turns', 6);
        if ($maxTurns < 1) $maxTurns = 1;
        if ($maxTurns > 20) $maxTurns = 20;

        $update = [
            'name' => trim((string)$this->input('name','Widget')),
            'primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->input('primary_color')) ? $this->input('primary_color') : '#7c5cff',
            'welcome_message' => trim((string)$this->input('welcome_message','¡Hola!')),
            'away_message' => trim((string)$this->input('away_message','')),
            'require_email' => (int)($this->input('require_email') ? 1 : 0),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'allowed_origins' => trim((string)$this->input('allowed_origins','')) ?: null,
        ];

        // Campos IA — solo si plan tiene ai_assist (los toleramos si la migración aún no corrió).
        if (\App\Core\Plan::has($tenant, 'ai_assist')) {
            $update['ai_fallback_mode'] = $aiMode;
            $update['ai_max_turns']     = $maxTurns;
            $update['ai_persona_name']  = mb_substr(trim((string)$this->input('ai_persona_name','Asistente')) ?: 'Asistente', 0, 80);
            $update['ai_system_prompt'] = trim((string)$this->input('ai_system_prompt','')) ?: null;
            $update['ai_use_kb']        = (int)($this->input('ai_use_kb') ? 1 : 0);
        }

        try {
            $this->db->update('chat_widgets', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        } catch (\Throwable $e) {
            // Si las columnas IA no existen todavía (migración pendiente), reintentamos sin ellas.
            foreach (['ai_fallback_mode','ai_max_turns','ai_persona_name','ai_system_prompt','ai_use_kb'] as $k) unset($update[$k]);
            $this->db->update('chat_widgets', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);
            $this->session->flash('warning','Configuración guardada parcialmente. Falta correr migrate_chat_ai.php para habilitar IA.');
            $this->redirect('/t/' . $tenant->slug . '/chat/widgets');
        }
        $this->session->flash('success','Widget actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/chat/widgets');
    }

    /* ──────────────── Visitor API (público) ──────────────── */

    /** Sirve el JS embebible. */
    public function widgetScript(array $params): void
    {
        $publicKey = (string)$params['publicKey'];
        $widget = $this->db->one('SELECT w.*, t.slug AS tenant_slug, t.name AS tenant_name FROM chat_widgets w JOIN tenants t ON t.id = w.tenant_id WHERE w.public_key = ? AND w.is_active = 1', [$publicKey]);
        if (!$widget) { http_response_code(404); echo '// widget not found'; return; }

        header('Content-Type: application/javascript');
        header('Cache-Control: public, max-age=300');
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
        $cfg = [
            'apiUrl' => $appUrl . '/chat-api',
            'publicKey' => $widget['public_key'],
            'color' => $widget['primary_color'],
            'welcome' => $widget['welcome_message'],
            'tenantName' => $widget['tenant_name'],
            'requireEmail' => (int)$widget['require_email'] === 1,
            'aiEnabled' => in_array((string)($widget['ai_fallback_mode'] ?? 'off'), ['no_agent','always'], true),
            'aiPersona' => trim((string)($widget['ai_persona_name'] ?? '')) ?: 'Asistente IA',
        ];
        $cfgJson = json_encode($cfg);
        echo $this->buildWidgetJs($cfgJson);
    }

    /** Inicia conversación (visitante). */
    public function visitorStart(array $params): void
    {
        $key = $this->input('public_key', '');
        $widget = $this->db->one('SELECT * FROM chat_widgets WHERE public_key = ? AND is_active = 1', [$key]);
        if (!$widget) { $this->json(['ok'=>false,'error'=>'invalid widget'], 400); }
        $this->checkOrigin($widget);

        $token = bin2hex(random_bytes(16));
        $convoId = $this->db->insert('chat_conversations', [
            'tenant_id' => (int)$widget['tenant_id'],
            'widget_id' => (int)$widget['id'],
            'visitor_token' => $token,
            'visitor_name' => trim((string)$this->input('name','')) ?: null,
            'visitor_email' => trim((string)$this->input('email','')) ?: null,
            'page_url' => substr((string)$this->input('page_url',''), 0, 500),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'status' => 'open',
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
        // Welcome message
        $this->db->insert('chat_messages', [
            'tenant_id' => (int)$widget['tenant_id'],
            'conversation_id' => $convoId,
            'sender_type' => 'system',
            'body' => $widget['welcome_message'] ?: '¡Hola! ¿En qué podemos ayudarte?',
        ]);
        $this->json(['ok' => true, 'token' => $token, 'conversation_id' => $convoId]);
    }

    public function visitorSend(array $params): void
    {
        $token = (string)$this->input('token','');
        $convo = $this->db->one('SELECT * FROM chat_conversations WHERE visitor_token = ? AND status <> "closed"', [$token]);
        if (!$convo) { $this->json(['ok'=>false,'error'=>'invalid token'], 400); }
        $widget = $this->db->one('SELECT * FROM chat_widgets WHERE id=?', [(int)$convo['widget_id']]);
        $this->checkOrigin($widget);

        $body = trim((string)$this->input('body',''));
        if ($body === '') $this->json(['ok'=>false,'error'=>'empty'], 400);

        $this->db->insert('chat_messages', [
            'tenant_id' => (int)$convo['tenant_id'],
            'conversation_id' => (int)$convo['id'],
            'sender_type' => 'visitor',
            'body' => $body,
        ]);
        $this->db->update('chat_conversations', ['last_message_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$convo['id']]);

        // Disparamos respuesta IA (si el widget está en modo no_agent / always y plan Enterprise).
        $this->maybeTriggerAi($widget, $convo);

        $this->json(['ok'=>true]);
    }

    /**
     * Si el widget tiene IA habilitada y el plan/cuota lo permite, llama a ChatAi::respond
     * de forma síncrona. La respuesta IA queda persistida como chat_message y el visitante
     * la verá en el siguiente /poll (~3s después).
     *
     * Nota: el costo de latencia (~2-4s) se absorbe en el POST del visitante. Si en el futuro
     * hay un worker async se puede mover ahí — por ahora síncrono es suficiente y predecible.
     */
    protected function maybeTriggerAi(?array $widget, array $convo): void
    {
        if (!$widget) return;
        $mode = (string)($widget['ai_fallback_mode'] ?? 'off');
        if ($mode === 'off') return;

        // Releemos la convo (los counters ai_turns / status pueden haber cambiado).
        $fresh = $this->db->one('SELECT * FROM chat_conversations WHERE id = ?', [(int)$convo['id']]);
        if (!$fresh) return;

        $decision = \App\Core\ChatAi::shouldRespond($widget, $fresh);
        if (!$decision['respond']) return;

        $tenant = \App\Core\Tenant::find((int)$fresh['tenant_id']);
        if (!$tenant) return;

        // No queremos que un fallo IA tumbe el endpoint del visitante.
        try {
            \App\Core\ChatAi::respond($tenant, $widget, $fresh);
        } catch (\Throwable $e) {
            error_log('ChatAi::respond failed: ' . $e->getMessage());
        }
    }

    public function visitorPoll(array $params): void
    {
        $token = (string)($_GET['token'] ?? '');
        $since = (int)($_GET['since'] ?? 0);
        $convo = $this->db->one('SELECT * FROM chat_conversations WHERE visitor_token = ?', [$token]);
        if (!$convo) { $this->json(['ok'=>false,'error'=>'invalid token'], 400); }
        $widget = $this->db->one('SELECT * FROM chat_widgets WHERE id=?', [(int)$convo['widget_id']]);
        $this->checkOrigin($widget);

        $msgs = $this->db->all('SELECT id, sender_type, body, is_ai, created_at FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC LIMIT 50', [(int)$convo['id'], $since]);
        $persona = trim((string)($widget['ai_persona_name'] ?? '')) ?: 'Asistente IA';
        // Show only visitor's own + agent/system messages
        $out = array_map(fn($m) => [
            'id' => (int)$m['id'],
            'sender' => $m['sender_type'],
            'body' => $m['body'],
            'is_ai' => (int)($m['is_ai'] ?? 0),
            'agent_name' => (int)($m['is_ai'] ?? 0) === 1 ? $persona : null,
            'created_at' => $m['created_at'],
        ], $msgs);
        $this->json(['ok'=>true, 'messages' => $out, 'status' => $convo['status']]);
    }

    /** Polling para agentes (lista + nuevos mensajes). */
    public function agentPoll(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('live_chat');
        $this->requireCan('chat.view');
        $convoId = (int)($_GET['conversation_id'] ?? 0);
        $since = (int)($_GET['since'] ?? 0);
        $rows = $this->db->all('SELECT m.id, m.sender_type, m.body, m.is_ai, m.created_at, u.name AS user_name FROM chat_messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.tenant_id = ? AND m.conversation_id = ? AND m.id > ? ORDER BY m.id ASC LIMIT 100', [$tenant->id, $convoId, $since]);
        $this->json(['ok' => true, 'messages' => $rows]);
    }

    protected function checkOrigin(?array $widget): void
    {
        // Allow CORS for widget API
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = $_SERVER['HTTP_ORIGIN'];
            $allowed = $widget['allowed_origins'] ?? '';
            if ($allowed) {
                $list = array_map('trim', explode(',', $allowed));
                if (!in_array($origin, $list, true) && !in_array('*', $list, true)) {
                    http_response_code(403); echo 'origin not allowed'; exit;
                }
            }
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
    }

    protected function buildWidgetJs(string $cfgJson): string
    {
        $body = <<<'JS'
  if (window.__kydChatLoaded) return;
  window.__kydChatLoaded = true;

  var apiBase = cfg.apiUrl;
  var STORE_KEY = 'kydesk_chat_token_' + cfg.publicKey;
  var lastId = 0; var pollTimer = null;

  function hexToRgb(h) {
    var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(h || '');
    return m ? [parseInt(m[1],16), parseInt(m[2],16), parseInt(m[3],16)] : [124,92,255];
  }
  function rgbToCss(rgb, a) { return 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + (a==null?1:a) + ')'; }
  function luminance(r,g,b){ return 0.2126*r + 0.7152*g + 0.0722*b; }
  function readableOn(rgb) { return luminance(rgb[0],rgb[1],rgb[2]) > 160 ? '#1a1a1a' : '#ffffff'; }

  function detectHostTheme() {
    try {
      var el = document.body || document.documentElement;
      while (el) {
        var bg = window.getComputedStyle(el).backgroundColor;
        var m = /rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)(?:[,\s/]+([\d.]+))?\s*\)/.exec(bg);
        if (m) {
          var alpha = m[4] === undefined ? 1 : parseFloat(m[4]);
          if (alpha > 0.05) {
            var lum = luminance(+m[1], +m[2], +m[3]);
            return lum < 128 ? 'dark' : 'light';
          }
        }
        el = el.parentElement;
      }
    } catch(e) {}
    try {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
    } catch(e) {}
    return 'light';
  }

  function buildPalette(theme, color) {
    var primaryRgb = hexToRgb(color);
    var onPrimary = readableOn(primaryRgb);
    if (theme === 'dark') {
      return {
        primary: color, onPrimary: onPrimary,
        panelBg: '#1c1d24', panelText: '#f3f3f6',
        listBg: '#15161c',
        msgBg: '#272832', msgBorder: 'rgba(255,255,255,0.08)', msgText: '#f3f3f6',
        inputBg: '#272832', inputText: '#f3f3f6', inputBorder: 'rgba(255,255,255,0.12)',
        muted: '#9c9caa', divider: 'rgba(255,255,255,0.08)',
        focusRing: rgbToCss(primaryRgb, 0.35)
      };
    }
    return {
      primary: color, onPrimary: onPrimary,
      panelBg: '#ffffff', panelText: '#1a1a1a',
      listBg: '#fafafb',
      msgBg: '#ffffff', msgBorder: '#ececef', msgText: '#1a1a1a',
      inputBg: '#ffffff', inputText: '#1a1a1a', inputBorder: '#ececef',
      muted: '#8e8e9a', divider: '#ececef',
      focusRing: rgbToCss(primaryRgb, 0.25)
    };
  }

  function init() {
    var theme = detectHostTheme();
    var p = buildPalette(theme, cfg.color);

    var host = document.createElement('div');
    host.id = 'kyd-chat-widget-host';
    host.style.cssText = 'all: initial; position: static;';
    document.body.appendChild(host);
    var root = host.attachShadow ? host.attachShadow({mode:'open'}) : host;

    var css = `
      :host, .kyd-root { all: initial; }
      .kyd-root, .kyd-root * { box-sizing: border-box; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
      .kyd-bubble { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; border-radius: 50%; background: ${p.primary}; color: ${p.onPrimary}; display: grid; place-items: center; cursor: pointer; box-shadow: 0 8px 24px -6px rgba(0,0,0,.25); z-index: 2147483647; transition: transform .2s; border: 0; padding: 0; }
      .kyd-bubble:hover { transform: scale(1.05); }
      .kyd-bubble svg { width: 28px; height: 28px; pointer-events: none; }
      .kyd-panel { position: fixed; bottom: 90px; right: 20px; width: 360px; max-width: calc(100vw - 40px); height: 520px; max-height: calc(100vh - 110px); background: ${p.panelBg}; color: ${p.panelText}; border-radius: 18px; box-shadow: 0 20px 50px -12px rgba(0,0,0,.35); z-index: 2147483647; display: flex; flex-direction: column; overflow: hidden; opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .25s, transform .25s; }
      .kyd-panel.open { opacity: 1; transform: translateY(0); pointer-events: auto; }
      .kyd-header { padding: 16px 44px 16px 18px; background: ${p.primary}; color: ${p.onPrimary}; position: relative; flex: 0 0 auto; }
      .kyd-header strong { font-weight: 700; font-size: 15px; display: block; }
      .kyd-header .kyd-sub { font-size: 11.5px; opacity: .9; margin-top: 2px; display: block; }
      .kyd-close { position: absolute; top: 12px; right: 12px; width: 28px; height: 28px; background: transparent; border: 0; color: ${p.onPrimary}; opacity: .85; cursor: pointer; font-size: 20px; line-height: 1; padding: 0; border-radius: 6px; }
      .kyd-close:hover { opacity: 1; background: rgba(0,0,0,0.15); }
      .kyd-msgs { flex: 1 1 auto; overflow-y: auto; padding: 14px; background: ${p.listBg}; display: flex; flex-direction: column; gap: 8px; }
      .kyd-msg-wrap { display: flex; flex-direction: column; gap: 2px; max-width: 85%; align-self: flex-start; }
      .kyd-msg-wrap.visitor { align-self: flex-end; align-items: flex-end; }
      .kyd-msg-meta { font-size: 10.5px; color: ${p.muted}; padding: 0 4px; display: flex; align-items: center; gap: 4px; }
      .kyd-msg-meta .kyd-ai-dot { width: 6px; height: 6px; border-radius: 50%; background: ${p.primary}; display: inline-block; }
      .kyd-msg { padding: 8px 12px; border-radius: 14px; font-size: 13.5px; word-wrap: break-word; line-height: 1.45; white-space: pre-wrap; }
      .kyd-msg.agent, .kyd-msg.system { background: ${p.msgBg}; color: ${p.msgText}; border: 1px solid ${p.msgBorder}; align-self: flex-start; }
      .kyd-msg.visitor { background: ${p.primary}; color: ${p.onPrimary}; align-self: flex-end; }
      .kyd-msg.system { font-size: 12px; opacity: .85; font-style: italic; }
      .kyd-msg.ai { background: ${p.msgBg}; border: 1px solid ${p.primary}; }
      .kyd-form { padding: 10px; border-top: 1px solid ${p.divider}; display: flex; gap: 8px; background: ${p.panelBg}; flex: 0 0 auto; }
      .kyd-form textarea { flex: 1; border: 1px solid ${p.inputBorder}; background: ${p.inputBg}; color: ${p.inputText}; border-radius: 10px; padding: 9px 12px; font-size: 13.5px; outline: none; resize: none; font-family: inherit; line-height: 1.4; min-height: 38px; max-height: 120px; }
      .kyd-form textarea:focus { border-color: ${p.primary}; box-shadow: 0 0 0 3px ${p.focusRing}; }
      .kyd-form textarea::placeholder { color: ${p.muted}; opacity: 1; }
      .kyd-form button { background: ${p.primary}; color: ${p.onPrimary}; border: 0; border-radius: 10px; padding: 0 14px; cursor: pointer; font-weight: 600; font-size: 13px; min-height: 38px; }
      .kyd-prestart { padding: 18px; overflow-y: auto; }
      .kyd-prestart h3 { font-size: 14px; font-weight: 600; margin: 0 0 6px 0; color: ${p.panelText}; }
      .kyd-prestart input { width: 100%; padding: 10px 14px; border: 1px solid ${p.inputBorder}; background: ${p.inputBg}; color: ${p.inputText}; border-radius: 10px; font-size: 13.5px; margin-top: 8px; outline: none; font-family: inherit; }
      .kyd-prestart input:focus { border-color: ${p.primary}; box-shadow: 0 0 0 3px ${p.focusRing}; }
      .kyd-prestart input::placeholder { color: ${p.muted}; opacity: 1; }
      .kyd-prestart button { width: 100%; background: ${p.primary}; color: ${p.onPrimary}; border: 0; border-radius: 10px; padding: 11px; cursor: pointer; font-weight: 600; font-size: 13.5px; margin-top: 14px; }
      .kyd-msgs::-webkit-scrollbar, .kyd-prestart::-webkit-scrollbar { width: 8px; }
      .kyd-msgs::-webkit-scrollbar-thumb, .kyd-prestart::-webkit-scrollbar-thumb { background: ${p.inputBorder}; border-radius: 4px; }
      @media (max-width: 480px) {
        .kyd-panel { right: 10px; left: 10px; bottom: 86px; width: auto; max-width: none; height: calc(100vh - 110px); }
        .kyd-bubble { right: 14px; bottom: 14px; }
      }
    `;

    var styleEl = document.createElement('style');
    styleEl.textContent = css;
    root.appendChild(styleEl);

    var rootDiv = document.createElement('div');
    rootDiv.className = 'kyd-root';
    root.appendChild(rootDiv);

    var bubble = document.createElement('button');
    bubble.type = 'button';
    bubble.className = 'kyd-bubble';
    bubble.setAttribute('aria-label', 'Abrir chat');
    bubble.innerHTML = '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
    rootDiv.appendChild(bubble);

    var panel = document.createElement('div');
    panel.className = 'kyd-panel';
    rootDiv.appendChild(panel);

    bubble.onclick = function() {
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) render(panel);
      else stopPolling();
    };

    function api(path, body, method) {
      method = method || 'POST';
      var opts = { method: method, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } };
      if (body) {
        opts.body = Object.keys(body).map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(body[k]); }).join('&');
      }
      return fetch(apiBase + path, opts).then(function(r){ return r.json(); });
    }

    function getToken() { try { return localStorage.getItem(STORE_KEY); } catch(e) { return null; } }
    function setToken(t) { try { localStorage.setItem(STORE_KEY, t); } catch(e) {} }

    function stopPolling() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    function render(panelEl) {
      var token = getToken();
      panelEl.innerHTML = '';

      var header = document.createElement('div');
      header.className = 'kyd-header';
      var hStrong = document.createElement('strong'); hStrong.textContent = cfg.tenantName || 'Chat';
      var hSub = document.createElement('span'); hSub.className = 'kyd-sub'; hSub.textContent = cfg.welcome || '';
      var hClose = document.createElement('button'); hClose.type = 'button'; hClose.className = 'kyd-close'; hClose.setAttribute('aria-label', 'Cerrar'); hClose.textContent = '×';
      header.appendChild(hStrong); header.appendChild(hSub); header.appendChild(hClose);
      panelEl.appendChild(header);
      hClose.onclick = function() { panel.classList.remove('open'); stopPolling(); };

      if (!token) {
        var pre = document.createElement('div'); pre.className = 'kyd-prestart';
        var h3 = document.createElement('h3'); h3.textContent = 'Empezá una conversación';
        var nameInput = document.createElement('input'); nameInput.type = 'text'; nameInput.placeholder = 'Tu nombre';
        pre.appendChild(h3); pre.appendChild(nameInput);
        var emailInput = null;
        if (cfg.requireEmail) {
          emailInput = document.createElement('input'); emailInput.type = 'email'; emailInput.placeholder = 'Tu email';
          pre.appendChild(emailInput);
        }
        var startBtn = document.createElement('button'); startBtn.type = 'button'; startBtn.textContent = 'Iniciar chat';
        pre.appendChild(startBtn);
        panelEl.appendChild(pre);
        startBtn.onclick = function() {
          api('/start', {
            public_key: cfg.publicKey,
            name: nameInput.value || '',
            email: emailInput ? emailInput.value : '',
            page_url: location.href
          }).then(function(r){ if (r && r.ok) { setToken(r.token); render(panelEl); } });
        };
        setTimeout(function(){ try { nameInput.focus(); } catch(e){} }, 50);
        return;
      }

      var msgsBox = document.createElement('div'); msgsBox.className = 'kyd-msgs';
      panelEl.appendChild(msgsBox);
      var form = document.createElement('form'); form.className = 'kyd-form';
      var textarea = document.createElement('textarea'); textarea.rows = 1; textarea.placeholder = 'Escribí un mensaje...';
      var sendBtn = document.createElement('button'); sendBtn.type = 'submit'; sendBtn.textContent = 'Enviar';
      form.appendChild(textarea); form.appendChild(sendBtn);
      panelEl.appendChild(form);

      form.onsubmit = function(e) {
        e.preventDefault();
        var body = textarea.value.trim(); if (!body) return;
        api('/send', { token: token, body: body }).then(function(r){
          if (r && r.ok) { textarea.value = ''; poll(msgsBox); }
        });
      };
      textarea.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          if (typeof form.requestSubmit === 'function') form.requestSubmit();
          else form.dispatchEvent(new Event('submit', {cancelable:true}));
        }
      });
      setTimeout(function(){ try { textarea.focus(); } catch(e){} }, 50);

      lastId = 0;
      poll(msgsBox);
      stopPolling();
      pollTimer = setInterval(function(){
        if (!msgsBox.isConnected) { stopPolling(); return; }
        poll(msgsBox);
      }, 3000);
    }

    function renderMessage(m, msgsBox) {
      var isVisitor = m.sender === 'visitor';
      var isAi = m.is_ai === 1 || m.is_ai === '1' || m.is_ai === true;

      // Mensajes system: una sola línea centrada (sin wrapper).
      if (m.sender === 'system') {
        var sys = document.createElement('div');
        sys.className = 'kyd-msg system';
        sys.textContent = m.body;
        msgsBox.appendChild(sys);
        return;
      }

      var wrap = document.createElement('div');
      wrap.className = 'kyd-msg-wrap' + (isVisitor ? ' visitor' : '');

      // Etiqueta de remitente para mensajes del agente/IA.
      if (!isVisitor) {
        var meta = document.createElement('div');
        meta.className = 'kyd-msg-meta';
        if (isAi) {
          var dot = document.createElement('span'); dot.className = 'kyd-ai-dot';
          var lbl = document.createElement('span'); lbl.textContent = (m.agent_name || cfg.aiPersona || 'Asistente IA') + ' · IA';
          meta.appendChild(dot); meta.appendChild(lbl);
        } else if (m.agent_name) {
          meta.textContent = m.agent_name;
        } else {
          meta.textContent = 'Agente';
        }
        wrap.appendChild(meta);
      }

      var el = document.createElement('div');
      el.className = 'kyd-msg ' + m.sender + (isAi ? ' ai' : '');
      el.textContent = m.body;
      wrap.appendChild(el);
      msgsBox.appendChild(wrap);
    }

    function poll(msgsBox) {
      var token = getToken(); if (!token) return;
      fetch(apiBase + '/poll?token=' + encodeURIComponent(token) + '&since=' + lastId).then(function(r){ return r.json(); }).then(function(r){
        if (!r || !r.ok) return;
        if (!msgsBox || !msgsBox.isConnected) return;
        r.messages.forEach(function(m) {
          if (m.id <= lastId) return;
          lastId = m.id;
          renderMessage(m, msgsBox);
        });
        msgsBox.scrollTop = msgsBox.scrollHeight;
      }).catch(function(){});
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
JS;
        return '(function(cfg){' . $body . '})(' . $cfgJson . ');';
    }
}
