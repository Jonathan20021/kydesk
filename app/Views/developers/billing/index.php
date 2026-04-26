<?php if (!empty($invoices) && in_array(($invoices[0]['status'] ?? ''), ['pending','overdue','partial'], true)): ?>
<div class="dev-card dev-card-pad" style="border-color:rgba(245,158,11,.30); background:rgba(245,158,11,.04)">
    <div class="flex items-center gap-3">
        <i class="lucide lucide-landmark text-amber-300"></i>
        <div class="flex-1">
            <div class="font-display font-bold text-white text-[14px]">¿Necesitas pagar una factura?</div>
            <p class="text-[12.5px] text-slate-300 mt-0.5">Realiza un depósito bancario y sube el comprobante. Verificamos en 24-48h.</p>
        </div>
        <a href="<?= $url('/developers/billing/payment-info') ?>" class="dev-btn dev-btn-primary"><i class="lucide lucide-landmark text-[13px]"></i> Cómo pagar</a>
    </div>
</div>
<?php endif; ?>

<div class="grid sm:grid-cols-3 gap-4">
    <div class="dev-stat">
        <div class="dev-stat-label">Plan actual</div>
        <div class="dev-stat-value text-[20px]"><?= $sub ? $e($sub['plan_name']) : 'Sin plan' ?></div>
        <?php if ($sub): ?>
            <div class="text-[11.5px] text-slate-400 mt-1">Renovación: <?= $e($sub['current_period_end'] ?? '—') ?></div>
        <?php endif; ?>
        <div class="dev-stat-icon"><i class="lucide lucide-star text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Total pagado</div>
        <div class="dev-stat-value">$<?= number_format($totalPaid, 2) ?></div>
        <div class="dev-stat-icon" style="background:rgba(16,185,129,.10); color:#86efac"><i class="lucide lucide-check-circle text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Pendiente</div>
        <div class="dev-stat-value">$<?= number_format($totalPending, 2) ?></div>
        <div class="dev-stat-icon" style="background:rgba(245,158,11,.10); color:#fcd34d"><i class="lucide lucide-clock text-[15px]"></i></div>
    </div>
</div>

<?php if ($sub): ?>
<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Suscripción activa</h2>
            <p class="text-[12px] text-slate-400">Plan: <?= $e($sub['plan_name']) ?> · <?= $e($sub['billing_cycle']) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="<?= $url('/developers/billing/plans') ?>" class="dev-btn dev-btn-soft"><i class="lucide lucide-repeat text-[13px]"></i> Cambiar plan</a>
            <form method="POST" action="<?= $url('/developers/billing/cancel') ?>" onsubmit="return confirm('¿Cancelar suscripción?');">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button type="submit" class="dev-btn dev-btn-danger"><i class="lucide lucide-x text-[13px]"></i> Cancelar</button>
            </form>
        </div>
    </div>
    <div class="grid sm:grid-cols-3 gap-4 p-5">
        <div>
            <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500">Estado</div>
            <div class="mt-1"><span class="dev-pill <?= $sub['status']==='active'?'dev-pill-emerald':($sub['status']==='trial'?'dev-pill-amber':'dev-pill-gray') ?>"><?= $e($sub['status']) ?></span></div>
        </div>
        <div>
            <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500">Monto</div>
            <div class="mt-1 text-white font-display font-bold text-[18px]">$<?= number_format((float)$sub['amount'], 2) ?> <span class="text-[11.5px] text-slate-400 font-normal">/<?= $sub['billing_cycle']==='yearly'?'año':'mes' ?></span></div>
        </div>
        <div>
            <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500">Próxima renovación</div>
            <div class="mt-1 text-white"><?= $e($sub['current_period_end'] ?? '—') ?></div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="dev-card dev-card-pad text-center">
    <div class="w-14 h-14 rounded-2xl mx-auto grid place-items-center bg-amber-500/10 text-amber-300 mb-3"><i class="lucide lucide-star text-[22px]"></i></div>
    <p class="font-display font-bold text-white text-[16px] mb-1">No tienes plan activo</p>
    <p class="text-[13px] text-slate-400 mb-4">Suscríbete a un plan para empezar a usar la API.</p>
    <a href="<?= $url('/developers/billing/plans') ?>" class="dev-btn dev-btn-primary inline-flex"><i class="lucide lucide-tag text-[13px]"></i> Ver planes</a>
</div>
<?php endif; ?>

<div class="dev-card">
    <div class="dev-card-head">
        <h2 class="font-display font-bold text-white text-[16px]">Facturas</h2>
    </div>
    <?php if (empty($invoices)): ?>
        <div class="p-6 text-center text-[13px] text-slate-400">No hay facturas todavía.</div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="dev-table">
                <thead><tr><th>Número</th><th>Descripción</th><th>Total</th><th>Estado</th><th>Vence</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($invoices as $i): ?>
                        <tr>
                            <td><a href="<?= $url('/developers/billing/invoices/' . $i['id']) ?>" class="dev-link font-mono"><?= $e($i['invoice_number']) ?></a></td>
                            <td class="text-[12.5px]"><?= $e($i['description'] ?? '—') ?></td>
                            <td class="text-white font-display font-bold">$<?= number_format((float)$i['total'], 2) ?></td>
                            <td><span class="dev-pill <?= $i['status']==='paid'?'dev-pill-emerald':($i['status']==='overdue'?'dev-pill-red':'dev-pill-amber') ?>"><?= $e($i['status']) ?></span></td>
                            <td><?= $e($i['due_date'] ?? '—') ?></td>
                            <td><a href="<?= $url('/developers/billing/invoices/' . $i['id']) ?>" class="dev-btn dev-btn-soft dev-btn-icon"><i class="lucide lucide-arrow-right text-[14px]"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="dev-card">
    <div class="dev-card-head">
        <h2 class="font-display font-bold text-white text-[16px]">Historial de pagos</h2>
    </div>
    <?php if (empty($payments)): ?>
        <div class="p-6 text-center text-[13px] text-slate-400">Sin pagos.</div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="dev-table">
                <thead><tr><th>Fecha</th><th>Método</th><th>Referencia</th><th>Monto</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="text-[12.5px]"><?= $e($p['paid_at'] ?? $p['created_at']) ?></td>
                            <td class="text-[12.5px]"><?= $e($p['method']) ?></td>
                            <td class="text-[12.5px] font-mono"><?= $e($p['reference'] ?? '—') ?></td>
                            <td class="text-white font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?></td>
                            <td><span class="dev-pill <?= $p['status']==='completed'?'dev-pill-emerald':'dev-pill-amber' ?>"><?= $e($p['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
