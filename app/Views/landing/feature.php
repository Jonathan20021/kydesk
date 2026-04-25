<?php
$f = $feature;
$siblings = array_filter($allFeatures, fn($k) => $k !== $featureKey, ARRAY_FILTER_USE_KEY);
include APP_PATH . '/Views/partials/landing_nav.php';
?>

<!-- HERO -->
<section class="relative pt-36 pb-16 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1" style="background:radial-gradient(circle,<?= $f['color'] ?>aa,transparent 70%)"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grid-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <a href="<?= $url('/') ?>#features" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition mb-6"><i class="lucide lucide-arrow-left text-[13px]"></i> Todas las funcionalidades</a>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em]" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>;border:1px solid <?= $f['color'] ?>30">
                    <i class="lucide lucide-<?= $f['icon'] ?> text-[12px]"></i> <?= $e($f['category']) ?>
                </div>

                <h1 class="display-xl mt-7" style="text-wrap:balance;font-size:clamp(2.4rem,4.5vw + 1rem,4.5rem)">
                    <?= $e($f['title']) ?>
                </h1>

                <p class="mt-6 text-[18px] text-ink-500 max-w-xl leading-relaxed"><?= $e($f['tagline']) ?></p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="<?= $url('/demo') ?>" class="btn btn-lg glow-purple" style="background:linear-gradient(135deg,<?= $f['color'] ?>,<?= $f['color'] ?>cc);color:white"><i class="lucide lucide-play"></i> Probar en demo</a>
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-outline btn-lg">Crear cuenta gratis <i class="lucide lucide-arrow-right"></i></a>
                </div>

                <div class="mt-10 grid grid-cols-3 gap-4 max-w-lg">
                    <?php foreach ($f['hero_kpis'] as [$lbl, $val]): ?>
                        <div class="border-t-2 pt-3" style="border-color:<?= $f['color'] ?>">
                            <div class="font-display font-extrabold text-[24px] tracking-[-0.025em]" style="color:<?= $f['color'] ?>"><?= $e($val) ?></div>
                            <div class="text-[11px] text-ink-500 mt-1"><?= $e($lbl) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="relative rounded-[28px] p-8 overflow-hidden" style="background:linear-gradient(135deg,<?= $f['bg'] ?>,white);border:1px solid <?= $f['color'] ?>30;box-shadow:0 30px 60px -20px <?= $f['color'] ?>40">
                    <div class="absolute -top-8 -right-8 w-48 h-48 rounded-full" style="background:radial-gradient(circle,<?= $f['color'] ?>30,transparent 70%);filter:blur(20px)"></div>
                    <div class="relative">
                        <div class="w-20 h-20 rounded-3xl grid place-items-center mb-6" style="background:linear-gradient(135deg,<?= $f['color'] ?>,<?= $f['color'] ?>cc);color:white;box-shadow:0 16px 40px -10px <?= $f['color'] ?>80">
                            <i class="lucide lucide-<?= $f['icon'] ?> text-[36px]"></i>
                        </div>
                        <p class="text-[14.5px] leading-relaxed text-ink-700"><?= $e($f['description']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-24 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">CÓMO FUNCIONA</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">3 pasos · Setup en minutos</h2>
        </div>

        <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-5 relative">
            <div class="hidden md:block absolute top-12 left-[16.66%] right-[16.66%] h-px" style="background:linear-gradient(90deg,transparent,<?= $f['color'] ?>40,<?= $f['color'] ?>40,transparent);z-index:0"></div>
            <?php foreach ($f['steps'] as $i => [$title, $desc, $ic]): ?>
                <div class="relative bg-white">
                    <div class="w-12 h-12 rounded-2xl mx-auto grid place-items-center relative z-10" style="background:white;color:<?= $f['color'] ?>;border:2px solid <?= $f['color'] ?>;box-shadow:0 8px 16px -4px <?= $f['color'] ?>40">
                        <i class="lucide lucide-<?= $ic ?> text-[20px]"></i>
                    </div>
                    <div class="mt-3 text-center text-[10.5px] font-bold tracking-[0.16em] uppercase" style="color:<?= $f['color'] ?>">Paso <?= $i+1 ?></div>
                    <h3 class="mt-2 font-display font-extrabold text-[18px] text-center tracking-[-0.015em]"><?= $e($title) ?></h3>
                    <p class="mt-2 text-[13.5px] text-ink-500 text-center leading-relaxed max-w-xs mx-auto"><?= $e($desc) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section class="py-24" style="background:linear-gradient(180deg,#fafafb,white)">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">BENEFICIOS</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Lo que tu equipo gana</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($f['benefits'] as [$ic, $title, $desc]): ?>
                <div class="bento spotlight-card" style="border-color:<?= $f['color'] ?>20">
                    <div class="bento-glow"></div>
                    <div class="bento-icon" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>">
                        <i class="lucide lucide-<?= $ic ?> text-[22px] relative z-10"></i>
                    </div>
                    <h3 class="font-display font-extrabold text-[17px] mt-5 tracking-[-0.015em]"><?= $e($title) ?></h3>
                    <p class="text-[13px] text-ink-500 mt-2 leading-relaxed"><?= $e($desc) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- MOCKUP showcase -->
<section class="py-24">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">EN ACCIÓN</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Así se ve dentro de Kydesk</h2>
        </div>

        <?php
        $mockType = $f['mockup'] ?? 'inbox';
        $accent = $f['color'];
        $accentBg = $f['bg'];
        ?>

        <div class="mock-frame max-w-[1100px] mx-auto">
            <div class="flex items-center gap-1.5 px-3 py-2.5">
                <span class="w-3 h-3 rounded-full bg-[#ff5f57]"></span>
                <span class="w-3 h-3 rounded-full bg-[#febc2e]"></span>
                <span class="w-3 h-3 rounded-full bg-[#28c840]"></span>
                <div class="flex-1 text-center text-[11px] font-mono text-ink-400 inline-flex items-center justify-center gap-1.5"><i class="lucide lucide-lock text-[10px]"></i> kydesk.kyrosrd.com / acme<?= $mockType !== 'inbox' ? '/' . $mockType : '' ?></div>
            </div>
            <div class="rounded-[20px] overflow-hidden border border-[#ececef] p-8" style="background:linear-gradient(135deg,<?= $accentBg ?>,#fafafb)">

                <?php if ($mockType === 'kanban'): ?>
                    <div class="grid grid-cols-4 gap-3">
                        <?php foreach ([['Abierto','#3b82f6',2],['En progreso',$accent,3],['En espera','#9ca3af',1],['Resuelto','#16a34a',2]] as [$col,$c,$cnt]): ?>
                            <div class="rounded-2xl p-3 bg-white border border-[#ececef]">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= $c ?>"></span><span class="font-display font-bold text-[12px]"><?= $col ?></span></div>
                                    <span class="text-[10px] font-mono text-ink-400"><?= $cnt ?></span>
                                </div>
                                <?php for ($i=0; $i<$cnt; $i++): ?>
                                    <div class="rounded-xl p-3 mb-2 bg-[#fafafb] border border-[#ececef]">
                                        <div class="text-[9px] font-mono text-ink-400 mb-1">TK-04-0000<?= $i+1 ?></div>
                                        <div class="font-display font-bold text-[11.5px] line-clamp-2"><?= ['VPN se desconecta','Impresora offline','Reset de contraseña','Nuevo equipo','Error 500','Acceso RRHH'][($i+$cnt) % 6] ?></div>
                                        <div class="mt-2 flex items-center gap-1.5">
                                            <div class="w-5 h-5 rounded-full text-white text-[8px] grid place-items-center font-bold" style="background:<?= $accent ?>">M</div>
                                            <span class="text-[9px] text-ink-400">hace 2h</span>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'sla'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([['Urgente','15 min','12 min','#ef4444',80],['Alta','1h','38 min','#f59e0b',63],['Media','4h','2h 12min','#7c5cff',45]] as [$lbl,$total,$rest,$c,$pct]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-display font-bold text-[13px] inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= $c ?>"></span><?= $lbl ?></span>
                                    <span class="font-mono text-[11px] text-ink-400"><?= $total ?></span>
                                </div>
                                <div class="font-display font-extrabold text-[24px] tracking-[-0.02em]" style="color:<?= $c ?>"><?= $rest ?></div>
                                <div class="text-[10.5px] text-ink-400 mb-2">tiempo restante</div>
                                <div class="h-2 bg-[#f3f4f6] rounded-full overflow-hidden"><div class="h-full" style="width:<?= $pct ?>%;background:<?= $c ?>"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'automations'): ?>
                    <div class="space-y-3 max-w-3xl mx-auto">
                        <?php foreach ([
                            ['Auto-asignar urgentes a N2','urgent → Marco Técnico','zap',true],
                            ['Notificar a Slack si SLA en riesgo','sla.threshold:80% → webhook','bell-ring',true],
                            ['Cerrar resueltos +7 días','resolved.age:7d → status:closed','clock',true],
                            ['Etiquetar tickets de Acme como VIP','company:acme → tag:vip','tag',false],
                        ] as [$name,$rule,$ic,$active]): ?>
                            <div class="rounded-2xl p-4 bg-white border border-[#ececef] flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $accentBg ?>;color:<?= $accent ?>"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-display font-bold text-[13.5px] truncate"><?= $name ?></div>
                                    <div class="text-[11px] font-mono text-ink-400 truncate"><?= $rule ?></div>
                                </div>
                                <span class="kswitch"><input type="checkbox" <?= $active?'checked':'' ?> disabled><span class="kswitch-track"></span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'analytics'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <?php foreach ([['Tasa resolución','94%','+4%','#16a34a'],['1ª respuesta','38min','-12min','#3b82f6'],['Cumplimiento SLA','98.7%','+2.1%',$accent]] as [$lbl,$val,$delta,$c]): ?>
                            <div class="rounded-2xl p-4 bg-white border border-[#ececef]">
                                <div class="text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400"><?= $lbl ?></div>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="font-display font-extrabold text-[26px] tracking-[-0.02em]" style="color:<?= $c ?>"><?= $val ?></span>
                                    <span class="text-[11px] font-bold text-emerald-600"><?= $delta ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                        <div class="font-display font-bold text-[13px] mb-3">Tickets por día (últimos 14)</div>
                        <div class="flex items-end gap-2 h-32">
                            <?php foreach ([35,42,38,55,48,62,58,71,65,52,68,75,82,78] as $i => $h): ?>
                                <div class="flex-1 rounded-t-md" style="height:<?= ($h/82)*100 ?>%;background:<?= $i % 3 === 0 ? $accent : $accentBg ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($mockType === 'kb'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([['Primeros pasos','rocket','#0ea5e9','12 artículos'],['Red & VPN','wifi','#10b981','8 artículos'],['Cuentas','lock','#ec4899','15 artículos']] as [$n,$ic,$c,$count]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="w-12 h-12 rounded-2xl text-white grid place-items-center" style="background:<?= $c ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
                                <div class="font-display font-bold text-[15px] mt-4"><?= $n ?></div>
                                <div class="text-[11.5px] text-ink-400 mt-1"><?= $count ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'multitenant'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([['Acme Corp','A','#7c5cff','enterprise'],['Globex Inc','G','#ec4899','premium'],['Initech','I','#f59e0b','premium']] as [$n,$letter,$c,$tier]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold" style="background:<?= $c ?>"><?= $letter ?></div>
                                    <div>
                                        <div class="font-display font-bold text-[13.5px]"><?= $n ?></div>
                                        <div class="text-[10px] text-ink-400 uppercase tracking-wider">/t/<?= strtolower(str_replace(' ','',$n)) ?></div>
                                    </div>
                                </div>
                                <div class="text-[11px] text-ink-500 inline-flex items-center gap-1"><i class="lucide lucide-shield text-[11px]"></i> Aislado · <?= $tier ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'roles'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([
                            ['Owner','crown','#b91c1c','Acceso total','30+'],
                            ['Supervisor','shield-check','#f59e0b','Tickets, equipo, SLA','22'],
                            ['Técnico','wrench',$accent,'Resuelve y comenta','14'],
                        ] as [$role,$ic,$c,$desc,$perms]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:<?= $c ?>"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded-full" style="background:<?= $accentBg ?>;color:<?= $c ?>"><?= $perms ?> permisos</span>
                                </div>
                                <div class="font-display font-bold text-[14px]"><?= $role ?></div>
                                <div class="text-[11px] text-ink-500 mt-1"><?= $desc ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: /* inbox default */ ?>
                    <div class="space-y-2 max-w-3xl mx-auto">
                        <?php foreach ([
                            ['globe','Portal · Acme','VPN se desconecta cada 10 min','#ef4444','Urgente'],
                            ['mail','Email · Globex','No recibe correo corporativo','#f59e0b','Alta'],
                            ['phone','Teléfono · Initech','Impresora 3er piso offline',$accent,'Media'],
                            ['message-circle','Chat · Stark','Dudas con Office 365','#9ca3af','Baja'],
                        ] as [$ic,$ch,$s,$c,$pl]): ?>
                            <div class="rounded-2xl p-3.5 bg-white border border-[#ececef] flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-[#fafafb] border border-[#ececef] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10.5px] text-ink-400 mb-0.5"><?= $ch ?></div>
                                    <div class="text-[13px] font-semibold truncate"><?= $s ?></div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" style="background:<?= $c ?>1f;color:<?= $c ?>"><?= $pl ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-20">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">DUDAS FRECUENTES</div>
            <h2 class="display-xl" style="font-size:clamp(1.8rem,2.8vw + 1rem,2.4rem)">Preguntas sobre <?= $e($f['title']) ?></h2>
        </div>
        <div x-data="{open:0}">
            <?php foreach ($f['faqs'] as $i => [$q, $a]): ?>
                <div class="faq-item" :class="open===<?= $i ?> ? 'open' : ''" @click="open = open===<?= $i ?> ? -1 : <?= $i ?>">
                    <div class="faq-q"><?= $e($q) ?><div class="faq-icon" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>"><i class="lucide lucide-plus text-[16px]"></i></div></div>
                    <div class="faq-a"><?= $e($a) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- RELATED FEATURES -->
<?php if (!empty($siblings)): ?>
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="flex items-end justify-between mb-10">
            <div>
                <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-2">SEGUÍ EXPLORANDO</div>
                <h2 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Otras funcionalidades</h2>
            </div>
            <a href="<?= $url('/') ?>#features" class="text-[13px] font-semibold text-brand-700 inline-flex items-center gap-1">Ver todas <i class="lucide lucide-arrow-right text-[12px]"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php $count = 0; foreach ($siblings as $key => $sib): if ($count++ >= 4) break; ?>
                <a href="<?= $url('/features/' . $key) ?>" class="bento spotlight-card block group" style="padding:22px">
                    <div class="bento-glow"></div>
                    <div class="w-12 h-12 rounded-2xl grid place-items-center" style="background:<?= $sib['bg'] ?>;color:<?= $sib['color'] ?>;box-shadow:0 6px 14px -4px <?= $sib['color'] ?>40"><i class="lucide lucide-<?= $sib['icon'] ?> text-[20px]"></i></div>
                    <div class="font-display font-bold text-[15px] mt-4 tracking-[-0.015em]"><?= $e($sib['title']) ?></div>
                    <div class="text-[12px] text-ink-400 mt-1.5 line-clamp-2"><?= $e($sib['tagline']) ?></div>
                    <div class="mt-3 inline-flex items-center gap-1 text-[11.5px] font-semibold opacity-0 group-hover:opacity-100 transition" style="color:<?= $sib['color'] ?>">Saber más <i class="lucide lucide-arrow-right text-[11px]"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="py-24">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="relative rounded-[28px] overflow-hidden p-12 md:p-16 text-center" style="background:linear-gradient(135deg,<?= $f['color'] ?> 0%,#16151b 80%);color:white;box-shadow:0 30px 60px -20px <?= $f['color'] ?>80">
            <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 0% 0%,rgba(255,255,255,.18),transparent 50%),radial-gradient(circle at 100% 100%,rgba(255,255,255,.10),transparent 50%)"></div>
            <div class="relative max-w-2xl mx-auto">
                <h2 class="display-xl text-white" style="font-size:clamp(2rem,3.5vw + 1rem,3.4rem);text-wrap:balance">Probá <?= $e($f['title']) ?> ahora.</h2>
                <p class="mt-5 text-[16px]" style="color:rgba(255,255,255,.85)">Workspace pre-cargado · Sin tarjeta · Se borra en 24h</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= $url('/demo') ?>" class="btn btn-lg" style="background:white;color:#16151b"><i class="lucide lucide-play"></i> Probar demo</a>
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
