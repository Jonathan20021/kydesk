<?php $slug = $tenant->slug; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
            <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
            <span>Tipos</span>
        </div>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Tipos de reunión</h1>
        <p class="text-[13px] text-ink-400">Definí cuántos tipos de cita ofrecés en tu página pública: duración, ubicación, buffer, preguntas personalizadas y más.</p>
    </div>
    <a href="<?= $url('/t/' . $slug . '/meetings/types/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo tipo</a>
</div>

<?php if (empty($types)): ?>
    <div class="card card-pad text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-[#f3f0ff] grid place-items-center mx-auto mb-4"><i class="lucide lucide-list-plus text-[26px] text-brand-500"></i></div>
        <h3 class="font-display font-bold text-[18px]">Sin tipos creados</h3>
        <p class="text-[13px] text-ink-400 mt-1 max-w-md mx-auto">Creá tipos como "Demo 30 min", "Llamada técnica 1h" o "Sesión estratégica" para que clientes elijan cuál reservar.</p>
        <a href="<?= $url('/t/' . $slug . '/meetings/types/create') ?>" class="btn btn-primary btn-sm mt-4 inline-flex"><i class="lucide lucide-plus"></i> Crear el primero</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($types as $t):
            $bookingUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($tenant->slug) . '/' . rawurlencode($t['slug']);
        ?>
            <div class="card overflow-hidden flex flex-col">
                <div class="px-5 pt-4 pb-3 flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl grid place-items-center flex-shrink-0" style="background:<?= $e($t['color']) ?>22;color:<?= $e($t['color']) ?>">
                        <i class="lucide lucide-<?= $e($t['icon']) ?> text-[18px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-display font-bold text-[15.5px] truncate"><?= $e($t['name']) ?></h3>
                            <?php if (!(int)$t['is_active']): ?><span class="badge badge-gray text-[10px]">PAUSADO</span><?php endif; ?>
                        </div>
                        <div class="text-[11.5px] text-ink-400 mt-0.5">
                            <?= (int)$t['duration_minutes'] ?> min · <?= $e($t['host_name'] ?? '—') ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($t['description'])): ?>
                    <div class="px-5 pb-2 text-[12.5px] text-ink-500 line-clamp-2"><?= $e($t['description']) ?></div>
                <?php endif; ?>
                <div class="px-5 pb-3 flex flex-wrap gap-1.5">
                    <span class="badge badge-gray text-[10px]"><i class="lucide lucide-<?= $t['location_type']==='virtual'?'video':($t['location_type']==='phone'?'phone':($t['location_type']==='in_person'?'map-pin':'map')) ?> text-[10px]"></i> <?= ['virtual'=>'Virtual','phone'=>'Teléfono','in_person'=>'Presencial','custom'=>'Custom'][$t['location_type']] ?? $t['location_type'] ?></span>
                    <span class="badge badge-gray text-[10px]"><i class="lucide lucide-clock text-[10px]"></i> <?= (int)$t['min_notice_hours'] ?>h aviso</span>
                    <span class="badge badge-gray text-[10px]"><i class="lucide lucide-calendar-days text-[10px]"></i> <?= (int)$t['max_advance_days'] ?>d anticipo</span>
                    <?php if ((int)$t['requires_confirmation']): ?>
                        <span class="badge badge-amber text-[10px]"><i class="lucide lucide-shield-check text-[10px]"></i> Requiere confirmación</span>
                    <?php endif; ?>
                    <span class="badge badge-purple text-[10px]"><i class="lucide lucide-trending-up text-[10px]"></i> <?= (int)$t['total_bookings'] ?> reservas</span>
                </div>
                <div class="mt-auto px-5 py-3 flex items-center gap-1.5" style="border-top:1px solid var(--border);background:var(--bg)">
                    <button type="button" onclick="navigator.clipboard.writeText('<?= $e($bookingUrl) ?>'); this.querySelector('span').textContent='✓ Copiado'; setTimeout(()=>this.querySelector('span').textContent='Copiar enlace',1500)" class="admin-btn admin-btn-soft" style="height:30px;padding:0 10px;font-size:11.5px"><i class="lucide lucide-copy text-[11px]"></i><span>Copiar enlace</span></button>
                    <a href="<?= $e($bookingUrl) ?>" target="_blank" class="admin-btn admin-btn-soft" style="height:30px;padding:0 10px;font-size:11.5px"><i class="lucide lucide-external-link text-[11px]"></i></a>
                    <a href="<?= $url('/t/' . $slug . '/meetings/types/' . $t['id']) ?>" class="admin-btn admin-btn-soft ml-auto" style="height:30px;padding:0 10px;font-size:11.5px"><i class="lucide lucide-pencil text-[11px]"></i> Editar</a>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/types/' . $t['id'] . '/toggle') ?>" class="inline">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button type="submit" class="admin-btn admin-btn-soft" style="height:30px;padding:0 10px;font-size:11.5px" data-tooltip="<?= (int)$t['is_active']?'Pausar':'Reactivar' ?>"><i class="lucide lucide-<?= (int)$t['is_active']?'pause':'play' ?> text-[11px]"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
