<?php
namespace App\Core;

class Application
{
    public static ?Application $instance = null;
    public array $config;
    public Router $router;
    public Database $db;
    public Session $session;
    public Auth $auth;
    public ?Tenant $tenant = null;

    public function __construct(array $config)
    {
        self::$instance = $this;
        $this->config = $config;

        $this->session = new Session($config['session']);
        $this->session->start();

        $this->db = new Database($config['db']);
        $this->auth = new Auth($this->db, $this->session);
        $this->router = new Router();

        $this->registerRoutes();
    }

    public static function get(): Application { return self::$instance; }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $base = '/kyros-helpdesk';
        if (strpos($uri, $base) === 0) $uri = substr($uri, strlen($base));
        if ($uri === '' || $uri === false) $uri = '/';

        if (random_int(1, 50) === 1) {
            try { (new DemoSeeder($this->db))->cleanup(); } catch (\Throwable $e) { /* ignore */ }
        }

        $this->router->dispatch($method, $uri);
    }

    protected function registerRoutes(): void
    {
        $r = $this->router;

        // Públicas
        $r->get('/', ['App\Controllers\LandingController', 'index']);
        $r->get('/pricing', ['App\Controllers\LandingController', 'pricing']);
        $r->get('/features', ['App\Controllers\LandingController', 'features']);
        $r->get('/features/{key}', ['App\Controllers\LandingController', 'feature']);
        $r->get('/contact', ['App\Controllers\LandingController', 'contact']);

        // Demos
        $r->get('/demo', ['App\Controllers\DemoController', 'picker']);
        $r->post('/demo/start/{plan}', ['App\Controllers\DemoController', 'start']);
        $r->get('/demo/cleanup', ['App\Controllers\DemoController', 'cleanup']);

        // Auth
        $r->get('/auth/login', ['App\Controllers\AuthController', 'showLogin']);
        $r->post('/auth/login', ['App\Controllers\AuthController', 'login']);
        $r->get('/auth/register', ['App\Controllers\AuthController', 'showRegister']);
        $r->post('/auth/register', ['App\Controllers\AuthController', 'register']);
        $r->post('/auth/logout', ['App\Controllers\AuthController', 'logout']);
        $r->get('/auth/logout', ['App\Controllers\AuthController', 'logout']);

        // Portal público
        $r->get('/portal/{slug}', ['App\Controllers\PortalController', 'index']);
        $r->get('/portal/{slug}/new', ['App\Controllers\PortalController', 'create']);
        $r->post('/portal/{slug}/new', ['App\Controllers\PortalController', 'store']);
        $r->get('/portal/{slug}/ticket/{token}', ['App\Controllers\PortalController', 'show']);
        $r->post('/portal/{slug}/ticket/{token}/reply', ['App\Controllers\PortalController', 'reply']);
        $r->get('/portal/{slug}/kb', ['App\Controllers\PortalController', 'kb']);
        $r->get('/portal/{slug}/kb/{articleSlug}', ['App\Controllers\PortalController', 'article']);

        // Panel
        $r->get('/t/{slug}', ['App\Controllers\DashboardController', 'index']);
        $r->get('/t/{slug}/dashboard', ['App\Controllers\DashboardController', 'index']);

        // Tickets
        $r->get('/t/{slug}/tickets', ['App\Controllers\TicketController', 'index']);
        $r->get('/t/{slug}/tickets/board', ['App\Controllers\TicketController', 'board']);
        $r->get('/t/{slug}/tickets/create', ['App\Controllers\TicketController', 'create']);
        $r->post('/t/{slug}/tickets', ['App\Controllers\TicketController', 'store']);
        $r->get('/t/{slug}/tickets/{id}', ['App\Controllers\TicketController', 'show']);
        $r->post('/t/{slug}/tickets/{id}/comment', ['App\Controllers\TicketController', 'comment']);
        $r->post('/t/{slug}/tickets/{id}/update', ['App\Controllers\TicketController', 'update']);
        $r->post('/t/{slug}/tickets/{id}/assign', ['App\Controllers\TicketController', 'assign']);
        $r->post('/t/{slug}/tickets/{id}/escalate', ['App\Controllers\TicketController', 'escalate']);
        $r->post('/t/{slug}/tickets/{id}/move', ['App\Controllers\TicketController', 'move']);
        $r->post('/t/{slug}/tickets/{id}/delete', ['App\Controllers\TicketController', 'delete']);

        // Macros / Plantillas
        $r->get('/t/{slug}/macros', ['App\Controllers\MacroController', 'index']);
        $r->post('/t/{slug}/macros', ['App\Controllers\MacroController', 'store']);
        $r->get('/t/{slug}/macros/{id}', ['App\Controllers\MacroController', 'edit']);
        $r->post('/t/{slug}/macros/{id}', ['App\Controllers\MacroController', 'update']);
        $r->post('/t/{slug}/macros/{id}/delete', ['App\Controllers\MacroController', 'delete']);
        $r->get('/t/{slug}/macros.json', ['App\Controllers\MacroController', 'listJson']);

        // Notas
        $r->get('/t/{slug}/notes', ['App\Controllers\NoteController', 'index']);
        $r->post('/t/{slug}/notes', ['App\Controllers\NoteController', 'store']);
        $r->post('/t/{slug}/notes/{id}', ['App\Controllers\NoteController', 'update']);
        $r->post('/t/{slug}/notes/{id}/delete', ['App\Controllers\NoteController', 'delete']);

        // Todos
        $r->get('/t/{slug}/todos', ['App\Controllers\TodoController', 'index']);
        $r->post('/t/{slug}/todos/lists', ['App\Controllers\TodoController', 'storeList']);
        $r->post('/t/{slug}/todos/lists/{id}/delete', ['App\Controllers\TodoController', 'deleteList']);
        $r->post('/t/{slug}/todos', ['App\Controllers\TodoController', 'store']);
        $r->post('/t/{slug}/todos/{id}/toggle', ['App\Controllers\TodoController', 'toggle']);
        $r->post('/t/{slug}/todos/{id}/delete', ['App\Controllers\TodoController', 'delete']);

        // Usuarios
        $r->get('/t/{slug}/users', ['App\Controllers\UserController', 'index']);
        $r->get('/t/{slug}/users/create', ['App\Controllers\UserController', 'create']);
        $r->post('/t/{slug}/users', ['App\Controllers\UserController', 'store']);
        $r->get('/t/{slug}/users/{id}', ['App\Controllers\UserController', 'edit']);
        $r->post('/t/{slug}/users/{id}', ['App\Controllers\UserController', 'update']);
        $r->post('/t/{slug}/users/{id}/delete', ['App\Controllers\UserController', 'delete']);

        // Roles
        $r->get('/t/{slug}/roles', ['App\Controllers\RoleController', 'index']);
        $r->post('/t/{slug}/roles', ['App\Controllers\RoleController', 'store']);
        $r->get('/t/{slug}/roles/{id}', ['App\Controllers\RoleController', 'edit']);
        $r->post('/t/{slug}/roles/{id}', ['App\Controllers\RoleController', 'update']);
        $r->post('/t/{slug}/roles/{id}/delete', ['App\Controllers\RoleController', 'delete']);

        // Companies
        $r->get('/t/{slug}/companies', ['App\Controllers\CompanyController', 'index']);
        $r->get('/t/{slug}/companies/create', ['App\Controllers\CompanyController', 'create']);
        $r->post('/t/{slug}/companies', ['App\Controllers\CompanyController', 'store']);
        $r->get('/t/{slug}/companies/{id}', ['App\Controllers\CompanyController', 'show']);
        $r->post('/t/{slug}/companies/{id}', ['App\Controllers\CompanyController', 'update']);
        $r->post('/t/{slug}/companies/{id}/delete', ['App\Controllers\CompanyController', 'delete']);

        // Assets
        $r->get('/t/{slug}/assets', ['App\Controllers\AssetController', 'index']);
        $r->get('/t/{slug}/assets/create', ['App\Controllers\AssetController', 'create']);
        $r->post('/t/{slug}/assets', ['App\Controllers\AssetController', 'store']);
        $r->post('/t/{slug}/assets/{id}/delete', ['App\Controllers\AssetController', 'delete']);

        // KB
        $r->get('/t/{slug}/kb', ['App\Controllers\KbController', 'index']);
        $r->get('/t/{slug}/kb/create', ['App\Controllers\KbController', 'create']);
        $r->post('/t/{slug}/kb', ['App\Controllers\KbController', 'store']);
        $r->get('/t/{slug}/kb/{id}', ['App\Controllers\KbController', 'show']);
        $r->post('/t/{slug}/kb/{id}/delete', ['App\Controllers\KbController', 'delete']);

        // Automations
        $r->get('/t/{slug}/automations', ['App\Controllers\AutomationController', 'index']);
        $r->post('/t/{slug}/automations/{id}/toggle', ['App\Controllers\AutomationController', 'toggle']);
        $r->post('/t/{slug}/automations/{id}/delete', ['App\Controllers\AutomationController', 'delete']);

        // SLA
        $r->get('/t/{slug}/sla', ['App\Controllers\SlaController', 'index']);
        $r->post('/t/{slug}/sla/{id}', ['App\Controllers\SlaController', 'update']);

        // Audit
        $r->get('/t/{slug}/audit', ['App\Controllers\AuditController', 'index']);

        // Reportes
        $r->get('/t/{slug}/reports', ['App\Controllers\ReportController', 'index']);

        // Ajustes
        $r->get('/t/{slug}/settings', ['App\Controllers\SettingsController', 'index']);
        $r->post('/t/{slug}/settings', ['App\Controllers\SettingsController', 'update']);

        // Perfil
        $r->get('/t/{slug}/profile', ['App\Controllers\ProfileController', 'index']);
        $r->post('/t/{slug}/profile', ['App\Controllers\ProfileController', 'update']);

        // Personalización
        $r->get('/t/{slug}/preferences', ['App\Controllers\PreferencesController', 'index']);
        $r->post('/t/{slug}/preferences', ['App\Controllers\PreferencesController', 'update']);
        $r->post('/t/{slug}/preferences/reset', ['App\Controllers\PreferencesController', 'reset']);

        // Instalador
        $r->get('/install', ['App\Controllers\InstallController', 'index']);
        $r->post('/install', ['App\Controllers\InstallController', 'run']);
    }
}
