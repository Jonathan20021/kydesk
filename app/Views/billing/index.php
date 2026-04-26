<?php
$slug = $tenant->slug;
?>
<div class="page-head">
    <div>
        <h1 class="display-md">Facturación</h1>
        <p class="text-ink-500 mt-1">Tu suscripción, facturas y pagos</p>
    </div>
    <a href="<?= $url('/t/' . $slug . '/billing/payment-info') ?>" class="btn" style="background:linear-gradient(135deg,#0ea5e9,#6366f1); color:white"><i class="lucide lucide-landmark text-[14px]"></i> Cómo pagar</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card card-pad">
        <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Plan actual</div>
        <div class="font-display font-bold text-[20px]"><?= $sub ? $e($sub['plan_name'] ?? '—') : 'Sin plan' ?></div>
        <?php if ($sub): ?>
            <div class="text-[12px] text-ink-400 mt-1">Estado: <?= $e($sub['status']) ?> · <?= $e($sub['billing_cycle']) ?></div>
        <?php endif; ?>
    </div>
    <div class="card card-pad">
        <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Por pagar</div>
        <div class="font-display font-bold text-[24px]" style="color:<?= $totalPending > 0 ? '#b45309' : '#16151b' ?>">$<?= number_format($totalPending, 2) ?></div>
        <div class="text-[12px] text-ink-400 mt-1"><?= count(array_filter($invoices, fn($i) => in_array($i['status'], ['pending','overdue','partial'], true))) ?> factura(s) pendientes</div>
    </div>
    <div class="card card-pad">
        <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Datos bancarios</div>
        <div class="font-display font-bold text-[14px]"><?= $e($bank['bank_name']) ?></div>
        <div class="text-[12px] font-mono text-ink-700 mt-1">Cta: <?= $e($bank['bank_account_number']) ?></div>
        <a href="<?= $url('/t/' . $slug . '/billing/payment-info') ?>" class="text-[12px] mt-2 inline-block" style="color:#7c5cff">Ver detalles →</a>
    </div>
</div>

<!-- Facturas -->
<div class="card mb-6">
    <div class="card-head"><h2 class="card-title">Facturas</h2></div>
    <?php if (empty($invoices)): ?>
        <div class="card-pad text-center text-ink-400">No hay facturas todavía.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Número</th><th>Concepto</th><th>Total</th><th>Pagado</th><th>Estado</th><th>Vence</th></tr></thead>
                <tbody>
                    <?php foreach ($invoices as $i): ?>
                        <tr>
                            <td class="font-mono text-[12px]"><?= $e($i['invoice_number']) ?></td>
                            <td class="text-[13px]"><?= $e($i['description'] ?? '—') ?></td>
                            <td class="font-display font-bold">$<?= number_format((float)$i['total'], 2) ?> <?= $e($i['currency']) ?></td>
                            <td class="text-[12px]">$<?= number_format((float)$i['amount_paid'], 2) ?></td>
                            <td>
                                <?php $cls = $i['status']==='paid'?'status-paid':($i['status']==='overdue'?'status-overdue':'status-pending'); ?>
                                <span class="status-pill <?= $cls ?>"><?= $e($i['status']) ?></span>
                            </td>
                            <td class="text-[12px]"><?= $e($i['due_date'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Comprobantes enviados -->
<?php if (!empty($proofs)): ?>
<div class="card">
    <div class="card-head"><h2 class="card-title">Comprobantes enviados</h2></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Factura</th><th>Monto</th><th>Estado</th><th>Notas</th></tr></thead>
            <tbody>
                <?php foreach ($proofs as $p): ?>
                    <tr>
                        <td class="text-[12px] font-mono"><?= $e(substr($p['created_at'], 0, 16)) ?></td>
                        <td class="text-[12px] font-mono"><?= $e($p['invoice_number'] ?? '—') ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?> <?= $e($p['currency']) ?></td>
                        <td>
                            <?php $cls = $p['status']==='approved'?'status-paid':($p['status']==='rejected'?'status-overdue':'status-pending'); ?>
                            <span class="status-pill <?= $cls ?>"><?= $e($p['status']) ?></span>
                        </td>
                        <td class="text-[11.5px] text-ink-400"><?= $e($p['review_notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
