<?php
$isNew = empty($p);
$action = $isNew ? '/admin/plans' : '/admin/plans/' . $p['id'];
$features = $isNew ? [] : (json_decode($p['features'] ?? '[]', true) ?: []);
$availableFeatures = [
    'tickets' => 'Tickets',
    'kb' => 'Base de conocimiento',
    'notes' => 'Notas',
    'todos' => 'Tareas',
    'companies' => 'Empresas',
    'assets' => 'Activos',
    'reports' => 'Reportes',
    'users' => 'Usuarios',
    'roles' => 'Roles',
    'settings' => 'Ajustes',
    'automations' => 'Automatizaciones',
    'sla' => 'SLA',
    'audit' => 'Auditoría',
    'departments' => 'Departamentos',
    'integrations' => 'Integraciones (Slack, Discord, etc.)',
    'sso' => 'SSO + SAML',
    'custom_branding' => 'Marca personalizada',
];
?>
<form method="POST" action="<?= $url($action) ?>" class="max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="admin-label">Nombre *</label><input name="name" required value="<?= $e($p['name'] ?? '') ?>" class="admin-input"></div>
            <div><label class="admin-label">Slug *</label><input name="slug" required value="<?= $e($p['slug'] ?? '') ?>" class="admin-input" <?= $isNew ? '' : 'readonly' ?>></div>
            <div class="md:col-span-2"><label class="admin-label">Descripción</label><textarea name="description" rows="2" class="admin-textarea"><?= $e($p['description'] ?? '') ?></textarea></div>
            <div><label class="admin-label">Precio mensual ($)</label><input name="price_monthly" type="number" step="0.01" value="<?= $e($p['price_monthly'] ?? 0) ?>" class="admin-input"></div>
            <div><label class="admin-label">Precio anual ($)</label><input name="price_yearly" type="number" step="0.01" value="<?= $e($p['price_yearly'] ?? 0) ?>" class="admin-input"></div>
            <div><label class="admin-label">Moneda</label><input name="currency" value="<?= $e($p['currency'] ?? 'USD') ?>" class="admin-input"></div>
            <div><label class="admin-label">Días trial</label><input name="trial_days" type="number" value="<?= $e($p['trial_days'] ?? 14) ?>" class="admin-input"></div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-3">Límites</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><label class="admin-label">Máx. usuarios</label><input name="max_users" type="number" value="<?= $e($p['max_users'] ?? 999) ?>" class="admin-input"></div>
            <div><label class="admin-label">Tickets/mes</label><input name="max_tickets_month" type="number" value="<?= $e($p['max_tickets_month'] ?? 99999) ?>" class="admin-input"></div>
            <div><label class="admin-label">Artículos KB</label><input name="max_kb_articles" type="number" value="<?= $e($p['max_kb_articles'] ?? 999) ?>" class="admin-input"></div>
            <div><label class="admin-label">Storage (MB)</label><input name="max_storage_mb" type="number" value="<?= $e($p['max_storage_mb'] ?? 5120) ?>" class="admin-input"></div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-3">Features</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <?php foreach ($availableFeatures as $key => $label): ?>
                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border" style="border-color:#ececef; cursor:pointer">
                    <input type="checkbox" name="features[]" value="<?= $e($key) ?>" <?= in_array($key, $features) ? 'checked' : '' ?>>
                    <span class="text-[12.5px]"><?= $e($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-3">Apariencia y visibilidad</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="admin-label">Color</label><input name="color" type="color" value="<?= $e($p['color'] ?? '#7c5cff') ?>" class="admin-input" style="height:42px"></div>
            <div><label class="admin-label">Ícono (lucide)</label><input name="icon" value="<?= $e($p['icon'] ?? 'rocket') ?>" class="admin-input" placeholder="rocket"></div>
            <div><label class="admin-label">Orden</label><input name="sort_order" type="number" value="<?= $e($p['sort_order'] ?? 0) ?>" class="admin-input"></div>
            <div class="flex flex-col gap-2 justify-end">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= !empty($p['is_active']) || $isNew ? 'checked' : '' ?>> Plan activo</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_public" value="1" <?= !empty($p['is_public']) || $isNew ? 'checked' : '' ?>> Visible en pricing público</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Destacado</label>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> <?= $isNew ? 'Crear plan' : 'Guardar cambios' ?></button>
        <a href="<?= $url('/admin/plans') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
