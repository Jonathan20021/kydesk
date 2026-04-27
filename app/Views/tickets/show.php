<?php use App\Core\Helpers; use App\Core\Plan; $slug = $tenant->slug; $t = $ticket;
$priColor = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
$hasSla = Plan::has($tenant, 'sla');
$ageHours = max(1, round((time() - strtotime($t['created_at'])) / 3600));
$firstResp = $t['first_response_at'] ? round((strtotime($t['first_response_at']) - strtotime($t['created_at'])) / 60) : null;
$statusMeta = ['open'=>['Abierto','#3b82f6','#dbeafe'],'in_progress'=>['En progreso','#f59e0b','#fef3c7'],'on_hold'=>['En espera','#6b7280','#f3f4f6'],'resolved'=>['Resuelto','#16a34a','#d1fae5'],'closed'=>['Cerrado','#6b7280','#f3f4f6']];
[$stLabel, $stColor, $stBg] = $statusMeta[$t['status']] ?? ['—','#6b7280','#f3f4f6'];
?>

<a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a tickets</a>

<!-- HEADER COVER -->
<div class="relative rounded-[24px] overflow-hidden p-8" style="background:linear-gradient(135deg,#fafafb 0%,#f3f0ff 100%);border:1px solid #ececef">
    <div class="absolute top-0 inset-x-0 h-[3px]" style="background:linear-gradient(90deg,<?= $priColor[$t['priority']] ?? '#7c5cff' ?>,#d946ef,#f59e0b)"></div>
    <div class="absolute top-0 right-0 w-72 h-72 pointer-events-none" style="background:radial-gradient(circle,rgba(124,92,255,.08),transparent 60%)"></div>

    <div class="relative flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="kbd"><?= $e($t['code']) ?></span>
                <?= Helpers::priorityBadge($t['priority']) ?>
                <?= Helpers::statusBadge($t['status']) ?>
                <?php if ($hasSla && (int)$t['escalation_level'] > 0): ?>
                    <span class="status-pill priority-urgent"><i class="lucide lucide-trending-up text-[11px]"></i> N<?= (int)$t['escalation_level']+1 ?></span>
                <?php endif; ?>
                <?php if (!empty($t['company_tier']) && $t['company_tier'] === 'enterprise'): ?>
                    <span class="badge badge-purple"><i class="lucide lucide-crown text-[11px]"></i> ENTERPRISE</span>
                <?php endif; ?>
            </div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] leading-tight"><?= $e($t['subject']) ?></h1>

            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[12px] text-ink-500">
                <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-clock text-[12px] text-ink-400"></i> Creado <?= Helpers::ago($t['created_at']) ?></span>
                <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-user text-[12px] text-ink-400"></i> <?= $e($t['creator_name'] ?? ($t['requester_name'] ?? '—')) ?></span>
                <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-radio text-[12px] text-ink-400"></i> <?= ucfirst($t['channel']) ?></span>
                <?php if ($t['first_response_at']): ?>
                    <span class="inline-flex items-center gap-1.5 text-emerald-700"><i class="lucide lucide-corner-down-right text-[12px]"></i> 1ª respuesta en <?= $firstResp < 60 ? $firstResp.'min' : round($firstResp/60,1).'h' ?></span>
                <?php endif; ?>
                <?php if ($hasSla && $t['sla_due_at']): ?>
                    <span class="inline-flex items-center gap-1.5 text-amber-600"><i class="lucide lucide-alarm-clock text-[12px]"></i> SLA <?= date('d/m H:i', strtotime($t['sla_due_at'])) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <button class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(window.location.href); this.querySelector('span').textContent='Copiado'"><i class="lucide lucide-link"></i> <span>Copiar URL</span></button>
            <button class="btn btn-outline btn-sm"><i class="lucide lucide-share-2"></i> Compartir</button>
            <?php if ($auth->can('tickets.delete')): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#fecaca;background:#fef2f2"><i class="lucide lucide-trash-2"></i></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats strip -->
    <div class="relative mt-6 grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <?php
        $heroStats = [
            ['Estado', $stLabel, $stBg, $stColor, 'activity'],
            ['Edad del ticket', $ageHours < 24 ? $ageHours.'h' : round($ageHours/24,1).' días', '#f3f0ff', '#5a3aff', 'history'],
            ['Mensajes', count($comments), '#dbeafe', '#1d4ed8', 'message-circle'],
            ['Asignado', $t['assigned_name'] ? explode(' ', $t['assigned_name'])[0] : 'Nadie', $t['assigned_name'] ? '#d1fae5' : '#f3f4f6', $t['assigned_name'] ? '#047857' : '#6b7280', $t['assigned_name'] ? 'user-check' : 'user-x'],
        ];
        foreach ($heroStats as [$l,$v,$bg,$col,$ic]): ?>
            <div class="rounded-2xl px-4 py-3 flex items-center gap-3 bg-white" style="border:1px solid #ececef">
                <div class="w-9 h-9 rounded-xl grid place-items-center flex-shrink-0" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                <div class="min-w-0">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400"><?= $l ?></div>
                    <div class="font-display font-bold text-[14px] truncate" style="color:<?= $col ?>"><?= $e((string)$v) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($t['description'])): ?>
        <div class="relative mt-6 pt-6 border-t border-[#ececef]">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400 mb-2.5">Descripción</div>
            <div class="text-[14px] leading-relaxed whitespace-pre-wrap text-ink-700"><?= $e($t['description']) ?></div>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <!-- LEFT: Conversación + escalamientos + KB -->
    <div class="lg:col-span-2 space-y-5">
        <div class="card overflow-hidden">
            <div class="px-6 pt-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-messages-square text-[16px]"></i></div>
                    <div>
                        <h3 class="font-display font-extrabold text-[16px] tracking-[-0.015em]">Conversación</h3>
                        <div class="text-[11.5px] text-ink-400"><?= count($comments) ?> mensaje<?= count($comments)===1?'':'s' ?> · <?= $hasSla ? 'SLA activo' : 'Plan starter' ?></div>
                    </div>
                </div>
                <?php if (!empty($comments)): ?>
                    <button class="btn btn-ghost btn-xs"><i class="lucide lucide-filter text-[12px]"></i> Filtrar</button>
                <?php endif; ?>
            </div>
            <div class="px-6 py-5 space-y-5">
                <?php if (empty($comments)): ?>
                    <div class="empty-state">
                        <div class="empty-illust"><i class="lucide lucide-message-circle text-[26px]"></i></div>
                        <div class="empty-state-title">Inicia la conversación</div>
                        <p class="empty-state-text">Escribe la primera respuesta abajo o usa una plantilla rápida</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($comments as $c):
                    $mine = (int)$c['user_id'] === $auth->userId();
                    $color = Helpers::colorFor($c['user_email'] ?? $c['author_email'] ?? '');
                    $bubbleClass = $c['is_internal'] ? 'chat-bubble-internal' : ($mine ? 'chat-bubble-mine' : 'chat-bubble-other');
                ?>
                    <div class="chat-row <?= $mine?'mine':'' ?>">
                        <div class="avatar avatar-md" style="background:<?= $color ?>;color:white"><?= Helpers::initials($c['user_name'] ?? $c['author_name'] ?? 'U') ?></div>
                        <div class="min-w-0 <?= $mine?'text-right':'' ?>">
                            <div class="chat-bubble <?= $bubbleClass ?>">
                                <?php if ($c['is_internal']): ?><div class="flex items-center gap-1 text-[10.5px] font-bold uppercase tracking-[0.08em] mb-1.5 text-amber-700"><i class="lucide lucide-lock text-[10px]"></i> Nota interna</div><?php endif; ?>
                                <div class="whitespace-pre-wrap"><?= $e($c['body']) ?></div>
                            </div>
                            <div class="chat-meta"><?= $e($c['user_name'] ?? $c['author_name'] ?? '—') ?> · <?= Helpers::ago($c['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($auth->can('tickets.comment')): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/comment') ?>" class="border-t border-[#ececef]" x-data="{internal:false}">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

                    <div class="px-5 pt-4 flex flex-wrap items-center gap-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mr-1">Plantillas</span>
                        <?php
                        $defaultMacros = [
                            ['Estoy revisando','Hola, estoy revisando tu caso ahora mismo. Te respondo en breve.', 0],
                            ['Más info','¿Podrías compartir capturas o pasos exactos para reproducir el problema?', 0],
                            ['Resuelto','El problema ha sido resuelto. Cualquier consulta adicional, no dudes en escribirnos.', 0],
                        ];
                        $macrosToShow = !empty($macros) ? array_map(fn($m) => [$m['name'], $m['body'], (int)$m['is_internal']], array_slice($macros, 0, 4)) : $defaultMacros;
                        foreach ($macrosToShow as [$lbl, $tpl, $isInternal]):
                            $jsTpl = json_encode($tpl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                        ?>
                            <button type="button" onclick="const f=this.closest('form'); f.querySelector('textarea').value=<?= $e($jsTpl) ?>; <?= $isInternal ? 'f.querySelector(\'input[name=is_internal]\').checked=true; f._x_dataStack && (f._x_dataStack[0].internal=true);' : '' ?>" class="text-[11.5px] font-medium px-3 py-1.5 rounded-full border <?= $isInternal?'border-amber-200 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300':'border-[#ececef] hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700' ?> transition inline-flex items-center gap-1">
                                <?php if ($isInternal): ?><i class="lucide lucide-lock text-[10px] text-amber-600"></i><?php endif; ?>
                                <?= $e($lbl) ?>
                            </button>
                        <?php endforeach; ?>
                        <a href="<?= $url('/t/' . $slug . '/macros') ?>" class="text-[11.5px] font-medium px-3 py-1.5 rounded-full text-brand-700 hover:bg-brand-50 inline-flex items-center gap-1"><i class="lucide lucide-plus text-[11px]"></i> Gestionar</a>
                    </div>

                    <div class="p-5 pt-3">
                        <textarea name="body" required rows="3" placeholder="Escribe tu respuesta…" class="input" :class="internal && '!bg-amber-50 !border-amber-300'"></textarea>
                        <div class="mt-3 flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-[12.5px] cursor-pointer">
                                    <input type="checkbox" name="is_internal" value="1" x-model="internal" class="accent-amber-500">
                                    <span :class="internal && 'font-semibold text-amber-700'"><i class="lucide lucide-lock text-[12px]"></i> Nota interna</span>
                                </label>
                                <span class="text-[11px] text-ink-400 hidden sm:inline">⌘ + Enter para enviar</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn btn-outline btn-xs"><i class="lucide lucide-paperclip text-[12px]"></i> Adjuntar</button>
                                <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Enviar</button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($hasSla && !empty($escalations)): ?>
            <div class="card card-pad">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 grid place-items-center"><i class="lucide lucide-trending-up text-[16px]"></i></div>
                    <div>
                        <h3 class="font-display font-extrabold text-[16px] tracking-[-0.015em]">Escalamientos</h3>
                        <div class="text-[11.5px] text-ink-400">Historial de niveles</div>
                    </div>
                </div>
                <div class="space-y-2.5">
                    <?php foreach ($escalations as $es): ?>
                        <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-rose-50 border border-rose-100">
                            <div class="w-9 h-9 rounded-xl bg-white text-rose-700 grid place-items-center flex-shrink-0" style="border:1px solid #fecdd3"><i class="lucide lucide-arrow-up-right text-[14px]"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-bold text-[13px]">N<?= (int)$es['from_level']+1 ?> → N<?= (int)$es['to_level']+1 ?></span>
                                    <span class="text-[11px] text-rose-600 font-mono">· <?= Helpers::ago($es['created_at']) ?></span>
                                </div>
                                <div class="text-[12px] mt-0.5 text-ink-500">de <?= $e($es['from_name'] ?? '—') ?> a <strong class="text-ink-900"><?= $e($es['to_name'] ?? '—') ?></strong></div>
                                <?php if ($es['reason']): ?><div class="mt-2 text-[12.5px] italic text-ink-700">"<?= $e($es['reason']) ?>"</div><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($relatedArticles)): ?>
            <div class="card card-pad">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-xl grid place-items-center" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 6px 14px -4px rgba(124,92,255,.5)"><i class="lucide lucide-sparkles text-[16px]"></i></div>
                    <div>
                        <h3 class="font-display font-extrabold text-[16px] tracking-[-0.015em]">Artículos sugeridos</h3>
                        <div class="text-[11.5px] text-ink-400">Posibles soluciones de tu base de conocimiento</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <?php foreach ($relatedArticles as $a): ?>
                        <a href="<?= $url('/t/' . $slug . '/kb/' . $a['id']) ?>" class="group block p-4 rounded-2xl border border-[#ececef] hover:border-brand-300 hover:bg-brand-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-white border border-[#ececef] grid place-items-center mb-2 group-hover:bg-brand-100 group-hover:border-brand-200 transition"><i class="lucide lucide-book-open text-[14px] text-brand-700"></i></div>
                            <div class="font-display font-bold text-[13px] line-clamp-2 leading-snug"><?= $e($a['title']) ?></div>
                            <div class="text-[11.5px] mt-1.5 line-clamp-2 text-ink-400"><?= $e($a['excerpt'] ?? '') ?></div>
                            <div class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 opacity-0 group-hover:opacity-100 transition">Leer <i class="lucide lucide-arrow-right text-[11px]"></i></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT SIDEBAR -->
    <div class="space-y-4">
        <!-- Quick status -->
        <?php if ($auth->can('tickets.edit')): ?>
            <div class="card card-pad">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-3 text-ink-400">Cambio rápido de estado</div>
                <div class="grid grid-cols-2 gap-1.5">
                    <?php foreach (['open'=>['Abrir','#3b82f6','play'],'in_progress'=>['Trabajar','#f59e0b','play'],'on_hold'=>['Pausa','#6b7280','pause'],'resolved'=>['Resolver','#16a34a','check']] as $v=>[$lbl,$col,$ic]):
                        $active = $t['status'] === $v;
                    ?>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/update') ?>">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <input type="hidden" name="status" value="<?= $v ?>">
                            <input type="hidden" name="priority" value="<?= $e($t['priority']) ?>">
                            <input type="hidden" name="category_id" value="<?= (int)$t['category_id'] ?>">
                            <button class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-semibold transition" style="<?= $active ? 'background:'.$col.';color:white' : 'background:#f3f4f6;color:#2a2a33' ?>" onmouseover="if(!this.querySelector('input[name=status]') || '<?= $v ?>' !== '<?= $e($t['status']) ?>') this.style.background='<?= $col ?>22'" onmouseout="this.style.background='<?= $active ? $col : '#f3f4f6' ?>'"><i class="lucide lucide-<?= $ic ?> text-[12px]"></i> <?= $lbl ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detalles -->
        <div class="card card-pad">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-4 text-ink-400">Detalles</div>
            <dl class="space-y-3.5 text-[13px]">
                <?php foreach ([
                    ['user-round','Solicitante', $t['requester_name'] ?? '—'],
                    ['mail','Email', $t['requester_email'] ?? '—'],
                    ['phone','Teléfono', $t['requester_phone'] ?? '—'],
                    ['radio','Canal', ucfirst($t['channel'])],
                ] as [$ic,$l,$v]): ?>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-ink-400 inline-flex items-center gap-2"><i class="lucide lucide-<?= $ic ?> text-[12px]"></i> <?= $l ?></dt>
                        <dd class="font-medium text-right truncate min-w-0"><?= $e($v) ?></dd>
                    </div>
                <?php endforeach; ?>
                <!-- Empresa con link / inline action si está vacía -->
                <div class="flex items-start justify-between gap-3" x-data="{linkOpen:false}">
                    <dt class="text-ink-400 inline-flex items-center gap-2"><i class="lucide lucide-building-2 text-[12px]"></i> Empresa</dt>
                    <dd class="font-medium text-right min-w-0">
                        <?php if (!empty($t['company_name']) && !empty($t['company_id'])): ?>
                            <a href="<?= $url('/t/' . $slug . '/companies/' . (int)$t['company_id']) ?>" class="text-brand-700 hover:underline inline-flex items-center gap-1"><?= $e($t['company_name']) ?> <i class="lucide lucide-arrow-up-right text-[10px]"></i></a>
                        <?php elseif ($auth->can('tickets.edit')): ?>
                            <button type="button" @click="linkOpen = !linkOpen" class="text-[12px] text-brand-700 font-semibold inline-flex items-center gap-1"><i class="lucide lucide-link-2 text-[12px]"></i> Vincular empresa</button>
                            <div x-show="linkOpen" x-cloak class="mt-2 text-left">
                                <form method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/update') ?>" class="flex gap-1">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <select name="company_id" class="input" style="height:32px;font-size:12px;padding:0 8px;min-width:160px">
                                        <option value="0">— Empresa —</option>
                                        <?php foreach ($companies ?? [] as $co): ?>
                                            <option value="<?= (int)$co['id'] ?>"><?= $e($co['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary btn-xs" style="padding:0 10px;height:32px"><i class="lucide lucide-check text-[12px]"></i></button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="text-ink-400">—</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-ink-400 inline-flex items-center gap-2"><i class="lucide lucide-folder text-[12px]"></i> Categoría</dt>
                    <dd>
                        <?php if ($t['category_name']): ?>
                            <span class="inline-flex items-center gap-1.5"><span class="dot" style="background: <?= $e($t['category_color']) ?>"></span><?= $e($t['category_name']) ?></span>
                        <?php else: ?><span class="text-ink-400">—</span><?php endif; ?>
                    </dd>
                </div>
                <?php if (!empty($departments) || !empty($t['department_name'])): ?>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-ink-400 inline-flex items-center gap-2"><i class="lucide lucide-layers text-[12px]"></i> Departamento</dt>
                    <dd>
                        <?php if (!empty($t['department_name'])): ?>
                            <a href="<?= $url('/t/' . $slug . '/departments/' . (int)$t['department_id']) ?>" class="inline-flex items-center gap-1.5" style="color:<?= $e($t['department_color']) ?>">
                                <i class="lucide lucide-<?= $e($t['department_icon']) ?> text-[11px]"></i> <?= $e($t['department_name']) ?>
                            </a>
                        <?php else: ?><span class="text-ink-400">—</span><?php endif; ?>
                    </dd>
                </div>
                <?php endif; ?>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-ink-400 inline-flex items-center gap-2"><i class="lucide lucide-user-check text-[12px]"></i> Asignado</dt>
                    <dd>
                        <?php if ($t['assigned_name']): ?>
                            <span class="inline-flex items-center gap-1.5"><span class="avatar avatar-xs" style="background:<?= Helpers::colorFor($t['assigned_email'] ?? '') ?>;color:white"><?= Helpers::initials($t['assigned_name']) ?></span> <?= $e($t['assigned_name']) ?></span>
                        <?php else: ?><span class="text-ink-400">Sin asignar</span><?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Propiedades -->
        <?php if ($auth->can('tickets.edit')): ?>
            <div class="card card-pad">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-4 text-ink-400">Propiedades</div>
                <form method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/update') ?>" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div>
                        <label class="label">Estado</label>
                        <select name="status" class="input">
                            <?php foreach (['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $t['status']===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Prioridad</label>
                        <select name="priority" class="input">
                            <?php foreach (['low'=>'Baja','medium'=>'Media','high'=>'Alta','urgent'=>'Urgente'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $t['priority']===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Categoría</label>
                        <select name="category_id" class="input">
                            <option value="0">Sin categoría</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$t['category_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($departments)): ?>
                        <div>
                            <label class="label">Departamento</label>
                            <select name="department_id" class="input">
                                <option value="0">Sin departamento</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>" <?= (int)($t['department_id']??0)===(int)$d['id']?'selected':'' ?>><?= $e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-sm w-full"><i class="lucide lucide-save"></i> Guardar</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Asignar -->
        <?php if ($auth->can('tickets.assign')): ?>
            <div class="card card-pad">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-3 text-ink-400">Asignar técnico</div>
                <form method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/assign') ?>" class="flex items-center gap-2">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <select name="assigned_to" class="input">
                        <option value="0">Sin asignar</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= (int)$tech['id'] ?>" <?= (int)$t['assigned_to']===(int)$tech['id']?'selected':'' ?>><?= $e($tech['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary btn-icon" data-tooltip="Asignar"><i class="lucide lucide-check"></i></button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Escalamiento (solo si plan tiene SLA) -->
        <?php if ($hasSla && $auth->can('tickets.escalate')): ?>
            <div class="card card-pad" x-data="{open:false}">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Escalamiento</div>
                    <span class="badge badge-rose"><i class="lucide lucide-trending-up text-[10px]"></i> Nivel <?= (int)$t['escalation_level']+1 ?></span>
                </div>
                <button @click="open=!open" type="button" class="w-full inline-flex items-center justify-center gap-2 h-10 rounded-xl font-semibold text-[13px]" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white;box-shadow:0 8px 20px -6px rgba(239,68,68,.45)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'"><i class="lucide lucide-arrow-up-right"></i> Escalar</button>
                <form x-show="open" x-cloak method="POST" action="<?= $url('/t/' . $slug . '/tickets/' . $t['id'] . '/escalate') ?>" class="mt-3 space-y-2">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <select name="to_user_id" class="input">
                        <option value="0">Sin reasignar</option>
                        <?php foreach ($technicians as $tech): ?><option value="<?= (int)$tech['id'] ?>"><?= $e($tech['name']) ?></option><?php endforeach; ?>
                    </select>
                    <textarea name="reason" rows="2" placeholder="Motivo del escalamiento…" class="input"></textarea>
                    <button class="btn btn-danger btn-sm w-full">Confirmar escalamiento</button>
                </form>
            </div>
        <?php elseif (!$hasSla): ?>
            <div class="card card-pad relative overflow-hidden" style="background:linear-gradient(135deg,#fafafb,#f3f0ff);border-color:#cdbfff">
                <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full pointer-events-none" style="background:radial-gradient(circle,rgba(124,92,255,.18),transparent 65%)"></div>
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-white border border-brand-200 grid place-items-center"><i class="lucide lucide-lock text-[14px] text-brand-700"></i></div>
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-brand-700">Escalamiento</div>
                    </div>
                    <div class="font-display font-bold text-[14px] tracking-[-0.015em]">Disponible en plan Pro</div>
                    <p class="text-[12px] mt-1.5 text-ink-500 leading-relaxed">Escala tickets a niveles superiores con motivo y trazabilidad completa.</p>
                    <a href="<?= $url('/pricing') ?>" class="mt-3 inline-flex items-center gap-1 text-[12px] font-semibold text-brand-700">Ver planes <i class="lucide lucide-arrow-right text-[12px]"></i></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Activity timeline mini -->
        <div class="card card-pad">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-4 text-ink-400">Actividad</div>
            <div class="space-y-3 relative">
                <div class="absolute left-[11px] top-2 bottom-2 w-px bg-[#ececef]"></div>
                <?php
                $events = [['plus','Ticket creado',$t['created_at'],'#7c5cff']];
                if ($t['assigned_name']) $events[] = ['user-check','Asignado a '.$t['assigned_name'],$t['created_at'],'#16a34a'];
                if ($t['first_response_at']) $events[] = ['corner-down-right','Primera respuesta',$t['first_response_at'],'#3b82f6'];
                if ($hasSla && (int)$t['escalation_level'] > 0) $events[] = ['trending-up','Escalado a N'.((int)$t['escalation_level']+1),$t['updated_at'] ?? $t['created_at'],'#ef4444'];
                if ($t['resolved_at']) $events[] = ['check-circle-2','Ticket resuelto',$t['resolved_at'],'#16a34a'];
                foreach ($events as [$ic,$lbl,$at,$col]): ?>
                    <div class="relative flex items-start gap-3 pl-1">
                        <div class="w-6 h-6 rounded-full grid place-items-center flex-shrink-0 relative z-10" style="background:white;border:2px solid <?= $col ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[10px]"></i></div>
                        <div class="min-w-0 flex-1 pb-1">
                            <div class="text-[12.5px] font-medium leading-tight"><?= $e($lbl) ?></div>
                            <div class="text-[10.5px] text-ink-400 mt-0.5"><?= Helpers::ago($at) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
