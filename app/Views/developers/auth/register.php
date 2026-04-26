<?php $selPlan = $_GET['plan'] ?? 'dev_free'; ?>
<div class="text-center mb-6">
    <h1 class="font-display font-bold text-white text-[22px] mb-1">Crear cuenta de developer</h1>
    <p class="text-[13px] text-slate-400">Construye apps con la API de Kydesk</p>
</div>

<form method="POST" action="<?= $url('/developers/register') ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div>
        <label class="dev-label">Nombre completo</label>
        <input type="text" name="name" required autofocus class="dev-input" placeholder="Jane Developer">
    </div>

    <div>
        <label class="dev-label">Email</label>
        <input type="email" name="email" required class="dev-input" placeholder="jane@example.com">
    </div>

    <div>
        <label class="dev-label">Empresa <span class="text-slate-500 normal-case">(opcional)</span></label>
        <input type="text" name="company" class="dev-input" placeholder="Mi Studio">
    </div>

    <div>
        <label class="dev-label">Contraseña</label>
        <input type="password" name="password" required minlength="6" class="dev-input" placeholder="Mínimo 6 caracteres">
    </div>

    <?php if (!empty($plans)): ?>
    <div>
        <label class="dev-label">Plan inicial</label>
        <select name="plan" class="dev-input" style="height:46px">
            <?php foreach ($plans as $p): ?>
                <option value="<?= $e($p['slug']) ?>" <?= $p['slug'] === $selPlan ? 'selected' : '' ?>>
                    <?= $e($p['name']) ?> · $<?= number_format((float)$p['price_monthly'], 0) ?>/mes
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <button type="submit" class="dev-btn-primary mt-6">
        <i class="lucide lucide-rocket text-[15px]"></i> Crear cuenta
    </button>
</form>

<div class="text-center mt-5 text-[12.5px] text-slate-400">
    ¿Ya tienes cuenta? <a href="<?= $url('/developers/login') ?>" class="text-sky-300 hover:text-sky-200">Iniciar sesión</a>
</div>
