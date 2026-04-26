<div class="grid lg:grid-cols-3 gap-5">

    <!-- Bank info card -->
    <div class="dev-card lg:col-span-2 overflow-hidden relative">
        <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(600px 250px at 70% 0%, rgba(14,165,233,.15), transparent 70%)"></div>
        <div class="relative p-7">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><i class="lucide lucide-landmark"></i></div>
                <div>
                    <span class="dev-pill mb-1 inline-block"><i class="lucide lucide-info text-[10px]"></i> Pago manual con depósito</span>
                    <h2 class="font-display font-bold text-white text-[20px]">Datos bancarios para depósito</h2>
                </div>
            </div>

            <p class="text-[13.5px] text-slate-400 mb-5 leading-[1.65]">
                Realiza tu depósito o transferencia a la siguiente cuenta y luego sube el comprobante. Verificamos en <strong class="text-white">24-48h</strong> y activamos tu suscripción/factura.
            </p>

            <div class="grid sm:grid-cols-2 gap-3">
                <div class="dev-feature !p-4">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-slate-500 mb-1">Banco</div>
                    <div class="font-display font-bold text-white text-[15px]"><?= $e($bank['bank_name']) ?></div>
                </div>
                <div class="dev-feature !p-4">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-slate-500 mb-1">Tipo de cuenta</div>
                    <div class="font-display font-bold text-white text-[15px]"><?= $e($bank['bank_account_type']) ?></div>
                </div>
                <div class="dev-feature !p-4">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-slate-500 mb-1">Número de cuenta</div>
                    <div class="flex items-center gap-2">
                        <code class="font-mono text-[16px] text-white font-bold"><?= $e($bank['bank_account_number']) ?></code>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= $e($bank['bank_account_number']) ?>'); this.innerHTML='<i class=\'lucide lucide-check text-[12px]\'></i>'; setTimeout(()=>{this.innerHTML='<i class=\'lucide lucide-copy text-[12px]\'></i>'; window.lucide && window.lucide.createIcons()}, 1500); window.lucide && window.lucide.createIcons()" class="dev-btn dev-btn-soft dev-btn-icon !w-7 !h-7" title="Copiar"><i class="lucide lucide-copy text-[12px]"></i></button>
                    </div>
                </div>
                <div class="dev-feature !p-4">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-slate-500 mb-1">Cédula del titular</div>
                    <div class="flex items-center gap-2">
                        <code class="font-mono text-[15px] text-white font-bold"><?= $e($bank['bank_id_number']) ?></code>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= $e($bank['bank_id_number']) ?>'); this.innerHTML='<i class=\'lucide lucide-check text-[12px]\'></i>'; setTimeout(()=>{this.innerHTML='<i class=\'lucide lucide-copy text-[12px]\'></i>'; window.lucide && window.lucide.createIcons()}, 1500); window.lucide && window.lucide.createIcons()" class="dev-btn dev-btn-soft dev-btn-icon !w-7 !h-7" title="Copiar"><i class="lucide lucide-copy text-[12px]"></i></button>
                    </div>
                </div>
                <div class="dev-feature !p-4 sm:col-span-2">
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-slate-500 mb-1">Titular</div>
                    <div class="font-display font-bold text-white text-[15px]"><?= $e($bank['bank_account_holder']) ?></div>
                </div>
            </div>

            <div class="mt-5 dev-feature !p-4 flex items-start gap-3" style="background:rgba(14,165,233,.06)">
                <i class="lucide lucide-mail text-sky-300 mt-0.5"></i>
                <div class="flex-1 text-[12.5px] text-slate-300 leading-[1.6]">
                    <strong class="text-white">Después del depósito:</strong> sube el comprobante aquí mismo (formulario abajo) o envíalo por email a
                    <a href="mailto:<?= $e($bank['billing_approval_email']) ?>" class="dev-link"><?= $e($bank['billing_approval_email']) ?></a>.
                    Tu suscripción se activará tras la verificación.
                </div>
            </div>
        </div>
    </div>

    <!-- Steps card -->
    <div class="dev-card">
        <div class="dev-card-head"><h3 class="font-display font-bold text-white text-[15px]">Cómo funciona</h3></div>
        <div class="p-5 space-y-3">
            <?php foreach ([
                ['1','Deposita','Realiza tu depósito o transferencia a la cuenta indicada'],
                ['2','Sube el comprobante','Adjunta la imagen/PDF del recibo en el formulario'],
                ['3','Validamos','Verificamos el pago en 24-48h hábiles'],
                ['4','Activamos','Tu suscripción queda activa y recibes confirmación por email'],
            ] as [$n, $t, $d]): ?>
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg grid place-items-center text-white text-[12px] font-bold flex-shrink-0 mt-0.5" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><?= $n ?></div>
                    <div>
                        <div class="font-display font-bold text-white text-[13.5px]"><?= $t ?></div>
                        <div class="text-[12px] text-slate-400 leading-[1.5] mt-0.5"><?= $d ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Pending invoices -->
<?php if (!empty($pendingInvoices)): ?>
<div class="dev-card dev-card-pad" style="border-color:rgba(245,158,11,.30); background:rgba(245,158,11,.04)">
    <div class="flex items-start gap-3">
        <i class="lucide lucide-receipt text-amber-300 mt-1"></i>
        <div class="flex-1">
            <div class="font-display font-bold text-white text-[14.5px]"><?= count($pendingInvoices) ?> factura(s) pendiente(s) de pago</div>
            <div class="text-[12.5px] text-slate-300 mt-1">Total a pagar: <strong class="text-amber-200">$<?= number_format(array_sum(array_map(fn($i) => (float)$i['total'] - (float)$i['amount_paid'], $pendingInvoices)), 2) ?></strong></div>
        </div>
    </div>
    <div class="mt-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
        <?php foreach ($pendingInvoices as $inv): ?>
            <div class="dev-feature !p-3">
                <div class="flex items-center justify-between mb-1">
                    <code class="font-mono text-[12px] text-slate-200"><?= $e($inv['invoice_number']) ?></code>
                    <span class="dev-pill <?= $inv['status']==='overdue'?'dev-pill-red':'dev-pill-amber' ?> !text-[9.5px]"><?= $e($inv['status']) ?></span>
                </div>
                <div class="font-display font-bold text-white text-[15px]">$<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2) ?> <?= $e($inv['currency']) ?></div>
                <div class="text-[11px] text-slate-400 mt-0.5">Vence <?= $e($inv['due_date'] ?? '—') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Upload form -->
<form method="POST" action="<?= $url('/developers/billing/payment-proof') ?>" enctype="multipart/form-data" class="dev-card">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Subir comprobante de pago</h2>
            <p class="text-[12px] text-slate-400">JPG, PNG, WebP o PDF · Máx <?= (int)$bank['payment_max_file_mb'] ?>MB</p>
        </div>
    </div>
    <div class="p-5 grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="dev-label">Factura asociada (opcional)</label>
            <select name="dev_invoice_id" class="dev-input">
                <option value="0">— Sin asociar a factura —</option>
                <?php foreach ($pendingInvoices as $inv): ?>
                    <option value="<?= $inv['id'] ?>"><?= $e($inv['invoice_number']) ?> · $<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2) ?> <?= $e($inv['currency']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="dev-label">Monto depositado</label>
            <input type="number" step="0.01" name="amount" required class="dev-input" placeholder="0.00">
        </div>
        <div>
            <label class="dev-label">Moneda</label>
            <select name="currency" class="dev-input">
                <option value="DOP">DOP (RD$)</option>
                <option value="USD">USD ($)</option>
            </select>
        </div>
        <div>
            <label class="dev-label">Fecha del depósito</label>
            <input type="date" name="transfer_date" class="dev-input" value="<?= date('Y-m-d') ?>">
        </div>
        <div>
            <label class="dev-label">Banco emisor (opcional)</label>
            <input type="text" name="bank_used" class="dev-input" placeholder="Ej: Banreservas">
        </div>
        <div class="sm:col-span-2">
            <label class="dev-label">Referencia / # de transacción</label>
            <input type="text" name="reference" class="dev-input font-mono" placeholder="Ej: TX1234567890" style="font-size:12.5px">
        </div>
        <div class="sm:col-span-2">
            <label class="dev-label">Notas (opcional)</label>
            <textarea name="notes" class="dev-textarea" rows="2" placeholder="Algún detalle adicional"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="dev-label">Comprobante (imagen o PDF) <span class="text-amber-300">requerido</span></label>
            <input type="file" name="proof_file" required accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="dev-input" style="height:auto; padding:12px 14px">
            <div class="text-[10.5px] text-slate-500 mt-1">El archivo se enviará automáticamente a <?= $e($bank['billing_approval_email']) ?> para validación.</div>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="dev-btn dev-btn-primary"><i class="lucide lucide-upload text-[14px]"></i> Enviar comprobante para validación</button>
        </div>
    </div>
</form>

<!-- Historial de comprobantes -->
<div class="dev-card">
    <div class="dev-card-head">
        <h3 class="font-display font-bold text-white text-[15px]">Mis comprobantes enviados</h3>
    </div>
    <?php if (empty($proofs)): ?>
        <div class="p-6 text-center text-[13px] text-slate-400">Aún no has enviado comprobantes.</div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="dev-table">
                <thead><tr><th>Fecha</th><th>Factura</th><th>Monto</th><th>Referencia</th><th>Estado</th><th>Notas</th></tr></thead>
                <tbody>
                    <?php foreach ($proofs as $p):
                        $cls = $p['status'] === 'approved' ? 'dev-pill-emerald' : ($p['status'] === 'rejected' ? 'dev-pill-red' : 'dev-pill-amber');
                        $label = ['pending'=>'Pendiente','approved'=>'Aprobado','rejected'=>'Rechazado','cancelled'=>'Cancelado'][$p['status']] ?? $p['status'];
                    ?>
                        <tr>
                            <td class="text-[12px] font-mono"><?= $e(substr($p['created_at'], 0, 16)) ?></td>
                            <td class="text-[12px] font-mono"><?= $e($p['invoice_number'] ?? '—') ?></td>
                            <td class="font-display font-bold text-white">$<?= number_format((float)$p['amount'], 2) ?> <?= $e($p['currency']) ?></td>
                            <td class="text-[12px] font-mono text-slate-400"><?= $e($p['reference'] ?? '—') ?></td>
                            <td><span class="dev-pill <?= $cls ?>"><?= $label ?></span></td>
                            <td class="text-[11.5px] text-slate-400"><?= $e($p['review_notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
