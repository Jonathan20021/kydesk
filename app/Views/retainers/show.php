<?php use App\Core\Helpers; $slug = $tenant->slug;
$cycleLabel = ['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'];
$statusMap = [
    'draft'     => ['Borrador',   'badge-gray',   'pencil'],
    'active'    => ['Activa',     'badge-green',  'play-circle'],
    'paused'    => ['Pausada',    'badge-amber',  'pause-circle'],
    'cancelled' => ['Cancelada',  'badge-red',    'x-circle'],
    'expired'   => ['Expirada',   'badge-red',    'clock'],
];
[$sl,$scl,$si] = $statusMap[$r['status']] ?? ['—','badge-gray','help-circle'];
$totalIncluded = (float)($currentPeriod['included_hours'] ?? 0);
$consumed = (float)($currentPeriod['consumed_hours'] ?? 0);
$pctUsed = $totalIncluded > 0 ? min(100, ($consumed / $totalIncluded) * 100) : 0;
$unitLabels = [
    'hour'    => 'h',
    'ticket'  => 'tickets',
    'user'    => 'usuarios',
    'license' => 'lic.',
    'project' => 'proyectos',
    'month'   => 'meses',
    'custom'  => '',
];

// Convertir items existentes a JSON para tab edit
$itemsForJs = [];
foreach ($items as $it) {
    $itemsForJs[] = [
        'category_id' => $it['category_id'] ? (int)$it['category_id'] : '',
        'title' => $it['title'],
        'description' => $it['description'] ?? '',
        'quantity' => (float)$it['quantity'],
        'unit' => $it['unit'],
        'unit_label' => $it['unit_label'] ?? '',
        'unit_rate' => (float)$it['unit_rate'],
        'amount' => (float)$it['amount'],
        'is_recurring' => (int)$it['is_recurring'] === 1,
        'is_billable' => (int)$it['is_billable'] === 1,
    ];
}
$catsJs = array_map(fn($c) => ['id'=>(int)$c['id'],'name'=>$c['name'],'default_unit'=>$c['default_unit'],'default_unit_label'=>$c['default_unit_label'],'color'=>$c['color']], $categories);

$itemsTotal = 0.0;
$itemsRecurringTotal = 0.0;
foreach ($items as $it) {
    $itemsTotal += (float)$it['amount'];
    if ((int)$it['is_recurring'] === 1 && (int)$it['is_billable'] === 1) $itemsRecurringTotal += (float)$it['amount'];
}
?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/retainers') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a igualas</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="card card-pad lg:col-span-2">
        <div class="flex items-start gap-4">
            <?php if (!empty($r['category_color'])): ?>
                <div class="w-14 h-14 rounded-2xl grid place-items-center" style="background:<?= $e($r['category_color']) ?>15;color:<?= $e($r['category_color']) ?>;border:1px solid <?= $e($r['category_color']) ?>33"><i class="lucide lucide-<?= $e($r['category_icon']) ?> text-[22px]"></i></div>
            <?php else: ?>
                <div class="w-14 h-14 rounded-2xl grid place-items-center" style="background:#ecfdf5;color:#10b981"><i class="lucide lucide-handshake text-[22px]"></i></div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap text-[11.5px]">
                    <span class="font-mono text-ink-500"><?= $e($r['code']) ?></span>
                    <?php if ($r['category_name']): ?>
                        <span class="px-2 py-0.5 rounded-full font-semibold" style="background:<?= $e($r['category_color']) ?>15;color:<?= $e($r['category_color']) ?>"><?= $e($r['category_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($r['template_name'])): ?>
                        <span class="text-ink-400">· basada en plantilla "<?= $e($r['template_name']) ?>"</span>
                    <?php endif; ?>
                </div>
                <div class="font-display font-extrabold text-[22px] tracking-[-0.02em] mt-0.5"><?= $e($r['name']) ?></div>
                <div class="text-[12.5px] text-ink-500 mt-1 flex items-center gap-3 flex-wrap">
                    <?php if ($r['client_type'] === 'company'): ?>
                        <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-building-2 text-[12px]"></i> <?= $e($r['company_name'] ?? '—') ?></span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-user text-[12px]"></i> <?= $e($r['client_name'] ?? '—') ?></span>
                    <?php endif; ?>
                    <?php if (!empty($r['client_email'])): ?>
                        <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-mail text-[12px]"></i> <?= $e($r['client_email']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($r['client_phone'])): ?>
                        <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-phone text-[12px]"></i> <?= $e($r['client_phone']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <span class="badge <?= $scl ?>"><i class="lucide lucide-<?= $si ?> text-[10px]"></i> <?= $sl ?></span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5 pt-4" style="border-top:1px solid var(--border)">
            <div>
                <div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Ciclo</div>
                <div class="font-display font-bold text-[16px] mt-0.5"><?= $cycleLabel[$r['billing_cycle']] ?? $r['billing_cycle'] ?></div>
            </div>
            <div>
                <div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Monto</div>
                <div class="font-display font-bold text-[16px] mt-0.5"><?= $e($r['currency']) ?> <?= number_format((float)$r['amount'], 2) ?></div>
                <?php if ((float)$r['tax_pct'] > 0): ?>
                    <div class="text-[10.5px] text-ink-400">+ <?= rtrim(rtrim(number_format((float)$r['tax_pct'], 2), '0'), '.') ?>% imp.</div>
                <?php endif; ?>
            </div>
            <div>
                <div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Inicio</div>
                <div class="font-display font-bold text-[16px] mt-0.5"><?= $e($r['starts_on']) ?></div>
            </div>
            <div>
                <div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Próx. facturación</div>
                <div class="font-display font-bold text-[16px] mt-0.5"><?= $e($r['next_invoice_on'] ?: '—') ?></div>
            </div>
        </div>
    </div>

    <!-- Período actual -->
    <div class="card card-pad">
        <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400">Período actual</div>
        <?php if ($currentPeriod): ?>
            <div class="text-[12.5px] mt-1.5 text-ink-500"><?= $e($currentPeriod['period_start']) ?> → <?= $e($currentPeriod['period_end']) ?></div>
            <div class="mt-3">
                <div class="flex items-center justify-between text-[11.5px] mb-1">
                    <span class="text-ink-500">Horas consumidas</span>
                    <span class="font-mono font-semibold"><?= number_format($consumed, 2) ?> / <?= number_format($totalIncluded, 2) ?>h</span>
                </div>
                <div style="height:8px;background:#f3f4f6;border-radius:999px;overflow:hidden">
                    <div style="height:100%;background:<?= $consumed > $totalIncluded ? '#ef4444' : '#10b981' ?>;width:<?= $pctUsed ?>%;transition:width .3s"></div>
                </div>
                <?php if ($consumed > $totalIncluded && $totalIncluded > 0): ?>
                    <div class="text-[11.5px] text-red-600 mt-1.5"><i class="lucide lucide-alert-triangle text-[11px]"></i> Excedente: <?= number_format($consumed - $totalIncluded, 2) ?>h · $<?= number_format((float)$currentPeriod['overage_amount'], 2) ?></div>
                <?php endif; ?>
            </div>
            <?php if ($auth->can('retainers.bill')): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/' . $r['id'] . '/periods/' . $currentPeriod['id'] . '/close') ?>" onsubmit="return confirm('Cerrar este período? Se generará el siguiente automáticamente si la iguala está activa.')" class="mt-3">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-soft btn-xs" style="width:100%"><i class="lucide lucide-check-circle"></i> Cerrar período</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-[12.5px] text-ink-400 mt-2">No hay período abierto. Activá la iguala o registrá un consumo para iniciar uno.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs -->
<div x-data='{tab:"items", items: <?= htmlspecialchars(json_encode($itemsForJs), ENT_QUOTES) ?>, categories: <?= htmlspecialchars(json_encode($catsJs), ENT_QUOTES) ?>,
     addItem(){ this.items.push({category_id:"",title:"",description:"",quantity:1,unit:"hour",unit_label:"",unit_rate:0,amount:0,is_recurring:true,is_billable:true}); },
     removeItem(i){ this.items.splice(i,1); },
     recalc(i){ const it = this.items[i]; it.amount = (parseFloat(it.quantity)||0) * (parseFloat(it.unit_rate)||0); },
     onCategoryChange(i){ const it = this.items[i]; const cat = this.categories.find(c => c.id == it.category_id); if (cat) { it.unit = cat.default_unit; if (cat.default_unit_label) it.unit_label = cat.default_unit_label; } },
     totalRecurring(){ return this.items.filter(it => it.is_recurring && it.is_billable).reduce((s,it) => s + (parseFloat(it.amount)||0), 0); }
}'>
    <div class="admin-tabs mb-4" style="background:white;border:1px solid var(--border);max-width:fit-content">
        <?php foreach ([
            'items'        => ['Items ('.count($items).')', 'list'],
            'consumptions' => ['Consumos ('.count($consumptions).')', 'clock'],
            'periods'      => ['Períodos ('.count($periods).')',     'calendar'],
            'edit'         => ['Editar',                              'pencil'],
        ] as $key => [$lbl,$ic]): ?>
            <button type="button" @click="tab='<?= $key ?>'" :class="tab==='<?= $key ?>' && 'active'" class="admin-tab"><i class="lucide lucide-<?= $ic ?> text-[13px]"></i> <?= $e($lbl) ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Items -->
    <div x-show="tab==='items'">
        <?php if (empty($items)): ?>
            <div class="card card-pad text-center py-12">
                <i class="lucide lucide-list text-[24px] text-ink-300"></i>
                <h3 class="font-display font-bold mt-3">Sin items en este contrato</h3>
                <p class="text-[12.5px] text-ink-400 mt-1">Editá la iguala desde la pestaña "Editar" para agregar entregables, horas por categoría, productos o módulos.</p>
            </div>
        <?php else: ?>
            <div class="card overflow-hidden">
                <table class="admin-table">
                    <thead><tr><th>Item</th><th>Categoría</th><th class="text-right">Cant.</th><th>Unidad</th><th class="text-right">Tarifa</th><th class="text-right">Importe</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="font-display font-bold text-[13px]"><?= $e($it['title']) ?></div>
                                    <?php if (!empty($it['description'])): ?><div class="text-[11.5px] text-ink-500 mt-0.5 line-clamp-1"><?= $e($it['description']) ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($it['category_name'])): ?>
                                        <span class="inline-flex items-center gap-1 text-[11.5px] font-semibold px-2 py-0.5 rounded-full" style="background:<?= $e($it['category_color']) ?>15;color:<?= $e($it['category_color']) ?>"><i class="lucide lucide-<?= $e($it['category_icon']) ?> text-[10px]"></i> <?= $e($it['category_name']) ?></span>
                                    <?php else: ?><span class="text-ink-400">—</span><?php endif; ?>
                                </td>
                                <td class="text-right font-mono"><?= rtrim(rtrim(number_format((float)$it['quantity'], 2), '0'), '.') ?></td>
                                <td class="text-[12px]"><?= $e($it['unit_label'] ?: ($unitLabels[$it['unit']] ?? $it['unit'])) ?></td>
                                <td class="text-right font-mono text-[12px]"><?= number_format((float)$it['unit_rate'], 2) ?></td>
                                <td class="text-right font-mono font-bold"><?= number_format((float)$it['amount'], 2) ?></td>
                                <td>
                                    <?php if (!(int)$it['is_billable']): ?><span class="badge badge-gray">No facturable</span><?php endif; ?>
                                    <?php if (!(int)$it['is_recurring']): ?><span class="badge badge-amber">Único</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background:#fafafb;font-weight:700">
                            <td colspan="5" class="text-right">Total recurrente facturable</td>
                            <td class="text-right font-mono">$<?= number_format($itemsRecurringTotal, 2) ?></td>
                            <td></td>
                        </tr>
                        <tr style="background:#fafafb;font-weight:700">
                            <td colspan="5" class="text-right">Suma de items</td>
                            <td class="text-right font-mono">$<?= number_format($itemsTotal, 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Consumos -->
    <div x-show="tab==='consumptions'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <?php if ($auth->can('retainers.bill')): ?>
            <div class="card card-pad">
                <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Registrar consumo</h3>
                <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/' . $r['id'] . '/consumptions') ?>" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div>
                        <label class="label">Horas *</label>
                        <input name="hours" type="number" step="0.25" min="0.25" required class="input" placeholder="2.5">
                    </div>
                    <div>
                        <label class="label">Fecha</label>
                        <input name="consumed_at" type="datetime-local" value="<?= date('Y-m-d\TH:i') ?>" class="input">
                    </div>
                    <?php if (!empty($tickets)): ?>
                        <div>
                            <label class="label">Asociar ticket (opcional)</label>
                            <select name="ticket_id" class="input">
                                <option value="">— Sin asociar —</option>
                                <?php foreach ($tickets as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= $e($t['code']) ?> · <?= $e(mb_strimwidth($t['subject'], 0, 60, '…')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="label">Descripción</label>
                        <textarea name="description" rows="2" class="input" placeholder="¿Qué se hizo en estas horas?"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-[12.5px]">
                        <input type="checkbox" name="billable" value="1" checked> Es facturable
                    </label>
                    <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Registrar</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="<?= $auth->can('retainers.bill') ? 'lg:col-span-2' : 'lg:col-span-3' ?>">
            <div class="card overflow-hidden">
                <div class="p-4" style="border-bottom:1px solid var(--border)">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display font-bold text-[15px]">Historial de consumos</h3>
                        <span class="text-[12px] text-ink-500">Total acumulado: <strong><?= number_format($totalConsumed, 2) ?>h</strong></span>
                    </div>
                </div>
                <?php if (empty($consumptions)): ?>
                    <div class="text-center py-12 text-ink-400 text-[12.5px]">Sin consumos registrados todavía.</div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>Fecha</th><th>Horas</th><th>Ticket</th><th>Agente</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <?php foreach ($consumptions as $c): ?>
                                <tr>
                                    <td class="text-[11.5px] text-ink-500 font-mono"><?= $e($c['consumed_at']) ?></td>
                                    <td><span class="font-mono font-bold"><?= number_format((float)$c['hours'], 2) ?>h</span><?= !$c['billable'] ? ' <span class="badge badge-gray">No fact.</span>' : '' ?></td>
                                    <td>
                                        <?php if (!empty($c['ticket_code'])): ?>
                                            <a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$c['ticket_id']) ?>" class="text-brand-700 font-mono text-[11.5px]"><?= $e($c['ticket_code']) ?></a>
                                        <?php else: ?>
                                            <span class="text-ink-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-[12px]"><?= $e($c['user_name'] ?? '—') ?></td>
                                    <td class="text-[12px] text-ink-500 line-clamp-1 max-w-xs"><?= $e($c['description'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Períodos -->
    <div x-show="tab==='periods'" x-cloak>
        <div class="card overflow-hidden">
            <table class="admin-table">
                <thead><tr><th>Período</th><th>Monto</th><th>Horas (incl./consumidas)</th><th>Excedente</th><th>Estado</th><th>Facturado</th></tr></thead>
                <tbody>
                    <?php foreach ($periods as $p): ?>
                        <tr>
                            <td class="text-[12px] font-mono"><?= $e($p['period_start']) ?> → <?= $e($p['period_end']) ?></td>
                            <td>$<?= number_format((float)$p['amount'], 2) ?></td>
                            <td><?= number_format((float)$p['included_hours'], 2) ?>h / <strong><?= number_format((float)$p['consumed_hours'], 2) ?>h</strong></td>
                            <td><?= ((float)$p['overage_amount']) > 0 ? '<span class="text-red-600 font-bold">$' . number_format((float)$p['overage_amount'], 2) . '</span>' : '<span class="text-ink-400">—</span>' ?></td>
                            <td><span class="badge <?= $p['status'] === 'open' ? 'badge-green' : 'badge-gray' ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td class="text-[11.5px] text-ink-500"><?= $e($p['invoiced_at'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($periods)): ?><tr><td colspan="6" style="text-align:center;padding:20px;color:#8e8e9a">Sin períodos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Editar -->
    <div x-show="tab==='edit'" x-cloak>
        <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/' . $r['id']) ?>" x-data="{type:'<?= $e($r['client_type']) ?>'}" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="client_type" :value="type">

            <div class="card card-pad">
                <h3 class="font-display font-bold text-[15px] mb-3">Datos básicos</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2"><label class="label">Nombre</label><input name="name" value="<?= $e($r['name']) ?>" class="input"></div>
                    <div>
                        <label class="label">Categoría</label>
                        <select name="category_id" class="input">
                            <option value="">— Sin categorizar —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$r['category_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipo de cliente</label>
                        <select x-model="type" class="input">
                            <option value="company">Empresa</option>
                            <option value="individual">Cliente individual</option>
                        </select>
                    </div>
                    <div x-show="type==='company'">
                        <label class="label">Empresa</label>
                        <select name="company_id" class="input">
                            <option value="">—</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$r['company_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div x-show="type==='individual'" x-cloak><label class="label">Nombre cliente</label><input name="client_name" value="<?= $e($r['client_name']) ?>" class="input"></div>
                    <div><label class="label">Email cliente</label><input name="client_email" value="<?= $e($r['client_email']) ?>" class="input"></div>
                    <div><label class="label">Teléfono</label><input name="client_phone" value="<?= $e($r['client_phone']) ?>" class="input"></div>
                </div>
                <div class="mt-3"><label class="label">Descripción</label><textarea name="description" rows="2" class="input"><?= $e($r['description']) ?></textarea></div>
                <div class="mt-3"><label class="label">Alcance</label><textarea name="scope" rows="3" class="input"><?= $e($r['scope']) ?></textarea></div>
            </div>

            <!-- Items editables -->
            <div class="card card-pad">
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <h3 class="font-display font-bold text-[15px]">Items del contrato</h3>
                    <div class="flex items-center gap-2 text-[12px]">
                        <span class="text-ink-500">Recurrente facturable:</span>
                        <span class="font-mono font-bold text-[15px] text-brand-700">$<span x-text="totalRecurring().toFixed(2)"></span></span>
                        <button type="button" @click="addItem()" class="btn btn-soft btn-xs"><i class="lucide lucide-plus text-[12px]"></i> Agregar</button>
                    </div>
                </div>
                <div class="space-y-2">
                    <template x-for="(it, i) in items" :key="i">
                        <div class="rounded-xl p-3" style="border:1px solid var(--border);background:#fafafb">
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-12 md:col-span-5">
                                    <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Título *</label>
                                    <input :name="'items['+i+'][title]'" x-model="it.title" required class="input" style="height:36px">
                                </div>
                                <div class="col-span-6 md:col-span-3">
                                    <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Categoría</label>
                                    <select :name="'items['+i+'][category_id]'" x-model="it.category_id" @change="onCategoryChange(i)" class="input" style="height:36px">
                                        <option value="">— Ninguna —</option>
                                        <template x-for="c in categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                                    </select>
                                </div>
                                <div class="col-span-6 md:col-span-2"><label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Cantidad</label><input :name="'items['+i+'][quantity]'" x-model.number="it.quantity" @input="recalc(i)" type="number" step="0.25" min="0" class="input" style="height:36px"></div>
                                <div class="col-span-6 md:col-span-2">
                                    <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Unidad</label>
                                    <select :name="'items['+i+'][unit]'" x-model="it.unit" class="input" style="height:36px">
                                        <?php foreach (['hour'=>'horas','ticket'=>'tickets','user'=>'usuarios','license'=>'licencias','project'=>'proyectos','month'=>'meses','custom'=>'personalizado'] as $u=>$lbl): ?>
                                            <option value="<?= $u ?>"><?= $lbl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-span-6 md:col-span-3"><label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Etiqueta</label><input :name="'items['+i+'][unit_label]'" x-model="it.unit_label" class="input" style="height:36px"></div>
                                <div class="col-span-6 md:col-span-3"><label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Tarifa</label><input :name="'items['+i+'][unit_rate]'" x-model.number="it.unit_rate" @input="recalc(i)" type="number" step="0.01" min="0" class="input" style="height:36px"></div>
                                <div class="col-span-6 md:col-span-3"><label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Importe</label><input :name="'items['+i+'][amount]'" x-model.number="it.amount" type="number" step="0.01" min="0" class="input" style="height:36px"></div>
                                <div class="col-span-6 md:col-span-3 flex items-end gap-3 pb-1.5">
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="hidden" :name="'items['+i+'][is_recurring]'" :value="it.is_recurring ? 1 : 0"><input type="checkbox" x-model="it.is_recurring"> Recurrente</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="hidden" :name="'items['+i+'][is_billable]'" :value="it.is_billable ? 1 : 0"><input type="checkbox" x-model="it.is_billable"> Facturable</label>
                                </div>
                                <div class="col-span-12"><input :name="'items['+i+'][description]'" x-model="it.description" class="input" style="height:34px;font-size:12px" placeholder="Descripción"></div>
                            </div>
                            <div class="flex justify-end mt-2">
                                <button type="button" @click="removeItem(i)" class="text-[11.5px] text-red-600 hover:text-red-800 inline-flex items-center gap-1"><i class="lucide lucide-trash-2 text-[12px]"></i> Quitar</button>
                            </div>
                        </div>
                    </template>
                    <template x-if="items.length === 0">
                        <div class="text-center py-6 border-2 border-dashed rounded-xl" style="border-color:#e5e7eb">
                            <p class="text-[12.5px] text-ink-400">Sin items en este contrato.</p>
                            <button type="button" @click="addItem()" class="btn btn-soft btn-xs mt-2"><i class="lucide lucide-plus text-[12px]"></i> Agregar item</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card card-pad">
                <h3 class="font-display font-bold text-[15px] mb-3">Facturación</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="label">Ciclo</label>
                        <select name="billing_cycle" class="input">
                            <?php foreach (['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'] as $k=>$lbl): ?>
                                <option value="<?= $k ?>" <?= $r['billing_cycle']===$k?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="label">Monto</label><input name="amount" type="number" step="0.01" value="<?= $e($r['amount']) ?>" class="input"></div>
                    <div><label class="label">Moneda</label><input name="currency" value="<?= $e($r['currency']) ?>" class="input"></div>
                    <div><label class="label">Horas incluidas</label><input name="included_hours" type="number" step="0.25" value="<?= $e($r['included_hours']) ?>" class="input"></div>
                    <div><label class="label">Tickets incluidos</label><input name="included_tickets" type="number" value="<?= (int)$r['included_tickets'] ?>" class="input"></div>
                    <div><label class="label">Tarifa hora extra</label><input name="overage_hour_rate" type="number" step="0.01" value="<?= $e($r['overage_hour_rate']) ?>" class="input"></div>
                    <div><label class="label">Impuesto (%)</label><input name="tax_pct" type="number" step="0.01" min="0" max="100" value="<?= $e($r['tax_pct']) ?>" class="input"></div>
                    <div><label class="label">Términos de pago</label><input name="payment_terms" value="<?= $e($r['payment_terms']) ?>" class="input"></div>
                    <div><label class="label">Resp. SLA (min)</label><input name="response_sla_minutes" type="number" min="0" value="<?= $e($r['response_sla_minutes']) ?>" class="input"></div>
                    <div><label class="label">Resol. SLA (min)</label><input name="resolve_sla_minutes" type="number" min="0" value="<?= $e($r['resolve_sla_minutes']) ?>" class="input"></div>
                    <div><label class="label">Fin (opcional)</label><input name="ends_on" type="date" value="<?= $e($r['ends_on']) ?>" class="input"></div>
                    <div>
                        <label class="label">Estado</label>
                        <select name="status" class="input">
                            <?php foreach (['draft'=>'Borrador','active'=>'Activa','paused'=>'Pausada','cancelled'=>'Cancelada','expired'=>'Expirada'] as $k=>$lbl): ?>
                                <option value="<?= $k ?>" <?= $r['status']===$k?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label class="flex items-end gap-2 pb-1.5"><input type="checkbox" name="auto_renew" value="1" <?= (int)$r['auto_renew']?'checked':'' ?>> Renovación automática</label>
                </div>
                <div class="mt-3"><label class="label">Notas internas</label><textarea name="notes" rows="2" class="input"><?= $e($r['notes']) ?></textarea></div>
            </div>

            <div class="flex items-center justify-between sticky bottom-3 card card-pad" style="z-index:10;box-shadow:0 -8px 20px -8px rgba(22,21,27,.08)">
                <?php if ($auth->can('retainers.delete')): ?>
                    <button type="button" onclick="if(confirm('Eliminar esta iguala y su historial?')) document.getElementById('delete-retainer').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                <?php else: ?><div></div><?php endif; ?>
                <div class="flex gap-2">
                    <a href="<?= $url('/t/' . $slug . '/retainers/' . $r['id']) ?>" class="btn btn-soft btn-sm">Cancelar</a>
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar cambios</button>
                </div>
            </div>
        </form>
        <?php if ($auth->can('retainers.delete')): ?>
            <form id="delete-retainer" method="POST" action="<?= $url('/t/' . $slug . '/retainers/' . $r['id'] . '/delete') ?>" style="display:none">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            </form>
        <?php endif; ?>
    </div>
</div>
