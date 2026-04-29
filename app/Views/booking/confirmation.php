<?php
$brandColor = $settings['primary_color'] ?: '#7c5cff';
$publicSlug = $settings['public_slug'] ?: $tenant->slug;
$businessName = $settings['business_name'] ?: $tenant->name;
$showPowered = !empty($settings['show_powered_by']);
$when = strtotime($meeting['scheduled_at']);
$ends = strtotime($meeting['ends_at']);
$mesesEs = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$diasEs = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$dateLabel = $diasEs[(int)date('w', $when)] . ' ' . date('j', $when) . ' de ' . $mesesEs[(int)date('n', $when)] . ' de ' . date('Y', $when);

// Generate ICS calendar invite
$icsStart = gmdate('Ymd\THis\Z', $when);
$icsEnd   = gmdate('Ymd\THis\Z', $ends);
$icsUid = $meeting['public_token'] . '@' . parse_url($app->config['app']['url'] ?? 'kydesk', PHP_URL_HOST);
$icsTitle = 'Reunión: ' . ($meeting['type_name'] ?? 'Cita') . ' con ' . $businessName;
$icsDesc = $meeting['notes'] ?? '';
if (!empty($meeting['meeting_url'])) $icsDesc .= "\n\nEnlace: " . $meeting['meeting_url'];
$ics = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:$icsUid\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\nDTSTART:$icsStart\nDTEND:$icsEnd\nSUMMARY:" . str_replace(["\n","\r"], '', $icsTitle) . "\nDESCRIPTION:" . str_replace(["\n","\r"], '\\n', $icsDesc) . "\nEND:VEVENT\nEND:VCALENDAR";
$icsDataUri = 'data:text/calendar;charset=utf-8,' . rawurlencode($ics);

$manageUrl = $url('/book/' . rawurlencode($publicSlug) . '/manage/' . $meeting['public_token']);
?>
<style>
:root { --book-brand: <?= htmlspecialchars($brandColor) ?>; --book-brand-soft: <?= htmlspecialchars($brandColor) ?>15; --book-brand-mid: <?= htmlspecialchars($brandColor) ?>33; }
.book-shell { min-height: 100vh; background: linear-gradient(180deg, #fafafb 0%, #f3f4f6 100%); }
.book-card { background: white; border: 1px solid #ececef; border-radius: 24px; box-shadow: 0 8px 32px -12px rgba(22,21,27,.1); }
</style>

<div class="book-shell">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
        <div class="book-card overflow-hidden">
            <!-- Top success banner -->
            <div class="text-center px-8 pt-10 pb-6" style="background:linear-gradient(180deg, var(--book-brand-soft), white)">
                <div class="w-16 h-16 rounded-full mx-auto mb-4 grid place-items-center" style="background:var(--book-brand);color:white;box-shadow:0 12px 28px -12px <?= $e($brandColor) ?>">
                    <i class="lucide lucide-check text-[28px]"></i>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-[0.14em]" style="background:var(--book-brand-soft);color:var(--book-brand);border:1px solid var(--book-brand-mid)">
                    <i class="lucide lucide-check-circle text-[11px]"></i> <?= $meeting['status'] === 'scheduled' ? 'Reserva recibida' : 'Reserva confirmada' ?>
                </span>
                <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] text-ink-900 mt-3 mb-2">¡Listo, <?= $e(explode(' ', $meeting['customer_name'])[0]) ?>!</h1>
                <?php if (!empty($settings['success_message'])): ?>
                    <p class="text-[14px] text-ink-500 max-w-md mx-auto"><?= nl2br($e($settings['success_message'])) ?></p>
                <?php else: ?>
                    <p class="text-[14px] text-ink-500 max-w-md mx-auto">Te enviamos un email a <strong><?= $e($meeting['customer_email']) ?></strong> con todos los detalles. Ya podés cerrar esta ventana.</p>
                <?php endif; ?>
            </div>

            <!-- Detalle de la reunión -->
            <div class="px-8 py-6 space-y-3 border-t" style="border-color:#ececef">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:<?= $e($meeting['type_color'] ?? $brandColor) ?>22;color:<?= $e($meeting['type_color'] ?? $brandColor) ?>">
                        <i class="lucide lucide-<?= $e($meeting['type_icon'] ?? 'calendar') ?> text-[16px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400">Reunión</div>
                        <div class="font-display font-bold text-[15px] text-ink-900"><?= $e($meeting['type_name'] ?? 'Cita') ?></div>
                        <div class="text-[12px] text-ink-500"><?= (int)$meeting['duration_minutes'] ?> min</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 pt-3" style="border-top:1px solid #ececef">
                    <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#f3f4f6;color:#6b6b78"><i class="lucide lucide-calendar text-[16px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400">Cuándo</div>
                        <div class="font-display font-bold text-[15px] text-ink-900"><?= $e($dateLabel) ?></div>
                        <div class="text-[12px] text-ink-500"><?= date('H:i', $when) ?> - <?= date('H:i', $ends) ?> · <?= $e($meeting['timezone']) ?></div>
                    </div>
                </div>
                <div class="flex items-start gap-3 pt-3" style="border-top:1px solid #ececef">
                    <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#f3f4f6;color:#6b6b78">
                        <i class="lucide lucide-<?= $meeting['location_type']==='virtual'?'video':($meeting['location_type']==='phone'?'phone':($meeting['location_type']==='in_person'?'map-pin':'map')) ?> text-[16px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400">Dónde</div>
                        <?php if (!empty($meeting['meeting_url'])): ?>
                            <a href="<?= $e($meeting['meeting_url']) ?>" target="_blank" class="font-display font-bold text-[15px]" style="color:var(--book-brand)"><i class="lucide lucide-external-link text-[13px]"></i> Abrir enlace</a>
                        <?php elseif (!empty($meeting['location_value'])): ?>
                            <div class="font-display font-bold text-[15px] text-ink-900"><?= $e($meeting['location_value']) ?></div>
                        <?php else: ?>
                            <div class="font-display font-bold text-[15px] text-ink-900"><?= ['virtual'=>'Videollamada (te enviamos el enlace)','phone'=>'Te llamamos al teléfono indicado','in_person'=>'Presencial — coordinamos detalles','custom'=>'A coordinar por email'][$meeting['location_type']] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-start gap-3 pt-3" style="border-top:1px solid #ececef">
                    <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#f3f4f6;color:#6b6b78"><i class="lucide lucide-hash text-[16px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400">Código</div>
                        <div class="font-mono font-bold text-[14px] text-ink-900"><?= $e($meeting['code']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="px-8 pb-8 pt-4 flex flex-col gap-2" style="background:#fafafb">
                <?php if (!empty($conferenceConfig)):
                    $isAudioOnly = !empty($conferenceConfig['audioOnly']);
                ?>
                    <a href="<?= $e($manageUrl) ?>" class="inline-flex items-center justify-center gap-2 h-[48px] rounded-2xl text-white text-[14px] font-semibold" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);box-shadow:0 8px 22px -8px rgba(124,92,255,.55)">
                        <i class="lucide lucide-<?= $isAudioOnly ? 'phone' : 'video' ?> text-[15px]"></i>
                        Entrar a la <?= $isAudioOnly ? 'llamada' : 'video reunión' ?>
                    </a>
                    <p class="text-center text-[11px] text-ink-400 -mt-1">Desde el botón de arriba podés conectarte 15 min antes de la hora.</p>
                <?php endif; ?>
                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="<?= $e($icsDataUri) ?>" download="reunion-<?= $e($meeting['code']) ?>.ics" class="flex-1 inline-flex items-center justify-center gap-2 h-[44px] rounded-2xl border border-[#ececef] bg-white text-[13.5px] font-semibold hover:border-ink-300">
                        <i class="lucide lucide-calendar-plus text-[14px]"></i> Agregar al calendario
                    </a>
                    <?php if ((int)($meeting['allow_cancel'] ?? 1) === 1 || (int)($meeting['allow_reschedule'] ?? 1) === 1): ?>
                        <a href="<?= $e($manageUrl) ?>" class="flex-1 inline-flex items-center justify-center gap-2 h-[44px] rounded-2xl text-white text-[13.5px] font-semibold" style="background:var(--book-brand);box-shadow:0 4px 14px -4px <?= $e($brandColor) ?>aa">
                            <i class="lucide lucide-settings-2 text-[14px]"></i> Gestionar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($showPowered): ?>
            <p class="text-center text-[11px] text-ink-400 mt-6">Powered by <a href="https://kydesk.kyrosrd.com" target="_blank" class="font-semibold text-brand-700">Kydesk</a></p>
        <?php endif; ?>
    </div>
</div>
