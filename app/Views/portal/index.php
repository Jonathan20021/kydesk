<?php
$t = $tenant;
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
            <div class="hidden lg:flex items-center gap-0.5 text-[13px] font-medium text-ink-500 ml-4">
                <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Crear ticket</a>
                <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Base de conocimiento</a>
            </div>
            <div class="flex items-center gap-1.5 ml-auto">
                <a href="https://kydesk.kyrosrd.com" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-ink-400 hover:text-ink-900 transition">
                    Powered by
                    <span class="font-display font-bold text-ink-900">Kydesk</span>
                </a>
                <a href="<?= $url('/auth/login') ?>" class="btn btn-ghost btn-sm">Acceso equipo</a>
            </div>
        </div>
    </div>
</nav>
<div class="h-[88px]"></div>

<section class="py-16 relative overflow-hidden">
    <!-- Aurora background sutil con el brand del tenant -->
    <div class="absolute inset-x-0 top-0 h-[500px] pointer-events-none -z-10 overflow-hidden">
        <div class="absolute" style="width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(<?= $rgbStr ?>,.16),transparent 70%);top:-200px;left:-100px;filter:blur(60px)"></div>
        <div class="absolute" style="width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(<?= $rgbStr ?>,.10),transparent 70%);top:-100px;right:-100px;filter:blur(60px)"></div>
    </div>
    <div class="max-w-[840px] mx-auto px-6 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10.5px] font-bold uppercase tracking-[0.16em] mb-5" style="background:rgba(<?= $rgbStr ?>,.12);color:<?= $e($brand) ?>;border:1px solid rgba(<?= $rgbStr ?>,.25)"><i class="lucide lucide-life-buoy text-[12px]"></i> Centro de soporte</div>
        <h1 class="font-display font-extrabold tracking-[-0.025em] leading-[1.05]" style="font-size:clamp(2rem,4vw + 1rem,3.2rem);text-wrap:balance">¿En qué podemos ayudarte?</h1>
        <p class="mt-4 text-[16px] max-w-md mx-auto text-ink-500">Crea un ticket o consulta la base de conocimiento.</p>
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-3 text-left">
            <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="card card-pad block hover:shadow-lg transition group">
                <div class="w-12 h-12 rounded-2xl bg-brand-500 text-white grid place-items-center"><i class="lucide lucide-plus text-[18px]"></i></div>
                <h3 class="mt-5 font-display font-bold text-[16px]">Crear ticket</h3>
                <p class="mt-1.5 text-[12.5px] text-ink-400">Reporta una incidencia.</p>
                <div class="mt-4 text-[12px] font-semibold flex items-center gap-1">Empezar <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-0.5 transition"></i></div>
            </a>
            <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="card card-pad block hover:shadow-lg transition group">
                <div class="w-12 h-12 rounded-2xl bg-[#f3f4f6] text-ink-700 grid place-items-center"><i class="lucide lucide-book-open text-[18px]"></i></div>
                <h3 class="mt-5 font-display font-bold text-[16px]">Centro de ayuda</h3>
                <p class="mt-1.5 text-[12.5px] text-ink-400">Guías y respuestas.</p>
                <div class="mt-4 text-[12px] font-semibold flex items-center gap-1">Explorar <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-0.5 transition"></i></div>
            </a>
            <div class="card card-pad">
                <div class="w-12 h-12 rounded-2xl bg-[#f3f4f6] text-ink-700 grid place-items-center"><i class="lucide lucide-search text-[18px]"></i></div>
                <h3 class="mt-5 font-display font-bold text-[16px]">Seguir mi ticket</h3>
                <p class="mt-1.5 text-[12.5px] text-ink-400">Usa el enlace que recibiste.</p>
            </div>
        </div>
        <?php if ($t->data['support_email']): ?>
            <div class="mt-10 text-[13px] text-ink-400">O escríbenos a <a href="mailto:<?= $e($t->data['support_email']) ?>" class="font-semibold text-ink-900"><?= $e($t->data['support_email']) ?></a></div>
        <?php endif; ?>
    </div>
</section>
