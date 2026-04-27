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

        $this->render('chat/show', [
            'title' => 'Chat #' . $id,
            'convo' => $convo,
            'messages' => $messages,
            'users' => $users,
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
        ], 'id = :id', ['id' => $id]);

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if ($isAjax) $this->json(['ok'=>true]);
        else $this->redirect('/t/' . $tenant->slug . '/chat/' . $id);
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
        $this->db->update('chat_widgets', [
            'name' => trim((string)$this->input('name','Widget')),
            'primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->input('primary_color')) ? $this->input('primary_color') : '#7c5cff',
            'welcome_message' => trim((string)$this->input('welcome_message','¡Hola!')),
            'away_message' => trim((string)$this->input('away_message','')),
            'require_email' => (int)($this->input('require_email') ? 1 : 0),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'allowed_origins' => trim((string)$this->input('allowed_origins','')) ?: null,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
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
        $this->json(['ok'=>true]);
    }

    public function visitorPoll(array $params): void
    {
        $token = (string)($_GET['token'] ?? '');
        $since = (int)($_GET['since'] ?? 0);
        $convo = $this->db->one('SELECT * FROM chat_conversations WHERE visitor_token = ?', [$token]);
        if (!$convo) { $this->json(['ok'=>false,'error'=>'invalid token'], 400); }
        $widget = $this->db->one('SELECT * FROM chat_widgets WHERE id=?', [(int)$convo['widget_id']]);
        $this->checkOrigin($widget);

        $msgs = $this->db->all('SELECT id, sender_type, body, created_at FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC LIMIT 50', [(int)$convo['id'], $since]);
        // Show only visitor's own + agent/system messages
        $out = array_map(fn($m) => [
            'id' => (int)$m['id'],
            'sender' => $m['sender_type'],
            'body' => $m['body'],
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
        $rows = $this->db->all('SELECT m.id, m.sender_type, m.body, m.created_at, u.name AS user_name FROM chat_messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.tenant_id = ? AND m.conversation_id = ? AND m.id > ? ORDER BY m.id ASC LIMIT 100', [$tenant->id, $convoId, $since]);
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
        return <<<JS
(function(){
  var cfg = $cfgJson;
  var apiBase = cfg.apiUrl;
  var STORE_KEY = 'kydesk_chat_token_' + cfg.publicKey;
  var lastId = 0; var pollTimer = null; var conversation = null;

  // Styles
  var css = `
  .kyd-chat-bubble { position:fixed; bottom:20px; right:20px; width:60px; height:60px; border-radius:50%; background:\${cfg.color}; color:white; display:grid; place-items:center; cursor:pointer; box-shadow:0 8px 24px -6px rgba(0,0,0,.25); z-index:99999; transition:transform .2s; }
  .kyd-chat-bubble:hover { transform:scale(1.05); }
  .kyd-chat-bubble svg { width:28px; height:28px; }
  .kyd-chat-panel { position:fixed; bottom:90px; right:20px; width:360px; max-width:calc(100vw - 40px); height:520px; max-height:calc(100vh - 110px); background:white; border-radius:18px; box-shadow:0 20px 50px -12px rgba(0,0,0,.25); z-index:99999; display:flex; flex-direction:column; overflow:hidden; font-family:system-ui,-apple-system,sans-serif; opacity:0; transform:translateY(10px); pointer-events:none; transition:all .25s; }
  .kyd-chat-panel.open { opacity:1; transform:translateY(0); pointer-events:auto; }
  .kyd-chat-header { padding:16px 18px; background:\${cfg.color}; color:white; }
  .kyd-chat-header strong { font-weight:700; font-size:15px; }
  .kyd-chat-header div { font-size:11.5px; opacity:.85; margin-top:2px; }
  .kyd-chat-close { position:absolute; top:14px; right:14px; cursor:pointer; opacity:.8; }
  .kyd-chat-msgs { flex:1; overflow-y:auto; padding:14px; background:#fafafb; display:flex; flex-direction:column; gap:8px; }
  .kyd-msg { padding:8px 12px; border-radius:14px; font-size:13.5px; max-width:80%; word-wrap:break-word; line-height:1.45; }
  .kyd-msg.agent, .kyd-msg.system { background:white; border:1px solid #ececef; align-self:flex-start; }
  .kyd-msg.visitor { background:\${cfg.color}; color:white; align-self:flex-end; }
  .kyd-chat-form { padding:10px; border-top:1px solid #ececef; display:flex; gap:8px; background:white; }
  .kyd-chat-form input, .kyd-chat-form textarea { flex:1; border:1px solid #ececef; border-radius:10px; padding:8px 12px; font-size:13.5px; outline:none; resize:none; font-family:inherit; }
  .kyd-chat-form input:focus, .kyd-chat-form textarea:focus { border-color:\${cfg.color}; }
  .kyd-chat-form button { background:\${cfg.color}; color:white; border:none; border-radius:10px; padding:0 14px; cursor:pointer; font-weight:600; font-size:13px; }
  .kyd-prestart { padding:18px; }
  .kyd-prestart input { width:100%; padding:10px 14px; border:1px solid #ececef; border-radius:10px; font-size:13.5px; margin-top:8px; outline:none; }
  .kyd-prestart input:focus { border-color:\${cfg.color}; }
  .kyd-prestart button { width:100%; background:\${cfg.color}; color:white; border:none; border-radius:10px; padding:11px; cursor:pointer; font-weight:600; font-size:13.5px; margin-top:14px; }
  .kyd-typing { display:inline-block; padding:8px 12px; background:white; border:1px solid #ececef; border-radius:14px; align-self:flex-start; font-size:12px; color:#8e8e9a; }
  `;
  var style = document.createElement('style'); style.textContent = css; document.head.appendChild(style);

  // Bubble
  var bubble = document.createElement('div'); bubble.className='kyd-chat-bubble';
  bubble.innerHTML = '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
  document.body.appendChild(bubble);

  var panel = document.createElement('div'); panel.className='kyd-chat-panel';
  document.body.appendChild(panel);

  bubble.onclick = function() { panel.classList.toggle('open'); if (panel.classList.contains('open')) render(); };

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

  function render() {
    var token = getToken();
    panel.innerHTML = '';
    var header = document.createElement('div'); header.className='kyd-chat-header';
    header.innerHTML = '<strong>'+ cfg.tenantName +'</strong><div>'+ cfg.welcome +'</div><span class="kyd-chat-close">×</span>';
    panel.appendChild(header);
    header.querySelector('.kyd-chat-close').onclick = function() { panel.classList.remove('open'); };

    if (!token) {
      var pre = document.createElement('div'); pre.className='kyd-prestart';
      pre.innerHTML = '<div style="font-size:14px;font-weight:600;margin-bottom:6px">Empezá una conversación</div>'
        + '<input id="kyd-name" placeholder="Tu nombre" />'
        + (cfg.requireEmail ? '<input id="kyd-email" type="email" placeholder="Tu email" />' : '')
        + '<button id="kyd-start">Iniciar chat</button>';
      panel.appendChild(pre);
      document.getElementById('kyd-start').onclick = function() {
        var name = (document.getElementById('kyd-name')||{}).value || '';
        var email = (document.getElementById('kyd-email')||{}).value || '';
        api('/start', { public_key: cfg.publicKey, name: name, email: email, page_url: location.href }).then(function(r){
          if (r.ok) { setToken(r.token); render(); }
        });
      };
      return;
    }

    var msgs = document.createElement('div'); msgs.className='kyd-chat-msgs'; msgs.id='kyd-msgs';
    panel.appendChild(msgs);
    var form = document.createElement('form'); form.className='kyd-chat-form';
    form.innerHTML = '<textarea id="kyd-input" rows="1" placeholder="Escribí un mensaje..."></textarea><button type="submit">Enviar</button>';
    panel.appendChild(form);
    form.onsubmit = function(e) {
      e.preventDefault();
      var input = document.getElementById('kyd-input');
      var body = input.value.trim(); if (!body) return;
      api('/send', { token: token, body: body }).then(function(r){
        if (r.ok) { input.value=''; poll(); }
      });
    };
    var input = form.querySelector('textarea');
    input.addEventListener('keydown', function(e){ if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); form.dispatchEvent(new Event('submit')); } });

    lastId = 0;
    poll();
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(poll, 3000);
  }

  function poll() {
    var token = getToken(); if (!token) return;
    fetch(apiBase + '/poll?token=' + encodeURIComponent(token) + '&since=' + lastId).then(function(r){ return r.json(); }).then(function(r){
      if (!r.ok) return;
      var box = document.getElementById('kyd-msgs'); if (!box) return;
      r.messages.forEach(function(m) {
        if (m.id <= lastId) return;
        lastId = m.id;
        var el = document.createElement('div'); el.className='kyd-msg ' + m.sender;
        el.textContent = m.body;
        box.appendChild(el);
      });
      box.scrollTop = box.scrollHeight;
    });
  }
})();
JS;
    }
}
