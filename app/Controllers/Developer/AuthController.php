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
        echo $this->view->render('developers/auth/register', [
            'title' => 'Crear cuenta · Developers',
            'plans' => $this->db->all("SELECT * FROM dev_plans WHERE is_active=1 AND is_public=1 ORDER BY sort_order ASC, price_monthly ASC"),
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

        $this->devAuth->attempt($email, $password);
        $this->devAuth->log('developer.register');
        $this->session->flash('success', '¡Bienvenido! Tu cuenta de developer está lista.');
        $this->redirect('/developers/dashboard');
    }

    public function logout(): void
    {
        $this->devAuth->log('developer.logout');
        $this->devAuth->logout();
        $this->redirect('/developers/login');
    }

    protected function setting(string $key, ?string $default = null): ?string
    {
        $row = $this->db->one('SELECT `value` FROM saas_settings WHERE `key` = ?', [$key]);
        return $row['value'] ?? $default;
    }
}
