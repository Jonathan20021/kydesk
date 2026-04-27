<?php
namespace App\Core;

class Plan
{
    public const FEATURES = [
        'starter'    => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings'],
        'free'       => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings'],
        'pro'        => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments'],
        'business'   => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments'],
        'enterprise' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','sso','custom_branding'],
    ];

    public const PLAN_RANK = ['starter'=>1,'free'=>1,'pro'=>2,'business'=>2,'enterprise'=>3];

    public const LABELS = ['starter'=>'Starter','free'=>'Starter','pro'=>'Pro','business'=>'Pro','enterprise'=>'Enterprise'];

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

    public static function has(?Tenant $tenant, string $feature): bool
    {
        $plan = self::tenantPlan($tenant);
        return in_array($feature, self::FEATURES[$plan] ?? [], true);
    }

    public static function label(?Tenant $tenant): string
    {
        return self::LABELS[self::tenantPlan($tenant)] ?? 'Plan';
    }

    public static function requiredPlanFor(string $feature): string
    {
        foreach (['starter','pro','enterprise'] as $p) {
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
