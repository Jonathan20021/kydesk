<?php
namespace App\Controllers\Admin;

class UserController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('users.view');
        $q = trim((string)$this->input('q', ''));
        $tenantId = (int)$this->input('tenant_id', 0);
        $role = (string)$this->input('role', '');
        $where = ['1=1']; $params = [];
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $like = "%$q%"; $params[] = $like; $params[] = $like;
        }
        if ($tenantId) { $where[] = 'u.tenant_id = ?'; $params[] = $tenantId; }
        if ($role !== '') { $where[] = 'r.slug = ?'; $params[] = $role; }

        $users = $this->db->all(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug, t.name AS tenant_name, t.slug AS tenant_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             JOIN tenants t ON t.id = u.tenant_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY u.created_at DESC LIMIT 300",
            $params
        );

        $tenants = $this->db->all('SELECT id, name FROM tenants ORDER BY name ASC');

        $stats = [
            'total' => (int)$this->db->val('SELECT COUNT(*) FROM users'),
            'active' => (int)$this->db->val('SELECT COUNT(*) FROM users WHERE is_active = 1'),
            'technicians' => (int)$this->db->val('SELECT COUNT(*) FROM users WHERE is_technician = 1'),
        ];

        $this->render('admin/users/index', [
            'title' => 'Usuarios globales',
            'pageHeading' => 'Usuarios (todas las empresas)',
            'users' => $users,
            'tenants' => $tenants,
            'q' => $q, 'tenantId' => $tenantId, 'role' => $role,
            'stats' => $stats,
        ]);
    }

    public function create(): void
    {
        $this->requireCan('users.create');
        $tenantId = (int)$this->input('tenant_id', 0);
        $tenants = $this->db->all('SELECT id, name FROM tenants ORDER BY name ASC');
        $roles = $tenantId ? $this->db->all('SELECT * FROM roles WHERE tenant_id = ? ORDER BY id ASC', [$tenantId]) : [];
        $this->render('admin/users/create', [
            'title' => 'Nuevo usuario',
            'pageHeading' => 'Crear usuario en empresa',
            'tenants' => $tenants,
            'roles' => $roles,
            'tenantId' => $tenantId,
        ]);
    }

    public function store(): void
    {
        $this->requireCan('users.create');
        $this->validateCsrf();
        $tenantId = (int)$this->input('tenant_id', 0);
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$tenantId]);
        if (!$tenant) {
            $this->session->flash('error', 'Empresa inválida.');
            $this->redirect('/admin/users/create');
        }
        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        if (!$name || !$email || !$password) {
            $this->session->flash('error', 'Nombre, email y contraseña son obligatorios.');
            $this->redirect('/admin/users/create?tenant_id=' . $tenantId);
        }
        if ($this->db->val('SELECT id FROM users WHERE email = ?', [$email])) {
            $this->session->flash('error', 'El email ya existe.');
            $this->redirect('/admin/users/create?tenant_id=' . $tenantId);
        }
        $id = $this->db->insert('users', [
            'tenant_id' => $tenantId,
            'role_id' => ((int)$this->input('role_id', 0)) ?: null,
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'phone' => (string)$this->input('phone', ''),
            'title' => (string)$this->input('title', ''),
            'is_technician' => (int)($this->input('is_technician') ? 1 : 0),
            'is_active' => 1,
        ]);
        $this->superAuth->log('user.create', 'user', $id, ['tenant_id' => $tenantId]);
        $this->session->flash('success', 'Usuario creado en ' . $tenant['name'] . '.');
        $this->redirect('/admin/users');
    }

    public function edit(array $params): void
    {
        $this->requireCan('users.edit');
        $id = (int)$params['id'];
        $u = $this->db->one(
            'SELECT u.*, t.name AS tenant_name FROM users u JOIN tenants t ON t.id = u.tenant_id WHERE u.id = ?',
            [$id]
        );
        if (!$u) $this->redirect('/admin/users');
        $roles = $this->db->all('SELECT * FROM roles WHERE tenant_id = ? ORDER BY id ASC', [$u['tenant_id']]);
        $this->render('admin/users/edit', [
            'title' => $u['name'],
            'pageHeading' => 'Editar usuario',
            'u' => $u,
            'roles' => $roles,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCan('users.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $u = $this->db->one('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$u) $this->redirect('/admin/users');

        $data = [
            'name' => trim((string)$this->input('name', $u['name'])),
            'phone' => (string)$this->input('phone', ''),
            'title' => (string)$this->input('title', ''),
            'role_id' => ((int)$this->input('role_id', 0)) ?: null,
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'is_technician' => (int)($this->input('is_technician') ? 1 : 0),
        ];
        $email = trim((string)$this->input('email', ''));
        if ($email && $email !== $u['email']) {
            if ($this->db->val('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $id])) {
                $this->session->flash('error', 'El email ya existe.');
                $this->redirect('/admin/users/' . $id);
            }
            $data['email'] = $email;
        }
        $password = (string)$this->input('password', '');
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('users', $data, 'id = :id', ['id' => $id]);
        $this->superAuth->log('user.update', 'user', $id);
        $this->session->flash('success', 'Usuario actualizado.');
        $this->redirect('/admin/users');
    }

    public function delete(array $params): void
    {
        $this->requireCan('users.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('users', 'id = :id', ['id' => $id]);
        $this->superAuth->log('user.delete', 'user', $id);
        $this->session->flash('success', 'Usuario eliminado.');
        $this->back();
    }
}
