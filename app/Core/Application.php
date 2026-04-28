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
    public DevAuth $devAuth;
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
        $this->devAuth = new DevAuth($this->db, $this->session);
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

        // Departamentos (PRO)
        $r->get('/t/{slug}/departments', ['App\Controllers\DepartmentController', 'index']);
        $r->post('/t/{slug}/departments', ['App\Controllers\DepartmentController', 'store']);
        $r->get('/t/{slug}/departments/{id}', ['App\Controllers\DepartmentController', 'show']);
        $r->post('/t/{slug}/departments/{id}', ['App\Controllers\DepartmentController', 'update']);
        $r->post('/t/{slug}/departments/{id}/delete', ['App\Controllers\DepartmentController', 'delete']);
        $r->post('/t/{slug}/departments/{id}/agents', ['App\Controllers\DepartmentController', 'addAgent']);
        $r->post('/t/{slug}/departments/{id}/agents/{userId}/remove', ['App\Controllers\DepartmentController', 'removeAgent']);
        $r->post('/t/{slug}/departments/{id}/agents/{userId}/lead', ['App\Controllers\DepartmentController', 'toggleLead']);

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
        $r->post('/t/{slug}/todos/reminders', ['App\Controllers\TodoController', 'sendDueReminders']);
        $r->post('/t/{slug}/todos/{id}', ['App\Controllers\TodoController', 'update']);
        $r->post('/t/{slug}/todos/{id}/toggle', ['App\Controllers\TodoController', 'toggle']);
        $r->post('/t/{slug}/todos/{id}/remind', ['App\Controllers\TodoController', 'sendReminder']);
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

        // ─────────── CUSTOM FIELDS ───────────
        $r->get('/t/{slug}/custom-fields', ['App\Controllers\CustomFieldController', 'index']);
        $r->post('/t/{slug}/custom-fields', ['App\Controllers\CustomFieldController', 'store']);
        $r->post('/t/{slug}/custom-fields/{id}', ['App\Controllers\CustomFieldController', 'update']);
        $r->post('/t/{slug}/custom-fields/{id}/delete', ['App\Controllers\CustomFieldController', 'delete']);

        // ─────────── CSAT / NPS ───────────
        $r->get('/t/{slug}/csat', ['App\Controllers\CsatController', 'index']);
        $r->post('/t/{slug}/csat/settings', ['App\Controllers\CsatController', 'settings']);
        $r->post('/t/{slug}/csat/trigger', ['App\Controllers\CsatController', 'trigger']);
        $r->get('/csat/{token}', ['App\Controllers\CsatController', 'show']);
        $r->post('/csat/{token}', ['App\Controllers\CsatController', 'respond']);

        // ─────────── STATUS PAGE ───────────
        $r->get('/t/{slug}/status', ['App\Controllers\StatusPageController', 'index']);
        $r->post('/t/{slug}/status/components', ['App\Controllers\StatusPageController', 'componentStore']);
        $r->post('/t/{slug}/status/components/{id}', ['App\Controllers\StatusPageController', 'componentUpdate']);
        $r->post('/t/{slug}/status/components/{id}/delete', ['App\Controllers\StatusPageController', 'componentDelete']);
        $r->post('/t/{slug}/status/incidents', ['App\Controllers\StatusPageController', 'incidentStore']);
        $r->post('/t/{slug}/status/incidents/{id}', ['App\Controllers\StatusPageController', 'incidentUpdate']);
        $r->post('/t/{slug}/status/incidents/{id}/delete', ['App\Controllers\StatusPageController', 'incidentDelete']);
        $r->get('/status/{slug}', ['App\Controllers\StatusPageController', 'publicPage']);
        $r->post('/status/{slug}/subscribe', ['App\Controllers\StatusPageController', 'subscribe']);
        $r->get('/status/{slug}/confirm/{token}', ['App\Controllers\StatusPageController', 'confirm']);

        // ─────────── CUSTOMER PORTAL AUTH ───────────
        $r->get('/portal/{slug}/login', ['App\Controllers\PortalAuthController', 'showLogin']);
        $r->post('/portal/{slug}/login', ['App\Controllers\PortalAuthController', 'login']);
        $r->get('/portal/{slug}/register', ['App\Controllers\PortalAuthController', 'showRegister']);
        $r->post('/portal/{slug}/register', ['App\Controllers\PortalAuthController', 'register']);
        $r->get('/portal/{slug}/verify/{token}', ['App\Controllers\PortalAuthController', 'verify']);
        $r->post('/portal/{slug}/logout', ['App\Controllers\PortalAuthController', 'logout']);
        $r->get('/portal/{slug}/forgot', ['App\Controllers\PortalAuthController', 'showForgot']);
        $r->post('/portal/{slug}/forgot', ['App\Controllers\PortalAuthController', 'forgot']);
        $r->get('/portal/{slug}/reset/{token}', ['App\Controllers\PortalAuthController', 'showReset']);
        $r->post('/portal/{slug}/reset/{token}', ['App\Controllers\PortalAuthController', 'reset']);
        $r->get('/portal/{slug}/account', ['App\Controllers\PortalAuthController', 'account']);
        $r->post('/portal/{slug}/account/profile', ['App\Controllers\PortalAuthController', 'updateProfile']);
        $r->get('/t/{slug}/portal-users', ['App\Controllers\PortalAuthController', 'manageList']);
        $r->post('/t/{slug}/portal-users/{id}/toggle', ['App\Controllers\PortalAuthController', 'manageToggle']);
        $r->post('/t/{slug}/portal-users/{id}/manager', ['App\Controllers\PortalAuthController', 'manageToggleManager']);
        $r->post('/t/{slug}/portal-users/{id}/delete', ['App\Controllers\PortalAuthController', 'manageDelete']);

        // ─────────── COMPANY PORTAL (autenticado · multi-empresa) ───────────
        $r->get('/portal/{slug}/company',                            ['App\Controllers\CompanyPortalController', 'dashboard']);
        $r->get('/portal/{slug}/company/tickets',                    ['App\Controllers\CompanyPortalController', 'tickets']);
        $r->get('/portal/{slug}/company/tickets/new',                ['App\Controllers\CompanyPortalController', 'ticketCreate']);
        $r->post('/portal/{slug}/company/tickets',                   ['App\Controllers\CompanyPortalController', 'ticketStore']);
        $r->get('/portal/{slug}/company/tickets/{id}',               ['App\Controllers\CompanyPortalController', 'ticketShow']);
        $r->post('/portal/{slug}/company/tickets/{id}/reply',        ['App\Controllers\CompanyPortalController', 'ticketReply']);
        $r->get('/portal/{slug}/company/reports',                    ['App\Controllers\CompanyPortalController', 'reports']);
        $r->get('/portal/{slug}/company/team',                       ['App\Controllers\CompanyPortalController', 'team']);
        $r->get('/portal/{slug}/company/export/tickets.csv',         ['App\Controllers\CompanyPortalController', 'exportTicketsCsv']);
        $r->get('/portal/{slug}/company/export/tickets.pdf',         ['App\Controllers\CompanyPortalController', 'exportTicketsPdf']);
        $r->get('/portal/{slug}/company/export/report.csv',          ['App\Controllers\CompanyPortalController', 'exportReportCsv']);
        $r->get('/portal/{slug}/company/export/report.pdf',          ['App\Controllers\CompanyPortalController', 'exportReportPdf']);

        // ─────────── TIME TRACKING ───────────
        $r->get('/t/{slug}/time', ['App\Controllers\TimeController', 'index']);
        $r->post('/t/{slug}/time/start', ['App\Controllers\TimeController', 'start']);
        $r->post('/t/{slug}/time/manual', ['App\Controllers\TimeController', 'manualStore']);
        $r->post('/t/{slug}/time/{id}/stop', ['App\Controllers\TimeController', 'stop']);
        $r->post('/t/{slug}/time/{id}/delete', ['App\Controllers\TimeController', 'delete']);

        // ─────────── EMAIL-TO-TICKET ───────────
        $r->get('/t/{slug}/email-inbound', ['App\Controllers\EmailInboundController', 'index']);
        $r->post('/t/{slug}/email-inbound', ['App\Controllers\EmailInboundController', 'store']);
        $r->post('/t/{slug}/email-inbound/{id}', ['App\Controllers\EmailInboundController', 'update']);
        $r->post('/t/{slug}/email-inbound/{id}/delete', ['App\Controllers\EmailInboundController', 'delete']);
        $r->post('/t/{slug}/email-inbound/{id}/fetch', ['App\Controllers\EmailInboundController', 'fetch']);
        $r->post('/api/v1/email-inbound/forward', ['App\Controllers\EmailInboundController', 'forwardWebhook']);

        // ─────────── LIVE CHAT ───────────
        $r->get('/t/{slug}/chat', ['App\Controllers\ChatController', 'index']);
        $r->get('/t/{slug}/chat/widgets', ['App\Controllers\ChatController', 'widgetConfig']);
        $r->post('/t/{slug}/chat/widgets/{id}', ['App\Controllers\ChatController', 'widgetUpdate']);
        $r->get('/t/{slug}/chat/poll', ['App\Controllers\ChatController', 'agentPoll']);
        $r->get('/t/{slug}/chat/{id}', ['App\Controllers\ChatController', 'show']);
        $r->post('/t/{slug}/chat/{id}/reply', ['App\Controllers\ChatController', 'reply']);
        $r->post('/t/{slug}/chat/{id}/close', ['App\Controllers\ChatController', 'close']);
        $r->post('/t/{slug}/chat/{id}/to-ticket', ['App\Controllers\ChatController', 'convertToTicket']);
        $r->get('/chat-widget/{publicKey}.js', ['App\Controllers\ChatController', 'widgetScript']);
        $r->post('/chat-api/start', ['App\Controllers\ChatController', 'visitorStart']);
        $r->post('/chat-api/send', ['App\Controllers\ChatController', 'visitorSend']);
        $r->get('/chat-api/poll', ['App\Controllers\ChatController', 'visitorPoll']);

        // ─────────── AI ASISTENTE (tenant) ───────────
        $r->get('/t/{slug}/ai', ['App\Controllers\AiController', 'settings']);
        $r->post('/t/{slug}/ai/settings', ['App\Controllers\AiController', 'settingsUpdate']);
        $r->post('/t/{slug}/ai/run', ['App\Controllers\AiController', 'run']);

        // ─────────── ITSM ───────────
        $r->get('/t/{slug}/itsm', ['App\Controllers\ItsmController', 'index']);
        $r->post('/t/{slug}/itsm/catalog', ['App\Controllers\ItsmController', 'catalogStore']);
        $r->post('/t/{slug}/itsm/catalog/{id}/delete', ['App\Controllers\ItsmController', 'catalogDelete']);
        $r->post('/t/{slug}/itsm/changes', ['App\Controllers\ItsmController', 'changeStore']);
        $r->get('/t/{slug}/itsm/changes/{id}', ['App\Controllers\ItsmController', 'changeShow']);
        $r->post('/t/{slug}/itsm/changes/{id}', ['App\Controllers\ItsmController', 'changeUpdate']);
        $r->post('/t/{slug}/itsm/approvals/{id}', ['App\Controllers\ItsmController', 'changeApprove']);
        $r->post('/t/{slug}/itsm/problems', ['App\Controllers\ItsmController', 'problemStore']);
        $r->post('/t/{slug}/itsm/problems/{id}', ['App\Controllers\ItsmController', 'problemUpdate']);

        // ─────────── REPORTS BUILDER ───────────
        $r->get('/t/{slug}/reports-builder', ['App\Controllers\ReportBuilderController', 'index']);
        $r->post('/t/{slug}/reports-builder/create', ['App\Controllers\ReportBuilderController', 'create']);
        $r->get('/t/{slug}/reports-builder/{id}', ['App\Controllers\ReportBuilderController', 'show']);
        $r->post('/t/{slug}/reports-builder/{id}', ['App\Controllers\ReportBuilderController', 'update']);
        $r->post('/t/{slug}/reports-builder/{id}/delete', ['App\Controllers\ReportBuilderController', 'delete']);

        // Igualas (Retainers) — Business / Enterprise
        $r->get('/t/{slug}/retainers', ['App\Controllers\RetainerController', 'index']);
        $r->get('/t/{slug}/retainers/settings', ['App\Controllers\RetainerController', 'settings']);
        $r->post('/t/{slug}/retainers/categories', ['App\Controllers\RetainerController', 'categoryStore']);
        $r->post('/t/{slug}/retainers/categories/{id}', ['App\Controllers\RetainerController', 'categoryUpdate']);
        $r->post('/t/{slug}/retainers/categories/{id}/delete', ['App\Controllers\RetainerController', 'categoryDelete']);
        $r->post('/t/{slug}/retainers/templates', ['App\Controllers\RetainerController', 'templateStore']);
        $r->post('/t/{slug}/retainers/templates/{id}', ['App\Controllers\RetainerController', 'templateUpdate']);
        $r->post('/t/{slug}/retainers/templates/{id}/delete', ['App\Controllers\RetainerController', 'templateDelete']);
        $r->get('/t/{slug}/retainers/create', ['App\Controllers\RetainerController', 'create']);
        $r->post('/t/{slug}/retainers', ['App\Controllers\RetainerController', 'store']);
        $r->get('/t/{slug}/retainers/{id}', ['App\Controllers\RetainerController', 'show']);
        $r->post('/t/{slug}/retainers/{id}', ['App\Controllers\RetainerController', 'update']);
        $r->post('/t/{slug}/retainers/{id}/delete', ['App\Controllers\RetainerController', 'delete']);
        $r->post('/t/{slug}/retainers/{id}/consumptions', ['App\Controllers\RetainerController', 'logConsumption']);
        $r->post('/t/{slug}/retainers/{id}/periods/{periodId}/close', ['App\Controllers\RetainerController', 'closePeriod']);

        // Companies
        $r->get('/t/{slug}/companies', ['App\Controllers\CompanyController', 'index']);
        $r->get('/t/{slug}/companies/create', ['App\Controllers\CompanyController', 'create']);
        $r->post('/t/{slug}/companies', ['App\Controllers\CompanyController', 'store']);
        $r->get('/t/{slug}/companies/{id}', ['App\Controllers\CompanyController', 'show']);
        $r->post('/t/{slug}/companies/{id}', ['App\Controllers\CompanyController', 'update']);
        $r->post('/t/{slug}/companies/{id}/delete', ['App\Controllers\CompanyController', 'delete']);
        // Gestión de portal_users por empresa
        $r->post('/t/{slug}/companies/{id}/portal-users',                       ['App\Controllers\CompanyController', 'portalUserStore']);
        $r->post('/t/{slug}/companies/{id}/portal-users/{userId}',              ['App\Controllers\CompanyController', 'portalUserUpdate']);
        $r->post('/t/{slug}/companies/{id}/portal-users/{userId}/toggle',       ['App\Controllers\CompanyController', 'portalUserToggle']);
        $r->post('/t/{slug}/companies/{id}/portal-users/{userId}/manager',      ['App\Controllers\CompanyController', 'portalUserToggleManager']);
        $r->post('/t/{slug}/companies/{id}/portal-users/{userId}/resend',       ['App\Controllers\CompanyController', 'portalUserResend']);
        $r->post('/t/{slug}/companies/{id}/portal-users/{userId}/delete',       ['App\Controllers\CompanyController', 'portalUserDelete']);

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

        // Integraciones (PRO+)
        $r->get('/t/{slug}/integrations', ['App\Controllers\IntegrationController', 'index']);
        $r->get('/t/{slug}/integrations/{provider}', ['App\Controllers\IntegrationController', 'configure']);
        $r->post('/t/{slug}/integrations/{provider}', ['App\Controllers\IntegrationController', 'store']);
        $r->get('/t/{slug}/integrations/{provider}/{id}', ['App\Controllers\IntegrationController', 'configure']);
        $r->post('/t/{slug}/integrations/{provider}/{id}', ['App\Controllers\IntegrationController', 'update']);
        $r->post('/t/{slug}/integrations/{provider}/{id}/toggle', ['App\Controllers\IntegrationController', 'toggle']);
        $r->post('/t/{slug}/integrations/{provider}/{id}/test', ['App\Controllers\IntegrationController', 'test']);
        $r->post('/t/{slug}/integrations/{provider}/{id}/delete', ['App\Controllers\IntegrationController', 'delete']);

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

        // Billing del tenant (cliente helpdesk)
        $r->get('/t/{slug}/billing', ['App\Controllers\BillingController', 'index']);
        $r->get('/t/{slug}/billing/payment-info', ['App\Controllers\BillingController', 'paymentInfo']);
        $r->post('/t/{slug}/billing/payment-proof', ['App\Controllers\BillingController', 'uploadProof']);

        // Soporte directo (tenant → super admin)
        $r->get('/t/{slug}/support', ['App\Controllers\SupportController', 'index']);
        $r->post('/t/{slug}/support', ['App\Controllers\SupportController', 'store']);
        $r->get('/t/{slug}/support/{id}', ['App\Controllers\SupportController', 'show']);
        $r->post('/t/{slug}/support/{id}/reply', ['App\Controllers\SupportController', 'reply']);

        // ─────────────────── REST API v1 (modular) ───────────────────
        // Meta
        $r->get('/api',                      ['App\Controllers\Api\MetaController', 'index']);
        $r->get('/api/v1',                   ['App\Controllers\Api\MetaController', 'index']);
        $r->get('/api/v1/me',                ['App\Controllers\Api\MetaController', 'me']);
        $r->get('/api/v1/health',            ['App\Controllers\Api\MetaController', 'health']);
        $r->get('/api/v1/stats',             ['App\Controllers\Api\MetaController', 'stats']);
        $r->get('/api/v1/search',            ['App\Controllers\Api\MetaController', 'search']);
        // OpenAPI spec + Postman
        $r->get('/api/v1/openapi.json',      ['App\Controllers\Api\SpecController', 'openapi']);
        $r->get('/api/v1/postman.json',      ['App\Controllers\Api\SpecController', 'postman']);

        // CSV exports
        $r->get('/api/v1/tickets.csv',       ['App\Controllers\Api\ExportController', 'ticketsCsv']);
        $r->get('/api/v1/companies.csv',     ['App\Controllers\Api\ExportController', 'companiesCsv']);
        $r->get('/api/v1/users.csv',         ['App\Controllers\Api\ExportController', 'usersCsv']);

        // Events / Activity stream
        $r->get('/api/v1/events/recent',     ['App\Controllers\Api\EventsController', 'recent']);
        $r->get('/api/v1/events/stream',     ['App\Controllers\Api\EventsController', 'stream']);

        // Tickets (full CRUD + sub-resources)
        $r->get('/api/v1/tickets',                    ['App\Controllers\Api\TicketsController', 'index']);
        $r->post('/api/v1/tickets',                   ['App\Controllers\Api\TicketsController', 'create']);
        $r->post('/api/v1/tickets/batch',             ['App\Controllers\Api\TicketsController', 'batch']);
        $r->get('/api/v1/tickets/{id}',                ['App\Controllers\Api\TicketsController', 'show']);
        $r->patch('/api/v1/tickets/{id}',              ['App\Controllers\Api\TicketsController', 'update']);
        $r->post('/api/v1/tickets/{id}',               ['App\Controllers\Api\TicketsController', 'update']);
        $r->delete('/api/v1/tickets/{id}',             ['App\Controllers\Api\TicketsController', 'delete']);
        $r->post('/api/v1/tickets/{id}/delete',        ['App\Controllers\Api\TicketsController', 'delete']);
        $r->get('/api/v1/tickets/{id}/comments',       ['App\Controllers\Api\TicketsController', 'commentsIndex']);
        $r->post('/api/v1/tickets/{id}/comments',      ['App\Controllers\Api\TicketsController', 'commentsCreate']);
        $r->delete('/api/v1/tickets/{id}/comments/{cid}', ['App\Controllers\Api\TicketsController', 'commentDelete']);
        $r->post('/api/v1/tickets/{id}/escalate',      ['App\Controllers\Api\TicketsController', 'escalate']);
        $r->post('/api/v1/tickets/{id}/assign',        ['App\Controllers\Api\TicketsController', 'assign']);

        // Companies
        $r->get('/api/v1/companies',                   ['App\Controllers\Api\CompaniesController', 'index']);
        $r->post('/api/v1/companies',                  ['App\Controllers\Api\CompaniesController', 'create']);
        $r->get('/api/v1/companies/{id}',               ['App\Controllers\Api\CompaniesController', 'show']);
        $r->patch('/api/v1/companies/{id}',             ['App\Controllers\Api\CompaniesController', 'update']);
        $r->post('/api/v1/companies/{id}',              ['App\Controllers\Api\CompaniesController', 'update']);
        $r->delete('/api/v1/companies/{id}',            ['App\Controllers\Api\CompaniesController', 'delete']);

        // Categories
        $r->get('/api/v1/categories',                   ['App\Controllers\Api\CategoriesController', 'index']);
        $r->post('/api/v1/categories',                  ['App\Controllers\Api\CategoriesController', 'create']);
        $r->get('/api/v1/categories/{id}',               ['App\Controllers\Api\CategoriesController', 'show']);
        $r->patch('/api/v1/categories/{id}',             ['App\Controllers\Api\CategoriesController', 'update']);
        $r->post('/api/v1/categories/{id}',              ['App\Controllers\Api\CategoriesController', 'update']);
        $r->delete('/api/v1/categories/{id}',            ['App\Controllers\Api\CategoriesController', 'delete']);

        // Users
        $r->get('/api/v1/users',                        ['App\Controllers\Api\UsersController', 'index']);
        $r->post('/api/v1/users',                       ['App\Controllers\Api\UsersController', 'create']);
        $r->get('/api/v1/users/{id}',                    ['App\Controllers\Api\UsersController', 'show']);
        $r->patch('/api/v1/users/{id}',                  ['App\Controllers\Api\UsersController', 'update']);
        $r->post('/api/v1/users/{id}',                   ['App\Controllers\Api\UsersController', 'update']);
        $r->delete('/api/v1/users/{id}',                 ['App\Controllers\Api\UsersController', 'delete']);

        // KB
        $r->get('/api/v1/kb/articles',                  ['App\Controllers\Api\KbController', 'index']);
        $r->post('/api/v1/kb/articles',                 ['App\Controllers\Api\KbController', 'create']);
        $r->get('/api/v1/kb/articles/{id}',              ['App\Controllers\Api\KbController', 'show']);
        $r->patch('/api/v1/kb/articles/{id}',            ['App\Controllers\Api\KbController', 'update']);
        $r->post('/api/v1/kb/articles/{id}',             ['App\Controllers\Api\KbController', 'update']);
        $r->delete('/api/v1/kb/articles/{id}',           ['App\Controllers\Api\KbController', 'delete']);
        $r->get('/api/v1/kb/categories',                ['App\Controllers\Api\KbController', 'categoriesIndex']);

        // SLA
        $r->get('/api/v1/sla',                          ['App\Controllers\Api\SlaController', 'index']);
        $r->post('/api/v1/sla',                         ['App\Controllers\Api\SlaController', 'create']);
        $r->get('/api/v1/sla/{id}',                      ['App\Controllers\Api\SlaController', 'show']);
        $r->patch('/api/v1/sla/{id}',                    ['App\Controllers\Api\SlaController', 'update']);
        $r->post('/api/v1/sla/{id}',                     ['App\Controllers\Api\SlaController', 'update']);
        $r->delete('/api/v1/sla/{id}',                   ['App\Controllers\Api\SlaController', 'delete']);

        // Automations
        $r->get('/api/v1/automations',                  ['App\Controllers\Api\AutomationsController', 'index']);
        $r->post('/api/v1/automations',                 ['App\Controllers\Api\AutomationsController', 'create']);
        $r->get('/api/v1/automations/{id}',              ['App\Controllers\Api\AutomationsController', 'show']);
        $r->patch('/api/v1/automations/{id}',            ['App\Controllers\Api\AutomationsController', 'update']);
        $r->post('/api/v1/automations/{id}',             ['App\Controllers\Api\AutomationsController', 'update']);
        $r->delete('/api/v1/automations/{id}',           ['App\Controllers\Api\AutomationsController', 'delete']);

        // Assets
        $r->get('/api/v1/assets',                       ['App\Controllers\Api\AssetsController', 'index']);
        $r->post('/api/v1/assets',                      ['App\Controllers\Api\AssetsController', 'create']);
        $r->get('/api/v1/assets/{id}',                   ['App\Controllers\Api\AssetsController', 'show']);
        $r->patch('/api/v1/assets/{id}',                 ['App\Controllers\Api\AssetsController', 'update']);
        $r->post('/api/v1/assets/{id}',                  ['App\Controllers\Api\AssetsController', 'update']);
        $r->delete('/api/v1/assets/{id}',                ['App\Controllers\Api\AssetsController', 'delete']);

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
        $r->get('/admin/forgot', ['App\Controllers\Admin\AuthController', 'showForgot']);
        $r->post('/admin/forgot', ['App\Controllers\Admin\AuthController', 'forgot']);
        $r->get('/admin/reset/{token}', ['App\Controllers\Admin\AuthController', 'showReset']);
        $r->post('/admin/reset/{token}', ['App\Controllers\Admin\AuthController', 'reset']);
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
        $r->get('/admin/tenants/{id}/modules', ['App\Controllers\Admin\TenantController', 'modules']);
        $r->post('/admin/tenants/{id}/modules', ['App\Controllers\Admin\TenantController', 'modulesUpdate']);

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

        // AI Assistant (super admin: configuración global + asignación por tenant)
        $r->get('/admin/ai', ['App\Controllers\Admin\AdminAiController', 'index']);
        $r->post('/admin/ai/settings', ['App\Controllers\Admin\AdminAiController', 'settingsUpdate']);
        $r->post('/admin/ai/tenants/{id}/assign', ['App\Controllers\Admin\AdminAiController', 'assign']);
        $r->post('/admin/ai/tenants/{id}/unassign', ['App\Controllers\Admin\AdminAiController', 'unassign']);
        $r->post('/admin/ai/tenants/{id}/update', ['App\Controllers\Admin\AdminAiController', 'tenantUpdate']);

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
        $r->get('/admin/integration-limits', ['App\Controllers\Admin\IntegrationLimitsController', 'index']);
        $r->post('/admin/integration-limits', ['App\Controllers\Admin\IntegrationLimitsController', 'update']);

        // Support
        $r->get('/admin/support', ['App\Controllers\Admin\SupportController', 'index']);
        $r->post('/admin/support/{id}', ['App\Controllers\Admin\SupportController', 'update']);

        // ─────────── SUPER ADMIN: Developer management ───────────
        // Developers
        $r->get('/admin/developers', ['App\Controllers\Admin\DeveloperController', 'index']);
        $r->get('/admin/developers/create', ['App\Controllers\Admin\DeveloperController', 'create']);
        $r->post('/admin/developers', ['App\Controllers\Admin\DeveloperController', 'store']);
        $r->get('/admin/developers/{id}', ['App\Controllers\Admin\DeveloperController', 'show']);
        $r->post('/admin/developers/{id}', ['App\Controllers\Admin\DeveloperController', 'update']);
        $r->post('/admin/developers/{id}/suspend', ['App\Controllers\Admin\DeveloperController', 'suspend']);
        $r->post('/admin/developers/{id}/activate', ['App\Controllers\Admin\DeveloperController', 'activate']);
        $r->post('/admin/developers/{id}/delete', ['App\Controllers\Admin\DeveloperController', 'delete']);
        $r->post('/admin/developers/{id}/plan', ['App\Controllers\Admin\DeveloperController', 'changePlan']);
        $r->post('/admin/developers/{id}/overrides', ['App\Controllers\Admin\DeveloperController', 'overrides']);

        // Dev Tokens (cross-developer)
        $r->get('/admin/dev-tokens', ['App\Controllers\Admin\DevTokenController', 'index']);
        $r->post('/admin/dev-tokens/{id}/revoke', ['App\Controllers\Admin\DevTokenController', 'revoke']);

        // Dev portal settings
        $r->get('/admin/dev-settings', ['App\Controllers\Admin\DevSettingsController', 'index']);
        $r->post('/admin/dev-settings', ['App\Controllers\Admin\DevSettingsController', 'update']);

        // Payment proofs review + bank settings
        $r->get('/admin/payment-proofs', ['App\Controllers\Admin\PaymentProofController', 'index']);
        $r->get('/admin/payment-proofs/{id}', ['App\Controllers\Admin\PaymentProofController', 'show']);
        $r->get('/admin/payment-proofs/{id}/file', ['App\Controllers\Admin\PaymentProofController', 'downloadFile']);
        $r->post('/admin/payment-proofs/{id}/approve', ['App\Controllers\Admin\PaymentProofController', 'approve']);
        $r->post('/admin/payment-proofs/{id}/reject', ['App\Controllers\Admin\PaymentProofController', 'reject']);
        $r->get('/admin/bank-settings', ['App\Controllers\Admin\PaymentProofController', 'bankSettings']);
        $r->post('/admin/bank-settings', ['App\Controllers\Admin\PaymentProofController', 'updateBankSettings']);

        // Dev audit logs
        $r->get('/admin/dev-audit', ['App\Controllers\Admin\DevAuditController', 'index']);
        $r->get('/admin/dev-audit/requests', ['App\Controllers\Admin\DevAuditController', 'requestLog']);
        $r->get('/admin/dev-audit/webhooks', ['App\Controllers\Admin\DevAuditController', 'webhookDeliveries']);

        // Developer Plans
        $r->get('/admin/dev-plans', ['App\Controllers\Admin\DevPlanController', 'index']);
        $r->get('/admin/dev-plans/create', ['App\Controllers\Admin\DevPlanController', 'create']);
        $r->post('/admin/dev-plans', ['App\Controllers\Admin\DevPlanController', 'store']);
        $r->get('/admin/dev-plans/{id}', ['App\Controllers\Admin\DevPlanController', 'edit']);
        $r->post('/admin/dev-plans/{id}', ['App\Controllers\Admin\DevPlanController', 'update']);
        $r->post('/admin/dev-plans/{id}/toggle', ['App\Controllers\Admin\DevPlanController', 'toggle']);
        $r->post('/admin/dev-plans/{id}/delete', ['App\Controllers\Admin\DevPlanController', 'delete']);

        // Developer Subscriptions
        $r->get('/admin/dev-subscriptions', ['App\Controllers\Admin\DevSubscriptionController', 'index']);
        $r->post('/admin/dev-subscriptions/{id}', ['App\Controllers\Admin\DevSubscriptionController', 'update']);
        $r->post('/admin/dev-subscriptions/{id}/cancel', ['App\Controllers\Admin\DevSubscriptionController', 'cancel']);

        // Developer Apps
        $r->get('/admin/dev-apps', ['App\Controllers\Admin\DevAppController', 'index']);
        $r->post('/admin/dev-apps/{id}/suspend', ['App\Controllers\Admin\DevAppController', 'suspend']);
        $r->post('/admin/dev-apps/{id}/activate', ['App\Controllers\Admin\DevAppController', 'activate']);
        $r->post('/admin/dev-apps/{id}/delete', ['App\Controllers\Admin\DevAppController', 'delete']);

        // Developer Invoices
        $r->get('/admin/dev-invoices', ['App\Controllers\Admin\DevInvoiceController', 'index']);
        $r->get('/admin/dev-invoices/create', ['App\Controllers\Admin\DevInvoiceController', 'create']);
        $r->post('/admin/dev-invoices', ['App\Controllers\Admin\DevInvoiceController', 'store']);
        $r->get('/admin/dev-invoices/{id}', ['App\Controllers\Admin\DevInvoiceController', 'show']);
        $r->post('/admin/dev-invoices/{id}/pay', ['App\Controllers\Admin\DevInvoiceController', 'markPaid']);
        $r->post('/admin/dev-invoices/{id}/delete', ['App\Controllers\Admin\DevInvoiceController', 'delete']);

        // Developer Payments
        $r->get('/admin/dev-payments', ['App\Controllers\Admin\DevPaymentController', 'index']);
        $r->post('/admin/dev-payments', ['App\Controllers\Admin\DevPaymentController', 'store']);

        // ─────────── PORTAL DE DEVELOPERS (público + autenticado) ───────────
        // Landing pública
        $r->get('/developers', ['App\Controllers\Developer\LandingController', 'index']);
        $r->get('/developers/pricing', ['App\Controllers\Developer\LandingController', 'pricing']);
        $r->get('/developers/docs', ['App\Controllers\Developer\LandingController', 'docs']);

        // Auth
        $r->get('/developers/login', ['App\Controllers\Developer\AuthController', 'showLogin']);
        $r->post('/developers/login', ['App\Controllers\Developer\AuthController', 'login']);
        $r->get('/developers/register', ['App\Controllers\Developer\AuthController', 'showRegister']);
        $r->post('/developers/register', ['App\Controllers\Developer\AuthController', 'register']);
        $r->post('/developers/logout', ['App\Controllers\Developer\AuthController', 'logout']);
        $r->get('/developers/logout', ['App\Controllers\Developer\AuthController', 'logout']);
        $r->get('/developers/forgot', ['App\Controllers\Developer\AuthController', 'showForgot']);
        $r->post('/developers/forgot', ['App\Controllers\Developer\AuthController', 'forgot']);
        $r->get('/developers/reset/{token}', ['App\Controllers\Developer\AuthController', 'showReset']);
        $r->post('/developers/reset/{token}', ['App\Controllers\Developer\AuthController', 'reset']);
        $r->get('/developers/verify/{token}', ['App\Controllers\Developer\AuthController', 'verifyEmail']);
        $r->post('/developers/resend-verification', ['App\Controllers\Developer\AuthController', 'resendVerification']);

        // Dashboard
        $r->get('/developers/dashboard', ['App\Controllers\Developer\DashboardController', 'index']);

        // Apps
        $r->get('/developers/apps', ['App\Controllers\Developer\AppsController', 'index']);
        $r->get('/developers/apps/create', ['App\Controllers\Developer\AppsController', 'create']);
        $r->post('/developers/apps', ['App\Controllers\Developer\AppsController', 'store']);
        $r->get('/developers/apps/{id}', ['App\Controllers\Developer\AppsController', 'show']);
        $r->post('/developers/apps/{id}/update', ['App\Controllers\Developer\AppsController', 'update']);
        $r->post('/developers/apps/{id}/delete', ['App\Controllers\Developer\AppsController', 'delete']);
        $r->post('/developers/apps/{id}/tokens', ['App\Controllers\Developer\AppsController', 'tokenCreate']);
        $r->post('/developers/apps/{id}/tokens/{tokenId}/revoke', ['App\Controllers\Developer\AppsController', 'tokenRevoke']);

        // Billing
        $r->get('/developers/billing', ['App\Controllers\Developer\BillingController', 'index']);
        $r->get('/developers/billing/plans', ['App\Controllers\Developer\BillingController', 'plans']);
        $r->get('/developers/billing/checkout/{id}', ['App\Controllers\Developer\BillingController', 'checkout']);
        $r->post('/developers/billing/subscribe/{id}', ['App\Controllers\Developer\BillingController', 'subscribe']);
        $r->post('/developers/billing/cancel', ['App\Controllers\Developer\BillingController', 'cancel']);
        $r->get('/developers/billing/invoices/{id}', ['App\Controllers\Developer\BillingController', 'invoiceShow']);
        $r->get('/developers/billing/payment-info', ['App\Controllers\Developer\PaymentProofController', 'paymentInfo']);
        $r->post('/developers/billing/payment-proof', ['App\Controllers\Developer\PaymentProofController', 'uploadProof']);

        // Usage
        $r->get('/developers/usage', ['App\Controllers\Developer\UsageController', 'index']);

        // Profile
        $r->get('/developers/profile', ['App\Controllers\Developer\ProfileController', 'index']);
        $r->post('/developers/profile', ['App\Controllers\Developer\ProfileController', 'update']);

        // AI Studio
        $r->get('/developers/ai', ['App\Controllers\Developer\AiStudioController', 'index']);
        $r->get('/developers/ai/chat', ['App\Controllers\Developer\AiStudioController', 'chat']);
        $r->get('/developers/ai/digest', ['App\Controllers\Developer\AiStudioController', 'digest']);
        $r->get('/developers/ai/system-prompt', ['App\Controllers\Developer\AiStudioController', 'systemPrompt']);
        $r->get('/developers/ai/cursorrules', ['App\Controllers\Developer\AiStudioController', 'cursorRules']);
        $r->get('/developers/ai/mcp', ['App\Controllers\Developer\AiStudioController', 'mcpConfig']);

        // API Console (try-it)
        $r->get('/developers/console', ['App\Controllers\Developer\ConsoleController', 'index']);
        $r->post('/developers/console/save', ['App\Controllers\Developer\ConsoleController', 'save']);
        $r->post('/developers/console/saved/{id}/delete', ['App\Controllers\Developer\ConsoleController', 'deleteSaved']);

        // Webhooks
        $r->get('/developers/webhooks', ['App\Controllers\Developer\WebhooksController', 'index']);
        $r->post('/developers/webhooks', ['App\Controllers\Developer\WebhooksController', 'create']);
        $r->post('/developers/webhooks/{id}/toggle', ['App\Controllers\Developer\WebhooksController', 'toggle']);
        $r->post('/developers/webhooks/{id}/delete', ['App\Controllers\Developer\WebhooksController', 'delete']);
        $r->post('/developers/webhooks/{id}/rotate-secret', ['App\Controllers\Developer\WebhooksController', 'rotateSecret']);
        $r->post('/developers/webhooks/{id}/test', ['App\Controllers\Developer\WebhooksController', 'test']);
    }
}
