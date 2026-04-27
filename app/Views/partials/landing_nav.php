<?php
$navLinks = [
    ['/features',         'Producto',     'sparkles'],
    ['/pricing',          'Precios',      'tag'],
    ['/developers',       'Developers',   'code'],
    ['/changelog',        'Novedades',    'newspaper'],
    ['/contact',          'Contacto',     'mail'],
];
?>
<nav class="fixed top-3 sm:top-4 inset-x-0 z-50 px-3 sm:px-4" x-data="{ mobileMenu: false }" @keydown.escape.window="mobileMenu = false" :class="mobileMenu && 'mobile-menu-open'">
    <div class="nav-land">
        <div class="nav-land-inner">
            <a href="<?= $url('/') ?>" class="flex items-center gap-2.5 flex-shrink-0">
                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px] glow-purple" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">K</div>
                <span class="font-display font-extrabold text-[16px] tracking-[-0.02em]">Kydesk</span>
            </a>

            <!-- Desktop links -->
            <div class="hidden lg:flex items-center gap-0.5 text-[13px] font-medium text-ink-500 ml-4">
                <?php foreach ($navLinks as [$href, $label, $icon]): ?>
                    <a href="<?= $url($href) ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition flex items-center gap-1">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-1.5 ml-auto">
                <a href="<?= $url('/demo') ?>" class="btn btn-ghost btn-sm hidden md:inline-flex"><i class="lucide lucide-play-circle text-[13px]"></i> Demo</a>
                <a href="<?= $url('/auth/login') ?>" class="btn btn-ghost btn-sm hidden md:inline-flex">Entrar</a>
                <a href="<?= $url('/auth/register') ?>" class="btn btn-dark btn-sm hidden sm:inline-flex">Empezar gratis <i class="lucide lucide-arrow-right text-[13px]"></i></a>

                <!-- Mobile menu toggle -->
                <button type="button" @click="mobileMenu = !mobileMenu" :aria-expanded="mobileMenu" aria-controls="mobile-menu" aria-label="Abrir menú"
                    class="lg:hidden w-10 h-10 rounded-full grid place-items-center transition border flex-shrink-0"
                    :class="mobileMenu ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700 border-[#ececef] hover:border-brand-200'">
                    <i class="lucide" :class="mobileMenu ? 'lucide-x' : 'lucide-menu'" style="width:18px;height:18px"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile drawer -->
    <div x-show="mobileMenu" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         id="mobile-menu" class="lg:hidden mt-2 mx-auto" style="max-width:1100px">
        <div class="rounded-3xl bg-white border border-[#ececef] overflow-hidden" style="box-shadow:0 20px 50px -12px rgba(22,21,27,.18)">
            <div class="p-3 grid gap-0.5">
                <?php foreach ($navLinks as [$href, $label, $icon]): ?>
                    <a href="<?= $url($href) ?>"
                       @click="mobileMenu = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-2xl text-[14px] font-semibold text-ink-700 hover:bg-[#f3f4f6] hover:text-ink-900 transition">
                        <span class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-<?= $icon ?> text-[14px]"></i></span>
                        <span class="flex-1"><?= $label ?></span>
                        <i class="lucide lucide-chevron-right text-[14px] text-ink-400"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="p-3 border-t border-[#ececef] grid gap-2" style="background:#fafafb">
                <a href="<?= $url('/demo') ?>" @click="mobileMenu = false" class="btn btn-soft w-full justify-center" style="height:44px">
                    <i class="lucide lucide-play-circle text-[14px]"></i> Probar demo · 24h gratis
                </a>
                <div class="grid grid-cols-2 gap-2">
                    <a href="<?= $url('/auth/login') ?>" @click="mobileMenu = false" class="btn btn-outline w-full justify-center" style="height:44px">Entrar</a>
                    <a href="<?= $url('/auth/register') ?>" @click="mobileMenu = false" class="btn btn-dark w-full justify-center" style="height:44px">Empezar gratis</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop for mobile -->
    <div x-show="mobileMenu" x-cloak @click="mobileMenu = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         class="lg:hidden fixed inset-0 -z-10" style="background:rgba(15,13,24,.4);backdrop-filter:blur(2px);top:-12px"></div>
</nav>
