<?php
namespace App\Controllers\Api;

class EventsController extends BaseApiController
{
    public function recent(): void
    {
        $this->authenticate('read');
        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $where = ['tenant_id = ?']; $args = [$this->tid()];
        if ($e = $_GET['event'] ?? null) { $where[] = 'event = ?'; $args[] = $e; }
        if ($since = $_GET['since'] ?? null) { $where[] = 'created_at >= ?'; $args[] = $since; }
        $whereSql = implode(' AND ', $where);
        $rows = $this->db->all(
            "SELECT id, event, entity, entity_id, actor_user_id, payload_json, created_at
             FROM activity_events WHERE $whereSql
             ORDER BY id DESC LIMIT $limit OFFSET $offset",
            $args
        );
        foreach ($rows as &$r) $r['payload'] = json_decode((string)$r['payload_json'], true);
        $this->json($rows, 200, ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Server-Sent Events stream of recent events for the authenticated tenant.
     * Polls the activity_events table at low cost. The connection terminates after
     * 30 seconds (clients should reconnect — most EventSource implementations
     * reconnect automatically).
     */
    public function stream(): void
    {
        $this->authenticate('read');
        $tid = $this->tid();
        $lastId = (int)($_GET['last_id'] ?? 0);

        @set_time_limit(35);
        ignore_user_abort(false);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no'); // disable nginx proxy buffering
        // Open the stream
        echo ":ready\n\n";
        @ob_flush(); @flush();

        $start = time();
        while (time() - $start < 30) {
            $rows = $this->db->all(
                'SELECT id, event, entity, entity_id, payload_json, created_at FROM activity_events WHERE tenant_id=? AND id>? ORDER BY id ASC LIMIT 50',
                [$tid, $lastId]
            );
            foreach ($rows as $r) {
                $lastId = max($lastId, (int)$r['id']);
                $payload = ['id' => (int)$r['id'], 'event' => $r['event'], 'entity' => $r['entity'], 'entity_id' => $r['entity_id'], 'data' => json_decode((string)$r['payload_json'], true), 'at' => $r['created_at']];
                echo "id: {$r['id']}\n";
                echo "event: {$r['event']}\n";
                echo "data: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
            }
            if (!$rows) {
                echo ": ping " . date('c') . "\n\n";
            }
            @ob_flush(); @flush();
            if (connection_aborted()) break;
            sleep(2);
        }
        exit;
    }
}
