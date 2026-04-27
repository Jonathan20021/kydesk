<?php
$slug = $tenant->slug;
$icons = ['layers','life-buoy','headphones','wrench','heart-handshake','users','user-cog','briefcase','building-2','warehouse','graduation-cap','shield','wallet','credit-card','trending-up','megaphone','box','truck','book-open','code-2','flask-conical','sparkles','globe','zap'];
?>

<a href="<?= $url('/t/' . $slug . '/departments') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500 hover:text-ink-900 mb-3"><i class="lucide lucide-arrow-left"></i> Volver a departamentos</a>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-5">
    <div class="flex items-start gap-4">
        <div class="w-16 h-16 rounded-2xl grid place-items-center shrink-0" style="background:<?= $e($dept['color']) ?>15;color:<?= $e($dept['color']) ?>;border:1px solid <?= $e($dept['color']) ?>40">
            <i class="lucide lucide-<?= $e($dept['icon']) ?> text-[26px]"></i>
        </div>
        <div>
            <div class="flex items-center gap-2 mb-1">
                <?php if (!(int)$dept['is_active']): ?>
                    <span class="text-[10px] uppercase tracking-[0.14em] px-2 py-0.5 rounded-full" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb">Inactivo</span>
                <?php else: ?>
                    <span class="text-[10px] uppercase tracking-[0.14em] px-2 py-0.5 rounded-full" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Activo</span>
                <?php endif; ?>
                <span class="text-[11px] text-ink-400 font-mono">#<?= (int)$dept['id'] ?></span>
            </div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]"><?= $e($dept['name']) ?></h1>
            <?php if (!empty($dept['description'])): ?>
                <p class="text-[13px] text-ink-500 mt-1 max-w-2xl"><?= $e($dept['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <form method="POST" action="<?= $url('/t/' . $slug . '/departments/' . $dept['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar el departamento? Los tickets quedarán sin departamento asignado.')">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <button class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca"><i class="lucide lucide-trash-2 text-[12px]"></i> Eliminar</button>
    </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-5">
    <?php foreach ([
        ['Total tickets', $stats['total'], 'inbox', '#3b82f6'],
        ['Abiertos',      $stats['open'], 'circle-dot', '#f59e0b'],
        ['En progreso',   $stats['in_progress'], 'loader', '#7c5cff'],
        ['Resueltos',     $stats['resolved'], 'check-circle-2', '#16a34a'],
        ['Tiempo medio',  number_format($stats['avg_hours'] ?: 0, 1) . 'h', 'clock', '#0ea5e9'],
        ['SLA brechas',   $stats['breached'], 'alert-triangle', '#dc2626'],
    ] as [$lbl,$val,$ic,$col]): ?>
        <div class="card card-pad">
            <div class="flex items-center gap-2 mb-1.5">
                <i class="lucide lucide-<?= $ic ?> text-[13px]" style="color:<?= $col ?>"></i>
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $e($lbl) ?></span>
            </div>
            <div class="font-display font-extrabold text-[20px] tracking-[-0.02em]" style="color:<?= $col ?>"><?= is_numeric($val) ? number_format((int)$val) : $e($val) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Configuración -->
    <div class="lg:col-span-1 space-y-4">
        <div class="card card-pad" x-data="{color:'<?= $e($dept['color']) ?>', icon:'<?= $e($dept['icon']) ?>'}">
            <h3 class="font-display font-bold text-[14px] mb-3 flex items-center gap-2"><i class="lucide lucide-settings text-brand-600"></i> Configuración</h3>
            <form method="POST" action="<?= $url('/t/' . $slug . '/departments/' . $dept['id']) ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="label">Nombre</label>
                    <input name="name" required value="<?= $e($dept['name']) ?>" class="input">
                </div>
                <div>
                    <label class="label">Descripción</label>
                    <textarea name="description" rows="2" class="input"><?= $e($dept['description']) ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Color</label>
                        <input type="color" name="color" x-model="color" class="mt-1 w-full h-9 rounded-lg border border-[#ececef]">
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input name="email" type="email" value="<?= $e($dept['email']) ?>" class="input" placeholder="opcional">
                    </div>
                </div>
                <div>
                    <label class="label">Icono</label>
                    <div class="mt-1 grid grid-cols-8 gap-1">
                        <?php foreach ($icons as $ic): ?>
                            <button type="button" @click="icon='<?= $ic ?>'" :class="icon==='<?= $ic ?>' ? 'border-2' : 'border'" class="w-8 h-8 grid place-items-center rounded-lg transition" :style="icon==='<?= $ic ?>' ? 'background:'+color+'12;border-color:'+color+';color:'+color : 'border-color:#ececef;color:#8e8e9a'">
                                <i class="lucide lucide-<?= $ic ?> text-[12px]"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="icon" :value="icon">
                </div>
                <div>
                    <label class="label">Manager / Líder</label>
                    <select name="manager_user_id" class="input">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= (int)$dept['manager_user_id']===(int)$t['id']?'selected':'' ?>><?= $e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label">Orden</label>
                    <input name="sort_order" type="number" value="<?= (int)$dept['sort_order'] ?>" class="input">
                </div>
                <label class="flex items-center gap-2 text-[13px]">
                    <input type="checkbox" name="is_active" value="1" <?= (int)$dept['is_active']?'checked':'' ?>> Activo
                </label>
                <button class="btn btn-primary w-full"><i class="lucide lucide-save text-[12px]"></i> Guardar cambios</button>
            </form>
        </div>

        <?php if (!empty($slaPolicies)): ?>
            <div class="card card-pad">
                <h3 class="font-display font-bold text-[14px] mb-2 flex items-center gap-2"><i class="lucide lucide-gauge" style="color:#f59e0b"></i> SLAs propios</h3>
                <p class="text-[11.5px] text-ink-400 mb-3"><?= count($slaPolicies) ?> política(s) específica(s) para este departamento</p>
                <div class="space-y-2">
                    <?php foreach ($slaPolicies as $p): ?>
                        <div class="flex items-center gap-2 text-[12.5px]">
                            <span class="px-2 py-0.5 rounded-full font-semibold uppercase tracking-[0.1em]" style="background:#fffbeb;color:#b45309;font-size:10px"><?= $e($p['priority']) ?></span>
                            <span class="flex-1 truncate"><?= $e($p['name']) ?></span>
                            <span class="text-ink-400 font-mono"><?= (int)$p['response_minutes'] ?>m / <?= (int)$p['resolve_minutes'] ?>m</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= $url('/t/' . $slug . '/sla') ?>" class="text-[11.5px] mt-3 inline-flex items-center gap-1 font-semibold" style="color:#f59e0b">Gestionar SLAs <i class="lucide lucide-arrow-right text-[10px]"></i></a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Agentes y tickets -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Agentes -->
        <div class="card">
            <div class="card-pad flex items-center gap-3" style="border-bottom:1px solid #ececef">
                <div class="flex-1">
                    <h3 class="font-display font-bold text-[14px] flex items-center gap-2"><i class="lucide lucide-users text-brand-600"></i> Equipo del departamento</h3>
                    <p class="text-[11.5px] text-ink-400"><?= count($agents) ?> agente(s) asignado(s)</p>
                </div>
                <?php if (!empty($availableAgents)): ?>
                    <details class="relative" x-data x-cloak>
                        <summary class="btn btn-soft btn-sm cursor-pointer list-none"><i class="lucide lucide-user-plus text-[12px]"></i> Añadir agente</summary>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/departments/' . $dept['id'] . '/agents') ?>" class="absolute right-0 mt-2 w-[260px] z-10 card card-pad space-y-2" style="background:white;box-shadow:0 12px 28px -8px rgba(22,21,27,.18)">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <select name="user_id" required class="input">
                                <option value="">— Selecciona agente —</option>
                                <?php foreach ($availableAgents as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>"><?= $e($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="flex items-center gap-2 text-[12px]">
                                <input type="checkbox" name="is_lead" value="1"> Marcar como líder
                            </label>
                            <button class="btn btn-primary btn-sm w-full"><i class="lucide lucide-check text-[12px]"></i> Añadir</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
            <?php if (empty($agents)): ?>
                <div class="card-pad text-center py-12">
                    <div class="w-12 h-12 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-2"><i class="lucide lucide-users-round text-[20px] text-ink-400"></i></div>
                    <div class="font-display font-bold text-[13.5px]">Sin agentes asignados</div>
                    <p class="text-[12px] text-ink-400 mt-0.5">Añade técnicos para que reciban los tickets de este departamento</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-[#ececef]">
                    <?php foreach ($agents as $a): ?>
                        <div class="px-5 py-3 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full grid place-items-center font-display font-bold text-[13px]" style="background:<?= $e($dept['color']) ?>15;color:<?= $e($dept['color']) ?>"><?= strtoupper(substr($a['name'],0,1)) ?></div>
                            <div class="flex-1 min-w-0">
                                <div class="font-display font-bold text-[13.5px] flex items-center gap-2">
                                    <?= $e($a['name']) ?>
                                    <?php if ((int)$a['is_lead']): ?>
                                        <span class="text-[9.5px] uppercase tracking-[0.14em] px-1.5 py-0.5 rounded-full" style="background:#fef3c7;color:#92400e">Líder</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[11.5px] text-ink-400 truncate"><?= $e($a['email']) ?><?= !empty($a['title']) ? ' · '.$e($a['title']) : '' ?></div>
                            </div>
                            <div class="text-[11.5px] text-ink-500 hidden sm:block"><?= number_format((int)$a['tickets_in_dept']) ?> tickets</div>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/departments/' . $dept['id'] . '/agents/' . $a['id'] . '/lead') ?>" class="inline">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="btn btn-outline btn-sm" title="<?= (int)$a['is_lead'] ? 'Quitar como líder' : 'Marcar como líder' ?>"><i class="lucide lucide-<?= (int)$a['is_lead']?'star':'star-off' ?> text-[12px]"></i></button>
                            </form>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/departments/' . $dept['id'] . '/agents/' . $a['id'] . '/remove') ?>" onsubmit="return confirm('¿Quitar al agente del departamento?')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca" title="Quitar"><i class="lucide lucide-x text-[12px]"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tickets recientes -->
        <div class="card">
            <div class="card-pad flex items-center gap-3" style="border-bottom:1px solid #ececef">
                <div class="flex-1">
                    <h3 class="font-display font-bold text-[14px] flex items-center gap-2"><i class="lucide lucide-inbox" style="color:#3b82f6"></i> Tickets del departamento</h3>
                    <p class="text-[11.5px] text-ink-400">Últimos <?= count($tickets) ?> tickets · <?= number_format($stats['total']) ?> totales</p>
                </div>
                <a href="<?= $url('/t/' . $slug . '/tickets?department=' . $dept['id']) ?>" class="btn btn-soft btn-sm">Ver todos <i class="lucide lucide-arrow-right text-[12px]"></i></a>
            </div>
            <?php if (empty($tickets)): ?>
                <div class="card-pad text-center py-12">
                    <div class="w-12 h-12 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-2"><i class="lucide lucide-inbox text-[20px] text-ink-400"></i></div>
                    <div class="font-display font-bold text-[13.5px]">Aún no hay tickets</div>
                    <p class="text-[12px] text-ink-400 mt-0.5">Cuando se asigne un ticket a este departamento aparecerá aquí</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Asunto</th><th>Estado</th><th>Prioridad</th><th>Asignado</th></tr></thead>
                        <tbody>
                            <?php foreach ($tickets as $t):
                                $statusColor = ['open'=>'#f59e0b','in_progress'=>'#7c5cff','on_hold'=>'#6b7280','resolved'=>'#16a34a','closed'=>'#1f2937'][$t['status']] ?? '#6b7280';
                                $priorityColor = ['urgent'=>'#dc2626','high'=>'#f97316','medium'=>'#3b82f6','low'=>'#6b7280'][$t['priority']] ?? '#6b7280';
                            ?>
                                <tr>
                                    <td class="font-mono text-[12px]"><a href="<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>" style="color:inherit"><?= $e($t['code']) ?></a></td>
                                    <td class="text-[13px] max-w-[300px] truncate"><a href="<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>" style="color:inherit;font-weight:500"><?= $e($t['subject']) ?></a></td>
                                    <td><span class="badge" style="background:<?= $statusColor ?>15;color:<?= $statusColor ?>"><?= $e(ucfirst(str_replace('_',' ',$t['status']))) ?></span></td>
                                    <td><span class="badge" style="background:<?= $priorityColor ?>15;color:<?= $priorityColor ?>"><?= $e(ucfirst($t['priority'])) ?></span></td>
                                    <td class="text-[12.5px] text-ink-500"><?= $e($t['assigned_name'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
