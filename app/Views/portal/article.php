<?php
$t = $tenant; $a = $art;
$brand = $t->data['primary_color'] ?? '#7c5cff';
$brandRgb = sscanf($brand, "#%02x%02x%02x");
$rgbStr = $brandRgb ? implode(',', $brandRgb) : '124,92,255';
?>
<nav class="fixed top-4 inset-x-0 z-50 px-4">
    <div class="nav-land">
        <div class="nav-land-inner">
            <a href="<?= $url('/portal/' . $t->slug) ?>" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px]" style="background:<?= $e($brand) ?>;box-shadow:0 6px 14px -4px rgba(<?= $rgbStr ?>,.45)"><?= strtoupper(substr($t->name,0,1)) ?></div>
                <div class="leading-tight">
                    <div class="font-display font-extrabold text-[15px] tracking-[-0.015em]"><?= $e($t->name) ?></div>
                    <div class="text-[10px] text-ink-400 uppercase tracking-[0.12em]">Centro de soporte</div>
                </div>
            </a>
            <div class="flex items-center gap-1.5 ml-auto">
                <a href="https://kydesk.kyrosrd.com" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-ink-400 hover:text-ink-900 transition">
                    Powered by <span class="font-display font-bold text-ink-900">Kydesk</span>
                </a>
                <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="btn btn-ghost btn-sm"><i class="lucide lucide-book-open text-[13px]"></i> KB</a>
            </div>
        </div>
    </div>
</nav>
<div class="h-[88px]"></div>
<section class="py-14">
    <div class="max-w-[760px] mx-auto px-6">
        <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-400 mb-5"><i class="lucide lucide-arrow-left"></i> Centro de ayuda</a>
        <?php if ($a['cat_name']): ?><span class="badge badge-purple mb-3"><span class="dot" style="background:<?= $e($a['cat_color']) ?>"></span> <?= $e($a['cat_name']) ?></span><?php endif; ?>
        <h1 class="heading-md"><?= $e($a['title']) ?></h1>
        <?php if ($a['excerpt']): ?><p class="mt-4 text-[16px] leading-relaxed text-ink-500"><?= $e($a['excerpt']) ?></p><?php endif; ?>
        <div class="flex items-center gap-3 mt-5 pb-5 border-b border-[#ececef] text-[12px] text-ink-400">
            <span><?= date('d/m/Y', strtotime($a['updated_at'])) ?></span>
            <span>·</span>
            <span class="flex items-center gap-1"><i class="lucide lucide-eye text-[12px]"></i> <?= number_format($a['views']) ?></span>
        </div>
        <article class="mt-7 whitespace-pre-wrap text-[15px]" style="line-height:1.75"><?= $e($a['body']) ?></article>
        <div class="mt-12 card card-pad text-center" style="background:#f3f4f6;border:none">
            <div class="text-[13px] font-semibold mb-3.5">¿Te resultó útil?</div>
            <div class="flex items-center justify-center gap-2">
                <button class="btn btn-outline btn-sm"><i class="lucide lucide-thumbs-up"></i> Sí</button>
                <button class="btn btn-outline btn-sm"><i class="lucide lucide-thumbs-down"></i> No</button>
            </div>
            <div class="mt-4 text-[12px] text-ink-400">¿No encontraste lo que buscabas? <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="font-semibold text-ink-900">Abre un ticket</a></div>
        </div>
    </div>
</section>
