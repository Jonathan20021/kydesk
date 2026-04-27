<?php
namespace App\Core;

/**
 * Automation runner.
 *
 * Suscrito a Events::emit(). Para cada evento, busca automations activas
 * del tenant cuyo trigger_event coincida, evalúa condiciones y ejecuta
 * acciones. Actualiza run_count y last_run_at.
 *
 * Acciones soportadas:
 *   set_priority, set_status, assign_to, assign_to_department,
 *   notify_email, add_comment
 *
 * Condiciones soportadas:
 *   priority, category_id, department_id, keyword (en subject)
 */
class AutomationDispatcher
{
    /** Flag de re-entrada para evitar loops por acciones que disparan eventos. */
    protected static int $depth = 0;
    protected const MAX_DEPTH = 3;

    public static function dispatch(string $event, int $tenantId, ?string $entity, ?int $entityId, array $payload = []): void
    {
        if ($entity !== 'ticket' || !$entityId) return;
        if (self::$depth >= self::MAX_DEPTH) return;
        self::$depth++;
        try {
            self::run($event, $tenantId, $entityId);
        } catch (\Throwable $e) { /* no romper el request principal */ }
        finally {
            self::$depth--;
        }
    }

    protected static function run(string $event, int $tenantId, int $ticketId): void
    {
        $app = Application::get();
        if (!$app) return;
        $db = $app->db;

        $automations = [];
        try {
            $automations = $db->all(
                "SELECT * FROM automations WHERE tenant_id = ? AND active = 1 AND trigger_event = ? ORDER BY id ASC",
                [$tenantId, $event]
            );
        } catch (\Throwable $e) { return; }
        if (!$automations) return;

        $ticket = $db->one('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$ticketId, $tenantId]);
        if (!$ticket) return;

        foreach ($automations as $a) {
            $cond = json_decode($a['conditions'] ?? '[]', true) ?: [];
            $actions = json_decode($a['actions'] ?? '[]', true) ?: [];
            if (!self::conditionsMatch($cond, $ticket)) continue;

            $applied = self::applyActions($db, $tenantId, $ticket, $actions, (int)$a['id']);
            try {
                $db->run('UPDATE automations SET run_count = run_count + 1, last_run_at = NOW() WHERE id = ?', [(int)$a['id']]);
            } catch (\Throwable $e) {}
            try {
                $db->insert('audit_logs', [
                    'tenant_id' => $tenantId,
                    'user_id'   => null,
                    'action'    => 'automation.executed',
                    'entity'    => 'automation',
                    'entity_id' => (int)$a['id'],
                    'meta'      => json_encode([
                        'automation' => $a['name'],
                        'event'      => $event,
                        'ticket_id'  => $ticketId,
                        'applied'    => $applied,
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $e) {}

            // Refresh ticket data if anything changed
            if (in_array(true, $applied, true)) {
                $ticket = $db->one('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$ticketId, $tenantId]) ?: $ticket;
            }
        }
    }

    protected static function conditionsMatch(array $cond, array $ticket): bool
    {
        if (!empty($cond['priority']) && (string)$cond['priority'] !== (string)$ticket['priority']) return false;
        if (!empty($cond['category_id']) && (int)$cond['category_id'] !== (int)($ticket['category_id'] ?? 0)) return false;
        if (!empty($cond['department_id']) && (int)$cond['department_id'] !== (int)($ticket['department_id'] ?? 0)) return false;
        if (!empty($cond['keyword'])) {
            $kw = mb_strtolower((string)$cond['keyword']);
            $hay = mb_strtolower((string)($ticket['subject'] ?? '') . ' ' . (string)($ticket['description'] ?? ''));
            if (mb_strpos($hay, $kw) === false) return false;
        }
        return true;
    }

    /**
     * Ejecuta las acciones soportadas. Devuelve mapa de cuáles se aplicaron.
     */
    protected static function applyActions($db, int $tenantId, array $ticket, array $actions, int $automationId): array
    {
        $applied = [];
        $tid = (int)$ticket['id'];
        $update = [];

        if (!empty($actions['set_priority']) && in_array($actions['set_priority'], ['low','medium','high','urgent'], true)) {
            if ((string)$ticket['priority'] !== (string)$actions['set_priority']) {
                $update['priority'] = (string)$actions['set_priority'];
                $applied['set_priority'] = true;
            }
        }
        if (!empty($actions['set_status']) && in_array($actions['set_status'], ['open','in_progress','on_hold','resolved','closed'], true)) {
            if ((string)$ticket['status'] !== (string)$actions['set_status']) {
                $update['status'] = (string)$actions['set_status'];
                if ($actions['set_status'] === 'resolved') $update['resolved_at'] = date('Y-m-d H:i:s');
                if ($actions['set_status'] === 'closed')   $update['closed_at']   = date('Y-m-d H:i:s');
                $applied['set_status'] = true;
            }
        }
        if (!empty($actions['assign_to'])) {
            $userId = (int)$actions['assign_to'];
            $valid = $db->val('SELECT id FROM users WHERE id = ? AND tenant_id = ? AND is_active = 1', [$userId, $tenantId]);
            if ($valid && (int)($ticket['assigned_to'] ?? 0) !== $userId) {
                $update['assigned_to'] = $userId;
                $applied['assign_to'] = true;
            }
        }
        if (!empty($actions['assign_to_department'])) {
            $deptId = (int)$actions['assign_to_department'];
            try {
                $valid = $db->val('SELECT id FROM departments WHERE id = ? AND tenant_id = ? AND is_active = 1', [$deptId, $tenantId]);
                if ($valid && (int)($ticket['department_id'] ?? 0) !== $deptId) {
                    $update['department_id'] = $deptId;
                    $applied['assign_to_department'] = true;
                    // Auto-pick lead if no assignee
                    if (empty($update['assigned_to']) && empty($ticket['assigned_to'])) {
                        $lead = $db->val(
                            'SELECT du.user_id FROM department_users du JOIN users u ON u.id = du.user_id WHERE du.department_id = ? AND u.is_active = 1 ORDER BY du.is_lead DESC, RAND() LIMIT 1',
                            [$deptId]
                        );
                        if ($lead) { $update['assigned_to'] = (int)$lead; $applied['auto_assign_lead'] = true; }
                    }
                }
            } catch (\Throwable $e) { /* tabla departments puede no existir */ }
        }

        if ($update) {
            try { $db->update('tickets', $update, 'id = ? AND tenant_id = ?', [$tid, $tenantId]); }
            catch (\Throwable $e) {}
        }

        if (!empty($actions['add_comment'])) {
            try {
                $db->insert('ticket_comments', [
                    'tenant_id'   => $tenantId,
                    'ticket_id'   => $tid,
                    'user_id'     => null,
                    'author_name' => 'Automatización',
                    'body'        => (string)$actions['add_comment'],
                    'is_internal' => 1,
                ]);
                $applied['add_comment'] = true;
            } catch (\Throwable $e) {}
        }

        if (!empty($actions['notify_email'])) {
            $emails = array_filter(array_map('trim', explode(',', (string)$actions['notify_email'])));
            $tenantRow = $db->one('SELECT name, slug FROM tenants WHERE id = ?', [$tenantId]);
            $appUrl = rtrim(Application::get()->config['app']['url'] ?? '', '/');
            $url = $appUrl . '/t/' . ($tenantRow['slug'] ?? '') . '/tickets/' . $tid;
            $current = $db->one('SELECT * FROM tickets WHERE id = ?', [$tid]) ?: $ticket;
            $inner = '<p>Se disparó una automatización sobre el ticket <strong>' . htmlspecialchars($current['code'] ?? ('#' . $tid)) . '</strong>.</p>'
                . '<p><strong>Asunto:</strong> ' . htmlspecialchars($current['subject'] ?? '') . '</p>'
                . '<p><strong>Prioridad:</strong> ' . strtoupper(htmlspecialchars($current['priority'] ?? 'medium')) . ' · <strong>Estado:</strong> ' . htmlspecialchars($current['status'] ?? '') . '</p>'
                . (!empty($current['requester_name']) ? '<p><strong>Solicitante:</strong> ' . htmlspecialchars($current['requester_name']) . '</p>' : '');
            try {
                $mailer = new Mailer();
                foreach ($emails as $em) {
                    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) continue;
                    $mailer->send(
                        ['email' => $em, 'name' => $em],
                        '[Automatización] ' . ($current['code'] ?? '') . ' · ' . ($current['subject'] ?? ''),
                        Mailer::template('Notificación de automatización', $inner, 'Abrir ticket', $url)
                    );
                }
                $applied['notify_email'] = true;
            } catch (\Throwable $e) {}
        }

        return $applied;
    }
}
