<?php
namespace App\Controllers;

use App\Core\Controller;

class UserController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('users.view');
        $users = $this->db->all(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                    (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to = u.id) AS tickets_count
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.tenant_id = ? ORDER BY u.created_at DESC",
            [$tenant->id]
        );
        $this->render('users/index', ['title' => 'Usuarios', 'users' => $users]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('users.create');
        $roles = $this->db->all('SELECT * FROM roles WHERE tenant_id=? ORDER BY id', [$tenant->id]);
        $this->render('users/create', ['title' => 'Nuevo usuario', 'roles' => $roles]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('users.create');
        $this->validateCsrf();

        $name = trim((string)$this->input('name'));
        $email = trim((string)$this->input('email'));
        $password = (string)$this->input('password');
        if (!$name || !$email || !$password) {
            $this->session->flash('error','Todos los campos son obligatorios.');
            $this->redirect('/t/' . $tenant->slug . '/users/create');
        }
        if ($this->db->val('SELECT id FROM users WHERE email=?', [$email])) {
            $this->session->flash('error','Email ya registrado.');
            $this->redirect('/t/' . $tenant->slug . '/users/create');
        }
        $current = (int)$this->db->val('SELECT COUNT(*) FROM users WHERE tenant_id=?', [$tenant->id]);
        $this->enforceLimit('users', $current, 'usuarios', '/t/' . $tenant->slug . '/users');
        $this->db->insert('users', [
            'tenant_id' => $tenant->id,
            'role_id' => ((int)$this->input('role_id',0)) ?: null,
            'name' => $name, 'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'title' => (string)$this->input('title',''),
            'phone' => (string)$this->input('phone',''),
            'is_technician' => (int)($this->input('is_technician',0) ? 1 : 0),
            'is_active' => (int)($this->input('is_active',1) ? 1 : 0),
        ]);
        $this->session->flash('success','Usuario creado.');
        $this->redirect('/t/' . $tenant->slug . '/users');
    }

    public function edit(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('users.edit');
        $id = (int)$params['id'];
        $user = $this->db->one('SELECT * FROM users WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$user) $this->redirect('/t/' . $tenant->slug . '/users');
        $roles = $this->db->all('SELECT * FROM roles WHERE tenant_id=? ORDER BY id', [$tenant->id]);
        $this->render('users/edit', ['title' => 'Editar usuario', 'u' => $user, 'roles' => $roles]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('users.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $data = [
            'name' => trim((string)$this->input('name')),
            'title' => (string)$this->input('title',''),
            'phone' => (string)$this->input('phone',''),
            'role_id' => ((int)$this->input('role_id',0)) ?: null,
            'is_technician' => (int)($this->input('is_technician',0) ? 1 : 0),
            'is_active' => (int)($this->input('is_active',1) ? 1 : 0),
        ];
        $password = (string)$this->input('password','');
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('users', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Usuario actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/users');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('users.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        if ($id === $this->auth->userId()) {
            $this->session->flash('error','No puedes eliminarte a ti mismo.');
            $this->redirect('/t/' . $tenant->slug . '/users');
        }
        $this->db->delete('users', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Usuario eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/users');
    }
}
