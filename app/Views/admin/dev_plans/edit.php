<?php $isNew = empty($p); ?>
<form method="POST" action="<?= $isNew ? $url('/admin/dev-plans') : $url('/admin/dev-plans/' . $p['id']) ?>" class="admin-card admin-card-pad max-w-[900px] space-y-5">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label">Nombre</label>
            <input type="text" name="name" required class="admin-input" value="<?= $e($p['name'] ?? '') ?>">
        </div>
        <div>
            <label class="admin-label">Slug</label>
            <input type="text" name="slug" <?= $isNew ? 'required' : 'disabled' ?> class="admin-input" value="<?= $e($p['slug'] ?? '') ?>">
        </div>
    </div>

    <div>
        <label class="admin-label">Descripción</label>
        <textarea name="description" class="admin-textarea" rows="2"><?= $e($p['description'] ?? '') ?></textarea>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="admin-label">Precio mensual ($)</label>
            <input type="number" step="0.01" name="price_monthly" class="admin-input" value="<?= $e($p['price_monthly'] ?? '0') ?>">
        </div>
        <div>
            <label class="admin-label">Precio anual ($)</label>
            <input type="number" step="0.01" name="price_yearly" class="admin-input" value="<?= $e($p['price_yearly'] ?? '0') ?>">
        </div>
        <div>
            <label class="admin-label">Moneda</label>
            <input type="text" name="currency" class="admin-input" value="<?= $e($p['currency'] ?? 'USD') ?>">
        </div>
    </div>

    <div class="grid sm:grid-cols-4 gap-4">
        <div>
            <label class="admin-label">Max apps</label>
            <input type="number" name="max_apps" class="admin-input" value="<?= $e($p['max_apps'] ?? '1') ?>">
        </div>
        <div>
            <label class="admin-label">Max requests/mes</label>
            <input type="number" name="max_requests_month" class="admin-input" value="<?= $e($p['max_requests_month'] ?? '10000') ?>">
        </div>
        <div>
            <label class="admin-label">Tokens/app</label>
            <input type="number" name="max_tokens_per_app" class="admin-input" value="<?= $e($p['max_tokens_per_app'] ?? '5') ?>">
        </div>
        <div>
            <label class="admin-label">Rate limit/min</label>
            <input type="number" name="rate_limit_per_min" class="admin-input" value="<?= $e($p['rate_limit_per_min'] ?? '60') ?>">
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="admin-label">Overage ($ / 1k req)</label>
            <input type="number" step="0.0001" name="overage_price_per_1k" class="admin-input" value="<?= $e($p['overage_price_per_1k'] ?? '0') ?>">
        </div>
        <div>
            <label class="admin-label">Días de trial</label>
            <input type="number" name="trial_days" class="admin-input" value="<?= $e($p['trial_days'] ?? '0') ?>">
        </div>
        <div>
            <label class="admin-label">Sort order</label>
            <input type="number" name="sort_order" class="admin-input" value="<?= $e($p['sort_order'] ?? '0') ?>">
        </div>
    </div>

    <div>
        <label class="admin-label">Características (una por línea)</label>
        <textarea name="features_text" class="admin-textarea" rows="4" placeholder="api_access&#10;webhooks&#10;priority_support" oninput="syncFeatures(this)"><?= $e(implode("\n", json_decode((string)($p['features'] ?? '[]'), true) ?: [])) ?></textarea>
        <div id="featuresHidden"></div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label">Color</label>
            <input type="color" name="color" class="admin-input" style="height:42px" value="<?= $e($p['color'] ?? '#0ea5e9') ?>">
        </div>
        <div>
            <label class="admin-label">Icono (lucide)</label>
            <input type="text" name="icon" class="admin-input" value="<?= $e($p['icon'] ?? 'code') ?>" placeholder="rocket">
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-3">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= ($isNew || (int)($p['is_active'] ?? 1) === 1) ? 'checked' : '' ?>> <span class="text-[13px]">Activo</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_public" value="1" <?= ($isNew || (int)($p['is_public'] ?? 1) === 1) ? 'checked' : '' ?>> <span class="text-[13px]">Público</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> <span class="text-[13px]">Destacado</span></label>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> <?= $isNew ? 'Crear plan' : 'Guardar cambios' ?></button>
        <a href="<?= $url('/admin/dev-plans') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>

<script>
function syncFeatures(el){
    const lines = el.value.split('\n').map(s => s.trim()).filter(Boolean);
    const hidden = document.getElementById('featuresHidden');
    hidden.innerHTML = '';
    lines.forEach(l => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'features[]'; i.value = l;
        hidden.appendChild(i);
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const ta = document.querySelector('textarea[name="features_text"]');
    if (ta) syncFeatures(ta);
});
</script>
