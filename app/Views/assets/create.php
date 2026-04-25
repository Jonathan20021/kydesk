<?php $slug = $tenant->slug; ?>
<a href="<?= $url('/t/' . $slug . '/assets') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500"><i class="lucide lucide-arrow-left"></i> Volver</a>
<h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Nuevo activo</h1>
<form method="POST" action="<?= $url('/t/' . $slug . '/assets') ?>" class="card card-pad space-y-4 max-w-2xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="label">Nombre *</label><input name="name" required class="input"></div>
        <div>
            <label class="label">Tipo</label>
            <select name="type" class="input">
                <?php foreach (['laptop'=>'Laptop','phone'=>'Teléfono','monitor'=>'Monitor','printer'=>'Impresora','network'=>'Red','server'=>'Servidor','other'=>'Otro'] as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="label">Serial</label><input name="serial" class="input"></div>
        <div><label class="label">Modelo</label><input name="model" class="input"></div>
        <div>
            <label class="label">Estado</label>
            <select name="status" class="input"><option value="active">Activo</option><option value="maintenance">Mantenimiento</option><option value="retired">Retirado</option><option value="lost">Perdido</option></select>
        </div>
        <div>
            <label class="label">Empresa</label>
            <select name="company_id" class="input"><option value="0">—</option><?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div>
            <label class="label">Asignar a</label>
            <select name="assigned_to" class="input"><option value="0">—</option><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= $e($u['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div><label class="label">Ubicación</label><input name="location" class="input"></div>
        <div><label class="label">Fecha compra</label><input name="purchase_date" type="date" class="input"></div>
        <div><label class="label">Garantía hasta</label><input name="warranty_until" type="date" class="input"></div>
        <div class="md:col-span-2"><label class="label">Notas</label><textarea name="notes" rows="3" class="input"></textarea></div>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/assets') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm">Registrar</button>
    </div>
</form>
