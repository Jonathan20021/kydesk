<?php
$slug = $tenant->slug;
$totalDue = array_sum(array_map(fn($i) => (float)$i['total'] - (float)$i['amount_paid'], $pendingInvoices));
$pendingProofs = array_filter($proofs, fn($p) => $p['status'] === 'pending');
?>

<style>
    .pi-page-hero { position: relative; overflow: hidden; border-radius: 24px; padding: 28px 32px; margin-bottom: 24px; background: linear-gradient(135deg, #eef4ff 0%, #f3f0ff 100%); border: 1px solid #e6e3f5; }
    .pi-page-hero::before { content: ''; position: absolute; top: -40px; right: -40px; width: 280px; height: 280px; border-radius: 50%; background: radial-gradient(circle, rgba(124, 92, 255, .18), transparent 70%); pointer-events: none; }
    .pi-page-hero h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 26px; letter-spacing: -.025em; color: #16151b; margin: 0; }
    .pi-page-hero p { color: #5a5867; font-size: 14px; margin: 4px 0 0; }
    .pi-tag { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; background: rgba(124, 92, 255, .12); color: #5a3aff; border: 1px solid rgba(124, 92, 255, .22); }

    .pi-bank-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    @media (max-width: 768px) { .pi-bank-grid { grid-template-columns: 1fr; } }
    .pi-bank-cell { padding: 16px 18px; border-radius: 14px; border: 1px solid #ececef; background: #fff; transition: all .15s; }
    .pi-bank-cell:hover { border-color: #cdbfff; box-shadow: 0 4px 14px -6px rgba(124, 92, 255, .15); }
    .pi-bank-label { font-size: 10.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #8e8e9a; margin-bottom: 6px; }
    .pi-bank-value { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 17px; color: #16151b; line-height: 1.2; }
    .pi-bank-mono { font-family: 'Geist Mono', monospace; font-weight: 700; font-size: 17px; color: #16151b; letter-spacing: -.01em; }
    .pi-copy-btn { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 8px; background: #f3f0ff; color: #5a3aff; font-size: 11px; font-weight: 600; border: 1px solid #e7e0ff; cursor: pointer; transition: all .15s; margin-left: 8px; }
    .pi-copy-btn:hover { background: #e7e0ff; }
    .pi-copy-btn.copied { background: #d1fae5; color: #047857; border-color: #a7f3d0; }

    .pi-step { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; }
    .pi-step + .pi-step { border-top: 1px dashed #ececef; }
    .pi-step-num { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; color: white; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 13px; flex-shrink: 0; background: linear-gradient(135deg, #7c5cff 0%, #a78bfa 100%); box-shadow: 0 4px 10px -2px rgba(124, 92, 255, .35); }
    .pi-step-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 13.5px; color: #16151b; }
    .pi-step-desc { font-size: 12px; color: #6b6b78; line-height: 1.5; margin-top: 2px; }

    .pi-callout-info { display: flex; align-items: flex-start; gap: 12px; padding: 14px 18px; border-radius: 14px; background: #f3f0ff; border: 1px solid #cdbfff; color: #2a2a33; font-size: 13px; line-height: 1.6; margin-top: 18px; }
    .pi-callout-info a { color: #5a3aff; font-weight: 600; text-decoration: none; }
    .pi-callout-warn { display: flex; align-items: center; gap: 14px; padding: 16px 22px; border-radius: 16px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; margin-bottom: 24px; }

    .pi-form-section { padding: 22px 26px; border-bottom: 1px solid #ececef; }
    .pi-form-section:last-child { border-bottom: none; }
    .pi-section-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 15px; color: #16151b; margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
    .pi-section-meta { font-size: 12.5px; color: #6b6b78; margin-bottom: 16px; }
    .pi-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .pi-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
    @media (max-width: 768px) { .pi-grid-2, .pi-grid-3 { grid-template-columns: 1fr; } }

    .pi-file-drop { display: block; border: 2px dashed #cdbfff; border-radius: 14px; padding: 24px 20px; text-align: center; background: #faf9ff; transition: all .15s; cursor: pointer; }
    .pi-file-drop:hover { border-color: #7c5cff; background: #f3f0ff; }
    .pi-file-drop.has-file { border-style: solid; border-color: #10b981; background: #ecfdf5; }
    .pi-file-drop input[type="file"] { display: none; }
    .pi-file-icon { width: 48px; height: 48px; margin: 0 auto 10px; border-radius: 12px; display: grid; place-items: center; background: white; color: #7c5cff; box-shadow: 0 4px 12px -4px rgba(124, 92, 255, .25); }
    .pi-file-name { font-size: 13px; font-weight: 600; color: #16151b; }
    .pi-file-hint { font-size: 11.5px; color: #8e8e9a; margin-top: 4px; }

    .pi-submit-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 24px; height: 46px; border-radius: 12px; font-size: 14px; font-weight: 600; color: white; background: linear-gradient(135deg, #7c5cff 0%, #6c47ff 100%); box-shadow: 0 8px 20px -6px rgba(124, 92, 255, .45); border: none; cursor: pointer; transition: all .15s; }
    .pi-submit-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 26px -6px rgba(124, 92, 255, .55); }

    .pi-status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
    .pi-status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .pi-status-approved { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
    .pi-status-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .pi-amount-prefix { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8e8e9a; font-family: 'Geist Mono', monospace; font-weight: 600; font-size: 13px; pointer-events: none; }
</style>

<!-- HERO -->
<div class="pi-page-hero">
    <div class="flex items-start justify-between gap-4 flex-wrap relative" style="z-index:1">
        <div>
            <span class="pi-tag mb-3"><i class="lucide lucide-landmark text-[11px]"></i> Pago manual con depósito</span>
            <h1>Cómo pagar tu suscripción</h1>
            <p>Realiza tu depósito al banco indicado y sube el comprobante. Validamos en <strong style="color:#5a3aff">24-48h hábiles</strong>.</p>
        </div>
        <a href="<?= $url('/t/' . $slug . '/billing') ?>" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,.7); backdrop-filter:blur(8px)"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a facturación</a>
    </div>
</div>

<?php if (!empty($pendingInvoices)): ?>
<div class="pi-callout-warn">
    <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#fef3c7; color:#b45309"><i class="lucide lucide-receipt"></i></div>
    <div class="flex-1">
        <div class="font-display font-bold text-[14.5px]" style="color:#92400e">Tienes <?= count($pendingInvoices) ?> factura<?= count($pendingInvoices) > 1 ? 's' : '' ?> pendiente<?= count($pendingInvoices) > 1 ? 's' : '' ?> de pago</div>
        <div class="text-[12.5px]" style="color:#a16207">Total a depositar: <strong>$<?= number_format($totalDue, 2) ?></strong></div>
    </div>
    <a href="#upload-form" class="btn btn-sm" style="background:#92400e; color:white">Subir comprobante <i class="lucide lucide-arrow-down text-[12px]"></i></a>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    <div class="card lg:col-span-2 card-pad">
        <div class="section-head" style="margin-bottom:18px">
            <div class="section-head-icon" style="background:linear-gradient(135deg,#7c5cff,#a78bfa); color:white"><i class="lucide lucide-landmark text-[16px]"></i></div>
            <div class="flex-1">
                <h3 class="section-title">Datos bancarios</h3>
                <div class="section-head-meta">Realiza tu depósito o transferencia a esta cuenta</div>
            </div>
        </div>

        <div class="pi-bank-grid">
            <div class="pi-bank-cell">
                <div class="pi-bank-label">Banco</div>
                <div class="pi-bank-value"><?= $e($bank['bank_name']) ?></div>
            </div>
            <div class="pi-bank-cell">
                <div class="pi-bank-label">Tipo de cuenta</div>
                <div class="pi-bank-value"><?= $e($bank['bank_account_type']) ?></div>
            </div>
            <div class="pi-bank-cell">
                <div class="pi-bank-label">Número de cuenta</div>
                <div class="flex items-center gap-1 flex-wrap">
                    <span class="pi-bank-mono"><?= $e($bank['bank_account_number']) ?></span>
                    <button type="button" class="pi-copy-btn" data-copy="<?= $e($bank['bank_account_number']) ?>"><i class="lucide lucide-copy text-[11px]"></i> Copiar</button>
                </div>
            </div>
            <div class="pi-bank-cell">
                <div class="pi-bank-label">Cédula del titular</div>
                <div class="flex items-center gap-1 flex-wrap">
                    <span class="pi-bank-mono"><?= $e($bank['bank_id_number']) ?></span>
                    <button type="button" class="pi-copy-btn" data-copy="<?= $e($bank['bank_id_number']) ?>"><i class="lucide lucide-copy text-[11px]"></i> Copiar</button>
                </div>
            </div>
            <div class="pi-bank-cell" style="grid-column: 1 / -1">
                <div class="pi-bank-label">Titular de la cuenta</div>
                <div class="pi-bank-value"><?= $e($bank['bank_account_holder']) ?></div>
            </div>
        </div>

        <div class="pi-callout-info">
            <i class="lucide lucide-mail text-[16px]" style="color:#5a3aff; flex-shrink:0; margin-top:2px"></i>
            <div>Después del depósito, sube el comprobante en el formulario de abajo o envíalo a <a href="mailto:<?= $e($bank['billing_approval_email']) ?>"><?= $e($bank['billing_approval_email']) ?></a> para validación.</div>
        </div>
    </div>

    <div class="card card-pad">
        <div class="section-head" style="margin-bottom:8px">
            <div class="section-head-icon" style="background:#ecfdf5; color:#047857"><i class="lucide lucide-check-circle text-[16px]"></i></div>
            <div>
                <h3 class="section-title">Cómo funciona</h3>
                <div class="section-head-meta">4 pasos simples</div>
            </div>
        </div>
        <?php foreach ([
            ['Deposita', 'Realiza el depósito a la cuenta indicada'],
            ['Sube el comprobante', 'Adjunta la imagen o PDF del recibo'],
            ['Validamos', 'Verificamos en 24-48h hábiles'],
            ['Activamos', 'Tu suscripción queda activa'],
        ] as $i => [$t, $d]): ?>
            <div class="pi-step">
                <div class="pi-step-num"><?= $i + 1 ?></div>
                <div>
                    <div class="pi-step-title"><?= $t ?></div>
                    <div class="pi-step-desc"><?= $d ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/billing/payment-proof') ?>" enctype="multipart/form-data" class="card mb-6" id="upload-form" x-data="{fileName:'', fileSet:false}">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="card-pad" style="border-bottom:1px solid #ececef">
        <div class="section-head">
            <div class="section-head-icon" style="background:#dbeafe; color:#1d4ed8"><i class="lucide lucide-upload text-[16px]"></i></div>
            <div>
                <h3 class="section-title">Subir comprobante de pago</h3>
                <div class="section-head-meta">JPG, PNG, WebP o PDF · Máx <?= (int)$bank['payment_max_file_mb'] ?>MB</div>
            </div>
        </div>
    </div>

    <div class="pi-form-section">
        <h4 class="pi-section-title"><i class="lucide lucide-file-text text-[14px] text-ink-400"></i> Detalles del depósito</h4>
        <div class="pi-section-meta">Información del recibo bancario</div>

        <div style="margin-bottom:14px">
            <label class="label">Factura asociada <span class="text-[11px] text-ink-400 font-normal">(opcional)</span></label>
            <select name="invoice_id" class="input">
                <option value="0">— Sin asociar a factura específica —</option>
                <?php foreach ($pendingInvoices as $inv): ?>
                    <option value="<?= $inv['id'] ?>"><?= $e($inv['invoice_number']) ?> · $<?= number_format((float)$inv['total'] - (float)$inv['amount_paid'], 2) ?> <?= $e($inv['currency']) ?> · vence <?= $e($inv['due_date'] ?? '—') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pi-grid-3">
            <div>
                <label class="label">Monto depositado</label>
                <div class="relative">
                    <span class="pi-amount-prefix">$</span>
                    <input type="number" step="0.01" name="amount" required class="input" style="padding-left:26px" placeholder="0.00">
                </div>
            </div>
            <div>
                <label class="label">Moneda</label>
                <select name="currency" class="input">
                    <option value="DOP">DOP (RD$)</option>
                    <option value="USD">USD ($)</option>
                </select>
            </div>
            <div>
                <label class="label">Fecha del depósito</label>
                <input type="date" name="transfer_date" class="input" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="pi-grid-2" style="margin-top:14px">
            <div>
                <label class="label">Banco emisor <span class="text-[11px] text-ink-400 font-normal">(opcional)</span></label>
                <input type="text" name="bank_used" class="input" placeholder="Ej: Banreservas, BHD, Scotiabank">
            </div>
            <div>
                <label class="label">Referencia / # de transacción</label>
                <input type="text" name="reference" class="input font-mono" placeholder="Ej: TX1234567890" style="font-size:13px">
            </div>
        </div>

        <div style="margin-top:14px">
            <label class="label">Notas <span class="text-[11px] text-ink-400 font-normal">(opcional)</span></label>
            <textarea name="notes" class="input" rows="2" placeholder="Algún detalle adicional sobre el pago" style="height:auto; padding:12px 14px; line-height:1.55"></textarea>
        </div>
    </div>

    <div class="pi-form-section">
        <h4 class="pi-section-title"><i class="lucide lucide-paperclip text-[14px] text-ink-400"></i> Comprobante <span class="text-[11px] text-amber-700 font-bold uppercase tracking-wider ml-1">requerido</span></h4>
        <div class="pi-section-meta">El archivo se enviará automáticamente a <code style="background:#f3f0ff; padding:1px 6px; border-radius:4px; font-size:11.5px; color:#5a3aff"><?= $e($bank['billing_approval_email']) ?></code> para validación</div>

        <label class="pi-file-drop" :class="fileSet && 'has-file'">
            <input type="file" name="proof_file" required accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                @change="if($event.target.files.length){fileName=$event.target.files[0].name; fileSet=true}">
            <div class="pi-file-icon">
                <i class="lucide lucide-upload-cloud" x-show="!fileSet"></i>
                <i class="lucide lucide-file-check text-emerald-600" x-show="fileSet" x-cloak></i>
            </div>
            <div class="pi-file-name">
                <span x-show="!fileSet">Click para seleccionar archivo</span>
                <span x-show="fileSet" x-text="fileName" x-cloak></span>
            </div>
            <div class="pi-file-hint">JPG, PNG, WebP, GIF o PDF · Máx <?= (int)$bank['payment_max_file_mb'] ?>MB</div>
        </label>
    </div>

    <div class="pi-form-section" style="background:#fafafb">
        <button type="submit" class="pi-submit-btn">
            <i class="lucide lucide-send text-[14px]"></i> Enviar comprobante para validación
        </button>
        <span class="text-[12px] text-ink-500 ml-3">Recibirás un email de confirmación cuando se valide</span>
    </div>
</form>

<?php if (!empty($proofs)): ?>
<div class="card">
    <div class="card-pad" style="border-bottom:1px solid #ececef">
        <div class="section-head">
            <div class="section-head-icon" style="background:#fff7ed; color:#9a3412"><i class="lucide lucide-history text-[16px]"></i></div>
            <div class="flex-1">
                <h3 class="section-title">Mis comprobantes enviados</h3>
                <div class="section-head-meta"><?= count($proofs) ?> comprobante<?= count($proofs)===1?'':'s' ?> · <?= count($pendingProofs) ?> pendiente<?= count($pendingProofs)===1?'':'s' ?> de validación</div>
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Factura</th><th>Monto</th><th>Banco emisor</th><th>Referencia</th><th>Estado</th><th>Notas de revisión</th></tr></thead>
            <tbody>
                <?php foreach ($proofs as $p):
                    $cls = ['pending'=>'pi-status-pending','approved'=>'pi-status-approved','rejected'=>'pi-status-rejected','cancelled'=>'pi-status-pending'][$p['status']] ?? 'pi-status-pending';
                    $label = ['pending'=>'Pendiente','approved'=>'Aprobado','rejected'=>'Rechazado','cancelled'=>'Cancelado'][$p['status']] ?? $p['status'];
                    $icon = ['pending'=>'clock','approved'=>'check','rejected'=>'x','cancelled'=>'minus'][$p['status']] ?? 'clock';
                ?>
                    <tr>
                        <td class="text-[12.5px] font-mono text-ink-700"><?= $e(substr($p['created_at'], 0, 16)) ?></td>
                        <td class="text-[12px] font-mono"><?= $e($p['invoice_number'] ?? '—') ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['amount'], 2) ?> <span class="text-[11px] text-ink-400 font-normal"><?= $e($p['currency']) ?></span></td>
                        <td class="text-[12.5px]"><?= $e($p['bank_used'] ?? '—') ?></td>
                        <td class="text-[12px] font-mono text-ink-500"><?= $e($p['reference'] ?? '—') ?></td>
                        <td><span class="pi-status-pill <?= $cls ?>"><i class="lucide lucide-<?= $icon ?> text-[10px]"></i> <?= $label ?></span></td>
                        <td class="text-[11.5px] text-ink-500" style="max-width:280px"><?= $e($p['review_notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
(function(){
    document.querySelectorAll('.pi-copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(value).then(() => {
                const original = btn.innerHTML;
                btn.classList.add('copied');
                btn.innerHTML = '<i class="lucide lucide-check text-[11px]"></i> Copiado';
                if (window.lucide) window.lucide.createIcons();
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = original;
                    if (window.lucide) window.lucide.createIcons();
                }, 1500);
            });
        });
    });
})();
</script>
