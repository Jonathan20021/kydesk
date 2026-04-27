<?php $slug = $tenant->slug;
$icons = ['briefcase','code-2','server','life-buoy','shield','cloud','megaphone','scale','calculator','globe','key','wrench','zap','bookmark','box','heart-handshake','headphones','users','user-cog','flask-conical','sparkles','book-open','truck','warehouse','graduation-cap'];
$colors = ['#10b981','#3b82f6','#7c5cff','#f59e0b','#ec4899','#0ea5e9','#22c55e','#ef4444','#a855f7','#0284c7','#dc2626','#6b7280','#0f766e','#06b6d4','#7c2d12','#16a34a'];
$unitOptions = ['hour'=>'Horas','ticket'=>'Tickets','user'=>'Usuarios','license'=>'Licencias','project'=>'Proyectos','month'=>'Meses','custom'=>'Personalizado'];
$cycleLabels = ['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'];
?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/retainers') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a igualas</a>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] mt-2">Configuración de Igualas</h1>
    <p class="text-[13px] text-ink-400">Categorías de servicios (Soporte TI, Desarrollo, Sistemas…) y plantillas reutilizables. Todo configurable desde acá.</p>
</div>

<div class="admin-tabs mb-4" style="background:white;border:1px solid var(--border);max-width:fit-content">
    <a href="<?= $url('/t/' . $slug . '/retainers/settings?tab=categories') ?>" class="admin-tab <?= $tab==='categories'?'active':'' ?>"><i class="lucide lucide-tags text-[13px]"></i> Categorías (<?= count($categories) ?>)</a>
    <a href="<?= $url('/t/' . $slug . '/retainers/settings?tab=templates') ?>" class="admin-tab <?= $tab==='templates'?'active':'' ?>"><i class="lucide lucide-sparkles text-[13px]"></i> Plantillas (<?= count($templates) ?>)</a>
</div>

<?php if ($tab === 'categories'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Crear categoría -->
        <div class="card card-pad lg:col-span-1" x-data="{color:'#10b981', icon:'briefcase'}">
            <h3 class="font-display font-bold text-[15px] mb-1 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nueva categoría</h3>
            <p class="text-[11.5px] text-ink-400 mb-3">Crea tipos de iguala como Soporte TI, Desarrollo Software, Sistemas, etc.</p>
            <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/categories') ?>" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre</label><input name="name" required maxlength="120" class="input" placeholder="Ej: Desarrollo de software"></div>
                <div><label class="label">Descripción</label><textarea name="description" rows="2" class="input" placeholder="¿Qué tipo de servicio cubre esta categoría?"></textarea></div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Unidad por defecto</label>
                        <select name="default_unit" class="input">
                            <?php foreach ($unitOptions as $u=>$lbl): ?>
                                <option value="<?= $u ?>"><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="label">Etiqueta unidad</label><input name="default_unit_label" class="input" placeholder="ej: horas dev"></div>
                </div>
                <div>
                    <label class="label">Color</label>
                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                        <?php foreach ($colors as $col): ?>
                            <button type="button" @click="color='<?= $col ?>'" :class="color==='<?= $col ?>' ? 'ring-2 ring-offset-2' : ''" class="w-7 h-7 rounded-lg" style="background:<?= $col ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="color" :value="color">
                </div>
                <div>
                    <label class="label">Icono</label>
                    <div class="mt-1 grid grid-cols-8 gap-1">
                        <?php foreach ($icons as $ic): ?>
                            <button type="button" @click="icon='<?= $ic ?>'" :class="icon==='<?= $ic ?>' ? 'border-2' : 'border'" class="w-9 h-9 grid place-items-center rounded-lg transition" :style="icon==='<?= $ic ?>' ? 'background:'+color+'12;border-color:'+color+';color:'+color : 'border-color:#ececef;color:#8e8e9a'">
                                <i class="lucide lucide-<?= $ic ?> text-[14px]"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="icon" :value="icon">
                </div>
                <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" checked> Activa</label>
                <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear categoría</button>
            </form>
        </div>

        <!-- Listado -->
        <div class="lg:col-span-2 space-y-2">
            <?php foreach ($categories as $c): ?>
                <div class="card card-pad" x-data="{open:false}">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0" style="background:<?= $e($c['color']) ?>15;color:<?= $e($c['color']) ?>;border:1px solid <?= $e($c['color']) ?>33"><i class="lucide lucide-<?= $e($c['icon']) ?> text-[18px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="font-display font-bold text-[14.5px]"><?= $e($c['name']) ?></div>
                                <?php if (!(int)$c['is_active']): ?><span class="badge badge-gray">Inactiva</span><?php endif; ?>
                                <?php if ((int)$c['retainers_count'] > 0): ?><span class="text-[11.5px] text-ink-500"><?= (int)$c['retainers_count'] ?> iguala<?= (int)$c['retainers_count']!==1?'s':'' ?></span><?php endif; ?>
                            </div>
                            <?php if (!empty($c['description'])): ?><p class="text-[12px] text-ink-500 mt-0.5 line-clamp-1"><?= $e($c['description']) ?></p><?php endif; ?>
                            <div class="text-[11px] text-ink-400 mt-1">Unidad: <?= $e($unitOptions[$c['default_unit']] ?? $c['default_unit']) ?><?= !empty($c['default_unit_label']) ? ' · "'.$e($c['default_unit_label']).'"' : '' ?> · Slug: <code class="font-mono"><?= $e($c['slug']) ?></code></div>
                        </div>
                        <button type="button" @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i> Editar</button>
                    </div>

                    <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)" x-data="{color:'<?= $e($c['color']) ?>', icon:'<?= $e($c['icon']) ?>'}">
                        <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/categories/' . $c['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <div><label class="label">Nombre</label><input name="name" value="<?= $e($c['name']) ?>" class="input"></div>
                            <div>
                                <label class="label">Unidad</label>
                                <select name="default_unit" class="input">
                                    <?php foreach ($unitOptions as $u=>$lbl): ?>
                                        <option value="<?= $u ?>" <?= $c['default_unit']===$u?'selected':'' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label class="label">Etiqueta unidad</label><input name="default_unit_label" value="<?= $e($c['default_unit_label']) ?>" class="input"></div>
                            <div><label class="label">Orden</label><input name="sort_order" type="number" value="<?= (int)$c['sort_order'] ?>" class="input"></div>
                            <div class="md:col-span-2"><label class="label">Descripción</label><textarea name="description" rows="2" class="input"><?= $e($c['description']) ?></textarea></div>
                            <div>
                                <label class="label">Color</label>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <?php foreach ($colors as $col): ?>
                                        <button type="button" @click="color='<?= $col ?>'" :class="color==='<?= $col ?>' ? 'ring-2 ring-offset-2' : ''" class="w-6 h-6 rounded-lg" style="background:<?= $col ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="color" :value="color">
                            </div>
                            <div>
                                <label class="label">Icono</label>
                                <div class="grid grid-cols-10 gap-1 mt-1">
                                    <?php foreach (array_slice($icons, 0, 20) as $ic): ?>
                                        <button type="button" @click="icon='<?= $ic ?>'" :class="icon==='<?= $ic ?>' ? 'border-2' : 'border'" class="w-7 h-7 grid place-items-center rounded transition" :style="icon==='<?= $ic ?>' ? 'background:'+color+'12;border-color:'+color+';color:'+color : 'border-color:#ececef;color:#8e8e9a'">
                                            <i class="lucide lucide-<?= $ic ?> text-[12px]"></i>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="icon" :value="icon">
                            </div>
                            <label class="md:col-span-2 flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" <?= (int)$c['is_active']?'checked':'' ?>> Activa</label>
                            <div class="md:col-span-2 flex justify-between gap-2 pt-2" style="border-top:1px solid var(--border)">
                                <button type="button" onclick="if(confirm('Eliminar esta categoría? Las igualas asociadas quedan sin categoría.')) document.getElementById('delete-cat-<?= (int)$c['id'] ?>').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                                <div class="flex gap-2">
                                    <button type="button" @click="open=false" class="btn btn-soft btn-sm">Cancelar</button>
                                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button>
                                </div>
                            </div>
                        </form>
                        <form id="delete-cat-<?= (int)$c['id'] ?>" method="POST" action="<?= $url('/t/' . $slug . '/retainers/categories/' . $c['id'] . '/delete') ?>" style="display:none">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <div class="card card-pad text-center py-12">
                    <i class="lucide lucide-tags text-[24px] text-ink-300"></i>
                    <h3 class="font-display font-bold mt-3">Sin categorías</h3>
                    <p class="text-[12.5px] text-ink-400 mt-1">Crea la primera categoría para clasificar tus igualas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: /* templates tab */ ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Crear plantilla -->
        <div class="card card-pad lg:col-span-1" x-data='{
            items: [],
            categories: <?= htmlspecialchars(json_encode(array_map(fn($c) => ["id"=>(int)$c["id"],"name"=>$c["name"],"default_unit"=>$c["default_unit"],"default_unit_label"=>$c["default_unit_label"]], $categories)), ENT_QUOTES) ?>,
            addItem(){ this.items.push({category_id:"",title:"",quantity:1,unit:"hour",unit_label:"",unit_rate:0,amount:0,is_recurring:true,is_billable:true}); },
            removeItem(i){ this.items.splice(i,1); },
            recalc(i){ const it = this.items[i]; it.amount = (parseFloat(it.quantity)||0) * (parseFloat(it.unit_rate)||0); },
            onCategoryChange(i){ const it = this.items[i]; const cat = this.categories.find(c => c.id == it.category_id); if (cat) { it.unit = cat.default_unit; if (cat.default_unit_label) it.unit_label = cat.default_unit_label; } }
        }'>
            <h3 class="font-display font-bold text-[15px] mb-1 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nueva plantilla</h3>
            <p class="text-[11.5px] text-ink-400 mb-3">Plantillas con items pre-cargados para crear igualas más rápido.</p>
            <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/templates') ?>" class="space-y-2.5">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre</label><input name="name" required class="input" placeholder="Ej: Soporte TI Premium"></div>
                <div>
                    <label class="label">Categoría</label>
                    <select name="category_id" class="input">
                        <option value="">— Sin categoría —</option>
                        <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="label">Descripción</label><textarea name="description" rows="2" class="input"></textarea></div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Ciclo</label>
                        <select name="billing_cycle" class="input">
                            <?php foreach ($cycleLabels as $k=>$lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="label">Monto</label><input name="amount" type="number" step="0.01" min="0" value="0" class="input"></div>
                    <div><label class="label">Moneda</label><input name="currency" value="USD" class="input"></div>
                    <div><label class="label">Horas incluidas</label><input name="included_hours" type="number" step="0.25" min="0" value="0" class="input"></div>
                    <div><label class="label">Tickets incluidos</label><input name="included_tickets" type="number" min="0" value="0" class="input"></div>
                    <div><label class="label">Tarifa hora extra</label><input name="overage_hour_rate" type="number" step="0.01" min="0" value="0" class="input"></div>
                </div>
                <div><label class="label">Alcance</label><textarea name="scope" rows="2" class="input"></textarea></div>

                <!-- Items mini-builder -->
                <div class="pt-2" style="border-top:1px solid var(--border)">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Items pre-cargados</span>
                        <button type="button" @click="addItem()" class="text-[11px] text-brand-600 font-semibold inline-flex items-center gap-1"><i class="lucide lucide-plus text-[11px]"></i> Agregar</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(it, i) in items" :key="i">
                            <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                                <input :name="'items['+i+'][title]'" x-model="it.title" required placeholder="Título item" class="input" style="height:32px;font-size:12px">
                                <div class="grid grid-cols-3 gap-1 mt-1">
                                    <select :name="'items['+i+'][category_id]'" x-model="it.category_id" @change="onCategoryChange(i)" class="input" style="height:30px;font-size:11px">
                                        <option value="">Cat.</option>
                                        <template x-for="c in categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                                    </select>
                                    <input :name="'items['+i+'][quantity]'" x-model.number="it.quantity" @input="recalc(i)" type="number" step="0.25" placeholder="Qty" class="input" style="height:30px;font-size:11px">
                                    <select :name="'items['+i+'][unit]'" x-model="it.unit" class="input" style="height:30px;font-size:11px">
                                        <?php foreach ($unitOptions as $u=>$lbl): ?><option value="<?= $u ?>"><?= $lbl ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-1 mt-1">
                                    <input :name="'items['+i+'][unit_rate]'" x-model.number="it.unit_rate" @input="recalc(i)" type="number" step="0.01" placeholder="Tarifa" class="input" style="height:30px;font-size:11px">
                                    <input :name="'items['+i+'][amount]'" x-model.number="it.amount" type="number" step="0.01" placeholder="Importe" class="input" style="height:30px;font-size:11px">
                                </div>
                                <div class="flex items-center justify-between mt-1.5">
                                    <div class="flex gap-2 text-[11px]">
                                        <label class="flex items-center gap-1"><input type="hidden" :name="'items['+i+'][is_recurring]'" :value="it.is_recurring ? 1 : 0"><input type="checkbox" x-model="it.is_recurring"> Rec.</label>
                                        <label class="flex items-center gap-1"><input type="hidden" :name="'items['+i+'][is_billable]'" :value="it.is_billable ? 1 : 0"><input type="checkbox" x-model="it.is_billable"> Fact.</label>
                                    </div>
                                    <button type="button" @click="removeItem(i)" class="text-[10px] text-red-600"><i class="lucide lucide-trash-2 text-[11px]"></i></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-[12px]"><input type="checkbox" name="is_active" value="1" checked> Plantilla activa</label>
                <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear plantilla</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-2">
            <?php if (empty($templates)): ?>
                <div class="card card-pad text-center py-12">
                    <i class="lucide lucide-sparkles text-[24px] text-ink-300"></i>
                    <h3 class="font-display font-bold mt-3">Sin plantillas</h3>
                    <p class="text-[12.5px] text-ink-400 mt-1">Crea plantillas con items pre-configurados para acelerar la alta de igualas.</p>
                </div>
            <?php else: foreach ($templates as $t): ?>
                <div class="card card-pad" x-data="{open:false}">
                    <div class="flex items-center gap-3">
                        <?php if (!empty($t['category_color'])): ?>
                            <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0" style="background:<?= $e($t['category_color']) ?>15;color:<?= $e($t['category_color']) ?>;border:1px solid <?= $e($t['category_color']) ?>33"><i class="lucide lucide-<?= $e($t['category_icon']) ?> text-[18px]"></i></div>
                        <?php else: ?>
                            <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0 bg-brand-50 text-brand-600"><i class="lucide lucide-sparkles text-[18px]"></i></div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="font-display font-bold text-[14.5px]"><?= $e($t['name']) ?></div>
                                <?php if (!(int)$t['is_active']): ?><span class="badge badge-gray">Inactiva</span><?php endif; ?>
                                <span class="text-[11.5px] text-ink-500"><?= (int)$t['items_count'] ?> items</span>
                                <?php if ($t['category_name']): ?><span class="text-[11.5px] text-ink-500">· <?= $e($t['category_name']) ?></span><?php endif; ?>
                            </div>
                            <?php if (!empty($t['description'])): ?><p class="text-[12px] text-ink-500 mt-0.5 line-clamp-1"><?= $e($t['description']) ?></p><?php endif; ?>
                            <div class="text-[11.5px] text-ink-400 mt-1"><?= $e($cycleLabels[$t['billing_cycle']] ?? '—') ?> · <?= $e($t['currency']) ?> <?= number_format((float)$t['amount'], 2) ?> · <?= rtrim(rtrim(number_format((float)$t['included_hours'], 2),'0'),'.') ?>h incl.</div>
                        </div>
                        <a href="<?= $url('/t/' . $slug . '/retainers/create?template_id=' . (int)$t['id']) ?>" class="btn btn-soft btn-xs" data-tooltip="Usar plantilla"><i class="lucide lucide-zap text-[12px]"></i> Usar</a>
                        <button type="button" @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i> Editar</button>
                    </div>

                    <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                        <form method="POST" action="<?= $url('/t/' . $slug . '/retainers/templates/' . $t['id']) ?>" class="space-y-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <div><label class="label">Nombre</label><input name="name" value="<?= $e($t['name']) ?>" class="input"></div>
                            <div>
                                <label class="label">Categoría</label>
                                <select name="category_id" class="input">
                                    <option value="">—</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= (int)$t['category_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2"><label class="label">Descripción</label><textarea name="description" rows="2" class="input"><?= $e($t['description']) ?></textarea></div>
                            <div>
                                <label class="label">Ciclo</label>
                                <select name="billing_cycle" class="input">
                                    <?php foreach ($cycleLabels as $k=>$lbl): ?><option value="<?= $k ?>" <?= $t['billing_cycle']===$k?'selected':'' ?>><?= $lbl ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div><label class="label">Monto</label><input name="amount" type="number" step="0.01" value="<?= $e($t['amount']) ?>" class="input"></div>
                            <div><label class="label">Moneda</label><input name="currency" value="<?= $e($t['currency']) ?>" class="input"></div>
                            <div><label class="label">Horas incl.</label><input name="included_hours" type="number" step="0.25" value="<?= $e($t['included_hours']) ?>" class="input"></div>
                            <div><label class="label">Tickets incl.</label><input name="included_tickets" type="number" value="<?= (int)$t['included_tickets'] ?>" class="input"></div>
                            <div><label class="label">Tarifa hora extra</label><input name="overage_hour_rate" type="number" step="0.01" value="<?= $e($t['overage_hour_rate']) ?>" class="input"></div>
                            <div><label class="label">Impuesto (%)</label><input name="tax_pct" type="number" step="0.01" min="0" max="100" value="<?= $e($t['tax_pct']) ?>" class="input"></div>
                            <div><label class="label">Términos pago</label><input name="payment_terms" value="<?= $e($t['payment_terms']) ?>" class="input"></div>
                            <div class="md:col-span-2"><label class="label">Alcance</label><textarea name="scope" rows="2" class="input"><?= $e($t['scope']) ?></textarea></div>
                            <label class="md:col-span-2 flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" <?= (int)$t['is_active']?'checked':'' ?>> Plantilla activa</label>
                            <p class="md:col-span-2 text-[11.5px] text-ink-400">Para editar los items pre-cargados de esta plantilla, usá el formulario de creación de iguala (botón "Usar") y guardá un nuevo template — o gestioná items directamente desde el detalle de cada iguala.</p>
                            <div class="md:col-span-2 flex justify-between gap-2 pt-2" style="border-top:1px solid var(--border)">
                                <button type="button" onclick="if(confirm('Eliminar plantilla?')) document.getElementById('delete-tpl-<?= (int)$t['id'] ?>').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                                <div class="flex gap-2">
                                    <button type="button" @click="open=false" class="btn btn-soft btn-sm">Cancelar</button>
                                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button>
                                </div>
                            </div>
                        </form>
                        <form id="delete-tpl-<?= (int)$t['id'] ?>" method="POST" action="<?= $url('/t/' . $slug . '/retainers/templates/' . $t['id'] . '/delete') ?>" style="display:none">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
<?php endif; ?>
