<?php
use App\Core\Plan;
$slug = $tenant->slug;
$preselectedCompany = (int)($_GET['company_id'] ?? 0);
$preselectedAsset = (int)($_GET['asset_id'] ?? 0);
$allowedChannels = Plan::LIMITS[Plan::tenantPlan($tenant)]['channels'] ?? ['portal','email'];
?>

<a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a tickets</a>

<div>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Nuevo ticket</h1>
    <p class="text-[13px] text-ink-400">Los campos con * son obligatorios</p>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/tickets') ?>" class="card card-pad space-y-5 max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div>
        <label class="label">Asunto *</label>
        <input name="subject" required class="input" placeholder="Breve descripción del problema">
    </div>
    <div>
        <label class="label">Descripción</label>
        <textarea name="description" rows="6" class="input" placeholder="Explica con detalle…"></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="label">Categoría</label>
            <select name="category_id" class="input">
                <option value="0">Sin categoría</option>
                <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label">Prioridad</label>
            <select name="priority" class="input">
                <option value="low">Baja</option><option value="medium" selected>Media</option><option value="high">Alta</option><option value="urgent">Urgente</option>
            </select>
        </div>
        <div>
            <label class="label">Canal</label>
            <select name="channel" class="input">
                <?php foreach ([['internal','Interno'],['phone','Teléfono'],['email','Email'],['chat','Chat'],['portal','Portal']] as [$v,$l]):
                    $blocked = !in_array($v, $allowedChannels, true);
                ?><option value="<?= $v ?>" <?= $blocked?'disabled':'' ?>><?= $l ?><?= $blocked?' · plan superior':'' ?></option><?php endforeach; ?>
            </select>
            <?php if (count($allowedChannels) < 5): ?>
                <p class="text-[11px] text-ink-400 mt-1.5"><i class="lucide lucide-lock text-[10px]"></i> Tu plan permite: <?= implode(', ', $allowedChannels) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="pt-4 border-t border-[#ececef]">
        <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-3 text-ink-400">Solicitante</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">Empresa</label>
                <select name="company_id" class="input">
                    <option value="0">Selecciona…</option>
                    <?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $preselectedCompany === (int)$c['id'] ? 'selected' : '' ?>><?= $e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="label">Nombre</label><input name="requester_name" class="input"></div>
            <div><label class="label">Email</label><input name="requester_email" type="email" class="input"></div>
            <div><label class="label">Teléfono</label><input name="requester_phone" class="input"></div>
        </div>
    </div>
    <?php if ($auth->can('tickets.assign')): ?>
        <div>
            <label class="label">Asignar a</label>
            <select name="assigned_to" class="input">
                <option value="0">Sin asignar</option>
                <?php foreach ($technicians as $t): ?><option value="<?= (int)$t['id'] ?>"><?= $e($t['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Crear ticket</button>
    </div>
</form>
