<div class="admin-card dev-card-pad" style="background:linear-gradient(135deg, rgba(14,165,233,.05), rgba(99,102,241,.03));">
    <div class="flex items-start gap-3 admin-card-pad" style="padding:18px 22px">
        <div class="w-10 h-10 rounded-xl grid place-items-center text-white flex-shrink-0" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><i class="lucide lucide-landmark"></i></div>
        <div>
            <h2 class="admin-h2">Datos bancarios para depósitos</h2>
            <p class="text-[13px] text-ink-500 mt-1">Esta información se muestra a developers y clientes del helpdesk cuando van a pagar una suscripción o factura. Los comprobantes recibidos se notifican al email aprobador y aparecen en <a href="<?= $url('/admin/payment-proofs') ?>" style="color:var(--brand-700)" class="font-semibold">Comprobantes</a>.</p>
        </div>
    </div>
</div>

<form method="POST" action="<?= $url('/admin/bank-settings') ?>" class="admin-card admin-card-pad max-w-[820px] space-y-5">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div>
        <h3 class="admin-h2 mb-3 flex items-center gap-2"><i class="lucide lucide-credit-card text-brand-700"></i> Cuenta bancaria</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Banco</label>
                <input type="text" name="bank_name" required class="admin-input" value="<?= $e($bank['bank_name']) ?>">
            </div>
            <div>
                <label class="admin-label">Tipo de cuenta</label>
                <select name="bank_account_type" class="admin-select">
                    <?php foreach (['Corriente','Ahorros','Vista'] as $t): ?>
                        <option value="<?= $t ?>" <?= $bank['bank_account_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="admin-label">Número de cuenta</label>
                <input type="text" name="bank_account_number" required class="admin-input font-mono" value="<?= $e($bank['bank_account_number']) ?>">
            </div>
            <div>
                <label class="admin-label">Cédula del titular</label>
                <input type="text" name="bank_id_number" required class="admin-input font-mono" value="<?= $e($bank['bank_id_number']) ?>">
            </div>
            <div>
                <label class="admin-label">Nombre del titular</label>
                <input type="text" name="bank_account_holder" required class="admin-input" value="<?= $e($bank['bank_account_holder']) ?>">
            </div>
            <div>
                <label class="admin-label">Moneda principal</label>
                <select name="bank_currency" class="admin-select">
                    <option value="DOP" <?= $bank['bank_currency'] === 'DOP' ? 'selected' : '' ?>>DOP (RD$)</option>
                    <option value="USD" <?= $bank['bank_currency'] === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                </select>
            </div>
        </div>
    </div>

    <div>
        <h3 class="admin-h2 mb-3 flex items-center gap-2"><i class="lucide lucide-mail text-brand-700"></i> Validación</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="admin-label">Email para validar pagos</label>
                <input type="email" name="billing_approval_email" required class="admin-input" value="<?= $e($bank['billing_approval_email']) ?>">
                <div class="text-[11.5px] text-ink-400 mt-1">A esta dirección se envían automáticamente los comprobantes recibidos para validación. Por default: <code>jonathansandoval@kyrosrd.com</code></div>
            </div>
            <div>
                <label class="admin-label">Tamaño máximo de archivo (MB)</label>
                <input type="number" name="payment_max_file_mb" class="admin-input" value="<?= $e($bank['payment_max_file_mb']) ?>" min="1" max="50">
            </div>
            <div>
                <label class="admin-label">Comprobante requerido</label>
                <select name="payment_proof_required" class="admin-select">
                    <option value="1" <?= $bank['payment_proof_required'] === '1' ? 'selected' : '' ?>>Sí (recomendado)</option>
                    <option value="0" <?= $bank['payment_proof_required'] === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 pt-2 border-t" style="border-color:var(--border)">
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Guardar datos bancarios</button>
        <a href="<?= $url('/admin/payment-proofs') ?>" class="admin-btn admin-btn-soft">Volver a comprobantes</a>
    </div>
</form>

<!-- Vista previa de cómo se ve para el cliente -->
<div class="admin-card admin-card-pad max-w-[820px]" style="background:var(--bg)">
    <h3 class="admin-h2 mb-3 flex items-center gap-2"><i class="lucide lucide-eye text-ink-500"></i> Vista previa (lo que verá el cliente)</h3>
    <div class="grid sm:grid-cols-2 gap-3">
        <div class="admin-card-pad" style="background:white; border:1px solid var(--border); border-radius:12px">
            <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Banco</div>
            <div class="font-display font-bold text-[15px]"><?= $e($bank['bank_name']) ?></div>
        </div>
        <div class="admin-card-pad" style="background:white; border:1px solid var(--border); border-radius:12px">
            <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Tipo</div>
            <div class="font-display font-bold text-[15px]"><?= $e($bank['bank_account_type']) ?></div>
        </div>
        <div class="admin-card-pad" style="background:white; border:1px solid var(--border); border-radius:12px">
            <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">N° de cuenta</div>
            <code class="font-mono text-[16px] font-bold"><?= $e($bank['bank_account_number']) ?></code>
        </div>
        <div class="admin-card-pad" style="background:white; border:1px solid var(--border); border-radius:12px">
            <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Cédula</div>
            <code class="font-mono text-[15px] font-bold"><?= $e($bank['bank_id_number']) ?></code>
        </div>
    </div>
</div>
