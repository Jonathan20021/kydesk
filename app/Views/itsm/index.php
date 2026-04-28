<?php $slug = $tenant->slug;
$changeStatusMap = [
    'draft' => ['#6b7280','Borrador'],
    'pending_approval' => ['#f59e0b','Pendiente'],
    'approved' => ['#16a34a','Aprobado'],
    'rejected' => ['#dc2626','Rechazado'],
    'scheduled' => ['#3b82f6','Programado'],
    'in_progress' => ['#0ea5e9','En curso'],
    'completed' => ['#16a34a','Completado'],
    'cancelled' => ['#6b7280','Cancelado'],
    'failed' => ['#dc2626','Fallido'],
];
$problemStatusMap = [
    'new' => ['#dc2626','Nuevo'],
    'investigating' => ['#f59e0b','Investigando'],
    'known_error' => ['#7c5cff','Known Error'],
    'resolved' => ['#16a34a','Resuelto'],
    'closed' => ['#6b7280','Cerrado'],
];
?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="badge badge-purple"><i class="lucide lucide-workflow"></i> ITSM · ITIL</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">IT Service Management</h1>
        <p class="text-[13px] text-ink-400">Catálogo de servicios · Change Management · Problems · Aprobaciones</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Catálogo</div><div class="font-display font-extrabold text-[26px]"><?= $stats['catalog'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Changes pendientes</div><div class="font-display font-extrabold text-[26px] text-amber-600"><?= $stats['changes_pending'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Changes activos</div><div class="font-display font-extrabold text-[26px] text-blue-600"><?= $stats['changes_active'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Problems abiertos</div><div class="font-display font-extrabold text-[26px] text-rose-600"><?= $stats['problems_open'] ?></div></div>
</div>

<div x-data="{tab:'catalog'}">
    <div class="admin-tabs mb-4" style="background:white;border:1px solid var(--border);max-width:fit-content">
        <button @click="tab='catalog'" :class="tab==='catalog' && 'active'" class="admin-tab"><i class="lucide lucide-package text-[13px]"></i> Service Catalog (<?= count($catalog) ?>)</button>
        <button @click="tab='changes'" :class="tab==='changes' && 'active'" class="admin-tab"><i class="lucide lucide-git-pull-request text-[13px]"></i> Changes (<?= count($changes) ?>)</button>
        <button @click="tab='problems'" :class="tab==='problems' && 'active'" class="admin-tab"><i class="lucide lucide-bug text-[13px]"></i> Problems (<?= count($problems) ?>)</button>
    </div>

    <!-- Service Catalog -->
    <div x-show="tab==='catalog'" x-data="{editing:null}" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo item</h3>
            <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/catalog') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre</label><input name="name" required class="input" placeholder="Ej: Solicitar VPN, Reset password"></div>
                <div><label class="label">Descripción</label><textarea name="description" rows="2" class="input" placeholder="Lo que verá el solicitante"></textarea></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">Icono</label><input name="icon" value="package" class="input"></div>
                    <div><label class="label">Color</label><input name="color" type="color" value="#7c5cff" class="input" style="height:42px"></div>
                </div>
                <div>
                    <label class="label">Categoría</label>
                    <select name="category_id" class="input">
                        <option value="">— Ninguna —</option>
                        <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($departments)): ?>
                    <div>
                        <label class="label">Departamento</label>
                        <select name="department_id" class="input">
                            <option value="">—</option>
                            <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"><?= $e($d['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div><label class="label">SLA (minutos)</label><input name="sla_minutes" type="number" min="0" class="input" placeholder="Ej: 240"></div>
                <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="requires_approval" value="1"> Requiere aprobación</label>
                <div>
                    <label class="label">Aprobador</label>
                    <select name="approver_user_id" class="input">
                        <option value="">— Sin aprobador específico —</option>
                        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= $e($u['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <label class="flex items-start gap-2 text-[13px] p-3 rounded-xl border border-dashed border-[#cdbfff] bg-brand-50/40 cursor-pointer">
                    <input type="checkbox" name="is_public" value="1" class="mt-0.5">
                    <span><strong>Visible en portal público</strong><span class="block text-[11px] text-ink-500 mt-0.5">Si lo tildás, cualquier visitante del portal puede solicitarlo. Si no, solo aparecerá en el portal de empresa autenticado y en el form interno.</span></span>
                </label>
                <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear item</button>
            </form>
        </div>

        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($catalog as $s): ?>
                <div class="card card-pad relative" :class="editing === <?= (int)$s['id'] ?> && 'ring-2 ring-brand-300'">
                    <div x-show="editing !== <?= (int)$s['id'] ?>" class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-2xl grid place-items-center flex-shrink-0" style="background:<?= $e($s['color']) ?>15;color:<?= $e($s['color']) ?>"><i class="lucide lucide-<?= $e($s['icon']) ?> text-[18px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="font-display font-bold text-[14px]"><?= $e($s['name']) ?></div>
                                <?php if (!empty($s['is_public'])): ?>
                                    <span class="badge badge-green" data-tooltip="Visible en portal público"><i class="lucide lucide-globe text-[10px]"></i> Público</span>
                                <?php else: ?>
                                    <span class="badge badge-gray" data-tooltip="Solo portal interno + portal de empresa"><i class="lucide lucide-lock text-[10px]"></i> Interno</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($s['description'])): ?><p class="text-[12px] text-ink-500 mt-0.5 line-clamp-2"><?= $e($s['description']) ?></p><?php endif; ?>
                            <div class="flex items-center gap-2 mt-2 flex-wrap text-[11px]">
                                <?php if ($s['requires_approval']): ?><span class="badge badge-amber">Aprobación requerida</span><?php endif; ?>
                                <?php if (!empty($s['sla_minutes'])): ?><span class="badge badge-blue">SLA <?= (int)$s['sla_minutes'] ?>min</span><?php endif; ?>
                                <?php if (!empty($s['category_name'])): ?><span class="text-ink-500"><?= $e($s['category_name']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <button type="button" @click="editing=<?= (int)$s['id'] ?>" class="btn btn-soft btn-xs" data-tooltip="Editar"><i class="lucide lucide-pencil text-[11px]"></i></button>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/catalog/' . (int)$s['id'] . '/visibility') ?>" data-tooltip="<?= !empty($s['is_public']) ? 'Ocultar del público' : 'Hacer público' ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="btn btn-soft btn-xs"><i class="lucide lucide-<?= !empty($s['is_public']) ? 'eye-off' : 'globe' ?> text-[11px]"></i></button>
                            </form>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/catalog/' . (int)$s['id'] . '/delete') ?>" onsubmit="return confirm('Eliminar item?')" data-tooltip="Eliminar">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="btn btn-soft btn-xs" style="color:#b91c1c"><i class="lucide lucide-trash-2 text-[11px]"></i></button>
                            </form>
                        </div>
                    </div>

                    <form x-show="editing === <?= (int)$s['id'] ?>" x-cloak method="POST" action="<?= $url('/t/' . $slug . '/itsm/catalog/' . (int)$s['id']) ?>" class="space-y-2">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <div><label class="label">Nombre</label><input name="name" required class="input" value="<?= $e($s['name']) ?>"></div>
                        <div><label class="label">Descripción</label><textarea name="description" rows="2" class="input"><?= $e($s['description']) ?></textarea></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="label">Icono</label><input name="icon" value="<?= $e($s['icon']) ?>" class="input"></div>
                            <div><label class="label">Color</label><input name="color" type="color" value="<?= $e($s['color']) ?>" class="input" style="height:42px"></div>
                        </div>
                        <div>
                            <label class="label">Categoría</label>
                            <select name="category_id" class="input">
                                <option value="">— Ninguna —</option>
                                <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$s['category_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($departments)): ?>
                            <div>
                                <label class="label">Departamento</label>
                                <select name="department_id" class="input">
                                    <option value="">—</option>
                                    <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)$s['department_id']===(int)$d['id']?'selected':'' ?>><?= $e($d['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div><label class="label">SLA (minutos)</label><input name="sla_minutes" type="number" min="0" class="input" value="<?= $s['sla_minutes']?(int)$s['sla_minutes']:'' ?>"></div>
                        <div>
                            <label class="label">Aprobador</label>
                            <select name="approver_user_id" class="input">
                                <option value="">— Sin aprobador específico —</option>
                                <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)$s['approver_user_id']===(int)$u['id']?'selected':'' ?>><?= $e($u['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="flex items-center gap-2 text-[12.5px]"><input type="checkbox" name="requires_approval" value="1" <?= !empty($s['requires_approval'])?'checked':'' ?>> Requiere aprobación</label>
                            <label class="flex items-center gap-2 text-[12.5px]"><input type="checkbox" name="is_active" value="1" <?= !empty($s['is_active'])?'checked':'' ?>> Activo</label>
                            <label class="flex items-center gap-2 text-[12.5px]"><input type="checkbox" name="is_public" value="1" <?= !empty($s['is_public'])?'checked':'' ?>> Visible en portal público</label>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button type="button" @click="editing=null" class="btn btn-soft btn-sm flex-1">Cancelar</button>
                            <button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-save text-[12px]"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (empty($catalog)): ?>
                <div class="sm:col-span-2 card card-pad text-center py-12">
                    <i class="lucide lucide-package text-[24px] text-ink-300"></i>
                    <h3 class="font-display font-bold mt-3">Catálogo vacío</h3>
                    <p class="text-[12.5px] text-ink-400 mt-1">Creá el primer item del catálogo para que tus solicitantes puedan pedir servicios.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Changes -->
    <div x-show="tab==='changes'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo Change</h3>
            <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/changes') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Título</label><input name="title" required class="input"></div>
                <div><label class="label">Descripción</label><textarea name="description" rows="2" class="input"></textarea></div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="label">Tipo</label>
                        <select name="type" class="input">
                            <option value="standard">Estándar</option>
                            <option value="normal" selected>Normal</option>
                            <option value="emergency">Emergencia</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Riesgo</label>
                        <select name="risk" class="input">
                            <option value="low">Bajo</option>
                            <option value="medium" selected>Medio</option>
                            <option value="high">Alto</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Impacto</label>
                        <select name="impact" class="input">
                            <option value="low">Bajo</option>
                            <option value="medium" selected>Medio</option>
                            <option value="high">Alto</option>
                        </select>
                    </div>
                </div>
                <div><label class="label">Asignado a</label>
                    <select name="assignee_id" class="input">
                        <option value="">—</option>
                        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= $e($u['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="label">Aprobador (opcional)</label>
                    <select name="approver_user_id" class="input">
                        <option value="">— Sin aprobación —</option>
                        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= $e($u['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">Inicio planeado</label><input name="planned_start" type="datetime-local" class="input"></div>
                    <div><label class="label">Fin planeado</label><input name="planned_end" type="datetime-local" class="input"></div>
                </div>
                <div><label class="label">Servicios afectados</label><input name="affected_services" class="input" placeholder="API, Base de datos, ..."></div>
                <div><label class="label">Plan de rollback</label><textarea name="rollback_plan" rows="2" class="input"></textarea></div>
                <div><label class="label">Plan de pruebas</label><textarea name="test_plan" rows="2" class="input"></textarea></div>
                <button class="btn btn-primary w-full"><i class="lucide lucide-git-pull-request"></i> Crear Change</button>
            </form>
        </div>

        <div class="lg:col-span-2 card overflow-hidden">
            <table class="admin-table">
                <thead><tr><th>Código</th><th>Título</th><th>Tipo</th><th>Riesgo</th><th>Estado</th><th>Solicitante</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($changes as $c):
                    [$col, $lbl] = $changeStatusMap[$c['status']] ?? ['#6b7280', $c['status']];
                ?>
                    <tr>
                        <td class="font-mono text-[12px]"><a href="<?= $url('/t/' . $slug . '/itsm/changes/' . (int)$c['id']) ?>" class="text-brand-700"><?= $e($c['code']) ?></a></td>
                        <td class="text-[13px]"><?= $e($c['title']) ?></td>
                        <td><span class="badge badge-gray"><?= ucfirst($c['type']) ?></span></td>
                        <td><span class="badge badge-<?= ['low'=>'green','medium'=>'amber','high'=>'red'][$c['risk']] ?? 'gray' ?>"><?= ucfirst($c['risk']) ?></span></td>
                        <td><span class="badge" style="background:<?= $col ?>15;color:<?= $col ?>"><?= $lbl ?></span></td>
                        <td class="text-[12px]"><?= $e($c['requester_name'] ?? '—') ?></td>
                        <td><a href="<?= $url('/t/' . $slug . '/itsm/changes/' . (int)$c['id']) ?>" class="btn btn-soft btn-xs"><i class="lucide lucide-arrow-right text-[12px]"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($changes)): ?><tr><td colspan="7" style="text-align:center;padding:20px;color:#8e8e9a">Sin changes registrados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Problems -->
    <div x-show="tab==='problems'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo Problem</h3>
            <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/problems') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Título</label><input name="title" required class="input"></div>
                <div><label class="label">Descripción</label><textarea name="description" rows="3" class="input"></textarea></div>
                <div>
                    <label class="label">Prioridad</label>
                    <select name="priority" class="input">
                        <option value="low">Baja</option>
                        <option value="medium" selected>Media</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="label">Asignado a</label>
                    <select name="assignee_id" class="input">
                        <option value="">—</option>
                        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= $e($u['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-full"><i class="lucide lucide-bug"></i> Crear Problem</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-2">
            <?php foreach ($problems as $p):
                [$col, $lbl] = $problemStatusMap[$p['status']] ?? ['#6b7280', $p['status']];
            ?>
                <div class="card card-pad" x-data="{open:false}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="lucide lucide-bug text-[16px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-[11.5px] text-ink-500"><?= $e($p['code']) ?></span>
                                <div class="font-display font-bold text-[14px]"><?= $e($p['title']) ?></div>
                                <span class="badge" style="background:<?= $col ?>15;color:<?= $col ?>"><?= $lbl ?></span>
                                <span class="badge badge-gray"><?= ucfirst($p['priority']) ?></span>
                            </div>
                            <?php if (!empty($p['assignee_name'])): ?><div class="text-[11.5px] text-ink-500 mt-1"><?= $e($p['assignee_name']) ?></div><?php endif; ?>
                        </div>
                        <button @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i></button>
                    </div>
                    <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                        <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/problems/' . (int)$p['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <input name="title" value="<?= $e($p['title']) ?>" class="input md:col-span-2">
                            <select name="status" class="input">
                                <?php foreach ($problemStatusMap as $k=>[,$lblO]): ?>
                                    <option value="<?= $k ?>" <?= $p['status']===$k?'selected':'' ?>><?= $lblO ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="priority" class="input">
                                <?php foreach (['low'=>'Baja','medium'=>'Media','high'=>'Alta','urgent'=>'Urgente'] as $k=>$lblO): ?>
                                    <option value="<?= $k ?>" <?= $p['priority']===$k?'selected':'' ?>><?= $lblO ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="description" rows="2" class="input md:col-span-2" placeholder="Descripción"><?= $e($p['description']) ?></textarea>
                            <textarea name="root_cause" rows="2" class="input md:col-span-2" placeholder="Root cause"><?= $e($p['root_cause']) ?></textarea>
                            <textarea name="workaround" rows="2" class="input md:col-span-2" placeholder="Workaround"><?= $e($p['workaround']) ?></textarea>
                            <div class="md:col-span-2 flex justify-end"><button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button></div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($problems)): ?>
                <div class="card card-pad text-center py-12">
                    <i class="lucide lucide-bug text-[24px] text-ink-300"></i>
                    <h3 class="font-display font-bold mt-3">Sin problems</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
