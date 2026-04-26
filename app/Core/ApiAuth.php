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

    /** @return array{token:array,tenant:Tenant,user:?array}|null */
    public static function authenticate(?Database $db = null): ?array
    {
        $db = $db ?? Application::get()->db;
        $raw = self::extractBearer();
        if (!$raw) return null;
        $hash = hash('sha256', $raw);
        $token = $db->one('SELECT * FROM api_tokens WHERE token_hash=? AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())', [$hash]);
        if (!$token) return null;
        $tenant = Tenant::find((int)$token['tenant_id']);
        if (!$tenant) return null;
        $user = $token['user_id'] ? $db->one('SELECT id, name, email, role_id FROM users WHERE id=?', [(int)$token['user_id']]) : null;
        $db->update('api_tokens', [
            'last_used_at' => date('Y-m-d H:i:s'),
            'last_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ], 'id=?', [(int)$token['id']]);
        return ['token' => $token, 'tenant' => $tenant, 'user' => $user];
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
