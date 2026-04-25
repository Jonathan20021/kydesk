<?php use App\Core\Helpers; $slug = $tenant->slug;
$catMeta = [
    'general' => ['General','#7c5cff','#f3f0ff','sparkles'],
    'investigacion' => ['Investigación','#3b82f6','#dbeafe','search'],
    'resolucion' => ['Resolución','#16a34a','#d1fae5','check-circle-2'],
    'esperando' => ['Esperando cliente','#f59e0b','#fef3c7','clock'],
    'cierre' => ['Cierre','#6b7280','#f3f4f6','flag'],
];
?>

<div x-data="{showForm:false, preview:''}" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Plantillas de respuesta</h1>
            <p class="text-[13px] text-ink-400">Respuestas pre-armadas para acelerar a tu equipo · <?= number_format($stats['total']) ?> plantillas · <?= number_format($stats['uses']) ?> usos</p>
        </div>
        <button @click="showForm=!showForm" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nueva plantilla</button>
    </div>

    <form x-show="showForm" x-cloak x-transition method="POST" action="<?= $url('/t/' . $slug . '/macros') ?>" class="card card-pad">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div class="section-head">
            <div class="section-head-icon"><i class="lucide lucide-zap text-[16px]"></i></div>
            <div>
                <h3 class="section-title">Nueva plantilla</h3>
                <div class="section-head-meta">Reutilizable en cualquier ticket</div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2"><label class="label">Nombre *</label><input name="name" required placeholder="Ej. Estoy revisando tu caso" class="input"></div>
            <div>
                <label class="label">Categoría</label>
                <select name="category" class="input">
                    <?php foreach ($catMeta as $key => [$lbl,]): ?><option value="<?= $key ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-4"><label class="label">Contenido *</label><textarea name="body" required rows="5" placeholder="Hola {{nombre}}, estoy revisando tu caso ahora mismo. Te respondo en breve." class="input"></textarea>
            <div class="text-[11px] text-ink-400 mt-1.5">Tip: usá variables como <code class="font-mono bg-bg px-1 rounded">{{nombre}}</code>, <code class="font-mono bg-bg px-1 rounded">{{ticket}}</code></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div><label class="label">Atajo (opcional)</label><input name="shortcut" placeholder="/revisando" class="input font-mono"></div>
            <div class="flex items-center gap-2 pt-7">
                <label class="flex items-center gap-2 text-[13px] cursor-pointer">
                    <input type="checkbox" name="is_internal" value="1" class="accent-amber-500">
                    <span><i class="lucide lucide-lock text-[12px]"></i> Marcar como nota interna</span>
                </label>
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-[#ececef]">
            <button type="button" @click="showForm=false" class="btn btn-outline btn-sm">Cancelar</button>
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-check"></i> Guardar plantilla</button>
        </div>
    </form>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="search-pill md:col-span-2" style="max-width:none"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar plantillas…"></div>
        <select name="category" class="input">
            <option value="">Todas las categorías</option>
            <?php foreach ($catMeta as $key => [$lbl,]): ?><option value="<?= $key ?>" <?= $cat===$key?'selected':'' ?>><?= $lbl ?></option><?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($macros)): ?>
        <div class="card card-pad">
            <div class="empty-state py-16">
                <div class="empty-illust"><i class="lucide lucide-zap text-[28px]"></i></div>
                <div class="empty-state-title">Sin plantillas todavía</div>
                <p class="empty-state-text mb-5">Crea tu primera plantilla para acelerar respuestas repetitivas</p>
                <button @click="showForm=true" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Crear plantilla</button>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($macros as $m):
                $meta = $catMeta[$m['category']] ?? $catMeta['general'];
                [$cl,$cc,$cb,$cic] = $meta;
                $isInternal = (int)$m['is_internal'] === 1;
            ?>
                <div class="card card-pad spotlight-card relative" style="<?= $isInternal?'background:linear-gradient(135deg,#fef9c3 0%,#fafafb 70%);border-color:#fde68a':'' ?>">
                    <div class="bento-glow"></div>
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $cb ?>;color:<?= $cc ?>"><i class="lucide lucide-<?= $cic ?> text-[16px]"></i></div>
                            <div>
                                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em]" style="color:<?= $cc ?>"><?= $cl ?></div>
                                <?php if ($m['shortcut']): ?><div class="font-mono text-[11px] text-ink-500 mt-0.5"><?= $e($m['shortcut']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isInternal): ?><span class="badge badge-amber"><i class="lucide lucide-lock text-[10px]"></i> Interna</span><?php endif; ?>
                    </div>
                    <h3 class="font-display font-bold text-[15px] tracking-[-0.015em] line-clamp-2"><?= $e($m['name']) ?></h3>
                    <p class="text-[12.5px] mt-2 text-ink-500 line-clamp-3 leading-relaxed"><?= $e($m['body']) ?></p>
                    <div class="mt-4 pt-4 flex items-center justify-between border-t border-[#ececef]">
                        <span class="text-[11px] text-ink-400 inline-flex items-center gap-1.5"><i class="lucide lucide-zap text-[11px]"></i> <?= (int)$m['use_count'] ?> usos</span>
                        <div class="flex items-center gap-1.5">
                            <a href="<?= $url('/t/' . $slug . '/macros/' . $m['id']) ?>" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i></a>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/macros/' . $m['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="btn btn-outline btn-xs" style="color:#ef4444;border-color:#fecaca"><i class="lucide lucide-trash-2 text-[12px]"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
