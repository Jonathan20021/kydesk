<?php use App\Core\Helpers; use App\Core\Prefs; $slug = $tenant->slug; $u = $auth->user();
$prefs = Prefs::get($u);
$priColor = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
$resolutionRate = ($stats['total'] ?? 0) > 0 ? round((($stats['resolved'] ?? 0) / $stats['total']) * 100) : 0;
$avgFirstResp = $stats['avg_first_response'] ?? 2.4;
$slaCompliance = $stats['sla_compliance'] ?? 92;
$widgetsOn = ['show_hero','show_stats','show_tickets_grid','show_inbox','show_team','show_sla','show_todos'];
$anyWidgetVisible = false;
foreach ($widgetsOn as $wk) { if (!empty($prefs[$wk])) { $anyWidgetVisible = true; break; } }
?>

<?php if (!$anyWidgetVisible): ?>
<div class="card card-pad">
    <div class="empty-state" style="padding:48px 24px">
        <div class="empty-illust"><i class="lucide lucide-layout-dashboard text-[28px]"></i></div>
        <div class="empty-state-title">Tu dashboard está vacío</div>
        <p class="empty-state-text">Activaste al menos un widget desde Personalizar panel para ver datos aquí.</p>
        <a href="<?= $url('/t/' . $slug . '/preferences') ?>" class="btn btn-primary btn-sm mt-4 inline-flex"><i class="lucide lucide-sliders-horizontal"></i> Personalizar panel</a>
    </div>
</div>
<?php endif; ?>

<?php if ($prefs['show_hero']): ?>
<!-- HERO CARD -->
<div class="hero-card">
    <div class="hero-stars">
        <svg viewBox="0 0 280 200" fill="none">
            <path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/>
            <path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/>
            <path d="M220 130 L223 145 L238 148 L223 151 L220 166 L217 151 L202 148 L217 145 Z" fill="white"/>
            <path d="M40 30 L42 38 L50 40 L42 42 L40 50 L38 42 L30 40 L38 38 Z" fill="white"/>
            <path d="M250 60 L252 68 L260 70 L252 72 L250 80 L248 72 L240 70 L248 68 Z" fill="white"/>
        </svg>
    </div>
    <div class="relative">
        <div class="hero-tag">Buenos días, <?= $e(explode(' ', $u['name'])[0]) ?></div>
        <h1 class="hero-title">Resuelve más, más rápido<br>con tu equipo en Kydesk</h1>
        <div class="flex items-center gap-3 mt-6">
            <a href="<?= $url('/t/' . $slug . '/tickets/create') ?>" class="hero-cta">
                Nuevo ticket
                <span class="hero-cta-arrow"><i class="lucide lucide-arrow-right"></i></span>
            </a>
            <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-full bg-white/15 backdrop-blur text-white text-[13px] font-semibold border border-white/20 hover:bg-white/25 transition"><i class="lucide lucide-list"></i> Ver bandeja</a>
        </div>

        <div class="hero-kpi">
            <div class="hero-kpi-item">
                <div class="hero-kpi-label">Resolución</div>
                <div class="hero-kpi-value"><?= $resolutionRate ?>%</div>
            </div>
            <div class="hero-kpi-item">
                <div class="hero-kpi-label">Cumplimiento SLA</div>
                <div class="hero-kpi-value"><?= $slaCompliance ?>%</div>
            </div>
            <div class="hero-kpi-item">
                <div class="hero-kpi-label">1ª respuesta</div>
                <div class="hero-kpi-value"><?= number_format($avgFirstResp, 1) ?>h</div>
            </div>
            <div class="hero-kpi-item ml-auto">
                <div class="hero-kpi-label">Total mes</div>
                <div class="hero-kpi-value"><?= (int)($stats['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($prefs['show_stats']): ?>
<!-- STAT MINI CARDS -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <?php
    $statCards = [
        ['Abiertos','ticket', $stats['open'] ?? 0, $stats['total'] ?? 0, '#dbeafe','#1d4ed8','#3b82f6','+2'],
        ['En progreso','clock-9', $stats['in_progress'] ?? 0, $stats['total'] ?? 0, '#fef3c7','#b45309','#f59e0b','+1'],
        ['Resueltos','check-circle-2', $stats['resolved'] ?? 0, $stats['total'] ?? 0, '#d1fae5','#047857','#16a34a','+5'],
    ];
    foreach ($statCards as [$l,$ic,$v,$total,$bg,$col,$lineCol,$delta]):
        $points = [3,4,2,5,3,6,4,7,5,6];
        $max = !empty($points) ? max($points) : 1;
        if ($max <= 0) $max = 1;
        $sparkPath = '';
        $w = 60; $h = 28;
        $denom = max(count($points) - 1, 1);
        foreach ($points as $i => $p) {
            $x = round(($i / $denom) * $w, 1);
            $y = round($h - ($p / $max) * $h, 1);
            $sparkPath .= ($i === 0 ? "M $x $y" : " L $x $y");
        }
    ?>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?>"></i></div>
            <div class="min-w-0 flex-1">
                <div class="stat-mini-meta"><?= $v ?>/<?= $total ?> tickets <span class="trend-up ml-1"><i class="lucide lucide-trending-up"></i><?= $delta ?></span></div>
                <div class="stat-mini-title"><?= $l ?></div>
            </div>
            <svg class="sparkline" viewBox="0 0 60 28" fill="none">
                <path d="<?= $sparkPath ?>" stroke="<?= $lineCol ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($prefs['show_tickets_grid']): ?>
<!-- TICKETS ACTIVOS -->
<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="section-title">Tickets activos</h2>
            <p class="text-[12px] mt-0.5 text-ink-400">Lo más reciente que requiere tu atención</p>
        </div>
        <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="section-link inline-flex items-center gap-1">Ver todos <i class="lucide lucide-arrow-right text-[12px]"></i></a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $catBgMap = ['Hardware'=>['#fef3c7','#b45309'],'Software'=>['#dbeafe','#1d4ed8'],'Red e infraestructura'=>['#d1fae5','#047857'],'Cuentas y accesos'=>['#fce7f3','#be185d'],'Seguridad'=>['#fee2e2','#b91c1c'],'Otros'=>['#f3f4f6','#6b6b78']];
        $heroIcons = ['Hardware'=>'cpu','Software'=>'package','Red e infraestructura'=>'wifi','Cuentas y accesos'=>'key-round','Seguridad'=>'shield-alert','Otros'=>'folder'];
        foreach (array_slice($recentTickets, 0, 3) as $t):
            [$bg, $col] = $catBgMap[$t['category_name'] ?? 'Otros'] ?? ['#f3e8ff','#7e22ce'];
            $catIcon = $heroIcons[$t['category_name'] ?? ''] ?? 'inbox';
            $progress = $t['status']==='resolved'||$t['status']==='closed'?100:($t['status']==='in_progress'?60:($t['status']==='on_hold'?40:20));
        ?>
            <a href="<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>" class="media-card block">
                <div class="media-card-image" style="background: <?= $bg ?>; color: <?= $col ?>">
                    <i class="lucide lucide-<?= $catIcon ?>"></i>
                    <?php if ($t['category_name']): ?>
                        <span class="media-card-tag" style="color:<?= $col ?>"><?= strtoupper($t['category_name']) ?></span>
                    <?php endif; ?>
                    <span class="media-card-fav"><i class="lucide lucide-bookmark text-[14px]"></i></span>
                </div>
                <div class="media-card-body">
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <span class="text-[10.5px] font-mono text-ink-400"><?= $e($t['code']) ?></span>
                        <?= Helpers::priorityBadge($t['priority']) ?>
                    </div>
                    <h3 class="media-card-title line-clamp-2"><?= $e($t['subject']) ?></h3>
                    <div class="media-card-progress"><div class="media-card-progress-bar" style="width: <?= $progress ?>%; background:<?= $priColor[$t['priority']] ?? '#7c5cff' ?>"></div></div>
                    <div class="media-card-foot">
                        <?php if ($t['assigned_name']): ?>
                            <div class="avatar avatar-sm" style="background: <?= Helpers::colorFor($t['assigned_email'] ?? '') ?>;color:white;"><?= Helpers::initials($t['assigned_name']) ?></div>
                            <div class="min-w-0">
                                <div class="text-[12.5px] font-display font-bold truncate"><?= $e($t['assigned_name']) ?></div>
                                <div class="text-[10.5px] text-ink-400">Técnico</div>
                            </div>
                        <?php else: ?>
                            <div class="avatar avatar-sm bg-[#f3f4f6] text-ink-400"><i class="lucide lucide-user-x text-[13px]"></i></div>
                            <div class="text-[12px] text-ink-400">Sin asignar</div>
                        <?php endif; ?>
                        <span class="ml-auto text-[10.5px] text-ink-400"><?= Helpers::ago($t['created_at']) ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (empty($recentTickets)): ?>
            <div class="md:col-span-2 lg:col-span-3 card card-pad">
                <div class="empty-state">
                    <div class="empty-illust"><i class="lucide lucide-inbox text-[28px]"></i></div>
                    <div class="empty-state-title">Bandeja vacía</div>
                    <p class="empty-state-text">Cuando entren nuevos tickets aparecerán aquí.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($prefs['show_inbox'] || $prefs['show_team']): ?>
<!-- BANDEJA + ESTADÍSTICA + TU EQUIPO -->
<div class="grid grid-cols-1 <?= $prefs['show_inbox'] && $prefs['show_team'] ? 'lg:grid-cols-3' : '' ?> gap-4">
    <?php if ($prefs['show_inbox']): ?>
    <div class="<?= $prefs['show_team'] ? 'lg:col-span-2' : '' ?> card overflow-hidden">
        <div class="px-6 pt-5 flex items-center justify-between">
            <div>
                <h3 class="section-title">Tu bandeja</h3>
                <p class="text-[12px] mt-0.5 text-ink-400">Tickets recientes asignados</p>
            </div>
            <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="section-link">Ver todos</a>
        </div>
        <table class="table mt-3">
            <thead>
                <tr><th>Asignado</th><th>Tipo</th><th>Asunto</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($recentTickets, 0, 5) as $t): ?>
                    <tr onclick="location.href='<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>'" style="cursor:pointer">
                        <td>
                            <?php if ($t['assigned_name']): ?>
                                <div class="flex items-center gap-2.5">
                                    <div class="avatar avatar-md" style="background: <?= Helpers::colorFor($t['assigned_email'] ?? '') ?>;color:white;"><?= Helpers::initials($t['assigned_name']) ?></div>
                                    <div>
                                        <div class="font-display font-bold text-[13px]"><?= $e($t['assigned_name']) ?></div>
                                        <div class="text-[11px] text-ink-400"><?= date('d/m/Y', strtotime($t['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2.5">
                                    <div class="avatar avatar-md bg-[#f3f4f6] text-ink-400"><i class="lucide lucide-user-x text-[14px]"></i></div>
                                    <span class="text-[12px] text-ink-400">Sin asignar</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['category_name']): ?>
                                <span class="badge badge-pink"><span class="dot" style="background: <?= $e($t['category_color']) ?>"></span> <?= $e($t['category_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><div class="text-[13px] font-medium line-clamp-1 max-w-[280px]"><?= $e($t['subject']) ?></div></td>
                        <td class="text-right"><span class="table-action"><i class="lucide lucide-arrow-up-right text-[13px]"></i></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentTickets)): ?><tr><td colspan="4" class="text-center py-12 text-ink-400">Sin tickets registrados</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($prefs['show_team']): ?>
    <div class="space-y-4">
        <div class="card card-pad">
            <div class="flex items-center justify-between">
                <h3 class="section-title">Cumplimiento SLA</h3>
                <span class="badge badge-emerald"><i class="lucide lucide-shield-check text-[11px]"></i> Buen estado</span>
            </div>

            <div class="donut-stat mt-3">
                <svg viewBox="0 0 168 168" class="absolute inset-0" style="transform:rotate(-90deg)">
                    <circle cx="84" cy="84" r="76" fill="none" stroke="#e7e0ff" stroke-width="14"/>
                    <circle cx="84" cy="84" r="76" fill="none" stroke="#7c5cff" stroke-width="14" stroke-linecap="round" stroke-dasharray="<?= round($slaCompliance * 4.77) ?> 477"/>
                </svg>
                <div class="donut-stat-inner" style="background:white">
                    <div class="text-center">
                        <div class="font-display font-extrabold text-[34px] tracking-[-0.03em] leading-none text-brand-700"><?= $slaCompliance ?>%</div>
                        <div class="text-[10.5px] mt-1.5 font-bold uppercase tracking-[0.16em] text-ink-400">A tiempo</div>
                    </div>
                </div>
                <div class="donut-stat-badge"><i class="lucide lucide-trending-up text-[10px]"></i> +4%</div>
            </div>

            <div class="font-display font-bold text-[16px] text-center mt-5">¡Sigue así, <?= $e(explode(' ', $u['name'])[0]) ?>! 🔥</div>
            <p class="text-center text-[12px] mt-1 text-ink-400">Tu equipo está cumpliendo metas</p>

            <div class="mt-5">
                <div class="flex items-center justify-between text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400 mb-2">
                    <span>Esta semana</span><span>Tickets / día</span>
                </div>
                <div class="bar-chart">
                    <?php
                    $rawSeries = is_array($series ?? null) ? $series : [];
                    $values = array_values(array_slice(array_map(fn($s) => (int)($s['created'] ?? 0), $rawSeries), -4));
                    if (empty($values)) $values = [0,0,0,0];
                    $max = max(max($values), 1);
                    foreach ($values as $i => $v): $h = $max > 0 ? round($v / $max * 100) : 0; ?>
                        <div class="bar <?= $i!==2?'bar-soft':'' ?>" style="height: <?= max($h,8) ?>%" data-tooltip="<?= $v ?>"></div>
                    <?php endforeach; ?>
                </div>
                <div class="bar-labels"><span>1-7</span><span>8-14</span><span>15-21</span><span>22-28</span></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="flex items-center justify-between mb-4">
                <h3 class="section-title">Tu equipo</h3>
                <button class="w-9 h-9 rounded-full bg-brand-50 text-brand-600 border border-brand-100 grid place-items-center hover:bg-brand-100 transition"><i class="lucide lucide-plus text-base"></i></button>
            </div>
            <div class="space-y-1">
                <?php foreach (array_slice($topTechs, 0, 3) as $t): ?>
                    <div class="mentor-row">
                        <div class="relative">
                            <div class="avatar avatar-md" style="background: <?= Helpers::colorFor($t['email']) ?>;color:white;"><?= Helpers::initials($t['name']) ?></div>
                            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="mentor-row-name truncate"><?= $e($t['name']) ?></div>
                            <div class="mentor-row-role">Técnico · <?= (int)$t['resolved'] ?> resueltos</div>
                        </div>
                        <button class="btn-follow"><i class="lucide lucide-user-plus text-[11px]"></i> Seguir</button>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($topTechs)): ?>
                    <div class="empty-state py-6">
                        <div class="empty-illust" style="width:56px;height:56px;border-radius:18px"><i class="lucide lucide-users text-[20px]"></i></div>
                        <div class="empty-state-title text-[13px]">Sin técnicos aún</div>
                        <p class="empty-state-text">Invita a tu equipo a Kydesk</p>
                    </div>
                <?php endif; ?>
            </div>
            <a href="<?= $url('/t/' . $slug . '/users') ?>" class="see-all mt-4">Ver todos</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($prefs['show_sla'] || $prefs['show_todos']): ?>
<!-- SLA + PENDIENTES -->
<div class="grid grid-cols-1 <?= $prefs['show_sla'] && $prefs['show_todos'] ? 'lg:grid-cols-2' : '' ?> gap-4">
    <?php if ($prefs['show_sla']): ?>
    <div class="card">
        <div class="px-6 pt-5 flex items-center justify-between">
            <div>
                <h3 class="section-title flex items-center gap-2"><i class="lucide lucide-alarm-clock text-[18px] text-rose-500"></i> SLA en riesgo</h3>
                <p class="text-[12px] mt-0.5 text-ink-400">Tickets que vencen pronto</p>
            </div>
            <?php if (count($atRiskTickets) > 0): ?>
                <span class="badge badge-rose"><?= count($atRiskTickets) ?> en riesgo</span>
            <?php else: ?>
                <span class="badge badge-emerald"><i class="lucide lucide-check text-[11px]"></i> Al día</span>
            <?php endif; ?>
        </div>
        <div class="px-3 pb-4 mt-3">
            <?php if (empty($atRiskTickets)): ?>
                <div class="empty-state">
                    <div class="empty-illust" style="background:linear-gradient(135deg,#d1fae5,white);color:#16a34a;border-color:#a7f3d0"><i class="lucide lucide-shield-check text-[28px]"></i></div>
                    <div class="empty-state-title">Sin tickets en riesgo</div>
                    <p class="empty-state-text">Tu equipo está cumpliendo todos los SLAs</p>
                </div>
            <?php endif; ?>
            <?php foreach ($atRiskTickets as $t): ?>
                <a href="<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>" class="sla-row">
                    <div class="sla-bar" style="background: <?= $priColor[$t['priority']] ?? '#7c5cff' ?>"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10.5px] font-mono text-ink-400"><?= $e($t['code']) ?></span>
                            <?= Helpers::priorityBadge($t['priority']) ?>
                        </div>
                        <div class="text-[13px] font-medium truncate"><?= $e($t['subject']) ?></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-[11px] font-bold text-rose-600 flex items-center gap-1"><i class="lucide lucide-clock text-[11px]"></i><?= $t['sla_due_at'] ? date('H:i', strtotime($t['sla_due_at'])) : '' ?></div>
                        <div class="text-[10px] text-ink-400 mt-0.5">vence hoy</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($prefs['show_todos']): ?>
    <div class="card">
        <div class="px-6 pt-5 flex items-center justify-between">
            <div>
                <h3 class="section-title flex items-center gap-2"><i class="lucide lucide-list-checks text-[18px] text-brand-500"></i> Mis pendientes</h3>
                <p class="text-[12px] mt-0.5 text-ink-400"><?= count($myTodos) ?> tareas activas</p>
            </div>
            <a href="<?= $url('/t/' . $slug . '/todos') ?>" class="section-link">Ver todas</a>
        </div>
        <div class="px-3 pb-4 mt-3">
            <?php if (empty($myTodos)): ?>
                <div class="empty-state">
                    <div class="empty-illust"><i class="lucide lucide-party-popper text-[28px]"></i></div>
                    <div class="empty-state-title">Todo al día</div>
                    <p class="empty-state-text">No tienes tareas pendientes ahora mismo</p>
                </div>
            <?php endif; ?>
            <?php foreach ($myTodos as $td): ?>
                <div class="todo-row">
                    <div class="check-circle priority-<?= $e($td['priority']) ?>"></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-medium leading-snug"><?= $e($td['title']) ?></div>
                        <div class="text-[11px] mt-1 text-ink-400 flex items-center gap-1.5">
                            <i class="lucide lucide-clock text-[11px]"></i> <?= Helpers::ago($td['created_at']) ?>
                        </div>
                    </div>
                    <?= Helpers::priorityBadge($td['priority']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
