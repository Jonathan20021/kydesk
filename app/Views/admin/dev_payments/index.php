<div class="grid sm:grid-cols-2 gap-4">
    <div class="admin-stat">
        <div class="admin-stat-label">Total recibido</div>
        <div class="admin-stat-value">$<?= number_format($totalCompleted, 2) ?></div>
        <div class="admin-stat-icon" style="background:#d1fae5; color:#047857"><i class="lucide lucide-check-circle text-[15px]"></i></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Recibido este mes</div>
        <div class="admin-stat-value">$<?= number_format($monthCompleted, 2) ?></div>
        <div class="admin-stat-icon"><i class="lucide lucide-trending-up text-[15px]"></i></div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Pagos de developers</h2>
        <button type="button" onclick="document.getElementById('newPay').classList.toggle('hidden')" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus text-[13px]"></i> Registrar pago</button>
    </div>

    <form id="newPay" method="POST" action="<?= $url('/admin/dev-payments') ?>" class="hidden admin-card-pad grid sm:grid-cols-3 gap-3 border-b" style="border-color:var(--border)">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div><label class="admin-label">ID Developer</label><input type="number" name="developer_id" class="admin-input" required></div>
        <div><label class="admin-label">ID Factura (opcional)</label><input type="number" name="invoice_id" class="admin-input"></div>
        <div><label class="admin-label">Monto</label><input type="number" step="0.01" name="amount" class="admin-input" required></div>
        <div><label class="admin-label">Método</label>
            <select name="method" class="admin-select">
                <option value="manual">Manual</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option><option value="paypal">PayPal</option><option value="stripe">Stripe</option>
            </select>
        </div>
        <div><label class="admin-label">Moneda</label><input type="text" name="currency" class="admin-input" value="USD"></div>
        <div><label class="admin-label">Referencia</label><input type="text" name="reference" class="admin-input"></div>
        <div class="sm:col-span-3"><button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Registrar</button></div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Fecha</th><th>Developer</th><th>Factura</th><th>Método</th><th>Monto</th><th>Estado</th></tr></thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" class="text-center py-10 text-ink-400">Sin pagos.</td></tr>
                <?php else: foreach ($payments as $p): ?>
                    <tr>
                        <td class="text-[12.5px]"><?= $e($p['paid_at'] ?? $p['created_at']) ?></td>
                        <td>
                            <a href="<?= $url('/admin/developers/' . $p['developer_id']) ?>" class="font-display font-bold hover:text-brand-700"><?= $e($p['dev_name']) ?></a>
                            <div class="text-[11px] text-ink-400"><?= $e($p['dev_email']) ?></div>
                        </td>
                        <td>
                            <?php if ($p['invoice_id']): ?>
                                <a href="<?= $url('/admin/dev-invoices/' . $p['invoice_id']) ?>" class="font-mono text-[11.5px]"><?= $e($p['invoice_number']) ?></a>
                            <?php else: ?>
                                <span class="text-ink-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-[12.5px]"><?= $e($p['method']) ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?></td>
                        <td><span class="admin-pill <?= $p['status']==='completed'?'admin-pill-green':'admin-pill-amber' ?>"><?= $e($p['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
