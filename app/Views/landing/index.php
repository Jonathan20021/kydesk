<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<!-- ========== HERO ========== -->
<section class="relative pt-28 pb-16 md:pt-36 md:pb-24 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grid-bg"></div>
    <div class="noise-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="max-w-3xl mx-auto text-center reveal-stagger" data-reveal>
            <?php
            $pillText = !empty($featuredChangelog)
                ? ($featuredChangelog['hero_pill_label'] ?: $featuredChangelog['title'])
                : __('landing.hero.pill_default');
            ?>
            <a href="<?= $url('/changelog') ?>" class="inline-flex justify-center">
                <div class="aura-pill hover:shadow-md transition cursor-pointer">
                    <span class="aura-pill-tag"><i class="lucide lucide-sparkles"></i> <?= $te('landing.hero.pill_new') ?></span>
                    <span class="text-ink-700 font-medium"><?= $e($pillText) ?></span>
                    <i class="lucide lucide-arrow-right text-[12px] text-ink-400"></i>
                </div>
            </a>

            <h1 class="display-xl mt-8" style="text-wrap:balance">
                <?= $te('landing.hero.title_pre') ?><br>
                <span class="gradient-shift"><?= $te('landing.hero.title_post') ?></span>
            </h1>

            <p class="mt-7 text-[19px] max-w-xl mx-auto leading-relaxed text-ink-500"><?= __('landing.hero.subtitle') ?></p>

            <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?= $url('/demo') ?>" class="btn btn-lg glow-purple" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white"><i class="lucide lucide-play"></i> <?= $te('landing.hero.cta_primary') ?></a>
                <a href="<?= $url('/auth/register') ?>" class="btn btn-outline btn-lg"><?= $te('landing.hero.cta_secondary') ?> <i class="lucide lucide-arrow-right"></i></a>
            </div>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[12.5px] text-ink-400">
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> <?= $te('landing.hero.bullet1') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> <?= $te('landing.hero.bullet2') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> <?= $te('landing.hero.bullet3') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> <?= $te('landing.hero.bullet4') ?></span>
            </div>
        </div>

        <!-- HERO MOCKUP — solo desktop / tablet grande -->
        <div class="hidden md:block mt-20 relative max-w-[1180px] mx-auto reveal" data-reveal>

            <div class="float-chip delay-1 hidden xl:flex absolute z-30" style="top:84px;left:-32px;">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 grid place-items-center"><i class="lucide lucide-check-circle-2"></i></div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold text-ink-400"><?= $te('landing.hero.chip_sla_label') ?></div>
                    <div class="font-display font-bold text-[13px]"><?= $te('landing.hero.chip_sla_value') ?></div>
                </div>
            </div>
            <div class="float-chip delay-2 hidden xl:flex absolute z-30" style="top:38%;right:-32px;">
                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-zap"></i></div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold text-ink-400"><?= $te('landing.hero.chip_resolution_label') ?></div>
                    <div class="font-display font-bold text-[13px]"><?= $te('landing.hero.chip_resolution_value') ?></div>
                </div>
            </div>
            <div class="float-chip delay-3 hidden xl:flex absolute z-30" style="bottom:72px;left:-28px;">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 grid place-items-center"><i class="lucide lucide-bell-ring"></i></div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold text-ink-400"><?= $te('landing.hero.chip_alert_label') ?></div>
                    <div class="font-display font-bold text-[13px]"><?= $te('landing.hero.chip_alert_value') ?></div>
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
                                    <div class="text-[9.5px] text-ink-400 uppercase tracking-wider"><?= __e('mock.plan') ?></div>
                                </div>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink-400 px-2 mb-2"><?= __e('mock.section.general') ?></div>
                            <div class="space-y-0.5">
                                <?php foreach ([['layout-dashboard',__('mock.nav.dashboard'),true,null],['inbox',__('mock.nav.tickets'),false,'12'],['kanban-square',__('mock.nav.board'),false,null],['line-chart',__('mock.nav.reports'),false,null]] as [$ic,$lbl,$on,$cnt]): ?>
                                    <div class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[12.5px] <?= $on?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500 font-medium' ?>">
                                        <i class="lucide lucide-<?= $ic ?> text-[14px]"></i><span class="flex-1"><?= $e($lbl) ?></span>
                                        <?php if ($cnt): ?><span class="bg-brand-500 text-white px-1.5 rounded-full text-[9.5px] font-semibold"><?= $cnt ?></span><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink-400 px-2 mt-4 mb-2"><?= __e('mock.section.management') ?></div>
                            <div class="space-y-0.5">
                                <?php foreach ([['building-2',__('mock.nav.companies')],['server',__('mock.nav.assets')],['book-open',__('mock.nav.kb')]] as [$ic,$lbl]): ?>
                                    <div class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[12.5px] text-ink-500 font-medium">
                                        <i class="lucide lucide-<?= $ic ?> text-[14px]"></i><?= $e($lbl) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-span-6 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="flex-1 h-9 rounded-full bg-white border border-[#ececef] flex items-center px-4 gap-2">
                                    <i class="lucide lucide-search text-ink-400 text-sm"></i>
                                    <span class="text-[11px] text-ink-400"><?= __e('mock.search') ?></span>
                                </div>
                                <div class="w-9 h-9 rounded-full bg-white border border-[#ececef] grid place-items-center"><i class="lucide lucide-bell text-sm text-ink-700"></i></div>
                                <div class="w-9 h-9 rounded-full text-white grid place-items-center text-[10px] font-bold" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">AD</div>
                            </div>
                            <div class="hero-card relative" style="padding: 22px 26px; border-radius: 18px;">
                                <div class="hero-stars" style="opacity:.3;"><svg viewBox="0 0 280 200"><path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/><path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/><path d="M220 130 L223 145 L238 148 L223 151 L220 166 L217 151 L202 148 L217 145 Z" fill="white"/></svg></div>
                                <div class="hero-tag"><?= __e('mock.support_tag') ?></div>
                                <div class="hero-title" style="font-size:22px; margin-top:6px;"><?= __('mock.solve_more') ?></div>
                                <div class="hero-cta" style="font-size:11.5px; padding:4px 6px 4px 14px; margin-top:14px;">
                                    <?= __e('mock.new_ticket') ?>
                                    <span class="hero-cta-arrow" style="width:22px;height:22px"><i class="lucide lucide-arrow-right text-[11px]"></i></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2.5 mt-3">
                                <?php foreach ([[__('mock.kpi.open'),'24/120','#f3e8ff','#7e22ce','grid-2x2'],[__('mock.kpi.in_progress'),'12/120','#fef3c7','#b45309','refresh-cw'],[__('mock.kpi.resolved'),'189/220','#d1fae5','#047857','book-open']] as [$l,$v,$bg,$col,$ic]): ?>
                                <div class="bg-white border border-[#ececef] rounded-2xl p-3 flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-sm"></i></div>
                                    <div>
                                        <div class="text-[9.5px] text-ink-400"><?= $v ?></div>
                                        <div class="font-display font-bold text-[12px]"><?= $e($l) ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex items-center justify-between mt-4 mb-2.5">
                                <div class="font-display font-bold text-[14px]"><?= __e('mock.recent_tickets') ?></div>
                                <div class="flex gap-1.5">
                                    <div class="w-6 h-6 rounded-full bg-white border border-[#ececef] grid place-items-center text-ink-500"><i class="lucide lucide-chevron-left text-[11px]"></i></div>
                                    <div class="w-6 h-6 rounded-full bg-brand-500 text-white grid place-items-center"><i class="lucide lucide-chevron-right text-[11px]"></i></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <?php
                                $cards = [
                                    [__('mock.tag.hardware'), __('mock.ticket1'), 'María T.', 'MT', '#7c5cff', '#fef3c7'],
                                    [__('mock.tag.networks'), __('mock.ticket2'), 'Carlos I.', 'CI', '#f59e0b', '#dbeafe'],
                                    [__('mock.tag.software'), __('mock.ticket3'), 'Juan S.',  'JS', '#ec4899', '#fce7f3'],
                                ];
                                foreach ($cards as [$tag,$cardTitle,$n,$in,$col,$bg]): ?>
                                <div class="bg-white border border-[#ececef] rounded-2xl overflow-hidden">
                                    <div class="h-16 grid place-items-center relative" style="background: linear-gradient(135deg,<?= $bg ?>,#fff);">
                                        <span class="absolute top-1.5 left-1.5 text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full bg-white/85 text-brand-700"><?= $e($tag) ?></span>
                                    </div>
                                    <div class="p-2.5">
                                        <div class="text-[10.5px] font-display font-bold leading-tight line-clamp-2"><?= $e($cardTitle) ?></div>
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
                                <div class="font-display font-bold text-[13px]"><?= __e('mock.statistic') ?></div>
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
                            <div class="font-display font-bold text-[13px] text-center mt-3"><?= __e('mock.great_job') ?></div>
                            <div class="flex items-end gap-3 h-[72px] mt-4 px-1 pb-2 border-b border-[#ececef]">
                                <div class="flex-1 rounded-t-md bg-brand-200" style="height:50%"></div>
                                <div class="flex-1 rounded-t-md bg-brand-500" style="height:90%"></div>
                                <div class="flex-1 rounded-t-md bg-brand-200" style="height:35%"></div>
                                <div class="flex-1 rounded-t-md bg-brand-200" style="height:65%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-4 mb-2">
                                <div class="font-display font-bold text-[13px]"><?= __e('mock.your_team') ?></div>
                                <div class="w-6 h-6 rounded-full bg-brand-50 grid place-items-center text-brand-700"><i class="lucide lucide-plus text-[11px]"></i></div>
                            </div>
                            <?php foreach ([['María T.','MT','#ec4899'],['Carlos I.','CI','#f59e0b']] as [$n,$in,$c]): ?>
                                <div class="flex items-center gap-2 py-1.5">
                                    <div class="w-7 h-7 rounded-full grid place-items-center text-white text-[9px] font-bold" style="background:<?= $c ?>"><?= $in ?></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-display font-bold text-[10.5px] truncate"><?= $n ?></div>
                                        <div class="text-[8.5px] text-ink-400"><?= __e('mock.role.agent') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HERO STATS — solo móvil (reemplaza el mockup) -->
        <div class="md:hidden mt-12 max-w-md mx-auto reveal" data-reveal>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-white border border-[#ececef] p-4">
                    <div class="font-display font-extrabold text-[22px] tracking-[-0.02em] text-brand-700 leading-none">82%</div>
                    <div class="text-[10px] uppercase tracking-[0.12em] font-bold text-ink-400 mt-1.5"><?= $te('landing.hero.mobile.sla') ?></div>
                </div>
                <div class="rounded-2xl bg-white border border-[#ececef] p-4">
                    <div class="font-display font-extrabold text-[22px] tracking-[-0.02em] text-emerald-600 leading-none">2.4×</div>
                    <div class="text-[10px] uppercase tracking-[0.12em] font-bold text-ink-400 mt-1.5"><?= $te('landing.hero.mobile.resolution') ?></div>
                </div>
                <div class="rounded-2xl bg-white border border-[#ececef] p-4">
                    <div class="font-display font-extrabold text-[22px] tracking-[-0.02em] text-rose-600 leading-none">+98%</div>
                    <div class="text-[10px] uppercase tracking-[0.12em] font-bold text-ink-400 mt-1.5"><?= $te('landing.hero.mobile.fulfilled') ?></div>
                </div>
            </div>
            <div class="mt-4 rounded-2xl p-5 text-white text-center" style="background:linear-gradient(135deg,#7c5cff 0%,#a78bfa 60%,#d946ef 110%);box-shadow:0 24px 48px -16px rgba(124,92,255,.5)">
                <div class="text-[10px] uppercase tracking-[0.16em] font-bold opacity-90"><?= $te('landing.hero.mobile.support') ?></div>
                <div class="font-display font-extrabold text-[22px] mt-1.5 leading-tight"><?= __('landing.hero.mobile.title') ?></div>
                <a href="<?= $url('/auth/register') ?>" class="inline-flex items-center gap-2 mt-4 bg-white text-ink-900 font-semibold text-[12.5px] px-4 py-2 rounded-full">
                    <?= $te('landing.hero.mobile.cta') ?> <i class="lucide lucide-arrow-right text-[12px]"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========== TRUSTED LOGOS — MARQUEE ========== -->
<section id="customers" class="py-14 border-y border-[#ececef] bg-white relative">
    <div class="max-w-[1240px] mx-auto px-6 mb-8">
        <p class="text-center text-[11.5px] font-bold uppercase tracking-[0.18em] text-ink-400"><?= $te('landing.trusted.heading') ?></p>
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

<!-- ========== WHAT'S NEW v4.6 (visual showcase) ========== -->
<section id="whats-new" class="relative py-24 overflow-hidden" style="background:linear-gradient(180deg,#fafafb 0%,white 100%)">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute" style="width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.08),transparent 60%);top:-180px;left:-100px;filter:blur(50px)"></div>
        <div class="absolute" style="width:480px;height:480px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.06),transparent 60%);bottom:-160px;right:-80px;filter:blur(50px)"></div>
    </div>
    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="text-center max-w-3xl mx-auto reveal" data-reveal>
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em] mb-4" style="background:linear-gradient(135deg,rgba(124,92,255,.12),rgba(217,70,239,.08));color:#5a3aff;border:1px solid rgba(124,92,255,.25)">
                <i class="lucide lucide-sparkles text-[12px]"></i> <?= __e('wn.release_pill') ?>
            </div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance"><?= __e('wn.title_pre') ?><br><span class="gradient-shift"><?= __e('wn.title_post') ?></span></h2>
            <p class="mt-6 text-[16px] text-ink-500 max-w-2xl mx-auto"><?= __e('wn.subtitle') ?></p>
        </div>

        <!-- 10 modules grid -->
        <div class="mt-14 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 reveal-stagger" data-reveal>
            <?php foreach ([
                ['email_inbound', __('module.email_inbound.name'), 'mail-open', '#0369a1', '#e0f2fe', 'PRO'],
                ['live_chat',     __('module.live_chat.name'),     'message-square', '#047857', '#ecfdf5', 'BUSINESS'],
                ['ai_assist',     __('module.ai_assist.name'),     'sparkles', '#7e22ce', '#f3e8ff', 'ENTERPRISE'],
                ['csat',          __('module.csat.name'),          'smile', '#b45309', '#fef3c7', 'TODOS'],
                ['itsm',          __('module.itsm.name'),          'workflow', '#0284c7', '#dbeafe', 'BUSINESS'],
                ['time_tracking', __('module.time_tracking.name'), 'timer', '#b91c1c', '#fee2e2', 'PRO'],
                ['status_page',   __('module.status_page.name'),   'activity', '#047857', '#d1fae5', 'TODOS'],
                ['customer_portal', __('module.customer_portal.name'), 'lock-keyhole', '#7c2d12', '#f3e8ff', 'TODOS'],
                ['reports_builder', __('module.reports_builder.name'), 'bar-chart-3', '#7e22ce', '#f3e8ff', 'BUSINESS'],
                ['custom_fields', __('module.custom_fields.name'), 'list-plus', '#0e7490', '#cffafe', 'TODOS'],
            ] as [$key, $title, $icon, $color, $bg, $tier]): ?>
                <a href="<?= $url('/features/' . $key) ?>" class="group relative rounded-2xl p-5 transition" style="background:white;border:1px solid #ececef" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 40px -16px rgba(22,21,27,.12)';this.style.borderColor='<?= $color ?>33';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';this.style.borderColor='#ececef';">
                    <div class="absolute top-3 right-3 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-[0.1em]" style="background:<?= $bg ?>;color:<?= $color ?>"><?= __e('wn.tier.' . $tier) ?></div>
                    <div class="w-11 h-11 rounded-xl grid place-items-center mb-3" style="background:<?= $bg ?>;color:<?= $color ?>"><i class="lucide lucide-<?= $icon ?> text-[18px]"></i></div>
                    <div class="font-display font-bold text-[13.5px] tracking-[-0.01em]"><?= $e($title) ?></div>
                    <div class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold opacity-0 group-hover:opacity-100 transition" style="color:<?= $color ?>"><?= __e('wn.go') ?> <i class="lucide lucide-arrow-right text-[10px]"></i></div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- AI spotlight (Enterprise highlight) -->
        <div class="mt-14 grid grid-cols-1 lg:grid-cols-5 gap-5 reveal" data-reveal>
            <div class="lg:col-span-3 relative rounded-3xl p-8 md:p-10 overflow-hidden text-white" style="background:linear-gradient(135deg,#1a1825 0%,#2a1f3d 50%,#3f1d6b 100%);box-shadow:0 24px 60px -16px rgba(124,92,255,.35)">
                <div class="absolute" style="width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(167,139,250,.4),transparent 60%);top:-120px;right:-80px;filter:blur(40px)"></div>
                <div class="relative">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10.5px] font-bold uppercase tracking-[0.16em]" style="background:rgba(167,139,250,.18);color:#c4b5fd;border:1px solid rgba(167,139,250,.35)">
                        <i class="lucide lucide-sparkles text-[11px]"></i> <?= __e('wn.ai_eyebrow') ?>
                    </div>
                    <h3 class="font-display font-extrabold text-[28px] md:text-[36px] tracking-[-0.025em] mt-5 leading-tight"><?= __e('wn.ai_title_pre') ?><br><?= __e('wn.ai_title_post') ?></h3>
                    <p class="mt-4 text-[14.5px]" style="color:rgba(255,255,255,.78)"><?= __e('wn.ai_para') ?></p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <?php foreach ([[__('wn.ai_cap1'),'message-square-quote'],[__('wn.ai_cap2'),'file-text'],[__('wn.ai_cap3'),'tag'],[__('wn.ai_cap4'),'heart-pulse'],[__('wn.ai_cap5'),'languages']] as [$lbl,$ic]): ?>
                            <span class="inline-flex items-center gap-1.5 text-[11.5px] px-2.5 py-1 rounded-full" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.9);border:1px solid rgba(255,255,255,.12)"><i class="lucide lucide-<?= $ic ?> text-[11px]"></i> <?= $e($lbl) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= $url('/features/ai_assist') ?>" class="mt-7 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-[13px] transition" style="background:white;color:#1a1825" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'"><?= __e('wn.ai_cta') ?> <i class="lucide lucide-arrow-right text-[13px]"></i></a>
                </div>
            </div>

            <div class="lg:col-span-2 grid grid-cols-1 gap-3">
                <div class="card card-pad" style="background:linear-gradient(135deg,#ecfdf5,white)">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white grid place-items-center"><i class="lucide lucide-mail-open text-[15px]"></i></div>
                        <div class="font-display font-bold text-[14px]"><?= __e('wn.email_title') ?></div>
                    </div>
                    <p class="text-[12.5px] text-ink-500"><?= __e('wn.email_desc') ?></p>
                    <a href="<?= $url('/features/email_inbound') ?>" class="text-[11.5px] font-semibold text-emerald-700 mt-2 inline-flex items-center gap-1"><?= __e('wn.know_more') ?> <i class="lucide lucide-arrow-right text-[11px]"></i></a>
                </div>
                <div class="card card-pad" style="background:linear-gradient(135deg,#dbeafe,white)">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-xl text-white grid place-items-center" style="background:#0284c7"><i class="lucide lucide-workflow text-[15px]"></i></div>
                        <div class="font-display font-bold text-[14px]"><?= __e('wn.itsm_title') ?></div>
                    </div>
                    <p class="text-[12.5px] text-ink-500"><?= __e('wn.itsm_desc') ?></p>
                    <a href="<?= $url('/features/itsm') ?>" class="text-[11.5px] font-semibold text-blue-700 mt-2 inline-flex items-center gap-1"><?= __e('wn.know_more') ?> <i class="lucide lucide-arrow-right text-[11px]"></i></a>
                </div>
            </div>
        </div>

        <div class="text-center mt-10">
            <a href="<?= $url('/changelog') ?>" class="inline-flex items-center gap-2 text-[13px] font-semibold text-brand-700 hover:gap-3 transition-all"><?= __e('wn.see_full_changelog') ?> <i class="lucide lucide-arrow-right text-[13px]"></i></a>
        </div>
    </div>
</section>

<!-- ========== FEATURE BLOCKS ========== -->
<section id="features" class="py-32 relative">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('feat.eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance"><?= __e('feat.title_pre') ?><br><?= __e('feat.title_mid') ?> <span class="gradient-shift"><?= __e('feat.title_post') ?></span>.</h2>
            <p class="mt-6 text-[16px] text-ink-500 max-w-lg mx-auto"><?= __e('feat.subtitle') ?></p>
        </div>

        <div class="mt-16 grid grid-cols-12 gap-4 reveal-stagger" data-reveal>
            <div class="col-span-12 lg:col-span-7 bento spotlight-card" style="min-height:400px;">
                <div class="bento-glow"></div>
                <span class="badge badge-purple"><i class="lucide lucide-inbox text-[11px]"></i> <?= __e('feat.inbox_badge') ?></span>
                <h3 class="font-display font-extrabold text-[28px] mt-4 tracking-[-0.025em]"><?= __e('feat.inbox_title') ?></h3>
                <p class="text-[14.5px] text-ink-500 mt-2 max-w-md leading-relaxed"><?= __e('feat.inbox_para') ?></p>
                <div class="mt-7 space-y-2.5">
                    <?php foreach ([
                        ['globe', __('feat.ticker1_ch'), __('feat.ticker1_s'), '#ef4444', __('feat.ticker1_pl')],
                        ['mail',  __('feat.ticker2_ch'), __('feat.ticker2_s'), '#f59e0b', __('feat.ticker2_pl')],
                        ['phone', __('feat.ticker3_ch'), __('feat.ticker3_s'), '#7c5cff', __('feat.ticker3_pl')],
                    ] as [$ic,$ch,$tickS,$c,$pl]): ?>
                        <div class="ticker-row">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#ececef] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[10.5px] text-ink-400 mb-0.5"><?= $e($ch) ?></div>
                                <div class="text-[13px] font-semibold truncate"><?= $e($tickS) ?></div>
                            </div>
                            <span class="status-pill" style="background:<?= $c ?>1f;color:<?= $c ?>"><?= $e($pl) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= $url('/features/inbox') ?>" class="inline-flex items-center gap-1 mt-6 text-[13px] font-semibold text-brand-700 hover:gap-2 transition-all"><?= __e('feat.inbox_more') ?> <i class="lucide lucide-arrow-right text-[13px]"></i></a>
            </div>

            <div class="col-span-12 lg:col-span-5 bento spotlight-card" style="min-height:400px;">
                <div class="bento-glow"></div>
                <span class="badge badge-amber"><i class="lucide lucide-gauge text-[11px]"></i> <?= __e('feat.sla_badge') ?></span>
                <h3 class="font-display font-extrabold text-[28px] mt-4 tracking-[-0.025em]"><?= __e('feat.sla_title') ?></h3>
                <p class="text-[14.5px] text-ink-500 mt-2 leading-relaxed"><?= __e('feat.sla_para') ?></p>
                <div class="mt-7 space-y-3.5">
                    <?php foreach ([[__('feat.priority.urgent'),'15m',88,'#ef4444'],[__('feat.priority.high'),'1h 30m',64,'#f59e0b'],[__('feat.priority.medium'),'4h 12m',38,'#7c5cff']] as [$l,$slaT,$p,$c]): ?>
                        <div class="border border-[#ececef] rounded-2xl p-4 hover:border-brand-200 transition">
                            <div class="flex items-center justify-between text-[12.5px] mb-2.5">
                                <span class="font-display font-bold flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= $c ?>"></span><?= $e($l) ?></span>
                                <span class="font-mono text-ink-500 text-[12px] font-semibold"><?= $e($slaT) ?></span>
                            </div>
                            <div class="progress"><div class="progress-bar" style="width:<?= $p ?>%; background:<?= $c ?>"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= $url('/features/sla') ?>" class="inline-flex items-center gap-1 mt-6 text-[13px] font-semibold text-amber-700 hover:gap-2 transition-all"><?= __e('feat.sla_more') ?> <i class="lucide lucide-arrow-right text-[13px]"></i></a>
            </div>

            <?php
            $bentos = [
                ['email_inbound',  __('module.email_inbound.name'),  __('module.email_inbound.desc'), 'mail-open','#e0f2fe','#0369a1'],
                ['live_chat',      __('module.live_chat.name'),      __('module.live_chat.desc'),      'message-square','#ecfdf5','#047857'],
                ['ai_assist',      __('module.ai_assist.name'),      __('module.ai_assist.desc'),      'sparkles','#f3e8ff','#7e22ce'],
                ['csat',           __('module.csat.name'),           __('module.csat.desc'),           'smile','#fef3c7','#b45309'],
                ['itsm',           __('module.itsm.name'),           __('module.itsm.desc'),           'workflow','#dbeafe','#0284c7'],
                ['time_tracking',  __('module.time_tracking.name'),  __('module.time_tracking.desc'),  'timer','#fee2e2','#b91c1c'],
                ['status_page',    __('module.status_page.name'),    __('module.status_page.desc'),    'activity','#d1fae5','#047857'],
                ['customer_portal',__('module.customer_portal.name'),__('module.customer_portal.desc'),'lock-keyhole','#f3e8ff','#7c2d12'],
                ['reports_builder',__('module.reports_builder.name'),__('module.reports_builder.desc'),'bar-chart-3','#f3e8ff','#7e22ce'],
                ['meetings',       __('module.meetings.name'),       __('module.meetings.desc'),       'calendar-clock','#f3f0ff','#7c5cff'],
                ['retainers',      __('module.retainers.name'),      __('module.retainers.desc'),      'handshake','#ecfdf5','#047857'],
                ['crm',            __('module.crm.name'),            __('module.crm.desc'),            'contact-round','#f3f0ff','#5a3aff'],
                ['kanban',         __('module.kanban.name'),         __('module.kanban.desc'),         'kanban-square','#dbeafe','#1d4ed8'],
                ['automations',    __('module.automations.name'),    __('module.automations.desc'),    'workflow','#f3e8ff','#7e22ce'],
            ];
            foreach ($bentos as [$key,$bTitle,$bDesc,$ic,$bg,$col]): ?>
                <a href="<?= $url('/features/' . $key) ?>" class="col-span-12 sm:col-span-6 lg:col-span-4 bento spotlight-card group block">
                    <div class="bento-glow"></div>
                    <div class="bento-icon" style="background:<?= $bg ?>;color:<?= $col ?>">
                        <i class="lucide lucide-<?= $ic ?> text-[22px] relative z-10"></i>
                    </div>
                    <h3 class="font-display font-extrabold text-[20px] mt-5 tracking-[-0.02em]"><?= $e($bTitle) ?></h3>
                    <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed"><?= $e($bDesc) ?></p>
                    <span class="inline-flex items-center gap-1 mt-4 text-[12.5px] font-semibold transition" style="color:<?= $col ?>"><?= __e('wn.know_more') ?> <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-1 transition"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== CRM SHOWCASE (Business / Enterprise) ========== -->
<section id="crm" class="py-28 relative overflow-hidden" style="background:linear-gradient(180deg,#fafafb 0%,#f3f0ff 100%)">
    <div class="absolute inset-0 pointer-events-none">
        <div class="aurora-blob" style="width:560px;height:560px;background:radial-gradient(circle,rgba(124,92,255,.35),transparent 70%);top:-180px;right:-120px;mix-blend-mode:multiply;opacity:.5;animation:aurora-1 24s ease-in-out infinite"></div>
        <div class="aurora-blob" style="width:480px;height:480px;background:radial-gradient(circle,rgba(167,139,250,.3),transparent 70%);bottom:-160px;left:-100px;mix-blend-mode:multiply;opacity:.5;animation:aurora-2 26s ease-in-out infinite"></div>
    </div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="grid grid-cols-12 gap-8 items-center">
            <!-- LEFT — copy -->
            <div class="col-span-12 lg:col-span-5 reveal" data-reveal>
                <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.18em] px-2.5 py-1 rounded-full mb-4" style="background:#f3f0ff;color:#5a3aff;border:1px solid #cdbfff">
                    <i class="lucide lucide-crown text-[11px]"></i> Business · Enterprise
                </div>
                <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3.4rem);text-wrap:balance">
                    Tu <span class="gradient-shift">CRM comercial</span><br>integrado al helpdesk.
                </h2>
                <p class="mt-5 text-[16px] text-ink-500 leading-relaxed max-w-md">
                    Captá leads, movélos por tu pipeline con drag & drop, registrá llamadas y reuniones, convertilos en clientes — y en el mismo lugar gestioná sus tickets, contratos y reuniones de soporte.
                </p>

                <ul class="mt-7 space-y-3">
                    <?php foreach ([
                        ['kanban-square', 'Pipeline kanban con drag & drop', 'Múltiples pipelines (Ventas, Onboarding, Renovaciones) con etapas configurables y probabilidades calibradas.'],
                        ['flame-kindling', 'Lead scoring y rating', 'Score 0-100 + Frío/Tibio/Caliente · ordenamiento automático de hot leads en el dashboard.'],
                        ['phone-call',  'Actividades programadas', 'Llamadas, emails, reuniones, tareas, WhatsApp · alertas de follow-ups vencidos.'],
                        ['user-check',  'Conversión a cliente en un click', 'Crea automáticamente la empresa y el usuario del Portal Cliente · vincula tickets existentes.'],
                        ['radar',       '10 orígenes pre-configurados',  'Web, Referido, Ads, LinkedIn, Cold call, Cold email, Evento, Partner, Form web, WhatsApp.'],
                        ['layers-2',    'Permisos granulares y multi-tenant', 'crm.view / create / edit / delete / config / assign / convert · gateado por plan.'],
                    ] as [$ic, $title, $desc]): ?>
                        <li class="flex gap-3">
                            <div class="w-9 h-9 rounded-xl grid place-items-center flex-shrink-0" style="background:white;color:#5a3aff;box-shadow:0 4px 12px -4px rgba(124,92,255,.3)">
                                <i class="lucide lucide-<?= $ic ?> text-[15px]"></i>
                            </div>
                            <div>
                                <div class="font-display font-bold text-[14px] tracking-[-0.01em]"><?= $e($title) ?></div>
                                <div class="text-[12.5px] text-ink-500 mt-0.5 leading-relaxed"><?= $e($desc) ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="<?= $url('/demo') ?>" class="btn glow-purple" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white"><i class="lucide lucide-play"></i> Probar demo Business</a>
                    <a href="<?= $url('/pricing') ?>" class="btn btn-outline">Ver planes <i class="lucide lucide-arrow-right text-[12px]"></i></a>
                </div>
            </div>

            <!-- RIGHT — kanban mock -->
            <div class="col-span-12 lg:col-span-7 reveal" data-reveal>
                <div class="rounded-3xl p-2 relative" style="background:linear-gradient(135deg,rgba(124,92,255,.18),rgba(167,139,250,.08));box-shadow:0 32px 80px -24px rgba(124,92,255,.35)">
                    <div class="rounded-[20px] bg-white border border-[#ececef] overflow-hidden">
                        <!-- Mock chrome -->
                        <div class="flex items-center gap-1.5 px-3 py-2.5 border-b border-[#ececef]">
                            <span class="w-3 h-3 rounded-full bg-[#ff5f57]"></span>
                            <span class="w-3 h-3 rounded-full bg-[#febc2e]"></span>
                            <span class="w-3 h-3 rounded-full bg-[#28c840]"></span>
                            <div class="flex-1 text-center text-[11px] font-mono text-ink-400 flex items-center justify-center gap-1.5"><i class="lucide lucide-lock text-[10px]"></i> kydesk / acme / crm / pipeline</div>
                        </div>

                        <div class="p-4 bg-[#fafafb]">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <div class="font-display font-extrabold text-[16px] tracking-[-0.025em]">Pipeline de Ventas</div>
                                    <div class="text-[10.5px] text-ink-400">12 oportunidades · $48,300 en pipeline</div>
                                </div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold" style="background:#f3f0ff;color:#5a3aff;border:1px solid #cdbfff">
                                    <i class="lucide lucide-target text-[10px]"></i> SALES
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <?php
                                $mockStages = [
                                    ['Calificado',  '#0ea5e9', '35%', [
                                        ['Acme · Plan Pro',         '$4,500', 'JM', '#7c5cff', false],
                                        ['Initech · Onboarding',    '$2,800', 'CL', '#0ea5e9', false],
                                    ]],
                                    ['Propuesta',   '#a78bfa', '55%', [
                                        ['Globex · CRM Setup',      '$9,200', 'AS', '#f59e0b', true],
                                        ['Hooli · Migración',       '$6,400', 'MR', '#16a34a', false],
                                        ['Stark Ind · Soporte',     '$3,100', 'TS', '#ec4899', false],
                                    ]],
                                    ['Negociación', '#f59e0b', '75%', [
                                        ['Wayne Ent · Anual',       '$18,600','BW', '#dc2626', true],
                                        ['Pied Piper · Pro',        '$3,700', 'RH', '#0ea5e9', false],
                                    ]],
                                ];
                                foreach ($mockStages as [$stName, $stColor, $stProb, $deals]): ?>
                                    <div class="bg-white rounded-2xl border border-[#ececef] overflow-hidden" style="border-top:3px solid <?= $stColor ?>">
                                        <div class="px-2.5 py-2 flex items-center justify-between" style="background:<?= $stColor ?>0d">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $stColor ?>"></span>
                                                <span class="font-display font-bold text-[11px]"><?= $e($stName) ?></span>
                                            </div>
                                            <span class="text-[9.5px] font-bold text-ink-500"><?= $stProb ?></span>
                                        </div>
                                        <div class="p-1.5 space-y-1.5 min-h-[160px]">
                                            <?php foreach ($deals as [$title, $amt, $init, $col, $hot]): ?>
                                                <div class="bg-white border border-[#ececef] rounded-lg p-2 hover:border-brand-200 transition cursor-pointer">
                                                    <div class="font-display font-bold text-[10.5px] line-clamp-2 leading-tight"><?= $e($title) ?></div>
                                                    <div class="flex items-center justify-between mt-1.5">
                                                        <div class="w-4 h-4 rounded-full grid place-items-center text-white text-[7.5px] font-bold" style="background:<?= $col ?>"><?= $e($init) ?></div>
                                                        <span class="font-mono font-extrabold text-[10.5px]"><?= $e($amt) ?></span>
                                                    </div>
                                                    <?php if ($hot): ?>
                                                        <span class="inline-flex items-center gap-0.5 text-[8.5px] font-bold mt-1 px-1 py-0.5 rounded-full" style="background:#fee2e2;color:#dc2626"><i class="lucide lucide-flame-kindling text-[8px]"></i> Hot</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <?php foreach ([
                                    ['Hot leads',     '4',  'flame-kindling', '#ef4444', '#fee2e2'],
                                    ['Cierre 30d',    '$24K', 'trending-up',  '#16a34a', '#ecfdf5'],
                                    ['Tasa conv.',    '38%','target',        '#7c5cff', '#f3f0ff'],
                                ] as [$lbl, $val, $ic, $col, $bg]): ?>
                                    <div class="bg-white border border-[#ececef] rounded-xl p-2 flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[12px]"></i></div>
                                        <div>
                                            <div class="text-[9px] text-ink-400 font-bold uppercase tracking-[0.1em]"><?= $e($lbl) ?></div>
                                            <div class="font-display font-extrabold text-[12.5px]"><?= $e($val) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Floating "convert to customer" chip -->
                    <div class="absolute -bottom-4 -right-4 hidden md:flex items-center gap-2.5 bg-white rounded-2xl pl-3 pr-4 py-2.5 border border-emerald-200" style="box-shadow:0 12px 30px -10px rgba(16,163,74,.3)">
                        <div class="w-8 h-8 rounded-xl grid place-items-center" style="background:linear-gradient(135deg,#16a34a,#10b981);color:white"><i class="lucide lucide-user-check text-[14px]"></i></div>
                        <div>
                            <div class="text-[9.5px] font-bold uppercase tracking-[0.12em] text-emerald-700">Lead → Cliente</div>
                            <div class="font-display font-bold text-[12px]">Empresa + Portal en 1 click</div>
                        </div>
                    </div>
                </div>
            </div>
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
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-300 mb-3"><?= __e('stats.eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3.5rem)"><?= __e('stats.title') ?></h2>
        </div>
        <div class="mt-16 grid grid-cols-2 lg:grid-cols-4 gap-8 reveal-stagger" data-reveal>
            <?php foreach ([['42','%',__('stats.s1'),'clock'],['3.8','×',__('stats.s2'),'zap'],['99.99','%',__('stats.s3'),'activity'],['12','K+',__('stats.s4'),'inbox']] as [$n,$suffix,$l,$ic]): ?>
                <div class="border-t border-white/15 pt-7">
                    <i class="lucide lucide-<?= $ic ?> text-[18px] text-brand-300"></i>
                    <div class="mt-5 flex items-baseline gap-1">
                        <span class="font-display font-extrabold text-[64px] tracking-[-0.04em] leading-none gradient-shift" data-counter="<?= $n ?>"><?= $n ?></span>
                        <span class="font-display font-extrabold text-[28px] text-white/70"><?= $suffix ?></span>
                    </div>
                    <div class="mt-3 text-[13.5px] text-white/70 max-w-[200px] leading-snug"><?= $e($l) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== INTEGRATIONS SECTION (super animada · v2) ========== -->
<section id="integrations" class="ix2-section">
    <style>
        .ix2-section { position:relative; padding:140px 0 160px; overflow:hidden; background:#0a0913; color:white; isolation:isolate; }
        .ix2-bg { position:absolute; inset:0; pointer-events:none; overflow:hidden; z-index:0; }
        .ix2-bg::before { content:''; position:absolute; inset:0; background:
            radial-gradient(ellipse 80% 60% at 20% 0%, rgba(124,92,255,.18), transparent 60%),
            radial-gradient(ellipse 70% 50% at 80% 100%, rgba(217,70,239,.15), transparent 60%),
            radial-gradient(ellipse 60% 50% at 50% 50%, rgba(14,165,233,.08), transparent 70%);
        }
        .ix2-grid { position:absolute; inset:0; opacity:.06; background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px); background-size:80px 80px; mask-image:radial-gradient(ellipse 60% 50% at center,black,transparent 75%); -webkit-mask-image:radial-gradient(ellipse 60% 50% at center,black,transparent 75%); }
        .ix2-noise { position:absolute; inset:0; opacity:.018; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E"); }
        .ix2-stars { position:absolute; inset:0; }
        .ix2-stars span { position:absolute; width:2px; height:2px; border-radius:50%; background:white; opacity:0; animation:ix2-twinkle 4s ease-in-out infinite; }
        @keyframes ix2-twinkle { 0%,100% { opacity:0; transform:scale(.5); } 50% { opacity:.8; transform:scale(1); } }

        /* HEADER */
        .ix2-eyebrow { display:inline-flex; align-items:center; gap:10px; padding:7px 18px; border-radius:999px; background:linear-gradient(135deg,rgba(124,92,255,.12),rgba(217,70,239,.08)); border:1px solid rgba(124,92,255,.25); backdrop-filter:blur(12px); font-size:11px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:rgba(255,255,255,.92); }
        .ix2-eyebrow-dot { width:8px; height:8px; border-radius:50%; background:#10b981; box-shadow:0 0 14px #10b981; animation:ix2-dot 2s ease-in-out infinite; }
        @keyframes ix2-dot { 0%,100% { box-shadow:0 0 0 0 rgba(16,185,129,.7); } 50% { box-shadow:0 0 0 10px rgba(16,185,129,0); } }

        .ix2-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(2.2rem,4vw + 1rem,4rem); letter-spacing:-.04em; line-height:1.02; text-wrap:balance; }
        .ix2-grad { background:linear-gradient(120deg,#a78bfa 0%,#d946ef 35%,#f0abfc 60%,#7c5cff 100%); background-size:300% 300%; -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; animation:ix2-grad 8s ease-in-out infinite; }
        @keyframes ix2-grad { 0%,100% { background-position:0% 50%; } 50% { background-position:100% 50%; } }
        .ix2-sub { font-size:16px; color:rgba(255,255,255,.62); line-height:1.65; max-width:620px; margin:24px auto 0; }

        /* SHOWCASE - left source / center connectors / right notifications */
        .ix2-showcase { position:relative; margin:90px auto 0; max-width:1180px; display:grid; grid-template-columns: 1fr 1fr 1fr; gap:32px; align-items:center; }
        @media (max-width:1024px) { .ix2-showcase { grid-template-columns:1fr; gap:48px; max-width:640px; } }

        /* SOURCE CARD (Kydesk event) */
        .ix2-source { position:relative; }
        .ix2-source-card { position:relative; padding:22px; border-radius:22px; background:linear-gradient(135deg,#16151b,#1a1825); border:1px solid rgba(124,92,255,.3); box-shadow:0 30px 80px -20px rgba(124,92,255,.4), inset 0 1px 0 rgba(255,255,255,.05); overflow:hidden; }
        .ix2-source-card::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(124,92,255,.08),transparent 50%); pointer-events:none; }
        .ix2-source-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; position:relative; }
        .ix2-source-logo { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,#7c5cff,#d946ef); display:grid; place-items:center; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; color:white; font-size:18px; box-shadow:0 8px 16px -4px rgba(124,92,255,.5); }
        .ix2-source-meta { font-size:10.5px; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:rgba(255,255,255,.5); }
        .ix2-source-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:14.5px; color:white; }
        .ix2-source-event { display:inline-flex; align-items:center; gap:6px; margin-top:4px; padding:3px 10px; border-radius:999px; background:rgba(16,185,129,.15); border:1px solid rgba(16,185,129,.4); font-family:'Geist Mono',monospace; font-size:11px; color:#34d399; }
        .ix2-source-event::before { content:''; width:6px; height:6px; border-radius:50%; background:#10b981; animation:ix2-dot 1.5s ease-in-out infinite; }
        .ix2-payload { margin-top:14px; padding:14px; border-radius:12px; background:rgba(0,0,0,.35); border:1px solid rgba(255,255,255,.06); font-family:'Geist Mono',monospace; font-size:11.5px; line-height:1.7; color:rgba(255,255,255,.82); position:relative; overflow:hidden; }
        .ix2-payload .k { color:#a78bfa; }
        .ix2-payload .s { color:#86efac; }
        .ix2-payload .n { color:#fcd34d; }
        .ix2-payload-cursor { display:inline-block; width:6px; height:13px; background:#a78bfa; vertical-align:text-bottom; animation:ix2-cursor 1s steps(2) infinite; margin-left:1px; }
        @keyframes ix2-cursor { 50% { opacity:0; } }

        .ix2-source-floats { position:absolute; inset:-30px; pointer-events:none; }
        .ix2-float-pill { position:absolute; padding:5px 11px; border-radius:999px; backdrop-filter:blur(12px); background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); font-size:10.5px; font-weight:600; color:rgba(255,255,255,.85); display:inline-flex; align-items:center; gap:6px; animation:ix2-float-up 8s ease-in-out infinite; }
        .ix2-float-pill:nth-child(1) { top:-10px; right:20px; animation-delay:0s; }
        .ix2-float-pill:nth-child(2) { bottom:0; right:-15px; animation-delay:2s; }
        .ix2-float-pill:nth-child(3) { top:30%; left:-20px; animation-delay:4s; }
        @keyframes ix2-float-up { 0%,100% { transform:translateY(0); opacity:.7; } 50% { transform:translateY(-12px); opacity:1; } }

        /* CONNECTOR (center) */
        .ix2-connector { position:relative; height:100%; min-height:380px; display:flex; align-items:center; justify-content:center; }
        .ix2-connector svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
        .ix2-pipe { fill:none; stroke:url(#ix2-pipeGrad); stroke-width:2; stroke-linecap:round; }
        .ix2-pipe-bg { fill:none; stroke:rgba(255,255,255,.06); stroke-width:2; stroke-linecap:round; stroke-dasharray:4 6; }
        .ix2-spark { fill:#a78bfa; filter:drop-shadow(0 0 8px #a78bfa) drop-shadow(0 0 14px rgba(167,139,250,.6)); }
        .ix2-hub { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:96px; height:96px; border-radius:24px; background:radial-gradient(circle at 30% 30%,#a78bfa,#7c5cff 50%,#5b21b6 100%); display:grid; place-items:center; box-shadow:0 20px 50px -10px rgba(124,92,255,.55), inset 0 2px 0 rgba(255,255,255,.2); animation:ix2-hub 4s ease-in-out infinite; z-index:10; }
        .ix2-hub::before, .ix2-hub::after { content:''; position:absolute; inset:0; border-radius:24px; border:2px solid rgba(167,139,250,.6); animation:ix2-hub-ripple 3s ease-out infinite; }
        .ix2-hub::after { animation-delay:1.5s; }
        @keyframes ix2-hub { 0%,100% { transform:translate(-50%,-50%) scale(1); } 50% { transform:translate(-50%,-50%) scale(1.04); } }
        @keyframes ix2-hub-ripple { 0% { transform:scale(1); opacity:.7; } 100% { transform:scale(1.8); opacity:0; } }
        .ix2-hub i { color:white; font-size:36px; }
        .ix2-hub-label { position:absolute; top:calc(100% + 14px); left:50%; transform:translateX(-50%); font-family:'Geist Mono',monospace; font-size:11px; color:rgba(255,255,255,.55); white-space:nowrap; }

        /* NOTIFICATIONS (right) — stacked cards */
        .ix2-notifs { display:flex; flex-direction:column; gap:14px; position:relative; }
        .ix2-notif { padding:16px 18px; border-radius:18px; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); backdrop-filter:blur(14px); position:relative; overflow:hidden; transform-origin:left center; animation:ix2-notif-in 1s cubic-bezier(.2,.9,.3,1.2) backwards; }
        .ix2-notif:nth-child(1) { animation-delay:.1s; }
        .ix2-notif:nth-child(2) { animation-delay:.4s; }
        .ix2-notif:nth-child(3) { animation-delay:.7s; }
        .ix2-notif::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; }
        @keyframes ix2-notif-in { 0% { transform:translateX(40px); opacity:0; } 100% { transform:translateX(0); opacity:1; } }
        .ix2-notif:hover { background:rgba(255,255,255,.07); border-color:rgba(255,255,255,.18); }
        .ix2-notif-head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
        .ix2-notif-icon { width:30px; height:30px; border-radius:8px; display:grid; place-items:center; flex-shrink:0; }
        .ix2-notif-app { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:12.5px; color:white; }
        .ix2-notif-time { font-size:10.5px; color:rgba(255,255,255,.4); margin-left:auto; }
        .ix2-notif-msg { font-size:12.5px; color:rgba(255,255,255,.78); line-height:1.55; }
        .ix2-notif-msg strong { color:white; font-weight:700; }
        .ix2-notif-tags { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
        .ix2-notif-tag { font-size:10px; padding:2px 7px; border-radius:6px; background:rgba(255,255,255,.06); color:rgba(255,255,255,.65); font-family:'Geist Mono',monospace; }

        /* MARQUEE - infinite scroll of providers */
        .ix2-marquee-wrap { margin-top:90px; position:relative; padding:24px 0; border-top:1px solid rgba(255,255,255,.06); border-bottom:1px solid rgba(255,255,255,.06); overflow:hidden; }
        .ix2-marquee-wrap::before, .ix2-marquee-wrap::after { content:''; position:absolute; top:0; bottom:0; width:120px; z-index:2; pointer-events:none; }
        .ix2-marquee-wrap::before { left:0; background:linear-gradient(90deg,#0a0913,transparent); }
        .ix2-marquee-wrap::after { right:0; background:linear-gradient(-90deg,#0a0913,transparent); }
        .ix2-marquee { display:flex; gap:48px; width:max-content; animation:ix2-scroll 40s linear infinite; }
        .ix2-marquee:hover { animation-play-state:paused; }
        @keyframes ix2-scroll { from { transform:translateX(0); } to { transform:translateX(-50%); } }
        .ix2-mq-item { display:inline-flex; align-items:center; gap:10px; padding:10px 18px; border-radius:14px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); transition:all .25s; flex-shrink:0; }
        .ix2-mq-item:hover { background:rgba(255,255,255,.07); border-color:rgba(255,255,255,.18); transform:translateY(-2px); }
        .ix2-mq-item-icon { width:24px; height:24px; border-radius:6px; display:grid; place-items:center; flex-shrink:0; }
        .ix2-mq-item-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:13px; color:rgba(255,255,255,.92); white-space:nowrap; }

        /* PROVIDER GRID with 3D tilt feel */
        .ix2-grid-cards { margin-top:80px; display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; max-width:1180px; margin-left:auto; margin-right:auto; perspective:1200px; }
        .ix2-card { position:relative; padding:22px; border-radius:20px; background:rgba(255,255,255,.025); border:1px solid rgba(255,255,255,.08); backdrop-filter:blur(20px); transition:transform .35s cubic-bezier(.2,.9,.3,1), border-color .25s, background .25s; overflow:hidden; transform-style:preserve-3d; }
        .ix2-card::before { content:''; position:absolute; inset:0; border-radius:20px; padding:1px; background:linear-gradient(135deg,transparent 30%,var(--ix-glow,#7c5cff) 50%,transparent 70%); -webkit-mask:linear-gradient(white,white) content-box,linear-gradient(white,white); -webkit-mask-composite:xor; mask-composite:exclude; opacity:0; transition:opacity .3s; pointer-events:none; }
        .ix2-card::after { content:''; position:absolute; top:-50%; left:-50%; width:200%; height:200%; background:radial-gradient(circle at center,var(--ix-glow,#7c5cff)20,transparent 50%); opacity:0; transition:opacity .35s; pointer-events:none; }
        .ix2-card:hover { transform:translateY(-6px) rotateX(2deg); border-color:transparent; background:rgba(255,255,255,.04); }
        .ix2-card:hover::before, .ix2-card:hover::after { opacity:1; }
        .ix2-card-icon { position:relative; width:48px; height:48px; border-radius:14px; display:grid; place-items:center; margin-bottom:14px; }
        .ix2-card-cat { position:relative; font-size:10px; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:rgba(255,255,255,.4); margin-bottom:4px; }
        .ix2-card-name { position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15.5px; color:white; letter-spacing:-.01em; }
        .ix2-card-desc { position:relative; font-size:12px; color:rgba(255,255,255,.55); line-height:1.5; margin-top:6px; }
        .ix2-card-status { position:absolute; top:18px; right:18px; display:inline-flex; align-items:center; gap:5px; font-size:10px; color:rgba(16,185,129,.9); font-weight:700; }
        .ix2-card-status::before { content:''; width:6px; height:6px; border-radius:50%; background:#10b981; box-shadow:0 0 8px #10b981; }

        /* STATS STRIP */
        .ix2-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1px; margin-top:60px; padding:0; border-radius:24px; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.06); backdrop-filter:blur(14px); max-width:880px; margin-left:auto; margin-right:auto; overflow:hidden; }
        .ix2-stat { padding:28px 24px; text-align:center; background:rgba(10,9,19,.5); position:relative; }
        .ix2-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:38px; letter-spacing:-.03em; line-height:1; }
        .ix2-stat-value.grad { background:linear-gradient(135deg,#a78bfa,#d946ef,#f0abfc); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
        .ix2-stat-label { font-size:11.5px; color:rgba(255,255,255,.55); margin-top:6px; }

        /* CTA */
        .ix2-cta-row { text-align:center; margin-top:48px; }
        .ix2-cta { display:inline-flex; align-items:center; gap:10px; padding:14px 30px; border-radius:14px; background:linear-gradient(135deg,#7c5cff,#a78bfa,#d946ef); background-size:200% 200%; color:white; font-weight:600; font-size:14px; box-shadow:0 18px 40px -10px rgba(124,92,255,.6), inset 0 1px 0 rgba(255,255,255,.2); transition:transform .25s, box-shadow .25s, background-position .8s; text-decoration:none; }
        .ix2-cta:hover { transform:translateY(-2px); background-position:100% 50%; box-shadow:0 22px 50px -8px rgba(124,92,255,.85); }

        /* Mobile tweaks */
        @media (max-width:1024px) {
            .ix2-connector { min-height:140px; }
            .ix2-hub { width:78px; height:78px; }
            .ix2-hub i { font-size:30px; }
        }
        @media (max-width:640px) {
            .ix2-section { padding:90px 0 110px; }
            .ix2-source-card { padding:18px; }
            .ix2-stats { grid-template-columns:repeat(2, 1fr); }
            .ix2-stat-value { font-size:28px; }
        }
    </style>

    <div class="ix2-bg">
        <div class="ix2-grid"></div>
        <div class="ix2-noise"></div>
        <div class="ix2-stars">
            <?php for ($i = 0; $i < 30; $i++):
                $top = rand(0, 100); $left = rand(0, 100); $delay = rand(0, 40) / 10;
            ?>
                <span style="top:<?= $top ?>%;left:<?= $left ?>%;animation-delay:<?= $delay ?>s"></span>
            <?php endfor; ?>
        </div>
    </div>

    <div class="max-w-[1240px] mx-auto px-6 relative">

        <!-- HEADER -->
        <div class="text-center reveal" data-reveal>
            <div class="ix2-eyebrow">
                <span class="ix2-eyebrow-dot"></span>
                <span><?= __e('ix.eyebrow') ?></span>
            </div>
            <h2 class="ix2-title mt-7">
                <?= __e('ix.title_pre') ?><br>
                <span class="ix2-grad"><?= __e('ix.title_post') ?></span>.
            </h2>
            <p class="ix2-sub">
                <?= __e('ix.sub') ?>
            </p>
        </div>

        <!-- LIVE EVENT FLOW -->
        <div class="ix2-showcase reveal" data-reveal>

            <!-- LEFT: Source -->
            <div class="ix2-source">
                <div class="ix2-source-card">
                    <div class="ix2-source-head">
                        <div class="ix2-source-logo">K</div>
                        <div class="flex-1">
                            <div class="ix2-source-meta"><?= __e('ix.source_meta') ?></div>
                            <div class="ix2-source-title"><?= __e('ix.source_title') ?></div>
                        </div>
                    </div>
                    <div class="ix2-source-event">ticket.created</div>
                    <div class="ix2-payload">
                        <div>{</div>
                        <div>&nbsp;&nbsp;<span class="k">"code"</span>: <span class="s">"TKT-1042"</span>,</div>
                        <div>&nbsp;&nbsp;<span class="k">"subject"</span>: <span class="s">"VPN se desconecta"</span>,</div>
                        <div>&nbsp;&nbsp;<span class="k">"priority"</span>: <span class="s">"high"</span>,</div>
                        <div>&nbsp;&nbsp;<span class="k">"requester"</span>: <span class="s">"maria@acme.com"</span><span class="ix2-payload-cursor"></span></div>
                        <div>}</div>
                    </div>
                </div>
                <div class="ix2-source-floats">
                    <span class="ix2-float-pill"><i class="lucide lucide-check text-emerald-400 text-[10px]"></i> <?= __e('ix.source_pill1') ?></span>
                    <span class="ix2-float-pill"><i class="lucide lucide-zap text-amber-400 text-[10px]"></i> 38ms</span>
                    <span class="ix2-float-pill"><i class="lucide lucide-shield text-blue-400 text-[10px]"></i> <?= __e('ix.source_pill3') ?></span>
                </div>
            </div>

            <!-- CENTER: Connector with traveling particles -->
            <div class="ix2-connector">
                <svg viewBox="0 0 360 380" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="ix2-pipeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#7c5cff" stop-opacity="0.1"/>
                            <stop offset="50%" stop-color="#a78bfa" stop-opacity="0.7"/>
                            <stop offset="100%" stop-color="#d946ef" stop-opacity="0.3"/>
                        </linearGradient>
                        <radialGradient id="ix2-sparkGrad">
                            <stop offset="0%" stop-color="#fff" stop-opacity="1"/>
                            <stop offset="60%" stop-color="#a78bfa" stop-opacity=".9"/>
                            <stop offset="100%" stop-color="#7c5cff" stop-opacity="0"/>
                        </radialGradient>
                    </defs>

                    <!-- Pipe backgrounds (dashed) -->
                    <path class="ix2-pipe-bg" d="M 0 80 Q 90 80 180 190"/>
                    <path class="ix2-pipe-bg" d="M 0 190 Q 90 190 180 190"/>
                    <path class="ix2-pipe-bg" d="M 0 300 Q 90 300 180 190"/>

                    <path class="ix2-pipe-bg" d="M 180 190 Q 270 80 360 80"/>
                    <path class="ix2-pipe-bg" d="M 180 190 Q 270 190 360 190"/>
                    <path class="ix2-pipe-bg" d="M 180 190 Q 270 300 360 300"/>

                    <!-- Active pipes with gradient -->
                    <path class="ix2-pipe" d="M 0 80 Q 90 80 180 190"/>
                    <path class="ix2-pipe" d="M 0 190 Q 90 190 180 190"/>
                    <path class="ix2-pipe" d="M 0 300 Q 90 300 180 190"/>
                    <path class="ix2-pipe" d="M 180 190 Q 270 80 360 80"/>
                    <path class="ix2-pipe" d="M 180 190 Q 270 190 360 190"/>
                    <path class="ix2-pipe" d="M 180 190 Q 270 300 360 300"/>

                    <!-- Traveling particles (in/out) -->
                    <circle class="ix2-spark" r="4">
                        <animateMotion dur="2s" repeatCount="indefinite" begin="0s" path="M 0 80 Q 90 80 180 190"/>
                    </circle>
                    <circle class="ix2-spark" r="4">
                        <animateMotion dur="2s" repeatCount="indefinite" begin=".7s" path="M 0 190 Q 90 190 180 190"/>
                    </circle>
                    <circle class="ix2-spark" r="4">
                        <animateMotion dur="2s" repeatCount="indefinite" begin="1.3s" path="M 0 300 Q 90 300 180 190"/>
                    </circle>

                    <circle class="ix2-spark" r="3.5">
                        <animateMotion dur="1.8s" repeatCount="indefinite" begin=".3s" path="M 180 190 Q 270 80 360 80"/>
                    </circle>
                    <circle class="ix2-spark" r="3.5">
                        <animateMotion dur="1.8s" repeatCount="indefinite" begin="1s" path="M 180 190 Q 270 190 360 190"/>
                    </circle>
                    <circle class="ix2-spark" r="3.5">
                        <animateMotion dur="1.8s" repeatCount="indefinite" begin="1.6s" path="M 180 190 Q 270 300 360 300"/>
                    </circle>
                </svg>
                <div class="ix2-hub">
                    <i class="lucide lucide-plug"></i>
                    <div class="ix2-hub-label">events.dispatch()</div>
                </div>
            </div>

            <!-- RIGHT: Notifications -->
            <div class="ix2-notifs">
                <!-- Slack -->
                <div class="ix2-notif" style="--ix-glow:#4A154B">
                    <div class="ix2-notif-head">
                        <div class="ix2-notif-icon" style="background:#4A154B;color:white"><i class="lucide lucide-slack text-[14px]"></i></div>
                        <div>
                            <div class="ix2-notif-app">Slack <span style="color:rgba(255,255,255,.4);font-weight:500">· #soporte-urgente</span></div>
                        </div>
                        <div class="ix2-notif-time"><?= __e('common.now') ?></div>
                    </div>
                    <div class="ix2-notif-msg"><?= __('ix.notif_slack_msg') ?></div>
                    <div class="ix2-notif-tags">
                        <span class="ix2-notif-tag">priority: high</span>
                        <span class="ix2-notif-tag">maria@acme.com</span>
                    </div>
                </div>

                <!-- Discord -->
                <div class="ix2-notif" style="--ix-glow:#5865F2">
                    <div class="ix2-notif-head">
                        <div class="ix2-notif-icon" style="background:#5865F2;color:white"><i class="lucide lucide-message-square text-[14px]"></i></div>
                        <div>
                            <div class="ix2-notif-app">Discord <span style="color:rgba(255,255,255,.4);font-weight:500">· #alertas</span></div>
                        </div>
                        <div class="ix2-notif-time"><?= __e('common.now') ?></div>
                    </div>
                    <div class="ix2-notif-msg"><?= __('ix.notif_discord_msg') ?></div>
                    <div class="ix2-notif-tags">
                        <span class="ix2-notif-tag">embed</span>
                        <span class="ix2-notif-tag">color: #3b82f6</span>
                    </div>
                </div>

                <!-- Telegram -->
                <div class="ix2-notif" style="--ix-glow:#0088CC">
                    <div class="ix2-notif-head">
                        <div class="ix2-notif-icon" style="background:#0088CC;color:white"><i class="lucide lucide-send text-[14px]"></i></div>
                        <div>
                            <div class="ix2-notif-app">Telegram <span style="color:rgba(255,255,255,.4);font-weight:500">· @kydesk_bot</span></div>
                        </div>
                        <div class="ix2-notif-time"><?= __e('common.now') ?></div>
                    </div>
                    <div class="ix2-notif-msg"><?= __('ix.notif_telegram_msg') ?></div>
                    <div class="ix2-notif-tags">
                        <span class="ix2-notif-tag">parse_mode: HTML</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- MARQUEE -->
        <div class="ix2-marquee-wrap reveal" data-reveal>
            <div class="ix2-marquee">
                <?php
                $marqueeItems = [
                    ['Slack',          'slack',          '#4A154B'],
                    ['Discord',        'message-square', '#5865F2'],
                    ['Telegram',       'send',           '#0088CC'],
                    ['Microsoft Teams','users-2',        '#5059C9'],
                    ['Zapier',         'zap',            '#FF4A00'],
                    ['n8n',            'workflow',       '#EA4B71'],
                    ['Make',           'cpu',            '#6D00CC'],
                    ['Webhook',        'webhook',        '#0ea5e9'],
                    ['Email',          'mail',           '#0EA5E9'],
                    ['Pushover',       'bell',           '#249DF1'],
                    ['Mattermost',     'message-circle', '#0058CC'],
                    ['Rocket.Chat',    'rocket',         '#F5455C'],
                ];
                // Render twice for seamless loop
                for ($pass = 0; $pass < 2; $pass++):
                    foreach ($marqueeItems as [$name, $icon, $color]): ?>
                        <div class="ix2-mq-item">
                            <div class="ix2-mq-item-icon" style="background:<?= $color ?>20"><i class="lucide lucide-<?= $icon ?> text-[12px]" style="color:<?= $color ?>"></i></div>
                            <span class="ix2-mq-item-name"><?= $name ?></span>
                        </div>
                    <?php endforeach;
                endfor; ?>
            </div>
        </div>

        <!-- PROVIDER CARDS GRID -->
        <div class="ix2-grid-cards reveal-stagger" data-reveal>
            <?php
            $grid = [
                ['Slack',          'slack',          '#4A154B', __('ix.cat_chat'),       __('ix.card_slack_desc')],
                ['Discord',        'message-square', '#5865F2', __('ix.cat_chat'),       __('ix.card_discord_desc')],
                ['Telegram',       'send',           '#0088CC', __('ix.cat_chat'),       __('ix.card_telegram_desc')],
                ['Microsoft Teams','users-2',        '#5059C9', __('ix.cat_chat'),       __('ix.card_teams_desc')],
                ['Zapier',         'zap',            '#FF4A00', __('ix.cat_automation'), __('ix.card_zapier_desc')],
                ['n8n',            'workflow',       '#EA4B71', __('ix.cat_automation'), __('ix.card_n8n_desc')],
                ['Make',           'cpu',            '#6D00CC', __('ix.cat_automation'), __('ix.card_make_desc')],
                ['Webhook',        'webhook',        '#0ea5e9', __('ix.cat_devops'),     __('ix.card_webhook_desc')],
            ];
            foreach ($grid as [$name, $icon, $color, $cat, $desc]): ?>
                <div class="ix2-card" style="--ix-glow:<?= $color ?>">
                    <div class="ix2-card-status"><?= __e('common.active') ?></div>
                    <div class="ix2-card-icon" style="background:<?= $color ?>22; border:1px solid <?= $color ?>50">
                        <i class="lucide lucide-<?= $icon ?> text-[20px]" style="color:<?= $color ?>"></i>
                    </div>
                    <div class="ix2-card-cat"><?= $e($cat) ?></div>
                    <div class="ix2-card-name"><?= $e($name) ?></div>
                    <div class="ix2-card-desc"><?= $e($desc) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- STATS -->
        <div class="ix2-stats reveal" data-reveal>
            <div class="ix2-stat">
                <div class="ix2-stat-value grad">12+</div>
                <div class="ix2-stat-label"><?= __e('ix.stat_providers') ?></div>
            </div>
            <div class="ix2-stat">
                <div class="ix2-stat-value grad">12</div>
                <div class="ix2-stat-label"><?= __e('ix.stat_events') ?></div>
            </div>
            <div class="ix2-stat">
                <div class="ix2-stat-value grad">&lt;2 min</div>
                <div class="ix2-stat-label"><?= __e('ix.stat_setup') ?></div>
            </div>
            <div class="ix2-stat">
                <div class="ix2-stat-value grad">∞</div>
                <div class="ix2-stat-label"><?= __e('ix.stat_enterprise') ?></div>
            </div>
        </div>

        <!-- CTA -->
        <div class="ix2-cta-row reveal" data-reveal>
            <a href="<?= $url('/features/integrations') ?>" class="ix2-cta">
                <i class="lucide lucide-plug text-[15px]"></i>
                <?= __e('ix.cta') ?>
                <i class="lucide lucide-arrow-right text-[15px]"></i>
            </a>
            <div class="mt-5 inline-flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[12.5px]" style="color:rgba(255,255,255,.5)">
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= __e('ix.bullet1') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= __e('ix.bullet2') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= __e('ix.bullet3') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-400 text-[13px]"></i> <?= __e('ix.bullet4') ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ========== API SECTION (rediseñada) ========== -->
<section id="api" class="apx-section">
    <!-- Decorative background -->
    <div class="apx-bg">
        <div class="apx-glow apx-glow-1"></div>
        <div class="apx-glow apx-glow-2"></div>
    </div>

    <div class="max-w-[1240px] mx-auto px-6 relative">

        <!-- Heading -->
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="apx-eyebrow">
                <span class="apx-eyebrow-dot"></span>
                <span class="font-mono">REST API</span>
                <span class="apx-eyebrow-sep">·</span>
                <span><?= __e('api.eyebrow_stable') ?></span>
            </div>
            <h2 class="apx-title mt-6">
                <?= __e('api.title_pre') ?> <span class="apx-grad"><?= __e('api.title_post') ?></span>.
            </h2>
            <p class="apx-sub mt-6">
                <?= __e('api.sub') ?>
            </p>
        </div>

        <!-- Big terminal mockup -->
        <div class="mt-14 max-w-[980px] mx-auto reveal" data-reveal>
            <div class="apx-window" x-data="apiDemo()" x-init="start()">
                <!-- Window chrome -->
                <div class="apx-chrome">
                    <div class="apx-chrome-dots">
                        <span style="background:#ff5f57"></span>
                        <span style="background:#febc2e"></span>
                        <span style="background:#28c840"></span>
                    </div>
                    <div class="apx-chrome-url">
                        <i class="lucide lucide-lock text-[10px]"></i>
                        <span class="font-mono">api.kydesk.kyrosrd.com</span>
                    </div>
                    <button @click="copy()" class="apx-copy-btn">
                        <i class="lucide lucide-copy text-[12px]"></i>
                        <span x-text="copied ? <?= htmlspecialchars(json_encode(__('api.copied')), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(__('api.copy')), ENT_QUOTES) ?>"></span>
                    </button>
                </div>

                <!-- Method tabs -->
                <div class="apx-tabs">
                    <template x-for="(t, i) in tabs" :key="i">
                        <button @click="switchTab(i)" :class="active===i?'apx-tab-on':''" class="apx-tab">
                            <span class="apx-tab-method" :style="`background:${methodColors[t.method]}`" x-text="t.method"></span>
                            <span x-text="t.label"></span>
                        </button>
                    </template>
                </div>

                <!-- Body: split request | response -->
                <div class="apx-split">
                    <!-- Request -->
                    <div class="apx-pane">
                        <div class="apx-pane-header">
                            <span class="apx-pane-label"><i class="lucide lucide-send text-[11px]"></i> <?= __e('api.request') ?></span>
                        </div>
                        <pre class="apx-code"><code x-html="rendered"></code><span class="apx-caret"></span></pre>
                    </div>
                    <!-- Response -->
                    <div class="apx-pane apx-pane-response">
                        <div class="apx-pane-header">
                            <span class="apx-pane-label"><i class="lucide lucide-arrow-down-left text-[11px]"></i> <?= __e('api.response') ?></span>
                            <span class="apx-status" x-show="showResponse" x-cloak>
                                <span class="apx-status-dot"></span>
                                <span class="font-mono" x-text="statusLine"></span>
                            </span>
                        </div>
                        <pre class="apx-code apx-code-response" x-show="showResponse" x-cloak><code x-html="responseRendered"></code></pre>
                        <div class="apx-loading" x-show="!showResponse" x-cloak>
                            <div class="apx-loading-bar"></div>
                            <div class="apx-loading-bar" style="width:80%"></div>
                            <div class="apx-loading-bar" style="width:60%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capability strip -->
        <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-3 max-w-[980px] mx-auto reveal" data-reveal>
            <?php foreach ([
                [__('api.feat_bearer_label'), 'key',     '#7c5cff', __('api.feat_bearer_sub')],
                [__('api.feat_latency_label'),'zap',     '#10b981', __('api.feat_latency_sub')],
                [__('api.feat_uptime_label'), 'activity','#38bdf8', __('api.feat_uptime_sub')],
                [__('api.feat_rate_label'),   'timer',   '#f59e0b', __('api.feat_rate_sub')],
            ] as [$lbl, $ic, $c, $sub]): ?>
                <div class="apx-feat" style="--c:<?= $c ?>">
                    <div class="apx-feat-icon"><i class="lucide lucide-<?= $ic ?>"></i></div>
                    <div class="apx-feat-label"><?= $e($lbl) ?></div>
                    <div class="apx-feat-sub"><?= $e($sub) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Endpoints marquee -->
        <div class="mt-14 reveal" data-reveal>
            <div class="text-center text-[11px] font-bold uppercase tracking-[0.2em] text-ink-400 mb-4"><?= __e('api.endpoints_caption') ?> <code class="font-mono text-ink-700">/api/v1</code></div>
            <div class="apx-marquee">
                <div class="apx-marquee-track">
                    <?php
                    $endpoints = [
                        ['GET',    '/tickets'],
                        ['POST',   '/tickets'],
                        ['GET',    '/tickets/{id}'],
                        ['PATCH',  '/tickets/{id}'],
                        ['DELETE', '/tickets/{id}'],
                        ['POST',   '/tickets/{id}/comments'],
                        ['GET',    '/tickets/{id}/comments'],
                        ['GET',    '/categories'],
                        ['POST',   '/categories'],
                        ['GET',    '/companies'],
                        ['POST',   '/companies'],
                        ['GET',    '/users'],
                        ['GET',    '/kb/articles'],
                        ['GET',    '/sla'],
                        ['GET',    '/automations'],
                        ['GET',    '/stats'],
                    ];
                    $methodColors = ['GET'=>'#10b981','POST'=>'#7c5cff','PATCH'=>'#f59e0b','DELETE'=>'#ef4444'];
                    for ($r = 0; $r < 2; $r++):
                        foreach ($endpoints as [$m, $p]): ?>
                            <div class="apx-endpoint">
                                <span class="apx-endpoint-method" style="background:<?= $methodColors[$m] ?>"><?= $m ?></span>
                                <code class="apx-endpoint-path"><?= $p ?></code>
                            </div>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
        </div>

        <!-- SDK examples -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-4 max-w-[1100px] mx-auto reveal" data-reveal>
            <?php foreach ([
                ['cURL', 'terminal', '#10b981', "curl https://kydesk.kyrosrd.com/api/v1/tickets \\\n  -H \"Authorization: Bearer kyd_xxx\""],
                ['JavaScript', 'braces', '#f7df1e', "fetch('/api/v1/tickets', {\n  headers: {\n    Authorization: 'Bearer kyd_xxx'\n  }\n}).then(r => r.json())"],
                ['Python', 'braces', '#3776ab', "import requests\n\nrequests.get(url, headers={\n  'Authorization': f'Bearer {token}'\n}).json()"],
            ] as [$lang, $ic, $c, $code]): ?>
                <div class="apx-sdk">
                    <div class="apx-sdk-head">
                        <div class="apx-sdk-icon" style="background:<?= $c ?>20;color:<?= $c ?>"><i class="lucide lucide-<?= $ic ?>"></i></div>
                        <span class="apx-sdk-lang"><?= $lang ?></span>
                        <button onclick="navigator.clipboard.writeText(this.parentElement.parentElement.querySelector('code').innerText);this.innerHTML='<i class=&quot;lucide lucide-check text-[12px]&quot;></i>'" class="apx-sdk-copy">
                            <i class="lucide lucide-copy text-[12px]"></i>
                        </button>
                    </div>
                    <pre class="apx-sdk-code"><code><?= $e($code) ?></code></pre>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA -->
        <div class="mt-16 text-center reveal" data-reveal>
            <a href="<?= $url('/auth/register') ?>" class="apx-cta">
                <i class="lucide lucide-key"></i>
                <?= __e('api.cta') ?>
                <i class="lucide lucide-arrow-right text-[14px]"></i>
            </a>
            <div class="mt-5 flex items-center justify-center gap-5 text-[12px] text-ink-400">
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600 text-[13px]"></i> <?= __e('api.cta_b1') ?></span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600 text-[13px]"></i> <?= __e('api.cta_b2') ?></span>
            </div>
        </div>
    </div>
</section>

<script>
function apiDemo() {
    return {
        active: 0,
        copied: false,
        showResponse: false,
        rendered: '',
        responseRendered: '',
        statusLine: '',
        timer: null,
        methodColors: { GET: '#10b981', POST: '#7c5cff', PATCH: '#f59e0b', DELETE: '#ef4444' },
        tabs: [
            { method: 'POST', label: <?= json_encode(__('api.tab_create')) ?> },
            { method: 'GET',  label: <?= json_encode(__('api.tab_list')) ?> },
            { method: 'GET',  label: <?= json_encode(__('api.tab_metrics')) ?> },
        ],
        snippets: [
            {
                code: `<span class="ax-kw">POST</span> <span class="ax-fn">/api/v1/tickets</span>
<span class="ax-com">Authorization:</span> <span class="ax-str">Bearer kyd_xxx</span>
<span class="ax-com">Content-Type:</span> <span class="ax-str">application/json</span>

{
  <span class="ax-key">"subject"</span>: <span class="ax-str">"VPN se desconecta"</span>,
  <span class="ax-key">"priority"</span>: <span class="ax-str">"high"</span>,
  <span class="ax-key">"requester_email"</span>: <span class="ax-str">"maria@acme.com"</span>
}`,
                status: '201 Created · 38ms',
                response: `{
  <span class="ax-key">"data"</span>: {
    <span class="ax-key">"id"</span>: <span class="ax-num">847</span>,
    <span class="ax-key">"code"</span>: <span class="ax-str">"TK-01-00847"</span>,
    <span class="ax-key">"status"</span>: <span class="ax-str">"open"</span>,
    <span class="ax-key">"priority"</span>: <span class="ax-str">"high"</span>,
    <span class="ax-key">"sla_due_at"</span>: <span class="ax-str">"2026-04-26T15:42Z"</span>
  }
}`
            },
            {
                code: `<span class="ax-kw">GET</span> <span class="ax-fn">/api/v1/tickets</span>?status=open
<span class="ax-com">Authorization:</span> <span class="ax-str">Bearer kyd_xxx</span>`,
                status: '200 OK · 24ms',
                response: `{
  <span class="ax-key">"data"</span>: [
    { <span class="ax-key">"id"</span>: <span class="ax-num">847</span>, <span class="ax-key">"subject"</span>: <span class="ax-str">"VPN…"</span>,      <span class="ax-key">"priority"</span>: <span class="ax-str">"high"</span> },
    { <span class="ax-key">"id"</span>: <span class="ax-num">846</span>, <span class="ax-key">"subject"</span>: <span class="ax-str">"Impresora…"</span>, <span class="ax-key">"priority"</span>: <span class="ax-str">"medium"</span> },
    { <span class="ax-key">"id"</span>: <span class="ax-num">845</span>, <span class="ax-key">"subject"</span>: <span class="ax-str">"Login…"</span>,    <span class="ax-key">"priority"</span>: <span class="ax-str">"low"</span> }
  ],
  <span class="ax-key">"meta"</span>: { <span class="ax-key">"total"</span>: <span class="ax-num">42</span> }
}`
            },
            {
                code: `<span class="ax-kw">GET</span> <span class="ax-fn">/api/v1/stats</span>
<span class="ax-com">Authorization:</span> <span class="ax-str">Bearer kyd_xxx</span>`,
                status: '200 OK · 18ms',
                response: `{
  <span class="ax-key">"data"</span>: {
    <span class="ax-key">"tickets"</span>: {
      <span class="ax-key">"total"</span>: <span class="ax-num">12480</span>,
      <span class="ax-key">"open"</span>:  <span class="ax-num">87</span>,
      <span class="ax-key">"resolved"</span>: <span class="ax-num">11891</span>
    },
    <span class="ax-key">"sla"</span>: { <span class="ax-key">"breached"</span>: <span class="ax-num">3</span> },
    <span class="ax-key">"users"</span>: <span class="ax-num">12</span>
  }
}`
            }
        ],
        start() { this.switchTab(0); this.cycle(); },
        cycle() {
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                this.switchTab((this.active + 1) % this.tabs.length);
            }, 7500);
        },
        switchTab(i) {
            this.active = i;
            this.cycle();
            this.showResponse = false;
            this.rendered = '';
            const snip = this.snippets[i];
            this.typeText(snip.code, () => {
                setTimeout(() => {
                    this.statusLine = snip.status;
                    this.responseRendered = snip.response;
                    this.showResponse = true;
                }, 280);
            });
        },
        typeText(html, done) {
            const target = html;
            let pos = 0;
            const tick = () => {
                if (pos > target.length) { done && done(); return; }
                if (target[pos] === '<') {
                    const end = target.indexOf('>', pos);
                    if (end !== -1) pos = end + 1;
                }
                pos++;
                this.rendered = target.slice(0, pos);
                if (pos <= target.length) requestAnimationFrame(() => setTimeout(tick, 10));
            };
            tick();
        },
        async copy() {
            try {
                await navigator.clipboard.writeText(this.snippets[this.active].code.replace(/<[^>]+>/g, ''));
                this.copied = true;
                setTimeout(() => this.copied = false, 1500);
            } catch (e) {}
        }
    };
}
</script>

<!-- ========== TESTIMONIALS ========== -->
<section id="testimonials" class="py-32">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('testi.eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance"><?= __e('testi.title_pre') ?> <span class="gradient-shift"><?= __e('testi.title_post') ?></span>.</h2>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-5 reveal-stagger" data-reveal>
            <?php foreach ([
                [__('testi.q1'),'Laura Méndez',__('testi.r1'),'LM','#ec4899'],
                [__('testi.q2'),'Carlos Rivera',__('testi.r2'),'CR','#7c5cff'],
                [__('testi.q3'),'Ana Torres',__('testi.r3'),'AT','#f59e0b'],
            ] as [$q,$n,$r,$in,$c]): ?>
                <div class="testi spotlight-card">
                    <div class="testi-quote">"</div>
                    <div class="flex items-center gap-1 mb-4">
                        <?php for ($s=0;$s<5;$s++): ?><i class="lucide lucide-star text-amber-400 text-[14px]" style="fill:#f59e0b"></i><?php endfor; ?>
                    </div>
                    <p class="text-[14.5px] leading-relaxed text-ink-700">"<?= $e($q) ?>"</p>
                    <div class="flex items-center gap-3 mt-6 pt-5 border-t border-[#ececef]">
                        <div class="avatar avatar-md" style="background:<?= $c ?>;color:white"><?= $e($in) ?></div>
                        <div>
                            <div class="font-display font-bold text-[13.5px]"><?= $e($n) ?></div>
                            <div class="text-[11.5px] text-ink-400"><?= $e($r) ?></div>
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
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('pricing.eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem);text-wrap:balance"><?= __e('pricing.title_pre') ?> <span class="gradient-shift"><?= __e('pricing.title_mid') ?></span><?= __e('pricing.title_post') ?></h2>
        </div>

        <?php
        $homePlans = $plans ?? [];
        $homeFeatureLabels = [
            'tickets' => __('pricing.feat.tickets'),
            'kb' => __('pricing.feat.kb'),
            'notes' => __('pricing.feat.notes'),
            'todos' => __('pricing.feat.todos'),
            'companies' => __('pricing.feat.companies'),
            'assets' => __('pricing.feat.assets'),
            'reports' => __('pricing.feat.reports'),
            'users' => __('pricing.feat.users'),
            'roles' => __('pricing.feat.roles'),
            'settings' => __('pricing.feat.settings'),
            'automations' => __('pricing.feat.automations'),
            'sla' => __('pricing.feat.sla'),
            'audit' => __('pricing.feat.audit'),
            'departments' => __('pricing.feat.departments'),
            'integrations' => __('pricing.feat.integrations'),
            'retainers' => __('pricing.feat.retainers'),
            'csat' => __('pricing.feat.csat'),
            'status_page' => __('pricing.feat.status_page'),
            'customer_portal' => __('pricing.feat.customer_portal'),
            'custom_fields' => __('pricing.feat.custom_fields'),
            'email_inbound' => __('pricing.feat.email_inbound'),
            'time_tracking' => __('pricing.feat.time_tracking'),
            'live_chat' => __('pricing.feat.live_chat'),
            'reports_builder' => __('pricing.feat.reports_builder'),
            'itsm' => __('pricing.feat.itsm'),
            'ai_assist' => __('pricing.feat.ai_assist'),
            'meetings' => __('pricing.feat.meetings'),
            'sso' => __('pricing.feat.sso'),
            'custom_branding' => __('pricing.feat.custom_branding'),
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
                if ((int)$hp['max_users'] >= 9999) $feats[] = __('pricing.unlimited_users');
                else $feats[] = __('pricing.up_to_users', ['n' => (int)$hp['max_users']]);
                if ((int)$hp['max_tickets_month'] >= 99999) $feats[] = __('pricing.unlimited_tickets');
                else $feats[] = __('pricing.tickets_month', ['n' => number_format($hp['max_tickets_month'])]);
                foreach ($highlightFeatures as $hf) $feats[] = $homeFeatureLabels[$hf] ?? ucfirst($hf);
                $feats = array_slice($feats, 0, 6);
            ?>
                <div class="price-card <?= $isFeat ? 'featured' : '' ?>">
                    <?php if ($isFeat): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="aura-pill-tag"><?= __e('pricing.recommended') ?></span></div>
                    <?php endif; ?>
                    <div class="text-[11px] uppercase tracking-[0.16em] font-bold <?= $isFeat ? 'text-brand-300 relative' : 'text-ink-400' ?>"><?= $e($hp['name']) ?></div>
                    <div class="mt-3 <?= $isFeat ? 'relative' : '' ?>">
                        <span class="price-amount <?= $isFeat ? 'gradient-shift' : '' ?>">$<?= number_format($hp['price_monthly'], 0) ?></span>
                        <span class="<?= $isFeat ? 'text-white/60' : 'text-ink-400' ?> text-[14px] ml-2"><?= __e('pricing.per_month') ?></span>
                    </div>
                    <?php if (!empty($hp['description'])): ?>
                        <p class="text-[13px] mt-3 <?= $isFeat ? 'text-white/70 relative' : 'text-ink-500' ?>"><?= $e($hp['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($isFeat): ?>
                        <a href="<?= $url('/auth/register') ?>" class="btn btn-lg w-full mt-6 justify-center relative" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 10px 24px -8px rgba(124,92,255,.6)"><?= (int)$hp['trial_days'] > 0 ? $e(__('pricing.try_n_days', ['n' => (int)$hp['trial_days']])) : $e(__('pricing.start_plan', ['plan' => $hp['name']])) ?></a>
                    <?php else: ?>
                        <a href="<?= $url($isFree ? '/auth/register' : '/pricing') ?>" class="btn btn-outline btn-sm w-full mt-6 justify-center"><?= $isFree ? __e('pricing.start_free') : $e(__('pricing.try_plan', ['plan' => $hp['name']])) ?></a>
                    <?php endif; ?>
                    <div class="mt-6 pt-6 <?= $isFeat ? 'border-t border-white/10 relative' : 'border-t border-[#ececef]' ?> space-y-1">
                        <?php foreach ($feats as $f): ?>
                            <div class="price-feat <?= $isFeat ? 'text-white/85' : '' ?>"><span class="price-feat-check" <?= $isFeat ? 'style="background:rgba(124,92,255,.2);color:#a78bfa"' : '' ?>><i class="lucide lucide-check text-[12px]"></i></span><?= $e($f) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($homePlans)): ?>
                <div class="md:col-span-3 text-center py-12 text-ink-400"><?= __e('pricing.preparing') ?></div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-10">
            <a href="<?= $url('/pricing') ?>" class="text-[13px] font-semibold text-brand-700 inline-flex items-center gap-1.5 hover:gap-2 transition-all"><?= __e('pricing.see_full') ?> <i class="lucide lucide-arrow-right text-[14px]"></i></a>
        </div>
    </div>
</section>

<!-- ========== FAQ ========== -->
<section class="py-24">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-14 reveal" data-reveal>
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('faq.eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3vw + 1rem,3rem)"><?= __e('faq.heading') ?></h2>
        </div>
        <div class="reveal" data-reveal x-data="{open:0}">
            <?php $faqs = [
                [__('faq.q1'), __('faq.a1')],
                [__('faq.q2'), __('faq.a2')],
                [__('faq.q3'), __('faq.a3')],
                [__('faq.q4'), __('faq.a4')],
                [__('faq.q5'), __('faq.a5')],
            ]; foreach ($faqs as $i => [$q,$a]): ?>
                <div class="faq-item" :class="open===<?= $i ?> ? 'open' : ''" @click="open = open===<?= $i ?> ? -1 : <?= $i ?>">
                    <div class="faq-q"><?= $e($q) ?><div class="faq-icon"><i class="lucide lucide-plus text-[16px]"></i></div></div>
                    <div class="faq-a"><?= $e($a) ?></div>
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
                <div class="aura-pill mx-auto" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.25)"><span class="text-white/90 font-medium"><?= __e('cta.pill') ?></span></div>
                <h2 class="display-xl text-white mt-7" style="font-size:clamp(2.4rem,4.5vw + 1rem,4.5rem);text-wrap:balance"><?= __e('cta.title_pre') ?><br><?= __e('cta.title_post') ?></h2>
                <p class="mt-7 text-[18px] text-white/85 max-w-md mx-auto"><?= __e('cta.subtitle') ?></p>
                <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-lg" style="background:white;color:#16151b"><?= __e('cta.start_free') ?> <i class="lucide lucide-arrow-right"></i></a>
                    <a href="<?= $url('/contact') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)"><?= __e('cta.contact') ?></a>
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
