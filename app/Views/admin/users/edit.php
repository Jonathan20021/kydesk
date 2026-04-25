<form method="POST" action="<?= $url('/admin/users/' . $u['id']) ?>" class="max-w-2xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-1">Empresa</div>
        <div class="text-[14px] font-semibold mb-4"><?= $e($u['tenant_name']) ?></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="admin-label">Nombre</label><input name="name" value="<?= $e($u['name']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Email</label><input name="email" type="email" value="<?= $e($u['email']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Cargo</label><input name="title" value="<?= $e($u['title']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Teléfono</label><input name="phone" value="<?= $e($u['phone']) ?>" class="admin-input"></div>
            <div>
                <label class="admin-label">Rol</label>
                <select name="role_id" class="admin-select">
                    <option value="">— Sin rol —</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= $u['role_id']==$r['id']?'selected':'' ?>><?= $e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="admin-label">Nueva contraseña (opcional)</label><input name="password" type="text" class="admin-input" placeholder="Dejar vacío para no cambiar"></div>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= $u['is_active']?'checked':'' ?>> Activo</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_technician" value="1" <?= $u['is_technician']?'checked':'' ?>> Técnico</label>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar cambios</button>
        <a href="<?= $url('/admin/users') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
