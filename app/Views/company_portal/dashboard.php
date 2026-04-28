<?php
use App\Core\Helpers;

$slug = $tenantPublic->slug;
$isManager = !empty($portalUser['is_company_manager']);

// Datasets para Chart.js
$labels = [];
$totals = [];
$resolved = [];
foreach ($monthly as $m) {
    $labels[] = $m['ym'];
    $totals[] = (int)$m['total'];
    $resolved[] = (int)$m['resolved'];
}

$statusMap = [
    'open'        => ['#3b82f6','Abiertos'],
    'in_progress' => ['#f59e0b','En progreso'],
    'on_hold'     => ['#6b7280','En espera'],
    'resolved'    => ['#16a34a','Resueltos'],
    'closed'      => ['#0f172a','Cerrados'],
];
$prMap = [
    'urgent' => ['#dc2626','Urgente'],
    'high'   => ['#f59e0b','Alta'],
    'medium' => ['#3b82f6','Media'],
    'low'    => ['#6b7280','Baja'],
];

$statusLabels = []; $statusValues = []; $statusColors = [];
foreach ($byStatus as $r) {
    [$col, $lbl] = $statusMap[$r['status']] ?? ['#94a3b8', $r['status']];
    $statusLabels[] = $lbl; $statusValues[] = (int)$r['n']; $statusColors[] = $col;
}
$prLabels = []; $prValues = []; $prColors = [];
foreach ($byPriority as $r) {
    [$col, $lbl] = $prMap[$r['priority']] ?? ['#94a3b8', $r['priority']];
    $prLabels[] = $lbl; $prValues[] = (int)$r['n']; $prColors[] = $col;
}
$catLabels = []; $catValues = []; $catColors = [];
foreach ($byCategory as $r) {
    $catLabels[] = $r['name']; $catValues[] = (int)$r['n']; $catColors[] = $r['color'] ?: '#94a3b8';
}

ob_start(); ?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-5">
    <div>
        <div class="text-[11px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">Resumen</div>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.02em]">Hola, <?= $e(explode(' ', $portalUser['name'])[0]) ?></h1>
        <p class="text-[13px] text-ink-500">Esto es lo que pasa con los tickets de <strong><?= $e($company['name']) ?></strong>.</p>
    </div>
    <?php if ($isManager): ?>
        <div class="flex gap-2">
            <a href="<?= $url('/portal/' . $slug . '/company/reports') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-bar-chart-3"></i> Ver reportes</a>
            <a href="<?= $url('/portal/' . $slug . '/company/export/tickets.csv') ?>" class="btn btn-outline btn-sm"><i class="lucide lucide-download"></i> CSV</a>
        </div>
    <?php endif; ?>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <?php
    $kpiCards = [
        ['Tickets totales',  (int)$kpis['total'],   '#7c5cff', 'inbox'],
        ['Abiertos',         (int)$kpis['open'],    '#f59e0b', 'circle-dot'],
        ['Resueltos',        (int)$kpis['resolved'],'#16a34a', 'check-circle'],
        ['Urgentes activos', (int)$kpis['urgent'],  '#dc2626', 'alert-triangle'],
    ];
    foreach ($kpiCards as [$lbl, $val, $col, $ic]): ?>
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400"><?= $e($lbl) ?></div>
                <div class="w-7 h-7 rounded-lg grid place-items-center" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[14px]"></i></div>
            </div>
            <div class="font-display font-extrabold text-[28px] tracking-[-0.02em]"><?= number_format($val) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Métricas secundarias -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
    <div class="card card-pad">
        <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">Primera respuesta</div>
        <div class="font-display font-extrabold text-[20px]"><?= $kpis['first_resp'] !== null ? round((float)$kpis['first_resp']) . ' min' : '—' ?></div>
        <div class="text-[11.5px] text-ink-400">Promedio histórico</div>
    </div>
    <div class="card card-pad">
        <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">Tiempo de resolución</div>
        <div class="font-display font-extrabold text-[20px]"><?= $kpis['resolve_t'] !== null ? round((float)$kpis['resolve_t'], 1) . ' h' : '—' ?></div>
        <div class="text-[11.5px] text-ink-400">Promedio histórico</div>
    </div>
    <div class="card card-pad">
        <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400 mb-1">CSAT promedio</div>
        <div class="font-display font-extrabold text-[20px]"><?= $kpis['csat_avg'] !== null ? round((float)$kpis['csat_avg'], 2) . ' / 5' : '—' ?></div>
        <div class="text-[11.5px] text-ink-400"><?= (int)$kpis['breached'] ?> con SLA incumplido</div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-6">
    <div class="card card-pad lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-display font-bold text-[15px]">Volumen últimos 6 meses</h3>
            <span class="badge bg-[#f3f4f6] text-ink-500"><?= count($labels) ?> meses</span>
        </div>
        <div style="position:relative;height:240px"><canvas id="chartMonthly"></canvas></div>
    </div>
    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px] mb-3">Por estado</h3>
        <div style="position:relative;height:240px"><canvas id="chartStatus"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-6">
    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px] mb-3">Por prioridad</h3>
        <div style="position:relative;height:240px"><canvas id="chartPriority"></canvas></div>
    </div>
    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px] mb-3">Top categorías</h3>
        <div style="position:relative;height:240px"><canvas id="chartCategory"></canvas></div>
    </div>
</div>

<!-- Recientes -->
<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-[#ececef]">
        <h3 class="font-display font-bold text-[15px]">Tickets recientes</h3>
        <a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="text-[12px] text-brand-600 hover:text-brand-700 font-semibold">Ver todos →</a>
    </div>
    <?php if (empty($recent)): ?>
        <div class="text-center py-12">
            <i class="lucide lucide-inbox text-[28px] text-ink-300"></i>
            <h3 class="font-display font-bold mt-3">Sin tickets aún</h3>
            <a href="<?= $url('/portal/' . $slug . '/company/tickets/new') ?>" class="btn btn-primary btn-sm mt-3 inline-flex"><i class="lucide lucide-plus"></i> Crear el primero</a>
        </div>
    <?php else: ?>
        <div class="divide-y" style="border-color:var(--border)">
            <?php foreach ($recent as $t):
                [$prCol, $prLbl] = $prMap[$t['priority']] ?? ['#6b7280', $t['priority']];
                [$stCol, $stLbl] = $statusMap[$t['status']] ?? ['#6b7280', $t['status']];
            ?>
                <a href="<?= $url('/portal/' . $slug . '/company/tickets/' . $t['id']) ?>" class="flex items-center gap-3 p-4 hover:bg-[#fafafb] transition" style="text-decoration:none;color:inherit">
                    <div class="w-9 h-9 rounded-xl grid place-items-center shrink-0" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><i class="lucide lucide-circle-dot text-[14px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-[11.5px] text-ink-500"><?= $e($t['code']) ?></span>
                            <span class="font-display font-bold text-[13.5px] truncate"><?= $e($t['subject']) ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-[11.5px] text-ink-400 mt-0.5">
                            <span><?= Helpers::ago($t['created_at']) ?></span>
                            <span>·</span>
                            <span><?= $e($t['requester_name']) ?></span>
                            <span class="badge" style="background:<?= $prCol ?>15;color:<?= $prCol ?>"><?= $prLbl ?></span>
                            <span class="badge" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><?= $stLbl ?></span>
                        </div>
                    </div>
                    <i class="lucide lucide-chevron-right text-[14px] text-ink-300 shrink-0"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const baseFont = { family: "Inter, system-ui, sans-serif", size: 11 };
    Chart.defaults.font = baseFont;
    Chart.defaults.color = '#6b6b78';
    Chart.defaults.borderColor = 'rgba(124,92,255,0.08)';

    const monthly = {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Total',     data: <?= json_encode($totals) ?>,    backgroundColor: 'rgba(124,92,255,0.85)', borderRadius: 8 },
            { label: 'Resueltos', data: <?= json_encode($resolved) ?>,  backgroundColor: 'rgba(22,163,74,0.85)',  borderRadius: 8 },
        ]
    };
    new Chart(document.getElementById('chartMonthly'), {
        type: 'bar', data: monthly,
        options: { responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($statusLabels) ?>, datasets: [{ data: <?= json_encode($statusValues) ?>, backgroundColor: <?= json_encode($statusColors) ?>, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } } }
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
})();
</script>
<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
