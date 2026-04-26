<?php
namespace App\Core;

class DevAuth
{
    protected ?array $developer = null;
    protected bool $loaded = false;
    protected const SESSION_KEY = 'dev_uid';

    public function __construct(protected Database $db, protected Session $session) {}

    public function attempt(string $email, string $password): ?array
    {
        $row = $this->db->one('SELECT * FROM developers WHERE email = :e LIMIT 1', ['e' => $email]);
        if (!$row) return null;
        if (!password_verify($password, $row['password'])) return null;
        if ((int)$row['is_active'] !== 1) return null;
        if (!empty($row['suspended_at'])) return null;

        $this->session->regenerate();
        $this->session->put(self::SESSION_KEY, (int)$row['id']);
        $this->db->update('developers', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 60),
        ], 'id = :id', ['id' => $row['id']]);
        $this->loaded = false;
        return $row;
    }

    public function logout(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function check(): bool
    {
        return $this->session->get(self::SESSION_KEY) !== null;
    }

    public function developer(): ?array
    {
        if ($this->loaded) return $this->developer;
        $id = $this->session->get(self::SESSION_KEY);
        if (!$id) { $this->loaded = true; return null; }
        $this->developer = $this->db->one('SELECT * FROM developers WHERE id = :i', ['i' => $id]);
        $this->loaded = true;
        return $this->developer;
    }

    public function id(): ?int
    {
        $d = $this->developer();
        return $d ? (int)$d['id'] : null;
    }

    public function activeSubscription(): ?array
    {
        $id = $this->id();
        if (!$id) return null;
        return $this->db->one(
            "SELECT s.*, p.slug AS plan_slug, p.name AS plan_name, p.max_apps, p.max_requests_month,
                    p.max_tokens_per_app, p.rate_limit_per_min, p.features, p.color AS plan_color,
                    p.icon AS plan_icon, p.price_monthly, p.price_yearly, p.overage_price_per_1k
             FROM dev_subscriptions s
             JOIN dev_plans p ON p.id = s.plan_id
             WHERE s.developer_id = :id AND s.status IN ('trial','active','past_due')
             ORDER BY FIELD(s.status,'active','trial','past_due'), s.id DESC LIMIT 1",
            ['id' => $id]
        );
    }

    public function plan(): ?array
    {
        $sub = $this->activeSubscription();
        if (!$sub) return null;
        return [
            'slug' => $sub['plan_slug'],
            'name' => $sub['plan_name'],
            'max_apps' => (int)$sub['max_apps'],
            'max_requests_month' => (int)$sub['max_requests_month'],
            'max_tokens_per_app' => (int)$sub['max_tokens_per_app'],
            'rate_limit_per_min' => (int)$sub['rate_limit_per_min'],
            'features' => json_decode((string)$sub['features'], true) ?: [],
            'color' => $sub['plan_color'],
            'icon' => $sub['plan_icon'],
        ];
    }

    public function hasFeature(string $feature): bool
    {
        $p = $this->plan();
        if (!$p) return false;
        return in_array($feature, $p['features'] ?? [], true);
    }

    public function log(string $action, ?string $entity = null, ?int $entityId = null, $meta = null): void
    {
        try {
            $this->db->insert('dev_audit_logs', [
                'developer_id' => $this->id(),
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'meta' => is_string($meta) ? $meta : ($meta ? json_encode($meta) : null),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (\Throwable $e) {}
    }
}
