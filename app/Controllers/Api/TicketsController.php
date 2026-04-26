<?php
namespace App\Controllers\Api;

use App\Core\Events;
use App\Core\Helpers;

class TicketsController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $tid = $this->tid();

        $where = ['t.tenant_id = ?']; $args = [$tid];
        if ($s = $_GET['status'] ?? null)       { $where[] = 't.status = ?';   $args[] = $s; }
        if ($p = $_GET['priority'] ?? null)     { $where[] = 't.priority = ?'; $args[] = $p; }
        if ($a = $_GET['assigned_to'] ?? null)  { $where[] = 't.assigned_to = ?'; $args[] = (int)$a; }
        if ($c = $_GET['category_id'] ?? null)  { $where[] = 't.category_id = ?'; $args[] = (int)$c; }
        if ($co = $_GET['company_id'] ?? null)  { $where[] = 't.company_id = ?'; $args[] = (int)$co; }
        if ($ch = $_GET['channel'] ?? null)     { $where[] = 't.channel = ?'; $args[] = $ch; }
        if ($q = $_GET['q'] ?? null) {
            $where[] = '(t.subject LIKE ? OR t.code LIKE ? OR t.description LIKE ?)';
            $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%";
        }
        if ($created_after = $_GET['created_after'] ?? null)  { $where[] = 't.created_at >= ?'; $args[] = $created_after; }
        if ($created_before = $_GET['created_before'] ?? null){ $where[] = 't.created_at <= ?'; $args[] = $created_before; }

        $sort = $this->sortClause(['id','code','subject','priority','status','created_at','updated_at','sla_due_at'], 'updated_at');
        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $whereSql = implode(' AND ', $where);

        $rows = $this->db->all(
            "SELECT t.id, t.code, t.subject, t.description, t.status, t.priority, t.channel,
                    t.requester_name, t.requester_email, t.requester_phone, t.category_id, t.company_id,
                    t.assigned_to, t.created_by, t.escalation_level, t.sla_due_at, t.sla_breached,
                    t.first_response_at, t.resolved_at, t.closed_at, t.satisfaction_rating, t.tags,
                    t.public_token, t.created_at, t.updated_at
             FROM tickets t WHERE $whereSql ORDER BY t.$sort LIMIT $limit OFFSET $offset", $args
        );
        $total = (int)$this->db->val("SELECT COUNT(*) FROM tickets t WHERE $whereSql", $args);

        $rows = $this->expandTickets($rows);

        $this->json(
            $rows,
            200,
            ['total' => $total, 'limit' => $limit, 'offset' => $offset, 'has_more' => ($offset + $limit) < $total],
            $this->paginatedLinks('/api/v1/tickets', $offset, $limit, $total)
        );
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Ticket no encontrado', 404, 'not_found', ['resource' => 'ticket', 'id' => $id]);

        $rows = $this->expandTickets([$row]);
        $this->json($rows[0]);
    }

    public function create(): void
    {
        $this->authenticate('write');
        if ($cached = $this->checkIdempotency()) {
            http_response_code($cached['status']);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($cached['cached']);
            exit;
        }

        $b = $this->body();
        $this->require($b, ['subject']);

        $allowedChannels = ['portal','email','phone','chat','internal'];
        $allowedPriorities = ['low','medium','high','urgent'];
        $allowedStatuses = ['open','in_progress','on_hold','resolved','closed'];

        $statusIn = (string)$this->in($b, 'status', 'open');
        $priorityIn = (string)$this->in($b, 'priority', 'medium');
        $channelIn = (string)$this->in($b, 'channel', 'portal');

        $insertData = [
            'tenant_id' => $this->tid(),
            'code' => 'TMP-' . bin2hex(random_bytes(4)),
            'subject' => trim((string)$this->in($b, 'subject')),
            'description' => (string)$this->in($b, 'description', ''),
            'status' => in_array($statusIn, $allowedStatuses, true) ? $statusIn : 'open',
            'priority' => in_array($priorityIn, $allowedPriorities, true) ? $priorityIn : 'medium',
            'channel' => in_array($channelIn, $allowedChannels, true) ? $channelIn : 'portal',
            'category_id' => ($cid = (int)$this->in($b, 'category_id', 0)) ?: null,
            'company_id'  => ($coid = (int)$this->in($b, 'company_id', 0)) ?: null,
            'asset_id'    => ($aid = (int)$this->in($b, 'asset_id', 0)) ?: null,
            'assigned_to' => ($at = (int)$this->in($b, 'assigned_to', 0)) ?: null,
            'requester_name'  => (string)$this->in($b, 'requester_name', ''),
            'requester_email' => (string)$this->in($b, 'requester_email', ''),
            'requester_phone' => (string)$this->in($b, 'requester_phone', ''),
            'tags' => (string)$this->in($b, 'tags', ''),
            'created_by' => $this->uid(),
            'public_token' => bin2hex(random_bytes(16)),
        ];

        $id = $this->db->insert('tickets', $insertData);
        $this->db->update('tickets', ['code' => Helpers::ticketCode($this->tid(), $id)], 'id=?', [$id]);
        $row = $this->db->one('SELECT * FROM tickets WHERE id=?', [$id]);
        Events::emit(Events::TICKET_CREATED, $this->tid(), 'ticket', $id, $row, $this->uid());
        $rows = $this->expandTickets([$row]);
        $this->created($rows[0], rtrim($this->app->config['app']['url'], '/') . '/api/v1/tickets/' . $id);
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Ticket no encontrado', 404, 'not_found');

        $this->ifMatchOk('"' . substr(sha1((string)$exists['updated_at']), 0, 16) . '"');

        $allowed = ['subject','description','status','priority','category_id','company_id','asset_id','assigned_to','tags','requester_name','requester_email','requester_phone','satisfaction_rating'];
        $upd = [];
        foreach ($allowed as $f) if (array_key_exists($f, $b)) $upd[$f] = $b[$f];
        if (($upd['status'] ?? null) === 'resolved' && empty($exists['resolved_at'])) $upd['resolved_at'] = date('Y-m-d H:i:s');
        if (($upd['status'] ?? null) === 'closed') $upd['closed_at'] = date('Y-m-d H:i:s');
        if ($upd) $this->db->update('tickets', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $row = $this->db->one('SELECT * FROM tickets WHERE id=?', [$id]);

        if ($upd) {
            Events::emit(Events::TICKET_UPDATED, $this->tid(), 'ticket', $id, ['changes' => array_keys($upd), 'ticket' => $row], $this->uid());
            if (($upd['status'] ?? null) === 'resolved') {
                Events::emit(Events::TICKET_RESOLVED, $this->tid(), 'ticket', $id, $row, $this->uid());
            }
        }

        $rows = $this->expandTickets([$row]);
        $this->json($rows[0]);
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Ticket no encontrado', 404, 'not_found');
        $this->db->delete('tickets', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        Events::emit(Events::TICKET_DELETED, $this->tid(), 'ticket', $id, ['id' => $id], $this->uid());
        $this->json(['deleted' => true, 'id' => $id]);
    }

    // ─── Sub-resources ─────────────────────────────────────────────

    public function commentsIndex(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Ticket no encontrado', 404, 'not_found');

        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $rows = $this->db->all(
            'SELECT id, ticket_id, user_id, author_name, author_email, body, is_internal, created_at
             FROM ticket_comments WHERE ticket_id=? AND tenant_id=?
             ORDER BY created_at ASC LIMIT ' . $limit . ' OFFSET ' . $offset,
            [$id, $this->tid()]
        );
        $total = (int)$this->db->val('SELECT COUNT(*) FROM ticket_comments WHERE ticket_id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($rows, 200, ['total' => $total, 'limit' => $limit, 'offset' => $offset]);
    }

    public function commentsCreate(array $params): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['body']);
        $id = (int)$params['id'];
        $tk = $this->db->one('SELECT id FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$tk) $this->error('Ticket no encontrado', 404, 'not_found');

        $cid = $this->db->insert('ticket_comments', [
            'tenant_id' => $this->tid(),
            'ticket_id' => $id,
            'user_id' => $this->uid(),
            'author_name' => $this->in($b, 'author_name', $this->ctx['user']['name'] ?? 'API'),
            'author_email' => $this->in($b, 'author_email', $this->ctx['user']['email'] ?? ''),
            'body' => trim((string)$b['body']),
            'is_internal' => (int)($this->in($b, 'is_internal', 0) ? 1 : 0),
        ]);
        $this->db->run('UPDATE tickets SET updated_at=NOW(), first_response_at=COALESCE(first_response_at, NOW()) WHERE id=?', [$id]);
        $row = $this->db->one('SELECT * FROM ticket_comments WHERE id=?', [$cid]);
        Events::emit(Events::COMMENT_CREATED, $this->tid(), 'comment', $cid, ['ticket_id' => $id, 'comment' => $row], $this->uid());
        $this->created($row);
    }

    public function commentDelete(array $params): void
    {
        $this->authenticate('write');
        $tid = (int)$params['id'];
        $cid = (int)$params['cid'];
        $exists = $this->db->one('SELECT id FROM ticket_comments WHERE id=? AND ticket_id=? AND tenant_id=?', [$cid, $tid, $this->tid()]);
        if (!$exists) $this->error('Comentario no encontrado', 404, 'not_found');
        $this->db->delete('ticket_comments', 'id=? AND tenant_id=?', [$cid, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $cid]);
    }

    public function escalate(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $tk = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$tk) $this->error('Ticket no encontrado', 404, 'not_found');
        $b = $this->body();
        $newLevel = ((int)$tk['escalation_level']) + 1;
        $this->db->insert('ticket_escalations', [
            'tenant_id' => $this->tid(),
            'ticket_id' => $id,
            'from_user_id' => $this->uid(),
            'to_user_id' => (int)$this->in($b, 'to_user_id', 0) ?: null,
            'from_level' => $tk['escalation_level'],
            'to_level' => $newLevel,
            'reason' => (string)$this->in($b, 'reason', ''),
        ]);
        $this->db->update('tickets', ['escalation_level' => $newLevel], 'id=?', [$id]);
        $row = $this->db->one('SELECT * FROM tickets WHERE id=?', [$id]);
        Events::emit(Events::TICKET_ESCALATED, $this->tid(), 'ticket', $id, ['from_level' => $tk['escalation_level'], 'to_level' => $newLevel, 'ticket' => $row], $this->uid());
        $this->json($row);
    }

    public function assign(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $b = $this->body();
        $this->require($b, ['user_id']);
        $userId = (int)$b['user_id'];
        $tk = $this->db->one('SELECT id FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$tk) $this->error('Ticket no encontrado', 404, 'not_found');
        $u = $this->db->one('SELECT id FROM users WHERE id=? AND tenant_id=?', [$userId, $this->tid()]);
        if (!$u) $this->error('Usuario no encontrado', 422, 'validation_error');
        $this->db->update('tickets', ['assigned_to' => $userId], 'id=?', [$id]);
        Events::emit(Events::TICKET_ASSIGNED, $this->tid(), 'ticket', $id, ['ticket_id' => $id, 'user_id' => $userId], $this->uid());
        $this->json(['assigned' => true, 'ticket_id' => $id, 'user_id' => $userId]);
    }

    public function batch(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $ops = $this->in($b, 'operations', []);
        if (!is_array($ops) || empty($ops)) $this->error('operations array requerido', 422, 'validation_error');
        if (count($ops) > 50) $this->error('Máximo 50 operaciones por batch', 422, 'validation_error');

        $results = [];
        foreach ($ops as $i => $op) {
            $action = $op['action'] ?? 'update';
            $id = (int)($op['id'] ?? 0);
            try {
                if ($action === 'delete') {
                    $this->db->delete('tickets', 'id=? AND tenant_id=?', [$id, $this->tid()]);
                    $results[] = ['index' => $i, 'id' => $id, 'action' => 'delete', 'ok' => true];
                } elseif ($action === 'update') {
                    $upd = array_intersect_key($op, array_flip(['status','priority','assigned_to','category_id','tags']));
                    if ($upd) $this->db->update('tickets', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
                    $results[] = ['index' => $i, 'id' => $id, 'action' => 'update', 'ok' => true];
                } else {
                    $results[] = ['index' => $i, 'id' => $id, 'ok' => false, 'error' => 'Unknown action: ' . $action];
                }
            } catch (\Throwable $e) {
                $results[] = ['index' => $i, 'id' => $id, 'ok' => false, 'error' => $e->getMessage()];
            }
        }
        $this->json($results, 200, ['count' => count($results)]);
    }

    // ─── Expansion ─────────────────────────────────────────────────

    protected function expandTickets(array $rows): array
    {
        if (!$rows) return $rows;
        $expandCompany = $this->shouldExpand('company');
        $expandCategory = $this->shouldExpand('category');
        $expandAssignee = $this->shouldExpand('assignee');
        $expandComments = $this->shouldExpand('comments');

        if (!$expandCompany && !$expandCategory && !$expandAssignee && !$expandComments) return $rows;

        $companyIds = array_filter(array_column($rows, 'company_id'));
        $categoryIds = array_filter(array_column($rows, 'category_id'));
        $userIds = array_filter(array_column($rows, 'assigned_to'));
        $ticketIds = array_column($rows, 'id');

        $companies = []; $categories = []; $users = []; $comments = [];
        if ($expandCompany && $companyIds) {
            $in = implode(',', array_map('intval', $companyIds));
            $companies = $this->db->all("SELECT id, name, industry, tier, website FROM companies WHERE id IN ($in)");
            $companies = array_column($companies, null, 'id');
        }
        if ($expandCategory && $categoryIds) {
            $in = implode(',', array_map('intval', $categoryIds));
            $categories = $this->db->all("SELECT id, name, color, icon FROM ticket_categories WHERE id IN ($in)");
            $categories = array_column($categories, null, 'id');
        }
        if ($expandAssignee && $userIds) {
            $in = implode(',', array_map('intval', $userIds));
            $users = $this->db->all("SELECT id, name, email, title, avatar FROM users WHERE id IN ($in)");
            $users = array_column($users, null, 'id');
        }
        if ($expandComments && $ticketIds) {
            $in = implode(',', array_map('intval', $ticketIds));
            $allCmts = $this->db->all("SELECT id, ticket_id, body, is_internal, author_name, created_at FROM ticket_comments WHERE ticket_id IN ($in) ORDER BY created_at ASC");
            foreach ($allCmts as $c) $comments[$c['ticket_id']][] = $c;
        }

        foreach ($rows as &$r) {
            if ($expandCompany)  $r['company']  = $r['company_id']  ? ($companies[$r['company_id']]  ?? null) : null;
            if ($expandCategory) $r['category'] = $r['category_id'] ? ($categories[$r['category_id']] ?? null) : null;
            if ($expandAssignee) $r['assignee'] = $r['assigned_to'] ? ($users[$r['assigned_to']] ?? null) : null;
            if ($expandComments) $r['comments'] = $comments[$r['id']] ?? [];
        }
        return $rows;
    }
}
