<?php
$slug = $tenant->slug;
$publicSlug = $settings['public_slug'] ?: $slug;
$publicUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($publicSlug);
$timezones = ['America/Santo_Domingo','America/Mexico_City','America/Bogota','America/Lima','America/Argentina/Buenos_Aires','America/Santiago','America/New_York','America/Los_Angeles','Europe/Madrid','Europe/London','Europe/Paris','UTC'];
$aiAvailable = \App\Core\MeetingAi::guard($tenant)['ok'];
// Consumo IA del módulo de reuniones (último mes en curso)
$meetingAiUsage = $app->db->one(
    "SELECT COUNT(*) AS reqs, IFNULL(SUM(tokens_in),0) AS tin, IFNULL(SUM(tokens_out),0) AS tout
     FROM ai_completions
     WHERE tenant_id = ? AND status = 'ok'
       AND action LIKE 'meeting_%'
       AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",
    [$tenant->id]
);
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

        <div class="card card-pad space-y-3" style="<?= !$aiAvailable ? 'opacity:.6' : '' ?>">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-[15px] flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-[0.14em]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white">
                        <i class="lucide lucide-sparkles text-[10px]"></i> Kyros IA
                    </span>
                </h3>
                <?php if (!$aiAvailable): ?>
                    <span class="badge badge-amber text-[10px]">No asignada</span>
                <?php endif; ?>
            </div>
            <?php if (!$aiAvailable): ?>
                <p class="text-[12px] text-ink-500">Kyros IA requiere plan Enterprise + asignación del equipo Kydesk. Las opciones quedan deshabilitadas hasta entonces.</p>
            <?php endif; ?>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <div>
                    <div>Análisis automático al reservar</div>
                    <div class="text-[11px] text-ink-400">Detecta intent, sentiment, urgencia y resumen al instante</div>
                </div>
                <input type="checkbox" name="ai_auto_analyze" value="1" <?= (int)($settings['ai_auto_analyze'] ?? 1)?'checked':'' ?> <?= !$aiAvailable?'disabled':'' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <div>
                    <div>Suggester en página pública</div>
                    <div class="text-[11px] text-ink-400">Cliente describe necesidad, IA recomienda tipo</div>
                </div>
                <input type="checkbox" name="ai_public_suggester" value="1" <?= (int)($settings['ai_public_suggester'] ?? 1)?'checked':'' ?> <?= !$aiAvailable?'disabled':'' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <div>
                    <div>Briefing pre-meeting on-demand</div>
                    <div class="text-[11px] text-ink-400">Botón en el detalle para generar brief con IA</div>
                </div>
                <input type="checkbox" name="ai_briefing_enabled" value="1" <?= (int)($settings['ai_briefing_enabled'] ?? 1)?'checked':'' ?> <?= !$aiAvailable?'disabled':'' ?>>
            </label>

            <?php if ($aiAvailable && $meetingAiUsage): ?>
                <div class="pt-3 mt-2" style="border-top:1px solid var(--border)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-2">Consumo IA del módulo este mes</div>
                    <div class="grid grid-cols-3 gap-2 text-[12px]">
                        <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                            <div class="text-ink-400 text-[10px] uppercase tracking-[0.1em]">Llamadas</div>
                            <div class="font-mono font-bold text-ink-700"><?= number_format((int)$meetingAiUsage['reqs']) ?></div>
                        </div>
                        <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                            <div class="text-ink-400 text-[10px] uppercase tracking-[0.1em]">Tokens in</div>
                            <div class="font-mono font-bold text-ink-700"><?= number_format((int)$meetingAiUsage['tin']) ?></div>
                        </div>
                        <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                            <div class="text-ink-400 text-[10px] uppercase tracking-[0.1em]">Tokens out</div>
                            <div class="font-mono font-bold text-ink-700"><?= number_format((int)$meetingAiUsage['tout']) ?></div>
                        </div>
                    </div>
                    <p class="text-[11px] text-ink-400 mt-2">Se descuentan de la cuota IA asignada al workspace · <a href="<?= $url('/t/' . $slug . '/ai') ?>" class="text-brand-700">ver detalles</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex gap-2 sticky bottom-4">
            <a href="<?= $e($publicUrl) ?>" target="_blank" class="btn btn-outline btn-sm flex-1"><i class="lucide lucide-eye"></i> Vista previa</a>
            <button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-check"></i> Guardar</button>
        </div>
    </div>
</form>
