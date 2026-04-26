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
     *  - Developer tokens (dev_api_tokens table): bound to a developer + app, with quota enforcement.
     *
     * Returns:
     *   ['type'=>'tenant', 'token'=>..., 'tenant'=>Tenant, 'user'=>?array]
     *   ['type'=>'developer', 'token'=>..., 'developer'=>array, 'app'=>array, 'tenant'=>Tenant, 'limits'=>array]
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
            $developer = $db->one('SELECT * FROM developers WHERE id=?', [(int)$devTok['developer_id']]);
            if (!$developer || (int)$developer['is_active'] !== 1 || !empty($developer['suspended_at'])) {
                return ['type' => 'developer', 'denied' => 'developer_suspended', 'token' => $devTok, 'developer' => $developer, 'app' => null, 'tenant' => null];
            }
            $appRow = $db->one('SELECT * FROM dev_apps WHERE id=?', [(int)$devTok['app_id']]);
            if (!$appRow || $appRow['status'] !== 'active') {
                return ['type' => 'developer', 'denied' => 'app_suspended', 'token' => $devTok, 'developer' => $developer, 'app' => $appRow, 'tenant' => null];
            }

            // Resolve the app's sandbox tenant (it should always exist; if not, lazy-create)
            $tenant = null;
            if (!empty($appRow['tenant_id'])) {
                $tenant = Tenant::find((int)$appRow['tenant_id']);
            }
            if (!$tenant) {
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

            $limits = DevAuth::effectiveLimits($db, (int)$developer['id']);

            // Effective subscription required
            if (!$limits['has_subscription']) {
                return ['type' => 'developer', 'denied' => 'no_subscription', 'token' => $devTok, 'developer' => $developer, 'app' => $appRow, 'tenant' => $tenant, 'limits' => $limits];
            }
            // Subscription must be in active-ish state for non-trial; trial OK
            if (!in_array($limits['sub_status'], ['active','trial','past_due'], true)) {
                return ['type' => 'developer', 'denied' => 'subscription_inactive', 'token' => $devTok, 'developer' => $developer, 'app' => $appRow, 'tenant' => $tenant, 'limits' => $limits];
            }

            $db->update('dev_api_tokens', [
                'last_used_at' => date('Y-m-d H:i:s'),
                'last_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ], 'id=?', [(int)$devTok['id']]);

            return [
                'type' => 'developer',
                'token' => $devTok,
                'developer' => $developer,
                'app' => $appRow,
                'tenant' => $tenant,
                'user' => null,
                'limits' => $limits,
            ];
        }

        return null;
    }

    /**
     * Enforce per-plan policies on a developer auth context.
     * Returns null if all policies pass; otherwise returns ['code'=>..., 'message'=>..., 'status'=>...].
     *
     * Tenant tokens skip enforcement (their plan is enforced by the tenant license layer).
     */
    public static function enforcePolicies(array $ctx, ?Database $db = null): ?array
    {
        if (($ctx['type'] ?? null) !== 'developer') return null;

        // Already-denied cases from authenticate()
        if (!empty($ctx['denied'])) {
            $messages = [
                'developer_suspended' => 'Tu cuenta de developer está suspendida. Contacta al soporte.',
                'app_suspended' => 'Esta app está suspendida o archivada.',
                'no_subscription' => 'No hay suscripción activa para este developer.',
                'subscription_inactive' => 'La suscripción no está activa (estado: ' . ($ctx['limits']['sub_status'] ?? '?') . ').',
            ];
            return [
                'code' => $ctx['denied'],
                'message' => $messages[$ctx['denied']] ?? 'Acceso denegado.',
                'status' => 403,
            ];
        }

        $db = $db ?? Application::get()->db;
        $devId = (int)($ctx['developer']['id'] ?? 0);
        $appId = (int)($ctx['app']['id'] ?? 0);
        $limits = $ctx['limits'] ?? [];

        // Get global enforcement flags
        $enforceQuota = self::setting($db, 'dev_portal_enforce_quota', '1') === '1';
        $enforceRate = self::setting($db, 'dev_portal_enforce_rate_limit', '1') === '1';
        $blockOnOverage = self::setting($db, 'dev_portal_block_on_overage', '0') === '1';

        // Rate limit: requests in the last minute
        if ($enforceRate && $limits['rate_limit_per_min'] > 0) {
            $count = (int)$db->val(
                'SELECT COUNT(*) FROM dev_api_request_log WHERE developer_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
                [$devId]
            );
            if ($count >= $limits['rate_limit_per_min']) {
                return [
                    'code' => 'rate_limit_exceeded',
                    'message' => "Has alcanzado el rate limit ({$limits['rate_limit_per_min']} req/min). Espera un momento e intenta de nuevo.",
                    'status' => 429,
                    'retry_after' => 60,
                ];
            }
        }

        // Monthly quota
        if ($enforceQuota && $limits['max_requests_month'] > 0) {
            $month = (int)$db->val(
                "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
                [$devId]
            );
            if ($month >= $limits['max_requests_month']) {
                // If overage allowed and pricing > 0, allow but flag (super admin will bill later)
                if (!$blockOnOverage && $limits['overage_price_per_1k'] > 0) {
                    // allow through, but mark in headers
                    if (function_exists('header')) {
                        header('X-Quota-Status: overage');
                        header("X-Quota-Used: $month");
                        header("X-Quota-Limit: {$limits['max_requests_month']}");
                    }
                    return null;
                }
                return [
                    'code' => 'quota_exceeded',
                    'message' => "Cuota mensual agotada ({$limits['max_requests_month']} requests). Mejora tu plan.",
                    'status' => 429,
                    'used' => $month,
                    'limit' => $limits['max_requests_month'],
                ];
            }
            // Approaching limit: add warning header
            $alertPct = (int)self::setting($db, 'dev_portal_alert_at_pct', '80');
            $usedPct = ($month / max(1, $limits['max_requests_month'])) * 100;
            if (function_exists('header')) {
                header("X-Quota-Used: $month");
                header("X-Quota-Limit: {$limits['max_requests_month']}");
                header("X-Quota-Pct: " . round($usedPct, 1));
                if ($usedPct >= $alertPct) header('X-Quota-Warning: approaching-limit');
            }
        }

        // Rate limit hint headers (informational)
        if ($limits['rate_limit_per_min'] > 0 && function_exists('header')) {
            header("X-RateLimit-Limit: {$limits['rate_limit_per_min']}");
        }

        return null;
    }

    /**
     * Records a single request to dev_api_request_log + aggregate dev_api_usage.
     * Should be called AFTER policy enforcement so even denied calls get logged
     * (for auditing rate-limit hits).
     */
    public static function logRequest(array $ctx, int $statusCode = 200, int $durationMs = 0, ?Database $db = null): void
    {
        if (($ctx['type'] ?? null) !== 'developer') return;
        $db = $db ?? Application::get()->db;
        $devId = (int)($ctx['developer']['id'] ?? 0);
        $appId = (int)($ctx['app']['id'] ?? 0);
        $tokId = (int)($ctx['token']['id'] ?? 0);
        if (!$devId) return;

        try {
            $db->insert('dev_api_request_log', [
                'developer_id' => $devId,
                'app_id' => $appId ?: null,
                'token_id' => $tokId ?: null,
                'method' => substr((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), 0, 10),
                'path' => substr((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), 0, 255),
                'status_code' => $statusCode,
                'duration_ms' => $durationMs,
                'ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);

            // Aggregate
            $today = date('Y-m-d');
            $isError = $statusCode >= 400;
            $existing = $db->one('SELECT id FROM dev_api_usage WHERE developer_id=? AND app_id=? AND period_date=?', [$devId, $appId, $today]);
            if ($existing) {
                $db->run(
                    'UPDATE dev_api_usage SET requests=requests+1, errors=errors+?, last_at=NOW() WHERE id=?',
                    [$isError ? 1 : 0, (int)$existing['id']]
                );
            } else {
                $db->insert('dev_api_usage', [
                    'developer_id' => $devId,
                    'app_id' => $appId ?: null,
                    'token_id' => $tokId ?: null,
                    'period_date' => $today,
                    'requests' => 1,
                    'errors' => $isError ? 1 : 0,
                    'last_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) { /* never break the request because of logging */ }
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

    protected static function setting(Database $db, string $key, ?string $default = null): ?string
    {
        try {
            $row = $db->one('SELECT `value` FROM saas_settings WHERE `key` = ?', [$key]);
            return $row['value'] ?? $default;
        } catch (\Throwable $e) { return $default; }
    }
}
