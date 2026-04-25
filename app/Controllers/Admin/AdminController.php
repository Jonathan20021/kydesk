<?php
namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\SuperAuth;
use App\Core\View;

abstract class AdminController
{
    protected Application $app;
    protected Database $db;
    protected Session $session;
    protected SuperAuth $superAuth;
    protected Auth $auth;
    protected View $view;

    public function __construct()
    {
        $this->app = Application::get();
        $this->db = $this->app->db;
        $this->session = $this->app->session;
        $this->superAuth = $this->app->superAuth;
        $this->auth = $this->app->auth;
        $this->view = new View();
    }

    protected function render(string $tpl, array $data = [], ?string $layout = 'admin'): void
    {
        $data['superAuth'] = $this->superAuth;
        $data['superAdmin'] = $this->superAuth->admin();
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
        $ref = $_SERVER['HTTP_REFERER'] ?? $this->view->url('/admin');
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
            echo 'Sesión expirada o token inválido.';
            exit;
        }
    }

    protected function requireSuperAuth(): void
    {
        if (!$this->superAuth->check()) {
            $this->session->flash('error', 'Debes iniciar sesión como super admin.');
            $this->redirect('/admin/login');
        }
        $admin = $this->superAuth->admin();
        if (!$admin || (int)$admin['is_active'] !== 1) {
            $this->superAuth->logout();
            $this->session->flash('error', 'Tu cuenta está inactiva.');
            $this->redirect('/admin/login');
        }
    }

    protected function requireCan(string $action): void
    {
        $this->requireSuperAuth();
        if (!$this->superAuth->can($action)) {
            http_response_code(403);
            $this->session->flash('error', 'No tienes permiso para esta acción.');
            $this->redirect('/admin');
        }
    }
}
