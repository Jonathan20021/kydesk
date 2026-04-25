<?php
namespace App\Core;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [strtoupper($method), $path, $handler];
    }
    public function get(string $p, array $h): void  { $this->add('GET', $p, $h); }
    public function post(string $p, array $h): void { $this->add('POST', $p, $h); }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        foreach ($this->routes as [$m, $path, $handler]) {
            if ($m !== $method) continue;
            $regex = $this->compile($path);
            if (preg_match($regex, $uri, $m2)) {
                $params = [];
                foreach ($m2 as $k => $v) {
                    if (!is_int($k)) $params[$k] = $v;
                }
                [$class, $action] = $handler;
                if (!class_exists($class)) {
                    $this->abort(500, "Controlador no encontrado: $class");
                    return;
                }
                $ctrl = new $class();
                if (!method_exists($ctrl, $action)) {
                    $this->abort(500, "Acción no encontrada: $class::$action");
                    return;
                }
                $ctrl->$action($params);
                return;
            }
        }
        $this->abort(404, 'Ruta no encontrada');
    }

    protected function compile(string $path): string
    {
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);
        return '#^' . $regex . '/?$#';
    }

    protected function abort(int $code, string $msg): void
    {
        http_response_code($code);
        $view = new View();
        echo $view->render('errors/' . $code, ['message' => $msg], 'public');
    }
}
