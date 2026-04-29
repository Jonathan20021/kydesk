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
                    <span class="aura-pill-tag"><i class="lucide lucide-rocket"></i> <?= __e('careers2.eyebrow') ?></span>
                    <span class="text-ink-700 font-medium"><?= __e('careers2.pill') ?></span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)"><?= __e('careers2.title_pre') ?> <span class="gradient-shift"><?= __e('careers2.title_post') ?></span>.</h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed"><?= __e('careers2.subtitle') ?></p>
        </div>
    </div>
</section>

<!-- VALUES -->
<section class="pb-20">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <?php $values = [
                ['heart',    __('careers2.value1.t'), __('careers2.value1.d')],
                ['globe',    __('careers2.value2.t'), __('careers2.value2.d')],
                ['sparkles', __('careers2.value3.t'), __('careers2.value3.d')],
            ]; foreach ($values as [$ic,$valT,$valD]): ?>
                <div class="rounded-2xl p-7 bg-white border border-[#ececef]">
                    <div class="w-12 h-12 rounded-2xl grid place-items-center mb-5" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 20px -6px rgba(124,92,255,.45)"><i class="lucide lucide-<?= $ic ?> text-[20px]"></i></div>
                    <h3 class="font-display font-bold text-[18px] tracking-[-0.015em]"><?= $e($valT) ?></h3>
                    <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed"><?= $e($valD) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('careers2.benefits_eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem)"><?= __e('careers2.benefits_title') ?></h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php $benefits = [
                ['globe-2',         __('careers2.b1')],
                ['banknote',        __('careers2.b2')],
                ['heart-pulse',     __('careers2.b3')],
                ['plane',           __('careers2.b4')],
                ['laptop',          __('careers2.b5')],
                ['graduation-cap',  __('careers2.b6')],
                ['baby',            __('careers2.b7')],
                ['gift',            __('careers2.b8')],
            ]; foreach ($benefits as [$ic,$lbl]): ?>
                <div class="rounded-xl p-5 bg-white border border-[#ececef] flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg grid place-items-center" style="background:#f3f0ff;color:#5a3aff"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                    <span class="text-[13.5px] font-semibold"><?= $e($lbl) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- OPEN ROLES -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3"><?= __e('careers2.roles_eyebrow') ?></div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem)"><?= __e('careers2.roles_title') ?></h2>
        </div>

        <div class="space-y-3">
            <?php $roles = [
                [__('careers2.role1.t'), __('careers2.role1.dept'), __('careers2.role1.meta'), __('careers2.role1.desc')],
                [__('careers2.role2.t'), __('careers2.role2.dept'), __('careers2.role2.meta'), __('careers2.role2.desc')],
                [__('careers2.role3.t'), __('careers2.role3.dept'), __('careers2.role3.meta'), __('careers2.role3.desc')],
                [__('careers2.role4.t'), __('careers2.role4.dept'), __('careers2.role4.meta'), __('careers2.role4.desc')],
                [__('careers2.role5.t'), __('careers2.role5.dept'), __('careers2.role5.meta'), __('careers2.role5.desc')],
            ]; foreach ($roles as [$title, $dept, $meta, $desc]): ?>
                <a href="<?= $url('/contact') ?>" class="block rounded-2xl p-6 bg-white border border-[#ececef] hover:border-brand-300 hover:shadow-[0_12px_30px_-12px_rgba(124,92,255,.18)] transition">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10.5px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full" style="background:#f3f0ff;color:#5a3aff"><?= $e($dept) ?></span>
                            </div>
                            <h3 class="font-display font-bold text-[18px] tracking-[-0.015em]"><?= $e($title) ?></h3>
                            <div class="text-[12px] text-ink-400 mt-1"><?= $e($meta) ?></div>
                            <p class="text-[13.5px] text-ink-500 mt-3 leading-relaxed"><?= $e($desc) ?></p>
                        </div>
                        <div class="text-brand-700 flex-shrink-0"><i class="lucide lucide-arrow-right text-[18px]"></i></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-10 rounded-2xl p-6 text-center" style="background:#f3f0ff;border:1px solid #cdbfff">
            <p class="text-[13.5px] text-ink-700"><?= __('careers2.no_role_msg', ['email' => '<a href="mailto:jonathansandoval@kyrosrd.com" class="font-semibold text-brand-700 hover:underline">jonathansandoval@kyrosrd.com</a>']) ?></p>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
