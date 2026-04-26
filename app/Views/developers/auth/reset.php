<div class="text-center mb-6">
    <h1 class="font-display font-bold text-white text-[22px] mb-1">Nueva contraseña</h1>
    <p class="text-[13px] text-slate-400">Elige una contraseña fuerte</p>
</div>

<form method="POST" action="<?= $url('/developers/reset/' . $token) ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div>
        <label class="dev-label">Nueva contraseña</label>
        <input type="password" name="password" required minlength="6" autofocus class="dev-input" placeholder="Mínimo 6 caracteres">
    </div>
    <button type="submit" class="dev-btn-primary mt-6"><i class="lucide lucide-key text-[15px]"></i> Cambiar contraseña</button>
</form>
