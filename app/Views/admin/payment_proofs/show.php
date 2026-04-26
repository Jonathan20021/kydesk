<?php
$isPending = $proof['status'] === 'pending';
$isImage = strpos((string)$proof['file_mime'], 'image/') === 0;
$isPdf = $proof['file_mime'] === 'application/pdf';
$fileUrl = $proof['file_path'] ? $url('/admin/payment-proofs/' . $proof['id'] . '/file') : null;
?>
<div class="grid lg:grid-cols-3 gap-5">

    <!-- Datos del comprobante -->
    <div class="admin-card lg:col-span-2 admin-card-pad space-y-4">
        <div class="flex items-start justify-between">
            <div>
                <span class="admin-pill <?= $proof['proof_type']==='developer'?'admin-pill-purple':'admin-pill-blue' ?>"><?= $e($proof['proof_type']) ?></span>
                <h2 class="admin-h2 mt-2">Comprobante #<?= (int)$proof['id'] ?></h2>
                <p class="text-[12px] text-ink-400">Recibido <?= $e($proof['created_at']) ?></p>
            </div>
            <span class="admin-pill <?= $proof['status']==='approved'?'admin-pill-green':($proof['status']==='rejected'?'admin-pill-red':'admin-pill-amber') ?>"><?= $e($proof['status']) ?></span>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:12px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Monto reportado</div>
                <div class="font-display font-bold text-[24px]">$<?= number_format((float)$proof['amount'], 2) ?> <?= $e($proof['currency']) ?></div>
            </div>
            <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:12px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Fecha del depósito</div>
                <div class="font-display font-bold text-[16px]"><?= $e($proof['transfer_date'] ?? '—') ?></div>
            </div>
            <?php if ($proof['reference']): ?>
            <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:12px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Referencia / Tx ID</div>
                <code class="font-mono text-[14px]"><?= $e($proof['reference']) ?></code>
            </div>
            <?php endif; ?>
            <?php if ($proof['bank_used']): ?>
            <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:12px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Banco emisor</div>
                <div class="font-display font-bold text-[14px]"><?= $e($proof['bank_used']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($proof['notes']): ?>
            <div class="admin-card-pad sm:col-span-2" style="border:1px solid var(--border); border-radius:12px">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Notas del cliente</div>
                <div class="text-[13px]"><?= nl2br($e($proof['notes'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Vista del archivo -->
        <?php if ($fileUrl): ?>
        <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:12px">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400">Comprobante adjunto</div>
                <a href="<?= $fileUrl ?>" target="_blank" class="admin-btn admin-btn-soft text-[12px]"><i class="lucide lucide-download text-[12px]"></i> Descargar</a>
            </div>
            <?php if ($isImage): ?>
                <img src="<?= $fileUrl ?>" alt="Comprobante" style="max-width:100%; max-height:600px; border-radius:8px; border:1px solid var(--border)">
            <?php elseif ($isPdf): ?>
                <iframe src="<?= $fileUrl ?>" style="width:100%; height:600px; border:1px solid var(--border); border-radius:8px"></iframe>
            <?php else: ?>
                <div class="text-[13px] text-ink-400">Archivo: <?= $e($proof['file_mime']) ?> · <?= number_format((int)$proof['file_size'] / 1024, 1) ?> KB</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($proof['review_notes']): ?>
        <div class="admin-card-pad" style="border:1px solid var(--border); border-radius:12px; background:var(--bg)">
            <div class="text-[11px] uppercase font-bold tracking-wider text-ink-400 mb-1">Notas de revisión</div>
            <div class="text-[13px]"><?= nl2br($e($proof['review_notes'])) ?></div>
            <?php if ($proof['reviewed_at']): ?>
                <div class="text-[11px] text-ink-400 mt-2">Revisado: <?= $e($proof['reviewed_at']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar acciones -->
    <div class="space-y-4">
        <div class="admin-card admin-card-pad">
            <h3 class="admin-h2 mb-3">Quien envió</h3>
            <?php if ($proof['proof_type'] === 'developer'): ?>
                <div class="font-display font-bold"><?= $e($proof['dev_name']) ?></div>
                <div class="text-[12px] text-ink-400"><?= $e($proof['dev_email']) ?></div>
                <a href="<?= $url('/admin/developers/' . $proof['d_id']) ?>" class="admin-btn admin-btn-soft mt-2 w-full text-[12px]"><i class="lucide lucide-user text-[12px]"></i> Abrir developer</a>
            <?php else: ?>
                <div class="font-display font-bold"><?= $e($proof['tenant_name']) ?></div>
                <div class="text-[12px] text-ink-400"><?= $e($proof['submitter_email']) ?></div>
                <?php if ($proof['tenant_id']): ?>
                    <a href="<?= $url('/admin/tenants/' . $proof['tenant_id']) ?>" class="admin-btn admin-btn-soft mt-2 w-full text-[12px]"><i class="lucide lucide-building-2 text-[12px]"></i> Abrir empresa</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($proof['dev_invoice_number'] || $proof['tenant_invoice_number']): ?>
        <div class="admin-card admin-card-pad">
            <h3 class="admin-h2 mb-2">Factura asociada</h3>
            <code class="font-mono text-[13px]"><?= $e($proof['dev_invoice_number'] ?? $proof['tenant_invoice_number']) ?></code>
            <div class="text-[12px] text-ink-400 mt-1">Total: $<?= number_format((float)($proof['dev_invoice_total'] ?? $proof['tenant_invoice_total'] ?? 0), 2) ?></div>
            <?php if ($proof['dev_invoice_id']): ?>
                <a href="<?= $url('/admin/dev-invoices/' . $proof['dev_invoice_id']) ?>" class="admin-btn admin-btn-soft mt-2 w-full text-[12px]"><i class="lucide lucide-file-text text-[12px]"></i> Abrir factura</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($isPending): ?>
        <form method="POST" action="<?= $url('/admin/payment-proofs/' . $proof['id'] . '/approve') ?>" class="admin-card admin-card-pad space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <h3 class="admin-h2"><i class="lucide lucide-check-circle text-[15px]" style="color:#047857"></i> Aprobar pago</h3>
            <p class="text-[12px] text-ink-500">Aprobar registra el pago, marca la factura como pagada y envía email de confirmación al cliente.</p>
            <textarea name="review_notes" class="admin-textarea" rows="2" placeholder="Notas internas (opcional)"></textarea>
            <button type="submit" class="admin-btn admin-btn-primary w-full" style="background:#047857; box-shadow:none"><i class="lucide lucide-check text-[14px]"></i> Aprobar y registrar pago</button>
        </form>

        <form method="POST" action="<?= $url('/admin/payment-proofs/' . $proof['id'] . '/reject') ?>" class="admin-card admin-card-pad space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <h3 class="admin-h2"><i class="lucide lucide-x-circle text-[15px]" style="color:#b91c1c"></i> Rechazar</h3>
            <p class="text-[12px] text-ink-500">Indica el motivo. Se enviará email al cliente.</p>
            <textarea name="review_notes" class="admin-textarea" rows="3" required placeholder="Ej: el monto no coincide con el del depósito recibido"></textarea>
            <button type="submit" class="admin-btn admin-btn-danger w-full"><i class="lucide lucide-x text-[14px]"></i> Rechazar comprobante</button>
        </form>
        <?php endif; ?>

        <a href="<?= $url('/admin/payment-proofs') ?>" class="admin-btn admin-btn-soft w-full"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a la lista</a>
    </div>
</div>
