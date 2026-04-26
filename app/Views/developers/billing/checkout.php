<div class="dev-card max-w-[640px] mx-auto p-7">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-12 h-12 rounded-2xl grid place-items-center text-white" style="background:<?= $e($plan['color']) ?>"><i class="lucide lucide-<?= $e($plan['icon']) ?>"></i></div>
        <div>
            <h2 class="font-display font-bold text-white text-[22px]"><?= $e($plan['name']) ?></h2>
            <p class="text-[12.5px] text-slate-400"><?= $e($plan['description']) ?></p>
        </div>
    </div>

    <form method="POST" action="<?= $url('/developers/billing/subscribe/' . $plan['id']) ?>" class="space-y-5">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <div>
            <label class="dev-label">Ciclo de facturación</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="dev-card p-4 cursor-pointer hover:border-sky-500/40 transition" style="border-color:rgba(56,189,248,.18)">
                    <input type="radio" name="billing_cycle" value="monthly" checked class="hidden peer">
                    <div class="peer-checked:text-sky-300">
                        <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500">Mensual</div>
                        <div class="font-display font-bold text-white text-[24px] mt-1">$<?= number_format((float)$plan['price_monthly'], 0) ?><span class="text-[12px] text-slate-400 font-normal">/mes</span></div>
                    </div>
                </label>
                <?php if ((float)$plan['price_yearly'] > 0): ?>
                <label class="dev-card p-4 cursor-pointer hover:border-sky-500/40 transition relative" style="border-color:rgba(56,189,248,.18)">
                    <input type="radio" name="billing_cycle" value="yearly" class="hidden peer">
                    <span class="absolute top-2 right-2 dev-pill dev-pill-emerald text-[9px]"><?= round((1 - ($plan['price_yearly'] / 12) / max(0.01, $plan['price_monthly'])) * 100) ?>% off</span>
                    <div>
                        <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500">Anual</div>
                        <div class="font-display font-bold text-white text-[24px] mt-1">$<?= number_format((float)$plan['price_yearly'], 0) ?><span class="text-[12px] text-slate-400 font-normal">/año</span></div>
                    </div>
                </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="dev-card p-4" style="background:rgba(56,189,248,.04)">
            <div class="text-[11px] uppercase font-bold tracking-wider text-slate-500 mb-2">Lo que recibes</div>
            <ul class="space-y-1.5 text-[13px]">
                <li class="flex items-center gap-2 text-slate-200"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= number_format((int)$plan['max_requests_month']) ?> requests/mes</li>
                <li class="flex items-center gap-2 text-slate-200"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= (int)$plan['max_apps'] ?> apps con workspaces aislados</li>
                <li class="flex items-center gap-2 text-slate-200"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= (int)$plan['max_tokens_per_app'] ?> tokens API por app</li>
                <li class="flex items-center gap-2 text-slate-200"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= (int)$plan['rate_limit_per_min'] ?> requests/min</li>
            </ul>
        </div>

        <?php if ((float)$plan['price_monthly'] > 0):
            $bank = \App\Core\BankInfo::all();
        ?>
            <div class="dev-feature !p-4" style="background:rgba(14,165,233,.05); border-color:rgba(56,189,248,.20)">
                <div class="flex items-center gap-2 mb-2">
                    <i class="lucide lucide-landmark text-sky-300 text-[15px]"></i>
                    <strong class="text-white text-[13.5px]">Pago manual con depósito bancario</strong>
                </div>
                <div class="grid sm:grid-cols-2 gap-2 text-[12px]">
                    <div><span class="text-slate-500">Banco:</span> <strong class="text-slate-200"><?= $e($bank['bank_name']) ?></strong></div>
                    <div><span class="text-slate-500">Cuenta:</span> <code class="text-slate-200 font-mono"><?= $e($bank['bank_account_number']) ?></code></div>
                    <div><span class="text-slate-500">Tipo:</span> <strong class="text-slate-200"><?= $e($bank['bank_account_type']) ?></strong></div>
                    <div><span class="text-slate-500">Cédula:</span> <code class="text-slate-200 font-mono"><?= $e($bank['bank_id_number']) ?></code></div>
                </div>
                <div class="text-[11.5px] text-slate-400 mt-3 leading-[1.5]">
                    Al confirmar, se genera una factura pendiente. Realiza el depósito y <a href="<?= $url('/developers/billing/payment-info') ?>" class="dev-link">sube el comprobante</a> — o envíalo a <code class="text-slate-300"><?= $e($bank['billing_approval_email']) ?></code>. Verificamos en 24-48h.
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center gap-2 pt-2">
            <button type="submit" class="dev-btn dev-btn-primary flex-1"><i class="lucide lucide-check text-[14px]"></i> Confirmar suscripción</button>
            <a href="<?= $url('/developers/billing/plans') ?>" class="dev-btn dev-btn-soft">Cancelar</a>
        </div>
    </form>
</div>
