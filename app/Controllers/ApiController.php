<?php
namespace App\Controllers;

use App\Core\Application;
use App\Core\ApiAuth;
use App\Core\Helpers;

class ApiController
{
    protected Application $app;
    protected $db;
    protected ?array $ctx = null; // ['token','tenant','user']

    public function __construct()
    {
        $this->app = Application::get();
        $this->db = $this->app->db;
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function error(string $message, int $code = 400, ?string $type = null): void
    {
        $this->json(['error' => ['message' => $message, 'type' => $type ?? 'request_error', 'status' => $code]], $code);
    }

    protected function authenticate(string $needScope = 'read'): void
    {
        $ctx = ApiAuth::authenticate($this->db);
        if (!$ctx) $this->error('Token API inválido o ausente. Envía Authorization: Bearer {token}', 401, 'unauthorized');
        if (!ApiAuth::requireScope($ctx, $needScope)) $this->error("Token sin scope '$needScope'", 403, 'forbidden');
        $this->ctx = $ctx;
    }

    protected function body(): array
    {
        if (!empty($_POST)) return $_POST;
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $j = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    protected function in(array $b, string $k, $default = null) { return $b[$k] ?? $default; }
    protected function tid(): int { return $this->ctx['tenant']->id; }
    protected function uid(): ?int { return isset($this->ctx['user']['id']) ? (int)$this->ctx['user']['id'] : null; }

    // ==================== META ====================

    public function index(): void
    {
        $this->json([
            'name' => 'Kydesk Helpdesk API',
            'version' => 'v1',
            'docs' => rtrim($this->app->config['app']['url'], '/') . '/api/docs',
            'auth' => 'Bearer token in Authorization header',
            'endpoints' => [
                'GET  /api/v1/me',
                'GET  /api/v1/tickets', 'POST /api/v1/tickets', 'GET /api/v1/tickets/{id}', 'PATCH /api/v1/tickets/{id}', 'DELETE /api/v1/tickets/{id}',
                'POST /api/v1/tickets/{id}/comments', 'GET /api/v1/tickets/{id}/comments',
                'GET  /api/v1/categories', 'POST /api/v1/categories',
                'GET  /api/v1/companies', 'POST /api/v1/companies',
                'GET  /api/v1/users',
                'GET  /api/v1/kb/articles',
                'GET  /api/v1/sla',
                'GET  /api/v1/automations',
                'GET  /api/v1/stats',
            ],
        ]);
    }

    public function me(): void
    {
        $this->authenticate('read');
        $this->json([
            'tenant' => [
                'id' => $this->ctx['tenant']->id,
                'slug' => $this->ctx['tenant']->slug,
                'name' => $this->ctx['tenant']->name,
            ],
            'user' => $this->ctx['user'],
            'token' => [
                'name' => $this->ctx['token']['name'],
                'preview' => $this->ctx['token']['token_preview'],
                'scopes' => explode(',', $this->ctx['token']['scopes']),
                'last_used_at' => $this->ctx['token']['last_used_at'],
            ],
        ]);
    }

    // ==================== TICKETS ====================

    public function ticketsIndex(): void
    {
        $this->authenticate('read');
        $where = ['t.tenant_id = ?']; $args = [$this->tid()];
        if ($s = $_GET['status'] ?? null) { $where[] = 't.status = ?'; $args[] = $s; }
        if ($p = $_GET['priority'] ?? null) { $where[] = 't.priority = ?'; $args[] = $p; }
        if ($a = $_GET['assigned_to'] ?? null) { $where[] = 't.assigned_to = ?'; $args[] = (int)$a; }
        if ($q = $_GET['q'] ?? null) { $where[] = '(t.subject LIKE ? OR t.code LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $rows = $this->db->all(
            "SELECT t.id, t.code, t.subject, t.description, t.status, t.priority, t.channel,
                    t.requester_name, t.requester_email, t.category_id, t.company_id, t.assigned_to,
                    t.created_at, t.updated_at, t.resolved_at, t.closed_at, t.escalation_level
             FROM tickets t WHERE " . implode(' AND ', $where) . "
             ORDER BY t.updated_at DESC LIMIT $limit OFFSET $offset", $args);
        $total = (int)$this->db->val("SELECT COUNT(*) FROM tickets t WHERE " . implode(' AND ', $where), $args);
        $this->json(['data' => $rows, 'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset]]);
    }

    public function ticketsShow(array $params): void
    {
        $this->authenticate('read');
        $row = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [(int)$params['id'], $this->tid()]);
        if (!$row) $this->error('Ticket no encontrado', 404, 'not_found');
        $this->json(['data' => $row]);
    }

    public function ticketsCreate(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $subject = trim((string)$this->in($b, 'subject', ''));
        if ($subject === '') $this->error('subject es requerido', 422, 'validation_error');
        $id = $this->db->insert('tickets', [
            'tenant_id' => $this->tid(),
            'code' => 'TMP-' . bin2hex(random_bytes(4)),
            'subject' => $subject,
            'description' => (string)$this->in($b, 'description', ''),
            'status' => (string)$this->in($b, 'status', 'open'),
            'priority' => (string)$this->in($b, 'priority', 'medium'),
            'channel' => in_array((string)$this->in($b, 'channel', 'portal'), ['portal','email','phone','chat','internal'], true) ? (string)$this->in($b, 'channel', 'portal') : 'portal',
            'category_id' => ($cid = (int)$this->in($b, 'category_id', 0)) ?: null,
            'company_id'  => ($coid = (int)$this->in($b, 'company_id', 0)) ?: null,
            'assigned_to' => ($at = (int)$this->in($b, 'assigned_to', 0)) ?: null,
            'requester_name'  => (string)$this->in($b, 'requester_name', ''),
            'requester_email' => (string)$this->in($b, 'requester_email', ''),
            'requester_phone' => (string)$this->in($b, 'requester_phone', ''),
            'tags' => (string)$this->in($b, 'tags', ''),
            'created_by' => $this->uid(),
            'public_token' => bin2hex(random_bytes(16)),
        ]);
        $this->db->update('tickets', ['code' => Helpers::ticketCode($this->tid(), $id)], 'id=?', [$id]);
        $row = $this->db->one('SELECT * FROM tickets WHERE id=?', [$id]);
        $this->json(['data' => $row], 201);
    }

    public function ticketsUpdate(array $params): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id, status FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Ticket no encontrado', 404, 'not_found');
        $allowed = ['subject','description','status','priority','category_id','company_id','assigned_to','tags'];
        $upd = [];
        foreach ($allowed as $f) if (array_key_exists($f, $b)) $upd[$f] = $b[$f];
        if (($upd['status'] ?? null) === 'resolved' && empty($exists['resolved_at'])) $upd['resolved_at'] = date('Y-m-d H:i:s');
        if (($upd['status'] ?? null) === 'closed') $upd['closed_at'] = date('Y-m-d H:i:s');
        if ($upd) $this->db->update('tickets', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['data' => $this->db->one('SELECT * FROM tickets WHERE id=?', [$id])]);
    }

    public function ticketsDelete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Ticket no encontrado', 404, 'not_found');
        $this->db->delete('tickets', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['data' => ['deleted' => true, 'id' => $id]]);
    }

    public function commentsIndex(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $rows = $this->db->all('SELECT id, user_id, author_name, author_email, body, is_internal, created_at FROM ticket_comments WHERE ticket_id=? AND tenant_id=? ORDER BY created_at ASC', [$id, $this->tid()]);
        $this->json(['data' => $rows]);
    }

    public function commentsCreate(array $params): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $body = trim((string)$this->in($b, 'body', ''));
        if ($body === '') $this->error('body es requerido', 422, 'validation_error');
        $id = (int)$params['id'];
        $tk = $this->db->one('SELECT id FROM tickets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$tk) $this->error('Ticket no encontrado', 404, 'not_found');
        $cid = $this->db->insert('ticket_comments', [
            'tenant_id' => $this->tid(),
            'ticket_id' => $id,
            'user_id' => $this->uid(),
            'author_name' => $this->in($b, 'author_name', $this->ctx['user']['name'] ?? 'API'),
            'author_email' => $this->in($b, 'author_email', $this->ctx['user']['email'] ?? ''),
            'body' => $body,
            'is_internal' => (int)($this->in($b, 'is_internal', 0) ? 1 : 0),
        ]);
        $this->db->run('UPDATE tickets SET updated_at=NOW(), first_response_at=COALESCE(first_response_at, NOW()) WHERE id=?', [$id]);
        $this->json(['data' => $this->db->one('SELECT * FROM ticket_comments WHERE id=?', [$cid])], 201);
    }

    // ==================== CATEGORIES ====================

    public function categoriesIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, color, icon, created_at FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$this->tid()]);
        $this->json(['data' => $rows]);
    }

    public function categoriesCreate(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $name = trim((string)$this->in($b, 'name', ''));
        if ($name === '') $this->error('name es requerido', 422);
        $id = $this->db->insert('ticket_categories', [
            'tenant_id' => $this->tid(),
            'name' => $name,
            'color' => (string)$this->in($b, 'color', '#7c5cff'),
            'icon'  => (string)$this->in($b, 'icon', 'tag'),
        ]);
        $this->json(['data' => $this->db->one('SELECT * FROM ticket_categories WHERE id=?', [$id])], 201);
    }

    // ==================== COMPANIES ====================

    public function companiesIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, industry, tier, website, created_at FROM companies WHERE tenant_id=? ORDER BY name', [$this->tid()]);
        $this->json(['data' => $rows]);
    }

    public function companiesCreate(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $name = trim((string)$this->in($b, 'name', ''));
        if ($name === '') $this->error('name es requerido', 422);
        $id = $this->db->insert('companies', [
            'tenant_id' => $this->tid(),
            'name' => $name,
            'industry' => (string)$this->in($b, 'industry', ''),
            'tier' => (string)$this->in($b, 'tier', 'standard'),
            'website' => (string)$this->in($b, 'website', ''),
        ]);
        $this->json(['data' => $this->db->one('SELECT * FROM companies WHERE id=?', [$id])], 201);
    }

    // ==================== USERS ====================

    public function usersIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, email, title, is_technician, is_active, role_id, created_at FROM users WHERE tenant_id=? ORDER BY name', [$this->tid()]);
        $this->json(['data' => $rows]);
    }

    // ==================== KB ====================

    public function kbIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all("SELECT id, title, slug, excerpt, status, visibility, views, created_at FROM kb_articles WHERE tenant_id=? AND status='published' ORDER BY views DESC", [$this->tid()]);
        $this->json(['data' => $rows]);
    }

    // ==================== SLA / AUTOMATIONS ====================

    public function slaIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, priority, response_minutes, resolve_minutes, active FROM sla_policies WHERE tenant_id=? ORDER BY FIELD(priority,"urgent","high","medium","low")', [$this->tid()]);
        $this->json(['data' => $rows]);
    }

    public function automationsIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, description, trigger_event, conditions, actions, active, run_count, last_run_at FROM automations WHERE tenant_id=?', [$this->tid()]);
        foreach ($rows as &$r) {
            $r['conditions'] = json_decode((string)$r['conditions'], true);
            $r['actions'] = json_decode((string)$r['actions'], true);
        }
        $this->json(['data' => $rows]);
    }

    // ==================== STATS ====================

    public function stats(): void
    {
        $this->authenticate('read');
        $tid = $this->tid();
        $this->json(['data' => [
            'tickets' => [
                'total'       => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=?", [$tid]),
                'open'        => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='open'", [$tid]),
                'in_progress' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='in_progress'", [$tid]),
                'resolved'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='resolved'", [$tid]),
                'closed'      => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='closed'", [$tid]),
                'this_month'  => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tid]),
            ],
            'sla' => [
                'breached' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND sla_breached=1", [$tid]),
            ],
            'users' => (int)$this->db->val("SELECT COUNT(*) FROM users WHERE tenant_id=?", [$tid]),
            'companies' => (int)$this->db->val("SELECT COUNT(*) FROM companies WHERE tenant_id=?", [$tid]),
        ]]);
    }
}
