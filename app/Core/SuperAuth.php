<?php
namespace App\Core;

class SuperAuth
{
    protected ?array $admin = null;
    protected bool $loaded = false;
    protected const SESSION_KEY = 'sa_uid';

    public function __construct(protected Database $db, protected Session $session) {}

    public function attempt(string $email, string $password): ?array
    {
        $row = $this->db->one('SELECT * FROM super_admins WHERE email = :e LIMIT 1', ['e' => $email]);
        if (!$row) return null;
        if (!password_verify($password, $row['password'])) return null;
        if ((int)$row['is_active'] !== 1) return null;

        $this->session->regenerate();
        $this->session->put(self::SESSION_KEY, (int)$row['id']);
        $this->db->update('super_admins', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
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

    public function admin(): ?array
    {
        if ($this->loaded) return $this->admin;
        $id = $this->session->get(self::SESSION_KEY);
        if (!$id) { $this->loaded = true; return null; }
        $this->admin = $this->db->one('SELECT * FROM super_admins WHERE id = :i', ['i' => $id]);
        $this->loaded = true;
        return $this->admin;
    }

    public function id(): ?int
    {
        $a = $this->admin();
        return $a ? (int)$a['id'] : null;
    }

    public function role(): ?string
    {
        $a = $this->admin();
        return $a['role'] ?? null;
    }

    public function isOwner(): bool
    {
        return $this->role() === 'owner';
    }

    /**
     * Authorization based on role.
     * owner: all
     * admin: all except managing other super admins (except viewing)
     * support: tenants, users (read), support tickets
     * billing: plans, subscriptions, invoices, payments
     */
    public function can(string $action): bool
    {
        $role = $this->role();
        if (!$role) return false;
        if ($role === 'owner') return true;

        $matrix = [
            'admin' => [
                'tenants.*' => true,
                'plans.*' => true,
                'subscriptions.*' => true,
                'invoices.*' => true,
                'payments.*' => true,
                'users.*' => true,
                'reports.*' => true,
                'settings.*' => true,
                'support.*' => true,
                'super_admins.view' => true,
                'developers.*' => true,
                'dev_plans.*' => true,
                'dev_subscriptions.*' => true,
                'dev_apps.*' => true,
                'dev_invoices.*' => true,
                'dev_payments.*' => true,
            ],
            'support' => [
                'tenants.view' => true, 'tenants.edit' => true,
                'users.view' => true, 'users.edit' => true,
                'support.*' => true,
                'reports.view' => true,
                'developers.view' => true,
                'dev_apps.view' => true,
            ],
            'billing' => [
                'tenants.view' => true,
                'plans.*' => true,
                'subscriptions.*' => true,
                'invoices.*' => true,
                'payments.*' => true,
                'reports.view' => true,
                'developers.view' => true,
                'dev_plans.*' => true,
                'dev_subscriptions.*' => true,
                'dev_invoices.*' => true,
                'dev_payments.*' => true,
            ],
        ];
        $perms = $matrix[$role] ?? [];
        if (isset($perms[$action]) && $perms[$action]) return true;
        // check wildcard
        $module = explode('.', $action)[0] ?? '';
        if ($module && isset($perms[$module . '.*']) && $perms[$module . '.*']) return true;
        return false;
    }

    public function log(string $action, ?string $entity = null, ?int $entityId = null, $meta = null): void
    {
        try {
            $this->db->insert('super_audit_logs', [
                'super_admin_id' => $this->id(),
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
