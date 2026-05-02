<?php
namespace App\Core;

class Plan
{
    public const FEATURES = [
        'starter'    => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','custom_fields','csat','status_page','customer_portal'],
        'free'       => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','custom_fields','csat','status_page','customer_portal'],
        'pro'        => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations','custom_fields','csat','status_page','customer_portal','email_inbound','time_tracking'],
        'business'   => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations','retainers','custom_fields','csat','status_page','customer_portal','email_inbound','time_tracking','live_chat','reports_builder','itsm','meetings','crm','quotes'],
        'enterprise' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations','retainers','custom_fields','csat','status_page','customer_portal','email_inbound','time_tracking','live_chat','ai_assist','reports_builder','itsm','sso','custom_branding','meetings','crm','quotes'],
    ];

    /** Catálogo de módulos que el super admin puede gestionar por tenant. */
    public const MODULE_CATALOG = [
        'tickets'          => ['Tickets',           'inbox',         'Operación de tickets, kanban y flujos.', 'core'],
        'kb'              => ['Conocimiento',    'book-open',     'Base de conocimiento interna y portal.', 'core'],
        'notes'           => ['Notas',           'notebook-pen',  'Notas internas del equipo.',             'core'],
        'todos'           => ['Tareas',          'check-square',  'Listas de tareas / pendientes.',         'core'],
        'companies'       => ['Empresas',        'building-2',    'Cuentas y empresas cliente.',            'core'],
        'assets'          => ['Activos',         'server',        'Inventario de activos asignados.',       'core'],
        'reports'         => ['Reportes',        'line-chart',    'Reportes operativos y KPIs.',            'core'],
        'users'           => ['Usuarios',        'users',         'Gestión de usuarios del workspace.',     'core'],
        'roles'           => ['Roles',           'shield',        'Roles y permisos.',                      'core'],
        'settings'        => ['Ajustes',         'settings',      'Configuración del workspace.',           'core'],
        'departments'     => ['Departamentos',   'layers',        'Áreas con sus agentes y SLA.',           'pro'],
        'automations'     => ['Automatizaciones','workflow',      'Reglas que ejecutan acciones solas.',    'pro'],
        'integrations'    => ['Integraciones',   'plug',          'Slack, Telegram, webhooks y más.',       'pro'],
        'sla'             => ['SLA',             'gauge',         'Políticas de respuesta y resolución.',   'pro'],
        'audit'           => ['Auditoría',       'history',       'Bitácora completa de eventos.',          'pro'],
        'retainers'        => ['Igualas',           'handshake',       'Contratos recurrentes con empresas y clientes individuales.', 'business'],
        'custom_fields'    => ['Custom Fields',     'list-plus',       'Campos personalizados por categoría de ticket.', 'core'],
        'csat'             => ['CSAT / NPS',        'smile',           'Encuestas de satisfacción post-resolución.', 'core'],
        'status_page'      => ['Status Page',       'activity',        'Página pública de estado con incidentes y suscriptores.', 'core'],
        'customer_portal'  => ['Portal Clientes',   'lock-keyhole',    'Portal autenticado con histórico para clientes.', 'core'],
        'email_inbound'    => ['Email-to-Ticket',   'mail-open',       'Recibir emails y convertirlos en tickets (IMAP / forward).', 'pro'],
        'time_tracking'    => ['Time Tracking',     'timer',           'Cronómetro por ticket integrado a Igualas.', 'pro'],
        'live_chat'        => ['Live Chat',         'message-square',  'Widget de chat en vivo embebible para sitios.', 'business'],
        'ai_assist'        => ['IA Asistente',      'sparkles',        'Sugerir respuesta, resumir, clasificar, sentiment, traducir. Gestionada por Kydesk.', 'enterprise'],
        'itsm'             => ['ITSM',              'workflow',        'Service Catalog, Change Management, Problems, Approvals.', 'business'],
        'reports_builder'  => ['Reports Builder',   'bar-chart-3',     'Constructor visual de reportes con widgets y filtros.', 'business'],
        'sso'              => ['SSO + SAML',        'key-round',       'Inicio de sesión único corporativo.',    'enterprise'],
        'custom_branding'  => ['Branding propio',   'palette',         'Logos, colores y dominios personalizados.', 'enterprise'],
        'meetings'         => ['Agenda de reuniones','calendar-clock', 'Página pública estilo Calendly para que clientes reserven citas con tu equipo.', 'business'],
        'crm'              => ['CRM Leads / Clientes','contact-round',  'Gestión avanzada de leads, pipelines, oportunidades, actividades y conversión de clientes.', 'business'],
        'quotes'           => ['Cotizaciones',          'file-text',     'Generador profesional de cotizaciones con plantillas, ITBIS configurable, descuentos y exportación PDF con branding propio.', 'business'],
    ];

    public const PLAN_RANK = ['starter'=>1,'free'=>1,'pro'=>2,'business'=>2,'enterprise'=>3];

    public const LABELS = ['starter'=>'Starter','free'=>'Starter','pro'=>'Pro','business'=>'Business','enterprise'=>'Enterprise'];

    /** Cache en proceso de overrides por tenant_id. */
    protected static array $overrideCache = [];

    public static function tenantPlan(?Tenant $tenant): string
    {
        if (!$tenant) return 'starter';
        $demoPlan = $tenant->data['demo_plan'] ?? null;
        if ($demoPlan && isset(self::FEATURES[$demoPlan])) return $demoPlan;

        // La suscripción es la fuente de verdad. Si no es usable, degrada a starter.
        try {
            $status = License::status($tenant);
            if (!$status['is_usable']) return 'starter';
            $licPlan = $status['plan_slug'] ?? null;
            if ($licPlan && isset(self::FEATURES[$licPlan])) return $licPlan;
        } catch (\Throwable $e) { /* tabla suscripciones opcional */ }

        $plan = $tenant->data['plan'] ?? 'starter';
        return isset(self::FEATURES[$plan]) ? $plan : 'starter';
    }

    /**
     * Devuelve los overrides activos del tenant: feature => 'on' | 'off'.
     * Tabla opcional — si no existe, se asume sin overrides.
     */
    public static function tenantOverrides(?Tenant $tenant): array
    {
        if (!$tenant) return [];
        $tid = $tenant->id;
        if (array_key_exists($tid, self::$overrideCache)) return self::$overrideCache[$tid];
        try {
            $rows = Application::get()->db->all(
                'SELECT feature, state FROM tenant_module_overrides WHERE tenant_id = ?',
                [$tid]
            );
            $map = [];
            foreach ($rows as $r) $map[$r['feature']] = $r['state'];
            return self::$overrideCache[$tid] = $map;
        } catch (\Throwable $e) {
            return self::$overrideCache[$tid] = [];
        }
    }

    public static function clearOverrideCache(?int $tenantId = null): void
    {
        if ($tenantId === null) self::$overrideCache = [];
        else unset(self::$overrideCache[$tenantId]);
    }

    /**
     * Conjunto efectivo de features habilitadas para el tenant.
     * Combina plan + overrides del super admin.
     */
    public static function effectiveFeatures(?Tenant $tenant): array
    {
        $plan = self::tenantPlan($tenant);
        $base = self::FEATURES[$plan] ?? [];
        $set = array_fill_keys($base, true);
        foreach (self::tenantOverrides($tenant) as $feature => $state) {
            if ($state === 'on')  $set[$feature] = true;
            if ($state === 'off') unset($set[$feature]);
        }
        return array_keys($set);
    }

    public static function has(?Tenant $tenant, string $feature): bool
    {
        $overrides = self::tenantOverrides($tenant);
        if (isset($overrides[$feature])) {
            return $overrides[$feature] === 'on';
        }
        $plan = self::tenantPlan($tenant);
        return in_array($feature, self::FEATURES[$plan] ?? [], true);
    }

    public static function label(?Tenant $tenant): string
    {
        return self::LABELS[self::tenantPlan($tenant)] ?? 'Plan';
    }

    public static function requiredPlanFor(string $feature): string
    {
        foreach (['starter','pro','business','enterprise'] as $p) {
            if (in_array($feature, self::FEATURES[$p] ?? [], true)) return $p;
        }
        return 'enterprise';
    }

    public const LIMITS = [
        'starter'    => ['users'=>3,    'tickets_per_month'=>100,    'kb_articles'=>10,   'channels'=>['portal','email']],
        'free'       => ['users'=>3,    'tickets_per_month'=>100,    'kb_articles'=>10,   'channels'=>['portal','email']],
        'pro'        => ['users'=>999,  'tickets_per_month'=>99999,  'kb_articles'=>999,  'channels'=>['portal','email','phone','chat','internal']],
        'business'   => ['users'=>999,  'tickets_per_month'=>99999,  'kb_articles'=>999,  'channels'=>['portal','email','phone','chat','internal']],
        'enterprise' => ['users'=>9999, 'tickets_per_month'=>999999, 'kb_articles'=>9999, 'channels'=>['portal','email','phone','chat','internal']],
    ];

    public static function limit(?Tenant $tenant, string $key)
    {
        $plan = self::tenantPlan($tenant);
        return self::LIMITS[$plan][$key] ?? null;
    }

    public static function channelAllowed(?Tenant $tenant, string $channel): bool
    {
        $plan = self::tenantPlan($tenant);
        $allowed = self::LIMITS[$plan]['channels'] ?? [];
        return in_array($channel, $allowed, true);
    }

    public static function checkLimit(?Tenant $tenant, string $key, int $current): array
    {
        $max = self::limit($tenant, $key);
        if (!is_int($max)) return ['ok' => true, 'max' => null, 'current' => $current];
        return ['ok' => $current < $max, 'max' => $max, 'current' => $current];
    }
}
