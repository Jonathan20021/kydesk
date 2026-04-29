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
                    <span class="aura-pill-tag"><i class="lucide lucide-message-circle"></i> <?= __e('contact.eyebrow') ?></span>
                    <span class="text-ink-700 font-medium"><?= __e('contact.pill_clean') ?></span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)"><?= __e('contact.title_clean_pre') ?> <span class="gradient-shift"><?= __e('contact.title_clean_post') ?></span>.</h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed"><?= __e('contact.hero_sub_clean') ?></p>
        </div>
    </div>
</section>

<!-- CONTENT -->
<section class="pb-24 relative">
    <div class="max-w-[1100px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-5 gap-8">

        <!-- Info side -->
        <div class="lg:col-span-2 space-y-4">
            <?php foreach ([
                ['mail',           __('contact.email_label'),    'jonathansandoval@kyrosrd.com', __('contact.info_email_d'), 'mailto:jonathansandoval@kyrosrd.com'],
                ['message-circle', __('contact.whatsapp_label'), '+1 849 502 4061',              __('contact.info_wa_d'),    'https://wa.me/18495024061'],
                ['globe',          __('contact.info_loc_label'), __('contact.info_loc_v'),       __('contact.info_loc_d'),   '#'],
                ['clock',          __('contact.info_eta_label'), __('contact.info_eta_v'),       __('contact.info_eta_d'),   '#'],
            ] as [$ic,$lbl,$val,$desc,$href]): ?>
                <a href="<?= $href ?>" <?= str_starts_with($href, 'http') ? 'target="_blank" rel="noopener"' : '' ?> class="card card-pad spotlight-card block" style="padding:22px">
                    <div class="bento-glow"></div>
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-2xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 18px -6px rgba(124,92,255,.45)"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                        <div class="min-w-0">
                            <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-brand-700"><?= $e($lbl) ?></div>
                            <div class="font-display font-extrabold text-[16px] mt-0.5 tracking-[-0.015em] break-words"><?= $val /* may contain &lt; entity */ ?></div>
                            <div class="text-[12px] mt-1 text-ink-500"><?= $e($desc) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Form -->
        <div class="lg:col-span-3 relative">
            <div class="absolute -inset-1 rounded-[32px] opacity-50 blur-2xl pointer-events-none" style="background:linear-gradient(135deg,rgba(124,92,255,.4),rgba(217,70,239,.3))"></div>

            <form action="mailto:jonathansandoval@kyrosrd.com" method="POST" enctype="text/plain" class="relative rounded-[28px] p-8 md:p-10" style="background:white;border:1px solid #ececef;box-shadow:0 30px 60px -20px rgba(124,92,255,0.18)">
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-brand-600 mb-2"><?= __e('contact.send_us_msg') ?></div>
                <h2 class="font-display font-extrabold text-[24px] tracking-[-0.025em] leading-tight"><?= __e('contact.how_help') ?></h2>

                <div class="mt-7 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label"><?= __e('contact.label_name') ?></label>
                            <div class="relative">
                                <i class="lucide lucide-user text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="nombre" class="input pl-11" placeholder="Ana García">
                            </div>
                        </div>
                        <div>
                            <label class="label"><?= __e('contact.label_company') ?></label>
                            <div class="relative">
                                <i class="lucide lucide-building-2 text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="empresa" class="input pl-11" placeholder="Acme Inc.">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="label"><?= __e('contact.label_email') ?></label>
                        <div class="relative">
                            <i class="lucide lucide-mail text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                            <input type="email" name="email" class="input pl-11" placeholder="ana@acme.com">
                        </div>
                    </div>
                    <div>
                        <label class="label"><?= __e('contact.label_topic') ?></label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <?php foreach ([
                                [__('contact.topic_demo'),'play-circle'],
                                [__('contact.topic_sales'),'tag'],
                                [__('contact.topic_integration'),'plug'],
                                [__('contact.topic_other'),'circle-help'],
                            ] as [$lbl,$ic]): ?>
                                <label class="cursor-pointer border border-[#ececef] rounded-xl p-3 text-center hover:border-brand-300 hover:bg-brand-50 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700">
                                    <input type="radio" name="topic" value="<?= $e($lbl) ?>" class="hidden">
                                    <i class="lucide lucide-<?= $ic ?> text-[16px] block mx-auto mb-1"></i>
                                    <span class="text-[11.5px] font-semibold"><?= $e($lbl) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="label"><?= __e('contact.label_message') ?></label>
                        <textarea rows="5" name="mensaje" class="input" placeholder="<?= __e('contact.placeholder_msg') ?>"></textarea>
                    </div>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-xl font-semibold text-[14px] mt-6 transition" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.55)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'"><?= __e('contact.btn_send_email') ?> <i class="lucide lucide-send"></i></button>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="https://wa.me/18495024061" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 h-11 rounded-xl text-[13px] font-semibold transition" style="background:#25D366;color:white;box-shadow:0 8px 18px -6px rgba(37,211,102,.45)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'"><i class="lucide lucide-message-circle"></i> <?= __e('contact.btn_wa') ?></a>
                    <a href="mailto:jonathansandoval@kyrosrd.com" class="inline-flex items-center justify-center gap-2 h-11 rounded-xl text-[13px] font-semibold transition border" style="border-color:#ececef;color:#2a2a33;background:white" onmouseover="this.style.borderColor='#cdbfff';this.style.color='#5a3aff'" onmouseout="this.style.borderColor='#ececef';this.style.color='#2a2a33'"><i class="lucide lucide-mail"></i> <?= __e('contact.btn_email') ?></a>
                </div>

                <p class="text-[11.5px] text-ink-400 text-center mt-4 inline-flex items-center justify-center gap-1.5 w-full"><i class="lucide lucide-shield-check text-[12px] text-emerald-600"></i> <?= __e('contact.privacy_note') ?></p>
            </form>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
