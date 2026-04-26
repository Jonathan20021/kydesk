<?php
namespace App\Controllers\Api;

class UsersController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $where = ['tenant_id = ?']; $args = [$this->tid()];
        if ($q = $_GET['q'] ?? null) { $where[] = '(name LIKE ? OR email LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
        if (isset($_GET['is_technician'])) { $where[] = 'is_technician = ?'; $args[] = (int)$_GET['is_technician']; }
        if (isset($_GET['is_active'])) { $where[] = 'is_active = ?'; $args[] = (int)$_GET['is_active']; }
        $whereSql = implode(' AND ', $where);
        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $sort = $this->sortClause(['id','name','email','created_at'], 'name', 'ASC');
        $rows = $this->db->all("SELECT id, name, email, title, avatar, phone, is_technician, is_active, role_id, last_login_at, created_at FROM users WHERE $whereSql ORDER BY $sort LIMIT $limit OFFSET $offset", $args);
        $total = (int)$this->db->val("SELECT COUNT(*) FROM users WHERE $whereSql", $args);
        $this->json($rows, 200, ['total' => $total, 'limit' => $limit, 'offset' => $offset]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT id, name, email, title, avatar, phone, is_technician, is_active, role_id, last_login_at, created_at FROM users WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Usuario no encontrado', 404, 'not_found');
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['name','email','password']);
        if ($this->db->one('SELECT id FROM users WHERE email=?', [$b['email']])) {
            $this->error('Ya existe un usuario con ese email', 422, 'validation_error', ['field' => 'email']);
        }
        $id = $this->db->insert('users', [
            'tenant_id' => $this->tid(),
            'name' => trim((string)$b['name']),
            'email' => strtolower(trim((string)$b['email'])),
            'password' => password_hash((string)$b['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'phone' => (string)$this->in($b, 'phone', ''),
            'title' => (string)$this->in($b, 'title', ''),
            'is_active' => (int)($this->in($b, 'is_active', 1) ? 1 : 0),
            'is_technician' => (int)($this->in($b, 'is_technician', 0) ? 1 : 0),
            'role_id' => ($r = (int)$this->in($b, 'role_id', 0)) ?: null,
        ]);
        $this->created($this->db->one('SELECT id, name, email, title, is_technician, is_active, role_id, created_at FROM users WHERE id=?', [$id]));
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM users WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Usuario no encontrado', 404, 'not_found');
        $b = $this->body();
        $upd = array_intersect_key($b, array_flip(['name','phone','title','is_active','is_technician','role_id','avatar']));
        if (isset($b['password']) && $b['password']) $upd['password'] = password_hash((string)$b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        if ($upd) $this->db->update('users', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($this->db->one('SELECT id, name, email, title, is_technician, is_active, role_id, created_at FROM users WHERE id=?', [$id]));
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('users', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }
}
