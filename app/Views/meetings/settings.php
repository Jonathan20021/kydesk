<?php
$slug = $tenant->slug;
$publicSlug = $settings['public_slug'] ?: $slug;
$publicUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($publicSlug);
$timezones = ['America/Santo_Domingo','America/Mexico_City','America/Bogota','America/Lima','America/Argentina/Buenos_Aires','America/Santiago','America/New_York','America/Los_Angeles','Europe/Madrid','Europe/London','Europe/Paris','UTC'];
?>

<div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
    <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
    <span>Ajustes</span>
</div>
<h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em] mb-5">Página pública</h1>

<form method="POST" action="<?= $url('/t/' . $slug . '/meetings/settings') ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-[15px]">URL pública</h3>
                <label class="inline-flex items-center gap-2 text-[12px]">
                    <input type="checkbox" name="is_enabled" value="1" <?= (int)$settings['is_enabled']?'checked':'' ?>>
                    <span>Activa</span>
                </label>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Slug público</label>
                <div class="flex items-center gap-2">
                    <span class="text-[12px] text-ink-400 font-mono"><?= $e(rtrim($app->config['app']['url'], '/')) ?>/book/</span>
                    <input name="public_slug" class="input" value="<?= $e($publicSlug) ?>" placeholder="<?= $e($slug) ?>">
                </div>
                <p class="text-[11px] text-ink-400 mt-1">URL actual: <a href="<?= $e($publicUrl) ?>" target="_blank" class="text-brand-700 underline"><?= $e($publicUrl) ?></a></p>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Branding y mensajes</h3>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Título de la página</label>
                <input name="page_title" class="input" value="<?= $e($settings['page_title'] ?? '') ?>" placeholder="Agenda una reunión con <?= $e($tenant->name) ?>">
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Descripción</label>
                <textarea name="page_description" rows="2" class="input" style="height:auto;padding:12px 16px"><?= $e($settings['page_description'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Logo URL (opcional)</label>
                    <input name="logo_url" class="input" value="<?= $e($settings['logo_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Color primario</label>
                    <input type="color" name="primary_color" value="<?= $e($settings['primary_color'] ?? '#7c5cff') ?>" class="w-full h-11 rounded-2xl border" style="border-color:var(--border)">
                </div>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Mensaje de bienvenida</label>
                <textarea name="welcome_message" rows="3" class="input" style="height:auto;padding:12px 16px" placeholder="Hola, gracias por visitar nuestra agenda..."><?= $e($settings['welcome_message'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Mensaje de éxito (post-reserva)</label>
                <textarea name="success_message" rows="3" class="input" style="height:auto;padding:12px 16px" placeholder="¡Reserva confirmada! Te enviamos un email..."><?= $e($settings['success_message'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Información del negocio</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Nombre del negocio</label>
                    <input name="business_name" class="input" value="<?= $e($settings['business_name'] ?? $tenant->name) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Email de contacto</label>
                    <input type="email" name="business_email" class="input" value="<?= $e($settings['business_email'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Teléfono</label>
                    <input name="business_phone" class="input" value="<?= $e($settings['business_phone'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Dirección</label>
                    <input name="business_address" class="input" value="<?= $e($settings['business_address'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Zona horaria</h3>
            <select name="timezone" class="input">
                <?php foreach ($timezones as $tz): ?>
                    <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? '')===$tz?'selected':'' ?>><?= $tz ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-[11px] text-ink-400">Los horarios se mostrarán al cliente en esta zona.</p>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Campos del formulario</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Pedir teléfono (obligatorio)</span>
                <input type="checkbox" name="require_phone" value="1" <?= !empty($settings['require_phone']) ? 'checked' : '' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Pedir empresa (obligatorio)</span>
                <input type="checkbox" name="require_company" value="1" <?= !empty($settings['require_company']) ? 'checked' : '' ?>>
            </label>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Notificaciones internas</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Notificarme las nuevas reservas</span>
                <input type="checkbox" name="notify_new_booking" value="1" <?= (int)$settings['notify_new_booking']?'checked':'' ?>>
            </label>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Emails extra (separados por coma)</label>
                <textarea name="notify_emails" rows="2" class="input" style="height:auto;padding:12px 16px" placeholder="ventas@empresa.com, ana@empresa.com"><?= $e($settings['notify_emails'] ?? '') ?></textarea>
                <p class="text-[11px] text-ink-400 mt-1">Si está vacío usamos el email del host asignado al tipo.</p>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Footer</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Mostrar "Powered by Kydesk"</span>
                <input type="checkbox" name="show_powered_by" value="1" <?= (int)$settings['show_powered_by']?'checked':'' ?>>
            </label>
        </div>

        <div class="flex gap-2 sticky bottom-4">
            <a href="<?= $e($publicUrl) ?>" target="_blank" class="btn btn-outline btn-sm flex-1"><i class="lucide lucide-eye"></i> Vista previa</a>
            <button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-check"></i> Guardar</button>
        </div>
    </div>
</form>
