<?php $isNew = empty($d); ?>
<form method="POST" action="<?= $isNew ? $url('/admin/developers') : $url('/admin/developers/' . $d['id']) ?>" class="admin-card admin-card-pad max-w-[800px] space-y-5">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div>
        <h2 class="admin-h2"><?= $isNew ? 'Crear developer' : 'Editar developer' ?></h2>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label">Nombre</label>
            <input type="text" name="name" required class="admin-input" value="<?= $e($d['name'] ?? '') ?>">
        </div>
        <div>
            <label class="admin-label">Email</label>
            <input type="email" name="email" required class="admin-input" value="<?= $e($d['email'] ?? '') ?>" <?= $isNew ? '' : 'disabled' ?>>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label">Empresa</label>
            <input type="text" name="company" class="admin-input" value="<?= $e($d['company'] ?? '') ?>">
        </div>
        <div>
            <label class="admin-label">Website</label>
            <input type="url" name="website" class="admin-input" value="<?= $e($d['website'] ?? '') ?>">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label">Teléfono</label>
            <input type="text" name="phone" class="admin-input" value="<?= $e($d['phone'] ?? '') ?>">
        </div>
        <div>
            <label class="admin-label">País</label>
            <input type="text" name="country" class="admin-input" value="<?= $e($d['country'] ?? '') ?>">
        </div>
    </div>

    <div>
        <label class="admin-label"><?= $isNew ? 'Contraseña' : 'Cambiar contraseña (opcional)' ?></label>
        <input type="password" name="password" class="admin-input" <?= $isNew ? 'required minlength="6"' : '' ?> placeholder="<?= $isNew ? 'Mínimo 6 caracteres' : 'Dejar vacío para conservar' ?>">
    </div>

    <?php if ($isNew && !empty($plans)): ?>
    <div>
        <label class="admin-label">Plan inicial</label>
        <select name="plan_id" class="admin-select">
            <option value="">— Sin plan —</option>
            <?php foreach ($plans as $p): ?>
                <option value="<?= $p['id'] ?>"><?= $e($p['name']) ?> · $<?= number_format((float)$p['price_monthly'], 0) ?>/mes</option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div>
        <label class="admin-label">Notas internas</label>
        <textarea name="notes" class="admin-textarea" rows="3"><?= $e($d['notes'] ?? '') ?></textarea>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
        <label class="flex items-center gap-2 admin-card-pad" style="padding:12px 14px">
            <input type="checkbox" name="is_active" value="1" <?= ($isNew || (int)($d['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
            <span class="text-[13px]">Cuenta activa</span>
        </label>
        <label class="flex items-center gap-2 admin-card-pad" style="padding:12px 14px">
            <input type="checkbox" name="is_verified" value="1" <?= !empty($d['is_verified']) ? 'checked' : '' ?>>
            <span class="text-[13px]">Verificado</span>
        </label>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> <?= $isNew ? 'Crear developer' : 'Guardar cambios' ?></button>
        <a href="<?= $url('/admin/developers') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
