<?php $slug = $tenant->slug; $m = $macro; ?>

<a href="<?= $url('/t/' . $slug . '/macros') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a plantillas</a>

<div>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Editar plantilla</h1>
    <p class="text-[13px] text-ink-400">Actualiza el contenido y la categoría</p>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/macros/' . $m['id']) ?>" class="card card-pad max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="section-head">
        <div class="section-head-icon"><i class="lucide lucide-pencil text-[16px]"></i></div>
        <div>
            <h3 class="section-title">Plantilla</h3>
            <div class="section-head-meta"><?= (int)$m['use_count'] ?> usos · creada <?= date('d/m/Y', strtotime($m['created_at'])) ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2"><label class="label">Nombre *</label><input name="name" required value="<?= $e($m['name']) ?>" class="input"></div>
        <div>
            <label class="label">Categoría</label>
            <select name="category" class="input">
                <?php foreach ([['general','General'],['investigacion','Investigación'],['resolucion','Resolución'],['esperando','Esperando cliente'],['cierre','Cierre']] as [$v,$l]): ?>
                    <option value="<?= $v ?>" <?= $m['category']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="mt-4">
        <label class="label">Contenido *</label>
        <textarea name="body" required rows="8" class="input"><?= $e($m['body']) ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div><label class="label">Atajo</label><input name="shortcut" value="<?= $e($m['shortcut'] ?? '') ?>" placeholder="/revisando" class="input font-mono"></div>
        <div class="flex items-center gap-2 pt-7">
            <label class="flex items-center gap-2 text-[13px] cursor-pointer">
                <input type="checkbox" name="is_internal" value="1" <?= $m['is_internal']?'checked':'' ?> class="accent-amber-500">
                <span><i class="lucide lucide-lock text-[12px]"></i> Marcar como nota interna</span>
            </label>
        </div>
    </div>

    <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/macros') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar cambios</button>
    </div>
</form>
