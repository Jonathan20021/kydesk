<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background:linear-gradient(135deg,#f3f0ff,#fafafb)">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl border border-[#ececef] p-7">
            <h2 class="font-display font-bold text-[18px] mb-4">Nueva contraseña</h2>
            <form method="POST" action="<?= $url('/portal/' . $tenantPublic->slug . '/reset/' . $token) ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nueva contraseña</label><input name="password" type="password" required minlength="6" class="input"></div>
                <button class="btn btn-primary w-full" style="height:44px">Actualizar</button>
            </form>
        </div>
    </div>
</div>
