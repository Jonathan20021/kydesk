<?php
namespace App\Core;

/**
 * Despacha eventos a las integraciones activas de un tenant.
 * Cada provider tiene su handler que normaliza el payload al formato esperado.
 */
class IntegrationDispatcher
{
    /**
     * Envía un evento a todas las integraciones activas del tenant que lo tengan suscrito.
     */
    public static function dispatch(string $event, int $tenantId, ?string $entity, ?int $entityId, array $payload): void
    {
        $db = Application::get()->db ?? null;
        if (!$db) return;

        try {
            $integrations = $db->all(
                "SELECT * FROM integrations WHERE tenant_id = ? AND is_active = 1",
                [$tenantId]
            );
        } catch (\Throwable $_e) { return; }
        if (!$integrations) return;

        foreach ($integrations as $i) {
            $events = json_decode((string)$i['events'], true) ?: [];
            // events array vacío significa "todos"
            if (!empty($events) && !in_array($event, $events, true) && !in_array('*', $events, true)) continue;

            $providerDef = IntegrationRegistry::get((string)$i['provider']);
            if (!$providerDef) continue;

            $config = json_decode((string)$i['config'], true) ?: [];
            $context = [
                'event' => $event,
                'entity' => $entity,
                'entity_id' => $entityId,
                'tenant_id' => $tenantId,
                'data' => $payload,
                'occurred_at' => date('c'),
                'integration_name' => $i['name'],
            ];

            $start = microtime(true);
            $result = self::sendViaHandler($providerDef['handler'] ?? '', $providerDef, $config, $context);
            $latency = (int)round((microtime(true) - $start) * 1000);

            self::logResult($db, (int)$i['id'], $tenantId, $event, $result, $latency);
        }
    }

    /**
     * Envío de prueba: devuelve resultado sin loguear (o loguea como test).
     */
    public static function testSend(array $providerDef, array $config, ?int $integrationId = null, ?int $tenantId = null): array
    {
        $context = [
            'event' => 'test.ping',
            'entity' => null,
            'entity_id' => null,
            'tenant_id' => $tenantId ?? 0,
            'data' => [
                'message' => 'Esta es una prueba de conexión desde Kydesk.',
                'tested_at' => date('c'),
            ],
            'occurred_at' => date('c'),
            'integration_name' => $providerDef['name'] ?? 'Test',
            'is_test' => true,
        ];
        $start = microtime(true);
        $result = self::sendViaHandler($providerDef['handler'] ?? '', $providerDef, $config, $context);
        $result['latency_ms'] = (int)round((microtime(true) - $start) * 1000);

        if ($integrationId && $tenantId) {
            $db = Application::get()->db ?? null;
            if ($db) self::logResult($db, $integrationId, $tenantId, 'test.ping', $result, $result['latency_ms']);
        }
        return $result;
    }

    protected static function sendViaHandler(string $handler, array $providerDef, array $config, array $context): array
    {
        try {
            switch ($handler) {
                case 'sendSlackLike':       return self::sendSlackLike($config, $context);
                case 'sendDiscord':         return self::sendDiscord($config, $context);
                case 'sendTelegram':        return self::sendTelegram($config, $context);
                case 'sendTeams':           return self::sendTeams($config, $context);
                case 'sendGenericWebhook':  return self::sendGenericWebhook($providerDef, $config, $context);
                case 'sendEmail':           return self::sendEmail($config, $context);
                case 'sendPushover':        return self::sendPushover($config, $context);
                default: return ['ok' => false, 'status_code' => 0, 'error' => 'Handler no soportado: ' . $handler, 'response' => ''];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'status_code' => 0, 'error' => 'Excepción: ' . $e->getMessage(), 'response' => ''];
        }
    }

    /* ────────────────── HANDLERS ────────────────── */

    /**
     * Slack & Mattermost & Rocket.Chat: payload compatible.
     */
    protected static function sendSlackLike(array $config, array $context): array
    {
        $url = trim((string)($config['webhook_url'] ?? ''));
        if (!$url) return self::err('Falta webhook_url');

        $title = self::titleFor($context);
        $detail = self::detailFor($context);
        $color = self::eventColor($context['event']);
        $username = trim((string)($config['username'] ?? '')) ?: 'Kydesk';
        $body = [
            'username' => $username,
            'icon_emoji' => ':bell:',
            'attachments' => [[
                'fallback' => $title,
                'color' => $color,
                'title' => $title,
                'text' => $detail,
                'fields' => self::fieldsFor($context),
                'ts' => time(),
                'footer' => 'Kydesk Helpdesk',
            ]],
        ];
        if (!empty($config['channel'])) $body['channel'] = $config['channel'];

        return self::httpPost($url, json_encode($body, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
    }

    /**
     * Discord: usa "embeds" con colores int.
     */
    protected static function sendDiscord(array $config, array $context): array
    {
        $url = trim((string)($config['webhook_url'] ?? ''));
        if (!$url) return self::err('Falta webhook_url');

        $colorHex = self::eventColor($context['event']);
        $colorInt = hexdec(ltrim($colorHex, '#'));
        $body = [
            'username' => $config['username'] ?? 'Kydesk',
            'embeds' => [[
                'title' => self::titleFor($context),
                'description' => self::detailFor($context),
                'color' => $colorInt,
                'timestamp' => date('c'),
                'fields' => array_map(fn($f) => ['name' => $f['title'], 'value' => $f['value'], 'inline' => true], self::fieldsFor($context)),
                'footer' => ['text' => 'Kydesk Helpdesk'],
            ]],
        ];
        return self::httpPost($url, json_encode($body, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
    }

    /**
     * Telegram: Bot API sendMessage con HTML.
     */
    protected static function sendTelegram(array $config, array $context): array
    {
        $token = trim((string)($config['bot_token'] ?? ''));
        $chatId = trim((string)($config['chat_id'] ?? ''));
        if (!$token || !$chatId) return self::err('Falta bot_token o chat_id');

        $title = htmlspecialchars(self::titleFor($context), ENT_QUOTES, 'UTF-8');
        $detail = htmlspecialchars(self::detailFor($context), ENT_QUOTES, 'UTF-8');
        $fields = '';
        foreach (self::fieldsFor($context) as $f) {
            $fields .= "\n<b>" . htmlspecialchars($f['title']) . ":</b> " . htmlspecialchars($f['value']);
        }
        $text = "<b>" . $title . "</b>\n" . $detail . $fields;

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $body = json_encode([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], JSON_UNESCAPED_UNICODE);
        return self::httpPost($url, $body, ['Content-Type: application/json']);
    }

    /**
     * Microsoft Teams: MessageCard.
     */
    protected static function sendTeams(array $config, array $context): array
    {
        $url = trim((string)($config['webhook_url'] ?? ''));
        if (!$url) return self::err('Falta webhook_url');

        $color = ltrim(self::eventColor($context['event']), '#');
        $facts = [];
        foreach (self::fieldsFor($context) as $f) $facts[] = ['name' => $f['title'], 'value' => $f['value']];
        $body = [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'summary' => self::titleFor($context),
            'themeColor' => $color,
            'title' => self::titleFor($context),
            'text' => self::detailFor($context),
            'sections' => [['facts' => $facts]],
        ];
        return self::httpPost($url, json_encode($body, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
    }

    /**
     * Webhook genérico, Zapier, n8n, Make.
     */
    protected static function sendGenericWebhook(array $providerDef, array $config, array $context): array
    {
        $url = trim((string)($config['url'] ?? $config['webhook_url'] ?? $config['hook_url'] ?? ''));
        if (!$url) return self::err('Falta URL destino');

        $body = json_encode($context, JSON_UNESCAPED_UNICODE);
        $headers = ['Content-Type: application/json', 'X-Kydesk-Event: ' . $context['event']];
        if (!empty($config['secret'])) {
            $headers[] = 'X-Kydesk-Signature: ' . hash_hmac('sha256', $body, (string)$config['secret']);
        }
        if (!empty($config['auth_header'])) {
            $headers[] = 'Authorization: ' . $config['auth_header'];
        }
        $method = strtoupper((string)($config['method'] ?? 'POST'));
        return self::httpRequest($method, $url, $body, $headers);
    }

    /**
     * Email forwarding via Mailer (Resend).
     */
    protected static function sendEmail(array $config, array $context): array
    {
        $to = trim((string)($config['to'] ?? ''));
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) return self::err('Email destino inválido');

        $prefix = trim((string)($config['subject_prefix'] ?? '[Kydesk]'));
        $subject = $prefix . ' ' . self::titleFor($context);
        $detail = self::detailFor($context);
        $rows = '';
        foreach (self::fieldsFor($context) as $f) {
            $rows .= '<tr><td style="padding:6px 12px;color:#6b6b78;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em">' . htmlspecialchars($f['title']) . '</td><td style="padding:6px 12px;color:#16151b;font-size:13px">' . htmlspecialchars($f['value']) . '</td></tr>';
        }
        $inner = '<p>' . htmlspecialchars($detail) . '</p>'
               . '<table style="border-collapse:collapse;width:100%;margin-top:14px;background:#fafafb;border-radius:10px;border:1px solid #ececef">' . $rows . '</table>';
        try {
            (new Mailer())->send(
                ['email' => $to, 'name' => $to],
                $subject,
                Mailer::template(self::titleFor($context), $inner, null, null)
            );
            return ['ok' => true, 'status_code' => 200, 'response' => 'Email queued', 'error' => null];
        } catch (\Throwable $e) {
            return self::err('Mailer falló: ' . $e->getMessage());
        }
    }

    /**
     * Pushover.
     */
    protected static function sendPushover(array $config, array $context): array
    {
        $token = trim((string)($config['api_token'] ?? ''));
        $userKey = trim((string)($config['user_key'] ?? ''));
        if (!$token || !$userKey) return self::err('Falta api_token o user_key');

        $body = http_build_query([
            'token' => $token,
            'user' => $userKey,
            'title' => self::titleFor($context),
            'message' => self::detailFor($context),
            'priority' => (int)($config['priority'] ?? 0),
        ]);
        return self::httpPost('https://api.pushover.net/1/messages.json', $body, ['Content-Type: application/x-www-form-urlencoded']);
    }

    /* ────────────────── HELPERS ────────────────── */

    protected static function titleFor(array $ctx): string
    {
        $event = $ctx['event'];
        $data = $ctx['data'] ?? [];
        $code = $data['code'] ?? null;
        $subject = $data['subject'] ?? $data['title'] ?? null;
        $name = $data['name'] ?? null;

        if (!empty($ctx['is_test'])) return '🔔 Prueba de conexión · ' . ($ctx['integration_name'] ?? 'Kydesk');

        $prefix = match ($event) {
            'ticket.created'   => '🎫 Nuevo ticket',
            'ticket.updated'   => '✏️ Ticket actualizado',
            'ticket.assigned'  => '👤 Ticket asignado',
            'ticket.resolved'  => '✅ Ticket resuelto',
            'ticket.escalated' => '⚠️ Ticket escalado',
            'ticket.deleted'   => '🗑️ Ticket eliminado',
            'comment.created'  => '💬 Nuevo comentario',
            'sla.breach'       => '🚨 SLA vencido',
            'company.created'  => '🏢 Nueva empresa',
            'kb.published'     => '📚 Nuevo artículo',
            'todo.created'     => '📝 Nueva tarea',
            'todo.completed'   => '✓ Tarea completada',
            default            => '🔔 ' . $event,
        };
        $tail = $code ? " [$code]" : '';
        $name = $subject ?: $name ?: '';
        return $prefix . $tail . ($name ? ' · ' . $name : '');
    }

    protected static function detailFor(array $ctx): string
    {
        if (!empty($ctx['is_test'])) {
            return 'Si recibes este mensaje, la integración está funcionando correctamente. ¡Listo!';
        }
        $data = $ctx['data'] ?? [];
        $desc = $data['description'] ?? $data['body'] ?? $data['message'] ?? '';
        if (is_string($desc) && strlen($desc) > 280) $desc = substr($desc, 0, 277) . '...';
        return $desc ?: 'Evento: ' . $ctx['event'];
    }

    protected static function fieldsFor(array $ctx): array
    {
        if (!empty($ctx['is_test'])) {
            return [
                ['title' => 'Estado', 'value' => 'Conectado'],
                ['title' => 'Hora', 'value' => date('Y-m-d H:i:s')],
            ];
        }
        $data = $ctx['data'] ?? [];
        $fields = [];
        foreach (['priority' => 'Prioridad', 'status' => 'Estado', 'assigned_name' => 'Asignado', 'requester_name' => 'Solicitante', 'category_name' => 'Categoría', 'department_name' => 'Departamento', 'company_name' => 'Empresa'] as $k => $label) {
            if (!empty($data[$k])) $fields[] = ['title' => $label, 'value' => (string)$data[$k]];
        }
        return $fields;
    }

    protected static function eventColor(string $event): string
    {
        return match (true) {
            str_starts_with($event, 'sla.')                              => '#dc2626',
            str_contains($event, '.deleted') || $event === 'ticket.escalated' => '#ef4444',
            str_contains($event, '.resolved') || str_contains($event, '.completed') => '#16a34a',
            str_contains($event, '.created')                             => '#3b82f6',
            str_contains($event, '.updated') || str_contains($event, '.assigned') => '#7c5cff',
            default                                                       => '#6b7280',
        };
    }

    protected static function httpPost(string $url, string $body, array $headers): array
    {
        return self::httpRequest('POST', $url, $body, $headers);
    }

    protected static function httpRequest(string $method, string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'Kydesk-Integration/1.0',
        ]);
        $resp = curl_exec($ch);
        $sc = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $ok = $sc >= 200 && $sc < 300;
        return [
            'ok' => $ok,
            'status_code' => $sc,
            'response' => substr((string)($resp ?: ''), 0, 500),
            'error' => $ok ? null : ($err ?: ('HTTP ' . $sc)),
        ];
    }

    protected static function err(string $msg): array
    {
        return ['ok' => false, 'status_code' => 0, 'response' => '', 'error' => $msg];
    }

    protected static function logResult($db, int $integrationId, int $tenantId, string $event, array $result, int $latencyMs): void
    {
        try {
            $db->insert('integration_logs', [
                'integration_id' => $integrationId,
                'tenant_id' => $tenantId,
                'event_type' => $event,
                'status' => $result['ok'] ? 'success' : 'failed',
                'status_code' => $result['status_code'] ?? 0,
                'latency_ms' => $latencyMs,
                'response_excerpt' => substr((string)($result['response'] ?? ''), 0, 500),
                'error_message' => $result['ok'] ? null : substr((string)($result['error'] ?? ''), 0, 500),
            ]);
            // Update counters
            if ($result['ok']) {
                $db->run('UPDATE integrations SET last_event_at=NOW(), last_status=?, success_count=success_count+1 WHERE id=?', ['success', $integrationId]);
            } else {
                $db->run('UPDATE integrations SET last_event_at=NOW(), last_status=?, error_count=error_count+1 WHERE id=?', ['failed', $integrationId]);
            }
            // Limit logs per integration to last 200
            $db->run('DELETE FROM integration_logs WHERE integration_id=? AND id NOT IN (SELECT id FROM (SELECT id FROM integration_logs WHERE integration_id=? ORDER BY id DESC LIMIT 200) x)', [$integrationId, $integrationId]);
        } catch (\Throwable $_e) { /* don't break the host */ }
    }
}
