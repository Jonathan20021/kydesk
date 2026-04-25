<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #22c55e"><div class="admin-stat-label">Total recibido</div><div class="admin-stat-value">$<?= number_format($stats['total'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #d946ef"><div class="admin-stat-label">Este mes</div><div class="admin-stat-value">$<?= number_format($stats['this_month'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #7c5cff"><div class="admin-stat-label">Transacciones</div><div class="admin-stat-value"><?= number_format($stats['count']) ?></div></div>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Empresa</th><th>Factura</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td class="text-[11.5px] text-ink-500"><?= $e($p['paid_at'] ?? $p['created_at']) ?></td>
                <td><a href="<?= $url('/admin/tenants/' . $p['tenant_id']) ?>" style="color:inherit"><?= $e($p['tenant_name']) ?></a></td>
                <td class="text-[12px] font-mono"><?= $e($p['invoice_number'] ?? '—') ?></td>
                <td>$<?= number_format($p['amount'], 2) ?></td>
                <td><span class="admin-pill admin-pill-gray"><?= $e($p['method']) ?></span></td>
                <td class="text-[11.5px] font-mono"><?= $e($p['reference'] ?? '—') ?></td>
                <td><span class="admin-pill admin-pill-<?= $p['status']==='completed'?'green':'amber' ?>"><?= $e(ucfirst($p['status'])) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?><tr><td colspan="7" style="text-align:center; padding:30px; color:#8e8e9a">Sin pagos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
