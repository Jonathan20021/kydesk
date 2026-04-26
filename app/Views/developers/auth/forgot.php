<div class="text-center mb-6">
    <h1 class="font-display font-bold text-white text-[22px] mb-1">Recuperar contraseña</h1>
    <p class="text-[13px] text-slate-400">Te enviaremos un enlace para crear una nueva</p>
</div>

<form method="POST" action="<?= $url('/developers/forgot') ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div>
        <label class="dev-label">Email</label>
        <input type="email" name="email" required autofocus class="dev-input" placeholder="dev@example.com">
    </div>
    <button type="submit" class="dev-btn-primary mt-6"><i class="lucide lucide-mail text-[15px]"></i> Enviar enlace</button>
</form>

<div class="text-center mt-5 text-[12.5px] text-slate-400">
    <a href="<?= $url('/developers/login') ?>" class="text-sky-300 hover:text-sky-200">← Volver a login</a>
</div>
