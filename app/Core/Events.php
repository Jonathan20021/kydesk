<?php
namespace App\Core;

/**
 * Lightweight event bus.
 *
 * Records the event in `activity_events` (audit trail) and queues
 * webhook deliveries for any matching webhook subscribers in `dev_webhooks`.
 *
 * Webhooks are delivered synchronously on the same request for low-latency
 * scenarios. For high-volume tenants, set `WEBHOOK_DELIVERY_QUEUE=1` and run
 * the dispatcher worker (`php public/cron.php deliver-webhooks`).
 */
class Events
{
    public const TICKET_CREATED   = 'ticket.created';
    public const TICKET_UPDATED   = 'ticket.updated';
    public const TICKET_ASSIGNED  = 'ticket.assigned';
    public const TICKET_RESOLVED  = 'ticket.resolved';
    public const TICKET_ESCALATED = 'ticket.escalated';
    public const TICKET_DELETED   = 'ticket.deleted';
    public const COMMENT_CREATED  = 'comment.created';
    public const SLA_BREACH       = 'sla.breach';
    public const COMPANY_CREATED  = 'company.created';
    public const KB_PUBLISHED     = 'kb.published';

    public static function emit(string $event, int $tenantId, ?string $entity = null, ?int $entityId = null, array $payload = [], ?int $actorUserId = null): void
    {
        $db = Application::get()->db ?? null;
        if (!$db) return;

        // 1) Record in activity stream (best effort)
        try {
            $db->insert('activity_events', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'entity' => $entity,
                'entity_id' => $entityId,
                'actor_user_id' => $actorUserId,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) { /* table may not exist yet */ }

        // 2) Find matching dev_webhooks (by tenant via dev_apps.tenant_id) and deliver
        try {
            $hooks = $db->all(
                "SELECT w.* FROM dev_webhooks w
                 JOIN dev_apps a ON a.id = w.app_id
                 WHERE a.tenant_id = ? AND w.is_active = 1 AND a.status = 'active'
                   AND (w.events = '*' OR w.events LIKE ? OR w.events = ?)",
                [$tenantId, '%' . $event . '%', $event]
            );
        } catch (\Throwable $e) { return; }

        if (!$hooks) return;

        $payloadFull = [
            'event' => $event,
            'entity' => $entity,
            'entity_id' => $entityId,
            'tenant_id' => $tenantId,
            'data' => $payload,
            'occurred_at' => date('c'),
        ];
        $body = json_encode($payloadFull, JSON_UNESCAPED_UNICODE);

        foreach ($hooks as $h) {
            self::deliverHook($db, $h, $event, $body);
        }
    }

    protected static function deliverHook($db, array $hook, string $event, string $body): void
    {
        // Detailed event-list match (when not '*')
        if ($hook['events'] !== '*') {
            $events = array_map('trim', explode(',', (string)$hook['events']));
            if (!in_array($event, $events, true) && !in_array('*', $events, true)) return;
        }

        $sig = hash_hmac('sha256', $body, (string)$hook['secret']);

        $ch = curl_init($hook['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Kydesk-Signature: ' . $sig,
                'X-Kydesk-Event: ' . $event,
                'X-Kydesk-Webhook-Id: ' . $hook['id'],
                'User-Agent: Kydesk-Webhook/1.0',
            ],
            CURLOPT_TIMEOUT => 5, // short timeout to not block the API request
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $resp = curl_exec($ch);
        $sc = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        try {
            $db->insert('webhook_deliveries', [
                'webhook_id' => (int)$hook['id'],
                'event' => $event,
                'payload_json' => $body,
                'status_code' => $sc,
                'response_excerpt' => substr((string)($resp ?: $err), 0, 500),
                'attempt' => 1,
                'delivered_at' => $sc >= 200 && $sc < 300 ? date('Y-m-d H:i:s') : null,
                'next_retry_at' => $sc >= 200 && $sc < 300 ? null : date('Y-m-d H:i:s', strtotime('+5 minutes')),
            ]);
            $newFailures = $sc >= 200 && $sc < 300 ? 0 : ((int)$hook['failure_count'] + 1);
            $autoDisable = $newFailures >= 10;
            $db->update('dev_webhooks', [
                'last_triggered_at' => date('Y-m-d H:i:s'),
                'last_status_code' => $sc,
                'failure_count' => $newFailures,
                'is_active' => $autoDisable ? 0 : (int)$hook['is_active'],
            ], 'id=?', [(int)$hook['id']]);

            // Notificar al developer si se acaba de auto-deshabilitar
            if ($autoDisable && (int)$hook['is_active'] === 1) {
                $dev = $db->one('SELECT email, name FROM developers WHERE id=?', [(int)$hook['developer_id']]);
                if ($dev) {
                    \App\Core\DevMailer::webhookDisabled((string)$dev['email'], (string)$dev['name'], (string)$hook['name'], $newFailures);
                }
            }
        } catch (\Throwable $e) { /* don't break the host request */ }
    }
}
