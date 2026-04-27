<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background:linear-gradient(135deg,#f3f0ff,#fafafb)">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[18px]"><?= $e(strtoupper(substr($tenantPublic->name, 0, 1))) ?></div>
            <h1 class="font-display font-extrabold text-[22px] mt-3"><?= $e($tenantPublic->name) ?></h1>
            <p class="text-[13px] text-ink-500">Portal de soporte</p>
        </div>
        <div class="bg-white rounded-3xl shadow-xl border border-[#ececef] p-7">
            <h2 class="font-display font-bold text-[18px] mb-4">Iniciar sesión</h2>
            <form method="POST" action="<?= $url('/portal/' . $tenantPublic->slug . '/login') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="label">Email</label>
                    <input name="email" type="email" required class="input" autofocus>
                </div>
                <div>
                    <label class="label flex items-center justify-between">
                        <span>Contraseña</span>
                        <a href="<?= $url('/portal/' . $tenantPublic->slug . '/forgot') ?>" class="text-[11.5px] text-brand-700 font-semibold">Olvidé mi contraseña</a>
                    </label>
                    <input name="password" type="password" required class="input">
                </div>
                <button class="btn btn-primary w-full" style="height:44px"><i class="lucide lucide-log-in"></i> Entrar</button>
            </form>
            <p class="text-[12.5px] text-ink-500 mt-5 text-center">¿No tenés cuenta? <a href="<?= $url('/portal/' . $tenantPublic->slug . '/register') ?>" class="font-semibold text-brand-700">Crear cuenta</a></p>
            <p class="text-[12px] text-ink-400 mt-3 text-center">o <a href="<?= $url('/portal/' . $tenantPublic->slug) ?>" class="hover:text-ink-700">crear ticket sin cuenta</a></p>
        </div>
    </div>
</div>
