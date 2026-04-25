<?php $t = $tenant; ?>
<nav class="bg-white border-b border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6 h-[68px] flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold" style="background:<?= $e($t->data['primary_color'] ?? '#7c5cff') ?>"><?= strtoupper(substr($t->name,0,1)) ?></div>
            <div>
                <div class="font-display font-bold text-[14px]"><?= $e($t->name) ?></div>
                <div class="text-[10.5px] text-ink-400 uppercase tracking-[0.1em]">Centro de soporte</div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="btn btn-ghost btn-sm"><i class="lucide lucide-book-open text-[13px]"></i> Conocimiento</a>
            <a href="<?= $url('/auth/login') ?>" class="btn btn-outline btn-sm">Acceso equipo</a>
        </div>
    </div>
</nav>
<section class="py-24">
    <div class="max-w-[840px] mx-auto px-6 text-center">
        <div class="w-16 h-16 rounded-3xl text-white grid place-items-center mx-auto mb-6" style="background:<?= $e($t->data['primary_color'] ?? '#7c5cff') ?>;box-shadow:var(--shadow-purple)"><i class="lucide lucide-life-buoy text-[26px]"></i></div>
        <h1 class="heading-xl" style="text-wrap:balance">¿En qué podemos ayudarte?</h1>
        <p class="mt-4 text-[16px] max-w-md mx-auto text-ink-400">Crea un ticket o consulta la base de conocimiento.</p>
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
