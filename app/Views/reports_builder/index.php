<?php $slug = $tenant->slug; ?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Reports Builder</h1>
        <p class="text-[13px] text-ink-400">Construí reportes personalizados con widgets y filtros guardados</p>
    </div>
    <form method="POST" action="<?= $url('/t/' . $slug . '/reports-builder/create') ?>">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <button class="btn btn-primary"><i class="lucide lucide-plus"></i> Nuevo reporte</button>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    <?php foreach ($reports as $r): ?>
        <a href="<?= $url('/t/' . $slug . '/reports-builder/' . (int)$r['id']) ?>" class="card card-pad hover:shadow-md transition" style="text-decoration:none;color:inherit">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-bar-chart-3 text-[16px]"></i></div>
                <?php if ($r['is_favorite']): ?><i class="lucide lucide-star text-amber-500 text-[14px]"></i><?php endif; ?>
            </div>
            <div class="font-display font-bold text-[14.5px] mt-3"><?= $e($r['name']) ?></div>
            <?php if (!empty($r['description'])): ?><p class="text-[12px] text-ink-500 mt-1 line-clamp-2"><?= $e($r['description']) ?></p><?php endif; ?>
            <div class="text-[11px] text-ink-400 mt-3">Por <?= $e($r['author_name'] ?? '—') ?> · <?= $e($r['updated_at']) ?></div>
        </a>
    <?php endforeach; ?>
    <?php if (empty($reports)): ?>
        <div class="sm:col-span-2 lg:col-span-3 card card-pad text-center py-12">
            <i class="lucide lucide-bar-chart-3 text-[24px] text-ink-300"></i>
            <h3 class="font-display font-bold mt-3">Sin reportes</h3>
            <p class="text-[12.5px] text-ink-400 mt-1">Creá tu primer reporte personalizado.</p>
        </div>
    <?php endif; ?>
</div>
