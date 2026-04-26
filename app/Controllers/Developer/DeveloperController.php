<?php
namespace App\Controllers\Developer;

use App\Core\Application;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\DevAuth;
use App\Core\Session;
use App\Core\View;

abstract class DeveloperController
{
    protected Application $app;
    protected Database $db;
    protected Session $session;
    protected DevAuth $devAuth;
    protected View $view;

    public function __construct()
    {
        $this->app = Application::get();
        $this->db = $this->app->db;
        $this->session = $this->app->session;
        $this->devAuth = $this->app->devAuth;
        $this->view = new View();
    }

    protected function render(string $tpl, array $data = [], ?string $layout = 'developers'): void
    {
        $data['devAuth'] = $this->devAuth;
        $data['developer'] = $this->devAuth->developer();
        $data['devSubscription'] = $this->devAuth->activeSubscription();
        $data['devPlan'] = $this->devAuth->plan();
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
        $ref = $_SERVER['HTTP_REFERER'] ?? $this->view->url('/developers');
        header('Location: ' . $ref);
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
            echo 'Sesión expirada o token inválido.';
            exit;
        }
    }

    protected function requireDeveloper(): void
    {
        if (!$this->devAuth->check()) {
            $this->session->flash('error', 'Debes iniciar sesión como developer.');
            $this->redirect('/developers/login');
        }
        $d = $this->devAuth->developer();
        if (!$d || (int)$d['is_active'] !== 1 || !empty($d['suspended_at'])) {
            $this->devAuth->logout();
            $this->session->flash('error', 'Tu cuenta está suspendida o inactiva.');
            $this->redirect('/developers/login');
        }
    }
}
