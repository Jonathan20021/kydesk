<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$leadId = (int)$lead['id'];
$statusLabels = [
    'new'         => ['Nuevo',       '#94a3b8'],
    'contacted'   => ['Contactado',  '#3b82f6'],
    'qualified'   => ['Calificado',  '#0ea5e9'],
    'proposal'    => ['Propuesta',   '#a78bfa'],
    'negotiation' => ['Negociación', '#f59e0b'],
    'customer'    => ['Cliente',     '#16a34a'],
    'lost'        => ['Perdido',     '#ef4444'],
    'archived'    => ['Archivado',   '#6b7280'],
];
$ratingLabels = [
    'cold' => ['Frío', '#3b82f6', 'snowflake'],
    'warm' => ['Tibio', '#f59e0b', 'flame'],
    'hot'  => ['Caliente', '#ef4444', 'flame-kindling'],
];
$activityIcons = [
    'call'=>'phone','email'=>'mail','meeting'=>'calendar','task'=>'check-square',
    'whatsapp'=>'message-circle','sms'=>'smartphone','note_event'=>'sticky-note',
];
[$sl, $sCol] = $statusLabels[$lead['status']] ?? ['—', '#6b7280'];
[$rl, $rCol, $rIc] = $ratingLabels[$lead['rating']] ?? ['—', '#6b7280', 'circle'];
$isCustomer = $lead['status'] === 'customer';
$tagIds = array_column($tags, 'id');

$stagesByPipeline = [];
foreach ($stages as $st) $stagesByPipeline[(int)$st['pipeline_id']][] = $st;
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver a leads</a>
        <div class="flex items-center gap-3">
            <div class="avatar avatar-md" style="background:<?= Helpers::colorFor($lead['email'] ?? $lead['code']) ?>;color:white"><?= Helpers::initials(trim(($lead['first_name']??'').' '.($lead['last_name']??''))) ?></div>
            <div>
                <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]"><?= $e(trim(($lead['first_name']??'') . ' ' . ($lead['last_name']??''))) ?></h1>
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <span class="font-mono text-[11px] text-ink-500"><?= $e($lead['code']) ?></span>
                    <span class="text-ink-300">·</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $sCol ?>1a;color:<?= $sCol ?>"><?= $e($sl) ?></span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $rCol ?>1a;color:<?= $rCol ?>"><i class="lucide lucide-<?= $rIc ?> text-[10px]"></i> <?= $e($rl) ?></span>
                    <?php foreach ($tags as $t): ?>
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:<?= $e($t['color']) ?>1a;color:<?= $e($t['color']) ?>"><i class="lucide lucide-tag text-[10px]"></i> <?= $e($t['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php if (!$isCustomer && $auth->can('crm.convert')): ?>
            <button type="button" onclick="document.getElementById('convertModal').classList.remove('hidden')" class="btn btn-sm" style="background:linear-gradient(135deg,#16a34a,#10b981);color:white"><i class="lucide lucide-user-check"></i> Convertir a cliente</button>
        <?php endif; ?>
        <?php if ($auth->can('crm.delete')): ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/crm/leads/' . $leadId . '/delete') ?>" onsubmit="return confirm('¿Eliminar lead y todo su historial? Esta acción no se puede deshacer.')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-soft btn-sm" style="color:#dc2626"><i class="lucide lucide-trash-2"></i> Eliminar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($isCustomer): ?>
<div class="card card-pad mb-4 flex items-center gap-3" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border-color:#a7f3d0">
    <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:#16a34a;color:white"><i class="lucide lucide-check-circle-2"></i></div>
    <div class="flex-1">
        <div class="font-display font-bold text-[13.5px]">Lead convertido a cliente</div>
        <div class="text-[11.5px] text-ink-500">Convertido el <?= $e(date('d M Y, H:i', strtotime($lead['converted_at'] ?? 'now'))) ?>. <?php if ($lead['linked_company_name']): ?>Empresa: <strong><?= $e($lead['linked_company_name']) ?></strong>.<?php endif; ?></div>
    </div>
    <?php if ($lead['linked_company_name']): ?>
        <a href="<?= $url('/t/' . $slug . '/companies/' . (int)$lead['company_id']) ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-building-2"></i> Ver empresa</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $url('/t/' . $slug . '/crm/leads/' . $leadId) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-user text-brand-600"></i> Datos del contacto</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre</label><input class="input" name="first_name" value="<?= $e($lead['first_name']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Apellido</label><input class="input" name="last_name" value="<?= $e($lead['last_name'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email</label><input class="input" type="email" name="email" value="<?= $e($lead['email'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Teléfono</label><input class="input" name="phone" value="<?= $e($lead['phone'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">WhatsApp</label><input class="input" name="whatsapp" value="<?= $e($lead['whatsapp'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Cargo</label><input class="input" name="job_title" value="<?= $e($lead['job_title'] ?? '') ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-building-2 text-amber-600"></i> Empresa</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Empresa registrada</label>
                    <select class="input" name="company_id">
                        <option value="0">— Sin vincular —</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$lead['company_id'] === (int)$c['id'] ? 'selected':'' ?>><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre de empresa (libre)</label><input class="input" name="company_name" value="<?= $e($lead['company_name'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Sitio web</label><input class="input" name="website" value="<?= $e($lead['website'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Industria</label><input class="input" name="industry" value="<?= $e($lead['industry'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">País</label><input class="input" name="country" value="<?= $e($lead['country'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Ciudad</label><input class="input" name="city" value="<?= $e($lead['city'] ?? '') ?>"></div>
                <div class="sm:col-span-2"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Dirección</label><input class="input" name="address" value="<?= $e($lead['address'] ?? '') ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-message-square-text text-emerald-600"></i> Notas internas del lead</div>
            <textarea class="input" name="notes" rows="4"><?= $e($lead['notes'] ?? '') ?></textarea>
            <label class="inline-flex items-center gap-2 mt-3 text-[12px] cursor-pointer"><input type="checkbox" name="consent_marketing" value="1" <?= (int)$lead['consent_marketing']===1?'checked':'' ?> class="rounded"> Consintió comunicaciones comerciales</label>
        </div>

        <!-- DEALS -->
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-3">
                <div class="font-display font-bold text-[14.5px] flex items-center gap-2"><i class="lucide lucide-target text-rose-600"></i> Oportunidades (<?= count($deals) ?>)</div>
                <?php if ($auth->can('crm.create')): ?>
                <button type="button" onclick="document.getElementById('dealModal').classList.remove('hidden')" class="btn btn-soft btn-sm"><i class="lucide lucide-plus"></i> Nueva</button>
                <?php endif; ?>
            </div>
            <?php if (empty($deals)): ?>
                <div class="text-[12.5px] text-ink-400 py-4 text-center">Sin oportunidades aún. Crea la primera para empezar a mover este lead por el pipeline.</div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($deals as $d): ?>
                        <div class="border border-[#ececef] rounded-xl p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 text-[10.5px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $e($d['pipeline_color']) ?>1a;color:<?= $e($d['pipeline_color']) ?>"><?= $e($d['pipeline_name']) ?></span>
                                        <span class="inline-flex items-center gap-1 text-[10.5px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $e($d['stage_color']) ?>1a;color:<?= $e($d['stage_color']) ?>"><?= $e($d['stage_name']) ?></span>
                                        <?php if ($d['won_at']): ?><span class="text-[10px] font-bold text-emerald-700">✓ Ganada</span><?php endif; ?>
                                        <?php if ($d['lost_at']): ?><span class="text-[10px] font-bold text-rose-700">✗ Perdida</span><?php endif; ?>
                                    </div>
                                    <div class="font-display font-bold text-[13.5px] mt-1"><?= $e($d['title']) ?></div>
                                    <?php if (!empty($d['description'])): ?>
                                        <div class="text-[11.5px] text-ink-500 mt-0.5 line-clamp-2"><?= $e($d['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-extrabold text-[15px]">$<?= number_format((float)$d['amount'], 0) ?></div>
                                    <div class="text-[10px] text-ink-400"><?= (int)$d['probability'] ?>% prob · <?= $d['expected_close_on'] ? date('d M Y', strtotime($d['expected_close_on'])) : 'Sin fecha' ?></div>
                                </div>
                            </div>
                            <details class="mt-2">
                                <summary class="text-[11px] text-brand-700 cursor-pointer font-semibold">Editar oportunidad</summary>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/deals/' . (int)$d['id']) ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-3 pt-3 border-t border-[#ececef]" onclick="event.stopPropagation()">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <div class="sm:col-span-3"><label class="text-[10px] font-bold uppercase text-ink-400 block">Título</label><input class="input" name="title" value="<?= $e($d['title']) ?>"></div>
                                    <div><label class="text-[10px] font-bold uppercase text-ink-400 block">Etapa</label>
                                        <select class="input" name="stage_id">
                                            <?php foreach ($stagesByPipeline[(int)$d['pipeline_id']] ?? [] as $st): ?>
                                                <option value="<?= (int)$st['id'] ?>" <?= (int)$d['stage_id']===(int)$st['id']?'selected':'' ?>><?= $e($st['name']) ?> · <?= (int)$st['probability'] ?>%</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div><label class="text-[10px] font-bold uppercase text-ink-400 block">Monto</label><input type="number" step="0.01" class="input" name="amount" value="<?= $e($d['amount']) ?>"></div>
                                    <div><label class="text-[10px] font-bold uppercase text-ink-400 block">Cierre esp.</label><input type="date" class="input" name="expected_close_on" value="<?= $e($d['expected_close_on'] ?? '') ?>"></div>
                                    <div class="sm:col-span-3"><label class="text-[10px] font-bold uppercase text-ink-400 block">Descripción</label><textarea class="input" name="description" rows="2"><?= $e($d['description'] ?? '') ?></textarea></div>
                                    <div class="sm:col-span-3"><label class="text-[10px] font-bold uppercase text-ink-400 block">Razón si se perdió</label><input class="input" name="lost_reason" value="<?= $e($d['lost_reason'] ?? '') ?>"></div>
                                    <div class="sm:col-span-3 flex justify-between items-center">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button>
                                        <?php if ($auth->can('crm.delete')): ?>
                                        <button type="button" onclick="if(confirm('¿Eliminar oportunidad?')){this.closest('details').querySelector('.dealDeleteForm').submit()}" class="text-[11px] text-rose-600 font-semibold">Eliminar</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                <?php if ($auth->can('crm.delete')): ?>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/deals/' . (int)$d['id'] . '/delete') ?>" class="dealDeleteForm hidden">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                </form>
                                <?php endif; ?>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ACTIVITIES -->
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-3">
                <div class="font-display font-bold text-[14.5px] flex items-center gap-2"><i class="lucide lucide-clock text-sky-600"></i> Actividades</div>
            </div>
            <?php if ($auth->can('crm.edit')): ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/crm/activities') ?>" class="grid grid-cols-1 sm:grid-cols-6 gap-2 mb-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="lead_id" value="<?= $leadId ?>">
                <select class="input sm:col-span-1" name="type">
                    <option value="task">Tarea</option>
                    <option value="call">Llamada</option>
                    <option value="email">Email</option>
                    <option value="meeting">Reunión</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="sms">SMS</option>
                </select>
                <input class="input sm:col-span-2" name="subject" placeholder="Asunto…" required>
                <input type="datetime-local" class="input sm:col-span-2" name="scheduled_at">
                <button class="btn btn-primary btn-sm sm:col-span-1"><i class="lucide lucide-plus"></i> Agendar</button>
            </form>
            <?php endif; ?>
            <?php if (empty($activities)): ?>
                <div class="text-[12.5px] text-ink-400 py-4 text-center">Sin actividades registradas.</div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($activities as $a):
                        $ic = $activityIcons[$a['type']] ?? 'circle';
                        $when = $a['scheduled_at'] ? date('d M Y, H:i', strtotime($a['scheduled_at'])) : ($a['completed_at'] ? date('d M Y, H:i', strtotime($a['completed_at'])) : 'Sin fecha');
                        $isPast = $a['scheduled_at'] && strtotime($a['scheduled_at']) < time() && $a['outcome']==='pending';
                        $done = $a['outcome'] === 'completed';
                    ?>
                        <div class="flex gap-2.5 p-2.5 rounded-xl border <?= $done ? 'border-emerald-200 bg-emerald-50/30' : ($isPast ? 'border-rose-200 bg-rose-50/30' : 'border-[#ececef]') ?>">
                            <div class="w-8 h-8 rounded-lg grid place-items-center flex-shrink-0" style="background:#f3f0ff;color:#7c5cff"><i class="lucide lucide-<?= $ic ?> text-[13px]"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[12.5px] font-bold"><?= $e($a['subject']) ?></span>
                                    <?php if ($done): ?><span class="text-[10px] font-bold text-emerald-700">✓ Completada</span><?php endif; ?>
                                    <?php if ($isPast): ?><span class="text-[10px] font-bold text-rose-700">⏰ Vencida</span><?php endif; ?>
                                </div>
                                <?php if (!empty($a['body'])): ?>
                                    <div class="text-[11.5px] text-ink-500 mt-0.5"><?= $e($a['body']) ?></div>
                                <?php endif; ?>
                                <div class="text-[10.5px] text-ink-400 mt-1"><?= $e($when) ?> · por <?= $e($a['owner_name'] ?? '—') ?></div>
                            </div>
                            <?php if ($auth->can('crm.edit')): ?>
                            <div class="flex flex-col gap-1">
                                <?php if (!$done): ?>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/activities/' . (int)$a['id'] . '/complete') ?>">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="outcome" value="completed">
                                    <button class="text-[10px] text-emerald-700 font-semibold" title="Marcar como completada"><i class="lucide lucide-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/activities/' . (int)$a['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="text-[10px] text-rose-600"><i class="lucide lucide-x"></i></button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- NOTES -->
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-sticky-note text-violet-600"></i> Notas (<?= count($notes) ?>)</div>
            <?php if ($auth->can('crm.edit')): ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/crm/notes') ?>" class="mb-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="lead_id" value="<?= $leadId ?>">
                <textarea class="input" name="body" rows="2" placeholder="Escribí una nota interna…" required></textarea>
                <div class="flex items-center justify-between mt-2">
                    <label class="inline-flex items-center gap-1.5 text-[11.5px] cursor-pointer"><input type="checkbox" name="is_pinned" value="1" class="rounded"> Fijar arriba</label>
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Agregar</button>
                </div>
            </form>
            <?php endif; ?>
            <?php if (empty($notes)): ?>
                <div class="text-[12.5px] text-ink-400 py-4 text-center">Aún no hay notas.</div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($notes as $n): ?>
                        <div class="border border-[#ececef] rounded-xl p-3 <?= (int)$n['is_pinned']===1 ? 'bg-amber-50/40 border-amber-200' : '' ?>">
                            <div class="flex items-center gap-2 mb-1">
                                <?php if ((int)$n['is_pinned']===1): ?><i class="lucide lucide-pin text-amber-600 text-[12px]"></i><?php endif; ?>
                                <span class="text-[11.5px] font-bold"><?= $e($n['author_name'] ?? '—') ?></span>
                                <span class="text-[10.5px] text-ink-400"><?= $e(date('d M Y, H:i', strtotime($n['created_at']))) ?></span>
                                <?php if ($auth->can('crm.edit')): ?>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/notes/' . (int)$n['id'] . '/delete') ?>" class="ml-auto" onsubmit="return confirm('¿Eliminar nota?')">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="text-[10px] text-rose-600"><i class="lucide lucide-x"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <div class="text-[12.5px] text-ink-700 whitespace-pre-wrap"><?= $e($n['body']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($tickets)): ?>
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-inbox text-sky-600"></i> Tickets vinculados (<?= count($tickets) ?>)</div>
            <table class="admin-table" style="width:100%">
                <tbody>
                    <?php foreach ($tickets as $tk): ?>
                        <tr style="cursor:pointer" onclick="location='<?= $url('/t/' . $slug . '/tickets/' . (int)$tk['id']) ?>'">
                            <td class="font-mono text-[11px] text-ink-500"><?= $e($tk['code']) ?></td>
                            <td class="text-[12.5px]"><?= $e($tk['subject']) ?></td>
                            <td class="text-[11px] text-ink-400"><?= $e($tk['status']) ?></td>
                            <td class="text-[11px] text-ink-400"><?= $e(date('d M', strtotime($tk['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-target text-rose-600"></i> Calificación</div>
            <div class="space-y-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Estado</label>
                    <select class="input" name="status">
                        <?php foreach ($statusLabels as $k => [$lbl,]): ?>
                            <option value="<?= $k ?>" <?= $lead['status']===$k?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Rating</label>
                    <select class="input" name="rating">
                        <?php foreach ($ratingLabels as $k => [$lbl,]): ?>
                            <option value="<?= $k ?>" <?= $lead['rating']===$k?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Score (0-100)</label><input type="number" class="input" name="score" value="<?= (int)$lead['score'] ?>" min="0" max="100"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Valor estimado</label><input type="number" step="0.01" class="input" name="estimated_value" value="<?= $e($lead['estimated_value']) ?>"></div>
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Moneda</label><input class="input" name="currency" value="<?= $e($lead['currency']) ?>"></div>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Cierre estimado</label><input type="date" class="input" name="expected_close_on" value="<?= $e($lead['expected_close_on'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Próximo follow-up</label><input type="datetime-local" class="input" name="next_followup_at" value="<?= $lead['next_followup_at'] ? $e(str_replace(' ', 'T', substr($lead['next_followup_at'], 0, 16))) : '' ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-radar text-sky-600"></i> Origen y owner</div>
            <div class="space-y-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Origen</label>
                    <select class="input" name="source_id">
                        <option value="0">— Sin definir —</option>
                        <?php foreach ($sources as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= (int)$lead['source_id']===(int)$s['id']?'selected':'' ?>><?= $e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Detalle del origen</label><input class="input" name="source_detail" value="<?= $e($lead['source_detail'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Owner</label>
                    <select class="input" name="owner_id">
                        <option value="0">— Sin asignar —</option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" <?= (int)$lead['owner_id']===(int)$o['id']?'selected':'' ?>><?= $e($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($lead['last_contacted_at'])): ?>
                    <div class="text-[10.5px] text-ink-400">Último contacto: <strong><?= $e(date('d M Y, H:i', strtotime($lead['last_contacted_at']))) ?></strong></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($allTags)): ?>
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-tags text-violet-600"></i> Tags</div>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($allTags as $t): $checked = in_array((int)$t['id'], array_map('intval', $tagIds), true); ?>
                    <label class="inline-flex items-center gap-1.5 text-[12px] font-semibold px-2.5 py-1 rounded-full border cursor-pointer" style="background:<?= $e($t['color']) ?>10;border-color:<?= $e($t['color']) ?>33;color:<?= $e($t['color']) ?>">
                        <input type="checkbox" name="tag_ids[]" value="<?= (int)$t['id'] ?>" class="rounded" <?= $checked?'checked':'' ?>>
                        <?= $e($t['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <button class="btn btn-primary w-full"><i class="lucide lucide-save"></i> Guardar cambios</button>
    </div>
</form>

<!-- Modal: nueva oportunidad -->
<?php if ($auth->can('crm.create')): ?>
<div id="dealModal" class="hidden fixed inset-0 z-50 grid place-items-center p-4" style="background:rgba(15,13,24,.6);backdrop-filter:blur(4px)">
    <div class="card card-pad w-full max-w-lg" style="background:white">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[16px]">Nueva oportunidad</div>
            <button type="button" onclick="document.getElementById('dealModal').classList.add('hidden')" class="text-ink-500 hover:text-ink-900"><i class="lucide lucide-x"></i></button>
        </div>
        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/deals') ?>" class="space-y-2">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="lead_id" value="<?= $leadId ?>">
            <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Título *</label><input class="input" name="title" required placeholder="Ej: Implementación CRM Q3"></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Pipeline</label>
                    <select class="input" name="pipeline_id" id="newDealPipeline" onchange="updateNewDealStages()">
                        <?php foreach ($pipelines as $p): ?><option value="<?= (int)$p['id'] ?>"><?= $e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Etapa</label>
                    <select class="input" name="stage_id" id="newDealStage"></select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Monto</label><input type="number" step="0.01" class="input" name="amount" value="<?= $e($lead['estimated_value']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Cierre estimado</label><input type="date" class="input" name="expected_close_on" value="<?= $e($lead['expected_close_on'] ?? '') ?>"></div>
            </div>
            <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Descripción</label><textarea class="input" name="description" rows="3"></textarea></div>
            <div class="flex justify-end gap-2 pt-2"><button type="button" onclick="document.getElementById('dealModal').classList.add('hidden')" class="btn btn-soft btn-sm">Cancelar</button><button class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Crear</button></div>
        </form>
    </div>
</div>
<script>
const __stagesByPipeline = <?= json_encode($stagesByPipeline) ?>;
function updateNewDealStages() {
    const pid = parseInt(document.getElementById('newDealPipeline').value, 10);
    const sel = document.getElementById('newDealStage');
    const stages = __stagesByPipeline[pid] || [];
    sel.innerHTML = stages.map(s => `<option value="${s.id}">${s.name} · ${parseInt(s.probability)}%</option>`).join('');
}
document.addEventListener('DOMContentLoaded', updateNewDealStages);
</script>
<?php endif; ?>

<!-- Modal: convertir a cliente -->
<?php if (!$isCustomer && $auth->can('crm.convert')): ?>
<div id="convertModal" class="hidden fixed inset-0 z-50 grid place-items-center p-4" style="background:rgba(15,13,24,.6);backdrop-filter:blur(4px)">
    <div class="card card-pad w-full max-w-md" style="background:white">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[16px] flex items-center gap-2"><i class="lucide lucide-user-check text-emerald-600"></i> Convertir a cliente</div>
            <button type="button" onclick="document.getElementById('convertModal').classList.add('hidden')" class="text-ink-500"><i class="lucide lucide-x"></i></button>
        </div>
        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/leads/' . $leadId . '/convert') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <p class="text-[12.5px] text-ink-500">El estado del lead pasa a "Cliente". Podés además crear automáticamente la empresa y/o el acceso al portal.</p>
            <?php if (!$lead['company_id'] && !empty($lead['company_name'])): ?>
                <label class="flex items-center gap-2 text-[12.5px] cursor-pointer p-2 rounded-lg border border-[#ececef]"><input type="checkbox" name="create_company" value="1" checked class="rounded"> Crear empresa <strong><?= $e($lead['company_name']) ?></strong></label>
            <?php endif; ?>
            <?php if (!$lead['portal_user_id'] && !empty($lead['email'])): ?>
                <label class="flex items-center gap-2 text-[12.5px] cursor-pointer p-2 rounded-lg border border-[#ececef]"><input type="checkbox" name="create_portal_user" value="1" class="rounded"> Crear usuario en el Portal Cliente para <strong><?= $e($lead['email']) ?></strong></label>
            <?php endif; ?>
            <div class="flex justify-end gap-2 pt-2"><button type="button" onclick="document.getElementById('convertModal').classList.add('hidden')" class="btn btn-soft btn-sm">Cancelar</button><button class="btn btn-primary btn-sm" style="background:linear-gradient(135deg,#16a34a,#10b981)"><i class="lucide lucide-check"></i> Convertir</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
