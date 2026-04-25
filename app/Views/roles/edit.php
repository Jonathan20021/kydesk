<?php $slug = $tenant->slug; ?>
<a href="<?= $url('/t/' . $slug . '/roles') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[14px]"></i> Volver a roles</a>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl grid place-items-center <?= $role['slug']==='owner'?'bg-rose-100 text-rose-700':($role['is_system']?'bg-amber-100 text-amber-700':'bg-brand-50 text-brand-700') ?>"><i class="lucide lucide-<?= $role['slug']==='owner'?'crown':($role['is_system']?'shield-check':'shield') ?> text-base"></i></div>
        <div>
            <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em] leading-tight">Editar rol</h1>
            <p class="text-[13px] text-ink-400"><?= $e($role['name']) ?> · <?= $role['is_system']?'Rol del sistema':'Rol personalizado' ?></p>
        </div>
    </div>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/roles/' . $role['id']) ?>" class="space-y-4 max-w-5xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="card card-pad">
        <div class="section-head">
            <div class="section-head-icon"><i class="lucide lucide-info text-[16px]"></i></div>
            <h3 class="section-title">Información del rol</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="label">Nombre</label><input name="name" required value="<?= $e($role['name']) ?>" <?= $role['is_system']?'disabled':'' ?> class="input"></div>
            <div><label class="label">Descripción</label><input name="description" value="<?= $e($role['description'] ?? '') ?>" placeholder="Breve resumen" class="input"></div>
        </div>
    </div>

    <?php if ($role['slug'] === 'owner'): ?>
        <div class="card card-pad flex items-start gap-4" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-color:#fcd34d">
            <div class="w-12 h-12 rounded-2xl bg-amber-300 text-amber-800 grid place-items-center flex-shrink-0"><i class="lucide lucide-crown text-base"></i></div>
            <div>
                <div class="font-display font-bold text-[16px] text-amber-900">Rol Owner</div>
                <p class="text-[13px] mt-1 text-amber-800">Este rol tiene acceso total a todas las funciones del workspace y no puede restringirse.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card card-pad" x-data="{toggleModule(ids){const all=ids.every(id=>this.$root.querySelector('input[value=\''+id+'\']').checked);ids.forEach(id=>this.$root.querySelector('input[value=\''+id+'\']').checked=!all);}}">
            <div class="section-head">
                <div class="section-head-icon"><i class="lucide lucide-key text-[16px]"></i></div>
                <div class="flex-1">
                    <h3 class="section-title">Permisos</h3>
                    <div class="section-head-meta">Selecciona qué acciones puede realizar este rol</div>
                </div>
            </div>
            <div class="space-y-3">
                <?php foreach ($byModule as $mod => $perms): ?>
                    <div class="rounded-2xl p-5 border border-[#ececef] bg-[#fafafb]">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-display font-bold text-[14px] capitalize flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl bg-white border border-[#ececef] grid place-items-center"><i class="lucide lucide-layers text-[14px] text-brand-600"></i></span>
                                <span><?= $e($mod) ?></span>
                                <span class="badge badge-gray text-[10px]"><?= count($perms) ?> permisos</span>
                            </h4>
                            <button type="button" @click="toggleModule(<?= json_encode(array_column($perms,'id')) ?>)" class="btn btn-ghost btn-xs">Alternar todos</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            <?php foreach ($perms as $p): $on = in_array((int)$p['id'], $assignedIds, true); ?>
                                <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl cursor-pointer text-[12.5px] bg-white border transition <?= $on?'':'border-[#ececef]' ?>" style="<?= $on?'background:#f3f0ff;border-color:#7c5cff;color:#5a3aff':'border-color:#ececef' ?>">
                                    <input type="checkbox" name="permissions[]" value="<?= (int)$p['id'] ?>" <?= $on?'checked':'' ?> class="accent-brand-500">
                                    <span class="flex-1 font-medium"><?= $e($p['label']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-end gap-2 sticky bottom-4 z-20">
        <a href="<?= $url('/t/' . $slug . '/roles') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar cambios</button>
    </div>
</form>
