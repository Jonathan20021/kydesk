<?php $slug = $tenant->slug; ?>
<a href="<?= $url('/t/' . $slug . '/users') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500"><i class="lucide lucide-arrow-left"></i> Volver</a>
<h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Editar usuario</h1>
<p class="text-[13px] text-ink-400"><?= $e($u['email']) ?></p>
<form method="POST" action="<?= $url('/t/' . $slug . '/users/' . $u['id']) ?>" class="card card-pad space-y-4 max-w-2xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="label">Nombre</label><input name="name" required value="<?= $e($u['name']) ?>" class="input"></div>
        <div><label class="label">Email</label><input value="<?= $e($u['email']) ?>" disabled class="input"></div>
        <div><label class="label">Nueva contraseña</label><input name="password" type="password" minlength="6" placeholder="Vacío para mantener" class="input"></div>
        <div><label class="label">Cargo</label><input name="title" value="<?= $e($u['title']) ?>" class="input"></div>
        <div><label class="label">Teléfono</label><input name="phone" value="<?= $e($u['phone']) ?>" class="input"></div>
        <div>
            <label class="label">Rol</label>
            <select name="role_id" class="input"><?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$u['role_id']===(int)$r['id']?'selected':'' ?>><?= $e($r['name']) ?></option><?php endforeach; ?></select>
        </div>
    </div>
    <div class="pt-4 flex items-center gap-6 border-t border-[#ececef]">
        <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_technician" value="1" <?= $u['is_technician']?'checked':'' ?>> Es técnico</label>
        <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" <?= $u['is_active']?'checked':'' ?>> Activo</label>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/users') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button>
    </div>
</form>
