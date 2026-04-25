<?php $slug = $tenant->slug; ?>
<a href="<?= $url('/t/' . $slug . '/users') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500"><i class="lucide lucide-arrow-left"></i> Volver</a>
<h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Invitar usuario</h1>
<form method="POST" action="<?= $url('/t/' . $slug . '/users') ?>" class="card card-pad space-y-4 max-w-2xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="label">Nombre completo</label><input name="name" required class="input"></div>
        <div><label class="label">Email</label><input name="email" type="email" required class="input"></div>
        <div><label class="label">Contraseña temporal</label><input name="password" type="password" required minlength="6" class="input"></div>
        <div><label class="label">Cargo</label><input name="title" class="input"></div>
        <div><label class="label">Teléfono</label><input name="phone" class="input"></div>
        <div>
            <label class="label">Rol</label>
            <select name="role_id" class="input"><?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= $r['slug']==='agent'?'selected':'' ?>><?= $e($r['name']) ?></option><?php endforeach; ?></select>
        </div>
    </div>
    <div class="pt-4 flex items-center gap-6 border-t border-[#ececef]">
        <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_technician" value="1"> Es técnico</label>
        <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" checked> Activo</label>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/users') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm">Crear</button>
    </div>
</form>
