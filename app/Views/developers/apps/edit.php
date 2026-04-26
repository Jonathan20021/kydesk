<?php $isNew = empty($devApp); $action = $isNew ? $url('/developers/apps') : $url('/developers/apps/' . $devApp['id'] . '/update'); ?>
<form method="POST" action="<?= $action ?>" class="dev-card dev-card-pad space-y-5 max-w-[760px]">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div>
        <h2 class="font-display font-bold text-white text-[18px]"><?= $isNew ? 'Crear app' : 'Editar app' ?></h2>
        <p class="text-[12.5px] text-slate-400 mt-1">Cada app tiene su propio workspace de helpdesk aislado y sus tokens API.</p>
    </div>

    <div>
        <label class="dev-label">Nombre</label>
        <input type="text" name="name" required class="dev-input" value="<?= $e($devApp['name'] ?? '') ?>" placeholder="Mi App de Soporte">
    </div>

    <div>
        <label class="dev-label">Descripción</label>
        <textarea name="description" class="dev-textarea" rows="2" placeholder="¿Qué hace esta app?"><?= $e($devApp['description'] ?? '') ?></textarea>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="dev-label">Homepage URL</label>
            <input type="url" name="homepage_url" class="dev-input" value="<?= $e($devApp['homepage_url'] ?? '') ?>" placeholder="https://miapp.com">
        </div>
        <div>
            <label class="dev-label">Callback URL</label>
            <input type="url" name="callback_url" class="dev-input" value="<?= $e($devApp['callback_url'] ?? '') ?>" placeholder="https://miapp.com/oauth/callback">
        </div>
    </div>

    <div>
        <label class="dev-label">Entorno</label>
        <select name="environment" class="dev-input">
            <?php foreach (['development','staging','production'] as $env): ?>
                <option value="<?= $env ?>" <?= ($devApp['environment'] ?? 'development') === $env ? 'selected' : '' ?>><?= ucfirst($env) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="dev-btn dev-btn-primary"><i class="lucide lucide-save text-[14px]"></i> <?= $isNew ? 'Crear app' : 'Guardar cambios' ?></button>
        <a href="<?= $url('/developers/apps') ?>" class="dev-btn dev-btn-soft">Cancelar</a>
    </div>
</form>
