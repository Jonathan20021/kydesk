<?php
namespace App\Core;

abstract class Controller
{
    protected Application $app;
    protected Database $db;
    protected Session $session;
    protected Auth $auth;
    protected View $view;

    public function __construct()
    {
        $this->app = Application::get();
        $this->db = $this->app->db;
        $this->session = $this->app->session;
        $this->auth = $this->app->auth;
        $this->view = new View();
    }

    protected function render(string $tpl, array $data = [], ?string $layout = 'app'): void
    {
        echo $this->view->render($tpl, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        if (!preg_match('#^https?://#i', $path)) {
            $path = $this->view->url($path);
        }
        header('Location: ' . $path);
        exit;
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? $this->view->url('/');
        header('Location: ' . $ref);
        exit;
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        if (array_key_exists($key, $_POST)) return $_POST[$key];
        if (array_key_exists($key, $_GET)) return $_GET[$key];
        return $default;
    }

    protected function validateCsrf(): void
    {
        $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!Csrf::validate($t)) {
            http_response_code(419);
            echo 'Sesión expirada o token inválido. Vuelve a la página anterior y recarga.';
            exit;
        }
    }

    protected function requireAuth(): void
    {
        if (!$this->auth->check()) {
            $this->session->flash('error', 'Debes iniciar sesión.');
            $this->redirect('/auth/login');
        }
    }

    protected function requireTenant(string $slug, bool $allowLocked = false): \App\Core\Tenant
    {
        $this->requireAuth();
        $tenant = \App\Core\Tenant::resolve($slug);
        if (!$tenant) {
            $this->session->flash('error', 'Organización no encontrada.');
            $this->redirect('/auth/login');
        }
        $user = $this->auth->user();
        if ((int)$user['tenant_id'] !== $tenant->id) {
            http_response_code(403);
            $this->session->flash('error', 'No tienes acceso a esta organización.');
            $this->redirect('/auth/login');
        }
        $this->app->tenant = $tenant;

        if (!$allowLocked) {
            try {
                $lic = \App\Core\License::status($tenant);
                if (!$lic['is_usable']) {
                    $this->redirect('/t/' . $tenant->slug . '/locked');
                }
            } catch (\Throwable $e) { /* tabla suscripciones opcional */ }
        }

        return $tenant;
    }

    protected function can(string $perm): bool
    {
        return $this->auth->can($perm);
    }

    protected function requireCan(string $perm): void
    {
        if (!$this->can($perm)) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['message' => "Permiso requerido: $perm"], 'app');
            exit;
        }
    }

    protected function requireFeature(string $feature): void
    {
        if (!\App\Core\Plan::has($this->app->tenant, $feature)) {
            http_response_code(402);
            echo $this->view->render('errors/upsell', [
                'feature' => $feature,
                'currentPlan' => \App\Core\Plan::tenantPlan($this->app->tenant),
                'requiredPlan' => \App\Core\Plan::requiredPlanFor($feature),
            ], 'app');
            exit;
        }
    }

    protected function enforceLimit(string $key, int $current, string $entityLabel, string $redirect): void
    {
        $check = \App\Core\Plan::checkLimit($this->app->tenant, $key, $current);
        if (!$check['ok']) {
            $plan = \App\Core\Plan::tenantPlan($this->app->tenant);
            $planLabel = \App\Core\Plan::label($this->app->tenant);
            $msg = sprintf('Límite del plan %s alcanzado: %d/%d %s. Hacé upgrade para continuar.', $planLabel, $check['current'], $check['max'], $entityLabel);
            $this->session->flash('error', $msg);
            $this->redirect($redirect);
        }
    }

    protected function enforceChannel(string $channel, string $redirect): void
    {
        if (!\App\Core\Plan::channelAllowed($this->app->tenant, $channel)) {
            $planLabel = \App\Core\Plan::label($this->app->tenant);
            $this->session->flash('error', sprintf('El canal "%s" no está disponible en plan %s. Tu plan permite: portal, email.', $channel, $planLabel));
            $this->redirect($redirect);
        }
    }
}
