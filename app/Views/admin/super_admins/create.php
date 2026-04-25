<form method="POST" action="<?= $url('/admin/super-admins') ?>" class="max-w-xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="admin-label">Nombre *</label><input name="name" required class="admin-input"></div>
            <div><label class="admin-label">Email *</label><input name="email" type="email" required class="admin-input"></div>
            <div><label class="admin-label">Teléfono</label><input name="phone" class="admin-input"></div>
            <div><label class="admin-label">Contraseña *</label><input name="password" type="text" required class="admin-input" placeholder="Mín. 8 caracteres"></div>
            <div>
                <label class="admin-label">Rol *</label>
                <select name="role" class="admin-select" required>
                    <option value="admin">Admin (acceso casi total)</option>
                    <option value="owner">Owner (control total — solo si confías 100%)</option>
                    <option value="support">Support (lectura tenants y soporte)</option>
                    <option value="billing">Billing (facturación)</option>
                </select>
            </div>
            <div class="md:col-span-2"><label class="admin-label">Notas internas</label><textarea name="notes" rows="3" class="admin-textarea"></textarea></div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-shield-plus"></i> Crear super admin</button>
        <a href="<?= $url('/admin/super-admins') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
