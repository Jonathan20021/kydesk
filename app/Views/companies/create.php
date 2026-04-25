<?php $slug = $tenant->slug; ?>
<a href="<?= $url('/t/' . $slug . '/companies') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500"><i class="lucide lucide-arrow-left"></i> Volver</a>
<h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Nueva empresa</h1>
<form method="POST" action="<?= $url('/t/' . $slug . '/companies') ?>" class="card card-pad space-y-4 max-w-2xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="label">Nombre *</label><input name="name" required class="input"></div>
        <div>
            <label class="label">Tier</label>
            <select name="tier" class="input"><option value="standard">Standard</option><option value="premium">Premium</option><option value="enterprise">Enterprise</option></select>
        </div>
        <div><label class="label">Industria</label><input name="industry" class="input"></div>
        <div><label class="label">Tamaño</label><input name="size" class="input" placeholder="Ej. 50-200"></div>
        <div><label class="label">Sitio web</label><input name="website" class="input" placeholder="https://"></div>
        <div><label class="label">Teléfono</label><input name="phone" class="input"></div>
        <div class="md:col-span-2"><label class="label">Dirección</label><input name="address" class="input"></div>
        <div class="md:col-span-2"><label class="label">Notas</label><textarea name="notes" rows="4" class="input"></textarea></div>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/companies') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm">Crear empresa</button>
    </div>
</form>
