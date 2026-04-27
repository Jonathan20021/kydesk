<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background:linear-gradient(135deg,#f3f0ff,#fafafb)">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="font-display font-extrabold text-[22px]"><?= $e($tenantPublic->name) ?></h1>
        </div>
        <div class="bg-white rounded-3xl shadow-xl border border-[#ececef] p-7">
            <h2 class="font-display font-bold text-[18px] mb-1">Recuperar contraseña</h2>
            <p class="text-[12.5px] text-ink-500 mb-4">Te enviamos un email con un link para restablecer.</p>
            <form method="POST" action="<?= $url('/portal/' . $tenantPublic->slug . '/forgot') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Email</label><input name="email" type="email" required class="input"></div>
                <button class="btn btn-primary w-full" style="height:44px"><i class="lucide lucide-send"></i> Enviar link</button>
            </form>
            <p class="text-[12.5px] text-ink-500 mt-5 text-center"><a href="<?= $url('/portal/' . $tenantPublic->slug . '/login') ?>" class="font-semibold text-brand-700">← Volver al login</a></p>
        </div>
    </div>
</div>
