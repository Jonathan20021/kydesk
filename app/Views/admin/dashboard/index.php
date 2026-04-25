<?php use App\Core\Helpers; ?>

<!-- Primary KPIs -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:var(--brand-50); color:var(--brand-700)"><i class="lucide lucide-building-2 text-[16px]"></i></div>
        <div class="admin-stat-label">Empresas activas</div>
        <div class="admin-stat-value"><?= number_format($stats['active_tenants']) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)"><?= number_format($stats['demo_tenants']) ?> en demo · <?= number_format($stats['total_tenants']) ?> total</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#ede9fe; color:#5b21b6"><i class="lucide lucide-trending-up text-[16px]"></i></div>
        <div class="admin-stat-label">MRR</div>
        <div class="admin-stat-value">$<?= number_format($stats['mrr'], 0) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)">ARR proyectado: $<?= number_format($stats['arr'], 0) ?></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#d1fae5; color:#047857"><i class="lucide lucide-banknote text-[16px]"></i></div>
        <div class="admin-stat-label">Ingresos totales</div>
        <div class="admin-stat-value">$<?= number_format($stats['total_revenue'], 0) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)">Recibidos en pagos</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#fef3c7; color:#b45309"><i class="lucide lucide-clock text-[16px]"></i></div>
        <div class="admin-stat-label">Por cobrar</div>
        <div class="admin-stat-value">$<?= number_format($stats['pending_amount'], 0) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)"><?= number_format($stats['pending_invoices']) ?> facturas pendientes</div>
    </div>
</div>

<!-- Secondary KPIs -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="admin-stat">
        <div class="admin-stat-label">Usuarios</div>
        <div class="admin-stat-value"><?= number_format($stats['total_users']) ?></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Tickets</div>
        <div class="admin-stat-value"><?= number_format($stats['total_tickets']) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)"><?= number_format($stats['open_tickets']) ?> abiertos</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Soporte SaaS</div>
        <div class="admin-stat-value"><?= number_format($stats['open_support']) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)">Tickets abiertos</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Demos</div>
        <div class="admin-stat-value"><?= number_format($stats['demo_tenants']) ?></div>
        <div class="text-[11.5px] mt-1" style="color:var(--ink-400)">Workspaces efímeros</div>
    </div>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="admin-card admin-card-pad">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="admin-h2">Crecimiento de empresas</h2>
                <div class="text-[11.5px] mt-0.5" style="color:var(--ink-400)">Últimos 6 meses</div>
            </div>
            <span class="admin-pill admin-pill-purple"><i class="lucide lucide-trending-up text-[10px]"></i> Tenants</span>
        </div>
        <div class="admin-chart-wrap"><canvas id="tenantsChart"></canvas></div>
    </div>
    <div class="admin-card admin-card-pad">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="admin-h2">Ingresos mensuales</h2>
                <div class="text-[11.5px] mt-0.5" style="color:var(--ink-400)">USD · últimos 6 meses</div>
            </div>
            <span class="admin-pill admin-pill-green"><i class="lucide lucide-banknote text-[10px]"></i> Revenue</span>
        </div>
        <div class="admin-chart-wrap"><canvas id="revenueChart"></canvas></div>
    </div>
</div>

<!-- Recent tenants & invoices -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-h2">Empresas recientes</h2>
            <a href="<?= $url('/admin/tenants') ?>" class="text-[12px] font-semibold inline-flex items-center gap-1" style="color:var(--brand-700)">Ver todas <i class="lucide lucide-arrow-right text-[11px]"></i></a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Empresa</th><th>Plan</th><th>Usuarios</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recentTenants as $t): ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/admin/tenants/' . $t['id']) ?>'">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:30px;height:30px;border-radius:9px;background:<?= Helpers::colorFor($t['slug']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:11.5px"><?= Helpers::initials($t['name']) ?></div>
                                <div style="min-width:0">
                                    <div style="font-weight:600"><?= $e($t['name']) ?></div>
                                    <div class="text-[11px] font-mono" style="color:var(--ink-400)"><?= $e($t['slug']) ?></div>
                                </div>
                            </div>
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
                    <tr><td colspan="4">
                        <div class="empty-state" style="padding:24px 12px">
                            <div class="empty-illust"><i class="lucide lucide-building-2"></i></div>
                            <div class="empty-state-title">Sin empresas registradas</div>
                            <div class="empty-state-text">Las nuevas empresas aparecerán aquí.</div>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-h2">Facturas recientes</h2>
            <a href="<?= $url('/admin/invoices') ?>" class="text-[12px] font-semibold inline-flex items-center gap-1" style="color:var(--brand-700)">Ver todas <i class="lucide lucide-arrow-right text-[11px]"></i></a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Número</th><th>Empresa</th><th>Total</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recentInvoices as $i): ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/admin/invoices/' . $i['id']) ?>'">
                        <td class="font-mono text-[12px]"><?= $e($i['invoice_number']) ?></td>
                        <td><?= $e($i['tenant_name'] ?? '—') ?></td>
                        <td style="font-weight:600">$<?= number_format($i['total'], 2) ?></td>
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
                    <tr><td colspan="4">
                        <div class="empty-state" style="padding:24px 12px">
                            <div class="empty-illust"><i class="lucide lucide-file-text"></i></div>
                            <div class="empty-state-title">Sin facturas aún</div>
                            <div class="empty-state-text">Las facturas emitidas se mostrarán aquí.</div>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Plan distribution -->
<div class="admin-card admin-card-pad">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="admin-h2">Distribución por plan</h2>
            <div class="text-[11.5px] mt-0.5" style="color:var(--ink-400)">Empresas agrupadas por plan asignado</div>
        </div>
        <a href="<?= $url('/admin/plans') ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-tag text-[13px]"></i> Gestionar planes</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <?php foreach ($planDistribution as $p): ?>
            <div style="padding:16px; border:1px solid var(--border); border-radius:14px; background:linear-gradient(180deg,#fff,var(--bg))">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em]" style="color:var(--ink-400)"><?= $e($p['plan']) ?></div>
                <div style="font-family:'Plus Jakarta Sans'; font-weight:800; font-size:24px; margin-top:4px; letter-spacing:-.02em"><?= (int)$p['count'] ?></div>
                <div class="text-[11px]" style="color:var(--ink-400)">empresas</div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($planDistribution)): ?>
            <div class="text-[12px] col-span-full text-center py-4" style="color:var(--ink-400)">Sin datos disponibles.</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tenantData = <?= json_encode($tenantsByMonth) ?>;
    const revenueData = <?= json_encode($revenueByMonth) ?>;
    const grid = 'rgba(22,21,27,.05)';
    const tickColor = '#8e8e9a';

    if (document.getElementById('tenantsChart') && window.Chart) {
        const ctx = document.getElementById('tenantsChart').getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 240);
        grad.addColorStop(0, 'rgba(124,92,255,.32)');
        grad.addColorStop(1, 'rgba(124,92,255,0)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: tenantData.map(d => d.month),
                datasets: [{
                    label: 'Nuevas empresas',
                    data: tenantData.map(d => d.count),
                    borderColor: '#7c5cff',
                    backgroundColor: grad,
                    tension: .4,
                    fill: true,
                    pointBackgroundColor: '#7c5cff',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: tickColor }, grid: { color: grid, drawBorder: false } },
                    x: { ticks: { color: tickColor }, grid: { display: false } }
                },
                maintainAspectRatio: false
            }
        });
    }
    if (document.getElementById('revenueChart') && window.Chart) {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 240);
        grad.addColorStop(0, 'rgba(124,92,255,.85)');
        grad.addColorStop(1, 'rgba(167,139,250,.55)');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: revenueData.map(d => d.month),
                datasets: [{
                    label: 'Ingresos',
                    data: revenueData.map(d => d.total),
                    backgroundColor: grad,
                    borderRadius: 10,
                    borderSkipped: false,
                    maxBarThickness: 36,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: tickColor }, grid: { color: grid, drawBorder: false } },
                    x: { ticks: { color: tickColor }, grid: { display: false } }
                },
                maintainAspectRatio: false
            }
        });
    }
});
</script>
