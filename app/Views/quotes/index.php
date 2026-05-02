<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$statusMap = [
    'draft'     => ['Borrador',   '#94a3b8', 'pencil'],
    'sent'      => ['Enviada',    '#3b82f6', 'send'],
    'viewed'    => ['Vista',      '#0ea5e9', 'eye'],
    'accepted'  => ['Aceptada',   '#16a34a', 'check-circle-2'],
    'rejected'  => ['Rechazada',  '#dc2626', 'x-circle'],
    'expired'   => ['Expirada',   '#f59e0b', 'clock'],
    'revised'   => ['Revisada',   '#7c5cff', 'refresh-cw'],
    'converted' => ['Convertida', '#16a34a', 'shopping-bag'],
];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] px-2.5 py-0.5 rounded-full" style="background:#f3f0ff;color:#5a3aff;border:1px solid #cdbfff">
                <i class="lucide lucide-crown text-[11px]"></i> BUSINESS
            </span>
            <span class="text-[11px] text-ink-400">Función incluida en tu plan</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Cotizaciones</h1>
        <p class="text-[13px] text-ink-400">Generador profesional con plantillas, ITBIS configurable, descuentos y exportación PDF con tu branding.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php if ($auth->can('quotes.config')): ?>
            <a href="<?= $url('/t/' . $slug . '/quotes/settings') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-settings-2"></i> Configurar</a>
        <?php endif; ?>
        <?php if ($auth->can('quotes.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/quotes/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nueva cotización</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php foreach ([
        ['Total',        $stats['total'],          'file-text',      '#7c5cff', '#f3f0ff', false],
        ['Borradores',   $stats['draft'],          'pencil',         '#94a3b8', '#f3f4f6', false],
        ['En cliente',   $stats['sent'],           'send',           '#3b82f6', '#e0f2fe', false],
        ['Aceptadas',    $stats['accepted'],       'check-circle-2', '#16a34a', '#ecfdf5', false],
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-5">
    <div class="card card-pad" style="background:linear-gradient(135deg,#fefce8,#fef9c3);border-color:#fde047">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl grid place-items-center" style="background:#facc15;color:white"><i class="lucide lucide-trending-up text-[20px]"></i></div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800">PIPELINE EN CLIENTES</div>
                <div class="font-display font-extrabold text-[24px] tracking-[-0.02em]">$<?= number_format((float)$stats['pipeline_value'], 2) ?></div>
                <div class="text-[11px] text-amber-700">Cotizaciones enviadas/vistas pendientes de respuesta</div>
            </div>
        </div>
    </div>
    <div class="card card-pad" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border-color:#a7f3d0">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl grid place-items-center" style="background:#16a34a;color:white"><i class="lucide lucide-check-circle-2 text-[20px]"></i></div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-800">VENDIDO (ACEPTADAS)</div>
                <div class="font-display font-extrabold text-[24px] tracking-[-0.02em]">$<?= number_format((float)$stats['won_value'], 2) ?></div>
                <div class="text-[11px] text-emerald-700">Total acumulado de cotizaciones aceptadas</div>
            </div>
        </div>
    </div>
</div>

<form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-3">
    <div class="search-pill md:col-span-2"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar por código, título o cliente…"></div>
    <select name="status" class="input">
        <option value="">Todos los estados</option>
        <?php foreach ($statusMap as $sk => [$sl,,]): ?>
            <option value="<?= $sk ?>" <?= $status===$sk?'selected':'' ?>><?= $sl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="client_type" class="input">
        <option value="">Todos los tipos</option>
        <option value="company" <?= $clientType==='company'?'selected':'' ?>>Empresa</option>
        <option value="individual" <?= $clientType==='individual'?'selected':'' ?>>Individual</option>
        <option value="lead" <?= $clientType==='lead'?'selected':'' ?>>Lead (CRM)</option>
    </select>
    <button class="btn btn-soft btn-sm"><i class="lucide lucide-filter text-[13px]"></i> Filtrar</button>
</form>

<?php if (empty($quotes)): ?>
    <div class="card card-pad text-center py-20">
        <div class="w-16 h-16 rounded-2xl bg-brand-50 grid place-items-center mx-auto mb-4"><i class="lucide lucide-file-text text-[26px] text-brand-600"></i></div>
        <h3 class="font-display font-bold text-[18px]">Sin cotizaciones</h3>
        <p class="text-[13px] text-ink-400 mt-1 max-w-md mx-auto">Crea tu primera cotización con líneas de items, ITBIS configurable y descuentos. Exportala como PDF profesional con tu logo y enviala al cliente con un link único.</p>
        <?php if ($auth->can('quotes.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/quotes/create') ?>" class="btn btn-primary btn-sm mt-4 inline-flex"><i class="lucide lucide-plus"></i> Crear la primera</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card overflow-hidden">
        <table class="admin-table" style="width:100%">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título / Cliente</th>
                    <th>Emitida</th>
                    <th>Válida hasta</th>
                    <th class="text-right">Total</th>
                    <th>Estado</th>
                    <th>Owner</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $q):
                    [$sl, $sCol, $sIc] = $statusMap[$q['status']] ?? ['—', '#6b7280', 'help-circle'];
                    $isExpiringSoon = $q['valid_until'] && in_array($q['status'], ['sent','viewed'], true) && strtotime($q['valid_until']) < strtotime('+3 days');
                ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/t/' . $slug . '/quotes/' . (int)$q['id']) ?>'">
                        <td class="font-mono text-[12px] text-ink-500"><?= $e($q['code']) ?></td>
                        <td>
                            <?php if (!empty($q['title'])): ?>
                                <div class="font-display font-bold text-[13px]"><?= $e($q['title']) ?></div>
                            <?php endif; ?>
                            <div class="text-[12px] text-ink-700"><?= $e($q['client_name']) ?></div>
                            <?php if (!empty($q['client_email'])): ?>
                                <div class="text-[11px] text-ink-400"><?= $e($q['client_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-[12px]"><?= $q['issued_at'] ? date('d M Y', strtotime($q['issued_at'])) : '—' ?></td>
                        <td class="text-[12px] <?= $isExpiringSoon ? 'text-amber-700 font-bold' : '' ?>">
                            <?= $q['valid_until'] ? date('d M Y', strtotime($q['valid_until'])) : '—' ?>
                            <?php if ($isExpiringSoon): ?> <i class="lucide lucide-alert-triangle text-[11px]"></i><?php endif; ?>
                        </td>
                        <td class="text-right font-mono font-bold text-[13px]"><?= $e($q['currency_symbol']) ?> <?= number_format((float)$q['total'], 2) ?></td>
                        <td><span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $sCol ?>1a;color:<?= $sCol ?>"><i class="lucide lucide-<?= $sIc ?> text-[10px]"></i> <?= $e($sl) ?></span></td>
                        <td class="text-[11.5px]"><?= $e($q['owner_name'] ?? '—') ?></td>
                        <td class="text-right">
                            <a href="<?= $url('/t/' . $slug . '/quotes/' . (int)$q['id'] . '/pdf') ?>" target="_blank" onclick="event.stopPropagation()" class="text-brand-700" data-tooltip="Ver PDF"><i class="lucide lucide-file-text text-[14px]"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="text-[11px] text-ink-400 mt-2">Mostrando <?= count($quotes) ?> resultados.</div>
<?php endif; ?>
