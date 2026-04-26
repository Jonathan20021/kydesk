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

    <div class="admin-card admin-card-pad mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="admin-h2">Email · Resend + SMTP</h2>
                <p class="text-sm text-slate-500">Resend es el transporte primario. Si falla, intenta el SMTP de respaldo.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Driver primario</label>
                <select name="mail_driver" class="admin-input">
                    <option value="resend" <?= ($settings['mail_driver'] ?? 'resend')==='resend'?'selected':'' ?>>Resend (recomendado)</option>
                    <option value="smtp"   <?= ($settings['mail_driver'] ?? '')==='smtp'?'selected':'' ?>>SMTP</option>
                </select>
            </div>
            <div><label class="admin-label">Reply-To</label><input name="mail_reply_to" value="<?= $e($settings['mail_reply_to'] ?? 'jonathansandoval@kyrosrd.com') ?>" class="admin-input"></div>
            <div><label class="admin-label">From email</label><input name="mail_from_email" value="<?= $e($settings['mail_from_email'] ?? 'no-reply@kyrosrd.com') ?>" class="admin-input"></div>
            <div><label class="admin-label">From nombre</label><input name="mail_from_name" value="<?= $e($settings['mail_from_name'] ?? 'Kydesk Helpdesk') ?>" class="admin-input"></div>
            <div class="md:col-span-2">
                <label class="admin-label">Resend API Key</label>
                <input name="resend_api_key" value="<?= $e($settings['resend_api_key'] ?? '') ?>" class="admin-input" placeholder="re_xxxxxxxxxxxxxxxxxxxx" autocomplete="off">
                <p class="text-xs text-slate-500 mt-1">Obtenida en <a class="text-indigo-600" href="https://resend.com/api-keys" target="_blank" rel="noopener">resend.com/api-keys</a>.</p>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-200">
            <h3 class="admin-h3 mb-3">SMTP de respaldo</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2"><label class="admin-label">Host</label><input name="smtp_host" value="<?= $e($settings['smtp_host'] ?? '') ?>" class="admin-input" placeholder="smtp.tu-proveedor.com"></div>
                <div><label class="admin-label">Puerto</label><input name="smtp_port" type="number" value="<?= $e($settings['smtp_port'] ?? '587') ?>" class="admin-input"></div>
                <div><label class="admin-label">Usuario</label><input name="smtp_user" value="<?= $e($settings['smtp_user'] ?? '') ?>" class="admin-input" autocomplete="off"></div>
                <div><label class="admin-label">Contraseña</label><input name="smtp_pass" type="password" value="<?= $e($settings['smtp_pass'] ?? '') ?>" class="admin-input" autocomplete="new-password"></div>
                <div>
                    <label class="admin-label">Cifrado</label>
                    <select name="smtp_secure" class="admin-input">
                        <option value="tls" <?= ($settings['smtp_secure'] ?? 'tls')==='tls'?'selected':'' ?>>TLS (STARTTLS)</option>
                        <option value="ssl" <?= ($settings['smtp_secure'] ?? '')==='ssl'?'selected':'' ?>>SSL</option>
                        <option value=""    <?= ($settings['smtp_secure'] ?? '')===''?'selected':'' ?>>Sin cifrado</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar ajustes</button>
    </div>
</form>

<form method="POST" action="<?= $url('/admin/settings/test-email') ?>" class="max-w-3xl mt-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-3">Probar envío</h2>
        <p class="text-sm text-slate-500 mb-3">Envía un correo de prueba al destino indicado usando la configuración guardada arriba.</p>
        <div class="flex gap-2">
            <input name="test_to" type="email" required placeholder="tu-email@dominio.com" class="admin-input flex-1">
            <button class="admin-btn admin-btn-secondary"><i class="lucide lucide-send"></i> Enviar prueba</button>
        </div>
    </div>
</form>
