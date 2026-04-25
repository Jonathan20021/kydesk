<form method="POST" action="<?= $url('/admin/users') ?>" class="max-w-2xl" x-data="{tenantId: <?= (int)$tenantId ?>}">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="admin-label">Empresa *</label>
                <select name="tenant_id" required class="admin-select" x-model="tenantId" @change="window.location='?tenant_id='+tenantId">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $tenantId==$t['id']?'selected':'' ?>><?= $e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="admin-label">Nombre *</label><input name="name" required class="admin-input"></div>
            <div><label class="admin-label">Email *</label><input name="email" type="email" required class="admin-input"></div>
            <div><label class="admin-label">Contraseña *</label><input name="password" type="text" required class="admin-input" placeholder="Mín. 6 caracteres"></div>
            <div><label class="admin-label">Cargo</label><input name="title" class="admin-input"></div>
            <div><label class="admin-label">Teléfono</label><input name="phone" class="admin-input"></div>
            <div>
                <label class="admin-label">Rol</label>
                <?php if (!empty($roles)): ?>
                    <select name="role_id" class="admin-select">
                        <option value="">— Sin rol —</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= $e($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="text-[12px] text-ink-400 p-2">Selecciona una empresa primero.</div>
                <?php endif; ?>
            </div>
            <div class="flex items-end"><label class="flex items-center gap-2"><input type="checkbox" name="is_technician" value="1"> Es técnico</label></div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-check"></i> Crear usuario</button>
        <a href="<?= $url('/admin/users') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
