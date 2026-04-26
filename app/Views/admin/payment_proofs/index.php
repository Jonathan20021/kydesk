<div class="grid sm:grid-cols-4 gap-4">
    <div class="admin-stat">
        <div class="admin-stat-label">Pendientes</div>
        <div class="admin-stat-value"><?= number_format($stats['pending']) ?></div>
        <div class="admin-stat-icon" style="background:#fef3c7; color:#b45309"><i class="lucide lucide-clock text-[15px]"></i></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Aprobados</div>
        <div class="admin-stat-value"><?= number_format($stats['approved']) ?></div>
        <div class="admin-stat-icon" style="background:#d1fae5; color:#047857"><i class="lucide lucide-check-circle text-[15px]"></i></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Rechazados</div>
        <div class="admin-stat-value"><?= number_format($stats['rejected']) ?></div>
        <div class="admin-stat-icon" style="background:#fee2e2; color:#b91c1c"><i class="lucide lucide-x-circle text-[15px]"></i></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Monto pendiente</div>
        <div class="admin-stat-value">$<?= number_format($stats['pending_amount'], 2) ?></div>
        <div class="admin-stat-icon"><i class="lucide lucide-banknote text-[15px]"></i></div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Comprobantes recibidos</h2>
        <a href="<?= $url('/admin/bank-settings') ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-landmark text-[13px]"></i> Datos bancarios</a>
    </div>
    <form method="GET" action="<?= $url('/admin/payment-proofs') ?>" class="admin-card-pad grid sm:grid-cols-3 gap-3 border-b" style="border-color:var(--border)">
        <div>
            <label class="admin-label">Estado</label>
            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="pending"   <?= $status==='pending'?'selected':'' ?>>Pendientes</option>
                <option value="approved"  <?= $status==='approved'?'selected':'' ?>>Aprobados</option>
                <option value="rejected"  <?= $status==='rejected'?'selected':'' ?>>Rechazados</option>
                <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelados</option>
            </select>
        </div>
        <div>
            <label class="admin-label">Tipo</label>
            <select name="type" class="admin-select" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="developer" <?= $type==='developer'?'selected':'' ?>>Developer</option>
                <option value="tenant"    <?= $type==='tenant'?'selected':'' ?>>Tenant (Helpdesk)</option>
            </select>
        </div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Fecha</th><th>Origen</th><th>Quién</th><th>Factura</th><th>Monto</th><th>Referencia</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center py-10 text-ink-400">Sin comprobantes.</td></tr>
                <?php else: foreach ($rows as $p): ?>
                    <tr>
                        <td class="text-[12px] font-mono"><?= $e(substr($p['created_at'], 0, 16)) ?></td>
                        <td>
                            <span class="admin-pill <?= $p['proof_type']==='developer'?'admin-pill-purple':'admin-pill-blue' ?>"><?= $p['proof_type'] ?></span>
                        </td>
                        <td>
                            <?php if ($p['proof_type'] === 'developer'): ?>
                                <div class="font-display font-bold text-[13px]"><?= $e($p['dev_name'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-400"><?= $e($p['dev_email'] ?? '—') ?></div>
                            <?php else: ?>
                                <div class="font-display font-bold text-[13px]"><?= $e($p['tenant_name'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-400"><?= $e($p['submitter_email'] ?? '—') ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-[12px]">
                            <?= $e($p['dev_invoice_number'] ?? $p['tenant_invoice_number'] ?? '—') ?>
                        </td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?> <?= $e($p['currency']) ?></td>
                        <td class="text-[12px] font-mono text-ink-400"><?= $e($p['reference'] ?? '—') ?></td>
                        <td>
                            <?php $cls = $p['status']==='approved'?'admin-pill-green':($p['status']==='rejected'?'admin-pill-red':'admin-pill-amber'); ?>
                            <span class="admin-pill <?= $cls ?>"><?= $e($p['status']) ?></span>
                        </td>
                        <td>
                            <a href="<?= $url('/admin/payment-proofs/' . $p['id']) ?>" class="admin-btn admin-btn-soft admin-btn-icon" title="Revisar"><i class="lucide lucide-arrow-right text-[13px]"></i></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
