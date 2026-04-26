<div class="grid lg:grid-cols-3 gap-5">
    <div class="admin-card lg:col-span-2 admin-card-pad space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <span class="admin-pill admin-pill-purple">Factura</span>
                <div class="font-mono text-[18px] font-bold mt-2"><?= $e($inv['invoice_number']) ?></div>
                <div class="text-[12px] text-ink-400">Emitida: <?= $e($inv['issue_date']) ?> · Vence: <?= $e($inv['due_date']) ?></div>
            </div>
            <span class="admin-pill <?= $inv['status']==='paid'?'admin-pill-green':($inv['status']==='overdue'?'admin-pill-red':'admin-pill-amber') ?>"><?= $e($inv['status']) ?></span>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:14px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Total</div>
                <div class="font-display font-bold text-[28px]">$<?= number_format((float)$inv['total'], 2) ?></div>
                <div class="text-[12px] text-ink-400 mt-1"><?= $e($inv['currency']) ?></div>
            </div>
            <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:14px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Pagado</div>
                <div class="font-display font-bold text-[28px]">$<?= number_format((float)$inv['amount_paid'], 2) ?></div>
                <?php if ((float)$inv['amount_paid'] < (float)$inv['total']): ?>
                    <div class="text-[12px] mt-1" style="color:#b45309">Pendiente: $<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-2">Concepto</div>
            <p class="text-[14px]"><?= $e($inv['description'] ?? '—') ?></p>
        </div>

        <?php if (!empty($payments)): ?>
        <div>
            <h3 class="font-display font-bold text-[14px] mb-2">Pagos registrados</h3>
            <table class="admin-table">
                <thead><tr><th>Fecha</th><th>Método</th><th>Referencia</th><th>Monto</th></tr></thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="text-[12.5px]"><?= $e($p['paid_at'] ?? $p['created_at']) ?></td>
                            <td class="text-[12.5px]"><?= $e($p['method']) ?></td>
                            <td class="text-[12.5px] font-mono"><?= $e($p['reference'] ?? '—') ?></td>
                            <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="admin-card admin-card-pad space-y-4">
        <h3 class="admin-h2">Developer</h3>
        <div>
            <a href="<?= $url('/admin/developers/' . $dev['id']) ?>" class="font-display font-bold hover:text-brand-700"><?= $e($dev['name']) ?></a>
            <div class="text-[12px] text-ink-400"><?= $e($dev['email']) ?></div>
            <?php if ($dev['company']): ?><div class="text-[12px] text-ink-400"><?= $e($dev['company']) ?></div><?php endif; ?>
        </div>

        <?php if ($inv['status'] !== 'paid'): ?>
            <div class="border-t pt-4" style="border-color:var(--border)">
                <h3 class="font-display font-bold text-[14px] mb-2">Registrar pago</h3>
                <form method="POST" action="<?= $url('/admin/dev-invoices/' . $inv['id'] . '/pay') ?>" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div>
                        <label class="admin-label">Monto</label>
                        <input type="number" step="0.01" name="amount" class="admin-input" value="<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2, '.', '') ?>" required>
                    </div>
                    <div>
                        <label class="admin-label">Método</label>
                        <select name="method" class="admin-select">
                            <option value="manual">Manual</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                            <option value="crypto">Crypto</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Referencia</label>
                        <input type="text" name="reference" class="admin-input" placeholder="Tx ID, ref bancaria...">
                    </div>
                    <button type="submit" class="admin-btn admin-btn-primary w-full"><i class="lucide lucide-check text-[13px]"></i> Marcar como pagada</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="border-t pt-3" style="border-color:var(--border)">
            <form method="POST" action="<?= $url('/admin/dev-invoices/' . $inv['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar factura?')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button type="submit" class="admin-btn admin-btn-danger w-full"><i class="lucide lucide-trash-2 text-[13px]"></i> Eliminar factura</button>
            </form>
        </div>
    </div>
</div>
