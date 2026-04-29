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
                    <span class="aura-pill-tag"><i class="lucide lucide-book-open"></i> <?= __e('docs2.eyebrow') ?></span>
                    <span class="text-ink-700 font-medium"><?= __e('docs2.pill') ?></span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)"><?= __e('docs2.title_pre') ?> <span class="gradient-shift"><?= __e('docs2.title_post') ?></span>.</h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed"><?= __e('docs2.subtitle') ?></p>
        </div>

        <div class="mt-10 max-w-xl mx-auto">
            <div class="relative">
                <i class="lucide lucide-search absolute left-5 top-1/2 -translate-y-1/2 text-ink-400"></i>
                <input type="text" placeholder="<?= __e('docs2.search') ?>" class="w-full h-14 pl-14 pr-5 rounded-2xl bg-white border border-[#ececef] text-[14px] outline-none focus:border-brand-300 focus:shadow-[0_0_0_4px_rgba(124,92,255,.1)] transition">
            </div>
        </div>
    </div>
</section>

<!-- SECTIONS -->
<section class="pb-20">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php
            $sections = [
                ['rocket',   __('docs2.s1.t'), '#7c5cff', __('docs2.s1.d'), [__('docs2.s1.i1'),__('docs2.s1.i2'),__('docs2.s1.i3'),__('docs2.s1.i4')]],
                ['inbox',    __('docs2.s2.t'), '#ec4899', __('docs2.s2.d'), [__('docs2.s2.i1'),__('docs2.s2.i2'),__('docs2.s2.i3'),__('docs2.s2.i4')]],
                ['gauge',    __('docs2.s3.t'), '#f59e0b', __('docs2.s3.d'), [__('docs2.s3.i1'),__('docs2.s3.i2'),__('docs2.s3.i3'),__('docs2.s3.i4')]],
                ['workflow', __('docs2.s4.t'), '#22c55e', __('docs2.s4.d'), [__('docs2.s4.i1'),__('docs2.s4.i2'),__('docs2.s4.i3'),__('docs2.s4.i4')]],
                ['book-open',__('docs2.s5.t'), '#0ea5e9', __('docs2.s5.d'), [__('docs2.s5.i1'),__('docs2.s5.i2'),__('docs2.s5.i3'),__('docs2.s5.i4')]],
                ['shield',   __('docs2.s6.t'), '#b91c1c', __('docs2.s6.d'), [__('docs2.s6.i1'),__('docs2.s6.i2'),__('docs2.s6.i3'),__('docs2.s6.i4')]],
                ['code',     __('docs2.s7.t'), '#7e22ce', __('docs2.s7.d'), [__('docs2.s7.i1'),__('docs2.s7.i2'),__('docs2.s7.i3'),__('docs2.s7.i4')]],
                ['plug',     __('docs2.s8.t'), '#14b8a6', __('docs2.s8.d'), [__('docs2.s8.i1'),__('docs2.s8.i2'),__('docs2.s8.i3'),__('docs2.s8.i4')]],
                ['settings', __('docs2.s9.t'), '#6b6b78', __('docs2.s9.d'), [__('docs2.s9.i1'),__('docs2.s9.i2'),__('docs2.s9.i3'),__('docs2.s9.i4')]],
            ];
            foreach ($sections as [$ic, $title, $color, $desc, $items]): ?>
                <div class="rounded-2xl p-7 bg-white border border-[#ececef] hover:border-brand-300 hover:shadow-[0_18px_40px_-15px_rgba(124,92,255,.15)] transition">
                    <div class="w-12 h-12 rounded-2xl grid place-items-center mb-5" style="background:<?= $color ?>22;color:<?= $color ?>"><i class="lucide lucide-<?= $ic ?> text-[20px]"></i></div>
                    <h3 class="font-display font-bold text-[17px] tracking-[-0.015em]"><?= $e($title) ?></h3>
                    <p class="text-[12.5px] text-ink-500 mt-2 leading-relaxed"><?= $e($desc) ?></p>
                    <ul class="mt-4 pt-4 border-t border-[#ececef] space-y-2">
                        <?php foreach ($items as $it): ?>
                            <li class="flex items-center gap-2 text-[13px] text-ink-700 hover:text-brand-700 transition cursor-pointer"><i class="lucide lucide-arrow-right text-[12px] text-ink-400"></i> <?= $e($it) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- API SAMPLE -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div>
                <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('docs2.api_eyebrow') ?></div>
                <h2 class="display-xl" style="font-size:clamp(2rem,3vw + 1rem,3rem)"><?= __e('docs2.api_title_pre') ?> <span class="gradient-shift"><?= __e('docs2.api_title_post') ?></span>.</h2>
                <p class="text-[15px] text-ink-500 mt-5 leading-relaxed"><?= __e('docs2.api_para') ?></p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="#" class="btn btn-primary"><i class="lucide lucide-book"></i> <?= __e('docs2.api_btn1') ?></a>
                    <a href="#" class="btn btn-outline"><i class="lucide lucide-github"></i> <?= __e('docs2.api_btn2') ?></a>
                </div>
            </div>
            <div class="rounded-2xl p-6 font-mono text-[12.5px] leading-relaxed" style="background:#0f0d18;color:#e9e8ef;box-shadow:0 30px 60px -20px rgba(124,92,255,.4)">
                <div class="text-[11px] uppercase tracking-[0.16em] font-bold mb-3" style="color:#a78bfa">POST /api/v1/tickets</div>
                <pre style="white-space:pre-wrap"><span style="color:#86efac">curl</span> -X POST https://api.kydesk.com/v1/tickets \
  -H <span style="color:#fde68a">"Authorization: Bearer YOUR_TOKEN"</span> \
  -H <span style="color:#fde68a">"Content-Type: application/json"</span> \
  -d '{
    <span style="color:#c4b5fd">"subject"</span>: <span style="color:#fde68a">"VPN no conecta"</span>,
    <span style="color:#c4b5fd">"priority"</span>: <span style="color:#fde68a">"high"</span>,
    <span style="color:#c4b5fd">"requester_email"</span>: <span style="color:#fde68a">"u@empresa.com"</span>
}'</pre>
            </div>
        </div>
    </div>
</section>

<!-- HELP CTA -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[900px] mx-auto px-6 text-center">
        <h2 class="display-xl" style="font-size:clamp(1.8rem,3vw + 1rem,2.6rem)"><?= __e('docs2.help_title') ?></h2>
        <p class="text-[15px] text-ink-500 mt-5"><?= __e('docs2.help_para') ?></p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="<?= $url('/contact') ?>" class="btn btn-primary btn-lg"><i class="lucide lucide-message-circle"></i> <?= __e('docs2.help_btn1') ?></a>
            <a href="<?= $url('/portal/demo/kb') ?>" class="btn btn-outline btn-lg"><i class="lucide lucide-life-buoy"></i> <?= __e('docs2.help_btn2') ?></a>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
