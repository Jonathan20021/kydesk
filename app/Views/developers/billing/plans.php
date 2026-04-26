<div class="text-center mb-6">
    <span class="dev-pill mb-3"><i class="lucide lucide-tag text-[11px]"></i> Planes</span>
    <h2 class="text-white font-display font-bold text-[24px]">Cambia o mejora tu plan</h2>
    <p class="dev-muted mt-2">Cancela o cambia cuando quieras. Sin permanencia.</p>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-<?= count($plans) > 4 ? '5' : count($plans) ?> gap-4">
    <?php foreach ($plans as $p):
        $isCurrent = (int)($currentPlanId ?? 0) === (int)$p['id'];
        $features = json_decode((string)$p['features'], true) ?: [];
    ?>
        <div class="dev-card p-5 relative <?= $isCurrent ? 'ring-2 ring-sky-400/50' : '' ?>">
            <?php if ($isCurrent): ?>
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 dev-pill dev-pill-sky">Tu plan actual</div>
            <?php elseif ($p['is_featured']): ?>
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 dev-pill bg-amber-500/20 text-amber-300 border-amber-500/30">Recomendado</div>
            <?php endif; ?>
            <div class="flex items-center gap-2 mb-2">
                <div class="w-9 h-9 rounded-xl grid place-items-center text-white" style="background:<?= $e($p['color']) ?>"><i class="lucide lucide-<?= $e($p['icon']) ?>"></i></div>
                <div>
                    <div class="font-display font-bold text-white text-[16px]"><?= $e($p['name']) ?></div>
                    <div class="text-[10.5px] text-slate-500 font-mono"><?= $e($p['slug']) ?></div>
                </div>
            </div>
            <div class="mb-2">
                <div class="font-display font-bold text-white text-[26px] leading-none">$<?= number_format((float)$p['price_monthly'], 0) ?><span class="text-[12px] dev-muted font-normal">/mes</span></div>
            </div>
            <p class="text-[12px] dev-muted mb-3 leading-[1.55]"><?= $e($p['description']) ?></p>
            <ul class="space-y-1 text-[12px] mb-4">
                <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <?= number_format((int)$p['max_requests_month']) ?> req/mes</li>
                <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <?= (int)$p['max_apps'] ?> app<?= (int)$p['max_apps'] === 1 ? '' : 's' ?></li>
                <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <?= (int)$p['rate_limit_per_min'] ?> req/min</li>
                <?php foreach (array_slice($features, 0, 3) as $f): ?>
                    <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <?= $e(ucfirst(str_replace('_',' ',$f))) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($isCurrent): ?>
                <button class="dev-btn dev-btn-soft w-full" disabled>Plan actual</button>
            <?php else: ?>
                <a href="<?= $url('/developers/billing/checkout/' . $p['id']) ?>" class="dev-btn dev-btn-primary w-full">Elegir plan</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
