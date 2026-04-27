<?php
$statusMap = [
    'operational' => ['#16a34a','Operacional','check-circle-2','#d1fae5'],
    'degraded' => ['#f59e0b','Rendimiento degradado','alert-circle','#fef3c7'],
    'partial_outage' => ['#f97316','Outage parcial','alert-triangle','#ffedd5'],
    'major_outage' => ['#dc2626','Outage mayor','x-circle','#fee2e2'],
    'maintenance' => ['#7c5cff','Mantenimiento programado','wrench','#f3e8ff'],
];
$incStatusMap = [
    'investigating' => ['#dc2626','Investigando'],
    'identified' => ['#f59e0b','Identificado'],
    'monitoring' => ['#0ea5e9','Monitoreando'],
    'resolved' => ['#16a34a','Resuelto'],
];
[$ovCol, $ovLbl, $ovIc, $ovBg] = $statusMap[$overall] ?? $statusMap['operational'];
?>

<div class="min-h-screen" style="background:#fafafb">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 text-white grid place-items-center font-display font-bold">K</div>
                <span class="font-display font-bold text-[18px]"><?= $e($tenantPublic->name) ?></span>
            </div>
            <a href="<?= $url('/') ?>" class="text-[12.5px] text-ink-500 hover:text-ink-900">← Inicio</a>
        </div>

        <!-- Overall status card -->
        <div class="rounded-3xl p-8 text-center" style="background:linear-gradient(135deg,<?= $ovBg ?>,white);border:1px solid <?= $ovCol ?>33;box-shadow:0 8px 30px -10px <?= $ovCol ?>40">
            <div class="w-16 h-16 mx-auto rounded-2xl grid place-items-center" style="background:<?= $ovCol ?>;color:white;box-shadow:0 8px 20px -4px <?= $ovCol ?>aa"><i class="lucide lucide-<?= $ovIc ?> text-[28px]"></i></div>
            <h1 class="font-display font-extrabold text-[26px] mt-4 tracking-[-0.02em]" style="color:<?= $ovCol ?>"><?= $ovLbl ?></h1>
            <p class="text-[13px] text-ink-500 mt-1"><?= $overall === 'operational' ? 'Todos los sistemas funcionan con normalidad.' : 'Hay problemas activos. Revisá los detalles abajo.' ?></p>
        </div>

        <!-- Subscribe -->
        <div class="card card-pad mt-6">
            <div class="flex items-start gap-3 flex-wrap">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 grid place-items-center shrink-0"><i class="lucide lucide-bell text-[16px]"></i></div>
                <div class="flex-1 min-w-[200px]">
                    <div class="font-display font-bold text-[14px]">Recibí alertas por email</div>
                    <p class="text-[11.5px] text-ink-500">Te avisamos al instante cuando hay incidentes nuevos o updates.</p>
                </div>
                <form method="POST" action="<?= $url('/status/' . $tenantPublic->slug . '/subscribe') ?>" class="flex gap-2 flex-1 min-w-[260px]">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input name="email" type="email" required class="input flex-1" placeholder="tu@email.com" style="height:38px">
                    <button class="btn btn-primary btn-sm">Suscribirme</button>
                </form>
            </div>
        </div>

        <!-- Active incidents -->
        <?php if (!empty($activeIncidents)): ?>
            <div class="mt-8">
                <h2 class="font-display font-bold text-[16px] mb-3 flex items-center gap-2"><i class="lucide lucide-alert-triangle text-rose-600"></i> Incidentes activos</h2>
                <?php foreach ($activeIncidents as $i):
                    [$col,$lbl] = $incStatusMap[$i['status']] ?? ['#6b7280', $i['status']];
                    $ups = $updatesByIncident[(int)$i['id']] ?? [];
                ?>
                    <div class="card card-pad mb-3">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <div class="font-display font-bold text-[15px]"><?= $e($i['title']) ?></div>
                            <span class="badge" style="background:<?= $col ?>15;color:<?= $col ?>;border:1px solid <?= $col ?>33"><?= $lbl ?></span>
                            <span class="badge badge-gray"><?= ucfirst($i['severity']) ?></span>
                        </div>
                        <p class="text-[12.5px] text-ink-500 mb-3"><?= $e($i['description']) ?></p>
                        <?php foreach ($ups as $u):
                            [$uc,$ul] = $incStatusMap[$u['status']] ?? ['#6b7280',$u['status']];
                        ?>
                            <div class="border-l-2 pl-3 ml-1 py-1.5" style="border-color:<?= $uc ?>">
                                <div class="text-[11.5px] flex items-center gap-2">
                                    <span class="font-bold" style="color:<?= $uc ?>"><?= $ul ?></span>
                                    <span class="text-ink-400 font-mono"><?= $e($u['posted_at']) ?></span>
                                </div>
                                <div class="text-[12.5px] text-ink-700 mt-0.5"><?= nl2br($e($u['body'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Components -->
        <div class="mt-8">
            <h2 class="font-display font-bold text-[16px] mb-3">Componentes</h2>
            <div class="card overflow-hidden">
                <?php foreach ($components as $idx => $c):
                    [$col, $lbl, $ic] = $statusMap[$c['status']] ?? ['#6b7280', $c['status'], 'help-circle'];
                ?>
                    <div class="flex items-center gap-3 p-4 <?= $idx > 0 ? 'border-t' : '' ?>" style="border-color:var(--border)">
                        <div class="w-9 h-9 rounded-xl grid place-items-center" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="lucide lucide-<?= $e($c['icon']) ?> text-[14px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-[13.5px]"><?= $e($c['name']) ?></div>
                            <?php if (!empty($c['description'])): ?><div class="text-[11.5px] text-ink-500 mt-0.5"><?= $e($c['description']) ?></div><?php endif; ?>
                        </div>
                        <span class="text-[12px] font-bold flex items-center gap-1.5" style="color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[12px]"></i> <?= $lbl ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($components)): ?>
                    <div class="text-center py-8 text-ink-400 text-[12.5px]">No hay componentes configurados.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent history -->
        <?php if (!empty($resolvedIncidents)): ?>
            <div class="mt-8">
                <h2 class="font-display font-bold text-[16px] mb-3">Historial reciente</h2>
                <div class="space-y-2">
                    <?php foreach ($resolvedIncidents as $i): ?>
                        <div class="card card-pad flex items-start gap-3">
                            <i class="lucide lucide-check-circle-2 text-[16px] text-emerald-600 mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                <div class="font-display font-bold text-[13.5px]"><?= $e($i['title']) ?></div>
                                <div class="text-[11.5px] text-ink-400 mt-0.5"><?= $e($i['started_at']) ?> · resuelto <?= $e($i['resolved_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center text-[11.5px] text-ink-400 mt-12">Powered by Kydesk Helpdesk</div>
    </div>
</div>
