<div class="min-h-screen relative overflow-hidden" style="background:#fafafb">

    <!-- Aurora background -->
    <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute" style="width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.4),transparent 70%);top:-200px;left:-100px;filter:blur(60px);animation:aurora-1 22s ease-in-out infinite"></div>
        <div class="absolute" style="width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(217,70,239,.3),transparent 70%);top:20%;right:-100px;filter:blur(60px);animation:aurora-2 26s ease-in-out infinite"></div>
        <div class="absolute" style="width:550px;height:550px;border-radius:50%;background:radial-gradient(circle,rgba(129,140,248,.35),transparent 70%);bottom:-150px;left:30%;filter:blur(70px);animation:aurora-3 30s ease-in-out infinite"></div>
        <div class="absolute inset-0" style="background-image:linear-gradient(rgba(124,92,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(124,92,255,0.06) 1px, transparent 1px); background-size: 64px 64px; mask-image: radial-gradient(ellipse 70% 50% at 50% 0%, black 30%, transparent 80%); -webkit-mask-image: radial-gradient(ellipse 70% 50% at 50% 0%, black 30%, transparent 80%);"></div>
    </div>

    <!-- Top nav -->
    <nav class="fixed top-4 inset-x-0 z-50 px-4">
        <div class="max-w-[1100px] mx-auto">
            <div class="flex items-center justify-between gap-4 px-5 py-2.5 rounded-full" style="background:rgba(255,255,255,0.78);backdrop-filter:blur(20px) saturate(180%);border:1px solid rgba(124,92,255,0.12);box-shadow:0 4px 20px -4px rgba(22,21,27,0.06)">
                <a href="<?= $url('/') ?>" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);box-shadow:0 4px 12px -2px rgba(124,92,255,.4)">K</div>
                    <span class="font-display font-extrabold text-[16px] tracking-[-0.02em]">Kydesk</span>
                </a>
                <div class="flex items-center gap-3">
                    <?php $variant = 'light'; $align = 'right'; include APP_PATH . '/Views/partials/lang_switcher.php'; ?>
                    <a href="<?= $url('/auth/register') ?>" class="text-[13px] font-semibold text-ink-700 hover:text-brand-700 transition"><?= $te('common.create_account') ?> →</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen grid place-items-center px-4 py-24">
        <div class="w-full max-w-[1100px] grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">

            <!-- LEFT: branding -->
            <div class="hidden lg:block">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em]" style="background:rgba(255,255,255,.85);border:1px solid rgba(124,92,255,0.2);color:#5a3aff">
                    <i class="lucide lucide-sparkles text-[12px]"></i> <?= $te('auth.login.welcome_back_pill') ?>
                </div>

                <h1 class="display-xl mt-7" style="font-size:clamp(2.4rem,4vw + 1rem,4rem);text-wrap:balance"><?= $te('auth.login.heroline_pre') ?><br><span class="gradient-shift"><?= $te('auth.login.heroline_post') ?></span>.</h1>

                <p class="mt-6 text-[16px] text-ink-500 max-w-md leading-relaxed"><?= $te('auth.login.hero_subtitle') ?></p>

                <div class="mt-10 space-y-3">
                    <?php foreach ([['zap', __('auth.login.feat1')], ['shield-check', __('auth.login.feat2')], ['workflow', __('auth.login.feat3')]] as [$ic, $featTxt]): ?>
                        <div class="flex items-center gap-3 text-[14px] text-ink-700">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#ececef] grid place-items-center" style="box-shadow:0 4px 12px -4px rgba(124,92,255,.15)"><i class="lucide lucide-<?= $ic ?> text-[16px] text-brand-600"></i></div>
                            <?= $e($featTxt) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-12 flex items-center gap-3 p-4 rounded-2xl" style="background:rgba(255,255,255,.6);backdrop-filter:blur(8px);border:1px solid rgba(124,92,255,0.15)">
                    <div class="flex -space-x-2">
                        <?php foreach ([['JS','#ec4899'],['MT','#7c5cff'],['CI','#f59e0b']] as [$in,$c]): ?>
                            <div class="w-8 h-8 rounded-full grid place-items-center text-white text-[10px] font-bold border-2 border-white" style="background:<?= $c ?>"><?= $in ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex-1 text-[12.5px] text-ink-700">
                        <div class="font-display font-bold"><?= $te('auth.login.team_count') ?></div>
                        <div class="text-ink-400"><?= $te('auth.login.team_resolve') ?></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: form card -->
            <div class="relative">
                <!-- Glow detrás del card -->
                <div class="absolute -inset-1 rounded-[32px] opacity-50 blur-2xl pointer-events-none" style="background:linear-gradient(135deg,rgba(124,92,255,.4),rgba(217,70,239,.3))"></div>

                <div class="relative rounded-[28px] p-8 md:p-10" style="background:white;border:1px solid #ececef;box-shadow:0 30px 60px -20px rgba(124,92,255,0.18)">
                    <div class="lg:hidden flex items-center gap-2.5 mb-8">
                        <div class="w-9 h-9 rounded-xl text-white grid place-items-center font-display font-bold" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">K</div>
                        <span class="font-display font-bold text-[18px]">Kydesk</span>
                    </div>

                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-brand-600 mb-2"><?= $te('auth.login.eyebrow') ?></div>
                    <h2 class="font-display font-extrabold text-[28px] tracking-[-0.025em] leading-tight"><?= $te('auth.login.title_pre') ?><br><?= $te('auth.login.title_post') ?></h2>
                    <p class="mt-2 text-[13.5px] text-ink-500"><?= $te('auth.login.subtitle') ?></p>

                    <form method="POST" action="<?= $url('/auth/login') ?>" class="mt-7 space-y-4">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <div>
                            <label class="label"><?= $te('common.email') ?></label>
                            <div class="relative">
                                <i class="lucide lucide-mail text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="email" type="email" required value="admin@demo.com" class="input pl-11">
                            </div>
                        </div>
                        <div x-data="{show:false}">
                            <div class="flex items-center justify-between">
                                <label class="label" style="margin-bottom:0"><?= $te('common.password') ?></label>
                                <a href="#" class="text-[12px] text-brand-700 font-medium hover:underline"><?= $te('common.forgot') ?></a>
                            </div>
                            <div class="relative mt-2">
                                <i class="lucide lucide-lock text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="password" :type="show?'text':'password'" required value="admin123" class="input pl-11 pr-11">
                                <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center text-ink-400 hover:text-ink-700"><i :class="show?'lucide-eye-off':'lucide-eye'" class="lucide text-[15px]"></i></button>
                            </div>
                        </div>
                        <button class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-xl font-semibold text-[14px] transition" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.55)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'"><?= $te('auth.login.button') ?> <i class="lucide lucide-arrow-right"></i></button>
                    </form>

                    <div class="flex items-center gap-3 mt-7 mb-4">
                        <div class="flex-1 h-px bg-[#ececef]"></div>
                        <span class="text-[10.5px] font-bold uppercase tracking-[0.2em] text-ink-400"><?= $te('auth.login.divider_demo') ?></span>
                        <div class="flex-1 h-px bg-[#ececef]"></div>
                    </div>

                    <p class="text-center text-[12px] text-ink-400 mb-3.5 flex items-center justify-center gap-1.5"><i class="lucide lucide-zap text-[12px] text-brand-500"></i> <?= $te('auth.login.demo_caption') ?></p>

                    <div class="grid grid-cols-3 gap-2">
                        <?php
                        $loginPlans = [
                            ['starter','Starter','$29',__('auth.login.plan_starter_meta'), false, '#dbeafe', '#1d4ed8'],
                            ['pro','Pro','$79',__('auth.login.plan_pro_meta'), true, '', ''],
                            ['enterprise','Enterprise','$199',__('auth.login.plan_ent_meta'), false, '#fef3c7', '#b45309'],
                        ];
                        foreach ($loginPlans as [$key,$label,$price,$meta,$featured,$bg,$col]):
                        ?>
                            <form method="POST" action="<?= $url('/demo/start/' . $key) ?>" class="<?= $featured?'-mt-2':'' ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button type="submit" class="group relative w-full text-center rounded-2xl py-4 px-3 transition-all duration-200 hover:-translate-y-1 <?= $featured ? 'text-white' : 'bg-white border border-[#ececef] hover:border-brand-300 hover:shadow-[0_14px_28px_-10px_rgba(124,92,255,0.22)]' ?>" <?= $featured ? 'style="background:linear-gradient(180deg,#1a1825,#16151b);box-shadow:0 12px 28px -8px rgba(124,92,255,.45)"' : '' ?>>
                                    <?php if ($featured): ?>
                                        <span class="absolute inset-0 rounded-2xl pointer-events-none" style="padding:1.5px;background:linear-gradient(135deg,#7c5cff,#d946ef);-webkit-mask:linear-gradient(white,white) content-box,linear-gradient(white,white);-webkit-mask-composite:xor;mask-composite:exclude"></span>
                                        <span class="absolute -top-2.5 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold tracking-[0.16em] text-white whitespace-nowrap" style="background:linear-gradient(135deg,#7c5cff,#d946ef);box-shadow:0 4px 10px -2px rgba(124,92,255,.5)">POPULAR</span>
                                        <span class="inline-flex w-7 h-7 rounded-lg items-center justify-center mb-1.5" style="background:rgba(124,92,255,.25);color:#c4b5fd"><i class="lucide lucide-zap text-[13px]"></i></span>
                                    <?php else: ?>
                                        <span class="inline-flex w-7 h-7 rounded-lg items-center justify-center mb-1.5" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $key==='starter'?'rocket':'crown' ?> text-[13px]"></i></span>
                                    <?php endif; ?>
                                    <div class="font-display font-bold text-[12.5px] tracking-[-0.015em] <?= $featured?'text-white':'text-ink-900' ?>"><?= $e($label) ?></div>
                                    <div class="font-display font-extrabold text-[16px] tracking-[-0.02em] mt-0.5 <?= $featured?'text-white':'text-ink-900' ?>"><?= $e($price) ?><span class="text-[9.5px] font-semibold opacity-50">/m</span></div>
                                    <div class="text-[10px] font-semibold mt-0.5 <?= $featured?'text-white/60':'text-ink-400' ?>"><?= $e($meta) ?></div>
                                    <div class="mt-2.5 inline-flex items-center justify-center gap-1 text-[9.5px] font-bold uppercase tracking-[0.08em] <?= $featured?'text-brand-300':'text-brand-700' ?> opacity-0 group-hover:opacity-100 transition"><i class="lucide lucide-play text-[9px]"></i> <?= $te('auth.login.try') ?></div>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>

                    <details class="mt-5 text-[12px] text-ink-500">
                        <summary class="cursor-pointer hover:text-ink-900 transition inline-flex items-center gap-1.5"><i class="lucide lucide-key-round text-[12px]"></i> <?= $te('auth.login.creds_summary') ?></summary>
                        <div class="mt-2 p-3 rounded-xl bg-[#f3f4f6] font-mono text-[11.5px]">admin@demo.com · admin123</div>
                    </details>

                    <p class="mt-7 text-center text-[13px] text-ink-400"><?= $te('auth.login.no_account') ?> <a href="<?= $url('/auth/register') ?>" class="font-semibold text-brand-700 hover:underline"><?= $te('auth.login.create_org') ?></a></p>
                </div>
            </div>
        </div>
    </div>
</div>
