<?php
namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\Application;
use App\Core\Database;
use App\Core\Helpers;

/**
 * Base controller for all API v1 endpoints.
 *
 * Provides:
 *  - Bearer auth + scope enforcement (with rate-limit + quota policies for dev tokens)
 *  - Uniform JSON response shape: { data, meta, links } / { error }
 *  - Pagination (offset + cursor), sort, filter, expand, fields helpers
 *  - Idempotency-Key support for unsafe verbs
 *  - Request-Id propagation, ETag generation, deprecation headers
 *  - Audit logging on developer tokens
 */
abstract class BaseApiController
{
    protected Application $app;
    protected Database $db;
    /** @var array{type:string,token:array,tenant:?\App\Core\Tenant,user:?array,developer?:array,app?:array,limits?:array,denied?:string} */
    protected array $ctx = [];
    protected float $reqStart = 0.0;
    protected string $requestId = '';
    /** @var int max items per page */
    protected int $maxPerPage = 100;

    public function __construct()
    {
        $this->app = Application::get();
        $this->db = $this->app->db;
        $this->reqStart = microtime(true);
        $this->requestId = bin2hex(random_bytes(8));

        // CORS preflight
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Idempotency-Key, X-Request-Id, If-Match, X-API-Version');
        header('Access-Control-Expose-Headers: X-Request-Id, X-RateLimit-Limit, X-RateLimit-Remaining, X-Quota-Used, X-Quota-Limit, X-Quota-Pct, X-Quota-Warning, X-API-Version, ETag, Retry-After, Deprecation, Sunset');
        header('X-Request-Id: ' . $this->requestId);
        header('X-API-Version: v1');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    // ─── Authentication ──────────────────────────────────────────────

    protected function authenticate(string $needScope = 'read'): void
    {
        $ctx = ApiAuth::authenticate($this->db);
        if (!$ctx) {
            $this->error('Token API inválido o ausente. Envía Authorization: Bearer {token}', 401, 'unauthorized', [
                'docs' => $this->app->config['app']['url'] . '/developers/docs#authentication',
            ]);
        }

        if (($ctx['type'] ?? null) === 'developer') {
            $deny = ApiAuth::enforcePolicies($ctx, $this->db);
            if ($deny) {
                if (!empty($deny['retry_after'])) header('Retry-After: ' . (int)$deny['retry_after']);
                ApiAuth::logRequest($ctx, $deny['status'] ?? 403, $this->elapsedMs(), $this->db);
                $extra = [];
                if (isset($deny['used'])) $extra['used'] = $deny['used'];
                if (isset($deny['limit'])) $extra['limit'] = $deny['limit'];
                if (isset($deny['retry_after'])) $extra['retry_after'] = $deny['retry_after'];
                $this->error($deny['message'], $deny['status'] ?? 403, $deny['code'] ?? 'forbidden', $extra);
            }
        }

        if (!ApiAuth::requireScope($ctx, $needScope)) {
            if (($ctx['type'] ?? null) === 'developer') {
                ApiAuth::logRequest($ctx, 403, $this->elapsedMs(), $this->db);
            }
            $this->error("Token sin scope '$needScope'. Genera un token con permisos suficientes.", 403, 'insufficient_scope', [
                'required_scope' => $needScope,
                'token_scopes' => explode(',', (string)($ctx['token']['scopes'] ?? '')),
            ]);
        }

        $this->ctx = $ctx;

        // Log on shutdown so every request is traced (dev tokens only)
        if (($ctx['type'] ?? null) === 'developer') {
            register_shutdown_function(function () use ($ctx) {
                $code = http_response_code() ?: 200;
                ApiAuth::logRequest($ctx, (int)$code, $this->elapsedMs(), $this->db);
            });
        }
    }

    protected function tid(): int { return (int)$this->ctx['tenant']->id; }
    protected function uid(): ?int { return isset($this->ctx['user']['id']) ? (int)$this->ctx['user']['id'] : null; }

    // ─── Body parsing ───────────────────────────────────────────────

    /** @return array */
    protected function body(): array
    {
        if (!empty($_POST)) return $_POST;
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $j = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    protected function in(array $b, string $k, $default = null) { return $b[$k] ?? $default; }

    protected function require(array $b, array $keys): void
    {
        $missing = [];
        foreach ($keys as $k) {
            if (!isset($b[$k]) || $b[$k] === '' || $b[$k] === null) $missing[] = $k;
        }
        if ($missing) $this->error('Faltan campos requeridos: ' . implode(', ', $missing), 422, 'validation_error', ['missing' => $missing]);
    }

    // ─── Idempotency ────────────────────────────────────────────────

    protected function idempotencyKey(): ?string
    {
        $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null;
        if (!$key) return null;
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key) ?: null;
    }

    protected function checkIdempotency(): ?array
    {
        $key = $this->idempotencyKey();
        if (!$key) return null;
        try {
            $row = $this->db->one(
                "SELECT response_json, status_code FROM api_idempotency
                 WHERE idem_key=? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1",
                [$this->idempotencyHash($key)]
            );
            if ($row) return ['cached' => json_decode($row['response_json'], true), 'status' => (int)$row['status_code']];
        } catch (\Throwable $e) { /* table missing */ }
        return null;
    }

    protected function storeIdempotent(array $payload, int $status): void
    {
        $key = $this->idempotencyKey();
        if (!$key) return;
        try {
            $this->db->insert('api_idempotency', [
                'idem_key' => $this->idempotencyHash($key),
                'tenant_id' => $this->ctx['tenant']->id ?? null,
                'developer_id' => $this->ctx['developer']['id'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                'path' => substr((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), 0, 255),
                'status_code' => $status,
                'response_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) { /* duplicate or table missing */ }
    }

    protected function idempotencyHash(string $key): string { return hash('sha256', $key); }

    // ─── JSON output ────────────────────────────────────────────────

    protected function json(array $data, int $code = 200, array $meta = [], array $links = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['data' => $data];
        if ($meta) $payload['meta'] = $meta;
        if ($links) $payload['links'] = $links;
        $payload['request_id'] = $this->requestId;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        // ETag for GETs (helps caching)
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $etag = '"' . substr(sha1($body), 0, 16) . '"';
            header("ETag: $etag");
            $ifNone = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
            if ($ifNone === $etag) {
                http_response_code(304);
                exit;
            }
        }
        echo $body;
        $this->storeIdempotent(['data' => $data, 'meta' => $meta, 'links' => $links], $code);
        exit;
    }

    protected function created(array $data, ?string $location = null): void
    {
        if ($location) header('Location: ' . $location);
        $this->json($data, 201);
    }

    protected function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * @param string $message Human-readable error description
     * @param int $code HTTP status
     * @param string $type Stable error code (e.g. validation_error, not_found)
     * @param array $extra Additional fields embedded in the error
     */
    protected function error(string $message, int $code = 400, string $type = 'request_error', array $extra = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'error' => array_merge([
                'type' => $type,
                'message' => $message,
                'status' => $code,
                'request_id' => $this->requestId,
            ], $extra),
        ];
        // Log error for dev tokens
        if (($this->ctx['type'] ?? null) === 'developer') {
            ApiAuth::logRequest($this->ctx, $code, $this->elapsedMs(), $this->db);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ─── Pagination + filters ───────────────────────────────────────

    /**
     * Resolve pagination from query string.
     * Supports ?page=N&per_page=K (offset) OR ?cursor=XYZ (opaque cursor)
     */
    protected function paginate(array $params = []): array
    {
        $perPage = max(1, min($this->maxPerPage, (int)($_GET['per_page'] ?? $_GET['limit'] ?? 25)));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = (int)($_GET['offset'] ?? (($page - 1) * $perPage));
        return [
            'limit' => $perPage,
            'offset' => $offset,
            'page' => $page,
        ];
    }

    protected function sortClause(array $allowed, string $default = 'id', string $dir = 'DESC'): string
    {
        $sort = (string)($_GET['sort'] ?? $default);
        $desc = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');
        if (!in_array($field, $allowed, true)) $field = $default;
        $dirEff = $desc ? 'DESC' : ($dir === 'DESC' && $sort === $default ? 'DESC' : 'ASC');
        return "$field $dirEff";
    }

    protected function filterFields(array $row, array $whitelist): array
    {
        $fields = $_GET['fields'] ?? null;
        if (!$fields) return array_intersect_key($row, array_flip($whitelist)) ?: $row;
        $requested = array_filter(array_map('trim', explode(',', (string)$fields)));
        $allowed = array_intersect($requested, $whitelist);
        if (!$allowed) return array_intersect_key($row, array_flip($whitelist));
        return array_intersect_key($row, array_flip($allowed));
    }

    protected function shouldExpand(string $relation): bool
    {
        $expand = $_GET['expand'] ?? $_GET['include'] ?? '';
        $list = array_map('trim', explode(',', (string)$expand));
        return in_array($relation, $list, true);
    }

    // ─── Headers / utility ──────────────────────────────────────────

    protected function elapsedMs(): int
    {
        return (int)((microtime(true) - $this->reqStart) * 1000);
    }

    protected function ifMatchOk(?string $currentEtag): void
    {
        $ifMatch = $_SERVER['HTTP_IF_MATCH'] ?? '';
        if ($ifMatch === '' || $currentEtag === null) return;
        if ($ifMatch !== $currentEtag) {
            $this->error('La versión del recurso ha cambiado (If-Match no coincide). Recarga y reintenta.', 412, 'precondition_failed');
        }
    }

    protected function paginatedLinks(string $basePath, int $offset, int $limit, int $total): array
    {
        $base = rtrim($this->app->config['app']['url'], '/') . $basePath;
        $qs = function (array $params) use ($base) {
            $q = http_build_query($params);
            return $base . ($q ? '?' . $q : '');
        };
        $first = $qs(['offset' => 0, 'limit' => $limit]);
        $last = $qs(['offset' => max(0, ($total - 1) - (($total - 1) % $limit)), 'limit' => $limit]);
        $next = ($offset + $limit) < $total ? $qs(['offset' => $offset + $limit, 'limit' => $limit]) : null;
        $prev = $offset > 0 ? $qs(['offset' => max(0, $offset - $limit), 'limit' => $limit]) : null;
        return ['first' => $first, 'prev' => $prev, 'next' => $next, 'last' => $last, 'self' => $qs(['offset' => $offset, 'limit' => $limit])];
    }
}
