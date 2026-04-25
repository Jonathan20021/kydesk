<?php
namespace App\Core;

class View
{
    public function render(string $tpl, array $data = [], ?string $layout = 'app'): string
    {
        $app = Application::get();
        $data['app']     = $app;
        $data['auth']    = $app->auth;
        $data['session'] = $app->session;
        $data['tenant']  = $app->tenant;
        $data['flash']   = [
            'success' => $app->session->flash('success'),
            'error'   => $app->session->flash('error'),
            'info'    => $app->session->flash('info'),
        ];
        $data['csrf'] = Csrf::token();
        $data['url']  = fn(string $path = '') => $this->url($path);
        $data['e']    = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        $data['asset']= fn(string $p) => $this->url('public/' . ltrim($p, '/'));

        $content = $this->renderFile(APP_PATH . '/Views/' . $tpl . '.php', $data);

        if ($layout === null) return $content;
        $data['content'] = $content;
        return $this->renderFile(APP_PATH . '/Views/layouts/' . $layout . '.php', $data);
    }

    protected function renderFile(string $file, array $data): string
    {
        if (!is_file($file)) {
            return '<pre style="padding:20px;color:#ef4444">Vista no encontrada: ' . htmlspecialchars($file) . '</pre>';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    public function url(string $path = ''): string
    {
        $app = Application::get();
        $base = rtrim($app->config['app']['url'], '/');
        return $base . '/' . ltrim($path, '/');
    }
}
