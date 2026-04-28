<?php
$slug = $tenantPublic->slug;

$prMap = ['urgent'=>['#dc2626','Urgente'],'high'=>['#f59e0b','Alta'],'medium'=>['#3b82f6','Media'],'low'=>['#6b7280','Baja']];
$stMap = [
    'open'        => ['#3b82f6','Abierto'],
    'in_progress' => ['#f59e0b','En progreso'],
    'on_hold'     => ['#6b7280','En espera'],
    'resolved'    => ['#16a34a','Resuelto'],
    'closed'      => ['#0f172a','Cerrado'],
];

$dailyLabels = []; $dailyTotals = []; $dailyResolved = [];
foreach ($daily as $r) { $dailyLabels[] = $r['d']; $dailyTotals[] = (int)$r['total']; $dailyResolved[] = (int)$r['resolved']; }

$prLabels = []; $prValues = []; $prColors = [];
foreach ($byPriority as $r) { [$col, $lbl] = $prMap[$r['priority']] ?? ['#94a3b8', $r['priority']]; $prLabels[] = $lbl; $prValues[] = (int)$r['n']; $prColors[] = $col; }
$stLabels = []; $stValues = []; $stColors = [];
foreach ($byStatus as $r) { [$col, $lbl] = $stMap[$r['status']] ?? ['#94a3b8', $r['status']]; $stLabels[] = $lbl; $stValues[] = (int)$r['n']; $stColors[] = $col; }
$catLabels = []; $catValues = []; $catColors = [];
foreach ($byCategory as $r) { $catLabels[] = $r['name']; $catValues[] = (int)$r['n']; $catColors[] = $r['color'] ?: '#94a3b8'; }
$chLabels = []; $chValues = [];
foreach ($byChannel as $r) { $chLabels[] = ucfirst($r['channel']); $chValues[] = (int)$r['n']; }

$delta = function ($curr, $prev) {
    $curr = (float)$curr; $prev = (float)$prev;
    if ($prev <= 0) return $curr > 0 ? '+∞' : '0%';
    $pct = (($curr - $prev) / $prev) * 100;
    return ($pct >= 0 ? '+' : '') . round($pct) . '%';
};

ob_start(); ?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-5">
    <div>
        <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em]">Reportes</h1>
        <p class="text-[12.5px] text-ink-500">Análisis de tickets de <strong><?= $e($company['name']) ?></strong> en los últimos <?= (int)$rangeDays ?> días.</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" class="flex items-center gap-2">
            <select name="days" class="input" style="width:auto" onchange="this.form.submit()">
                <?php foreach ([7=>'7 días',14=>'14 días',30=>'30 días',60=>'60 días',90=>'90 días',180=>'6 meses',365=>'1 año'] as $k=>$lbl): ?>
                    <option value="<?= $k ?>" <?= (int)$rangeDays===$k?'selected':'' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?= $url('/portal/' . $slug . '/company/export/report.csv?days=' . (int)$rangeDays) ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-download"></i> CSV</a>
        <a href="<?= $url('/portal/' . $slug . '/company/export/report.pdf?days=' . (int)$rangeDays) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="lucide lucide-printer"></i> PDF</a>
    </div>
</div>

<!-- KPIs del período -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <?php
    $cards = [
        ['Tickets',     (int)($totals['total'] ?? 0),    (int)($prev['total'] ?? 0),    '#7c5cff','inbox'],
        ['Resueltos',   (int)($totals['resolved'] ?? 0), (int)($prev['resolved'] ?? 0), '#16a34a','check-circle'],
        ['SLA breach',  (int)($totals['breached'] ?? 0), null, '#dc2626','alert-triangle'],
        ['CSAT',        $totals['csat'] !== null ? round((float)$totals['csat'], 2) : null, null, '#f59e0b','star'],
    ];
    foreach ($cards as [$lbl, $val, $prevVal, $col, $ic]): ?>
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400"><?= $e($lbl) ?></div>
                <div class="w-7 h-7 rounded-lg grid place-items-center" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[14px]"></i></div>
            </div>
            <div class="font-display font-extrabold text-[26px] tracking-[-0.02em]"><?= $val === null ? '—' : (is_float($val) ? $val : number_format($val)) ?></div>
            <?php if ($prevVal !== null): ?>
                <div class="text-[11px] text-ink-400 mt-0.5">vs <?= number_format($prevVal) ?> período anterior · <span class="font-semibold text-ink-700"><?= $delta($val, $prevVal) ?></span></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
    <div class="card card-pad">
        <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">Primera respuesta</div>
        <div class="font-display font-extrabold text-[20px]"><?= $totals['first_resp'] !== null ? round((float)$totals['first_resp']) . ' min' : '—' ?></div>
    </div>
    <div class="card card-pad">
        <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">Tiempo de resolución</div>
        <div class="font-display font-extrabold text-[20px]"><?= $totals['resolve_t'] !== null ? round((float)$totals['resolve_t'], 1) . ' h' : '—' ?></div>
        <?php if (!empty($prev['resolve_t'])): ?>
            <div class="text-[11px] text-ink-400 mt-0.5">vs <?= round((float)$prev['resolve_t'], 1) ?>h anterior · <span class="font-semibold"><?= $delta($totals['resolve_t'] ?? 0, $prev['resolve_t']) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="card card-pad">
        <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">Abiertos al final</div>
        <div class="font-display font-extrabold text-[20px]"><?= number_format((int)($totals['open'] ?? 0)) ?></div>
    </div>
</div>

<!-- Charts -->
<div class="card card-pad mb-3">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-display font-bold text-[15px]">Tickets por día</h3>
        <span class="badge bg-[#f3f4f6] text-ink-500"><?= count($dailyLabels) ?> días con actividad</span>
    </div>
    <canvas id="chartDaily" height="100"></canvas>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
    <div class="card card-pad"><h3 class="font-display font-bold text-[15px] mb-3">Por estado</h3><canvas id="chartStatus" height="170"></canvas></div>
    <div class="card card-pad"><h3 class="font-display font-bold text-[15px] mb-3">Por prioridad</h3><canvas id="chartPriority" height="170"></canvas></div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
    <div class="card card-pad"><h3 class="font-display font-bold text-[15px] mb-3">Top categorías</h3><canvas id="chartCategory" height="180"></canvas></div>
    <div class="card card-pad"><h3 class="font-display font-bold text-[15px] mb-3">Por canal</h3><canvas id="chartChannel" height="180"></canvas></div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-[#ececef]"><h3 class="font-display font-bold text-[14px]">Top solicitantes</h3></div>
        <table class="w-full text-[12.5px]">
            <thead class="bg-[#fafafb]"><tr><th class="text-left py-2 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Persona</th><th class="text-right py-2 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Tickets</th></tr></thead>
            <tbody>
                <?php foreach ($byRequester as $r): ?>
                    <tr class="border-b border-[#ececef]">
                        <td class="py-2 px-4">
                            <div class="font-semibold"><?= $e($r['name']) ?></div>
                            <div class="text-[11px] text-ink-400 truncate"><?= $e($r['email']) ?></div>
                        </td>
                        <td class="py-2 px-4 text-right font-display font-bold"><?= (int)$r['n'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($byRequester)): ?><tr><td colspan="2" class="text-center py-6 text-ink-400">Sin datos</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-[#ececef]"><h3 class="font-display font-bold text-[14px]">Agentes asignados</h3></div>
        <table class="w-full text-[12.5px]">
            <thead class="bg-[#fafafb]"><tr><th class="text-left py-2 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Agente</th><th class="text-right py-2 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Tickets</th></tr></thead>
            <tbody>
                <?php foreach ($byAgent as $r): ?>
                    <tr class="border-b border-[#ececef]"><td class="py-2 px-4 font-semibold"><?= $e($r['name']) ?></td><td class="py-2 px-4 text-right font-display font-bold"><?= (int)$r['n'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($byAgent)): ?><tr><td colspan="2" class="text-center py-6 text-ink-400">Sin datos</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    Chart.defaults.font = { family: "Inter, system-ui, sans-serif", size: 11 };
    Chart.defaults.color = '#6b6b78';

    new Chart(document.getElementById('chartDaily'), {
        type: 'line',
        data: {
            labels: <?= json_encode($dailyLabels) ?>,
            datasets: [
                { label: 'Total',     data: <?= json_encode($dailyTotals) ?>,    borderColor: '#7c5cff', backgroundColor: 'rgba(124,92,255,0.12)', tension: 0.35, fill: true, borderWidth: 2 },
                { label: 'Resueltos', data: <?= json_encode($dailyResolved) ?>,  borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.10)',  tension: 0.35, fill: true, borderWidth: 2 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($stLabels) ?>, datasets: [{ data: <?= json_encode($stValues) ?>, backgroundColor: <?= json_encode($stColors) ?>, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('chartPriority'), {
        type: 'bar',
        data: { labels: <?= json_encode($prLabels) ?>, datasets: [{ data: <?= json_encode($prValues) ?>, backgroundColor: <?= json_encode($prColors) ?>, borderRadius: 8 }] },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartCategory'), {
        type: 'bar',
        data: { labels: <?= json_encode($catLabels) ?>, datasets: [{ data: <?= json_encode($catValues) ?>, backgroundColor: <?= json_encode($catColors) ?>, borderRadius: 8 }] },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartChannel'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($chLabels) ?>, datasets: [{ data: <?= json_encode($chValues) ?>, backgroundColor: ['#7c5cff','#16a34a','#f59e0b','#3b82f6','#94a3b8'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
    });
})();
</script>
<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
