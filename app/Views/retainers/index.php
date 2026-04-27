<?php use App\Core\Helpers; $slug = $tenant->slug;
$statusMap = [
    'draft'     => ['Borrador',   'badge-gray',   'pencil'],
    'active'    => ['Activa',     'badge-green',  'play-circle'],
    'paused'    => ['Pausada',    'badge-amber',  'pause-circle'],
    'cancelled' => ['Cancelada',  'badge-red',    'x-circle'],
    'expired'   => ['Expirada',   'badge-red',    'clock'],
];
$cycleLabel = ['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] px-2.5 py-0.5 rounded-full" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0">
                <i class="lucide lucide-crown text-[11px]"></i> BUSINESS
            </span>
            <span class="text-[11px] text-ink-400">Función incluida en tu plan</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Igualas</h1>
        <p class="text-[13px] text-ink-400">Contratos recurrentes para soporte TI, desarrollo de software, sistemas, marketing, legal y más · todo configurable</p>
    </div>
    <div class="flex gap-2">
        <?php if ($auth->can('retainers.config')): ?>
            <a href="<?= $url('/t/' . $slug . '/retainers/settings') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-settings-2"></i> Configurar</a>
        <?php endif; ?>
        <?php if ($auth->can('retainers.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/retainers/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nueva iguala</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php foreach ([
        ['Total',            $stats['total'],   'handshake',    '#10b981', '#ecfdf5', false],
        ['Activas',          $stats['active'],  'play-circle',  '#16a34a', '#f0fdf4', false],
        ['Pausadas',         $stats['paused'],  'pause-circle', '#f59e0b', '#fffbeb', false],
        ['MRR estimado',     $stats['mrr'],     'trending-up',  '#7c5cff', '#f3f0ff', true],
    ] as [$lbl,$val,$ic,$col,$bg,$money]): ?>
        <div class="card card-pad flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $e($lbl) ?></div>
                <div class="font-display font-extrabold text-[22px] tracking-[-0.02em]">
                    <?= $money ? '$' . number_format((float)$val, 0) : number_format((int)$val) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filtros + Categorías como pills -->
<form method="GET" class="flex flex-col sm:flex-row gap-2 mb-3">
    <div class="search-pill flex-1"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar por código, nombre o cliente…"></div>
    <select name="status" class="input" style="max-width:200px">
        <option value="">Todos los estados</option>
        <?php foreach ($statusMap as $sk => [$sl,,$si]): ?>
            <option value="<?= $sk ?>" <?= $status===$sk?'selected':'' ?>><?= $sl ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="category_id" value="<?= (int)$categoryId ?>" id="categoryFilter">
    <button class="btn btn-soft btn-sm"><i class="lucide lucide-filter text-[13px]"></i> Filtrar</button>
</form>

<?php if (!empty($categories)): ?>
    <div class="flex items-center gap-1.5 flex-wrap mb-4 overflow-x-auto pb-1">
        <a href="<?= $url('/t/' . $slug . '/retainers' . ($q!==''?'?q='.urlencode($q):'')) ?>"
           class="inline-flex items-center gap-1.5 text-[12px] font-semibold px-2.5 py-1 rounded-full border <?= $categoryId===0 ? 'bg-ink-900 text-white border-ink-900' : 'bg-white border-[#ececef] text-ink-500 hover:border-ink-300' ?>">
            <i class="lucide lucide-layers-2 text-[12px]"></i> Todas
        </a>
        <?php foreach ($categories as $cat):
            $active = (int)$cat['id'] === $categoryId;
            $params = ['category_id' => $cat['id']];
            if ($q !== '') $params['q'] = $q;
            if ($status !== '') $params['status'] = $status;
        ?>
            <a href="<?= $url('/t/' . $slug . '/retainers?' . http_build_query($params)) ?>"
               class="inline-flex items-center gap-1.5 text-[12px] font-semibold px-2.5 py-1 rounded-full border transition"
               style="<?= $active ? 'background:'.$cat['color'].';border-color:'.$cat['color'].';color:white' : 'background:'.$cat['color'].'12;border-color:'.$cat['color'].'33;color:'.$cat['color'] ?>">
                <i class="lucide lucide-<?= $e($cat['icon']) ?> text-[12px]"></i> <?= $e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($retainers)): ?>
    <div class="card card-pad text-center py-20">
        <div class="w-16 h-16 rounded-2xl bg-[#ecfdf5] grid place-items-center mx-auto mb-4"><i class="lucide lucide-handshake text-[26px] text-[#10b981]"></i></div>
        <h3 class="font-display font-bold text-[18px]">Sin igualas registradas</h3>
        <p class="text-[13px] text-ink-400 mt-1 max-w-md mx-auto">Las igualas son contratos recurrentes que combinan horas, tickets, productos y entregables. Ideales para soporte mensual, desarrollo a medida, mantenimiento de sistemas o servicios profesionales.</p>
        <?php if ($auth->can('retainers.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/retainers/create') ?>" class="btn btn-primary btn-sm mt-4 inline-flex"><i class="lucide lucide-plus"></i> Crear la primera</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card overflow-hidden">
        <table class="admin-table" style="width:100%">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Iguala</th>
                    <th>Cliente</th>
                    <th>Categoría</th>
                    <th>Ciclo</th>
                    <th class="text-right">Monto</th>
                    <th>Items</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($retainers as $r):
                    [$sl,$scl,$si] = $statusMap[$r['status']] ?? ['—','badge-gray','help-circle'];
                ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/t/' . $slug . '/retainers/' . $r['id']) ?>'">
                        <td class="font-mono text-[12px] text-ink-500"><?= $e($r['code']) ?></td>
                        <td>
                            <div class="font-display font-bold text-[13.5px]"><?= $e($r['name']) ?></div>
                            <?php if (!empty($r['description'])): ?>
                                <div class="text-[11.5px] text-ink-400 mt-0.5 line-clamp-1"><?= $e($r['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['client_type'] === 'company'): ?>
                                <span class="inline-flex items-center gap-1.5 text-[12.5px]"><i class="lucide lucide-building-2 text-[12px] text-ink-400"></i> <?= $e($r['company_name'] ?? '—') ?></span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 text-[12.5px]"><i class="lucide lucide-user text-[12px] text-ink-400"></i> <?= $e($r['client_name'] ?? '—') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['category_name']): ?>
                                <span class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold px-2 py-0.5 rounded-full" style="background:<?= $e($r['category_color']) ?>15;color:<?= $e($r['category_color']) ?>;border:1px solid <?= $e($r['category_color']) ?>33">
                                    <i class="lucide lucide-<?= $e($r['category_icon']) ?> text-[10px]"></i> <?= $e($r['category_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-ink-400 text-[12px]">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-gray"><?= $cycleLabel[$r['billing_cycle']] ?? $r['billing_cycle'] ?></span></td>
                        <td class="text-right font-mono text-[12.5px]"><?= $e($r['currency']) ?> <?= number_format((float)$r['amount'], 2) ?></td>
                        <td class="text-[12px] text-ink-500"><?= (int)$r['items_count'] ?></td>
                        <td><span class="badge <?= $scl ?>"><i class="lucide lucide-<?= $si ?> text-[10px]"></i> <?= $sl ?></span></td>
                        <td class="text-right">
                            <a href="<?= $url('/t/' . $slug . '/retainers/' . $r['id']) ?>" class="btn btn-soft btn-xs" onclick="event.stopPropagation()"><i class="lucide lucide-arrow-right text-[12px]"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
