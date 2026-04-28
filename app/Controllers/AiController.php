<?php
namespace App\Controllers;

use App\Core\Controller;

class AiController extends Controller
{
    public const ACTIONS = ['suggest_reply','summarize','categorize','sentiment','translate'];

    public function settings(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('ai_assist');
        $this->requireCan('ai.config');

        $cfg = $this->db->one('SELECT a.*, sa.name AS admin_name FROM ai_settings a LEFT JOIN super_admins sa ON sa.id = a.assigned_by_admin WHERE a.tenant_id = ?', [$tenant->id])
            ?: ['tenant_id'=>$tenant->id,'is_assigned'=>0,'is_enabled'=>0,'monthly_quota'=>0,'used_this_month'=>0,'suggest_replies'=>1,'auto_summarize'=>0,'auto_categorize'=>0,'detect_sentiment'=>0,'auto_translate'=>0,'target_language'=>'es','assigned_at'=>null,'admin_name'=>null,'model'=>null,'provider'=>'anthropic'];

        $usage = $this->db->all(
            "SELECT action, COUNT(*) AS cnt, SUM(tokens_in) AS tokens_in, SUM(tokens_out) AS tokens_out
             FROM ai_completions WHERE tenant_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY action",
            [$tenant->id]
        );
        $logs = $this->db->all(
            'SELECT c.*, t.code AS ticket_code, u.name AS user_name FROM ai_completions c LEFT JOIN tickets t ON t.id = c.ticket_id LEFT JOIN users u ON u.id = c.user_id WHERE c.tenant_id = ? ORDER BY c.created_at DESC LIMIT 30',
            [$tenant->id]
        );

        $this->render('ai/settings', [
            'title' => 'IA Asistente',
            'cfg' => $cfg,
            'usage' => $usage,
            'logs' => $logs,
        ]);
    }

    /** El tenant solo puede toggle qué acciones usa y el idioma destino. */
    public function settingsUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('ai_assist');
        $this->requireCan('ai.config');
        $this->validateCsrf();

        $assigned = $this->db->one('SELECT * FROM ai_settings WHERE tenant_id = ?', [$tenant->id]);
        if (!$assigned || !(int)$assigned['is_assigned']) {
            $this->session->flash('error', 'IA no está asignada a este workspace. Contactá al administrador de Kydesk.');
            $this->redirect('/t/' . $tenant->slug . '/ai');
        }

        $this->db->update('ai_settings', [
            'auto_categorize'  => (int)($this->input('auto_categorize') ? 1 : 0),
            'auto_summarize'   => (int)($this->input('auto_summarize') ? 1 : 0),
            'suggest_replies'  => (int)($this->input('suggest_replies') ? 1 : 0),
            'detect_sentiment' => (int)($this->input('detect_sentiment') ? 1 : 0),
            'auto_translate'   => (int)($this->input('auto_translate') ? 1 : 0),
            'target_language'  => trim((string)$this->input('target_language','es')),
            'is_enabled'       => (int)($this->input('is_enabled') ? 1 : 0),
        ], 'tenant_id = :tid', ['tid' => $tenant->id]);
        $this->session->flash('success','Preferencias guardadas.');
        $this->redirect('/t/' . $tenant->slug . '/ai');
    }

    public function run(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('ai_assist');
        $this->requireCan('ai.use');
        $this->validateCsrf();

        $action = (string)$this->input('action', '');
        if (!in_array($action, self::ACTIONS, true)) $this->json(['ok'=>false, 'error'=>'Acción inválida'], 400);
        $ticketId = ((int)$this->input('ticket_id', 0)) ?: null;

        $cfg = $this->db->one('SELECT * FROM ai_settings WHERE tenant_id = ?', [$tenant->id]);
        $globalCfg = $this->loadGlobalAiSettings();

        if (($globalCfg['ai_global_enabled'] ?? '0') !== '1') $this->json(['ok'=>false, 'error'=>'IA globalmente deshabilitada'], 503);
        if (!$cfg || !(int)$cfg['is_assigned']) $this->json(['ok'=>false, 'error'=>'IA no está asignada a este workspace'], 403);
        if (!(int)$cfg['is_enabled']) $this->json(['ok'=>false, 'error'=>'IA pausada por el workspace'], 400);
        if (empty($globalCfg['ai_api_key'])) $this->json(['ok'=>false, 'error'=>'Super admin no configuró la API key'], 503);
        if ((int)$cfg['monthly_quota'] > 0 && (int)$cfg['used_this_month'] >= (int)$cfg['monthly_quota']) {
            $this->json(['ok'=>false,'error'=>'Cuota mensual de requests alcanzada. Contactá al admin.'], 429);
        }
        $tokenQuota = (int)($cfg['token_quota_monthly'] ?? 0);
        if ($tokenQuota > 0) {
            $tokensUsed = (int)($cfg['tokens_in_this_month'] ?? 0) + (int)($cfg['tokens_out_this_month'] ?? 0);
            if ($tokensUsed >= $tokenQuota) {
                $this->json(['ok'=>false,'error'=>'Cuota mensual de tokens alcanzada. Contactá al admin.'], 429);
            }
        }

        $context = $ticketId ? $this->ticketContext($tenant->id, $ticketId) : null;
        $prompt = $this->buildPrompt($action, (string)$this->input('input',''), $context, $cfg);

        $start = microtime(true);
        $apiKey = $globalCfg['ai_api_key'];
        $model = $cfg['model'] ?: ($globalCfg['ai_default_model'] ?? 'claude-haiku-4-5');
        $result = $this->callAnthropicAPI($apiKey, $model, $prompt);
        $duration = (int)((microtime(true) - $start) * 1000);

        $this->db->insert('ai_completions', [
            'tenant_id'  => $tenant->id,
            'user_id'    => $this->auth->userId(),
            'ticket_id'  => $ticketId,
            'action'     => $action,
            'input_text' => mb_substr($prompt['user'], 0, 5000),
            'output_text'=> $result['ok'] ? $result['text'] : null,
            'tokens_in'  => $result['tokens_in'] ?? 0,
            'tokens_out' => $result['tokens_out'] ?? 0,
            'duration_ms'=> $duration,
            'status'     => $result['ok'] ? 'ok' : 'error',
            'error'      => $result['ok'] ? null : substr((string)($result['error'] ?? ''), 0, 500),
        ]);
        if ($result['ok']) {
            $tin  = (int)($result['tokens_in']  ?? 0);
            $tout = (int)($result['tokens_out'] ?? 0);
            try {
                $this->db->run(
                    'UPDATE ai_settings
                       SET used_this_month       = used_this_month + 1,
                           tokens_in_this_month  = tokens_in_this_month + ?,
                           tokens_out_this_month = tokens_out_this_month + ?
                     WHERE tenant_id = ?',
                    [$tin, $tout, $tenant->id]
                );
            } catch (\Throwable $e) {
                // Fallback si las columnas de tokens aún no fueron migradas
                $this->db->run('UPDATE ai_settings SET used_this_month = used_this_month + 1 WHERE tenant_id = ?', [$tenant->id]);
            }
            if ($ticketId) $this->persistOnTicket($tenant->id, $ticketId, $action, $result['text']);
        }

        $this->json($result + ['action' => $action]);
    }

    /* ─────── helpers ─────── */

    protected function loadGlobalAiSettings(): array
    {
        $rows = $this->db->all("SELECT `key`,`value` FROM saas_settings WHERE `key` LIKE 'ai_%'");
        $map = [];
        foreach ($rows as $r) $map[$r['key']] = $r['value'];
        return $map + [
            'ai_provider' => 'anthropic',
            'ai_api_key' => '',
            'ai_default_model' => 'claude-haiku-4-5',
            'ai_default_quota' => '1000',
            'ai_global_enabled' => '0',
        ];
    }

    protected function ticketContext(int $tenantId, int $ticketId): ?array
    {
        $t = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$ticketId, $tenantId]);
        if (!$t) return null;
        $comments = $this->db->all('SELECT body, author_name, user_id, is_internal, created_at FROM ticket_comments WHERE ticket_id = ? ORDER BY created_at ASC LIMIT 30', [$ticketId]);
        return [
            'subject' => $t['subject'],
            'description' => $t['description'],
            'priority' => $t['priority'],
            'status' => $t['status'],
            'requester' => $t['requester_name'] ?? '',
            'comments' => $comments,
        ];
    }

    protected function buildPrompt(string $action, string $input, ?array $ctx, array $cfg): array
    {
        $ctxText = '';
        if ($ctx) {
            $ctxText = "Asunto: {$ctx['subject']}\n";
            $ctxText .= "Descripción: {$ctx['description']}\n";
            $ctxText .= "Prioridad: {$ctx['priority']} · Estado: {$ctx['status']}\n";
            if (!empty($ctx['comments'])) {
                $ctxText .= "\nHistorial de comentarios:\n";
                foreach ($ctx['comments'] as $c) {
                    if ($c['is_internal']) continue;
                    $who = $c['user_id'] ? 'Agente' : ($c['author_name'] ?: 'Cliente');
                    $ctxText .= "[$who] " . substr($c['body'], 0, 800) . "\n";
                }
            }
        }
        $lang = $cfg['target_language'] ?? 'es';

        $prompts = [
            'suggest_reply' => [
                'system' => "Sos un agente de soporte profesional. Redactá una respuesta clara, empática y útil al cliente, en idioma $lang. Máximo 4 párrafos. No inventes información que no esté en el contexto. Usá tono cordial.",
                'user' => "Contexto del ticket:\n$ctxText\n\nRedactá una respuesta apropiada al cliente.",
            ],
            'summarize' => [
                'system' => "Sos un asistente que resume tickets de soporte. Devolvé un resumen ejecutivo en $lang de 2-4 oraciones, claro y orientado a acción.",
                'user' => "Resumí este ticket:\n$ctxText",
            ],
            'categorize' => [
                'system' => "Clasificá tickets de soporte. Respondé SOLO con un JSON con keys: category (string corta), priority (low|medium|high|urgent), tags (array de strings).",
                'user' => "Clasificá este ticket:\n$ctxText",
            ],
            'sentiment' => [
                'system' => "Detectá el sentiment de un ticket. Respondé SOLO con un JSON: {\"sentiment\": \"positive|neutral|negative|urgent\", \"reason\": \"...\"}",
                'user' => "Analizá el sentiment:\n$ctxText",
            ],
            'translate' => [
                'system' => "Traducí texto al idioma $lang manteniendo el tono. Devolvé solo la traducción sin comentarios.",
                'user' => $input ?: $ctxText,
            ],
        ];
        return $prompts[$action] ?? $prompts['summarize'];
    }

    protected function callAnthropicAPI(string $apiKey, string $model, array $prompt): array
    {
        $payload = [
            'model' => $model,
            'max_tokens' => 1024,
            'system' => $prompt['system'],
            'messages' => [
                ['role' => 'user', 'content' => $prompt['user']],
            ],
        ];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) return ['ok' => false, 'error' => 'curl: ' . $err];
        $body = json_decode($resp, true);
        if ($code >= 400) return ['ok' => false, 'error' => $body['error']['message'] ?? "HTTP $code"];
        $text = '';
        if (!empty($body['content']) && is_array($body['content'])) {
            foreach ($body['content'] as $c) {
                if (($c['type'] ?? '') === 'text') $text .= $c['text'];
            }
        }
        return [
            'ok' => true,
            'text' => $text,
            'tokens_in' => (int)($body['usage']['input_tokens'] ?? 0),
            'tokens_out' => (int)($body['usage']['output_tokens'] ?? 0),
        ];
    }

    protected function persistOnTicket(int $tenantId, int $ticketId, string $action, string $output): void
    {
        try {
            if ($action === 'summarize') {
                $this->db->update('tickets', ['ai_summary' => $output], 'id=? AND tenant_id=?', [$ticketId, $tenantId]);
            }
            if ($action === 'sentiment') {
                $j = json_decode($output, true);
                $val = $j['sentiment'] ?? null;
                if (in_array($val, ['positive','neutral','negative','urgent'], true)) {
                    $this->db->update('tickets', ['ai_sentiment' => $val], 'id=? AND tenant_id=?', [$ticketId, $tenantId]);
                }
            }
        } catch (\Throwable $e) { /* swallow */ }
    }
}
