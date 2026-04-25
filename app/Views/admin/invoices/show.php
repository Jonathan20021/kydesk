<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 admin-card admin-card-pad">
        <div class="flex items-start justify-between mb-6">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-admin-700">FACTURA</div>
                <div style="font-family:'Plus Jakarta Sans';font-weight:800;font-size:24px"><?= $e($inv['invoice_number']) ?></div>
                <div class="text-[12.5px] text-ink-500 mt-1">Emitida: <?= $e($inv['issue_date']) ?> · Vence: <?= $e($inv['due_date']) ?></div>
            </div>
            <?php
            $colors = ['paid'=>'green','pending'=>'amber','overdue'=>'red','partial'=>'blue','draft'=>'gray','cancelled'=>'gray','refunded'=>'gray'];
            $col = $colors[$inv['status']] ?? 'gray';
            ?>
            <span class="admin-pill admin-pill-<?= $col ?>" style="padding:6px 14px;font-size:13px"><?= $e(ucfirst($inv['status'])) ?></span>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-1">FACTURAR A</div>
                <div style="font-weight:700"><?= $e($inv['tenant_name']) ?></div>
                <div class="text-[12.5px] text-ink-500"><?= $e($inv['billing_email'] ?? $inv['support_email'] ?? '') ?></div>
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-1">DESCRIPCIÓN</div>
                <div class="text-[13px]"><?= $e($inv['description'] ?? '—') ?></div>
            </div>
        </div>

        <table style="width:100%; margin-bottom:20px">
            <thead><tr style="border-bottom:2px solid #ececef"><th style="padding:10px 0; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.12em; color:#6b6b78">Concepto</th><th style="text-align:right; padding:10px 0; font-size:11px; text-transform:uppercase; letter-spacing:.12em; color:#6b6b78">Monto</th></tr></thead>
            <tbody>
                <tr><td style="padding:14px 0"><?= $e($inv['description'] ?: 'Servicios SaaS') ?></td><td style="text-align:right; font-weight:600">$<?= number_format($inv['subtotal'], 2) ?></td></tr>
                <?php if ($inv['discount'] > 0): ?><tr><td style="padding:6px 0; color:#16a34a">Descuento</td><td style="text-align:right; color:#16a34a">-$<?= number_format($inv['discount'], 2) ?></td></tr><?php endif; ?>
                <?php if ($inv['tax_amount'] > 0): ?><tr><td style="padding:6px 0; color:#6b6b78">Impuesto (<?= $inv['tax_rate'] ?>%)</td><td style="text-align:right">$<?= number_format($inv['tax_amount'], 2) ?></td></tr><?php endif; ?>
                <tr style="border-top:2px solid #ececef"><td style="padding:14px 0; font-weight:700; font-size:16px">Total</td><td style="text-align:right; font-weight:800; font-size:18px">$<?= number_format($inv['total'], 2) ?> <?= $e($inv['currency']) ?></td></tr>
                <tr><td style="padding:6px 0; color:#16a34a">Pagado</td><td style="text-align:right; color:#16a34a; font-weight:600">$<?= number_format($inv['amount_paid'], 2) ?></td></tr>
                <tr><td style="padding:6px 0; font-weight:600">Pendiente</td><td style="text-align:right; font-weight:700">$<?= number_format($inv['total'] - $inv['amount_paid'], 2) ?></td></tr>
            </tbody>
        </table>

        <?php if (!empty($inv['notes'])): ?>
            <div class="mt-4 p-3 rounded-lg" style="background:#fafafb"><div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-1">Notas</div><div class="text-[12.5px]"><?= nl2br($e($inv['notes'])) ?></div></div>
        <?php endif; ?>
    </div>

    <div>
        <div class="admin-card admin-card-pad mb-4">
            <h2 class="admin-h2 mb-3">Acciones</h2>
            <?php if ($inv['status'] !== 'paid'): ?>
                <form method="POST" action="<?= $url('/admin/invoices/' . $inv['id'] . '/pay') ?>" onsubmit="return confirm('¿Marcar factura como pagada?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div class="space-y-2 mb-3">
                        <select name="method" class="admin-select">
                            <option value="manual">Manual</option>
                            <option value="transfer">Transferencia</option>
                            <option value="card">Tarjeta</option>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                            <option value="other">Otro</option>
                        </select>
                        <input name="reference" placeholder="Referencia / # transacción" class="admin-input">
                    </div>
                    <button class="admin-btn admin-btn-primary" style="width:100%"><i class="lucide lucide-check-circle"></i> Marcar como pagada</button>
                </form>
            <?php else: ?>
                <div class="text-center p-3 rounded-lg" style="background:#dcfce7; color:#166534">
                    <i class="lucide lucide-check-circle text-[20px] mb-1 block"></i>
                    <div class="font-semibold text-[13px]">Factura pagada</div>
                    <div class="text-[11.5px] mt-1"><?= $e($inv['paid_at']) ?></div>
                </div>
            <?php endif; ?>
            <hr style="margin:14px 0; border:none; border-top:1px solid #ececef">
            <a href="<?= $url('/admin/tenants/' . $inv['tenant_id']) ?>" class="admin-btn admin-btn-soft" style="width:100%; margin-bottom:6px"><i class="lucide lucide-building-2"></i> Ver empresa</a>
            <form method="POST" action="<?= $url('/admin/invoices/' . $inv['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar factura?')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="admin-btn admin-btn-danger" style="width:100%"><i class="lucide lucide-trash-2"></i> Eliminar factura</button>
            </form>
        </div>

        <div class="admin-card admin-card-pad">
            <h2 class="admin-h2 mb-3">Pagos</h2>
            <?php foreach ($payments as $p): ?>
                <div class="border-b border-[#f3f3f5] py-2.5">
                    <div class="flex justify-between items-center">
                        <span style="font-weight:600">$<?= number_format($p['amount'], 2) ?></span>
                        <span class="admin-pill admin-pill-gray text-[10px]"><?= $e($p['method']) ?></span>
                    </div>
                    <div class="text-[11px] text-ink-400 mt-0.5"><?= $e($p['paid_at']) ?> · <?= $e($p['reference'] ?? '—') ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?><div class="text-[12.5px] text-ink-400 text-center py-4">Sin pagos.</div><?php endif; ?>
        </div>
    </div>
</div>
