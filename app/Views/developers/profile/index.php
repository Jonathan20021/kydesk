<form method="POST" action="<?= $url('/developers/profile') ?>" class="dev-card max-w-[760px] p-7 space-y-5">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div>
        <h2 class="font-display font-bold text-white text-[18px]">Perfil de developer</h2>
        <p class="text-[12.5px] text-slate-400 mt-1">Tus datos públicos y credenciales.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="dev-label">Nombre</label>
            <input type="text" name="name" class="dev-input" value="<?= $e($developer['name']) ?>" required>
        </div>
        <div>
            <label class="dev-label">Email</label>
            <input type="email" class="dev-input" value="<?= $e($developer['email']) ?>" disabled>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="dev-label">Empresa</label>
            <input type="text" name="company" class="dev-input" value="<?= $e($developer['company'] ?? '') ?>">
        </div>
        <div>
            <label class="dev-label">Website</label>
            <input type="url" name="website" class="dev-input" value="<?= $e($developer['website'] ?? '') ?>">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="dev-label">País</label>
            <input type="text" name="country" class="dev-input" value="<?= $e($developer['country'] ?? '') ?>">
        </div>
        <div>
            <label class="dev-label">Teléfono</label>
            <input type="text" name="phone" class="dev-input" value="<?= $e($developer['phone'] ?? '') ?>">
        </div>
    </div>

    <div>
        <label class="dev-label">Bio</label>
        <textarea name="bio" class="dev-textarea" rows="3"><?= $e($developer['bio'] ?? '') ?></textarea>
    </div>

    <div class="pt-4 border-t" style="border-color:rgba(56,189,248,.10)">
        <label class="dev-label">Cambiar contraseña <span class="text-slate-500 normal-case font-normal">(opcional)</span></label>
        <input type="password" name="password" class="dev-input" placeholder="Dejar vacío para conservar">
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="dev-btn dev-btn-primary"><i class="lucide lucide-save text-[14px]"></i> Guardar cambios</button>
    </div>
</form>
