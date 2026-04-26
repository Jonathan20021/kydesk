<section class="max-w-[1180px] mx-auto px-6 py-16">
    <div class="text-center mb-10">
        <span class="dev-pill mb-3"><i class="lucide lucide-tag text-[11px]"></i> Planes API</span>
        <h1 class="dev-h1">Planes y precios</h1>
        <p class="dev-muted mt-3 max-w-[640px] mx-auto">Paga solo por lo que usas. Cambia de plan o cancela cuando quieras desde tu panel.</p>
    </div>

    <?php if (empty($plans)): ?>
        <div class="dev-card p-10 text-center">
            <p class="dev-muted">Aún no hay planes públicos. Vuelve pronto.</p>
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-<?= count($plans) > 4 ? '5' : count($plans) ?> gap-5">
            <?php foreach ($plans as $p):
                $features = json_decode((string)$p['features'], true) ?: [];
            ?>
                <div class="dev-feature relative <?= $p['is_featured'] ? 'ring-2 ring-sky-400/40' : '' ?>">
                    <?php if ($p['is_featured']): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 dev-pill bg-sky-500 text-white border-sky-400">Más popular</div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:<?= $e($p['color']) ?>"><i class="lucide lucide-<?= $e($p['icon']) ?>"></i></div>
                        <div>
                            <div class="font-display font-bold text-white text-[17px]"><?= $e($p['name']) ?></div>
                            <div class="text-[11.5px] dev-muted"><?= $e($p['slug']) ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="font-display font-bold text-white text-[34px] leading-none">$<?= number_format((float)$p['price_monthly'], 0) ?><span class="text-[14px] dev-muted font-normal">/mes</span></div>
                        <?php if ((float)$p['price_yearly'] > 0): ?>
                            <div class="text-[12px] dev-muted mt-1">$<?= number_format((float)$p['price_yearly'], 0) ?>/año (<?= round((1 - ($p['price_yearly'] / 12) / max(0.01, $p['price_monthly'])) * 100) ?>% off)</div>
                        <?php endif; ?>
                    </div>
                    <p class="text-[12.5px] dev-muted mb-4 leading-[1.55]"><?= $e($p['description']) ?></p>
                    <ul class="space-y-1.5 text-[12.5px] mb-5">
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= number_format((int)$p['max_requests_month']) ?> requests/mes</span></li>
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= (int)$p['max_apps'] ?> app<?= (int)$p['max_apps'] === 1 ? '' : 's' ?></span></li>
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= (int)$p['max_tokens_per_app'] ?> tokens/app</span></li>
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= (int)$p['rate_limit_per_min'] ?> req/min</span></li>
                        <?php if ((float)$p['overage_price_per_1k'] > 0): ?>
                            <li class="flex items-center gap-2"><i class="lucide lucide-zap text-amber-400 text-[12px]"></i> <span class="text-slate-300">Overage: $<?= number_format((float)$p['overage_price_per_1k'], 4) ?>/1k</span></li>
                        <?php endif; ?>
                        <?php foreach ($features as $f): ?>
                            <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= $e(ucfirst(str_replace('_',' ',$f))) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= $url('/developers/register?plan=' . urlencode($p['slug'])) ?>" class="dev-btn <?= $p['is_featured'] ? 'dev-btn-primary' : 'dev-btn-ghost' ?> w-full">
                        <?= (float)$p['price_monthly'] == 0 ? 'Empezar gratis' : 'Suscribirse' ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
