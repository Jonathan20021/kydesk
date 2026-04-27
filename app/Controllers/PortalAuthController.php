<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Tenant;

class PortalAuthController extends Controller
{
    public function showLogin(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); echo 'Portal no encontrado.'; return; }

        $this->render('portal_auth/login', [
            'title' => 'Iniciar sesión · ' . $tenant->name,
            'tenantPublic' => $tenant,
        ], 'public');
    }

    public function login(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); echo 'Portal no encontrado.'; return; }
        $this->validateCsrf();

        $email = trim((string)$this->input('email',''));
        $password = (string)$this->input('password','');
        $user = $this->db->one('SELECT * FROM portal_users WHERE tenant_id=? AND email=? AND is_active=1', [$tenant->id, $email]);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->session->flash('error','Credenciales inválidas.');
            $this->redirect('/portal/' . $tenant->slug . '/login');
        }
        $this->db->update('portal_users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$user['id']]);
        $this->session->put('portal_user_id_' . $tenant->id, (int)$user['id']);
        $this->session->regenerate();
        $this->redirect('/portal/' . $tenant->slug . '/account');
    }

    public function showRegister(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id = ? ORDER BY name', [$tenant->id]);
        $this->render('portal_auth/register', [
            'title' => 'Crear cuenta · ' . $tenant->name,
            'tenantPublic' => $tenant,
            'companies' => $companies,
        ], 'public');
    }

    public function register(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $this->validateCsrf();

        $name = trim((string)$this->input('name',''));
        $email = trim((string)$this->input('email',''));
        $password = (string)$this->input('password','');
        $companyId = (int)$this->input('company_id', 0) ?: null;

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $this->session->flash('error','Completa todos los campos. La contraseña debe tener al menos 6 caracteres.');
            $this->redirect('/portal/' . $tenant->slug . '/register');
        }
        if ($this->db->val('SELECT id FROM portal_users WHERE tenant_id=? AND email=?', [$tenant->id, $email])) {
            $this->session->flash('error','Ya existe una cuenta con ese email en este portal.');
            $this->redirect('/portal/' . $tenant->slug . '/register');
        }

        $verifyToken = bin2hex(random_bytes(16));
        $id = $this->db->insert('portal_users', [
            'tenant_id'    => $tenant->id,
            'company_id'   => $companyId,
            'name'         => $name,
            'email'        => $email,
            'password'     => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'phone'        => (string)$this->input('phone','') ?: null,
            'is_active'    => 1,
            'verify_token' => $verifyToken,
        ]);

        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
        $verifyUrl = $appUrl . '/portal/' . $tenant->slug . '/verify/' . $verifyToken;
        try {
            (new Mailer())->send(['email' => $email, 'name' => $name],
                'Confirma tu cuenta · ' . $tenant->name,
                Mailer::template('Bienvenido a ' . $tenant->name,
                    '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p><p>Tu cuenta del portal fue creada. Confirmá tu email haciendo click abajo.</p>',
                    'Confirmar email', $verifyUrl)
            );
        } catch (\Throwable $e) {}

        $this->session->put('portal_user_id_' . $tenant->id, (int)$id);
        $this->session->regenerate();
        $this->session->flash('success','Cuenta creada. Te enviamos un email para verificar.');
        $this->redirect('/portal/' . $tenant->slug . '/account');
    }

    public function verify(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $token = (string)$params['token'];
        $user = $this->db->one('SELECT * FROM portal_users WHERE tenant_id=? AND verify_token=?', [$tenant->id, $token]);
        if (!$user) { http_response_code(404); echo 'Token inválido.'; return; }
        $this->db->update('portal_users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verify_token' => null,
        ], 'id = :id', ['id' => (int)$user['id']]);
        $this->session->flash('success', 'Email verificado. ¡Bienvenido!');
        $this->redirect('/portal/' . $tenant->slug . '/login');
    }

    public function logout(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { $this->redirect('/'); return; }
        $this->session->forget('portal_user_id_' . $tenant->id);
        $this->session->regenerate();
        $this->redirect('/portal/' . $tenant->slug . '/login');
    }

    public function showForgot(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $this->render('portal_auth/forgot', [
            'title' => 'Recuperar contraseña · ' . $tenant->name,
            'tenantPublic' => $tenant,
        ], 'public');
    }

    public function forgot(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $this->validateCsrf();
        $email = trim((string)$this->input('email',''));
        $user = $this->db->one('SELECT * FROM portal_users WHERE tenant_id=? AND email=?', [$tenant->id, $email]);
        if ($user) {
            $token = bin2hex(random_bytes(16));
            $this->db->update('portal_users', [
                'reset_token' => $token,
                'reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ], 'id = :id', ['id' => (int)$user['id']]);
            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $resetUrl = $appUrl . '/portal/' . $tenant->slug . '/reset/' . $token;
            try {
                (new Mailer())->send(['email' => $email, 'name' => $user['name']],
                    'Restablecer contraseña · ' . $tenant->name,
                    Mailer::template('Restablecer contraseña',
                        '<p>Hicimos un pedido para resetear tu contraseña. El enlace expira en 1 hora.</p>',
                        'Restablecer ahora', $resetUrl)
                );
            } catch (\Throwable $e) {}
        }
        $this->session->flash('success','Si tu email existe, recibirás un link para restablecer la contraseña.');
        $this->redirect('/portal/' . $tenant->slug . '/login');
    }

    public function showReset(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $token = (string)$params['token'];
        $user = $this->db->one('SELECT * FROM portal_users WHERE tenant_id=? AND reset_token=? AND reset_expires_at > NOW()', [$tenant->id, $token]);
        if (!$user) { http_response_code(404); echo 'Link expirado o inválido.'; return; }
        $this->render('portal_auth/reset', [
            'title' => 'Nueva contraseña · ' . $tenant->name,
            'tenantPublic' => $tenant,
            'token' => $token,
        ], 'public');
    }

    public function reset(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $this->validateCsrf();
        $token = (string)$params['token'];
        $user = $this->db->one('SELECT * FROM portal_users WHERE tenant_id=? AND reset_token=? AND reset_expires_at > NOW()', [$tenant->id, $token]);
        if (!$user) { $this->session->flash('error','Link expirado o inválido.'); $this->redirect('/portal/' . $tenant->slug . '/login'); }

        $password = (string)$this->input('password','');
        if (strlen($password) < 6) {
            $this->session->flash('error','Mínimo 6 caracteres.');
            $this->redirect('/portal/' . $tenant->slug . '/reset/' . $token);
        }
        $this->db->update('portal_users', [
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]),
            'reset_token' => null,
            'reset_expires_at' => null,
        ], 'id = :id', ['id' => (int)$user['id']]);
        $this->session->flash('success','Contraseña actualizada. Ingresá con tu nueva contraseña.');
        $this->redirect('/portal/' . $tenant->slug . '/login');
    }

    /** Dashboard del cliente: ver sus tickets. */
    public function account(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $userId = (int)$this->session->get('portal_user_id_' . $tenant->id, 0);
        if (!$userId) $this->redirect('/portal/' . $tenant->slug . '/login');

        $user = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=?', [$userId, $tenant->id]);
        if (!$user) {
            $this->session->forget('portal_user_id_' . $tenant->id);
            $this->redirect('/portal/' . $tenant->slug . '/login');
        }

        $tickets = $this->db->all(
            "SELECT id, code, subject, status, priority, created_at, resolved_at FROM tickets
             WHERE tenant_id = ? AND (portal_user_id = ? OR requester_email = ?) ORDER BY created_at DESC LIMIT 100",
            [$tenant->id, $userId, $user['email']]
        );

        $stats = [
            'open' => count(array_filter($tickets, fn($t) => in_array($t['status'], ['open','in_progress','on_hold']))),
            'resolved' => count(array_filter($tickets, fn($t) => in_array($t['status'], ['resolved','closed']))),
            'total' => count($tickets),
        ];

        $this->render('portal_auth/account', [
            'title' => 'Mi cuenta · ' . $tenant->name,
            'tenantPublic' => $tenant,
            'portalUser' => $user,
            'tickets' => $tickets,
            'stats' => $stats,
        ], 'public');
    }

    public function updateProfile(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        if (!$tenant) { http_response_code(404); return; }
        $this->validateCsrf();
        $userId = (int)$this->session->get('portal_user_id_' . $tenant->id, 0);
        if (!$userId) $this->redirect('/portal/' . $tenant->slug . '/login');
        $this->db->update('portal_users', [
            'name' => trim((string)$this->input('name','')),
            'phone' => (string)$this->input('phone','') ?: null,
        ], 'id=? AND tenant_id=?', [$userId, $tenant->id]);
        $newPwd = (string)$this->input('new_password','');
        if (strlen($newPwd) >= 6) {
            $this->db->update('portal_users', ['password' => password_hash($newPwd, PASSWORD_BCRYPT, ['cost'=>12])], 'id=?', [$userId]);
        }
        $this->session->flash('success','Perfil actualizado.');
        $this->redirect('/portal/' . $tenant->slug . '/account');
    }

    /** Listado en super admin: usuarios del portal por tenant. */
    public function manageList(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('customer_portal');
        $this->requireCan('portal.manage');

        $users = $this->db->all(
            'SELECT pu.*, c.name AS company_name FROM portal_users pu LEFT JOIN companies c ON c.id = pu.company_id WHERE pu.tenant_id = ? ORDER BY pu.created_at DESC',
            [$tenant->id]
        );
        $this->render('portal_auth/manage', [
            'title' => 'Usuarios del Portal',
            'users' => $users,
        ]);
    }

    public function manageToggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('portal.manage');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $u = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if ($u) {
            $this->db->update('portal_users', ['is_active' => $u['is_active'] ? 0 : 1], 'id=?', [$id]);
        }
        $this->redirect('/t/' . $tenant->slug . '/portal-users');
    }

    public function manageDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('portal.manage');
        $this->validateCsrf();
        $this->db->delete('portal_users', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/portal-users');
    }

    protected function resolveTenant(string $slug): ?Tenant
    {
        return Tenant::resolve($slug);
    }
}
