<?php $slug = $tenant->slug;
$typeLabels = [
    'text'=>'Texto','textarea'=>'Texto largo','number'=>'Número','date'=>'Fecha',
    'select'=>'Lista','multiselect'=>'Lista múltiple','checkbox'=>'Checkbox',
    'url'=>'URL','email'=>'Email','phone'=>'Teléfono'
];
?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Custom Fields</h1>
        <p class="text-[13px] text-ink-400">Campos personalizados para tickets · globales o por categoría</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card card-pad lg:col-span-1" x-data="{type:'text'}">
        <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo campo</h3>
        <form method="POST" action="<?= $url('/t/' . $slug . '/custom-fields') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div><label class="label">Label *</label><input name="label" required class="input" placeholder="Ej: Modelo del equipo"></div>
            <div>
                <label class="label">Tipo</label>
                <select name="type" x-model="type" class="input">
                    <?php foreach ($typeLabels as $t=>$lbl): ?><option value="<?= $t ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Categoría (opcional)</label>
                <select name="category_id" class="input">
                    <option value="">— Global (todos los tickets) —</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div x-show="type==='select' || type==='multiselect'" x-cloak>
                <label class="label">Opciones (una por línea)</label>
                <textarea name="options_raw" rows="3" class="input" placeholder="Opción A&#10;Opción B&#10;Opción C"></textarea>
            </div>
            <div><label class="label">Placeholder</label><input name="placeholder" class="input"></div>
            <div><label class="label">Texto de ayuda</label><input name="help_text" class="input"></div>
            <div class="flex items-center gap-3 text-[13px]">
                <label class="flex items-center gap-1.5"><input type="checkbox" name="is_required" value="1"> Requerido</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" name="is_visible_portal" value="1" checked> Visible en portal</label>
            </div>
            <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear campo</button>
        </form>
    </div>

    <div class="lg:col-span-2 space-y-2">
        <?php if (empty($fields)): ?>
            <div class="card card-pad text-center py-12">
                <i class="lucide lucide-list-plus text-[24px] text-ink-300"></i>
                <h3 class="font-display font-bold mt-3">Sin custom fields</h3>
                <p class="text-[12.5px] text-ink-400 mt-1">Agregá campos como modelo, serial, ubicación, etc.</p>
            </div>
        <?php else: foreach ($fields as $f):
            $opts = $f['options'] ? (json_decode($f['options'], true) ?: []) : [];
        ?>
            <div class="card card-pad" x-data="{open:false, type:'<?= $e($f['type']) ?>'}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-list text-[16px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="font-display font-bold text-[14px]"><?= $e($f['label']) ?></div>
                            <span class="badge badge-gray font-mono text-[10px]"><?= $e($f['field_key']) ?></span>
                            <span class="badge badge-purple"><?= $typeLabels[$f['type']] ?? $f['type'] ?></span>
                            <?php if ($f['is_required']): ?><span class="badge badge-amber">Requerido</span><?php endif; ?>
                            <?php if (!$f['is_active']): ?><span class="badge badge-gray">Inactivo</span><?php endif; ?>
                        </div>
                        <?php if (!empty($f['category_name'])): ?>
                            <div class="text-[11.5px] text-ink-400 mt-0.5">
                                <span class="inline-flex items-center gap-1"><i class="lucide lucide-<?= $e($f['category_icon']) ?> text-[10px]"></i> <?= $e($f['category_name']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="text-[11.5px] text-ink-400 mt-0.5">Global · todos los tickets</div>
                        <?php endif; ?>
                    </div>
                    <button type="button" @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i></button>
                </div>

                <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                    <form method="POST" action="<?= $url('/t/' . $slug . '/custom-fields/' . $f['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <div><label class="label">Label</label><input name="label" value="<?= $e($f['label']) ?>" class="input"></div>
                        <div>
                            <label class="label">Tipo</label>
                            <select name="type" x-model="type" class="input">
                                <?php foreach ($typeLabels as $t=>$lbl): ?>
                                    <option value="<?= $t ?>" <?= $f['type']===$t?'selected':'' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="label">Categoría</label>
                            <select name="category_id" class="input">
                                <option value="">— Global —</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)$f['category_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><label class="label">Orden</label><input name="sort_order" type="number" value="<?= (int)$f['sort_order'] ?>" class="input"></div>
                        <div class="md:col-span-2" x-show="type==='select' || type==='multiselect'" x-cloak>
                            <label class="label">Opciones (una por línea)</label>
                            <textarea name="options_raw" rows="3" class="input"><?= $e(implode("\n", $opts)) ?></textarea>
                        </div>
                        <div><label class="label">Placeholder</label><input name="placeholder" value="<?= $e($f['placeholder']) ?>" class="input"></div>
                        <div><label class="label">Texto de ayuda</label><input name="help_text" value="<?= $e($f['help_text']) ?>" class="input"></div>
                        <div class="md:col-span-2 flex items-center gap-4 text-[13px]">
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="is_required" value="1" <?= $f['is_required']?'checked':'' ?>> Requerido</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="is_visible_portal" value="1" <?= $f['is_visible_portal']?'checked':'' ?>> Visible en portal</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" <?= $f['is_active']?'checked':'' ?>> Activo</label>
                        </div>
                        <div class="md:col-span-2 flex justify-between gap-2 pt-2" style="border-top:1px solid var(--border)">
                            <button type="button" onclick="if(confirm('Eliminar campo y sus valores?')) document.getElementById('del-<?= (int)$f['id'] ?>').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                            <div class="flex gap-2">
                                <button type="button" @click="open=false" class="btn btn-soft btn-sm">Cancelar</button>
                                <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                    <form id="del-<?= (int)$f['id'] ?>" method="POST" action="<?= $url('/t/' . $slug . '/custom-fields/' . $f['id'] . '/delete') ?>" style="display:none">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    </form>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
