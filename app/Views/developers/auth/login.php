<div class="text-center mb-6">
    <h1 class="font-display font-bold text-white text-[22px] mb-1">Iniciar sesión</h1>
    <p class="text-[13px] text-slate-400">Accede a tu panel de developer</p>
</div>

<form method="POST" action="<?= $url('/developers/login') ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div>
        <label class="dev-label">Email</label>
        <input type="email" name="email" required autofocus class="dev-input" placeholder="dev@example.com">
    </div>

    <div>
        <label class="dev-label">Contraseña</label>
        <input type="password" name="password" required class="dev-input" placeholder="••••••••">
    </div>

    <button type="submit" class="dev-btn-primary mt-6">
        <i class="lucide lucide-log-in text-[15px]"></i> Entrar
    </button>
</form>

<div class="text-center mt-5 text-[12.5px] text-slate-400">
    ¿No tienes cuenta? <a href="<?= $url('/developers/register') ?>" class="text-sky-300 hover:text-sky-200">Regístrate</a>
</div>
