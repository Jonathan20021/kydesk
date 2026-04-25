<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<!-- HERO -->
<section class="relative pt-36 pb-12 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grid-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex justify-center">
                <div class="aura-pill">
                    <span class="aura-pill-tag"><i class="lucide lucide-layers"></i> PRODUCTO</span>
                    <span class="text-ink-700 font-medium">8 módulos · 1 plataforma</span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)">Hecho para equipos<br>que <span class="gradient-shift">resuelven en serio</span>.</h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed">Cada módulo está diseñado para reducir fricción y darle a tu equipo más tiempo para resolver. Hacé clic en cualquiera para ver detalle.</p>
        </div>
    </div>
</section>

<!-- ALL FEATURES GRID -->
<section class="py-16 relative">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($features as $key => $f): ?>
                <a href="<?= $url('/features/' . $key) ?>" class="bento spotlight-card group block relative" style="border-color:<?= $f['color'] ?>20">
                    <div class="bento-glow"></div>
                    <div class="absolute -top-px left-6 right-6 h-[2px] rounded-full" style="background:linear-gradient(90deg,transparent,<?= $f['color'] ?>,transparent)"></div>

                    <div class="bento-icon" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>">
                        <i class="lucide lucide-<?= $f['icon'] ?> text-[22px] relative z-10"></i>
                    </div>

                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] mt-5" style="color:<?= $f['color'] ?>"><?= $e($f['category']) ?></div>
                    <h3 class="font-display font-extrabold text-[20px] mt-1.5 tracking-[-0.02em]"><?= $e($f['title']) ?></h3>
                    <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed line-clamp-2"><?= $e($f['tagline']) ?></p>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <?php foreach (array_slice($f['hero_kpis'], 0, 3) as [$lbl, $val]): ?>
                            <div>
                                <div class="font-display font-bold text-[13px]" style="color:<?= $f['color'] ?>"><?= $e($val) ?></div>
                                <div class="text-[10px] text-ink-400 mt-0.5"><?= $e($lbl) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-5 inline-flex items-center gap-1 text-[12.5px] font-semibold transition" style="color:<?= $f['color'] ?>">Ver detalle <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-1 transition"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- COMPARISON -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">¿POR QUÉ KYDESK?</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">No es <span class="gradient-shift">otro helpdesk</span>.</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 max-w-5xl mx-auto">
            <?php foreach ([
                ['zap','Velocidad obsesiva','Cargas instantáneas, atajos en todas partes, navegación con teclado. Tu equipo no espera.','#7c5cff','#f3f0ff'],
                ['shield-check','Multi-tenant nativo','Aísla cada cliente con su workspace, branding y datos. Perfecto para MSPs.','#16a34a','#d1fae5'],
                ['palette','100% personalizable','Color, densidad, sidebar, dashboard widgets, idioma — cada técnico hace su panel.','#ec4899','#fce7f3'],
            ] as [$ic,$t,$d,$col,$bg]): ?>
                <div class="bento spotlight-card text-center" style="padding:32px">
                    <div class="bento-glow"></div>
                    <div class="w-14 h-14 rounded-2xl grid place-items-center mx-auto" style="background:<?= $bg ?>;color:<?= $col ?>;box-shadow:0 8px 20px -6px <?= $col ?>40"><i class="lucide lucide-<?= $ic ?> text-[26px]"></i></div>
                    <h3 class="font-display font-extrabold text-[20px] mt-5 tracking-[-0.02em]"><?= $t ?></h3>
                    <p class="text-[13.5px] text-ink-500 mt-3 leading-relaxed"><?= $d ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="hero-card text-center glow-purple" style="padding:72px 48px;border-radius:32px;">
            <div class="hero-stars" style="top:24px;right:24px;transform:none;opacity:.45"><svg viewBox="0 0 280 200"><path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/><path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/></svg></div>
            <div class="relative max-w-2xl mx-auto">
                <h2 class="display-xl text-white" style="font-size:clamp(2rem,3.5vw + 1rem,3.4rem);text-wrap:balance">Probalo en vivo.</h2>
                <p class="mt-5 text-[16px] text-white/85">Workspace pre-cargado · Sin tarjeta · Se borra en 24h</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= $url('/demo') ?>" class="btn btn-lg" style="background:white;color:#16151b"><i class="lucide lucide-play"></i> Probar demo</a>
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
