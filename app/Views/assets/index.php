<?php use App\Core\Helpers; $slug = $tenant->slug;
$typeIcons = ['laptop'=>'laptop','phone'=>'smartphone','monitor'=>'monitor','printer'=>'printer','network'=>'wifi','server'=>'server'];
$statusInfo = ['active'=>['Activo','badge-emerald'],'maintenance'=>['Mantenimiento','badge-amber'],'retired'=>['Retirado','badge-gray'],'lost'=>['Perdido','badge-rose']]; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Activos</h1>
        <p class="text-[13px] text-ink-400">Inventario técnico de la organización</p>
    </div>
    <?php if ($auth->can('assets.create')): ?>
        <a href="<?= $url('/t/' . $slug . '/assets/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo activo</a>
    <?php endif; ?>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <?php foreach ([['Total',$stats['total'],'server','#f3e8ff','#7e22ce'],['Activos',$stats['active'],'circle-check','#d1fae5','#047857'],['Mantenimiento',$stats['maintenance'],'wrench','#fef3c7','#b45309'],['Retirados',$stats['retired'],'archive','#f3f4f6','#6b6b78']] as [$l,$v,$ic,$bg,$col]): ?>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
            <div class="min-w-0 flex-1">
                <div class="stat-mini-meta"><?= $l ?></div>
                <div class="stat-mini-title"><?= $v ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form method="GET" class="flex gap-3">
    <div class="search-pill flex-1" style="max-width:none"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar por nombre, serial, modelo…"></div>
    <select name="status" class="input" style="max-width:200px;height:44px;border-radius:999px;">
        <option value="">Cualquier estado</option>
        <?php foreach (['active'=>'Activo','maintenance'=>'Mantenimiento','retired'=>'Retirado','lost'=>'Perdido'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card overflow-hidden">
    <table class="table">
        <thead><tr><th>Activo</th><th>Tipo</th><th>Estado</th><th>Asignado</th><th>Empresa</th><th>Compra</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($assets as $a): [$stLbl,$stCls] = $statusInfo[$a['status']] ?? ['—','badge-gray']; ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-[#f3f4f6] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $typeIcons[$a['type']] ?? 'server' ?> text-base"></i></div>
                            <div>
                                <div class="font-display font-bold text-[13px]"><?= $e($a['name']) ?></div>
                                <div class="text-[11px] text-ink-400"><?= $e($a['serial'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-[12.5px] capitalize"><?= $e($a['type']) ?></td>
                    <td><span class="badge <?= $stCls ?>"><?= $stLbl ?></span></td>
                    <td>
                        <?php if ($a['user_name']): ?>
                            <div class="flex items-center gap-2">
                                <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($a['user_email'] ?? '') ?>;color:white"><?= Helpers::initials($a['user_name']) ?></div>
                                <span class="text-[12.5px]"><?= $e($a['user_name']) ?></span>
                            </div>
                        <?php else: ?><span class="text-[12px] text-ink-400">—</span><?php endif; ?>
                    </td>
                    <td class="text-[12.5px] text-ink-500"><?= $e($a['company_name'] ?? '—') ?></td>
                    <td class="text-[11.5px] font-mono text-ink-400"><?= $a['purchase_date'] ? date('d/m/Y', strtotime($a['purchase_date'])) : '—' ?></td>
                    <td>
                        <?php if ($auth->can('assets.delete')): ?>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/assets/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="table-action" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assets)): ?>
                <tr><td colspan="7" class="text-center py-16">
                    <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-server text-[22px] text-ink-400"></i></div>
                    <div class="font-display font-bold">Sin activos</div>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
