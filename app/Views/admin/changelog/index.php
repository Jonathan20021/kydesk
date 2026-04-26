<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
    <div>
        <p class="text-sm text-slate-500">Estas entradas aparecen en <code class="font-mono text-xs">/changelog</code> y la "destacada" se muestra en la pill del hero de la landing.</p>
    </div>
    <a href="<?= $url('/admin/changelog/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus"></i> Nueva entrada</a>
</div>

<?php if (empty($entries)): ?>
    <div class="admin-card admin-card-pad text-center py-16">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 grid place-items-center mx-auto mb-3"><i class="lucide lucide-newspaper text-[22px] text-slate-400"></i></div>
        <div class="font-display font-bold">Sin entradas</div>
        <p class="text-sm text-slate-500 mt-1">Crea la primera entrada del changelog.</p>
        <a href="<?= $url('/admin/changelog/create') ?>" class="admin-btn admin-btn-primary mt-4 inline-flex"><i class="lucide lucide-plus"></i> Nueva entrada</a>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php
        $tagColors = [
            'major' => ['#d946ef','#fae8ff','MAJOR'],
            'minor' => ['#7c5cff','#f3f0ff','MINOR'],
            'patch' => ['#22c55e','#dcfce7','PATCH'],
        ];
        foreach ($entries as $e):
            [$tCol, $tBg, $tLbl] = $tagColors[$e['release_type']] ?? $tagColors['minor']; ?>
            <div class="admin-card admin-card-pad flex flex-wrap items-center gap-4">
                <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0" style="background:<?= $tBg ?>;color:<?= $tCol ?>;border:1px solid <?= $tCol ?>30">
                    <i class="lucide lucide-sparkles text-[16px]"></i>
                </div>
                <div class="flex-1 min-w-[220px]">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="font-mono text-[12px] font-bold"><?= $e($e['version']) ?></span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full" style="background:<?= $tBg ?>;color:<?= $tCol ?>"><?= $tLbl ?></span>
                        <?php if ((int)$e['is_featured']): ?><span class="text-[10px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700"><i class="lucide lucide-star text-[10px]"></i> DESTACADA</span><?php endif; ?>
                        <?php if (!(int)$e['is_published']): ?><span class="text-[10px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">BORRADOR</span><?php endif; ?>
                    </div>
                    <div class="font-display font-bold text-[14.5px]"><?= $e($e['title']) ?></div>
                    <div class="text-[11.5px] text-slate-500 mt-0.5"><?= date('d/m/Y', strtotime($e['published_at'])) ?> · <?= (int)$e['items_count'] ?> cambios</div>
                </div>
                <div class="flex items-center gap-2 ml-auto flex-wrap">
                    <?php if (!(int)$e['is_featured']): ?>
                        <form method="POST" action="<?= $url('/admin/changelog/' . $e['id'] . '/feature') ?>">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" title="Destacar en hero"><i class="lucide lucide-star text-[13px]"></i></button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= $url('/admin/changelog/' . $e['id'] . '/publish') ?>">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="admin-btn admin-btn-secondary admin-btn-sm" title="<?= (int)$e['is_published']?'Despublicar':'Publicar' ?>"><i class="lucide lucide-<?= (int)$e['is_published']?'eye-off':'eye' ?> text-[13px]"></i></button>
                    </form>
                    <a href="<?= $url('/admin/changelog/' . $e['id']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="lucide lucide-pencil text-[13px]"></i> Editar</a>
                    <form method="POST" action="<?= $url('/admin/changelog/' . $e['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar esta entrada?')">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="admin-btn admin-btn-sm" style="color:#dc2626;border:1px solid #fecaca;background:#fef2f2"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
