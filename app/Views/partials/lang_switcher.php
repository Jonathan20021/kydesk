<?php
/**
 * Language switcher (compact dropdown).
 *
 * Available variables (always provided by View::render):
 *   $locale  - current code ('es' | 'en')
 *   $locales - ['es' => 'Español', 'en' => 'English']
 *   $url     - URL builder helper
 *
 * Optional caller variables:
 *   $variant - 'light' (default, for white/cream backgrounds)
 *            - 'dark'  (for ink-900 / dark glass surfaces)
 *   $align   - 'left' (default) | 'right' — dropdown alignment
 *   $compact - true to render only the flag/code (no chevron text)
 *
 * The switcher uses a plain <a href="/lang/xx?to=..."> per option, so it
 * works without JS. Alpine is only used to toggle the dropdown.
 */
$variant = $variant ?? 'light';
$align   = $align   ?? 'right';
$compact = $compact ?? false;

$flagEs = '🇪🇸';
$flagEn = '🇬🇧';
$flags  = ['es' => $flagEs, 'en' => $flagEn];

$currentFlag  = $flags[$locale] ?? $flagEs;
$currentLabel = $locales[$locale] ?? 'Español';
$currentCode  = strtoupper($locale);

// Bounce-back target — preserve current URL so user lands back on same page.
$backTo  = $_SERVER['REQUEST_URI'] ?? '/';
$backEnc = urlencode($backTo);

if ($variant === 'dark') {
    $btnClass    = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-semibold border transition';
    $btnStyle    = 'background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.9)';
    $btnHover    = "this.style.background='rgba(255,255,255,.12)'";
    $btnUnhover  = "this.style.background='rgba(255,255,255,.06)'";
    $menuStyle   = 'background:#16151b;border:1px solid rgba(255,255,255,.12);box-shadow:0 18px 40px -12px rgba(0,0,0,.55)';
    $itemTextEs  = 'color:rgba(255,255,255,.9)';
    $itemTextEn  = 'color:rgba(255,255,255,.9)';
    $itemHover   = "this.style.background='rgba(255,255,255,.06)'";
    $itemUnhover = "this.style.background='transparent'";
    $activeBg    = 'background:rgba(124,92,255,.18);';
} else {
    $btnClass    = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-semibold border transition hover:bg-[#f3f4f6]';
    $btnStyle    = 'background:#fff;border-color:#ececef;color:#2a2a33';
    $btnHover    = "";
    $btnUnhover  = "";
    $menuStyle   = 'background:#fff;border:1px solid #ececef;box-shadow:0 18px 40px -12px rgba(22,21,27,.18)';
    $itemTextEs  = '';
    $itemTextEn  = '';
    $itemHover   = "this.style.background='#f3f4f6'";
    $itemUnhover = "this.style.background='transparent'";
    $activeBg    = 'background:#f3f0ff;';
}

$alignClass = $align === 'left' ? 'left-0' : 'right-0';
?>
<div x-data="{ langOpen: false }" @keydown.escape.window="langOpen = false" @click.outside="langOpen = false" class="relative">
    <button type="button" @click="langOpen = !langOpen"
        :aria-expanded="langOpen"
        aria-label="<?= $te('common.language') ?>"
        class="<?= $btnClass ?>"
        style="<?= $btnStyle ?>"
        <?php if ($btnHover): ?>onmouseover="<?= $btnHover ?>"<?php endif; ?>
        <?php if ($btnUnhover): ?>onmouseout="<?= $btnUnhover ?>"<?php endif; ?>>
        <span style="font-size:13px;line-height:1"><?= $currentFlag ?></span>
        <?php if (!$compact): ?>
            <span><?= $e($currentCode) ?></span>
            <i class="lucide lucide-chevron-down text-[11px] opacity-70" :class="langOpen && 'rotate-180'" style="transition:transform .15s"></i>
        <?php endif; ?>
    </button>
    <div x-show="langOpen" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="absolute mt-2 <?= $alignClass ?> z-50 min-w-[170px] rounded-2xl overflow-hidden p-1.5"
         style="<?= $menuStyle ?>">
        <?php foreach ($locales as $code => $label):
            $flag = $flags[$code] ?? '';
            $isActive = $code === $locale;
        ?>
            <a href="<?= $url('/lang/' . $code) ?>?to=<?= $backEnc ?>"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[13px] font-medium transition"
               style="<?= $isActive ? $activeBg : '' ?>"
               <?php if (!$isActive && $itemHover): ?>onmouseover="<?= $itemHover ?>"<?php endif; ?>
               <?php if (!$isActive && $itemUnhover): ?>onmouseout="<?= $itemUnhover ?>"<?php endif; ?>>
                <span style="font-size:14px;line-height:1"><?= $flag ?></span>
                <span class="flex-1"><?= $e($label) ?></span>
                <?php if ($isActive): ?>
                    <i class="lucide lucide-check text-[12px] text-brand-600"></i>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
