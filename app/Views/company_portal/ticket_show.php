<?php
use App\Core\Attachments;
use App\Core\Helpers;

$slug = $tenantPublic->slug;
$tk = $ticket;
$mainAtt = $attachmentsByComment['main'] ?? [];

$prMap = ['urgent'=>['#dc2626','Urgente'],'high'=>['#f59e0b','Alta'],'medium'=>['#3b82f6','Media'],'low'=>['#6b7280','Baja']];
$stMap = [
    'open'        => ['#3b82f6','Abierto'],
    'in_progress' => ['#f59e0b','En progreso'],
    'on_hold'     => ['#6b7280','En espera'],
    'resolved'    => ['#16a34a','Resuelto'],
    'closed'      => ['#0f172a','Cerrado'],
];
[$prCol, $prLbl] = $prMap[$tk['priority']] ?? ['#6b7280', $tk['priority']];
[$stCol, $stLbl] = $stMap[$tk['status']] ?? ['#6b7280', $tk['status']];

ob_start(); ?>
<a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 mb-3"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver al listado</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="kbd"><?= $e($tk['code']) ?></span>
                <span class="badge" style="background:<?= $prCol ?>15;color:<?= $prCol ?>"><?= $prLbl ?></span>
                <span class="badge" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><?= $stLbl ?></span>
                <?php if (!empty($tk['category_name'])): ?>
                    <span class="badge" style="background:<?= $e($tk['category_color'] ?? '#94a3b8') ?>15;color:<?= $e($tk['category_color'] ?? '#94a3b8') ?>"><?= $e($tk['category_name']) ?></span>
                <?php endif; ?>
            </div>
            <h1 class="font-display font-extrabold text-[22px] tracking-[-0.015em] mb-2"><?= $e($tk['subject']) ?></h1>
            <div class="text-[11.5px] text-ink-400 mb-4">Creado <?= Helpers::ago($tk['created_at']) ?> · Solicitante: <?= $e($tk['requester_name']) ?> &lt;<?= $e($tk['requester_email']) ?>&gt;</div>
            <div class="whitespace-pre-wrap text-[14px] leading-relaxed border-t border-[#ececef] pt-4"><?= $e($tk['description']) ?></div>
        </div>

        <?php if (!empty($mainAtt)): ?>
            <div class="card card-pad">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400 mb-3 flex items-center gap-1.5">
                    <i class="lucide lucide-paperclip text-[12px]"></i> Adjuntos del ticket
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    <?php foreach ($mainAtt as $a):
                        $u = Attachments::publicUrl($a['filename']);
                        $isImage = str_starts_with($a['mime'], 'image/');
                        $icon = Attachments::iconFor($a['mime']);
                    ?>
                        <a href="<?= $e($u) ?>" target="_blank" rel="noopener" class="card block hover:shadow-md transition" style="text-decoration:none;color:inherit;padding:10px">
                            <?php if ($isImage): ?>
                                <div class="aspect-video rounded-lg overflow-hidden bg-[#fafafb] mb-2"><img src="<?= $e($u) ?>" alt="" loading="lazy" class="w-full h-full object-cover"></div>
                            <?php else: ?>
                                <div class="aspect-video rounded-lg bg-brand-50 grid place-items-center mb-2"><i class="lucide lucide-<?= $icon ?> text-[22px] text-brand-600"></i></div>
                            <?php endif; ?>
                            <div class="text-[11.5px] font-semibold truncate"><?= $e($a['original_name']) ?></div>
                            <div class="text-[10px] text-ink-400"><?= Attachments::humanSize((int)$a['size']) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-[#ececef]"><h3 class="font-display font-bold text-[14px]">Conversación · <?= count($comments) ?></h3></div>
            <div class="px-5 py-4 space-y-4">
                <?php foreach ($comments as $c):
                    $mine = !$c['user_id'];
                    $cAtt = $attachmentsByComment[(int)$c['id']] ?? [];
                ?>
                    <div class="flex items-start gap-3 <?= $mine?'flex-row-reverse':'' ?>">
                        <div class="w-9 h-9 rounded-xl grid place-items-center text-white font-display font-bold text-[12px] shrink-0" style="background: <?= Helpers::colorFor($c['author_email'] ?? ($c['user_name'] ?? '')) ?>"><?= Helpers::initials($c['user_name'] ?? $c['author_name'] ?? 'U') ?></div>
                        <div class="flex-1 min-w-0 <?= $mine?'text-right':'' ?>">
                            <div class="inline-block max-w-full px-4 py-3 rounded-2xl text-left <?= $mine?'bg-[#f3f4f6]':'bg-brand-500 text-white' ?>">
                                <div class="text-[13.5px] whitespace-pre-wrap leading-relaxed"><?= $e($c['body']) ?></div>
                                <?php if (!empty($cAtt)): ?>
                                    <div class="mt-2 pt-2 border-t border-black/10 flex flex-wrap gap-1.5">
                                        <?php foreach ($cAtt as $a):
                                            $u = Attachments::publicUrl($a['filename']);
                                            $icon = Attachments::iconFor($a['mime']);
                                        ?>
                                            <a href="<?= $e($u) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[11.5px] px-2 py-1 rounded-lg" style="background:rgba(0,0,0,.06);color:inherit;text-decoration:none">
                                                <i class="lucide lucide-<?= $icon ?> text-[12px]"></i>
                                                <span class="truncate max-w-[180px]"><?= $e($a['original_name']) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1 text-[11px] text-ink-400"><?= $e($c['user_name'] ?? $c['author_name'] ?? '—') ?> · <?= Helpers::ago($c['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?><div class="text-center py-6 text-[13px] text-ink-400">Sin respuestas aún</div><?php endif; ?>
            </div>
        </div>

        <?php if (!in_array($tk['status'], ['closed'], true)): ?>
        <form method="POST" action="<?= $url('/portal/' . $slug . '/company/tickets/' . $tk['id'] . '/reply') ?>" enctype="multipart/form-data" class="card card-pad" x-data="{files:[]}">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <label class="label">Agregar mensaje</label>
            <textarea name="body" rows="4" class="input" placeholder="Escribe tu respuesta…"></textarea>
            <div x-show="files.length > 0" x-cloak class="mt-2 flex flex-wrap gap-1.5">
                <template x-for="(f, i) in files" :key="i">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] bg-brand-50 text-brand-700 border border-brand-200">
                        <i class="lucide lucide-paperclip text-[11px]"></i>
                        <span class="truncate max-w-[160px]" x-text="f.name"></span>
                    </span>
                </template>
            </div>
            <div class="mt-3 flex justify-between items-center gap-2">
                <label class="btn btn-outline btn-sm cursor-pointer">
                    <i class="lucide lucide-paperclip text-[13px]"></i>
                    <span x-text="files.length === 0 ? 'Adjuntar' : files.length + ' archivo' + (files.length === 1 ? '' : 's')"></span>
                    <input type="file" name="attachments[]" multiple class="hidden" @change="files = Array.from($event.target.files)">
                </label>
                <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Enviar respuesta</button>
            </div>
        </form>
        <?php else: ?>
            <div class="card card-pad text-center text-[12.5px] text-ink-500">Este ticket está cerrado. Si necesitas continuar la conversación, crea un nuevo ticket.</div>
        <?php endif; ?>
    </div>

    <aside class="space-y-3">
        <div class="card card-pad">
            <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-3">Detalles</div>
            <dl class="space-y-2.5 text-[12.5px]">
                <div class="flex justify-between gap-3"><dt class="text-ink-400">Estado</dt><dd><span class="badge" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><?= $stLbl ?></span></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-400">Prioridad</dt><dd><span class="badge" style="background:<?= $prCol ?>15;color:<?= $prCol ?>"><?= $prLbl ?></span></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-400">Categoría</dt><dd><?= $tk['category_name'] ? $e($tk['category_name']) : '—' ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-400">Asignado</dt><dd><?= $tk['assigned_name'] ? $e($tk['assigned_name']) : 'Sin asignar' ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-400">Canal</dt><dd><?= $e($tk['channel']) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-400">Creado</dt><dd><?= $e($tk['created_at']) ?></dd></div>
                <?php if (!empty($tk['first_response_at'])): ?>
                    <div class="flex justify-between gap-3"><dt class="text-ink-400">Primera resp.</dt><dd><?= Helpers::ago($tk['first_response_at']) ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($tk['resolved_at'])): ?>
                    <div class="flex justify-between gap-3"><dt class="text-ink-400">Resuelto</dt><dd><?= Helpers::ago($tk['resolved_at']) ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($tk['sla_due_at'])): ?>
                    <div class="flex justify-between gap-3"><dt class="text-ink-400">SLA</dt><dd><?= $tk['sla_breached'] ? '<span class="text-rose-600 font-semibold">Incumplido</span>' : 'En tiempo' ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($tk['satisfaction_rating'])): ?>
                    <div class="flex justify-between gap-3"><dt class="text-ink-400">CSAT</dt><dd><?= (int)$tk['satisfaction_rating'] ?> / 5</dd></div>
                <?php endif; ?>
            </dl>
        </div>

        <div class="card card-pad">
            <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-2">Solicitante</div>
            <div class="text-[13px] font-semibold"><?= $e($tk['requester_name']) ?></div>
            <a href="mailto:<?= $e($tk['requester_email']) ?>" class="text-[12px] text-brand-600 break-all"><?= $e($tk['requester_email']) ?></a>
            <?php if (!empty($tk['requester_phone'])): ?>
                <div class="text-[12px] text-ink-500 mt-1"><i class="lucide lucide-phone text-[11px]"></i> <?= $e($tk['requester_phone']) ?></div>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
