<?php
use App\Controllers\MeetingController;
$slug = $tenant->slug;
$m = $meeting;
[$lbl, $cls, $ico] = MeetingController::STATUS_LABELS[$m['status']] ?? [ucfirst($m['status']), 'badge-gray', 'circle'];
$when = strtotime($m['scheduled_at']);
$ends = strtotime($m['ends_at']);
$customAnswers = !empty($m['custom_answers']) ? json_decode($m['custom_answers'], true) : [];
$publicSlug = $app->db->val('SELECT public_slug FROM meeting_settings WHERE tenant_id=?', [$tenant->id]) ?: $slug;
$manageUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($publicSlug) . '/manage/' . $m['public_token'];
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="flex items-center gap-2 text-[12px] text-ink-400">
        <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
        <a href="<?= $url('/t/' . $slug . '/meetings/list') ?>" class="hover:text-ink-700">Lista</a> /
        <span class="font-mono"><?= $e($m['code']) ?></span>
    </div>
    <span class="badge <?= $cls ?>"><i class="lucide lucide-<?= $ico ?> text-[11px]"></i> <?= $lbl ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 rounded-2xl grid place-items-center flex-shrink-0" style="background:<?= $e($m['type_color'] ?? '#7c5cff') ?>22;color:<?= $e($m['type_color'] ?? '#7c5cff') ?>">
                    <i class="lucide lucide-<?= $e($m['type_icon'] ?? 'video') ?> text-[24px]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em]"><?= $e($m['type_name'] ?? 'Reunión') ?></h1>
                    <div class="text-[13px] text-ink-500"><?= (int)$m['duration_minutes'] ?> minutos</div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-xl p-3" style="background:var(--bg)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Cuándo</div>
                    <div class="font-display font-bold text-[16px] mt-0.5"><?= date('l j M Y', $when) ?></div>
                    <div class="text-[13px] text-ink-700"><?= date('H:i', $when) ?> - <?= date('H:i', $ends) ?></div>
                    <div class="text-[11px] text-ink-400"><?= $e($m['timezone']) ?></div>
                </div>
                <div class="rounded-xl p-3" style="background:var(--bg)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Dónde</div>
                    <div class="font-display font-bold text-[14px] mt-0.5"><?= ['virtual'=>'Virtual','phone'=>'Llamada','in_person'=>'Presencial','custom'=>'Custom'][$m['location_type']] ?? $m['location_type'] ?></div>
                    <?php if (!empty($m['meeting_url'])): ?>
                        <a href="<?= $e($m['meeting_url']) ?>" target="_blank" class="text-[12px] text-brand-700 truncate inline-block max-w-full"><i class="lucide lucide-external-link text-[11px]"></i> Abrir enlace</a>
                    <?php elseif (!empty($m['location_value'])): ?>
                        <div class="text-[12px] text-ink-700"><?= $e($m['location_value']) ?></div>
                    <?php else: ?>
                        <div class="text-[12px] text-ink-400 italic">Sin definir</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cliente -->
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Cliente</h3>
            <div class="grid grid-cols-2 gap-3 text-[13px]">
                <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Nombre</span><div class="font-semibold"><?= $e($m['customer_name']) ?></div></div>
                <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Email</span><div class="font-semibold"><a href="mailto:<?= $e($m['customer_email']) ?>" class="text-brand-700"><?= $e($m['customer_email']) ?></a></div></div>
                <?php if (!empty($m['customer_phone'])): ?>
                    <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Teléfono</span><div class="font-semibold"><?= $e($m['customer_phone']) ?></div></div>
                <?php endif; ?>
                <?php if (!empty($m['customer_company'])): ?>
                    <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Empresa</span><div class="font-semibold"><?= $e($m['customer_company']) ?></div></div>
                <?php endif; ?>
                <?php if (!empty($m['company_name'])): ?>
                    <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Empresa vinculada</span><div class="font-semibold"><?= $e($m['company_name']) ?></div></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($m['notes'])): ?>
                <div>
                    <span class="text-[11px] text-ink-400 uppercase tracking-wide">Mensaje</span>
                    <div class="mt-1 p-3 rounded-xl text-[13px] text-ink-700" style="background:var(--bg);white-space:pre-wrap"><?= $e($m['notes']) ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($customAnswers)): ?>
                <div class="space-y-2">
                    <span class="text-[11px] text-ink-400 uppercase tracking-wide">Respuestas</span>
                    <?php foreach ($customAnswers as $ans): ?>
                        <div class="p-3 rounded-xl" style="background:var(--bg)">
                            <div class="text-[11px] text-ink-400"><?= $e($ans['label'] ?? '') ?></div>
                            <div class="text-[13px] text-ink-700 mt-0.5"><?= $e($ans['value'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Editar -->
        <?php if ($auth->can('meetings.edit') && !in_array($m['status'], ['cancelled','no_show'], true)): ?>
        <details class="card card-pad">
            <summary class="cursor-pointer font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-pencil text-[14px]"></i> Editar reunión</summary>
            <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Host</label>
                    <select name="host_user_id" class="input">
                        <option value="0">— Sin host —</option>
                        <?php foreach ($hosts as $h): ?>
                            <option value="<?= (int)$h['id'] ?>" <?= (int)$m['host_user_id']===(int)$h['id']?'selected':'' ?>><?= $e($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Empresa vinculada</label>
                    <select name="company_id" class="input">
                        <option value="0">—</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$m['company_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Cliente</label>
                    <input name="customer_name" class="input" value="<?= $e($m['customer_name']) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Email</label>
                    <input type="email" name="customer_email" class="input" value="<?= $e($m['customer_email']) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Teléfono</label>
                    <input name="customer_phone" class="input" value="<?= $e($m['customer_phone'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Empresa (texto)</label>
                    <input name="customer_company" class="input" value="<?= $e($m['customer_company'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">URL de la reunión</label>
                    <input type="url" name="meeting_url" class="input" value="<?= $e($m['meeting_url'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Detalle de ubicación</label>
                    <input name="location_value" class="input" value="<?= $e($m['location_value'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Asunto</label>
                    <input name="subject" class="input" value="<?= $e($m['subject'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Notas internas</label>
                    <textarea name="notes" rows="2" class="input" style="height:auto;padding:12px 16px"><?= $e($m['notes'] ?? '') ?></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-check"></i> Guardar cambios</button>
                </div>
            </form>
        </details>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Acciones</h3>

            <?php if ($auth->can('meetings.edit') && $m['status'] === 'scheduled'): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/confirm') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-primary btn-sm w-full"><i class="lucide lucide-check-circle"></i> Confirmar</button>
                </form>
            <?php endif; ?>

            <?php if ($auth->can('meetings.edit') && in_array($m['status'], ['scheduled','confirmed'], true)): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/complete') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-soft btn-sm w-full"><i class="lucide lucide-check-check"></i> Marcar completada</button>
                </form>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/no-show') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-outline btn-sm w-full"><i class="lucide lucide-user-x"></i> No-show</button>
                </form>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/cancel') ?>" onsubmit="return confirm('¿Cancelar la reunión y notificar al cliente?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input name="cancel_reason" class="input mb-2" placeholder="Motivo (opcional)">
                    <button class="btn btn-danger btn-sm w-full"><i class="lucide lucide-x-circle"></i> Cancelar reunión</button>
                </form>
            <?php endif; ?>

            <?php if (!empty($m['cancel_reason'])): ?>
                <div class="rounded-xl p-3" style="background:#fef2f2;border:1px solid #fecaca">
                    <div class="text-[11px] font-bold uppercase tracking-[0.14em]" style="color:#991b1b">Motivo de cancelación</div>
                    <div class="text-[12.5px] mt-1" style="color:#7f1d1d"><?= $e($m['cancel_reason']) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($auth->can('meetings.delete')): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar permanentemente esta reunión?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-ghost btn-sm w-full text-[#ef4444]"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card card-pad space-y-2 text-[12.5px]">
            <h3 class="font-display font-bold text-[15px] mb-1">Detalles</h3>
            <div class="flex justify-between"><span class="text-ink-400">Código</span><span class="font-mono"><?= $e($m['code']) ?></span></div>
            <div class="flex justify-between"><span class="text-ink-400">Host</span><span><?= $e($m['host_name'] ?? '—') ?></span></div>
            <div class="flex justify-between"><span class="text-ink-400">Origen</span><span><?= ['public'=>'Página pública','manual'=>'Manual','import'=>'Importado'][$m['source']] ?? $m['source'] ?></span></div>
            <div class="flex justify-between"><span class="text-ink-400">Creada</span><span><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></span></div>
            <?php if (!empty($m['confirmation_sent_at'])): ?>
                <div class="flex justify-between"><span class="text-ink-400">Confirmación enviada</span><span><?= date('d/m H:i', strtotime($m['confirmation_sent_at'])) ?></span></div>
            <?php endif; ?>
            <div class="pt-2 mt-2" style="border-top:1px solid var(--border)">
                <a href="<?= $e($manageUrl) ?>" target="_blank" class="text-[12px] text-brand-700"><i class="lucide lucide-external-link text-[11px]"></i> Enlace público del cliente</a>
            </div>
        </div>
    </div>
</div>
