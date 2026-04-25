<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<section class="pt-32 pb-16">
    <div class="max-w-[1240px] mx-auto px-6 text-center">
        <div class="text-[11.5px] font-bold uppercase tracking-[0.14em] text-brand-600 mb-3">PRECIOS</div>
        <h1 class="heading-xl" style="text-wrap:balance">Planes simples.<br><span class="text-ink-400">Sin sorpresas.</span></h1>
        <p class="mt-6 text-[17px] max-w-lg mx-auto text-ink-500">Empieza gratis. Escala cuando crezcas.</p>
    </div>
    <div class="max-w-[1240px] mx-auto px-6 mt-16 grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php
        $plans = [
            ['Starter','$0','mes','Para empezar', [['3 usuarios',1],['100 tickets/mes',1],['Portal público',1],['Reportes avanzados',0],['Automatizaciones',0]], false],
            ['Pro','$29','usuario/mes','En crecimiento', [['Usuarios ilimitados',1],['Tickets ilimitados',1],['Portal con marca',1],['Reportes avanzados',1],['SLAs personalizados',1],['Automatizaciones',1]], true],
            ['Enterprise','A medida','','Organizaciones', [['Todo de Pro',1],['SSO SAML',1],['Auditoría avanzada',1],['SLA 99.99%',1],['Soporte 24/7',1]], false],
        ];
        foreach ($plans as [$n,$p,$per,$d,$feats,$hl]): ?>
            <div class="card card-pad relative <?= $hl?'!bg-ink-900 !text-white !border-ink-900 shadow-2xl':'' ?>" style="<?= $hl?'transform:scale(1.03)':'' ?>">
                <?php if ($hl): ?><div class="absolute top-6 right-6 px-2.5 h-5 rounded-full bg-brand-500/30 text-white text-[10px] font-bold flex items-center">POPULAR</div><?php endif; ?>
                <div class="text-[13px] font-semibold <?= $hl?'text-brand-200':'text-brand-600' ?>"><?= $n ?></div>
                <div class="mt-2.5 flex items-baseline gap-1">
                    <span class="font-display font-extrabold text-[44px] tracking-[-0.035em] leading-none"><?= $p ?></span>
                    <?php if ($per): ?><span class="text-[12.5px] <?= $hl?'text-brand-200':'text-ink-400' ?>">/ <?= $per ?></span><?php endif; ?>
                </div>
                <p class="mt-1.5 text-[13px] <?= $hl?'text-brand-200':'text-ink-500' ?>"><?= $d ?></p>
                <a href="<?= $url('/auth/register') ?>" class="block text-center h-11 rounded-full font-semibold text-[13.5px] mt-6 leading-[44px] transition <?= $hl?'bg-white text-ink-900 hover:bg-ink-100':'bg-brand-500 text-white hover:bg-brand-600' ?>"><?= $n==='Enterprise'?'Contactar':'Empezar' ?></a>
                <ul class="mt-6 space-y-2.5 text-[13px]">
                    <?php foreach ($feats as [$f,$yes]): ?>
                        <li class="flex items-start gap-2 <?= !$yes?'opacity-40':'' ?>"><i class="lucide <?= $yes?'lucide-check text-emerald-500':'lucide-minus' ?>"></i> <?= $f ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
