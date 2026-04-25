<form method="POST" action="<?= $url('/admin/settings') ?>" class="max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Información general</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="admin-label">Nombre del SaaS</label><input name="saas_name" value="<?= $e($settings['saas_name'] ?? 'Kydesk') ?>" class="admin-input"></div>
            <div><label class="admin-label">Empresa</label><input name="saas_company" value="<?= $e($settings['saas_company'] ?? '') ?>" class="admin-input"></div>
            <div><label class="admin-label">Email de soporte</label><input name="saas_support_email" type="email" value="<?= $e($settings['saas_support_email'] ?? '') ?>" class="admin-input"></div>
            <div><label class="admin-label">Email de facturación</label><input name="saas_billing_email" type="email" value="<?= $e($settings['saas_billing_email'] ?? '') ?>" class="admin-input"></div>
            <div><label class="admin-label">URL Términos</label><input name="saas_terms_url" value="<?= $e($settings['saas_terms_url'] ?? '') ?>" class="admin-input"></div>
            <div><label class="admin-label">URL Privacidad</label><input name="saas_privacy_url" value="<?= $e($settings['saas_privacy_url'] ?? '') ?>" class="admin-input"></div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Facturación</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="admin-label">Moneda</label><input name="saas_currency" value="<?= $e($settings['saas_currency'] ?? 'USD') ?>" class="admin-input"></div>
            <div><label class="admin-label">Tasa impuesto (%)</label><input name="saas_tax_rate" type="number" step="0.01" value="<?= $e($settings['saas_tax_rate'] ?? 0) ?>" class="admin-input"></div>
            <div><label class="admin-label">Prefijo facturas</label><input name="saas_invoice_prefix" value="<?= $e($settings['saas_invoice_prefix'] ?? 'INV') ?>" class="admin-input"></div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Suscripciones</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="admin-label">Plan por defecto (slug)</label><input name="saas_default_plan" value="<?= $e($settings['saas_default_plan'] ?? 'pro') ?>" class="admin-input"></div>
            <div><label class="admin-label">Días de trial por defecto</label><input name="saas_default_trial_days" type="number" value="<?= $e($settings['saas_default_trial_days'] ?? 14) ?>" class="admin-input"></div>
            <div class="md:col-span-2 flex items-center gap-2">
                <label class="flex items-center gap-2"><input type="checkbox" name="saas_allow_registration" value="1" <?= !empty($settings['saas_allow_registration'])?'checked':'' ?>> Permitir auto-registro de empresas</label>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar ajustes</button>
    </div>
</form>
