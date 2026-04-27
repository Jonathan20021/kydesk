<?php
namespace App\Controllers\Admin;

use App\Core\Mailer;

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

    public function showForgot(): void
    {
        if ($this->superAuth->check()) $this->redirect('/admin/dashboard');
        $this->render('admin/forgot', ['title' => 'Recuperar contraseña'], 'admin_auth');
    }

    public function forgot(): void
    {
        $this->validateCsrf();
        $email = trim((string)$this->input('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email inválido.');
            $this->redirect('/admin/forgot');
        }

        // Buscar el super admin (no revelar si existe o no por seguridad)
        $admin = $this->db->one('SELECT * FROM super_admins WHERE email = ? AND is_active = 1 LIMIT 1', [$email]);
        if ($admin) {
            $token = bin2hex(random_bytes(24));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $this->db->update('super_admins', [
                'reset_token' => $token,
                'reset_expires_at' => $expires,
                'reset_requested_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ], 'id = :id', ['id' => (int)$admin['id']]);

            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $resetUrl = $appUrl . '/admin/reset/' . $token;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
            $inner = '<p>Hola <strong>' . htmlspecialchars($admin['name']) . '</strong>,</p>'
                . '<p>Recibimos un pedido para restablecer la contraseña del panel super admin de Kydesk.</p>'
                . '<p>El enlace expira en <strong>1 hora</strong>. Si no fuiste vos, ignorá este email — tu contraseña no cambia.</p>'
                . '<div style="margin:18px 0;padding:14px 16px;background:#fafafb;border-left:3px solid #7c5cff;border-radius:8px;font-size:12.5px;color:#6b6b78">'
                . '<strong>IP:</strong> ' . htmlspecialchars($ip) . '<br>'
                . '<strong>User-Agent:</strong> ' . htmlspecialchars($ua)
                . '</div>';
            try {
                (new Mailer())->send(
                    ['email' => $admin['email'], 'name' => $admin['name']],
                    'Kydesk · Restablecer contraseña super admin',
                    Mailer::template('Restablecer contraseña', $inner, 'Establecer nueva contraseña', $resetUrl)
                );
            } catch (\Throwable $e) { /* swallow */ }
            try {
                $this->db->insert('super_audit_logs', [
                    'super_admin_id' => (int)$admin['id'],
                    'action' => 'password_reset.requested',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ]);
            } catch (\Throwable $e) {}
        }

        // Mensaje genérico para no revelar si el email existe
        $this->session->flash('success', 'Si el email existe en el sistema, recibirás un enlace para restablecer la contraseña en menos de 1 minuto.');
        $this->redirect('/admin/login');
    }

    public function showReset(array $params): void
    {
        if ($this->superAuth->check()) $this->redirect('/admin/dashboard');
        $token = (string)$params['token'];
        $admin = $this->db->one('SELECT id, email, name FROM super_admins WHERE reset_token = ? AND reset_expires_at > NOW() AND is_active = 1 LIMIT 1', [$token]);
        if (!$admin) {
            $this->session->flash('error', 'El enlace expiró o no es válido. Solicitá uno nuevo.');
            $this->redirect('/admin/forgot');
        }
        $this->render('admin/reset', [
            'title' => 'Nueva contraseña',
            'token' => $token,
            'adminName' => $admin['name'],
            'adminEmail' => $admin['email'],
        ], 'admin_auth');
    }

    public function reset(array $params): void
    {
        if ($this->superAuth->check()) $this->redirect('/admin/dashboard');
        $this->validateCsrf();
        $token = (string)$params['token'];
        $admin = $this->db->one('SELECT * FROM super_admins WHERE reset_token = ? AND reset_expires_at > NOW() AND is_active = 1 LIMIT 1', [$token]);
        if (!$admin) {
            $this->session->flash('error', 'El enlace expiró o no es válido.');
            $this->redirect('/admin/forgot');
        }
        $password = (string)$this->input('password', '');
        $confirm = (string)$this->input('password_confirm', '');
        if (strlen($password) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/admin/reset/' . $token);
        }
        if ($password !== $confirm) {
            $this->session->flash('error', 'Las contraseñas no coinciden.');
            $this->redirect('/admin/reset/' . $token);
        }
        $this->db->update('super_admins', [
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'reset_token' => null,
            'reset_expires_at' => null,
            'reset_requested_ip' => null,
        ], 'id = :id', ['id' => (int)$admin['id']]);
        try {
            $this->db->insert('super_audit_logs', [
                'super_admin_id' => (int)$admin['id'],
                'action' => 'password_reset.completed',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {}

        // Email de confirmación de cambio
        try {
            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $loginUrl = $appUrl . '/admin/login';
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
            $inner = '<p>Hola <strong>' . htmlspecialchars($admin['name']) . '</strong>,</p>'
                . '<p>Tu contraseña del panel super admin fue cambiada exitosamente.</p>'
                . '<p style="font-size:12px;color:#6b6b78">IP del cambio: ' . htmlspecialchars($ip) . ' · ' . date('Y-m-d H:i:s') . '</p>'
                . '<p>Si <strong>no fuiste vos</strong>, contactá al equipo de seguridad inmediatamente.</p>';
            (new Mailer())->send(
                ['email' => $admin['email'], 'name' => $admin['name']],
                'Kydesk · Contraseña super admin actualizada',
                Mailer::template('Contraseña actualizada', $inner, 'Iniciar sesión', $loginUrl)
            );
        } catch (\Throwable $e) {}

        $this->session->flash('success', 'Contraseña actualizada. Iniciá sesión con la nueva.');
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
