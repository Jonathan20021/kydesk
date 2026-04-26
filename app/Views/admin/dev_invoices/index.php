<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Facturas de developers</h2>
        <div class="flex gap-2">
            <form method="GET" action="<?= $url('/admin/dev-invoices') ?>">
                <select name="status" class="admin-select" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach (['pending','paid','partial','overdue','cancelled','refunded'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?= $url('/admin/dev-invoices/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus text-[13px]"></i> Nueva factura</a>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Número</th><th>Developer</th><th>Total</th><th>Pagado</th><th>Estado</th><th>Vence</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="7" class="text-center py-10 text-ink-400">Sin facturas.</td></tr>
                <?php else: foreach ($invoices as $i): ?>
                    <tr>
                        <td><a href="<?= $url('/admin/dev-invoices/' . $i['id']) ?>" class="font-mono text-[12.5px] hover:text-brand-700"><?= $e($i['invoice_number']) ?></a></td>
                        <td>
                            <div class="font-display font-bold"><?= $e($i['dev_name']) ?></div>
                            <div class="text-[11px] text-ink-400"><?= $e($i['dev_email']) ?></div>
                        </td>
                        <td class="font-display font-bold">$<?= number_format((float)$i['total'], 2) ?></td>
                        <td>$<?= number_format((float)$i['amount_paid'], 2) ?></td>
                        <td><span class="admin-pill <?= $i['status']==='paid'?'admin-pill-green':($i['status']==='overdue'?'admin-pill-red':'admin-pill-amber') ?>"><?= $e($i['status']) ?></span></td>
                        <td class="text-[12.5px]"><?= $e($i['due_date'] ?? '—') ?></td>
                        <td><a href="<?= $url('/admin/dev-invoices/' . $i['id']) ?>" class="admin-btn admin-btn-soft admin-btn-icon"><i class="lucide lucide-arrow-right text-[14px]"></i></a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
