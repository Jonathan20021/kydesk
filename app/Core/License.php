<?php
namespace App\Core;

class License
{
    public const STATE_TRIAL     = 'trial';
    public const STATE_ACTIVE    = 'active';
    public const STATE_PAST_DUE  = 'past_due';
    public const STATE_EXPIRED   = 'expired';
    public const STATE_SUSPENDED = 'suspended';
    public const STATE_CANCELLED = 'cancelled';
    public const STATE_NONE      = 'none';

    public const USABLE_STATES = [self::STATE_TRIAL, self::STATE_ACTIVE, self::STATE_PAST_DUE];

    public static function fetchSubscription(int $tenantId): ?array
    {
        $app = Application::get();
        try {
            return $app->db->one(
                "SELECT s.*, p.slug AS plan_slug, p.name AS plan_name, p.color AS plan_color
                 FROM subscriptions s
                 JOIN plans p ON p.id = s.plan_id
                 WHERE s.tenant_id = ?
                 ORDER BY s.id DESC LIMIT 1",
                [$tenantId]
            ) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function status(?Tenant $tenant): array
    {
        $blank = [
            'state'        => self::STATE_NONE,
            'subscription' => null,
            'plan_slug'    => 'starter',
            'plan_name'    => 'Sin plan',
            'days_left'    => null,
            'hours_left'   => null,
            'seconds_left' => null,
            'trial_ends_at'=> null,
            'period_end'   => null,
            'is_trial'     => false,
            // Tenants legacy sin suscripción se consideran usables hasta que el
            // super admin asigne una. La activación pasa por activar/extender.
            'is_usable'    => true,
            'is_expired'   => false,
            'is_grace'     => false,
            'raw_status'   => self::STATE_NONE,
            'message'      => 'Esta organización aún no tiene una suscripción registrada.',
        ];
        if (!$tenant) return $blank;

        // Demos manejan su propio ciclo (demo_expires_at) — siempre usables
        if ((int)($tenant->data['is_demo'] ?? 0) === 1) {
            return [
                'state' => 'demo', 'subscription' => null,
                'plan_slug' => $tenant->data['demo_plan'] ?? 'pro',
                'plan_name' => 'Demo',
                'days_left' => null, 'hours_left' => null, 'seconds_left' => null,
                'trial_ends_at' => $tenant->data['demo_expires_at'] ?? null, 'period_end' => null,
                'is_trial' => false, 'is_usable' => true, 'is_expired' => false, 'is_grace' => false,
                'raw_status' => 'demo',
                'message' => 'Workspace demo.',
            ];
        }

        if ((int)($tenant->data['is_active'] ?? 1) === 0 || !empty($tenant->data['suspended_at'])) {
            $blank['state']     = self::STATE_SUSPENDED;
            $blank['raw_status']= self::STATE_SUSPENDED;
            $blank['is_usable'] = false;
            $blank['is_expired']= true;
            $blank['message']   = $tenant->data['suspended_reason']
                ? 'Cuenta suspendida: ' . $tenant->data['suspended_reason']
                : 'Cuenta suspendida por el administrador.';
            return $blank;
        }

        $sub = self::fetchSubscription($tenant->id);
        if (!$sub) return $blank;

        $now = time();
        $trialEnds  = $sub['trial_ends_at'] ? strtotime($sub['trial_ends_at']) : null;
        $periodEnd  = $sub['current_period_end'] ? strtotime($sub['current_period_end']) : null;
        $rawStatus  = (string)$sub['status'];

        $state = $rawStatus;
        $isTrial = $rawStatus === self::STATE_TRIAL;
        $secondsLeft = null;
        $isExpired = false;

        if ($isTrial) {
            if ($trialEnds !== null) {
                $secondsLeft = $trialEnds - $now;
                if ($secondsLeft <= 0) { $state = self::STATE_EXPIRED; $isExpired = true; }
            }
        } elseif ($rawStatus === self::STATE_ACTIVE) {
            if ($periodEnd !== null) {
                $secondsLeft = $periodEnd - $now;
                if ($secondsLeft < 0 && abs($secondsLeft) > 86400 * 3) {
                    $state = self::STATE_EXPIRED; $isExpired = true;
                }
            }
        } elseif (in_array($rawStatus, [self::STATE_CANCELLED, self::STATE_EXPIRED, self::STATE_SUSPENDED], true)) {
            $isExpired = true;
        }

        $hoursLeft = $secondsLeft !== null ? (int)floor($secondsLeft / 3600) : null;
        $daysLeft  = $secondsLeft !== null ? (int)floor($secondsLeft / 86400) : null;

        $isUsable = !$isExpired && in_array($state, self::USABLE_STATES, true);
        $isGrace  = $rawStatus === self::STATE_PAST_DUE;

        $message = match (true) {
            $state === self::STATE_EXPIRED && $isTrial => 'Tu período de prueba expiró. Activa tu licencia para continuar.',
            $state === self::STATE_EXPIRED             => 'Tu suscripción expiró. Renueva la licencia para continuar.',
            $state === self::STATE_CANCELLED           => 'Esta suscripción fue cancelada por el administrador.',
            $state === self::STATE_SUSPENDED           => 'Suscripción suspendida.',
            $state === self::STATE_PAST_DUE            => 'Pago vencido. Regulariza para evitar suspensión.',
            $state === self::STATE_TRIAL               => 'Estás en período de prueba.',
            $state === self::STATE_ACTIVE              => 'Licencia activa.',
            default                                    => 'Estado de licencia desconocido.',
        };

        return [
            'state'         => $state,
            'subscription'  => $sub,
            'plan_slug'     => $sub['plan_slug'] ?? 'starter',
            'plan_name'     => $sub['plan_name'] ?? 'Plan',
            'days_left'     => $daysLeft,
            'hours_left'    => $hoursLeft,
            'seconds_left'  => $secondsLeft,
            'trial_ends_at' => $sub['trial_ends_at'] ?? null,
            'period_end'    => $sub['current_period_end'] ?? null,
            'is_trial'      => $isTrial,
            'is_usable'     => $isUsable,
            'is_expired'    => $isExpired,
            'is_grace'      => $isGrace,
            'raw_status'    => $rawStatus,
            'message'       => $message,
        ];
    }

    public static function isUsable(?Tenant $tenant): bool
    {
        return self::status($tenant)['is_usable'];
    }

    public static function settingInt(string $key, int $default = 0): int
    {
        $app = Application::get();
        $v = $app->db->val('SELECT `value` FROM saas_settings WHERE `key` = ?', [$key]);
        if ($v === null || $v === '') return $default;
        return (int)$v;
    }

    public static function settingStr(string $key, string $default = ''): string
    {
        $app = Application::get();
        $v = $app->db->val('SELECT `value` FROM saas_settings WHERE `key` = ?', [$key]);
        return $v === null ? $default : (string)$v;
    }

    public static function defaultTrialDays(): int
    {
        return max(0, self::settingInt('saas_default_trial_days', 14));
    }

    public static function defaultPlan(): array
    {
        $app = Application::get();
        $slug = self::settingStr('saas_default_plan', 'starter');
        $plan = $app->db->one('SELECT * FROM plans WHERE slug = ? AND is_active = 1 LIMIT 1', [$slug]);
        if (!$plan) {
            $plan = $app->db->one('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1');
        }
        return $plan ?: ['id' => 0, 'slug' => 'starter', 'name' => 'Starter', 'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'USD', 'trial_days' => 14];
    }

    public static function startTrialFor(int $tenantId, ?array $plan = null, ?int $trialDays = null): int
    {
        $app = Application::get();
        $plan = $plan ?: self::defaultPlan();
        $days = $trialDays ?? max((int)($plan['trial_days'] ?? 0), self::defaultTrialDays());
        $now = date('Y-m-d H:i:s');
        $trialEnds = $days > 0 ? date('Y-m-d H:i:s', strtotime("+{$days} days")) : null;

        $subscriptionId = $app->db->insert('subscriptions', [
            'tenant_id'             => $tenantId,
            'plan_id'               => (int)$plan['id'],
            'status'                => self::STATE_TRIAL,
            'billing_cycle'         => 'monthly',
            'amount'                => 0,
            'currency'              => $plan['currency'] ?? 'USD',
            'started_at'            => $now,
            'trial_ends_at'         => $trialEnds,
            'current_period_start'  => $now,
            'current_period_end'    => $trialEnds,
            'auto_renew'            => 0,
        ]);

        try {
            $app->db->update('tenants', ['subscription_id' => $subscriptionId], 'id = :id', ['id' => $tenantId]);
        } catch (\Throwable $e) { /* subscription_id column optional */ }
        if (!empty($plan['slug'])) {
            try {
                $app->db->update('tenants', ['plan' => $plan['slug']], 'id = :id', ['id' => $tenantId]);
            } catch (\Throwable $e) { /* legacy ENUM may not accept slug — license is source of truth */ }
        }

        return (int)$subscriptionId;
    }

    public static function activate(int $subscriptionId, string $cycle = 'monthly', ?float $amount = null): void
    {
        $app = Application::get();
        $sub = $app->db->one('SELECT s.*, p.price_monthly, p.price_yearly FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.id = ?', [$subscriptionId]);
        if (!$sub) return;
        $cycle = in_array($cycle, ['monthly','yearly','lifetime'], true) ? $cycle : 'monthly';
        $now = date('Y-m-d H:i:s');
        $end = match ($cycle) {
            'yearly'   => date('Y-m-d H:i:s', strtotime('+1 year')),
            'lifetime' => date('Y-m-d H:i:s', strtotime('+10 years')),
            default    => date('Y-m-d H:i:s', strtotime('+1 month')),
        };
        if ($amount === null) {
            $amount = $cycle === 'yearly' ? (float)$sub['price_yearly'] : (float)$sub['price_monthly'];
        }
        $app->db->update('subscriptions', [
            'status'               => self::STATE_ACTIVE,
            'billing_cycle'        => $cycle,
            'amount'               => $amount,
            'started_at'           => $sub['started_at'] ?: $now,
            'current_period_start' => $now,
            'current_period_end'   => $end,
            'trial_ends_at'        => null,
            'cancelled_at'         => null,
            'auto_renew'           => 1,
        ], 'id = :id', ['id' => $subscriptionId]);
    }

    public static function extendTrial(int $subscriptionId, int $days): void
    {
        if ($days <= 0) return;
        $app = Application::get();
        $sub = $app->db->one('SELECT * FROM subscriptions WHERE id = ?', [$subscriptionId]);
        if (!$sub) return;
        $base = $sub['trial_ends_at'] ? strtotime($sub['trial_ends_at']) : time();
        if ($base < time()) $base = time();
        $newEnds = date('Y-m-d H:i:s', $base + ($days * 86400));
        $app->db->update('subscriptions', [
            'status'             => self::STATE_TRIAL,
            'trial_ends_at'      => $newEnds,
            'current_period_end' => $newEnds,
        ], 'id = :id', ['id' => $subscriptionId]);
    }

    public static function expire(int $subscriptionId): void
    {
        $app = Application::get();
        $app->db->update('subscriptions', [
            'status' => self::STATE_EXPIRED,
        ], 'id = :id', ['id' => $subscriptionId]);
    }
}
