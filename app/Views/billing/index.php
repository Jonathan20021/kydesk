<?php $slug = $tenant->slug; ?>

<style>
    .b-stat { padding: 18px 22px; border-radius: 18px; background: white; border: 1px solid #ececef; transition: all .15s; position: relative; overflow: hidden; }
    .b-stat:hover { transform: translateY(-1px); box-shadow: 0 8px 22px -10px rgba(22,21,27,.10); }
    .b-stat-label { font-size: 10.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #8e8e9a; }
    .b-stat-value { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 28px; color: #16151b; line-height: 1.1; margin-top: 6px; letter-spacing: -.02em; }
    .b-stat-meta { font-size: 12px; color: #6b6b78; margin-top: 6px; }
    .b-stat-icon { position: absolute; top: 16px; right: 16px; width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; }

    .b-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
    .b-status-paid { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
    .b-status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .b-status-overdue { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .b-status-partial { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
</style>

<div class="page-head">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="text-[11px] font-bold uppercase tracking-[0.14em]" style="color:#5a3aff">Facturación</span>
        </div>
        <h1 class="display-md">Tu suscripción y facturas</h1>
        <p class="text-ink-500 mt-1 text-[13.5px]">Revisa tu plan, paga tus facturas y consulta el historial</p>
    </div>
    <a href="<?= $url('/t/' . $slug . '/billing/payment-info') ?>" class="btn" style="background:linear-gradient(135deg,#7c5cff,#6c47ff); color:white; box-shadow:0 6px 16px -4px rgba(124,92,255,.4)"><i class="lucide lucide-landmark text-[14px]"></i> Cómo pagar</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="b-stat">
        <div class="b-stat-icon" style="background:#f3f0ff; color:#5a3aff"><i class="lucide lucide-star text-[16px]"></i></div>
        <div class="b-stat-label">Plan actual</div>
        <div class="b-stat-value" style="font-size:22px"><?= $sub ? $e($sub['plan_name'] ?? '—') : 'Sin plan' ?></div>
        <?php if ($sub): ?>
            <div class="b-stat-meta"><?= $e(ucfirst($sub['status'])) ?> · <?= $e($sub['billing_cycle']) ?></div>
        <?php endif; ?>
    </div>
    <div class="b-stat" <?= $totalPending > 0 ? 'style="border-color:#fde68a"' : '' ?>>
        <div class="b-stat-icon" style="background:<?= $totalPending > 0 ? '#fef3c7' : '#ecfdf5' ?>; color:<?= $totalPending > 0 ? '#b45309' : '#047857' ?>"><i class="lucide lucide-receipt text-[16px]"></i></div>
        <div class="b-stat-label">Por pagar</div>
        <div class="b-stat-value" style="color:<?= $totalPending > 0 ? '#b45309' : '#16151b' ?>">$<?= number_format($totalPending, 2) ?></div>
        <div class="b-stat-meta"><?= count(array_filter($invoices, fn($i) => in_array($i['status'], ['pending','overdue','partial'], true))) ?> factura(s) pendientes</div>
    </div>
    <div class="b-stat">
        <div class="b-stat-icon" style="background:#dbeafe; color:#1d4ed8"><i class="lucide lucide-landmark text-[16px]"></i></div>
        <div class="b-stat-label">Cuenta para depósitos</div>
        <div class="b-stat-value" style="font-size:14px; line-height:1.4"><?= $e($bank['bank_name']) ?></div>
        <div class="b-stat-meta font-mono">Cta: <?= $e($bank['bank_account_number']) ?></div>
        <a href="<?= $url('/t/' . $slug . '/billing/payment-info') ?>" class="text-[12px] mt-2 inline-flex items-center gap-1 font-semibold" style="color:#5a3aff">Ver detalles <i class="lucide lucide-arrow-right text-[11px]"></i></a>
    </div>
</div>

<?php if ($totalPending > 0): ?>
<div class="card mb-6" style="border-color:#fde68a; background:linear-gradient(135deg,#fffbeb,#fef3c7)">
    <div class="card-pad flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#fef3c7; color:#b45309"><i class="lucide lucide-alert-circle"></i></div>
        <div class="flex-1">
            <div class="font-display font-bold text-[14.5px]" style="color:#92400e">¿Necesitas pagar una factura?</div>
            <div class="text-[12.5px]" style="color:#a16207">Realiza un depósito al banco indicado y sube el comprobante. Validamos en 24-48h.</div>
        </div>
        <a href="<?= $url('/t/' . $slug . '/billing/payment-info') ?>" class="btn btn-sm" style="background:#92400e; color:white">Cómo pagar <i class="lucide lucide-arrow-right text-[12px]"></i></a>
    </div>
</div>
<?php endif; ?>

<div class="card mb-6">
    <div class="card-pad" style="border-bottom:1px solid #ececef">
        <div class="section-head">
            <div class="section-head-icon" style="background:#f3f0ff; color:#5a3aff"><i class="lucide lucide-file-text text-[16px]"></i></div>
            <div class="flex-1">
                <h3 class="section-title">Facturas</h3>
                <div class="section-head-meta"><?= count($invoices) ?> factura<?= count($invoices)===1?'':'s' ?> en total</div>
            </div>
        </div>
    </div>
    <?php if (empty($invoices)): ?>
        <div class="card-pad text-center" style="padding:40px 20px">
            <div class="w-12 h-12 rounded-2xl mx-auto grid place-items-center mb-3" style="background:#f3f0ff; color:#5a3aff"><i class="lucide lucide-file-text text-[20px]"></i></div>
            <div class="font-display font-bold">Aún no hay facturas</div>
            <div class="text-[13px] text-ink-500 mt-1">Cuando suscribas un plan, aparecerán aquí</div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Número</th><th>Concepto</th><th>Total</th><th>Pagado</th><th>Estado</th><th>Vence</th></tr></thead>
                <tbody>
                    <?php foreach ($invoices as $i):
                        $cls = $i['status']==='paid'?'b-status-paid':($i['status']==='overdue'?'b-status-overdue':($i['status']==='partial'?'b-status-partial':'b-status-pending'));
                    ?>
                        <tr>
                            <td class="font-mono text-[12.5px] text-ink-700"><?= $e($i['invoice_number']) ?></td>
                            <td class="text-[13px]"><?= $e($i['description'] ?? '—') ?></td>
                            <td class="font-display font-bold">$<?= number_format((float)$i['total'], 2) ?> <span class="text-[11px] text-ink-400 font-normal"><?= $e($i['currency']) ?></span></td>
                            <td class="text-[12.5px]">$<?= number_format((float)$i['amount_paid'], 2) ?></td>
                            <td><span class="b-status <?= $cls ?>"><?= $e(ucfirst($i['status'])) ?></span></td>
                            <td class="text-[12.5px] text-ink-500"><?= $e($i['due_date'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($proofs)): ?>
<div class="card">
    <div class="card-pad" style="border-bottom:1px solid #ececef">
        <div class="section-head">
            <div class="section-head-icon" style="background:#fff7ed; color:#9a3412"><i class="lucide lucide-paperclip text-[16px]"></i></div>
            <div class="flex-1">
                <h3 class="section-title">Comprobantes enviados</h3>
                <div class="section-head-meta"><?= count($proofs) ?> comprobante<?= count($proofs)===1?'':'s' ?></div>
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Factura</th><th>Monto</th><th>Estado</th><th>Notas</th></tr></thead>
            <tbody>
                <?php foreach ($proofs as $p):
                    $cls = $p['status']==='approved'?'b-status-paid':($p['status']==='rejected'?'b-status-overdue':'b-status-pending');
                ?>
                    <tr>
                        <td class="text-[12.5px] font-mono"><?= $e(substr($p['created_at'], 0, 16)) ?></td>
                        <td class="text-[12px] font-mono"><?= $e($p['invoice_number'] ?? '—') ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?> <span class="text-[11px] text-ink-400 font-normal"><?= $e($p['currency']) ?></span></td>
                        <td><span class="b-status <?= $cls ?>"><?= $e(ucfirst($p['status'])) ?></span></td>
                        <td class="text-[11.5px] text-ink-500"><?= $e($p['review_notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
