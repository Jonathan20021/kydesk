<?php $slug = $tenant->slug; ?>

<div class="page-head">
    <div>
        <h1 class="display-md">Cómo pagar tu suscripción</h1>
        <p class="text-ink-500 mt-1">Depósito bancario · validación en 24-48h</p>
    </div>
    <a href="<?= $url('/t/' . $slug . '/billing') ?>" class="btn btn-outline btn-sm"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    <!-- Datos bancarios -->
    <div class="card lg:col-span-2">
        <div class="card-pad">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><i class="lucide lucide-landmark"></i></div>
                <div>
                    <h2 class="font-display font-bold text-[18px]">Datos bancarios</h2>
                    <p class="text-[12.5px] text-ink-500">Realiza tu depósito a esta cuenta</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div class="p-4 rounded-2xl border border-[#ececef]">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Banco</div>
                    <div class="font-display font-bold text-[15px] text-ink-900"><?= $e($bank['bank_name']) ?></div>
                </div>
                <div class="p-4 rounded-2xl border border-[#ececef]">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Tipo de cuenta</div>
                    <div class="font-display font-bold text-[15px] text-ink-900"><?= $e($bank['bank_account_type']) ?></div>
                </div>
                <div class="p-4 rounded-2xl border border-[#ececef]">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Número de cuenta</div>
                    <div class="flex items-center gap-2 mt-1">
                        <code class="font-mono text-[16px] font-bold text-ink-900"><?= $e($bank['bank_account_number']) ?></code>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= $e($bank['bank_account_number']) ?>'); this.innerText='✓'" class="btn btn-ghost btn-sm" style="padding:0 8px; height:28px">Copiar</button>
                    </div>
                </div>
                <div class="p-4 rounded-2xl border border-[#ececef]">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Cédula del titular</div>
                    <div class="flex items-center gap-2 mt-1">
                        <code class="font-mono text-[15px] font-bold text-ink-900"><?= $e($bank['bank_id_number']) ?></code>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= $e($bank['bank_id_number']) ?>'); this.innerText='✓'" class="btn btn-ghost btn-sm" style="padding:0 8px; height:28px">Copiar</button>
                    </div>
                </div>
                <div class="p-4 rounded-2xl border border-[#ececef] sm:col-span-2">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Titular</div>
                    <div class="font-display font-bold text-[15px] text-ink-900"><?= $e($bank['bank_account_holder']) ?></div>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-2xl flex items-start gap-2" style="background:#eff6ff; border:1px solid #dbeafe">
                <i class="lucide lucide-mail text-blue-600 mt-0.5"></i>
                <div class="text-[12.5px] text-ink-700 leading-[1.5]">
                    Después del depósito, sube el comprobante aquí mismo o envíalo a <a href="mailto:<?= $e($bank['billing_approval_email']) ?>" style="color:#7c5cff" class="font-semibold"><?= $e($bank['billing_approval_email']) ?></a> para validación.
                </div>
            </div>
        </div>
    </div>

    <!-- Steps -->
    <div class="card">
        <div class="card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3">Cómo funciona</h3>
            <?php foreach ([
                ['1','Deposita','Realiza el depósito a la cuenta indicada'],
                ['2','Sube el comprobante','Adjunta la imagen/PDF del recibo'],
                ['3','Validamos','Verificamos el pago en 24-48h hábiles'],
                ['4','Activamos','Tu suscripción queda activa'],
            ] as [$n, $t, $d]): ?>
                <div class="flex items-start gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg grid place-items-center text-white text-[12px] font-bold flex-shrink-0" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><?= $n ?></div>
                    <div>
                        <div class="font-display font-bold text-[13px]"><?= $t ?></div>
                        <div class="text-[11.5px] text-ink-500"><?= $d ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (!empty($pendingInvoices)): ?>
<div class="card mb-6" style="border-color:#fde68a; background:#fffbeb">
    <div class="card-pad">
        <div class="flex items-center gap-3">
            <i class="lucide lucide-receipt" style="color:#b45309"></i>
            <div class="flex-1">
                <div class="font-display font-bold"><?= count($pendingInvoices) ?> factura(s) pendiente(s)</div>
                <div class="text-[12.5px] text-ink-500">Total a pagar: <strong style="color:#b45309">$<?= number_format(array_sum(array_map(fn($i) => (float)$i['total'] - (float)$i['amount_paid'], $pendingInvoices)), 2) ?></strong></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Upload form -->
<form method="POST" action="<?= $url('/t/' . $slug . '/billing/payment-proof') ?>" enctype="multipart/form-data" class="card mb-6">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="card-head">
        <h2 class="card-title">Subir comprobante de pago</h2>
        <span class="text-[12px] text-ink-400">JPG, PNG, WebP o PDF · Máx <?= (int)$bank['payment_max_file_mb'] ?>MB</span>
    </div>
    <div class="card-pad grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="form-label">Factura asociada (opcional)</label>
            <select name="invoice_id" class="form-input">
                <option value="0">— Sin asociar a factura —</option>
                <?php foreach ($pendingInvoices as $inv): ?>
                    <option value="<?= $inv['id'] ?>"><?= $e($inv['invoice_number']) ?> · $<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2) ?> <?= $e($inv['currency']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Monto depositado</label>
            <input type="number" step="0.01" name="amount" required class="form-input" placeholder="0.00">
        </div>
        <div>
            <label class="form-label">Moneda</label>
            <select name="currency" class="form-input">
                <option value="DOP">DOP (RD$)</option>
                <option value="USD">USD ($)</option>
            </select>
        </div>
        <div>
            <label class="form-label">Fecha del depósito</label>
            <input type="date" name="transfer_date" class="form-input" value="<?= date('Y-m-d') ?>">
        </div>
        <div>
            <label class="form-label">Banco emisor (opcional)</label>
            <input type="text" name="bank_used" class="form-input" placeholder="Ej: Banreservas">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Referencia / # de transacción</label>
            <input type="text" name="reference" class="form-input font-mono" placeholder="Ej: TX1234567890">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Notas (opcional)</label>
            <textarea name="notes" class="form-input" rows="2" placeholder="Algún detalle adicional"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Comprobante <span style="color:#b45309">requerido</span></label>
            <input type="file" name="proof_file" required accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="form-input">
            <div class="text-[11px] text-ink-400 mt-1">Se enviará automáticamente a <?= $e($bank['billing_approval_email']) ?> para validación.</div>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn" style="background:linear-gradient(135deg,#0ea5e9,#6366f1); color:white"><i class="lucide lucide-upload text-[14px]"></i> Enviar comprobante</button>
        </div>
    </div>
</form>

<?php if (!empty($proofs)): ?>
<div class="card">
    <div class="card-head"><h3 class="card-title">Mis comprobantes enviados</h3></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Factura</th><th>Monto</th><th>Referencia</th><th>Estado</th><th>Notas</th></tr></thead>
            <tbody>
                <?php foreach ($proofs as $p): ?>
                    <tr>
                        <td class="text-[12px] font-mono"><?= $e(substr($p['created_at'], 0, 16)) ?></td>
                        <td class="text-[12px] font-mono"><?= $e($p['invoice_number'] ?? '—') ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?> <?= $e($p['currency']) ?></td>
                        <td class="text-[12px] font-mono text-ink-400"><?= $e($p['reference'] ?? '—') ?></td>
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
