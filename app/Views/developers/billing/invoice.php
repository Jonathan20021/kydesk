<div class="dev-card max-w-[820px] mx-auto p-7">
    <div class="flex items-center justify-between mb-5">
        <div>
            <span class="dev-pill dev-pill-sky"><i class="lucide lucide-file-text text-[11px]"></i> Factura</span>
            <h2 class="font-display font-bold text-white text-[22px] mt-2 font-mono"><?= $e($inv['invoice_number']) ?></h2>
            <p class="text-[12.5px] text-slate-400 mt-1">Emitida: <?= $e($inv['issue_date']) ?> · Vence: <?= $e($inv['due_date']) ?></p>
        </div>
        <div class="text-right">
            <span class="dev-pill <?= $inv['status']==='paid'?'dev-pill-emerald':($inv['status']==='overdue'?'dev-pill-red':'dev-pill-amber') ?>"><?= $e($inv['status']) ?></span>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="dev-card p-4" style="background:rgba(56,189,248,.04)">
            <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500 mb-1">Total</div>
            <div class="font-display font-bold text-white text-[28px]">$<?= number_format((float)$inv['total'], 2) ?></div>
            <div class="text-[12px] text-slate-400 mt-1"><?= $e($inv['currency']) ?></div>
        </div>
        <div class="dev-card p-4" style="background:rgba(56,189,248,.04)">
            <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500 mb-1">Pagado</div>
            <div class="font-display font-bold text-white text-[28px]">$<?= number_format((float)$inv['amount_paid'], 2) ?></div>
            <?php if ((float)$inv['amount_paid'] < (float)$inv['total']): ?>
                <div class="text-[12px] text-amber-300 mt-1">Pendiente: $<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dev-card p-4 mb-5">
        <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500 mb-2">Concepto</div>
        <p class="text-slate-200 text-[14px]"><?= $e($inv['description'] ?? '—') ?></p>
        <div class="grid sm:grid-cols-3 gap-3 mt-4 text-[12.5px]">
            <div><span class="text-slate-500">Subtotal</span><div class="text-slate-200">$<?= number_format((float)$inv['subtotal'], 2) ?></div></div>
            <div><span class="text-slate-500">Impuesto (<?= number_format((float)$inv['tax_rate'], 1) ?>%)</span><div class="text-slate-200">$<?= number_format((float)$inv['tax_amount'], 2) ?></div></div>
            <div><span class="text-slate-500">Descuento</span><div class="text-slate-200">$<?= number_format((float)$inv['discount'], 2) ?></div></div>
        </div>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="dev-card">
            <div class="dev-card-head"><h3 class="font-display font-bold text-white text-[14px]">Pagos</h3></div>
            <table class="dev-table">
                <thead><tr><th>Fecha</th><th>Método</th><th>Referencia</th><th>Monto</th></tr></thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="text-[12.5px]"><?= $e($p['paid_at'] ?? $p['created_at']) ?></td>
                            <td class="text-[12.5px]"><?= $e($p['method']) ?></td>
                            <td class="text-[12.5px] font-mono"><?= $e($p['reference'] ?? '—') ?></td>
                            <td class="text-white font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="flex items-center gap-2 mt-6">
        <a href="<?= $url('/developers/billing') ?>" class="dev-btn dev-btn-soft"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
    </div>
</div>
