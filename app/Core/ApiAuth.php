<?php
namespace App\Core;

class ApiAuth
{
    public static function generate(): array
    {
        $raw = 'kyd_' . bin2hex(random_bytes(24)); // 52 chars total
        return [
            'token' => $raw,
            'hash'  => hash('sha256', $raw),
            'preview' => substr($raw, 0, 8) . '...' . substr($raw, -4),
        ];
    }

    /**
     * Authenticates the request and returns the auth context.
     *
     * Supports two flavors of API tokens:
     *  - Tenant tokens (api_tokens table): bound to a tenant + optional user.
     *  - Developer tokens (dev_api_tokens table): bound to a developer + app, no tenant.
     *
     * Returns:
     *   ['type'=>'tenant', 'token'=>..., 'tenant'=>Tenant, 'user'=>?array]
     *   ['type'=>'developer', 'token'=>..., 'developer'=>array, 'app'=>array]
     *
     * @return array|null
     */
    public static function authenticate(?Database $db = null): ?array
    {
        $db = $db ?? Application::get()->db;
        $raw = self::extractBearer();
        if (!$raw) return null;
        $hash = hash('sha256', $raw);

        // 1) Try tenant token
        $token = $db->one('SELECT * FROM api_tokens WHERE token_hash=? AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())', [$hash]);
        if ($token) {
            $tenant = Tenant::find((int)$token['tenant_id']);
            if (!$tenant) return null;
            $user = $token['user_id'] ? $db->one('SELECT id, name, email, role_id FROM users WHERE id=?', [(int)$token['user_id']]) : null;
            $db->update('api_tokens', [
                'last_used_at' => date('Y-m-d H:i:s'),
                'last_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ], 'id=?', [(int)$token['id']]);
            return [
                'type' => 'tenant',
                'token' => $token,
                'tenant' => $tenant,
                'user' => $user,
            ];
        }

        // 2) Try developer app token
        try {
            $devTok = $db->one('SELECT * FROM dev_api_tokens WHERE token_hash=? AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())', [$hash]);
        } catch (\Throwable $e) {
            return null; // table may not exist yet
        }
        if ($devTok) {
            $developer = $db->one('SELECT id, name, email, company, is_active, suspended_at FROM developers WHERE id=?', [(int)$devTok['developer_id']]);
            if (!$developer || (int)$developer['is_active'] !== 1 || !empty($developer['suspended_at'])) return null;
            $appRow = $db->one('SELECT * FROM dev_apps WHERE id=?', [(int)$devTok['app_id']]);
            if (!$appRow || $appRow['status'] !== 'active') return null;

            // Resolve the app's sandbox tenant (it should always exist; if not, lazy-create)
            $tenant = null;
            if (!empty($appRow['tenant_id'])) {
                $tenant = Tenant::find((int)$appRow['tenant_id']);
            }
            if (!$tenant) {
                // lazy provision
                $slug = 'dev-' . $appRow['slug'] . '-' . substr(bin2hex(random_bytes(3)), 0, 4);
                $tid = $db->insert('tenants', [
                    'name' => $appRow['name'],
                    'slug' => $slug,
                    'plan' => 'pro',
                    'is_active' => 1,
                    'is_developer_sandbox' => 1,
                    'dev_app_id' => $appRow['id'],
                ]);
                $db->update('dev_apps', ['tenant_id' => $tid], 'id=?', [$appRow['id']]);
                $tenant = Tenant::find($tid);
            }

            $db->update('dev_api_tokens', [
                'last_used_at' => date('Y-m-d H:i:s'),
                'last_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ], 'id=?', [(int)$devTok['id']]);

            // Track aggregate usage (best effort)
            try {
                $today = date('Y-m-d');
                $existing = $db->one('SELECT id, requests FROM dev_api_usage WHERE developer_id=? AND app_id=? AND period_date=?', [$developer['id'], $appRow['id'], $today]);
                if ($existing) {
                    $db->run('UPDATE dev_api_usage SET requests = requests + 1, last_at=NOW() WHERE id=?', [(int)$existing['id']]);
                } else {
                    $db->insert('dev_api_usage', [
                        'developer_id' => $developer['id'],
                        'app_id' => $appRow['id'],
                        'token_id' => $devTok['id'],
                        'period_date' => $today,
                        'requests' => 1,
                        'last_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $e) { /* ignore */ }

            return [
                'type' => 'developer',
                'token' => $devTok,
                'developer' => $developer,
                'app' => $appRow,
                'tenant' => $tenant,
                'user' => null,
            ];
        }

        return null;
    }

    public static function extractBearer(): ?string
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!$h && function_exists('apache_request_headers')) {
            $hs = apache_request_headers();
            $h = $hs['Authorization'] ?? $hs['authorization'] ?? '';
        }
        if (preg_match('/Bearer\s+(.+)/i', (string)$h, $m)) return trim($m[1]);
        if (!empty($_GET['api_token'])) return (string)$_GET['api_token']; // dev fallback
        return null;
    }

    public static function requireScope(array $authCtx, string $needed): bool
    {
        $scopes = explode(',', (string)($authCtx['token']['scopes'] ?? ''));
        $scopes = array_map('trim', $scopes);
        return in_array($needed, $scopes, true) || in_array('*', $scopes, true);
    }
}
