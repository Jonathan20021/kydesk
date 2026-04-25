<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #22c55e"><div class="admin-stat-label">Activas</div><div class="admin-stat-value"><?= number_format($stats['active']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #f59e0b"><div class="admin-stat-label">En trial</div><div class="admin-stat-value"><?= number_format($stats['trial']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #ef4444"><div class="admin-stat-label">Canceladas</div><div class="admin-stat-value"><?= number_format($stats['cancelled']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #7c5cff"><div class="admin-stat-label">MRR</div><div class="admin-stat-value">$<?= number_format($stats['mrr'],0) ?></div></div>
</div>

<form method="GET" class="admin-card admin-card-pad mb-4">
    <div class="flex flex-wrap gap-2">
        <?php foreach (['' => 'Todas','active'=>'Activas','trial'=>'Trial','past_due'=>'Vencidas','suspended'=>'Suspendidas','cancelled'=>'Canceladas'] as $val => $lbl): ?>
            <a href="?status=<?= $val ?>" class="admin-btn <?= $status===$val?'admin-btn-primary':'admin-btn-soft' ?>"><?= $e($lbl) ?></a>
        <?php endforeach; ?>
    </div>
</form>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Empresa</th><th>Plan</th><th>Estado</th><th>Monto</th><th>Ciclo</th><th>Renovación</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($subs as $s): ?>
            <tr>
                <td>
                    <a href="<?= $url('/admin/tenants/' . $s['tenant_id']) ?>" style="color:inherit;text-decoration:none">
                        <div style="font-weight:600"><?= $e($s['tenant_name']) ?></div>
                        <div class="text-[11px] text-ink-400 font-mono"><?= $e($s['tenant_slug']) ?></div>
                    </a>
                </td>
                <td><span class="admin-pill admin-pill-purple"><?= $e($s['plan_name']) ?></span></td>
                <td>
                    <?php
                    $colors = ['active'=>'green','trial'=>'amber','past_due'=>'red','suspended'=>'red','cancelled'=>'gray','expired'=>'gray'];
                    $col = $colors[$s['status']] ?? 'gray';
                    ?>
                    <span class="admin-pill admin-pill-<?= $col ?>"><?= $e(ucfirst($s['status'])) ?></span>
                </td>
                <td>$<?= number_format($s['amount'], 2) ?></td>
                <td class="text-[11.5px] text-ink-500"><?= $e($s['billing_cycle']) ?></td>
                <td class="text-[11.5px] text-ink-500"><?= $e($s['current_period_end'] ?? '—') ?></td>
                <td>
                    <a href="<?= $url('/admin/tenants/' . $s['tenant_id']) ?>" class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-eye text-[13px]"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($subs)): ?><tr><td colspan="7" style="text-align:center; padding:30px; color:#8e8e9a">Sin suscripciones.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
