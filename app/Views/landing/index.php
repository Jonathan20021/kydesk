<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<!-- ========== HERO ========== -->
<section class="relative pt-36 pb-24 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grid-bg"></div>
    <div class="noise-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="max-w-3xl mx-auto text-center reveal-stagger" data-reveal>
            <div class="inline-flex justify-center">
                <div class="aura-pill">
                    <span class="aura-pill-tag"><i class="lucide lucide-sparkles"></i> NUEVO</span>
                    <span class="text-ink-700 font-medium">Tablero Kanban + Automatizaciones IA</span>
                    <i class="lucide lucide-arrow-right text-[12px] text-ink-400"></i>
                </div>
            </div>

            <h1 class="display-xl mt-8" style="text-wrap:balance">
                El helpdesk para equipos<br>
                <span class="gradient-shift">que resuelven en serio.</span>
            </h1>

            <p class="mt-7 text-[19px] max-w-xl mx-auto leading-relaxed text-ink-500">Tickets, SLAs, escalamientos, automatizaciones, base de conocimiento y activos. Una plataforma multi-tenant que se siente <span class="text-ink-900 font-semibold">rápida</span>.</p>

            <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?= $url('/demo') ?>" class="btn btn-lg glow-purple" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white"><i class="lucide lucide-play"></i> Probar demo · 24h gratis</a>
                <a href="<?= $url('/auth/register') ?>" class="btn btn-outline btn-lg">Crear cuenta <i class="lucide lucide-arrow-right"></i></a>
            </div>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-6 text-[12.5px] text-ink-400">
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> 14 días gratis</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> Sin tarjeta</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> Setup 5 minutos</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> SOC 2 Tipo II</span>
            </div>
        </div>

        <!-- HERO MOCKUP con chips superpuestos en las esquinas (mitad sobre el frame, mitad fuera) -->
        <div class="mt-20 relative max-w-[1180px] mx-auto reveal" data-reveal>

            <div class="float-chip delay-1 hidden xl:flex absolute z-30" style="top:84px;left:-32px;">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 grid place-items-center"><i class="lucide lucide-check-circle-2"></i></div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold text-ink-400">SLA cumplido</div>
                    <div class="font-display font-bold text-[13px]">+98.7%</div>
                </div>
            </div>
            <div class="float-chip delay-2 hidden xl:flex absolute z-30" style="top:38%;right:-32px;">
                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-zap"></i></div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold text-ink-400">Resolución</div>
                    <div class="font-display font-bold text-[13px]">2.4× más rápido</div>
                </div>
            </div>
            <div class="float-chip delay-3 hidden xl:flex absolute z-30" style="bottom:72px;left:-28px;">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 grid place-items-center"><i class="lucide lucide-bell-ring"></i></div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold text-ink-400">Nueva alerta</div>
                    <div class="font-display font-bold text-[13px]">VPN desconectada</div>
                </div>
            </div>

            <div class="mock-frame">
                <div class="flex items-center gap-1.5 px-3 py-2.5">
                    <span class="w-3 h-3 rounded-full bg-[#ff5f57]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#febc2e]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#28c840]"></span>
                    <div class="flex-1 text-center text-[11px] font-mono text-ink-400 flex items-center justify-center gap-1.5"><i class="lucide lucide-lock text-[10px]"></i> kydesk.kyrosrd.com / acme</div>
                </div>
                <div class="rounded-[20px] overflow-hidden border border-[#ececef]" style="background:#f3f4f6">
                    <div class="grid grid-cols-12">
                        <div class="col-span-3 bg-white border-r border-[#ececef] p-4">
                            <div class="flex items-center gap-2 mb-4 px-2">
                                <div class="w-8 h-8 rounded-lg text-white grid place-items-center font-display font-bold text-sm" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">A</div>
                                <div>
                                    <div class="font-display font-bold text-[12.5px]">Acme Corp</div>
                                    <div class="text-[9.5px] text-ink-400 uppercase tracking-wider">Plan Pro</div>
                                </div>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink-400 px-2 mb-2">General</div>
                            <div class="space-y-0.5">
                                <?php foreach ([['layout-dashboard','Dashboard',true,null],['inbox','Tickets',false,'12'],['kanban-square','Tablero',false,null],['line-chart','Reportes',false,null]] as [$ic,$lbl,$on,$cnt]): ?>
                                    <div class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[12.5px] <?= $on?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500 font-medium' ?>">
                                        <i class="lucide lucide-<?= $ic ?> text-[14px]"></i><span class="flex-1"><?= $lbl ?></span>
                                        <?php if ($cnt): ?><span class="bg-brand-500 text-white px-1.5 rounded-full text-[9.5px] font-semibold"><?= $cnt ?></span><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink-400 px-2 mt-4 mb-2">Gestión</div>
                            <div class="space-y-0.5">
                                <?php foreach ([['building-2','Empresas'],['server','Activos'],['book-open','Conocimiento']] as [$ic,$lbl]): ?>
                                    <div class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[12.5px] text-ink-500 font-medium">
                                        <i class="lucide lucide-<?= $ic ?> text-[14px]"></i><?= $lbl ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-span-6 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="flex-1 h-9 rounded-full bg-white border border-[#ececef] flex items-center px-4 gap-2">
                                    <i class="lucide lucide-search text-ink-400 text-sm"></i>
                                    <span class="text-[11px] text-ink-400">Buscar tickets…</span>
                                </div>
                                <div class="w-9 h-9 rounded-full bg-white border border-[#ececef] grid place-items-center"><i class="lucide lucide-bell text-sm text-ink-700"></i></div>
                                <div class="w-9 h-9 rounded-full text-white grid place-items-center text-[10px] font-bold" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">AD</div>
                            </div>
                            <div class="hero-card relative" style="padding: 22px 26px; border-radius: 18px;">
                                <div class="hero-stars" style="opacity:.3;"><svg viewBox="0 0 280 200"><path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/><path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/><path d="M220 130 L223 145 L238 148 L223 151 L220 166 L217 151 L202 148 L217 145 Z" fill="white"/></svg></div>
                                <div class="hero-tag">Soporte profesional</div>
                                <div class="hero-title" style="font-size:22px; margin-top:6px;">Resuelve más,<br>más rápido</div>
                                <div class="hero-cta" style="font-size:11.5px; padding:4px 6px 4px 14px; margin-top:14px;">
                                    Nuevo ticket
                                    <span class="hero-cta-arrow" style="width:22px;height:22px"><i class="lucide lucide-arrow-right text-[11px]"></i></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2.5 mt-3">
                                <?php foreach ([['Abiertos','24/120','#f3e8ff','#7e22ce','grid-2x2'],['En progreso','12/120','#fef3c7','#b45309','refresh-cw'],['Resueltos','189/220','#d1fae5','#047857','book-open']] as [$l,$v,$bg,$col,$ic]): ?>
                                <div class="bg-white border border-[#ececef] rounded-2xl p-3 flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-sm"></i></div>
                                    <div>
                                        <div class="text-[9.5px] text-ink-400"><?= $v ?></div>
                                        <div class="font-display font-bold text-[12px]"><?= $l ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex items-center justify-between mt-4 mb-2.5">
                                <div class="font-display font-bold text-[14px]">Tickets recientes</div>
                                <div class="flex gap-1.5">
                                    <div class="w-6 h-6 rounded-full bg-white border border-[#ececef] grid place-items-center text-ink-500"><i class="lucide lucide-chevron-left text-[11px]"></i></div>
                                    <div class="w-6 h-6 rounded-full bg-brand-500 text-white grid place-items-center"><i class="lucide lucide-chevron-right text-[11px]"></i></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <?php
                                $cards = [
                                    ['HARDWARE', 'Impresora 3er piso offline', 'María T.', 'MT', '#7c5cff', '#fef3c7'],
                                    ['REDES', 'VPN se desconecta cada 10 min', 'Carlos I.', 'CI', '#f59e0b', '#dbeafe'],
                                    ['SOFTWARE', 'Error 500 reporte mensual', 'Juan S.', 'JS', '#ec4899', '#fce7f3'],
                                ];
                                foreach ($cards as [$tag,$t,$n,$in,$col,$bg]): ?>
                                <div class="bg-white border border-[#ececef] rounded-2xl overflow-hidden">
                                    <div class="h-16 grid place-items-center relative" style="background: linear-gradient(135deg,<?= $bg ?>,#fff);">
                                        <span class="absolute top-1.5 left-1.5 text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full bg-white/85 text-brand-700"><?= $tag ?></span>
                                    </div>
                                    <div class="p-2.5">
                                        <div class="text-[10.5px] font-display font-bold leading-tight line-clamp-2"><?= $t ?></div>
                                        <div class="h-1 rounded-full bg-[#f3f4f6] mt-2 overflow-hidden"><div class="h-full bg-brand-500" style="width:<?= rand(30,80) ?>%"></div></div>
                                        <div class="flex items-center gap-1.5 mt-2">
                                            <div class="w-4 h-4 rounded-full grid place-items-center text-white text-[7px] font-bold" style="background:<?= $col ?>"><?= $in ?></div>
                                            <div class="text-[9px] text-ink-400 font-medium"><?= $n ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-span-3 bg-white border-l border-[#ececef] p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="font-display font-bold text-[13px]">Estadística</div>
                                <i class="lucide lucide-more-vertical text-ink-400 text-sm"></i>
                            </div>
                            <div class="relative w-[140px] h-[140px] mx-auto">
                                <svg viewBox="0 0 140 140" class="absolute inset-0">
                                    <circle cx="70" cy="70" r="62" fill="none" stroke="#e7e0ff" stroke-width="10"/>
                                    <circle cx="70" cy="70" r="62" fill="none" stroke="url(#donut-grad)" stroke-width="10" stroke-linecap="round" stroke-dasharray="320 390" transform="rotate(-90 70 70)"/>
                                    <defs><linearGradient id="donut-grad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#7c5cff"/><stop offset="100%" stop-color="#d946ef"/></linearGradient></defs>
                                </svg>
                                <div class="absolute inset-3 rounded-full bg-white grid place-items-center text-center">
                                    <div>
                                        <div class="font-display font-extrabold text-[28px] tracking-[-0.025em] text-brand-700 leading-none">82%</div>
                                        <div class="text-[8.5px] uppercase tracking-[0.12em] font-bold text-ink-400 mt-1">SLA</div>
                                    </div>
                                </div>
                            </div>
                            <div class="font-display font-bold text-[13px] text-center mt-3">Excelente trabajo 🔥</div>
                            <div class="flex items-end gap-3 h-[72px] mt-4 px-1 pb-2 border-b border-[#ececef]">
                                <div class="flex-1 rounded-t-md bg-brand-200" style="height:50%"></div>
                                <div class="flex-1 rounded-t-md bg-brand-500" style="height:90%"></div>
                                <div class="flex-1 rounded-t-md bg-brand-200" style="height:35%"></div>
                                <div class="flex-1 rounded-t-md bg-brand-200" style="height:65%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-4 mb-2">
                                <div class="font-display font-bold text-[13px]">Tu equipo</div>
                                <div class="w-6 h-6 rounded-full bg-brand-50 grid place-items-center text-brand-700"><i class="lucide lucide-plus text-[11px]"></i></div>
                            </div>
                            <?php foreach ([['María T.','MT','#ec4899'],['Carlos I.','CI','#f59e0b']] as [$n,$in,$c]): ?>
                                <div class="flex items-center gap-2 py-1.5">
                                    <div class="w-7 h-7 rounded-full grid place-items-center text-white text-[9px] font-bold" style="background:<?= $c ?>"><?= $in ?></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-display font-bold text-[10.5px] truncate"><?= $n ?></div>
                                        <div class="text-[8.5px] text-ink-400">Técnico</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== TRUSTED LOGOS — MARQUEE ========== -->
<section id="customers" class="py-14 border-y border-[#ececef] bg-white relative">
    <div class="max-w-[1240px] mx-auto px-6 mb-8">
        <p class="text-center text-[11.5px] font-bold uppercase tracking-[0.18em] text-ink-400">Equipos de soporte que confían en Kydesk</p>
    </div>
    <div class="marquee">
        <div class="marquee-track">
            <?php
            $logos = ['Acme Corp','Globex','Initech','Umbrella','Stark Ind','Wayne Ent','Cyberdyne','Tyrell','Massive Dyn','Soylent'];
            for ($i = 0; $i < 2; $i++):
                foreach ($logos as $n): ?>
                    <div class="font-display font-extrabold text-[24px] text-ink-400 tracking-[-0.02em] whitespace-nowrap"><?= $n ?></div>
            <?php endforeach; endfor; ?>
        </div>
    </div>
</section>

<!-- ========== FEATURE BLOCKS ========== -->
<section id="features" class="py-32 relative">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">PLATAFORMA</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance">Todo lo que tu equipo<br>necesita en <span class="gradient-shift">una plataforma</span>.</h2>
            <p class="mt-6 text-[16px] text-ink-500 max-w-lg mx-auto">Diseñado desde cero para soporte moderno. Sin complejidad, sin fricción.</p>
        </div>

        <div class="mt-16 grid grid-cols-12 gap-4 reveal-stagger" data-reveal>
            <div class="col-span-12 lg:col-span-7 bento spotlight-card" style="min-height:400px;">
                <div class="bento-glow"></div>
                <span class="badge badge-purple"><i class="lucide lucide-inbox text-[11px]"></i> BANDEJA UNIFICADA</span>
                <h3 class="font-display font-extrabold text-[28px] mt-4 tracking-[-0.025em]">Una bandeja para todo.</h3>
                <p class="text-[14.5px] text-ink-500 mt-2 max-w-md leading-relaxed">Portal, email, teléfono, chat e interno convergen en una sola vista priorizada por SLA.</p>
                <div class="mt-7 space-y-2.5">
                    <?php foreach ([['globe','Portal · Acme','VPN se desconecta cada 10 min','#ef4444','Urgente'],['mail','Email · Globex','No recibe correo corporativo','#f59e0b','Alta'],['phone','Teléfono · Initech','Impresora 3er piso offline','#7c5cff','Media']] as [$ic,$ch,$s,$c,$pl]): ?>
                        <div class="ticker-row">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#ececef] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[10.5px] text-ink-400 mb-0.5"><?= $ch ?></div>
                                <div class="text-[13px] font-semibold truncate"><?= $s ?></div>
                            </div>
                            <span class="status-pill" style="background:<?= $c ?>1f;color:<?= $c ?>"><?= $pl ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= $url('/features/inbox') ?>" class="inline-flex items-center gap-1 mt-6 text-[13px] font-semibold text-brand-700 hover:gap-2 transition-all">Saber más sobre Bandeja unificada <i class="lucide lucide-arrow-right text-[13px]"></i></a>
            </div>

            <div class="col-span-12 lg:col-span-5 bento spotlight-card" style="min-height:400px;">
                <div class="bento-glow"></div>
                <span class="badge badge-amber"><i class="lucide lucide-gauge text-[11px]"></i> SLA EN VIVO</span>
                <h3 class="font-display font-extrabold text-[28px] mt-4 tracking-[-0.025em]">Reloj SLA que late.</h3>
                <p class="text-[14.5px] text-ink-500 mt-2 leading-relaxed">Políticas por prioridad. Alertas antes de la brecha.</p>
                <div class="mt-7 space-y-3.5">
                    <?php foreach ([['Urgente','15m',88,'#ef4444'],['Alta','1h 30m',64,'#f59e0b'],['Media','4h 12m',38,'#7c5cff']] as [$l,$t,$p,$c]): ?>
                        <div class="border border-[#ececef] rounded-2xl p-4 hover:border-brand-200 transition">
                            <div class="flex items-center justify-between text-[12.5px] mb-2.5">
                                <span class="font-display font-bold flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= $c ?>"></span><?= $l ?></span>
                                <span class="font-mono text-ink-500 text-[12px] font-semibold"><?= $t ?></span>
                            </div>
                            <div class="progress"><div class="progress-bar" style="width:<?= $p ?>%; background:<?= $c ?>"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= $url('/features/sla') ?>" class="inline-flex items-center gap-1 mt-6 text-[13px] font-semibold text-amber-700 hover:gap-2 transition-all">Saber más sobre SLA <i class="lucide lucide-arrow-right text-[13px]"></i></a>
            </div>

            <?php
            $bentos = [
                ['kanban','Tablero Kanban','Drag & drop. Tu flujo, tu forma.','kanban-square','#dbeafe','#1d4ed8'],
                ['automations','Automatizaciones IA','Reglas que ejecutan acciones solas.','workflow','#f3e8ff','#7e22ce'],
                ['analytics','Analítica profunda','Métricas que mueven decisiones.','line-chart','#d1fae5','#047857'],
                ['kb','Conocimiento','Artículos públicos e internos.','book-open','#fef3c7','#b45309'],
                ['multitenant','Multi-tenant','Aísla cada organización.','building-2','#fce7f3','#be185d'],
                ['roles','Roles 30+','Permisos granulares por módulo.','shield','#fee2e2','#b91c1c'],
            ];
            foreach ($bentos as [$key,$t,$d,$ic,$bg,$col]): ?>
                <a href="<?= $url('/features/' . $key) ?>" class="col-span-12 sm:col-span-6 lg:col-span-4 bento spotlight-card group block">
                    <div class="bento-glow"></div>
                    <div class="bento-icon" style="background:<?= $bg ?>;color:<?= $col ?>">
                        <i class="lucide lucide-<?= $ic ?> text-[22px] relative z-10"></i>
                    </div>
                    <h3 class="font-display font-extrabold text-[20px] mt-5 tracking-[-0.02em]"><?= $t ?></h3>
                    <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed"><?= $d ?></p>
                    <span class="inline-flex items-center gap-1 mt-4 text-[12.5px] font-semibold transition" style="color:<?= $col ?>">Saber más <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-1 transition"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== STATS CON COUNT-UP ========== -->
<section class="py-24 bg-ink-900 text-white relative overflow-hidden">
    <div class="absolute inset-0">
        <div class="aurora-blob" style="width:600px;height:600px;background:radial-gradient(circle,rgba(124,92,255,0.5),transparent 70%);top:-200px;left:10%;mix-blend-mode:screen;opacity:.6;animation:aurora-1 22s ease-in-out infinite"></div>
        <div class="aurora-blob" style="width:500px;height:500px;background:radial-gradient(circle,rgba(217,70,239,0.4),transparent 70%);bottom:-200px;right:10%;mix-blend-mode:screen;opacity:.6;animation:aurora-2 26s ease-in-out infinite"></div>
    </div>
    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-300 mb-3">RESULTADOS REALES</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3.5rem)">Equipos que ya operan al máximo.</h2>
        </div>
        <div class="mt-16 grid grid-cols-2 lg:grid-cols-4 gap-8 reveal-stagger" data-reveal>
            <?php foreach ([['42','%','menos tiempo de respuesta','clock'],['3.8','×','productividad por técnico','zap'],['99.99','%','uptime del servicio','activity'],['12','K+','tickets/mes resueltos','inbox']] as [$n,$suffix,$l,$ic]): ?>
                <div class="border-t border-white/15 pt-7">
                    <i class="lucide lucide-<?= $ic ?> text-[18px] text-brand-300"></i>
                    <div class="mt-5 flex items-baseline gap-1">
                        <span class="font-display font-extrabold text-[64px] tracking-[-0.04em] leading-none gradient-shift" data-counter="<?= $n ?>"><?= $n ?></span>
                        <span class="font-display font-extrabold text-[28px] text-white/70"><?= $suffix ?></span>
                    </div>
                    <div class="mt-3 text-[13.5px] text-white/70 max-w-[200px] leading-snug"><?= $l ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== TESTIMONIALS ========== -->
<section id="testimonials" class="py-32">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">TESTIMONIOS</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance">Aman <span class="gradient-shift">cómo se siente</span>.</h2>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-5 reveal-stagger" data-reveal>
            <?php foreach ([
                ['Reducimos el tiempo de primera respuesta de 6h a 38min en el primer mes. Sencillamente revolucionario.','Laura Méndez','CTO · Globex','LM','#ec4899'],
                ['Por fin un helpdesk que mi equipo abre sin gemir. La velocidad y los atajos son adictivos.','Carlos Rivera','Lead de Soporte · Acme','CR','#7c5cff'],
                ['Las automatizaciones cierran solas el 30% de los tickets. Me devolvió tiempo a mí y a mis técnicos.','Ana Torres','Ops Manager · Initech','AT','#f59e0b'],
            ] as [$q,$n,$r,$in,$c]): ?>
                <div class="testi spotlight-card">
                    <div class="testi-quote">"</div>
                    <div class="flex items-center gap-1 mb-4">
                        <?php for ($s=0;$s<5;$s++): ?><i class="lucide lucide-star text-amber-400 text-[14px]" style="fill:#f59e0b"></i><?php endfor; ?>
                    </div>
                    <p class="text-[14.5px] leading-relaxed text-ink-700">"<?= $q ?>"</p>
                    <div class="flex items-center gap-3 mt-6 pt-5 border-t border-[#ececef]">
                        <div class="avatar avatar-md" style="background:<?= $c ?>;color:white"><?= $in ?></div>
                        <div>
                            <div class="font-display font-bold text-[13.5px]"><?= $n ?></div>
                            <div class="text-[11.5px] text-ink-400"><?= $r ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== PRICING ========== -->
<section id="pricing-preview" class="py-24 relative">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">PRECIOS</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance">Empezá <span class="gradient-shift">gratis</span>. Crecé sin sorpresas.</h2>
        </div>

        <?php
        $homePlans = $plans ?? [];
        $homeFeatureLabels = [
            'tickets' => 'Tickets',
            'kb' => 'Base de conocimiento',
            'notes' => 'Notas',
            'todos' => 'Tareas',
            'companies' => 'Empresas',
            'assets' => 'Activos',
            'reports' => 'Reportes',
            'users' => 'Usuarios',
            'roles' => 'Roles',
            'settings' => 'Ajustes',
            'automations' => 'Automatizaciones IA',
            'sla' => 'SLA + Escalamientos',
            'audit' => 'Auditoría',
            'sso' => 'SSO + SAML',
            'custom_branding' => 'Marca personalizada',
        ];
        $homeCols = max(2, min(4, count($homePlans) ?: 3));
        ?>
        <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?= $homeCols ?> gap-5 max-w-6xl mx-auto reveal-stagger" data-reveal>
            <?php foreach ($homePlans as $hp):
                $isFeat = (int)$hp['is_featured'] === 1;
                $isFree = (float)$hp['price_monthly'] === 0.0;
                $features = json_decode($hp['features'] ?? '[]', true) ?: [];
                $highlightFeatures = array_slice($features, 0, 6);
                $feats = [];
                if ((int)$hp['max_users'] >= 9999) $feats[] = 'Técnicos ilimitados';
                else $feats[] = 'Hasta ' . (int)$hp['max_users'] . ' técnicos';
                if ((int)$hp['max_tickets_month'] >= 99999) $feats[] = 'Tickets ilimitados';
                else $feats[] = number_format($hp['max_tickets_month']) . ' tickets/mes';
                foreach ($highlightFeatures as $hf) $feats[] = $homeFeatureLabels[$hf] ?? ucfirst($hf);
                $feats = array_slice($feats, 0, 6);
            ?>
                <div class="price-card <?= $isFeat ? 'featured' : '' ?>">
                    <?php if ($isFeat): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="aura-pill-tag">RECOMENDADO</span></div>
                    <?php endif; ?>
                    <div class="text-[11px] uppercase tracking-[0.16em] font-bold <?= $isFeat ? 'text-brand-300 relative' : 'text-ink-400' ?>"><?= $e($hp['name']) ?></div>
                    <div class="mt-3 <?= $isFeat ? 'relative' : '' ?>">
                        <span class="price-amount <?= $isFeat ? 'gradient-shift' : '' ?>">$<?= number_format($hp['price_monthly'], 0) ?></span>
                        <span class="<?= $isFeat ? 'text-white/60' : 'text-ink-400' ?> text-[14px] ml-2">/ mes</span>
                    </div>
                    <?php if (!empty($hp['description'])): ?>
                        <p class="text-[13px] mt-3 <?= $isFeat ? 'text-white/70 relative' : 'text-ink-500' ?>"><?= $e($hp['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($isFeat): ?>
                        <a href="<?= $url('/auth/register') ?>" class="btn btn-lg w-full mt-6 justify-center relative" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 10px 24px -8px rgba(124,92,255,.6)"><?= (int)$hp['trial_days'] > 0 ? 'Probar ' . (int)$hp['trial_days'] . ' días gratis' : 'Empezar ' . $e($hp['name']) ?></a>
                    <?php else: ?>
                        <a href="<?= $url($isFree ? '/auth/register' : '/pricing') ?>" class="btn btn-outline btn-sm w-full mt-6 justify-center"><?= $isFree ? 'Empezar gratis' : 'Probar ' . $e($hp['name']) ?></a>
                    <?php endif; ?>
                    <div class="mt-6 pt-6 <?= $isFeat ? 'border-t border-white/10 relative' : 'border-t border-[#ececef]' ?> space-y-1">
                        <?php foreach ($feats as $f): ?>
                            <div class="price-feat <?= $isFeat ? 'text-white/85' : '' ?>"><span class="price-feat-check" <?= $isFeat ? 'style="background:rgba(124,92,255,.2);color:#a78bfa"' : '' ?>><i class="lucide lucide-check text-[12px]"></i></span><?= $e($f) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($homePlans)): ?>
                <div class="md:col-span-3 text-center py-12 text-ink-400">No hay planes activos. Configurá planes desde el panel super admin.</div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-10">
            <a href="<?= $url('/pricing') ?>" class="text-[13px] font-semibold text-brand-700 inline-flex items-center gap-1.5 hover:gap-2 transition-all">Ver detalle completo de planes <i class="lucide lucide-arrow-right text-[14px]"></i></a>
        </div>
    </div>
</section>

<!-- ========== FAQ ========== -->
<section class="py-24">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-14 reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">FAQ</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3vw + 1rem,3rem)">Preguntas frecuentes</h2>
        </div>
        <div class="reveal" data-reveal x-data="{open:0}">
            <?php $faqs = [
                ['¿Necesito tarjeta de crédito para empezar?','No. Tu prueba de 14 días es completamente gratis y sin tarjeta. Te avisamos antes de que termine.'],
                ['¿Puedo migrar tickets desde otra herramienta?','Sí. Importamos desde Zendesk, Freshdesk, Atera, ServiceNow y CSV. Nuestro equipo te asiste sin costo en el plan Pro.'],
                ['¿Qué pasa si supero los técnicos del plan?','Nada se rompe. Te notificamos para sumarlos al siguiente ciclo. Sin sorpresas en la factura.'],
                ['¿Cómo funciona la seguridad multi-tenant?','Cada organización vive en su propio espacio aislado a nivel de datos. Roles granulares por módulo y auditoría completa.'],
                ['¿Tienen API y webhooks?','Sí. API REST documentada + webhooks para integrar con Slack, Teams, Jira y lo que necesites.'],
            ]; foreach ($faqs as $i => [$q,$a]): ?>
                <div class="faq-item" :class="open===<?= $i ?> ? 'open' : ''" @click="open = open===<?= $i ?> ? -1 : <?= $i ?>">
                    <div class="faq-q"><?= $q ?><div class="faq-icon"><i class="lucide lucide-plus text-[16px]"></i></div></div>
                    <div class="faq-a"><?= $a ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== CTA FINAL ========== -->
<section class="py-24">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="hero-card text-center reveal glow-purple" data-reveal style="padding:96px 48px;border-radius:36px;">
            <div class="hero-stars" style="top:24px;right:24px;transform:none;opacity:.45"><svg viewBox="0 0 280 200"><path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/><path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/><path d="M220 130 L223 145 L238 148 L223 151 L220 166 L217 151 L202 148 L217 145 Z" fill="white"/><path d="M40 30 L42 38 L50 40 L42 42 L40 50 L38 42 L30 40 L38 38 Z" fill="white"/></svg></div>
            <div class="relative max-w-2xl mx-auto">
                <div class="aura-pill mx-auto" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.25)"><span class="text-white/90 font-medium">Únete a 12K+ equipos</span></div>
                <h2 class="display-xl text-white mt-7" style="font-size:clamp(2.4rem,4.5vw + 1rem,4.5rem);text-wrap:balance">Dale a tu equipo<br>la herramienta correcta.</h2>
                <p class="mt-7 text-[18px] text-white/85 max-w-md mx-auto">En 5 minutos tu organización está operando.</p>
                <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-lg" style="background:white;color:#16151b">Empezar gratis <i class="lucide lucide-arrow-right"></i></a>
                    <a href="<?= $url('/contact') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)">Hablar con ventas</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.gsap && window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
        document.querySelectorAll('[data-reveal]').forEach(el => {
            ScrollTrigger.create({
                trigger: el, start: 'top 85%', once: true,
                onEnter: () => el.classList.add('in')
            });
        });

        // Counter animation
        document.querySelectorAll('[data-counter]').forEach(el => {
            const target = parseFloat(el.dataset.counter);
            const decimals = (el.dataset.counter.split('.')[1] || '').length;
            ScrollTrigger.create({
                trigger: el, start: 'top 90%', once: true,
                onEnter: () => {
                    const obj = { v: 0 };
                    gsap.to(obj, { v: target, duration: 2, ease: 'power2.out',
                        onUpdate: () => { el.textContent = obj.v.toFixed(decimals); }
                    });
                }
            });
        });

        // Parallax on hero mockup
        gsap.to('.mock-frame', {
            yPercent: -8,
            ease: 'none',
            scrollTrigger: { trigger: '.mock-frame', start: 'top top', end: 'bottom top', scrub: 1 }
        });
    } else {
        // Fallback if GSAP fails
        document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('in'));
    }

    // Spotlight cursor follow
    document.querySelectorAll('.spotlight-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            card.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    });
});
</script>
