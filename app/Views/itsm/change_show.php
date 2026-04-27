<?php $slug = $tenant->slug;
$statusMap = [
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
[$stCol, $stLbl] = $statusMap[$change['status']] ?? ['#6b7280', $change['status']];
$myUserId = $auth->userId();
$myApproval = null;
foreach ($approvals as $a) if ((int)$a['approver_id'] === $myUserId) $myApproval = $a;
?>

<div class="mb-4">
    <a href="<?= $url('/t/' . $slug . '/itsm') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a ITSM</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-12 h-12 rounded-2xl grid place-items-center" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><i class="lucide lucide-git-pull-request text-[20px]"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-[12px] text-ink-500"><?= $e($change['code']) ?></span>
                        <span class="badge" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><?= $stLbl ?></span>
                        <span class="badge badge-gray">Tipo: <?= ucfirst($change['type']) ?></span>
                        <span class="badge badge-<?= ['low'=>'green','medium'=>'amber','high'=>'red'][$change['risk']] ?? 'gray' ?>">Riesgo: <?= ucfirst($change['risk']) ?></span>
                        <span class="badge badge-<?= ['low'=>'green','medium'=>'amber','high'=>'red'][$change['impact']] ?? 'gray' ?>">Impacto: <?= ucfirst($change['impact']) ?></span>
                    </div>
                    <h1 class="font-display font-extrabold text-[22px] tracking-[-0.02em] mt-1"><?= $e($change['title']) ?></h1>
                    <div class="text-[12.5px] text-ink-500 mt-1">Solicitante: <?= $e($change['requester_name'] ?? '—') ?> · Asignado: <?= $e($change['assignee_name'] ?? '—') ?></div>
                </div>
            </div>
            <?php if (!empty($change['description'])): ?>
                <div class="mb-3"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em] mb-1">Descripción</div><div class="text-[13px] whitespace-pre-wrap"><?= $e($change['description']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($change['affected_services'])): ?>
                <div class="mb-3"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em] mb-1">Servicios afectados</div><div class="text-[13px]"><?= $e($change['affected_services']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($change['rollback_plan'])): ?>
                <div class="mb-3"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em] mb-1">Plan de rollback</div><div class="text-[13px] whitespace-pre-wrap"><?= $e($change['rollback_plan']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($change['test_plan'])): ?>
                <div><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em] mb-1">Plan de pruebas</div><div class="text-[13px] whitespace-pre-wrap"><?= $e($change['test_plan']) ?></div></div>
            <?php endif; ?>

            <?php if ($change['planned_start'] || $change['planned_end']): ?>
                <div class="grid grid-cols-2 gap-3 mt-4 pt-3" style="border-top:1px solid var(--border)">
                    <div><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Inicio planeado</div><div class="text-[13px] font-mono mt-0.5"><?= $e($change['planned_start'] ?: '—') ?></div></div>
                    <div><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Fin planeado</div><div class="text-[13px] font-mono mt-0.5"><?= $e($change['planned_end'] ?: '—') ?></div></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit form -->
        <div class="card card-pad" x-data="{open:false}">
            <button @click="open=!open" class="font-display font-bold text-[14px] flex items-center gap-2"><i class="lucide lucide-pencil text-[14px]"></i> Editar Change</button>
            <div x-show="open" x-cloak class="mt-3">
                <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/changes/' . (int)$change['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div class="md:col-span-2"><label class="label">Título</label><input name="title" value="<?= $e($change['title']) ?>" class="input"></div>
                    <div class="md:col-span-2"><label class="label">Descripción</label><textarea name="description" rows="3" class="input"><?= $e($change['description']) ?></textarea></div>
                    <div>
                        <label class="label">Estado</label>
                        <select name="status" class="input">
                            <?php foreach ($statusMap as $k=>[,$lblO]): ?>
                                <option value="<?= $k ?>" <?= $change['status']===$k?'selected':'' ?>><?= $lblO ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Asignado a</label>
                        <select name="assignee_id" class="input">
                            <option value="">—</option>
                            <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)$change['assignee_id']===(int)$u['id']?'selected':'' ?>><?= $e($u['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="label">Inicio planeado</label><input name="planned_start" type="datetime-local" value="<?= $change['planned_start']?str_replace(' ','T',$change['planned_start']):'' ?>" class="input"></div>
                    <div><label class="label">Fin planeado</label><input name="planned_end" type="datetime-local" value="<?= $change['planned_end']?str_replace(' ','T',$change['planned_end']):'' ?>" class="input"></div>
                    <div class="md:col-span-2"><label class="label">Plan de rollback</label><textarea name="rollback_plan" rows="2" class="input"><?= $e($change['rollback_plan']) ?></textarea></div>
                    <div class="md:col-span-2"><label class="label">Plan de pruebas</label><textarea name="test_plan" rows="2" class="input"><?= $e($change['test_plan']) ?></textarea></div>
                    <div class="md:col-span-2 flex justify-end"><button class="btn btn-primary btn-sm">Guardar</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Approvals sidebar -->
    <div class="space-y-3">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-check-circle text-emerald-600"></i> Aprobaciones</h3>
            <?php if (empty($approvals)): ?>
                <p class="text-[12.5px] text-ink-400">Sin aprobadores asignados.</p>
            <?php else: foreach ($approvals as $a):
                $aClass = $a['status'] === 'approved' ? 'badge-green' : ($a['status'] === 'rejected' ? 'badge-red' : 'badge-amber');
                $aLbl = ['pending'=>'Pendiente','approved'=>'Aprobado','rejected'=>'Rechazado'][$a['status']];
            ?>
                <div class="py-2 flex items-center gap-2 border-b border-[#ececef] last:border-0">
                    <div class="w-8 h-8 rounded-full text-white grid place-items-center text-[11px] font-bold" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)"><?= $e(strtoupper(substr($a['approver_name'],0,1))) ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-display font-bold"><?= $e($a['approver_name']) ?></div>
                        <span class="badge <?= $aClass ?>"><?= $aLbl ?></span>
                        <?php if ($a['decided_at']): ?><span class="text-[10.5px] text-ink-400"><?= $e($a['decided_at']) ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($a['comment'])): ?>
                    <div class="text-[11.5px] text-ink-500 ml-10 mb-2">"<?= $e($a['comment']) ?>"</div>
                <?php endif; ?>
            <?php endforeach; endif; ?>

            <?php if ($myApproval && $myApproval['status'] === 'pending'): ?>
                <div class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                    <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-500 mb-2">Tu decisión</div>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/itsm/approvals/' . (int)$myApproval['id']) ?>" class="space-y-2">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <textarea name="comment" rows="2" class="input" placeholder="Comentario (opcional)"></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <button name="decision" value="approved" class="btn btn-primary btn-sm"><i class="lucide lucide-check"></i> Aprobar</button>
                            <button name="decision" value="rejected" class="btn btn-outline btn-sm" style="color:#b91c1c;border-color:#fecaca"><i class="lucide lucide-x"></i> Rechazar</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
