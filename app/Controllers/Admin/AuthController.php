<?php
namespace App\Controllers\Admin;

class AuthController extends AdminController
{
    public function showLogin(): void
    {
        if ($this->superAuth->check()) {
            $this->redirect('/admin/dashboard');
        }
        $this->render('admin/login', ['title' => 'Super Admin'], 'admin_auth');
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        if (!$email || !$password) {
            $this->session->flash('error', 'Email y contraseña son requeridos.');
            $this->redirect('/admin/login');
        }
        $admin = $this->superAuth->attempt($email, $password);
        if (!$admin) {
            $this->session->flash('error', 'Credenciales inválidas o cuenta inactiva.');
            $this->redirect('/admin/login');
        }
        $this->superAuth->log('login');
        $this->session->flash('success', 'Bienvenido, ' . $admin['name'] . '.');
        $this->redirect('/admin/dashboard');
    }

    public function logout(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') $this->validateCsrf();
        $this->superAuth->log('logout');
        $this->superAuth->logout();
        $this->redirect('/admin/login');
    }

    public function profile(): void
    {
        $this->requireSuperAuth();
        $this->render('admin/profile', ['title' => 'Mi perfil', 'pageHeading' => 'Perfil de Super Admin']);
    }

    public function updateProfile(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $admin = $this->superAuth->admin();
        $data = [
            'name' => trim((string)$this->input('name', $admin['name'])),
            'phone' => (string)$this->input('phone', ''),
        ];
        $email = trim((string)$this->input('email', ''));
        if ($email && $email !== $admin['email']) {
            $exists = $this->db->val('SELECT id FROM super_admins WHERE email = ? AND id != ?', [$email, $admin['id']]);
            if ($exists) {
                $this->session->flash('error', 'Email ya está en uso.');
                $this->redirect('/admin/profile');
            }
            $data['email'] = $email;
        }
        $newPassword = (string)$this->input('new_password', '');
        if ($newPassword !== '') {
            $current = (string)$this->input('current_password', '');
            if (!password_verify($current, $admin['password'])) {
                $this->session->flash('error', 'Contraseña actual incorrecta.');
                $this->redirect('/admin/profile');
            }
            if (strlen($newPassword) < 8) {
                $this->session->flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
                $this->redirect('/admin/profile');
            }
            $data['password'] = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('super_admins', $data, 'id = :id', ['id' => $admin['id']]);
        $this->superAuth->log('profile.update');
        $this->session->flash('success', 'Perfil actualizado.');
        $this->redirect('/admin/profile');
    }
}
