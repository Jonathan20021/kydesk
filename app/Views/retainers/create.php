<?php $slug = $tenant->slug;
$unitLabels = [
    'hour'    => 'horas',
    'ticket'  => 'tickets',
    'user'    => 'usuarios',
    'license' => 'licencias',
    'project' => 'proyectos',
    'month'   => 'meses',
    'custom'  => 'personalizado',
];
// Convertir items del template a JSON para Alpine
$initialItems = [];
foreach ($templateItems as $ti) {
    $initialItems[] = [
        'category_id' => $ti['category_id'] ? (int)$ti['category_id'] : '',
        'title' => $ti['title'],
        'description' => $ti['description'] ?? '',
        'quantity' => (float)$ti['quantity'],
        'unit' => $ti['unit'],
        'unit_label' => $ti['unit_label'] ?? '',
        'unit_rate' => (float)$ti['unit_rate'],
        'amount' => (float)$ti['amount'],
        'is_recurring' => (int)$ti['is_recurring'] === 1,
        'is_billable' => (int)$ti['is_billable'] === 1,
    ];
}
$tpl = $template;
?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/retainers') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] mt-2">Nueva iguala</h1>
    <p class="text-[13px] text-ink-400">Configurá un contrato recurrente. Empezá desde una plantilla o desde cero.</p>
</div>

<?php if (empty($categories)): ?>
    <div class="card card-pad mb-4" style="background:#fff7ed;border-color:#fed7aa">
        <div class="flex items-start gap-3">
            <i class="lucide lucide-info text-[18px]" style="color:#c2410c"></i>
            <div class="text-[12.5px]" style="color:#7c2d12">
                Aún no hay categorías de iguala configuradas.
                <?php if ($auth->can('retainers.config')): ?>
                    <a href="<?= $url('/t/' . $slug . '/retainers/settings?tab=categories') ?>" class="font-bold underline">Configura categorías ahora</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Plantilla rápida -->
<?php if (!empty($templates) && !$tpl): ?>
    <div class="card card-pad mb-4">
        <div class="flex items-center gap-2 mb-3">
            <i class="lucide lucide-sparkles text-[16px] text-brand-600"></i>
            <h3 class="font-display font-bold text-[15px]">Empezá desde una plantilla</h3>
            <span class="text-[11px] text-ink-400">o desplegá el formulario debajo para arrancar en blanco</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
            <?php foreach ($templates as $t): ?>
                <a href="<?= $url('/t/' . $slug . '/retainers/create?template_id=' . (int)$t['id']) ?>" class="card card-pad hover:shadow-md transition" style="text-decoration:none;color:inherit">
                    <div class="font-display font-bold text-[13.5px]"><?= $e($t['name']) ?></div>
                    <?php if (!empty($t['description'])): ?>
                        <p class="text-[11.5px] text-ink-400 mt-1 line-clamp-2"><?= $e($t['description']) ?></p>
                    <?php endif; ?>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-[11px] text-ink-500"><?= ['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'][$t['billing_cycle']] ?? '—' ?></span>
                        <span class="font-mono text-[13px] font-bold text-brand-700"><?= $e($t['currency']) ?> <?= number_format((float)$t['amount'], 0) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $url('/t/' . $slug . '/retainers') ?>"
      x-data='{
          type: "company",
          items: <?= htmlspecialchars(json_encode($initialItems), ENT_QUOTES) ?>,
          categories: <?= htmlspecialchars(json_encode(array_map(fn($c) => ['id'=>(int)$c['id'],'name'=>$c['name'],'default_unit'=>$c['default_unit'],'default_unit_label'=>$c['default_unit_label'],'color'=>$c['color']], $categories)), ENT_QUOTES) ?>,
          addItem(){ this.items.push({category_id:"",title:"",description:"",quantity:1,unit:"hour",unit_label:"",unit_rate:0,amount:0,is_recurring:true,is_billable:true}); },
          removeItem(i){ this.items.splice(i,1); },
          recalc(i){ const it = this.items[i]; it.amount = (parseFloat(it.quantity)||0) * (parseFloat(it.unit_rate)||0); },
          onCategoryChange(i){ const it = this.items[i]; const cat = this.categories.find(c => c.id == it.category_id); if (cat) { it.unit = cat.default_unit; if (cat.default_unit_label) it.unit_label = cat.default_unit_label; } },
          totalRecurring(){ return this.items.filter(it => it.is_recurring && it.is_billable).reduce((s,it) => s + (parseFloat(it.amount)||0), 0); }
      }' class="space-y-4 max-w-4xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <?php if ($tpl): ?>
        <input type="hidden" name="template_id" value="<?= (int)$tpl['id'] ?>">
        <div class="card card-pad" style="background:#f3f0ff;border-color:#cdbfff">
            <div class="flex items-center gap-3">
                <i class="lucide lucide-sparkles text-[18px] text-brand-600"></i>
                <div class="flex-1">
                    <div class="font-display font-bold text-[14px]">Usando plantilla: <?= $e($tpl['name']) ?></div>
                    <p class="text-[11.5px] text-ink-500 mt-0.5"><?= $e($tpl['description'] ?? '') ?></p>
                </div>
                <a href="<?= $url('/t/' . $slug . '/retainers/create') ?>" class="text-[12px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-x"></i> Quitar plantilla</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cliente -->
    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px] mb-3">Cliente</h3>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <button type="button" @click="type='company'" :class="type==='company' ? 'ring-2 ring-brand-500 bg-brand-50' : ''" class="card card-pad text-left transition" style="cursor:pointer">
                <div class="flex items-center gap-2 mb-1">
                    <i class="lucide lucide-building-2 text-[16px] text-brand-600"></i>
                    <div class="font-display font-bold text-[14px]">Empresa</div>
                </div>
                <p class="text-[11.5px] text-ink-400">Cliente corporativo</p>
            </button>
            <button type="button" @click="type='individual'" :class="type==='individual' ? 'ring-2 ring-brand-500 bg-brand-50' : ''" class="card card-pad text-left transition" style="cursor:pointer">
                <div class="flex items-center gap-2 mb-1">
                    <i class="lucide lucide-user text-[16px] text-brand-600"></i>
                    <div class="font-display font-bold text-[14px]">Cliente individual</div>
                </div>
                <p class="text-[11.5px] text-ink-400">Persona física / freelancer</p>
            </button>
        </div>
        <input type="hidden" name="client_type" :value="type">

        <div x-show="type==='company'" class="space-y-3">
            <div>
                <label class="label">Empresa *</label>
                <select name="company_id" class="input">
                    <option value="">— Selecciona una empresa —</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-ink-400 mt-1">¿No aparece? <a href="<?= $url('/t/' . $slug . '/companies/create') ?>" class="text-brand-600 font-semibold">Crear empresa</a></p>
            </div>
        </div>

        <div x-show="type==='individual'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div><label class="label">Nombre completo *</label><input name="client_name" class="input" placeholder="Ej: María Rodríguez"></div>
            <div><label class="label">Documento</label><input name="client_doc" class="input" placeholder="DNI / RUT / CC"></div>
            <div><label class="label">Email</label><input name="client_email" type="email" class="input"></div>
            <div><label class="label">Teléfono</label><input name="client_phone" class="input"></div>
        </div>
    </div>

    <!-- Detalles + categoría -->
    <div class="card card-pad space-y-3">
        <h3 class="font-display font-bold text-[15px]">Iguala</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
                <label class="label">Nombre del contrato *</label>
                <input name="name" required class="input" value="<?= $tpl ? $e($tpl['name']) : '' ?>" placeholder="Ej: Iguala mensual de Soporte TI 2026">
            </div>
            <div>
                <label class="label">Categoría / Tipo</label>
                <select name="category_id" class="input">
                    <option value="">— Sin categorizar —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($tpl && (int)$tpl['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= $e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="label">Descripción</label>
            <textarea name="description" rows="2" class="input" placeholder="¿Qué incluye este contrato a alto nivel?"></textarea>
        </div>
        <div>
            <label class="label">Alcance / Servicios</label>
            <textarea name="scope" rows="3" class="input" placeholder="Detalle del alcance: tipos de tickets, SLAs, productos cubiertos…"><?= $tpl ? $e($tpl['scope']) : '' ?></textarea>
        </div>
    </div>

    <!-- Items / Líneas del contrato -->
    <div class="card card-pad">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <div>
                <h3 class="font-display font-bold text-[15px]">Items del contrato</h3>
                <p class="text-[11.5px] text-ink-400">Detalle de entregables, horas por categoría, módulos y productos cubiertos.</p>
            </div>
            <div class="flex items-center gap-2 text-[12px]">
                <span class="text-ink-500">Total recurrente facturable:</span>
                <span class="font-mono font-bold text-[15px] text-brand-700">$<span x-text="totalRecurring().toFixed(2)"></span></span>
                <button type="button" @click="addItem()" class="btn btn-soft btn-xs"><i class="lucide lucide-plus text-[12px]"></i> Agregar item</button>
            </div>
        </div>

        <template x-if="items.length === 0">
            <div class="text-center py-8 border-2 border-dashed rounded-xl" style="border-color:#e5e7eb">
                <i class="lucide lucide-list text-[22px] text-ink-300"></i>
                <p class="text-[12.5px] text-ink-400 mt-2">Sin items. Agregá el primer entregable, módulo de horas o producto incluido.</p>
                <button type="button" @click="addItem()" class="btn btn-soft btn-xs mt-3"><i class="lucide lucide-plus text-[12px]"></i> Agregar item</button>
            </div>
        </template>

        <div class="space-y-2">
            <template x-for="(it, i) in items" :key="i">
                <div class="rounded-xl p-3" style="border:1px solid var(--border);background:#fafafb">
                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-12 md:col-span-5">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Título *</label>
                            <input :name="'items['+i+'][title]'" x-model="it.title" required class="input" style="height:36px" placeholder="Ej: Soporte hasta 40h, Licencia Office 365, Sprint dev frontend">
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Categoría</label>
                            <select :name="'items['+i+'][category_id]'" x-model="it.category_id" @change="onCategoryChange(i)" class="input" style="height:36px">
                                <option value="">— Ninguna —</option>
                                <template x-for="c in categories" :key="c.id">
                                    <option :value="c.id" x-text="c.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Cantidad</label>
                            <input :name="'items['+i+'][quantity]'" x-model.number="it.quantity" @input="recalc(i)" type="number" step="0.25" min="0" class="input" style="height:36px">
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Unidad</label>
                            <select :name="'items['+i+'][unit]'" x-model="it.unit" class="input" style="height:36px">
                                <?php foreach ($unitLabels as $u => $lbl): ?>
                                    <option value="<?= $u ?>"><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Etiqueta unidad</label>
                            <input :name="'items['+i+'][unit_label]'" x-model="it.unit_label" class="input" style="height:36px" placeholder="ej: horas dev, sitios">
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Tarifa unitaria</label>
                            <input :name="'items['+i+'][unit_rate]'" x-model.number="it.unit_rate" @input="recalc(i)" type="number" step="0.01" min="0" class="input" style="height:36px">
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Importe</label>
                            <input :name="'items['+i+'][amount]'" x-model.number="it.amount" type="number" step="0.01" min="0" class="input" style="height:36px">
                        </div>
                        <div class="col-span-6 md:col-span-3 flex items-end gap-3 pb-1.5">
                            <label class="flex items-center gap-1.5 text-[12px]">
                                <input type="hidden" :name="'items['+i+'][is_recurring]'" :value="it.is_recurring ? 1 : 0">
                                <input type="checkbox" x-model="it.is_recurring"> Recurrente
                            </label>
                            <label class="flex items-center gap-1.5 text-[12px]">
                                <input type="hidden" :name="'items['+i+'][is_billable]'" :value="it.is_billable ? 1 : 0">
                                <input type="checkbox" x-model="it.is_billable"> Facturable
                            </label>
                        </div>
                        <div class="col-span-12">
                            <input :name="'items['+i+'][description]'" x-model="it.description" class="input" style="height:34px;font-size:12px" placeholder="Descripción / notas para este item (opcional)">
                        </div>
                    </div>
                    <div class="flex justify-end mt-2">
                        <button type="button" @click="removeItem(i)" class="text-[11.5px] text-red-600 hover:text-red-800 inline-flex items-center gap-1"><i class="lucide lucide-trash-2 text-[12px]"></i> Quitar item</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Facturación general -->
    <div class="card card-pad space-y-3">
        <h3 class="font-display font-bold text-[15px]">Facturación general</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="label">Ciclo *</label>
                <select name="billing_cycle" class="input">
                    <?php foreach (['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'] as $k=>$lbl): ?>
                        <option value="<?= $k ?>" <?= ($tpl && $tpl['billing_cycle']===$k) ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="label">Monto total *</label><input name="amount" type="number" step="0.01" min="0" value="<?= $tpl ? $e($tpl['amount']) : '0' ?>" class="input"></div>
            <div><label class="label">Moneda</label><input name="currency" class="input" value="<?= $tpl ? $e($tpl['currency']) : 'USD' ?>" maxlength="8"></div>
            <div><label class="label">Horas incluidas</label><input name="included_hours" type="number" step="0.25" min="0" value="<?= $tpl ? $e($tpl['included_hours']) : '0' ?>" class="input"></div>
            <div><label class="label">Tickets incluidos</label><input name="included_tickets" type="number" min="0" value="<?= $tpl ? $e($tpl['included_tickets']) : '0' ?>" class="input"></div>
            <div><label class="label">Tarifa hora extra</label><input name="overage_hour_rate" type="number" step="0.01" min="0" value="<?= $tpl ? $e($tpl['overage_hour_rate']) : '0' ?>" class="input"></div>
            <div><label class="label">Impuesto (%)</label><input name="tax_pct" type="number" step="0.01" min="0" max="100" value="<?= $tpl ? $e($tpl['tax_pct']) : '0' ?>" class="input"></div>
            <div><label class="label">Términos de pago</label><input name="payment_terms" class="input" value="<?= $tpl ? $e($tpl['payment_terms']) : '' ?>" placeholder="Ej: 30 días, contado, anticipo"></div>
        </div>
    </div>

    <!-- SLA opcional -->
    <div class="card card-pad space-y-3">
        <h3 class="font-display font-bold text-[15px]">SLA del contrato (opcional)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div><label class="label">Tiempo de respuesta (minutos)</label><input name="response_sla_minutes" type="number" min="0" class="input" placeholder="Ej: 60"></div>
            <div><label class="label">Tiempo de resolución (minutos)</label><input name="resolve_sla_minutes" type="number" min="0" class="input" placeholder="Ej: 480"></div>
        </div>
    </div>

    <!-- Vigencia -->
    <div class="card card-pad space-y-3">
        <h3 class="font-display font-bold text-[15px]">Vigencia</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div><label class="label">Inicio *</label><input name="starts_on" type="date" required value="<?= date('Y-m-d') ?>" class="input"></div>
            <div><label class="label">Fin (opcional)</label><input name="ends_on" type="date" class="input"></div>
            <div>
                <label class="label">Estado inicial</label>
                <select name="status" class="input">
                    <option value="active" selected>Activa</option>
                    <option value="draft">Borrador</option>
                    <option value="paused">Pausada</option>
                </select>
            </div>
        </div>
        <label class="flex items-center gap-2 text-[13px]">
            <input type="checkbox" name="auto_renew" value="1" checked> Renovación automática al cerrar período
        </label>
        <div>
            <label class="label">Notas internas</label>
            <textarea name="notes" rows="2" class="input" placeholder="Información sólo visible para el equipo."></textarea>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 sticky bottom-3 card card-pad" style="z-index:10;box-shadow:0 -8px 20px -8px rgba(22,21,27,.08)">
        <a href="<?= $url('/t/' . $slug . '/retainers') ?>" class="btn btn-soft btn-sm">Cancelar</a>
        <button class="btn btn-primary"><i class="lucide lucide-check"></i> Crear iguala</button>
    </div>
</form>
