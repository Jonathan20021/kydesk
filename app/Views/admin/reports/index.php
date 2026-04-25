<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #d946ef"><div class="admin-stat-label">MRR</div><div class="admin-stat-value">$<?= number_format($kpis['mrr'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #7c5cff"><div class="admin-stat-label">ARR proyectado</div><div class="admin-stat-value">$<?= number_format($kpis['arr'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #22c55e"><div class="admin-stat-label">Ingresos totales</div><div class="admin-stat-value">$<?= number_format($kpis['revenue_total'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #f59e0b"><div class="admin-stat-label">Por cobrar</div><div class="admin-stat-value">$<?= number_format($kpis['invoice_pending'],0) ?></div></div>

    <div class="admin-stat"><div class="admin-stat-label">Ingreso este mes</div><div class="admin-stat-value">$<?= number_format($kpis['revenue_month'],0) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Ingreso este año</div><div class="admin-stat-value">$<?= number_format($kpis['revenue_year'],0) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">ARPU promedio</div><div class="admin-stat-value">$<?= number_format($kpis['avg_arpu'],2) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Tasa de churn</div><div class="admin-stat-value"><?= $kpis['total_tenants']>0 ? round($kpis['churned']*100/$kpis['total_tenants'],1) : 0 ?>%</div></div>
</div>

<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="admin-stat"><div class="admin-stat-label">Total empresas</div><div class="admin-stat-value"><?= number_format($kpis['total_tenants']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Empresas pagas</div><div class="admin-stat-value"><?= number_format($kpis['paying_tenants']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">En trial</div><div class="admin-stat-value"><?= number_format($kpis['trial_tenants']) ?></div></div>
    <div class="admin-stat"><div class="admin-stat-label">Canceladas</div><div class="admin-stat-value"><?= number_format($kpis['churned']) ?></div></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-4">Ingresos mensuales (12 meses)</h2>
        <div class="admin-chart-wrap"><canvas id="rChart"></canvas></div>
    </div>
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-4">Nuevas empresas por mes</h2>
        <div class="admin-chart-wrap"><canvas id="tChart"></canvas></div>
    </div>
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-4">Usuarios nuevos por mes</h2>
        <div class="admin-chart-wrap"><canvas id="uChart"></canvas></div>
    </div>
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-4">Tickets por mes</h2>
        <div class="admin-chart-wrap"><canvas id="tkChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-4">Por plan</h2>
        <table class="admin-table" style="margin:0">
            <thead><tr><th>Plan</th><th>Empresas</th><th>MRR</th></tr></thead>
            <tbody>
            <?php foreach ($byPlan as $bp): ?>
                <tr>
                    <td><span class="admin-pill" style="background:<?= $e($bp['color']) ?>22; color:<?= $e($bp['color']) ?>"><?= $e($bp['plan']) ?></span></td>
                    <td><?= (int)$bp['tenants'] ?></td>
                    <td>$<?= number_format($bp['mrr'], 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="admin-card admin-card-pad">
        <h2 class="admin-h2 mb-4">Top 10 empresas (por ingresos)</h2>
        <table class="admin-table" style="margin:0">
            <thead><tr><th>Empresa</th><th>Usuarios</th><th>Tickets</th><th>$ Ingresos</th></tr></thead>
            <tbody>
            <?php foreach ($topTenants as $tt): ?>
                <tr><td><?= $e($tt['name']) ?></td><td><?= (int)$tt['users'] ?></td><td><?= (int)$tt['tickets'] ?></td><td>$<?= number_format($tt['revenue'], 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.Chart) return;
    const opts = { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}, maintainAspectRatio:false };
    const data = {
        r: <?= json_encode($revenueByMonth) ?>,
        t: <?= json_encode($tenantsByMonth) ?>,
        u: <?= json_encode($usersByMonth) ?>,
        tk: <?= json_encode($ticketsByMonth) ?>,
    };
    const make = (id, set, color, type='bar') => {
        const el = document.getElementById(id); if (!el) return;
        new Chart(el, { type, data: { labels: set.map(d=>d.m), datasets: [{ label:'', data: set.map(d=>parseFloat(d.c)), backgroundColor: color+'CC', borderColor: color, tension:.35, fill: type==='line' }] }, options: opts });
    };
    make('rChart', data.r, '#7c5cff');
    make('tChart', data.t, '#d946ef', 'line');
    make('uChart', data.u, '#22c55e', 'line');
    make('tkChart', data.tk, '#f59e0b');
});
</script>
