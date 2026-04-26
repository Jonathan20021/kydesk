<nav class="fixed top-4 inset-x-0 z-50 px-4">
    <div class="nav-land">
        <div class="nav-land-inner">
            <a href="<?= $url('/') ?>" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px] glow-purple" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">K</div>
                <span class="font-display font-extrabold text-[16px] tracking-[-0.02em]">Kydesk</span>
            </a>
            <div class="hidden lg:flex items-center gap-0.5 text-[13px] font-medium text-ink-500 ml-4">
                <a href="<?= $url('/features') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Producto</a>
                <a href="<?= $url('/pricing') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Precios</a>
                <a href="<?= $url('/developers') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition flex items-center gap-1"><i class="lucide lucide-code text-[12px]"></i> Developers</a>
                <a href="<?= $url('/#customers') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Clientes</a>
                <a href="<?= $url('/#testimonials') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Testimonios</a>
                <a href="<?= $url('/contact') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Contacto</a>
            </div>
            <div class="flex items-center gap-1.5 ml-auto">
                <a href="<?= $url('/demo') ?>" class="btn btn-ghost btn-sm hidden sm:inline-flex"><i class="lucide lucide-play-circle text-[13px]"></i> Demo</a>
                <a href="<?= $url('/auth/login') ?>" class="btn btn-ghost btn-sm">Entrar</a>
                <a href="<?= $url('/auth/register') ?>" class="btn btn-dark btn-sm">Empezar gratis <i class="lucide lucide-arrow-right text-[13px]"></i></a>
            </div>
        </div>
    </div>
</nav>
