<?php $slug = $tenant->slug; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Soporte directo</h1>
        <p class="text-[13px] text-ink-400">Comunicación directa con el equipo de Kydesk Helpdesk</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Form -->
    <div class="lg:col-span-2">
        <div class="card card-pad">
            <h2 class="font-display font-bold text-[16px] mb-4"><i class="lucide lucide-message-square-plus text-brand-600"></i> Nuevo ticket de soporte</h2>
            <form method="POST" action="<?= $url('/t/' . $slug . '/support') ?>" class="space-y-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Asunto <span class="text-rose-600">*</span></label>
                    <input name="subject" required maxlength="200" class="input mt-1" placeholder="Resumen breve del problema o consulta">
                </div>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Prioridad</label>
                    <div class="mt-1.5 grid grid-cols-4 gap-2">
                        <?php foreach ([['low','Baja','#9ca3af'],['medium','Media','#7c5cff'],['high','Alta','#f59e0b'],['urgent','Urgente','#ef4444']] as [$v, $l, $c]): ?>
                            <label class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-[#ececef] cursor-pointer text-[12px] font-semibold has-[:checked]:bg-brand-50 has-[:checked]:border-brand-300 has-[:checked]:text-brand-700">
                                <input type="radio" name="priority" value="<?= $v ?>" <?= $v==='medium'?'checked':'' ?> class="hidden">
                                <span class="dot" style="background:<?= $c ?>"></span> <?= $l ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Mensaje <span class="text-rose-600">*</span></label>
                    <textarea name="body" required rows="8" class="input mt-1" placeholder="Describe lo que necesitas. Incluye pasos para reproducir si es un bug, capturas con enlaces, o el comportamiento esperado vs el actual."></textarea>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11.5px] text-ink-400 flex items-center gap-1.5"><i class="lucide lucide-clock text-[13px]"></i> Respondemos en menos de 24h hábiles</p>
                    <button class="btn btn-primary"><i class="lucide lucide-send"></i> Enviar a soporte</button>
                </div>
            </form>
        </div>

        <div class="mt-4">
            <h2 class="font-display font-bold text-[15px] mb-3"><i class="lucide lucide-history text-ink-500"></i> Tickets anteriores</h2>
            <?php if (empty($tickets)): ?>
                <div class="card card-pad text-center py-10 text-[13px] text-ink-400">Aún no has abierto tickets de soporte.</div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($tickets as $t):
                        $statusColors = ['open'=>'#f59e0b','in_progress'=>'#7c5cff','waiting'=>'#9ca3af','resolved'=>'#10b981','closed'=>'#6b6b78'];
                        $sc = $statusColors[$t['status']] ?? '#6b6b78'; ?>
                        <a href="<?= $url('/t/' . $slug . '/support/' . $t['id']) ?>" class="card card-pad block hover:shadow-md transition flex items-center gap-3">
                            <span class="status-pill" style="background:<?= $sc ?>15;color:<?= $sc ?>;border:1px solid <?= $sc ?>30"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10.5px] text-ink-400"><?= $e($t['code']) ?></span>
                                    <div class="font-display font-bold text-[14px] truncate"><?= $e($t['subject']) ?></div>
                                </div>
                                <div class="text-[11.5px] text-ink-400 mt-0.5">Actualizado <?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></div>
                            </div>
                            <i class="lucide lucide-chevron-right text-ink-400"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        <div class="card card-pad" style="background:linear-gradient(135deg,#f3f0ff,#fff);border-color:#cdbfff80">
            <i class="lucide lucide-zap text-brand-600 text-[20px]"></i>
            <h3 class="font-display font-bold text-[14px] mt-2">¿Necesitas respuesta más rápida?</h3>
            <p class="text-[12px] text-ink-500 mt-1.5 leading-relaxed">Para incidencias críticas en producción, marca prioridad <strong>Urgente</strong>. Nuestro equipo recibe alertas inmediatas.</p>
        </div>
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[14px]"><i class="lucide lucide-mail text-ink-500"></i> Otros canales</h3>
            <ul class="mt-3 space-y-2 text-[12.5px]">
                <li><a href="mailto:jonathansandoval@kyrosrd.com" class="flex items-center gap-2 text-ink-700 hover:text-brand-700"><i class="lucide lucide-mail text-[13px] text-brand-600"></i> jonathansandoval@kyrosrd.com</a></li>
                <li><a href="https://wa.me/18495024061" target="_blank" rel="noopener" class="flex items-center gap-2 text-ink-700 hover:text-brand-700"><i class="lucide lucide-message-circle text-[13px] text-emerald-600"></i> +1 849 502 4061 · WhatsApp</a></li>
                <li class="flex items-center gap-2 text-ink-500"><i class="lucide lucide-globe text-[13px]"></i> República Dominicana · Remoto</li>
            </ul>
        </div>
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[14px]"><i class="lucide lucide-clock text-ink-500"></i> Horario</h3>
            <p class="text-[12.5px] text-ink-500 mt-2 leading-relaxed">Lun a Vie · 9:00–18:00 (GMT-4). Tickets fuera de horario se atienden la siguiente jornada hábil.</p>
        </div>
    </div>
</div>
