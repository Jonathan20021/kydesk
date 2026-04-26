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
    public SuperAuth $superAuth;
    public ?Tenant $tenant = null;

    public function __construct(array $config)
    {
        self::$instance = $this;
        $this->config = $config;

        $this->session = new Session($config['session']);
        $this->session->start();

        $this->db = new Database($config['db']);
        $this->auth = new Auth($this->db, $this->session);
        $this->superAuth = new SuperAuth($this->db, $this->session);
        $this->router = new Router();

        $this->registerRoutes();
    }

    public static function get(): Application { return self::$instance; }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $base = $this->config['app']['base'] ?? '';
        if ($base !== '' && strpos($uri, $base) === 0) $uri = substr($uri, strlen($base));
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
        $r->get('/clients', ['App\Controllers\LandingController', 'clients']);
        $r->get('/careers', ['App\Controllers\LandingController', 'careers']);
        $r->get('/docs', ['App\Controllers\LandingController', 'docs']);
        $r->get('/status', ['App\Controllers\LandingController', 'status']);
        $r->get('/changelog', ['App\Controllers\LandingController', 'changelog']);
        $r->get('/privacy', ['App\Controllers\LandingController', 'privacy']);
        $r->get('/terms', ['App\Controllers\LandingController', 'terms']);

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
        $r->get('/t/{slug}/locked', ['App\Controllers\LicenseController', 'locked']);

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

        // Categorías
        $r->get('/t/{slug}/categories', ['App\Controllers\CategoryController', 'index']);
        $r->post('/t/{slug}/categories', ['App\Controllers\CategoryController', 'store']);
        $r->post('/t/{slug}/categories/{id}', ['App\Controllers\CategoryController', 'update']);
        $r->post('/t/{slug}/categories/{id}/delete', ['App\Controllers\CategoryController', 'delete']);

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
        $r->get('/t/{slug}/automations/create', ['App\Controllers\AutomationController', 'create']);
        $r->post('/t/{slug}/automations', ['App\Controllers\AutomationController', 'store']);
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

        // Centro de ayuda + API docs (tenant)
        $r->get('/t/{slug}/help', ['App\Controllers\HelpController', 'index']);
        $r->get('/t/{slug}/api-docs', ['App\Controllers\HelpController', 'apiDocs']);
        $r->post('/t/{slug}/api-docs/tokens', ['App\Controllers\HelpController', 'tokenCreate']);
        $r->post('/t/{slug}/api-docs/tokens/{id}/revoke', ['App\Controllers\HelpController', 'tokenRevoke']);

        // Soporte directo (tenant → super admin)
        $r->get('/t/{slug}/support', ['App\Controllers\SupportController', 'index']);
        $r->post('/t/{slug}/support', ['App\Controllers\SupportController', 'store']);
        $r->get('/t/{slug}/support/{id}', ['App\Controllers\SupportController', 'show']);
        $r->post('/t/{slug}/support/{id}/reply', ['App\Controllers\SupportController', 'reply']);

        // ─────────────────── REST API v1 ───────────────────
        $r->get('/api',    ['App\Controllers\ApiController', 'index']);
        $r->get('/api/v1', ['App\Controllers\ApiController', 'index']);
        $r->get('/api/v1/me', ['App\Controllers\ApiController', 'me']);
        $r->get('/api/v1/tickets',           ['App\Controllers\ApiController', 'ticketsIndex']);
        $r->post('/api/v1/tickets',          ['App\Controllers\ApiController', 'ticketsCreate']);
        $r->get('/api/v1/tickets/{id}',       ['App\Controllers\ApiController', 'ticketsShow']);
        $r->patch('/api/v1/tickets/{id}',     ['App\Controllers\ApiController', 'ticketsUpdate']);
        $r->post('/api/v1/tickets/{id}',      ['App\Controllers\ApiController', 'ticketsUpdate']);
        $r->delete('/api/v1/tickets/{id}',    ['App\Controllers\ApiController', 'ticketsDelete']);
        $r->post('/api/v1/tickets/{id}/delete', ['App\Controllers\ApiController', 'ticketsDelete']);
        $r->get('/api/v1/tickets/{id}/comments',  ['App\Controllers\ApiController', 'commentsIndex']);
        $r->post('/api/v1/tickets/{id}/comments', ['App\Controllers\ApiController', 'commentsCreate']);
        $r->get('/api/v1/categories',  ['App\Controllers\ApiController', 'categoriesIndex']);
        $r->post('/api/v1/categories', ['App\Controllers\ApiController', 'categoriesCreate']);
        $r->get('/api/v1/companies',   ['App\Controllers\ApiController', 'companiesIndex']);
        $r->post('/api/v1/companies',  ['App\Controllers\ApiController', 'companiesCreate']);
        $r->get('/api/v1/users',         ['App\Controllers\ApiController', 'usersIndex']);
        $r->get('/api/v1/kb/articles',   ['App\Controllers\ApiController', 'kbIndex']);
        $r->get('/api/v1/sla',           ['App\Controllers\ApiController', 'slaIndex']);
        $r->get('/api/v1/automations',   ['App\Controllers\ApiController', 'automationsIndex']);
        $r->get('/api/v1/stats',         ['App\Controllers\ApiController', 'stats']);

        // Instalador
        $r->get('/install', ['App\Controllers\InstallController', 'index']);
        $r->post('/install', ['App\Controllers\InstallController', 'run']);

        // ─────────────────── SUPER ADMIN PANEL ───────────────────
        // Auth
        $r->get('/admin', ['App\Controllers\Admin\AuthController', 'showLogin']);
        $r->get('/admin/login', ['App\Controllers\Admin\AuthController', 'showLogin']);
        $r->post('/admin/login', ['App\Controllers\Admin\AuthController', 'login']);
        $r->post('/admin/logout', ['App\Controllers\Admin\AuthController', 'logout']);
        $r->get('/admin/logout', ['App\Controllers\Admin\AuthController', 'logout']);
        $r->get('/admin/profile', ['App\Controllers\Admin\AuthController', 'profile']);
        $r->post('/admin/profile', ['App\Controllers\Admin\AuthController', 'updateProfile']);

        // Dashboard
        $r->get('/admin/dashboard', ['App\Controllers\Admin\DashboardController', 'index']);

        // Tenants
        $r->get('/admin/tenants', ['App\Controllers\Admin\TenantController', 'index']);
        $r->get('/admin/tenants/create', ['App\Controllers\Admin\TenantController', 'create']);
        $r->post('/admin/tenants', ['App\Controllers\Admin\TenantController', 'store']);
        $r->get('/admin/tenants/{id}', ['App\Controllers\Admin\TenantController', 'show']);
        $r->post('/admin/tenants/{id}', ['App\Controllers\Admin\TenantController', 'update']);
        $r->post('/admin/tenants/{id}/suspend', ['App\Controllers\Admin\TenantController', 'suspend']);
        $r->post('/admin/tenants/{id}/activate', ['App\Controllers\Admin\TenantController', 'activate']);
        $r->post('/admin/tenants/{id}/delete', ['App\Controllers\Admin\TenantController', 'delete']);
        $r->post('/admin/tenants/{id}/impersonate', ['App\Controllers\Admin\TenantController', 'impersonate']);
        $r->post('/admin/tenants/{id}/license/activate', ['App\Controllers\Admin\TenantController', 'licenseActivate']);
        $r->post('/admin/tenants/{id}/license/extend', ['App\Controllers\Admin\TenantController', 'licenseExtendTrial']);
        $r->post('/admin/tenants/{id}/license/revoke', ['App\Controllers\Admin\TenantController', 'licenseRevoke']);

        // Plans
        $r->get('/admin/plans', ['App\Controllers\Admin\PlanController', 'index']);
        $r->get('/admin/plans/create', ['App\Controllers\Admin\PlanController', 'create']);
        $r->post('/admin/plans', ['App\Controllers\Admin\PlanController', 'store']);
        $r->get('/admin/plans/{id}', ['App\Controllers\Admin\PlanController', 'edit']);
        $r->post('/admin/plans/{id}', ['App\Controllers\Admin\PlanController', 'update']);
        $r->post('/admin/plans/{id}/toggle', ['App\Controllers\Admin\PlanController', 'toggle']);
        $r->post('/admin/plans/{id}/delete', ['App\Controllers\Admin\PlanController', 'delete']);

        // Subscriptions
        $r->get('/admin/subscriptions', ['App\Controllers\Admin\SubscriptionController', 'index']);
        $r->post('/admin/subscriptions/{id}', ['App\Controllers\Admin\SubscriptionController', 'update']);
        $r->post('/admin/subscriptions/{id}/cancel', ['App\Controllers\Admin\SubscriptionController', 'cancel']);

        // Invoices
        $r->get('/admin/invoices', ['App\Controllers\Admin\InvoiceController', 'index']);
        $r->get('/admin/invoices/create', ['App\Controllers\Admin\InvoiceController', 'create']);
        $r->post('/admin/invoices', ['App\Controllers\Admin\InvoiceController', 'store']);
        $r->get('/admin/invoices/{id}', ['App\Controllers\Admin\InvoiceController', 'show']);
        $r->post('/admin/invoices/{id}/pay', ['App\Controllers\Admin\InvoiceController', 'markPaid']);
        $r->post('/admin/invoices/{id}/delete', ['App\Controllers\Admin\InvoiceController', 'delete']);

        // Payments
        $r->get('/admin/payments', ['App\Controllers\Admin\PaymentController', 'index']);
        $r->post('/admin/payments', ['App\Controllers\Admin\PaymentController', 'store']);

        // Users (cross-tenant)
        $r->get('/admin/users', ['App\Controllers\Admin\UserController', 'index']);
        $r->get('/admin/users/create', ['App\Controllers\Admin\UserController', 'create']);
        $r->post('/admin/users', ['App\Controllers\Admin\UserController', 'store']);
        $r->get('/admin/users/{id}', ['App\Controllers\Admin\UserController', 'edit']);
        $r->post('/admin/users/{id}', ['App\Controllers\Admin\UserController', 'update']);
        $r->post('/admin/users/{id}/delete', ['App\Controllers\Admin\UserController', 'delete']);

        // Super Admins
        $r->get('/admin/super-admins', ['App\Controllers\Admin\SuperAdminController', 'index']);
        $r->get('/admin/super-admins/create', ['App\Controllers\Admin\SuperAdminController', 'create']);
        $r->post('/admin/super-admins', ['App\Controllers\Admin\SuperAdminController', 'store']);
        $r->get('/admin/super-admins/{id}', ['App\Controllers\Admin\SuperAdminController', 'edit']);
        $r->post('/admin/super-admins/{id}', ['App\Controllers\Admin\SuperAdminController', 'update']);
        $r->post('/admin/super-admins/{id}/delete', ['App\Controllers\Admin\SuperAdminController', 'delete']);

        // Reports
        $r->get('/admin/reports', ['App\Controllers\Admin\ReportController', 'index']);
        $r->get('/admin/audit', ['App\Controllers\Admin\ReportController', 'audit']);

        // Changelog
        $r->get('/admin/changelog', ['App\Controllers\Admin\ChangelogController', 'index']);
        $r->get('/admin/changelog/create', ['App\Controllers\Admin\ChangelogController', 'create']);
        $r->post('/admin/changelog', ['App\Controllers\Admin\ChangelogController', 'store']);
        $r->get('/admin/changelog/{id}', ['App\Controllers\Admin\ChangelogController', 'edit']);
        $r->post('/admin/changelog/{id}', ['App\Controllers\Admin\ChangelogController', 'update']);
        $r->post('/admin/changelog/{id}/delete', ['App\Controllers\Admin\ChangelogController', 'delete']);
        $r->post('/admin/changelog/{id}/feature', ['App\Controllers\Admin\ChangelogController', 'feature']);
        $r->post('/admin/changelog/{id}/publish', ['App\Controllers\Admin\ChangelogController', 'togglePublish']);

        // Settings
        $r->get('/admin/settings', ['App\Controllers\Admin\SettingsController', 'index']);
        $r->post('/admin/settings', ['App\Controllers\Admin\SettingsController', 'update']);
        $r->post('/admin/settings/test-email', ['App\Controllers\Admin\SettingsController', 'testEmail']);

        // Support
        $r->get('/admin/support', ['App\Controllers\Admin\SupportController', 'index']);
        $r->post('/admin/support/{id}', ['App\Controllers\Admin\SupportController', 'update']);
    }
}
