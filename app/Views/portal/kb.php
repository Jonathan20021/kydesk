<?php $t = $tenant; ?>
<nav class="bg-white border-b border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6 h-[68px] flex items-center justify-between">
        <a href="<?= $url('/portal/' . $t->slug) ?>" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold" style="background:<?= $e($t->data['primary_color'] ?? '#7c5cff') ?>"><?= strtoupper(substr($t->name,0,1)) ?></div>
            <div class="font-display font-bold text-[14px]"><?= $e($t->name) ?></div>
        </a>
        <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="btn btn-outline btn-sm">Crear ticket</a>
    </div>
</nav>
<section class="py-16">
    <div class="max-w-[960px] mx-auto px-6">
        <div class="text-center mb-10">
            <h1 class="heading-lg">Centro de ayuda</h1>
            <p class="mt-3 text-[15px] max-w-lg mx-auto text-ink-500">Guías y respuestas a preguntas frecuentes.</p>
            <form method="GET" class="mt-6 max-w-md mx-auto">
                <div class="search-pill" style="max-width:none"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="¿Qué buscas?"></div>
            </form>
        </div>
        <?php if (empty($q) && !empty($cats)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
            <?php foreach ($cats as $c): ?>
                <div class="card card-pad hover:shadow-lg transition">
                    <div class="w-11 h-11 rounded-2xl text-white grid place-items-center" style="background:<?= $e($c['color']) ?>"><i class="lucide lucide-<?= $e($c['icon']) ?> text-base"></i></div>
                    <div class="mt-4 font-display font-bold text-[15px]"><?= $e($c['name']) ?></div>
                    <div class="text-[12.5px] mt-1 text-ink-400"><?= $e($c['description'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="space-y-2">
            <?php foreach ($articles as $a): ?>
                <a href="<?= $url('/portal/' . $t->slug . '/kb/' . $a['slug']) ?>" class="card card-pad block hover:shadow-md transition">
                    <div class="flex items-center gap-2 mb-2">
                        <?php if ($a['cat_name']): ?><span class="badge badge-purple"><span class="dot" style="background:<?= $e($a['cat_color']) ?>"></span> <?= $e($a['cat_name']) ?></span><?php endif; ?>
                        <span class="text-[11px] text-ink-400"><?= number_format($a['views']) ?> vistas</span>
                    </div>
                    <h3 class="font-display font-bold text-[16px]"><?= $e($a['title']) ?></h3>
                    <p class="mt-1 text-[13px] text-ink-500 line-clamp-2"><?= $e($a['excerpt'] ?? '') ?></p>
                </a>
            <?php endforeach; ?>
            <?php if (empty($articles)): ?>
                <div class="card card-pad text-center py-16">
                    <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-search-x text-[22px] text-ink-400"></i></div>
                    <div class="font-display font-bold">Sin resultados</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
