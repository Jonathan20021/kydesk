<?php $slug = $tenant->slug;
$statusColor = [
    'operational' => ['#16a34a', 'Operacional'],
    'degraded' => ['#f59e0b', 'Degradado'],
    'partial_outage' => ['#f97316', 'Outage parcial'],
    'major_outage' => ['#dc2626', 'Outage mayor'],
    'maintenance' => ['#7c5cff', 'Mantenimiento'],
];
$incStatusColor = [
    'investigating' => ['#dc2626', 'Investigando'],
    'identified' => ['#f59e0b', 'Identificado'],
    'monitoring' => ['#0ea5e9', 'Monitoreando'],
    'resolved' => ['#16a34a', 'Resuelto'],
];
?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Status Page</h1>
        <p class="text-[13px] text-ink-400">Comunicá incidentes y disponibilidad a tus clientes</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= $e($publicUrl) ?>" target="_blank" class="btn btn-soft btn-sm"><i class="lucide lucide-external-link"></i> Ver pública</a>
    </div>
</div>

<div class="card card-pad mb-4 flex items-center gap-3 flex-wrap">
    <i class="lucide lucide-link text-[16px] text-brand-600"></i>
    <span class="text-[12.5px] text-ink-500">URL pública:</span>
    <code class="font-mono text-[12.5px] text-brand-700"><?= $e($publicUrl) ?></code>
    <span class="text-[12px] text-ink-400 ml-auto"><?= $subscribers ?> suscriptores</span>
</div>

<div x-data="{tab:'components'}">
    <div class="admin-tabs mb-4" style="background:white;border:1px solid var(--border);max-width:fit-content">
        <button @click="tab='components'" :class="tab==='components' && 'active'" class="admin-tab"><i class="lucide lucide-server text-[13px]"></i> Componentes (<?= count($components) ?>)</button>
        <button @click="tab='incidents'" :class="tab==='incidents' && 'active'" class="admin-tab"><i class="lucide lucide-alert-triangle text-[13px]"></i> Incidentes (<?= count($incidents) ?>)</button>
    </div>

    <!-- Components tab -->
    <div x-show="tab==='components'" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo componente</h3>
            <form method="POST" action="<?= $url('/t/' . $slug . '/status/components') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre</label><input name="name" required class="input" placeholder="Ej: API, Portal, Dashboard"></div>
                <div><label class="label">Descripción</label><input name="description" class="input"></div>
                <div><label class="label">Icono</label><input name="icon" class="input" value="server" placeholder="server, cloud, database…"></div>
                <div>
                    <label class="label">Estado</label>
                    <select name="status" class="input">
                        <?php foreach ($statusColor as $k => [,$lbl]): ?>
                            <option value="<?= $k ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-2">
            <?php if (empty($components)): ?>
                <div class="card card-pad text-center py-12">
                    <i class="lucide lucide-server text-[24px] text-ink-300"></i>
                    <h3 class="font-display font-bold mt-3">Sin componentes</h3>
                    <p class="text-[12.5px] text-ink-400 mt-1">Definí los servicios visibles en la status page.</p>
                </div>
            <?php else: foreach ($components as $c):
                [$col, $lbl] = $statusColor[$c['status']] ?? ['#6b7280', $c['status']];
            ?>
                <div class="card card-pad" x-data="{open:false}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="lucide lucide-<?= $e($c['icon']) ?> text-[16px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-[14px]"><?= $e($c['name']) ?></div>
                            <?php if (!empty($c['description'])): ?><div class="text-[12px] text-ink-500"><?= $e($c['description']) ?></div><?php endif; ?>
                        </div>
                        <span class="badge" style="background:<?= $col ?>15;color:<?= $col ?>;border:1px solid <?= $col ?>33"><?= $lbl ?></span>
                        <button @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i></button>
                    </div>
                    <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                        <form method="POST" action="<?= $url('/t/' . $slug . '/status/components/' . $c['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <input name="name" value="<?= $e($c['name']) ?>" class="input">
                            <select name="status" class="input">
                                <?php foreach ($statusColor as $k => [,$lblO]): ?>
                                    <option value="<?= $k ?>" <?= $c['status']===$k?'selected':'' ?>><?= $lblO ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="icon" value="<?= $e($c['icon']) ?>" class="input">
                            <input name="sort_order" type="number" value="<?= (int)$c['sort_order'] ?>" class="input">
                            <input name="description" value="<?= $e($c['description']) ?>" class="input md:col-span-2">
                            <label class="md:col-span-2 flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" <?= $c['is_active']?'checked':'' ?>> Activo</label>
                            <div class="md:col-span-2 flex justify-between gap-2">
                                <button type="button" onclick="if(confirm('Eliminar componente?')) document.getElementById('del-comp-<?= (int)$c['id'] ?>').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i></button>
                                <button class="btn btn-primary btn-sm">Guardar</button>
                            </div>
                        </form>
                        <form id="del-comp-<?= (int)$c['id'] ?>" method="POST" action="<?= $url('/t/' . $slug . '/status/components/' . $c['id'] . '/delete') ?>" style="display:none">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Incidents tab -->
    <div x-show="tab==='incidents'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-alert-triangle text-rose-600"></i> Reportar incidente</h3>
            <form method="POST" action="<?= $url('/t/' . $slug . '/status/incidents') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Título</label><input name="title" required class="input" placeholder="Ej: API caída"></div>
                <div><label class="label">Descripción inicial</label><textarea name="description" rows="3" class="input" placeholder="Estamos investigando un problema en…"></textarea></div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Severidad</label>
                        <select name="severity" class="input">
                            <option value="minor">Menor</option>
                            <option value="major">Mayor</option>
                            <option value="critical">Crítico</option>
                            <option value="maintenance">Mantenimiento</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Estado inicial</label>
                        <select name="status" class="input">
                            <option value="investigating">Investigando</option>
                            <option value="identified">Identificado</option>
                            <option value="monitoring">Monitoreando</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Componentes afectados</label>
                    <div class="space-y-1 max-h-32 overflow-y-auto p-2 rounded-lg" style="background:#fafafb;border:1px solid var(--border)">
                        <?php foreach ($components as $c): ?>
                            <label class="flex items-center gap-2 text-[12.5px]"><input type="checkbox" name="components[]" value="<?= (int)$c['id'] ?>"> <?= $e($c['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="label">Cambiar estado de componentes a</label>
                    <select name="component_status" class="input">
                        <option value="">— No cambiar —</option>
                        <?php foreach ($statusColor as $k => [,$lbl]): if ($k==='operational') continue; ?>
                            <option value="<?= $k ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="label">Inicio</label><input name="started_at" type="datetime-local" value="<?= date('Y-m-d\TH:i') ?>" class="input"></div>
                <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_public" value="1" checked> Público (notifica suscriptores)</label>
                <button class="btn btn-primary w-full"><i class="lucide lucide-megaphone"></i> Reportar incidente</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-3">
            <?php if (empty($incidents)): ?>
                <div class="card card-pad text-center py-12">
                    <i class="lucide lucide-shield-check text-[24px] text-emerald-600"></i>
                    <h3 class="font-display font-bold mt-3">Sin incidentes registrados</h3>
                </div>
            <?php else: foreach ($incidents as $i):
                [$col, $lbl] = $incStatusColor[$i['status']] ?? ['#6b7280', $i['status']];
            ?>
                <div class="card card-pad" x-data="{open:false}">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center shrink-0" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="lucide lucide-<?= $i['status']==='resolved'?'check-circle':'alert-triangle' ?> text-[16px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="font-display font-bold text-[14px]"><?= $e($i['title']) ?></div>
                                <span class="badge" style="background:<?= $col ?>15;color:<?= $col ?>"><?= $lbl ?></span>
                                <span class="badge badge-gray"><?= ucfirst($i['severity']) ?></span>
                                <?php if (!$i['is_public']): ?><span class="badge badge-amber">Privado</span><?php endif; ?>
                            </div>
                            <div class="text-[11.5px] text-ink-400 mt-1"><?= $e($i['started_at']) ?> <?= $i['resolved_at']?'· Resuelto: '.$e($i['resolved_at']):'' ?></div>
                            <?php if (!empty($i['description'])): ?><p class="text-[12.5px] text-ink-500 mt-1.5 line-clamp-2"><?= $e($i['description']) ?></p><?php endif; ?>
                        </div>
                        <button @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-message-square text-[12px]"></i> Update</button>
                    </div>
                    <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                        <form method="POST" action="<?= $url('/t/' . $slug . '/status/incidents/' . $i['id']) ?>" class="space-y-2">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <select name="status" class="input">
                                <?php foreach ($incStatusColor as $k=>[,$lblO]): ?>
                                    <option value="<?= $k ?>" <?= $i['status']===$k?'selected':'' ?>><?= $lblO ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="body" rows="2" required class="input" placeholder="Mensaje de update…"></textarea>
                            <div class="flex justify-between gap-2">
                                <button type="button" onclick="if(confirm('Eliminar incidente?')) document.getElementById('del-inc-<?= (int)$i['id'] ?>').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i></button>
                                <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Publicar update</button>
                            </div>
                        </form>
                        <form id="del-inc-<?= (int)$i['id'] ?>" method="POST" action="<?= $url('/t/' . $slug . '/status/incidents/' . $i['id'] . '/delete') ?>" style="display:none">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
