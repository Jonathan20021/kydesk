<?php
namespace App\Controllers\Admin;

class SuperAdminController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('super_admins.view');
        $admins = $this->db->all('SELECT * FROM super_admins ORDER BY id ASC');
        $this->render('admin/super_admins/index', [
            'title' => 'Super Admins',
            'pageHeading' => 'Super Administradores',
            'admins' => $admins,
        ]);
    }

    public function create(): void
    {
        $this->requireCan('super_admins.create');
        $this->render('admin/super_admins/create', [
            'title' => 'Nuevo Super Admin',
            'pageHeading' => 'Crear Super Administrador',
        ]);
    }

    public function store(): void
    {
        $this->requireCan('super_admins.create');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        $role = (string)$this->input('role', 'admin');

        if (!$name || !$email || !$password) {
            $this->session->flash('error', 'Todos los campos son obligatorios.');
            $this->redirect('/admin/super-admins/create');
        }
        if (strlen($password) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/admin/super-admins/create');
        }
        if (!in_array($role, ['owner','admin','support','billing'], true)) $role = 'admin';
        if ($this->db->val('SELECT id FROM super_admins WHERE email = ?', [$email])) {
            $this->session->flash('error', 'Ya existe un super admin con ese email.');
            $this->redirect('/admin/super-admins/create');
        }

        $id = $this->db->insert('super_admins', [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => $role,
            'phone' => (string)$this->input('phone', ''),
            'notes' => (string)$this->input('notes', ''),
            'is_active' => 1,
        ]);
        $this->superAuth->log('super_admin.create', 'super_admin', $id, ['email' => $email, 'role' => $role]);
        $this->session->flash('success', 'Super administrador creado.');
        $this->redirect('/admin/super-admins');
    }

    public function edit(array $params): void
    {
        $this->requireCan('super_admins.edit');
        $id = (int)$params['id'];
        $a = $this->db->one('SELECT * FROM super_admins WHERE id = ?', [$id]);
        if (!$a) $this->redirect('/admin/super-admins');
        $this->render('admin/super_admins/edit', [
            'title' => $a['name'],
            'pageHeading' => 'Editar Super Admin',
            'a' => $a,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCan('super_admins.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $a = $this->db->one('SELECT * FROM super_admins WHERE id = ?', [$id]);
        if (!$a) $this->redirect('/admin/super-admins');

        $data = [
            'name' => trim((string)$this->input('name', $a['name'])),
            'phone' => (string)$this->input('phone', ''),
            'notes' => (string)$this->input('notes', ''),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
        ];
        $role = (string)$this->input('role', $a['role']);
        // Solo owner puede cambiar role o crear otro owner
        if ($this->superAuth->isOwner() && in_array($role, ['owner','admin','support','billing'], true)) {
            $data['role'] = $role;
        }
        $email = trim((string)$this->input('email', ''));
        if ($email && $email !== $a['email']) {
            if ($this->db->val('SELECT id FROM super_admins WHERE email = ? AND id != ?', [$email, $id])) {
                $this->session->flash('error', 'Email ya existe.');
                $this->redirect('/admin/super-admins/' . $id);
            }
            $data['email'] = $email;
        }
        $password = (string)$this->input('password', '');
        if ($password !== '' && strlen($password) >= 8) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('super_admins', $data, 'id = :id', ['id' => $id]);
        $this->superAuth->log('super_admin.update', 'super_admin', $id);
        $this->session->flash('success', 'Super admin actualizado.');
        $this->redirect('/admin/super-admins');
    }

    public function delete(array $params): void
    {
        $this->requireCan('super_admins.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        if ($id === $this->superAuth->id()) {
            $this->session->flash('error', 'No puedes eliminarte a ti mismo.');
            $this->redirect('/admin/super-admins');
        }
        $a = $this->db->one('SELECT role FROM super_admins WHERE id = ?', [$id]);
        if ($a && $a['role'] === 'owner' && !$this->superAuth->isOwner()) {
            $this->session->flash('error', 'Solo un owner puede eliminar otro owner.');
            $this->redirect('/admin/super-admins');
        }
        $this->db->delete('super_admins', 'id = :id', ['id' => $id]);
        $this->superAuth->log('super_admin.delete', 'super_admin', $id);
        $this->session->flash('success', 'Super admin eliminado.');
        $this->redirect('/admin/super-admins');
    }
}
