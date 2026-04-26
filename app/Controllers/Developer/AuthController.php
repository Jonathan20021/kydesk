<?php
namespace App\Controllers\Developer;

use App\Core\Csrf;

class AuthController extends DeveloperController
{
    public function showLogin(): void
    {
        if ($this->devAuth->check()) $this->redirect('/developers/dashboard');
        echo $this->view->render('developers/auth/login', [
            'title' => 'Iniciar sesión · Developers',
        ], 'developers_auth');
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        $row = $this->devAuth->attempt($email, $password);
        if (!$row) {
            $this->session->flash('error', 'Credenciales inválidas o cuenta inactiva.');
            $this->redirect('/developers/login');
        }
        $this->devAuth->log('developer.login');
        $this->redirect('/developers/dashboard');
    }

    public function showRegister(): void
    {
        if ($this->devAuth->check()) $this->redirect('/developers/dashboard');
        $allow = $this->setting('dev_portal_allow_registration', '1');
        if ($allow !== '1') {
            $this->session->flash('error', 'El registro de developers está deshabilitado.');
            $this->redirect('/developers/login');
        }
        $plans = [];
        try {
            $plans = $this->db->all("SELECT * FROM dev_plans WHERE is_active=1 AND is_public=1 ORDER BY sort_order ASC, price_monthly ASC");
        } catch (\Throwable $_e) {}
        echo $this->view->render('developers/auth/register', [
            'title' => 'Crear cuenta · Developers',
            'plans' => $plans,
        ], 'developers_auth');
    }

    public function register(): void
    {
        $this->validateCsrf();
        $allow = $this->setting('dev_portal_allow_registration', '1');
        if ($allow !== '1') {
            $this->session->flash('error', 'El registro está deshabilitado.');
            $this->redirect('/developers/login');
        }

        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        $company = trim((string)$this->input('company', ''));
        $planSlug = (string)$this->input('plan', $this->setting('dev_portal_default_plan', 'dev_free'));

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $this->session->flash('error', 'Nombre, email y contraseña (mín. 6) son requeridos.');
            $this->redirect('/developers/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email no válido.');
            $this->redirect('/developers/register');
        }
        if ($this->db->one('SELECT id FROM developers WHERE email=?', [$email])) {
            $this->session->flash('error', 'Ya existe una cuenta con ese email.');
            $this->redirect('/developers/register');
        }

        $devId = $this->db->insert('developers', [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'company' => $company ?: null,
            'is_active' => 1,
            'is_verified' => (int)($this->setting('dev_portal_require_verification', '0') === '1' ? 0 : 1),
        ]);

        // Crear suscripción al plan elegido (o al default)
        $plan = $this->db->one('SELECT * FROM dev_plans WHERE slug=? AND is_active=1', [$planSlug]);
        if (!$plan) {
            $plan = $this->db->one("SELECT * FROM dev_plans WHERE slug='dev_free' AND is_active=1");
        }
        if ($plan) {
            $isFree = (float)$plan['price_monthly'] == 0.0;
            $trialDays = (int)$plan['trial_days'];
            $status = $isFree ? 'active' : ($trialDays > 0 ? 'trial' : 'active');
            $now = date('Y-m-d H:i:s');
            $endDate = $isFree ? date('Y-m-d H:i:s', strtotime('+10 years'))
                : ($trialDays > 0 ? date('Y-m-d H:i:s', strtotime("+{$trialDays} days"))
                    : date('Y-m-d H:i:s', strtotime('+1 month')));
            $this->db->insert('dev_subscriptions', [
                'developer_id' => $devId,
                'plan_id' => $plan['id'],
                'status' => $status,
                'billing_cycle' => 'monthly',
                'amount' => $plan['price_monthly'],
                'started_at' => $now,
                'trial_ends_at' => $status === 'trial' ? $endDate : null,
                'current_period_start' => $now,
                'current_period_end' => $endDate,
                'auto_renew' => $isFree ? 0 : 1,
            ]);
        }

        // If verification is required, send email and flag account
        $requireVer = $this->setting('dev_portal_email_verification_required', '0') === '1' || $this->setting('dev_portal_require_verification', '0') === '1';
        if ($requireVer) {
            $this->db->update('developers', ['is_verified' => 0], 'id=?', [$devId]);
            $this->sendVerificationEmail($devId, $email, $name);
        }

        // Welcome email (always sent, regardless of verification requirement)
        $planNameForEmail = $plan ? (string)$plan['name'] : 'Free';
        \App\Core\DevMailer::welcomeRegistered($email, $name, $planNameForEmail);

        $this->devAuth->attempt($email, $password);
        $this->devAuth->log('developer.register');
        $this->session->flash('success', $requireVer
            ? '¡Bienvenido! Te enviamos un email para verificar tu cuenta.'
            : '¡Bienvenido! Tu cuenta de developer está lista.');
        $this->redirect('/developers/dashboard');
    }

    public function logout(): void
    {
        $this->devAuth->log('developer.logout');
        $this->devAuth->logout();
        $this->redirect('/developers/login');
    }

    // ─── Email verification ────────────────────────────────────────

    public function verifyEmail(array $params): void
    {
        $token = (string)$params['token'];
        $row = $this->db->one(
            "SELECT t.*, d.email, d.name FROM dev_email_tokens t
             JOIN developers d ON d.id = t.developer_id
             WHERE t.token = ? AND t.purpose = 'verify_email' AND t.used_at IS NULL AND t.expires_at > NOW() LIMIT 1",
            [hash('sha256', $token)]
        );
        if (!$row) {
            $this->session->flash('error', 'El enlace de verificación es inválido o ha expirado.');
            $this->redirect('/developers/login');
        }
        $this->db->update('developers', ['is_verified' => 1, 'email_verified_at' => date('Y-m-d H:i:s')], 'id=?', [(int)$row['developer_id']]);
        $this->db->update('dev_email_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id=?', [(int)$row['id']]);
        $this->session->flash('success', '✓ Email verificado. Ya puedes usar todos los recursos del portal.');
        $this->redirect('/developers/login');
    }

    public function resendVerification(): void
    {
        $this->validateCsrf();
        if (!$this->devAuth->check()) $this->redirect('/developers/login');
        $d = $this->devAuth->developer();
        if (!empty($d['is_verified'])) {
            $this->session->flash('info', 'Tu email ya está verificado.');
            $this->redirect('/developers/profile');
        }
        $this->sendVerificationEmail((int)$d['id'], (string)$d['email'], (string)$d['name']);
        $this->session->flash('success', 'Email de verificación enviado a ' . $d['email']);
        $this->redirect('/developers/profile');
    }

    public function showForgot(): void
    {
        echo $this->view->render('developers/auth/forgot', [
            'title' => 'Recuperar contraseña',
        ], 'developers_auth');
    }

    public function forgot(): void
    {
        $this->validateCsrf();
        $email = trim((string)$this->input('email', ''));
        if ($email === '') {
            $this->session->flash('error', 'Ingresa tu email.');
            $this->redirect('/developers/forgot');
        }
        $dev = $this->db->one('SELECT id, name, email FROM developers WHERE email = ? AND is_active = 1', [$email]);
        // Always show success to avoid email enumeration
        $this->session->flash('success', 'Si existe una cuenta con ese email, te hemos enviado un enlace para restablecer tu contraseña.');
        if ($dev) {
            $this->sendPasswordReset((int)$dev['id'], (string)$dev['email'], (string)$dev['name']);
        }
        $this->redirect('/developers/login');
    }

    public function showReset(array $params): void
    {
        $token = (string)$params['token'];
        $row = $this->db->one(
            "SELECT id, developer_id FROM dev_email_tokens
             WHERE token = ? AND purpose='password_reset' AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
            [hash('sha256', $token)]
        );
        if (!$row) {
            $this->session->flash('error', 'Enlace inválido o expirado.');
            $this->redirect('/developers/forgot');
        }
        echo $this->view->render('developers/auth/reset', [
            'title' => 'Nueva contraseña',
            'token' => $token,
        ], 'developers_auth');
    }

    public function reset(array $params): void
    {
        $this->validateCsrf();
        $token = (string)$params['token'];
        $row = $this->db->one(
            "SELECT id, developer_id FROM dev_email_tokens
             WHERE token = ? AND purpose='password_reset' AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
            [hash('sha256', $token)]
        );
        if (!$row) {
            $this->session->flash('error', 'Enlace inválido o expirado.');
            $this->redirect('/developers/forgot');
        }
        $pass = (string)$this->input('password', '');
        if (strlen($pass) < 6) {
            $this->session->flash('error', 'La contraseña debe tener al menos 6 caracteres.');
            $this->redirect('/developers/reset/' . $token);
        }
        $this->db->update('developers', ['password' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])], 'id=?', [(int)$row['developer_id']]);
        $this->db->update('dev_email_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id=?', [(int)$row['id']]);
        $this->session->flash('success', '✓ Contraseña actualizada. Ya puedes iniciar sesión.');
        $this->redirect('/developers/login');
    }

    // ─── Email helpers ─────────────────────────────────────────────

    protected function sendVerificationEmail(int $devId, string $email, string $name): void
    {
        $raw = bin2hex(random_bytes(32));
        $ttl = (int)$this->setting('dev_portal_email_verification_ttl_minutes', '1440');
        $this->db->insert('dev_email_tokens', [
            'developer_id' => $devId,
            'token' => hash('sha256', $raw),
            'purpose' => 'verify_email',
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$ttl} minutes")),
        ]);
        $url = rtrim($this->app->config['app']['url'], '/') . '/developers/verify/' . $raw;
        \App\Core\DevMailer::emailVerification($email, $name, $url, $ttl);
    }

    protected function sendPasswordReset(int $devId, string $email, string $name): void
    {
        $raw = bin2hex(random_bytes(32));
        $ttl = (int)$this->setting('dev_portal_password_reset_ttl_minutes', '60');
        $this->db->insert('dev_email_tokens', [
            'developer_id' => $devId,
            'token' => hash('sha256', $raw),
            'purpose' => 'password_reset',
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$ttl} minutes")),
        ]);
        $url = rtrim($this->app->config['app']['url'], '/') . '/developers/reset/' . $raw;
        \App\Core\DevMailer::passwordReset($email, $name, $url, $ttl);
    }

    protected function setting(string $key, ?string $default = null): ?string
    {
        try {
            $row = $this->db->one('SELECT `value` FROM saas_settings WHERE `key` = ?', [$key]);
            return $row['value'] ?? $default;
        } catch (\Throwable $_e) {
            return $default;
        }
    }
}
