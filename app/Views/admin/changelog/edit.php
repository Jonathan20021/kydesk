<?php
$isEdit = !empty($entry);
$action = $isEdit ? '/admin/changelog/' . (int)$entry['id'] : '/admin/changelog';
$publishedAt = $isEdit ? date('Y-m-d', strtotime($entry['published_at'])) : date('Y-m-d');
?>

<form method="POST" action="<?= $url($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4 max-w-6xl"
      x-data='{ items: <?= htmlspecialchars(json_encode(empty($items) ? [["item_type"=>"feature","text"=>""]] : array_map(fn($i)=>["item_type"=>$i["item_type"],"text"=>$i["text"]], $items)), ENT_QUOTES) ?> }'>
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <!-- Main column -->
    <div class="lg:col-span-2 space-y-4">
        <div class="admin-card admin-card-pad space-y-4">
            <h3 class="admin-h2 mb-2">Información</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="admin-label">Versión <span class="text-rose-500">*</span></label>
                    <input name="version" required maxlength="40" class="admin-input font-mono" value="<?= $e($entry['version'] ?? 'v') ?>" placeholder="v3.1.0">
                </div>
                <div class="md:col-span-2">
                    <label class="admin-label">Tipo de release</label>
                    <select name="release_type" class="admin-input">
                        <?php foreach (['major'=>'Major (cambios grandes)','minor'=>'Minor (nueva feature)','patch'=>'Patch (fixes)'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($entry['release_type'] ?? 'minor')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="admin-label">Título <span class="text-rose-500">*</span></label>
                <input name="title" required maxlength="180" class="admin-input" value="<?= $e($entry['title'] ?? '') ?>" placeholder="Tablero Kanban + Automatizaciones IA">
            </div>
            <div>
                <label class="admin-label">Resumen <span class="text-slate-400 font-normal">(opcional, aparece en el meta description)</span></label>
                <textarea name="summary" rows="2" maxlength="255" class="admin-input"><?= $e($entry['summary'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="admin-card admin-card-pad">
            <div class="flex items-center justify-between mb-3">
                <h3 class="admin-h2">Cambios</h3>
                <button type="button" @click="items.push({item_type:'feature', text:''})" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="lucide lucide-plus text-[13px]"></i> Agregar</button>
            </div>
            <p class="text-sm text-slate-500 mb-4">Cada item aparece como una línea bajo el release. Tipos: <strong>feature</strong> (morado), <strong>fix</strong> (ámbar), <strong>improvement</strong> (verde).</p>
            <div class="space-y-2">
                <template x-for="(item, idx) in items" :key="idx">
                    <div class="flex items-start gap-2 p-2 rounded-xl border border-slate-200">
                        <select :name="'item_type[]'" x-model="item.item_type" class="admin-input" style="max-width:140px;height:38px">
                            <option value="feature">feature</option>
                            <option value="fix">fix</option>
                            <option value="improvement">improvement</option>
                        </select>
                        <input :name="'item_text[]'" x-model="item.text" maxlength="500" class="admin-input flex-1" style="height:38px" placeholder="Descripción del cambio…">
                        <button type="button" @click="items.splice(idx, 1)" class="admin-btn admin-btn-sm" style="color:#dc2626;border:1px solid #fecaca;background:#fef2f2;height:38px"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        <div class="admin-card admin-card-pad">
            <h3 class="admin-h2 mb-3">Publicación</h3>
            <div class="space-y-3">
                <div>
                    <label class="admin-label">Fecha</label>
                    <input type="date" name="published_at" class="admin-input" value="<?= $e($publishedAt) ?>">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" <?= !$isEdit || (int)$entry['is_published'] ? 'checked' : '' ?>>
                    Publicado (visible en /changelog)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_featured" value="1" <?= ($isEdit && (int)$entry['is_featured']) ? 'checked' : '' ?>>
                    <i class="lucide lucide-star text-[14px] text-amber-500"></i>
                    Destacada (hero pill de la landing)
                </label>
                <p class="text-[11px] text-slate-500">Solo una entrada puede estar destacada a la vez.</p>
            </div>
        </div>

        <div class="admin-card admin-card-pad">
            <h3 class="admin-h2 mb-3">Hero pill</h3>
            <p class="text-sm text-slate-500 mb-3">Texto que aparecerá en la pill del hero principal cuando esta entrada esté <em>destacada</em>. Si lo dejas vacío se usará el título.</p>
            <input name="hero_pill_label" maxlength="80" class="admin-input" value="<?= $e($entry['hero_pill_label'] ?? '') ?>" placeholder="Tablero Kanban + Automatizaciones IA">
            <div class="mt-3 p-3 rounded-xl" style="background:#f3f0ff;border:1px solid #cdbfff">
                <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-brand-700 mb-1.5">Preview</div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-[#cdbfff80] text-[12px]">
                    <span class="px-2 py-0.5 rounded-full bg-brand-500 text-white text-[10px] font-bold">NUEVO</span>
                    <span class="font-semibold text-ink-700" x-text="document.querySelector('input[name=hero_pill_label]')?.value || document.querySelector('input[name=title]')?.value || 'Texto del pill'"></span>
                </div>
            </div>
        </div>

        <div class="admin-card admin-card-pad">
            <button class="admin-btn admin-btn-primary w-full"><i class="lucide lucide-check"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear entrada' ?></button>
            <a href="<?= $url('/admin/changelog') ?>" class="admin-btn admin-btn-secondary w-full mt-2 justify-center">Cancelar</a>
        </div>
    </div>
</form>
