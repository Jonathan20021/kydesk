<form method="POST" action="<?= $url('/admin/super-admins/' . $a['id']) ?>" class="max-w-xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="admin-label">Nombre</label><input name="name" value="<?= $e($a['name']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Email</label><input name="email" type="email" value="<?= $e($a['email']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Teléfono</label><input name="phone" value="<?= $e($a['phone']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Nueva contraseña (opcional)</label><input name="password" type="text" class="admin-input" placeholder="Dejar vacío para no cambiar"></div>
            <div>
                <label class="admin-label">Rol</label>
                <select name="role" class="admin-select" <?= $superAuth->isOwner() ? '' : 'disabled' ?>>
                    <option value="admin" <?= $a['role']==='admin'?'selected':'' ?>>Admin</option>
                    <option value="owner" <?= $a['role']==='owner'?'selected':'' ?>>Owner</option>
                    <option value="support" <?= $a['role']==='support'?'selected':'' ?>>Support</option>
                    <option value="billing" <?= $a['role']==='billing'?'selected':'' ?>>Billing</option>
                </select>
                <?php if (!$superAuth->isOwner()): ?><div class="text-[11px] text-ink-400 mt-1">Solo un Owner puede cambiar roles.</div><?php endif; ?>
            </div>
            <div class="md:col-span-2"><label class="admin-label">Notas</label><textarea name="notes" rows="3" class="admin-textarea"><?= $e($a['notes']) ?></textarea></div>
            <div class="md:col-span-2"><label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= $a['is_active']?'checked':'' ?>> Cuenta activa</label></div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar</button>
        <a href="<?= $url('/admin/super-admins') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
