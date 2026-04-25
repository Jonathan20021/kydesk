<?php $slug = $tenant->slug; ?>
<a href="<?= $url('/t/' . $slug . '/kb') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500"><i class="lucide lucide-arrow-left"></i> Volver</a>
<h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Nuevo artículo</h1>
<form method="POST" action="<?= $url('/t/' . $slug . '/kb') ?>" class="card card-pad space-y-4 max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div><label class="label">Título *</label><input name="title" required class="input"></div>
    <div><label class="label">Resumen</label><input name="excerpt" class="input"></div>
    <div><label class="label">Contenido (Markdown)</label><textarea name="body" rows="14" class="input font-mono text-[13px]" placeholder="# Título&#10;&#10;Escribe aquí…"></textarea></div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="label">Categoría</label>
            <select name="category_id" class="input"><option value="0">Sin categoría</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div><label class="label">Estado</label><select name="status" class="input"><option value="draft">Borrador</option><option value="published">Publicado</option></select></div>
        <div><label class="label">Visibilidad</label><select name="visibility" class="input"><option value="internal">Interno</option><option value="public">Público</option></select></div>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/kb') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm">Crear artículo</button>
    </div>
</form>
