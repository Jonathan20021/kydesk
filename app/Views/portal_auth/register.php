<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background:linear-gradient(135deg,#f3f0ff,#fafafb)">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[18px]"><?= $e(strtoupper(substr($tenantPublic->name, 0, 1))) ?></div>
            <h1 class="font-display font-extrabold text-[22px] mt-3"><?= $e($tenantPublic->name) ?></h1>
        </div>
        <div class="bg-white rounded-3xl shadow-xl border border-[#ececef] p-7">
            <h2 class="font-display font-bold text-[18px] mb-4">Crear cuenta</h2>
            <form method="POST" action="<?= $url('/portal/' . $tenantPublic->slug . '/register') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre completo</label><input name="name" required class="input"></div>
                <div><label class="label">Email</label><input name="email" type="email" required class="input"></div>
                <div><label class="label">Teléfono</label><input name="phone" class="input"></div>
                <?php if (!empty($companies)): ?>
                <div>
                    <label class="label">Empresa (opcional)</label>
                    <select name="company_id" class="input">
                        <option value="">— No aplica —</option>
                        <?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div><label class="label">Contraseña (mín 6)</label><input name="password" type="password" required minlength="6" class="input"></div>
                <button class="btn btn-primary w-full" style="height:44px"><i class="lucide lucide-user-plus"></i> Crear cuenta</button>
            </form>
            <p class="text-[12.5px] text-ink-500 mt-5 text-center">¿Ya tenés cuenta? <a href="<?= $url('/portal/' . $tenantPublic->slug . '/login') ?>" class="font-semibold text-brand-700">Iniciar sesión</a></p>
        </div>
    </div>
</div>
