<?php
namespace App\Controllers\Api;

use App\Core\ApiAuth;

class MetaController extends BaseApiController
{
    public function index(): void
    {
        $this->json([
            'name' => 'Kydesk Helpdesk API',
            'version' => 'v1',
            'documentation' => rtrim($this->app->config['app']['url'], '/') . '/developers/docs',
            'openapi' => rtrim($this->app->config['app']['url'], '/') . '/api/v1/openapi.json',
            'postman' => rtrim($this->app->config['app']['url'], '/') . '/api/v1/postman.json',
            'changelog' => rtrim($this->app->config['app']['url'], '/') . '/developers/docs#changelog',
            'auth' => 'Bearer token en header Authorization. Ver /developers/docs#authentication',
            'resources' => [
                'tickets' => '/api/v1/tickets',
                'comments' => '/api/v1/tickets/{id}/comments',
                'categories' => '/api/v1/categories',
                'companies' => '/api/v1/companies',
                'users' => '/api/v1/users',
                'kb' => '/api/v1/kb/articles',
                'sla' => '/api/v1/sla',
                'automations' => '/api/v1/automations',
                'assets' => '/api/v1/assets',
                'search' => '/api/v1/search?q=...',
                'stats' => '/api/v1/stats',
                'health' => '/api/v1/health',
                'me' => '/api/v1/me',
            ],
        ]);
    }

    public function me(): void
    {
        $this->authenticate('read');
        $type = $this->ctx['type'] ?? 'tenant';
        $payload = [
            'type' => $type,
            'tenant' => $this->ctx['tenant'] ? [
                'id' => $this->ctx['tenant']->id,
                'slug' => $this->ctx['tenant']->slug,
                'name' => $this->ctx['tenant']->name,
            ] : null,
            'user' => $this->ctx['user'] ?? null,
            'token' => [
                'name' => $this->ctx['token']['name'],
                'preview' => $this->ctx['token']['token_preview'],
                'scopes' => explode(',', $this->ctx['token']['scopes']),
                'last_used_at' => $this->ctx['token']['last_used_at'],
            ],
        ];
        if ($type === 'developer') {
            $payload['developer'] = [
                'id' => $this->ctx['developer']['id'],
                'name' => $this->ctx['developer']['name'],
                'email' => $this->ctx['developer']['email'],
                'company' => $this->ctx['developer']['company'] ?? null,
            ];
            $payload['app'] = [
                'id' => $this->ctx['app']['id'],
                'name' => $this->ctx['app']['name'],
                'slug' => $this->ctx['app']['slug'],
                'environment' => $this->ctx['app']['environment'],
            ];
            $payload['limits'] = $this->ctx['limits'] ?? null;
        }
        $this->json($payload);
    }

    public function health(): void
    {
        $dbOk = false; $dbLatencyMs = 0;
        try {
            $start = microtime(true);
            $this->db->val('SELECT 1');
            $dbLatencyMs = (int)((microtime(true) - $start) * 1000);
            $dbOk = true;
        } catch (\Throwable $e) { /* down */ }

        $this->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'version' => 'v1',
            'time' => date('c'),
            'checks' => [
                'database' => ['status' => $dbOk ? 'ok' : 'down', 'latency_ms' => $dbLatencyMs],
            ],
        ]);
    }

    public function stats(): void
    {
        $this->authenticate('read');
        $tid = $this->tid();
        $this->json([
            'tickets' => [
                'total'       => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=?", [$tid]),
                'open'        => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='open'", [$tid]),
                'in_progress' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='in_progress'", [$tid]),
                'on_hold'     => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='on_hold'", [$tid]),
                'resolved'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='resolved'", [$tid]),
                'closed'      => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='closed'", [$tid]),
                'by_priority' => [
                    'urgent' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND priority='urgent'", [$tid]),
                    'high'   => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND priority='high'", [$tid]),
                    'medium' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND priority='medium'", [$tid]),
                    'low'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND priority='low'", [$tid]),
                ],
                'this_month'  => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tid]),
                'this_week'   => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", [$tid]),
                'today'       => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND DATE(created_at)=CURDATE()", [$tid]),
            ],
            'sla' => [
                'breached'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND sla_breached=1", [$tid]),
                'compliance_pct' => (function() use ($tid) {
                    $total = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=?", [$tid]);
                    if ($total === 0) return 100.0;
                    $breached = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND sla_breached=1", [$tid]);
                    return round((($total - $breached) / $total) * 100, 2);
                })(),
            ],
            'users' => (int)$this->db->val("SELECT COUNT(*) FROM users WHERE tenant_id=?", [$tid]),
            'companies' => (int)$this->db->val("SELECT COUNT(*) FROM companies WHERE tenant_id=?", [$tid]),
            'kb_articles' => (int)$this->db->val("SELECT COUNT(*) FROM kb_articles WHERE tenant_id=?", [$tid]),
            'assets' => (int)$this->db->val("SELECT COUNT(*) FROM assets WHERE tenant_id=?", [$tid]),
            'avg_resolution_minutes' => (int)$this->db->val(
                "SELECT IFNULL(AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)),0) FROM tickets WHERE tenant_id=? AND resolved_at IS NOT NULL",
                [$tid]
            ),
            'csat_avg' => (float)$this->db->val(
                "SELECT IFNULL(AVG(satisfaction_rating),0) FROM tickets WHERE tenant_id=? AND satisfaction_rating IS NOT NULL",
                [$tid]
            ),
            'generated_at' => date('c'),
        ]);
    }

    public function search(): void
    {
        $this->authenticate('read');
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) $this->error('Parámetro q (mín 2 caracteres) requerido', 422, 'validation_error');
        $like = "%$q%";
        $tid = $this->tid();
        $limit = min(20, (int)($_GET['limit'] ?? 5));

        $tickets = $this->db->all('SELECT id, code, subject, status, priority FROM tickets WHERE tenant_id=? AND (subject LIKE ? OR code LIKE ? OR description LIKE ?) ORDER BY updated_at DESC LIMIT ' . $limit, [$tid, $like, $like, $like]);
        $companies = $this->db->all('SELECT id, name, industry, tier FROM companies WHERE tenant_id=? AND (name LIKE ? OR website LIKE ?) ORDER BY name LIMIT ' . $limit, [$tid, $like, $like]);
        $users = $this->db->all('SELECT id, name, email FROM users WHERE tenant_id=? AND (name LIKE ? OR email LIKE ?) ORDER BY name LIMIT ' . $limit, [$tid, $like, $like]);
        $kb = $this->db->all('SELECT id, title, slug, status FROM kb_articles WHERE tenant_id=? AND (title LIKE ? OR excerpt LIKE ? OR body LIKE ?) ORDER BY views DESC LIMIT ' . $limit, [$tid, $like, $like, $like]);

        $this->json([
            'query' => $q,
            'results' => [
                'tickets'   => ['count' => count($tickets), 'data' => $tickets],
                'companies' => ['count' => count($companies), 'data' => $companies],
                'users'     => ['count' => count($users), 'data' => $users],
                'kb_articles' => ['count' => count($kb), 'data' => $kb],
            ],
            'total' => count($tickets) + count($companies) + count($users) + count($kb),
        ]);
    }
}
