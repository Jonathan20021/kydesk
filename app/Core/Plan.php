<?php
namespace App\Core;

class Plan
{
    public const FEATURES = [
        'starter'    => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings'],
        'free'       => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings'],
        'pro'        => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations'],
        'business'   => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations','retainers'],
        'enterprise' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations','retainers','sso','custom_branding'],
    ];

    /** Catálogo de módulos que el super admin puede gestionar por tenant. */
    public const MODULE_CATALOG = [
        'tickets'         => ['Tickets',         'inbox',         'Operación de tickets, kanban y flujos.', 'core'],
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
        'retainers'       => ['Igualas',         'handshake',     'Contratos recurrentes con empresas y clientes individuales.', 'business'],
        'sso'             => ['SSO + SAML',      'key-round',     'Inicio de sesión único corporativo.',    'enterprise'],
        'custom_branding' => ['Branding propio', 'palette',       'Logos, colores y dominios personalizados.', 'enterprise'],
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
