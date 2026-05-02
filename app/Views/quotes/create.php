<?php
$slug = $tenant->slug;
$defaultTaxRate = 0;
$defaultTaxLabel = 'ITBIS';
foreach ($taxes as $t) {
    if ((int)$t['is_default'] === 1) {
        $defaultTaxRate = (float)$t['rate'];
        $defaultTaxLabel = $t['name'];
        break;
    }
}
$prefilled = $prefilledLead ?? null;
$tplItems = $templateItems ?? [];
?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/quotes') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver a cotizaciones</a>
    <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Nueva cotización</h1>
    <p class="text-[12.5px] text-ink-400">Completá los datos del cliente, agregá los items y los totales se calculan automáticamente.</p>
</div>

<?php if (!empty($templates)): ?>
<div class="card card-pad mb-4 flex items-center gap-3" style="background:linear-gradient(135deg,#f3f0ff,#fafafb);border-color:#cdbfff">
    <i class="lucide lucide-sparkles text-brand-600 text-[18px]"></i>
    <div class="flex-1 text-[12.5px]">¿Querés acelerar? Usá una <strong>plantilla</strong> con items preconfigurados.</div>
    <form method="GET" class="flex items-center gap-2">
        <select name="template_id" class="input" style="max-width:240px">
            <option value="0">— Sin plantilla —</option>
            <?php foreach ($templates as $tpl): ?>
                <option value="<?= (int)$tpl['id'] ?>" <?= ($template && (int)$template['id']===(int)$tpl['id'])?'selected':'' ?>><?= $e($tpl['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-soft btn-sm">Cargar</button>
    </form>
</div>
<?php endif; ?>

<form method="POST" action="<?= $url('/t/' . $slug . '/quotes') ?>" id="quoteForm" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <?php if ($prefilled): ?><input type="hidden" name="lead_id" value="<?= (int)$prefilled['id'] ?>"><?php endif; ?>

    <!-- LEFT: cliente + items -->
    <div class="lg:col-span-2 space-y-4">

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-user text-brand-600"></i> Cliente</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Tipo</label>
                    <select name="client_type" class="input" id="clientType">
                        <option value="company" <?= $prefilled?'':'selected' ?>>Empresa</option>
                        <option value="individual">Individual</option>
                        <option value="lead" <?= $prefilled?'selected':'' ?>>Lead (CRM)</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Empresa registrada (opcional)</label>
                    <select name="company_id" id="companyPicker" class="input">
                        <option value="0">— Cargar manualmente —</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-name="<?= $e($c['name']) ?>" data-phone="<?= $e($c['phone'] ?? '') ?>" data-address="<?= $e($c['address'] ?? '') ?>"><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre / Razón social *</label>
                    <input class="input" name="client_name" required value="<?= $e($prefilled ? trim(($prefilled['first_name']??'').' '.($prefilled['last_name']??'')) : '') ?>">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">RNC / Documento</label>
                    <input class="input" name="client_doc">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Persona de contacto</label>
                    <input class="input" name="client_contact">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email</label>
                    <input type="email" class="input" name="client_email" value="<?= $e($prefilled['email'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Teléfono</label>
                    <input class="input" name="client_phone" value="<?= $e($prefilled['phone'] ?? '') ?>">
                </div>
                <div class="sm:col-span-3">
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Dirección</label>
                    <input class="input" name="client_address" value="<?= $e($prefilled['address'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-text text-amber-600"></i> Encabezado</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Asunto / Título</label>
                    <input class="input" name="title" placeholder="Ej: Implementación CRM Q3 2026" value="<?= $e($template['name'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Emitida</label>
                    <input type="date" class="input" name="issued_at" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Validez (días)</label>
                    <input type="number" min="1" class="input" name="validity_days" value="<?= (int)($template['validity_days'] ?? $settings['validity_days']) ?>">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Introducción (aparece arriba de los items en el PDF)</label>
                    <textarea class="input" name="intro" rows="3" placeholder="Estimado cliente, agradecemos la oportunidad…"><?= $e($template['intro'] ?? $settings['intro_text']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- LINE ITEMS -->
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-3">
                <div class="font-display font-bold text-[14.5px] flex items-center gap-2"><i class="lucide lucide-list text-emerald-600"></i> Items de la cotización</div>
                <button type="button" onclick="addRow()" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Agregar línea</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-[12.5px]" id="itemsTable">
                    <thead>
                        <tr class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">
                            <th class="text-left pb-2" style="min-width:240px">Descripción</th>
                            <th class="text-right pb-2 px-1" style="width:90px">Cantidad</th>
                            <th class="pb-2 px-1" style="width:100px">Unidad</th>
                            <th class="text-right pb-2 px-1" style="width:110px">Precio</th>
                            <th class="text-right pb-2 px-1" style="width:80px">Desc. %</th>
                            <th class="text-center pb-2 px-1" style="width:50px">ITBIS</th>
                            <th class="text-right pb-2 px-1" style="width:120px">Subtotal</th>
                            <th class="pb-2" style="width:30px"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>

            <?php if (!empty($catalog)): ?>
            <div class="mt-3 pt-3 border-t border-[#ececef]">
                <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 mb-1.5">Cargar desde catálogo de servicios</div>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach (array_slice($catalog, 0, 10) as $cat): ?>
                        <button type="button" onclick='addRowFromCatalog(<?= json_encode(["title"=>$cat["name"],"description"=>$cat["description"]??""]) ?>)' class="text-[11.5px] font-semibold px-2.5 py-1 rounded-full border border-[#ececef] hover:border-brand-300 hover:bg-brand-50 transition">
                            <i class="lucide lucide-plus text-[10px]"></i> <?= $e($cat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-shield text-violet-600"></i> Términos y notas</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Términos y condiciones</label>
                    <textarea class="input" name="terms" rows="6"><?= $e($template['terms'] ?? $settings['terms_text']) ?></textarea>
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Notas internas / mensaje al cliente</label>
                    <textarea class="input" name="notes" rows="6" placeholder="Notas adicionales que aparecen junto a los datos de pago…"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: totales + opciones -->
    <div class="space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-banknote text-emerald-600"></i> Moneda y totales</div>
            <div class="grid grid-cols-2 gap-2 mb-3">
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Moneda</label>
                    <input class="input" name="currency" value="<?= $e($settings['currency']) ?>">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Símbolo</label>
                    <input class="input" name="currency_symbol" value="<?= $e($settings['currency_symbol']) ?>">
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between text-[12.5px] py-1.5">
                    <span class="text-ink-500">Subtotal</span>
                    <span class="font-mono font-bold" id="dispSubtotal">0.00</span>
                </div>

                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Descuento global (%)</label>
                    <input type="number" min="0" max="100" step="0.01" class="input" name="discount_pct" id="discountPct" value="<?= (float)$settings['default_discount_pct'] ?>" oninput="recalc()">
                    <div class="text-right text-[11.5px] text-rose-600 mt-1 font-mono" id="dispDiscount">−0.00</div>
                </div>

                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Impuesto (%)</label>
                    <select class="input mb-1" id="taxPicker" onchange="applyTaxPreset()">
                        <?php foreach ($taxes as $t): ?>
                            <option value="<?= $e($t['rate']) ?>" data-label="<?= $e($t['name']) ?>" <?= (int)$t['is_default']===1?'selected':'' ?>><?= $e($t['name']) ?> · <?= $e($t['rate']) ?>%</option>
                        <?php endforeach; ?>
                        <option value="custom">— Personalizado —</option>
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" min="0" max="100" step="0.001" class="input" name="tax_rate" id="taxRate" value="<?= $defaultTaxRate ?>" oninput="recalc()">
                        <input class="input" name="tax_label" id="taxLabel" value="<?= $e($defaultTaxLabel) ?>">
                    </div>
                    <div class="text-right text-[11.5px] text-ink-700 mt-1 font-mono" id="dispTax">0.00</div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Envío</label>
                        <input type="number" min="0" step="0.01" class="input" name="shipping_amount" id="shipping" value="0" oninput="recalc()">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Otros</label>
                        <input type="number" min="0" step="0.01" class="input" name="other_charges_amount" id="other" value="0" oninput="recalc()">
                    </div>
                </div>
                <input class="input text-[11.5px]" name="other_charges_label" placeholder="Etiqueta para 'otros'…">

                <div class="rounded-xl p-4 mt-3" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] opacity-90">TOTAL</div>
                    <div class="font-display font-extrabold text-[28px] tracking-[-0.02em] font-mono" id="dispTotal">0.00</div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary w-full"><i class="lucide lucide-save"></i> Crear cotización</button>
    </div>
</form>

<script>
const TPL_ITEMS = <?= json_encode($tplItems) ?>;

function fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:<?= (int)($settings['decimals'] ?? 2) ?>}); }

function rowTpl(idx, data = {}) {
    const taxable = data.is_taxable === 0 ? '' : 'checked';
    return `
    <tr data-idx="${idx}" class="item-row align-top border-b border-[#ececef]">
        <td class="py-1.5">
            <input class="input it-title" name="items[${idx}][title]" placeholder="Producto o servicio…" value="${escAttr(data.title || '')}" required>
            <textarea class="input it-desc mt-1 text-[11.5px]" name="items[${idx}][description]" rows="1" placeholder="Descripción opcional…">${escHtml(data.description || '')}</textarea>
        </td>
        <td class="py-1.5 px-1"><input type="number" step="0.001" min="0.001" class="input it-qty text-right" name="items[${idx}][quantity]" value="${data.quantity || 1}" oninput="recalc()"></td>
        <td class="py-1.5 px-1">
            <select class="input it-unit" name="items[${idx}][unit]">
                ${['unit','hour','license','service','project','month','custom'].map(u => `<option value="${u}" ${data.unit===u?'selected':''}>${u}</option>`).join('')}
            </select>
        </td>
        <td class="py-1.5 px-1"><input type="number" step="0.01" min="0" class="input it-price text-right" name="items[${idx}][unit_price]" value="${data.unit_price || 0}" oninput="recalc()"></td>
        <td class="py-1.5 px-1"><input type="number" step="0.01" min="0" max="100" class="input it-disc text-right" name="items[${idx}][discount_pct]" value="${data.discount_pct || 0}" oninput="recalc()"></td>
        <td class="py-1.5 px-1 text-center"><input type="checkbox" name="items[${idx}][is_taxable]" value="1" class="rounded it-tax" ${taxable} onchange="recalc()"></td>
        <td class="py-1.5 px-1 text-right font-mono font-bold it-sub">0.00</td>
        <td class="py-1.5"><button type="button" onclick="this.closest('tr').remove(); recalc();" class="text-rose-600"><i class="lucide lucide-trash-2 text-[14px]"></i></button></td>
    </tr>`;
}

function escAttr(s) { return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;'); }
function escHtml(s) { return String(s).replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

let _idx = 0;
function addRow(data = {}) {
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', rowTpl(_idx++, data));
    if (window.lucide) window.lucide.createIcons();
    recalc();
}
function addRowFromCatalog(data) { addRow(data); }

function applyTaxPreset() {
    const p = document.getElementById('taxPicker');
    const opt = p.options[p.selectedIndex];
    if (opt.value === 'custom') return;
    document.getElementById('taxRate').value = opt.value;
    document.getElementById('taxLabel').value = opt.dataset.label || 'ITBIS';
    recalc();
}

function recalc() {
    let subtotal = 0, taxableBase = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.it-qty').value) || 0;
        const price = parseFloat(row.querySelector('.it-price').value) || 0;
        const disc = parseFloat(row.querySelector('.it-disc').value) || 0;
        const isTax = row.querySelector('.it-tax').checked;
        const sub = qty * price * (1 - disc / 100);
        row.querySelector('.it-sub').textContent = fmt(sub);
        subtotal += sub;
        if (isTax) taxableBase += sub;
    });
    const discountPct = parseFloat(document.getElementById('discountPct').value) || 0;
    const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping').value) || 0;
    const other = parseFloat(document.getElementById('other').value) || 0;

    const discountAmount = subtotal * (discountPct / 100);
    const taxableAfter = taxableBase > 0 ? taxableBase - (taxableBase / (subtotal || 1)) * discountAmount : 0;
    const taxAmount = taxableAfter * (taxRate / 100);
    const total = subtotal - discountAmount + taxAmount + shipping + other;

    document.getElementById('dispSubtotal').textContent = fmt(subtotal);
    document.getElementById('dispDiscount').textContent = '−' + fmt(discountAmount);
    document.getElementById('dispTax').textContent = fmt(taxAmount);
    document.getElementById('dispTotal').textContent = fmt(total);
}

document.getElementById('companyPicker').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt.value || opt.value === '0') return;
    document.querySelector('[name="client_name"]').value = opt.dataset.name || '';
    document.querySelector('[name="client_phone"]').value = opt.dataset.phone || '';
    document.querySelector('[name="client_address"]').value = opt.dataset.address || '';
});

// Cargar items iniciales (prefilled o template o una fila vacía)
(function init() {
    if (TPL_ITEMS && TPL_ITEMS.length) {
        TPL_ITEMS.forEach(it => addRow({
            title: it.title, description: it.description,
            quantity: it.quantity, unit: it.unit,
            unit_price: it.unit_price, discount_pct: it.discount_pct,
            is_taxable: parseInt(it.is_taxable)
        }));
    } else {
        addRow();
    }
})();
</script>
