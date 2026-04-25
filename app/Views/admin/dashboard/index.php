<?php use App\Core\Helpers; ?>

<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #d946ef">
        <div class="admin-stat-label">Empresas Activas</div>
        <div class="admin-stat-value"><?= number_format($stats['active_tenants']) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1"><?= number_format($stats['demo_tenants']) ?> en demo · <?= number_format($stats['total_tenants']) ?> total</div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #7c5cff">
        <div class="admin-stat-label">MRR</div>
        <div class="admin-stat-value">$<?= number_format($stats['mrr'], 0) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1">ARR proyectado: $<?= number_format($stats['arr'], 0) ?></div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #22c55e">
        <div class="admin-stat-label">Ingresos Totales</div>
        <div class="admin-stat-value">$<?= number_format($stats['total_revenue'], 0) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1">Recibidos en pagos</div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #f59e0b">
        <div class="admin-stat-label">Por Cobrar</div>
        <div class="admin-stat-value">$<?= number_format($stats['pending_amount'], 0) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1"><?= number_format($stats['pending_invoices']) ?> facturas pendientes</div>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="admin-stat">
        <div class="admin-stat-label">Usuarios</div>
        <div class="admin-stat-value"><?= number_format($stats['total_users']) ?></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Tickets</div>
        <div class="admin-stat-value"><?= number_format($stats['total_tickets']) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1"><?= number_format($stats['open_tickets']) ?> abiertos</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Soporte SaaS</div>
        <div class="admin-stat-value"><?= number_format($stats['open_support']) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1">Tickets abiertos</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Demos</div>
        <div class="admin-stat-value"><?= number_format($stats['demo_tenants']) ?></div>
        <div class="text-[11.5px] text-ink-400 mt-1">Workspaces efímeros</div>
    </div>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="admin-card admin-card-pad">
        <div class="flex items-center justify-between mb-4">
            <h2 class="admin-h2">Crecimiento de empresas</h2>
            <span class="text-[11.5px] text-ink-400">Últimos 6 meses</span>
        </div>
        <canvas id="tenantsChart" height="120"></canvas>
    </div>
    <div class="admin-card admin-card-pad">
        <div class="flex items-center justify-between mb-4">
            <h2 class="admin-h2">Ingresos mensuales</h2>
            <span class="text-[11.5px] text-ink-400">USD</span>
        </div>
        <canvas id="revenueChart" height="120"></canvas>
    </div>
</div>

<!-- Recent tenants & invoices -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="admin-card">
        <div class="flex items-center justify-between p-5 pb-3">
            <h2 class="admin-h2">Empresas recientes</h2>
            <a href="<?= $url('/admin/tenants') ?>" class="text-[12px] font-semibold text-admin-700">Ver todas →</a>
        </div>
        <table class="admin-table">
            <thead><tr><th>Empresa</th><th>Plan</th><th>Usuarios</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($recentTenants as $t): ?>
                <tr style="cursor:pointer" onclick="location='<?= $url('/admin/tenants/' . $t['id']) ?>'">
                    <td>
                        <div style="font-weight:600"><?= $e($t['name']) ?></div>
                        <div class="text-[11px] text-ink-400 font-mono"><?= $e($t['slug']) ?></div>
                    </td>
                    <td><span class="admin-pill admin-pill-purple"><?= $e(ucfirst($t['plan'])) ?></span></td>
                    <td><?= (int)$t['users_count'] ?></td>
                    <td>
                        <?php if ($t['is_demo']): ?><span class="admin-pill admin-pill-amber">Demo</span>
                        <?php elseif ($t['is_active']): ?><span class="admin-pill admin-pill-green">Activa</span>
                        <?php else: ?><span class="admin-pill admin-pill-red">Inactiva</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentTenants)): ?>
                <tr><td colspan="4" style="text-align:center; padding:20px; color:#8e8e9a">Sin empresas registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-card">
        <div class="flex items-center justify-between p-5 pb-3">
            <h2 class="admin-h2">Facturas recientes</h2>
            <a href="<?= $url('/admin/invoices') ?>" class="text-[12px] font-semibold text-admin-700">Ver todas →</a>
        </div>
        <table class="admin-table">
            <thead><tr><th>Número</th><th>Empresa</th><th>Total</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($recentInvoices as $i): ?>
                <tr style="cursor:pointer" onclick="location='<?= $url('/admin/invoices/' . $i['id']) ?>'">
                    <td class="font-mono text-[12px]"><?= $e($i['invoice_number']) ?></td>
                    <td><?= $e($i['tenant_name'] ?? '—') ?></td>
                    <td>$<?= number_format($i['total'], 2) ?></td>
                    <td>
                        <?php
                        $statusColors = ['paid'=>'green','pending'=>'amber','overdue'=>'red','partial'=>'blue','draft'=>'gray','cancelled'=>'red','refunded'=>'gray'];
                        $col = $statusColors[$i['status']] ?? 'gray';
                        ?>
                        <span class="admin-pill admin-pill-<?= $col ?>"><?= $e(ucfirst($i['status'])) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentInvoices)): ?>
                <tr><td colspan="4" style="text-align:center; padding:20px; color:#8e8e9a">Sin facturas aún.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Plan distribution -->
<div class="admin-card admin-card-pad">
    <h2 class="admin-h2 mb-4">Distribución por plan</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <?php foreach ($planDistribution as $p): ?>
            <div style="padding:14px; border:1px solid #ececef; border-radius:12px">
                <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $e($p['plan']) ?></div>
                <div style="font-family:'Plus Jakarta Sans'; font-weight:700; font-size:24px; margin-top:4px"><?= (int)$p['count'] ?></div>
                <div class="text-[11px] text-ink-400">empresas</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tenantData = <?= json_encode($tenantsByMonth) ?>;
    const revenueData = <?= json_encode($revenueByMonth) ?>;

    if (document.getElementById('tenantsChart') && window.Chart) {
        new Chart(document.getElementById('tenantsChart'), {
            type: 'line',
            data: {
                labels: tenantData.map(d => d.month),
                datasets: [{
                    label: 'Nuevas empresas',
                    data: tenantData.map(d => d.count),
                    borderColor: '#d946ef',
                    backgroundColor: 'rgba(217,70,239,.1)',
                    tension: .35,
                    fill: true,
                }]
            },
            options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}, maintainAspectRatio:false }
        });
    }
    if (document.getElementById('revenueChart') && window.Chart) {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: revenueData.map(d => d.month),
                datasets: [{
                    label: 'Ingresos',
                    data: revenueData.map(d => d.total),
                    backgroundColor: 'rgba(124,92,255,.7)',
                    borderRadius: 8,
                }]
            },
            options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}, maintainAspectRatio:false }
        });
    }
});
</script>
