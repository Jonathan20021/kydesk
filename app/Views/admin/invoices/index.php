<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #22c55e"><div class="admin-stat-label">Cobrado</div><div class="admin-stat-value">$<?= number_format($stats['paid'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #f59e0b"><div class="admin-stat-label">Por cobrar</div><div class="admin-stat-value">$<?= number_format($stats['pending'],0) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #ef4444"><div class="admin-stat-label">Vencidas</div><div class="admin-stat-value"><?= number_format($stats['overdue']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #d946ef"><div class="admin-stat-label">Total</div><div class="admin-stat-value"><?= number_format($stats['total']) ?></div></div>
</div>

<div class="admin-card admin-card-pad mb-4">
    <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
        <div class="flex-1"><label class="admin-label">Buscar</label><input name="q" value="<?= $e($q) ?>" placeholder="Número o empresa…" class="admin-input"></div>
        <div>
            <label class="admin-label">Estado</label>
            <select name="status" class="admin-select">
                <option value="">Todos</option>
                <?php foreach (['draft','pending','paid','partial','overdue','cancelled','refunded'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button class="admin-btn admin-btn-soft"><i class="lucide lucide-search"></i></button>
            <a href="<?= $url('/admin/invoices/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus"></i> Nueva factura</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Número</th><th>Empresa</th><th>Total</th><th>Pagado</th><th>Estado</th><th>Vencimiento</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $i): ?>
            <tr style="cursor:pointer" onclick="location='<?= $url('/admin/invoices/' . $i['id']) ?>'">
                <td class="font-mono text-[12px]"><?= $e($i['invoice_number']) ?></td>
                <td><?= $e($i['tenant_name']) ?></td>
                <td>$<?= number_format($i['total'], 2) ?></td>
                <td>$<?= number_format($i['amount_paid'], 2) ?></td>
                <td>
                    <?php
                    $colors = ['paid'=>'green','pending'=>'amber','overdue'=>'red','partial'=>'blue','draft'=>'gray','cancelled'=>'gray','refunded'=>'gray'];
                    $col = $colors[$i['status']] ?? 'gray';
                    ?>
                    <span class="admin-pill admin-pill-<?= $col ?>"><?= $e(ucfirst($i['status'])) ?></span>
                </td>
                <td class="text-[11.5px] text-ink-500"><?= $e($i['due_date'] ?? '—') ?></td>
                <td><a href="<?= $url('/admin/invoices/' . $i['id']) ?>" class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-eye text-[13px]"></i></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($invoices)): ?><tr><td colspan="7" style="text-align:center; padding:30px; color:#8e8e9a">Sin facturas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
