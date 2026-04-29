<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<!-- HERO -->
<section class="relative pt-36 pb-12 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
    </div>
    <div class="grid-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex justify-center">
                <div class="aura-pill" style="background:rgba(34,197,94,.1); border-color:rgba(34,197,94,.3)">
                    <span class="relative inline-flex w-2 h-2 mr-2"><span class="absolute inset-0 rounded-full" style="background:#22c55e;animation:pulse-ring 2s ease-out infinite"></span><span class="relative inline-block w-2 h-2 rounded-full" style="background:#22c55e"></span></span>
                    <span class="font-bold text-emerald-700"><?= __e('status2.pill') ?></span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.4rem,5vw + 1rem,4.5rem)"><?= __e('status2.title_pre') ?> <span class="gradient-shift"><?= __e('status2.title_post') ?></span>.</h1>
            <p class="mt-7 text-[16px] text-ink-500 max-w-xl mx-auto leading-relaxed"><?= __e('status2.subtitle') ?></p>
        </div>
    </div>
</section>

<!-- SYSTEMS -->
<section class="pb-12">
    <div class="max-w-[1100px] mx-auto px-6">
        <div class="rounded-2xl bg-white border border-[#ececef] overflow-hidden">
            <?php
            $systems = [
                [__('status2.sys.web'),      'operational', '99.99%'],
                [__('status2.sys.api'),      'operational', '99.98%'],
                [__('status2.sys.webhooks'), 'operational', '99.97%'],
                [__('status2.sys.email'),    'operational', '99.95%'],
                [__('status2.sys.portal'),   'operational', '99.99%'],
                [__('status2.sys.db'),       'operational', '100%'],
                [__('status2.sys.search'),   'operational', '99.98%'],
                [__('status2.sys.notif'),    'operational', '99.96%'],
            ];
            foreach ($systems as $i => [$name, $status, $uptime]):
                $colors = [
                    'operational' => ['#22c55e', __('status2.lbl.operational')],
                    'degraded'    => ['#f59e0b', __('status2.lbl.degraded')],
                    'outage'      => ['#ef4444', __('status2.lbl.outage')],
                ];
                [$col, $lbl] = $colors[$status];
            ?>
                <div class="flex items-center justify-between px-6 py-4 <?= $i < count($systems)-1 ? 'border-b border-[#f3f3f5]' : '' ?>">
                    <div class="flex items-center gap-3">
                        <span class="relative inline-flex w-2.5 h-2.5"><span class="absolute inset-0 rounded-full" style="background:<?= $col ?>;animation:pulse-ring 2s ease-out infinite;opacity:.4"></span><span class="relative inline-block w-2.5 h-2.5 rounded-full" style="background:<?= $col ?>"></span></span>
                        <span class="font-semibold text-[14px]"><?= $e($name) ?></span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-[12px] text-ink-400 font-mono"><?= $e($uptime) ?> <?= __e('status2.uptime_suffix') ?></span>
                        <span class="text-[11.5px] font-bold uppercase tracking-[0.14em]" style="color:<?= $col ?>"><?= $e($lbl) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- METRICS -->
<section class="py-12 border-t border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php $metrics = [
                [__('status2.metrics.up_30d'),  '99.98%', '#22c55e'],
                [__('status2.metrics.up_90d'),  '99.97%', '#22c55e'],
                [__('status2.metrics.api_lat'), '142ms',  '#7c5cff'],
                [__('status2.metrics.tk_min'),  '1,240',  '#0ea5e9'],
            ]; foreach ($metrics as [$lbl,$val,$col]): ?>
                <div class="rounded-2xl p-6 bg-white border border-[#ececef]">
                    <div class="text-[11px] uppercase tracking-[0.14em] font-bold text-ink-400"><?= $e($lbl) ?></div>
                    <div class="font-display font-extrabold text-[28px] tracking-[-0.025em] mt-2" style="color:<?= $col ?>"><?= $e($val) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- INCIDENT HISTORY -->
<section class="py-16 border-t border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6">
        <h2 class="font-display font-bold text-[24px] tracking-[-0.015em] mb-6"><?= __e('status2.history_title') ?></h2>
        <div class="space-y-3">
            <?php
            $incidents = [
                [date('Y-m-d', strtotime('-3 days')),  'resolved', __('status2.inc1.title'), '17 min', __('status2.inc1.note')],
                [date('Y-m-d', strtotime('-12 days')), 'resolved', __('status2.inc2.title'), '32 min', __('status2.inc2.note')],
                [date('Y-m-d', strtotime('-28 days')), 'resolved', __('status2.inc3.title'), '8 min',  __('status2.inc3.note')],
            ];
            foreach ($incidents as [$date, $status, $title, $duration, $note]): ?>
                <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#166534"><?= __e('status2.resolved_label') ?></span>
                                <span class="text-[11.5px] text-ink-400"><?= $e($date) ?> · <?= __e('status2.duration') ?> <?= $e($duration) ?></span>
                            </div>
                            <div class="font-display font-bold text-[14.5px]"><?= $e($title) ?></div>
                            <p class="text-[13px] text-ink-500 mt-2"><?= $e($note) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-16 border-t border-[#ececef]">
    <div class="max-w-[800px] mx-auto px-6 text-center">
        <p class="text-[13.5px] text-ink-500"><?= __e('status2.subscribe.text') ?></p>
        <form class="mt-5 flex flex-col sm:flex-row gap-2 max-w-md mx-auto">
            <input type="email" placeholder="tu@empresa.com" class="flex-1 h-12 px-4 rounded-xl border border-[#ececef] outline-none focus:border-brand-300 text-[14px]">
            <button type="submit" class="btn btn-primary"><?= __e('status2.subscribe.btn') ?></button>
        </form>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
