<div class="grid sm:grid-cols-3 lg:grid-cols-7 gap-3">
    <div class="admin-stat"><div class="admin-stat-label">Developers</div><div class="admin-stat-value text-[22px]"><?= number_format($stats['developers_total']) ?></div><div class="text-[11px] text-ink-400 mt-1"><?= $stats['developers_active'] ?> activos</div></div>
    <div class="admin-stat"><div class="admin-stat-label">Apps</div><div class="admin-stat-value text-[22px]"><?= number_format($stats['apps_total']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Tokens activos</div><div class="admin-stat-value text-[22px]"><?= number_format($stats['tokens_active']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Suscripciones</div><div class="admin-stat-value text-[22px]"><?= number_format($stats['subs_active']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Requests MTD</div><div class="admin-stat-value text-[22px]"><?= number_format($stats['mtd_requests']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Revenue MTD</div><div class="admin-stat-value text-[22px]">$<?= number_format($stats['mtd_revenue'], 2) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Estado portal</div><div class="admin-stat-value text-[22px]"><?= ($settings['dev_portal_enabled'] ?? '1') === '1' ? '<span class="admin-pill admin-pill-green">ON</span>' : '<span class="admin-pill admin-pill-red">OFF</span>' ?></div></div>
</div>

<form method="POST" action="<?= $url('/admin/dev-settings') ?>" class="grid lg:grid-cols-2 gap-5">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad space-y-4">
        <div class="flex items-center gap-2">
            <i class="lucide lucide-power text-brand-700"></i>
            <h3 class="admin-h2">Disponibilidad</h3>
        </div>
        <div class="space-y-3">
            <?php
            $toggles = [
                'dev_portal_enabled' => ['Portal habilitado', 'Si está OFF, los developers no pueden acceder.'],
                'dev_portal_allow_registration' => ['Registro abierto', 'Permite que nuevos developers se registren.'],
                'dev_portal_require_verification' => ['Requiere verificación', 'El developer debe verificar su email antes de operar.'],
                'dev_portal_overage_enabled' => ['Overage permitido', 'Permite cobrar excedente cuando se supera la cuota mensual.'],
            ];
            foreach ($toggles as $k => [$lbl, $hint]):
                $v = ($settings[$k] ?? '0') === '1';
            ?>
                <label class="flex items-start gap-3 admin-card-pad" style="border:1px solid var(--border); border-radius:12px; padding:12px 14px;">
                    <input type="checkbox" name="<?= $k ?>" value="1" <?= $v?'checked':'' ?> class="mt-0.5">
                    <div class="flex-1">
                        <div class="font-display font-bold text-[13px]"><?= $e($lbl) ?></div>
                        <div class="text-[11.5px] text-ink-400 mt-0.5"><?= $e($hint) ?></div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card admin-card-pad space-y-4">
        <div class="flex items-center gap-2">
            <i class="lucide lucide-shield-check text-brand-700"></i>
            <h3 class="admin-h2">Enforcement</h3>
        </div>
        <div class="space-y-3">
            <?php
            $sec = [
                'dev_portal_enforce_quota' => ['Aplicar cuota mensual', 'Bloquea las requests cuando se alcanza max_requests_month.'],
                'dev_portal_enforce_rate_limit' => ['Aplicar rate limit', 'Bloquea las requests cuando se supera rate_limit_per_min.'],
                'dev_portal_block_on_overage' => ['Bloquear en overage', 'Si está ON, bloquea aunque haya overage_price configurado.'],
            ];
            foreach ($sec as $k => [$lbl, $hint]):
                $v = ($settings[$k] ?? '0') === '1';
            ?>
                <label class="flex items-start gap-3 admin-card-pad" style="border:1px solid var(--border); border-radius:12px; padding:12px 14px;">
                    <input type="checkbox" name="<?= $k ?>" value="1" <?= $v?'checked':'' ?> class="mt-0.5">
                    <div class="flex-1">
                        <div class="font-display font-bold text-[13px]"><?= $e($lbl) ?></div>
                        <div class="text-[11.5px] text-ink-400 mt-0.5"><?= $e($hint) ?></div>
                    </div>
                </label>
            <?php endforeach; ?>
            <div>
                <label class="admin-label">Alerta de cuota al llegar a (%)</label>
                <input type="number" min="50" max="100" name="dev_portal_alert_at_pct" class="admin-input" value="<?= $e($settings['dev_portal_alert_at_pct'] ?? '80') ?>">
            </div>
        </div>
    </div>

    <div class="admin-card admin-card-pad space-y-4 lg:col-span-2">
        <div class="flex items-center gap-2">
            <i class="lucide lucide-text-cursor-input text-brand-700"></i>
            <h3 class="admin-h2">Branding y defaults</h3>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Nombre del portal</label>
                <input type="text" name="dev_portal_name" class="admin-input" value="<?= $e($settings['dev_portal_name'] ?? 'Kydesk Developers') ?>">
            </div>
            <div>
                <label class="admin-label">Empresa (label)</label>
                <input type="text" name="dev_portal_company_label" class="admin-input" value="<?= $e($settings['dev_portal_company_label'] ?? 'Kydesk Developers') ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label">Tagline (en la landing)</label>
                <input type="text" name="dev_portal_tagline" class="admin-input" value="<?= $e($settings['dev_portal_tagline'] ?? '') ?>">
            </div>
            <div>
                <label class="admin-label">Email de soporte</label>
                <input type="email" name="dev_portal_support_email" class="admin-input" value="<?= $e($settings['dev_portal_support_email'] ?? '') ?>">
            </div>
            <div>
                <label class="admin-label">Plan por defecto al registrarse</label>
                <input type="text" name="dev_portal_default_plan" class="admin-input" value="<?= $e($settings['dev_portal_default_plan'] ?? 'dev_free') ?>" placeholder="dev_free">
            </div>
            <div>
                <label class="admin-label">Días de trial por defecto</label>
                <input type="number" min="0" max="90" name="dev_portal_default_trial_days" class="admin-input" value="<?= $e($settings['dev_portal_default_trial_days'] ?? '14') ?>">
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 flex items-center gap-2">
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Guardar ajustes</button>
        <a href="<?= $url('/admin/developers') ?>" class="admin-btn admin-btn-soft">Volver</a>
    </div>
</form>
