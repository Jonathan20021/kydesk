<?php
namespace App\Core;

class Auth
{
    protected ?array $user = null;
    protected array $permissions = [];
    protected bool $loaded = false;

    public function __construct(protected Database $db, protected Session $session) {}

    public function attempt(string $email, string $password): ?array
    {
        $row = $this->db->one('SELECT * FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
        if (!$row) return null;
        if (!password_verify($password, $row['password'])) return null;
        if ((int)$row['is_active'] !== 1) return null;

        $this->session->regenerate();
        $this->session->put('uid', (int)$row['id']);
        $this->session->put('tid', (int)$row['tenant_id']);
        $this->db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $row['id']]);
        $this->loaded = false;
        return $row;
    }

    public function login(int $userId): void
    {
        $row = $this->db->one('SELECT * FROM users WHERE id = :i', ['i' => $userId]);
        if (!$row) return;
        $this->session->regenerate();
        $this->session->put('uid', (int)$row['id']);
        $this->session->put('tid', (int)$row['tenant_id']);
        $this->loaded = false;
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->session->start();
    }

    public function check(): bool { return $this->session->get('uid') !== null; }

    public function user(): ?array
    {
        if ($this->loaded) return $this->user;
        $id = $this->session->get('uid');
        if (!$id) { $this->loaded = true; return null; }
        $this->user = $this->db->one(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :i', ['i' => $id]
        );
        if ($this->user) {
            $rows = $this->db->all(
                'SELECT p.slug FROM role_permissions rp
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE rp.role_id = :r', ['r' => $this->user['role_id']]
            );
            $this->permissions = array_column($rows, 'slug');
        }
        $this->loaded = true;
        return $this->user;
    }

    public function userId(): ?int
    {
        $u = $this->user();
        return $u ? (int)$u['id'] : null;
    }

    public function tenantId(): ?int
    {
        $u = $this->user();
        return $u ? (int)$u['tenant_id'] : null;
    }

    public function can(string $permission): bool
    {
        $u = $this->user();
        if (!$u) return false;
        if (($u['role_slug'] ?? '') === 'owner') return true;
        return in_array($permission, $this->permissions, true);
    }

    public function permissions(): array
    {
        $this->user();
        return $this->permissions;
    }
}
