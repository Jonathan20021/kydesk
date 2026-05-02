<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$quoteId = (int)$quote['id'];
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
[$sl, $sCol, $sIc] = $statusMap[$quote['status']] ?? ['—', '#6b7280', 'help-circle'];
$readonly = in_array($quote['status'], ['accepted','rejected','converted'], true);
$eventIcons = [
    'created' => 'plus-circle', 'updated' => 'pencil', 'sent' => 'send',
    'viewed' => 'eye', 'accepted' => 'check-circle-2', 'rejected' => 'x-circle',
    'expired' => 'clock', 'revised' => 'refresh-cw',
    'pdf_downloaded' => 'download', 'converted' => 'shopping-bag',
];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <a href="<?= $url('/t/' . $slug . '/quotes') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver a cotizaciones</a>
        <div class="flex items-center gap-2 mb-1">
            <span class="font-mono text-[12px] text-ink-500"><?= $e($quote['code']) ?></span>
            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $sCol ?>1a;color:<?= $sCol ?>"><i class="lucide lucide-<?= $sIc ?> text-[10px]"></i> <?= $e($sl) ?></span>
        </div>
        <h1 class="font-display font-extrabold text-[24px] tracking-[-0.025em]"><?= $e($quote['title'] ?: $quote['client_name']) ?></h1>
        <p class="text-[12.5px] text-ink-400">Para <strong><?= $e($quote['client_name']) ?></strong> · Total <strong class="font-mono text-emerald-700"><?= $e($quote['currency_symbol']) ?> <?= number_format((float)$quote['total'], 2) ?></strong></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $url('/t/' . $slug . '/quotes/' . $quoteId . '/pdf') ?>" target="_blank" class="btn btn-soft btn-sm"><i class="lucide lucide-file-text"></i> PDF</a>
        <button type="button" onclick="navigator.clipboard.writeText('<?= $e($publicUrl) ?>'); this.querySelector('span').textContent='Copiado ✓'" class="btn btn-soft btn-sm"><i class="lucide lucide-link"></i> <span>Copiar link público</span></button>
        <?php if (!$readonly && $auth->can('quotes.send')): ?>
            <button type="button" onclick="document.getElementById('sendModal').classList.remove('hidden')" class="btn btn-sm" style="background:linear-gradient(135deg,#3b82f6,#0ea5e9);color:white"><i class="lucide lucide-send"></i> Enviar al cliente</button>
        <?php endif; ?>
        <?php if ($auth->can('quotes.create')): ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/' . $quoteId . '/duplicate') ?>" class="inline">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-soft btn-sm"><i class="lucide lucide-copy"></i> Duplicar</button>
            </form>
        <?php endif; ?>
        <?php if ($auth->can('quotes.delete')): ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/' . $quoteId . '/delete') ?>" onsubmit="return confirm('¿Eliminar cotización? Esta acción no se puede deshacer.')" class="inline">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-soft btn-sm" style="color:#dc2626"><i class="lucide lucide-trash-2"></i></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($quote['status'] === 'accepted'): ?>
<div class="card card-pad mb-4 flex items-center gap-3" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border-color:#a7f3d0">
    <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:#16a34a;color:white"><i class="lucide lucide-check-circle-2"></i></div>
    <div class="flex-1">
        <div class="font-display font-bold text-[13.5px]">Cotización aceptada</div>
        <div class="text-[11.5px] text-ink-500">
            <?php if (!empty($quote['accepted_by_name'])): ?>Por <strong><?= $e($quote['accepted_by_name']) ?></strong> &lt;<?= $e($quote['accepted_by_email']) ?>&gt; · <?php endif; ?>
            <?= $e(date('d M Y, H:i', strtotime($quote['accepted_at']))) ?>
        </div>
    </div>
</div>
<?php elseif ($quote['status'] === 'rejected'): ?>
<div class="card card-pad mb-4" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border-color:#fecaca">
    <div class="font-display font-bold text-[13.5px] text-rose-700">Cotización rechazada</div>
    <?php if (!empty($quote['rejected_reason'])): ?>
        <div class="text-[12px] text-rose-700 mt-1">Motivo: <?= $e($quote['rejected_reason']) ?></div>
    <?php endif; ?>
</div>
<?php elseif ($quote['status'] === 'expired'): ?>
<div class="card card-pad mb-4 flex items-center gap-3" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fde68a">
    <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:#f59e0b;color:white"><i class="lucide lucide-clock"></i></div>
    <div class="flex-1">
        <div class="font-display font-bold text-[13.5px]">Cotización expirada</div>
        <div class="text-[11.5px] text-ink-500">Vencida el <?= $e(date('d M Y', strtotime($quote['valid_until']))) ?>. Editala para extender la validez (cambiará a "Revisada").</div>
    </div>
</div>
<?php endif; ?>

<form method="POST" action="<?= $url('/t/' . $slug . '/quotes/' . $quoteId) ?>" id="quoteForm" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-user text-brand-600"></i> Cliente</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Tipo</label>
                    <select name="client_type" class="input">
                        <option value="company" <?= $quote['client_type']==='company'?'selected':'' ?>>Empresa</option>
                        <option value="individual" <?= $quote['client_type']==='individual'?'selected':'' ?>>Individual</option>
                        <option value="lead" <?= $quote['client_type']==='lead'?'selected':'' ?>>Lead (CRM)</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Empresa registrada</label>
                    <select name="company_id" class="input">
                        <option value="0">— Manual —</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$quote['company_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre / Razón social</label><input class="input" name="client_name" value="<?= $e($quote['client_name']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">RNC / Documento</label><input class="input" name="client_doc" value="<?= $e($quote['client_doc'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Persona de contacto</label><input class="input" name="client_contact" value="<?= $e($quote['client_contact'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email</label><input type="email" class="input" name="client_email" value="<?= $e($quote['client_email'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Teléfono</label><input class="input" name="client_phone" value="<?= $e($quote['client_phone'] ?? '') ?>"></div>
                <div class="sm:col-span-3"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Dirección</label><input class="input" name="client_address" value="<?= $e($quote['client_address'] ?? '') ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-text text-amber-600"></i> Encabezado</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Título</label><input class="input" name="title" value="<?= $e($quote['title'] ?? '') ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Emitida</label><input type="date" class="input" name="issued_at" value="<?= $e($quote['issued_at']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Válida hasta</label><input type="date" class="input" name="valid_until" value="<?= $e($quote['valid_until']) ?>"></div>
                <div class="sm:col-span-2"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Introducción</label><textarea class="input" name="intro" rows="3"><?= $e($quote['intro'] ?? '') ?></textarea></div>
            </div>
        </div>

        <!-- ITEMS -->
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-3">
                <div class="font-display font-bold text-[14.5px] flex items-center gap-2"><i class="lucide lucide-list text-emerald-600"></i> Items (<?= count($items) ?>)</div>
                <button type="button" onclick="addRow()" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Agregar línea</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[12.5px]">
                    <thead>
                        <tr class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">
                            <th class="text-left pb-2" style="min-width:240px">Descripción</th>
                            <th class="text-right pb-2 px-1" style="width:90px">Cantidad</th>
                            <th class="pb-2 px-1" style="width:100px">Unidad</th>
                            <th class="text-right pb-2 px-1" style="width:110px">Precio</th>
                            <th class="text-right pb-2 px-1" style="width:80px">Desc. %</th>
                            <th class="text-center pb-2 px-1" style="width:50px">Tax</th>
                            <th class="text-right pb-2 px-1" style="width:120px">Subtotal</th>
                            <th class="pb-2" style="width:30px"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-shield text-violet-600"></i> Términos y notas</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Términos y condiciones</label><textarea class="input" name="terms" rows="6"><?= $e($quote['terms'] ?? '') ?></textarea></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Notas</label><textarea class="input" name="notes" rows="6"><?= $e($quote['notes'] ?? '') ?></textarea></div>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-banknote text-emerald-600"></i> Totales</div>
            <div class="grid grid-cols-2 gap-2 mb-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Moneda</label><input class="input" name="currency" value="<?= $e($quote['currency']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Símbolo</label><input class="input" name="currency_symbol" value="<?= $e($quote['currency_symbol']) ?>"></div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-[12.5px] py-1.5"><span class="text-ink-500">Subtotal</span><span class="font-mono font-bold" id="dispSubtotal">0.00</span></div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Descuento global (%)</label>
                    <input type="number" min="0" max="100" step="0.01" class="input" name="discount_pct" id="discountPct" value="<?= (float)$quote['discount_pct'] ?>" oninput="recalc()">
                    <div class="text-right text-[11.5px] text-rose-600 mt-1 font-mono" id="dispDiscount">−0.00</div>
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Impuesto (%)</label>
                    <select class="input mb-1" id="taxPicker" onchange="applyTaxPreset()">
                        <?php foreach ($taxes as $t): ?>
                            <option value="<?= $e($t['rate']) ?>" data-label="<?= $e($t['name']) ?>" <?= (float)$t['rate']===(float)$quote['tax_rate']?'selected':'' ?>><?= $e($t['name']) ?> · <?= $e($t['rate']) ?>%</option>
                        <?php endforeach; ?>
                        <option value="custom">— Personalizado —</option>
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" min="0" max="100" step="0.001" class="input" name="tax_rate" id="taxRate" value="<?= $e($quote['tax_rate']) ?>" oninput="recalc()">
                        <input class="input" name="tax_label" id="taxLabel" value="<?= $e($quote['tax_label']) ?>">
                    </div>
                    <div class="text-right text-[11.5px] text-ink-700 mt-1 font-mono" id="dispTax">0.00</div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Envío</label><input type="number" min="0" step="0.01" class="input" name="shipping_amount" id="shipping" value="<?= $e($quote['shipping_amount']) ?>" oninput="recalc()"></div>
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Otros</label><input type="number" min="0" step="0.01" class="input" name="other_charges_amount" id="other" value="<?= $e($quote['other_charges_amount']) ?>" oninput="recalc()"></div>
                </div>
                <input class="input text-[11.5px]" name="other_charges_label" placeholder="Etiqueta para 'otros'…" value="<?= $e($quote['other_charges_label'] ?? '') ?>">
                <div class="rounded-xl p-4 mt-3" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] opacity-90">TOTAL</div>
                    <div class="font-display font-extrabold text-[28px] tracking-[-0.02em] font-mono" id="dispTotal">0.00</div>
                </div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-2 flex items-center gap-2"><i class="lucide lucide-user-cog text-sky-600"></i> Owner</div>
            <select class="input" name="owner_id">
                <option value="0">— Sin asignar —</option>
                <?php foreach ($owners as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= (int)$quote['owner_id']===(int)$o['id']?'selected':'' ?>><?= $e($o['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn btn-primary w-full" <?= $readonly?'disabled':'' ?>><i class="lucide lucide-save"></i> Guardar cambios</button>
        <?php if ($readonly): ?>
            <p class="text-[11px] text-ink-400 text-center">Cotización en estado "<?= $sl ?>" · solo lectura.</p>
        <?php endif; ?>

        <!-- Manual status change -->
        <?php if ($auth->can('quotes.edit') && !in_array($quote['status'], ['accepted','rejected','converted'], true)): ?>
        <div class="card card-pad">
            <div class="font-display font-bold text-[13.5px] mb-2 flex items-center gap-2"><i class="lucide lucide-shuffle text-violet-600"></i> Cambio manual de estado</div>
            <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/' . $quoteId . '/status') ?>" class="space-y-2">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <select class="input" name="status">
                    <option value="accepted">Marcar aceptada</option>
                    <option value="rejected">Marcar rechazada</option>
                </select>
                <input class="input" name="accepted_by_name" placeholder="Nombre (si la aceptaron por otro canal)">
                <input type="email" class="input" name="accepted_by_email" placeholder="Email">
                <input class="input" name="rejected_reason" placeholder="Motivo del rechazo (opcional)">
                <button class="btn btn-soft btn-sm w-full"><i class="lucide lucide-check"></i> Aplicar cambio</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Activity timeline -->
        <div class="card card-pad">
            <div class="font-display font-bold text-[13.5px] mb-2 flex items-center gap-2"><i class="lucide lucide-activity text-rose-600"></i> Actividad (<?= count($events) ?>)</div>
            <?php if (empty($events)): ?>
                <div class="text-[12px] text-ink-400 py-2 text-center">Sin actividad.</div>
            <?php else: ?>
                <div class="space-y-1.5 max-h-72 overflow-y-auto">
                    <?php foreach ($events as $ev):
                        $ic = $eventIcons[$ev['event_type']] ?? 'circle';
                    ?>
                        <div class="flex gap-2 text-[12px] border-l-2 pl-2 py-1" style="border-color:<?= $statusMap[$ev['event_type']][1] ?? '#ececef' ?>">
                            <i class="lucide lucide-<?= $ic ?> text-[12px] mt-0.5 text-ink-400"></i>
                            <div class="flex-1 min-w-0">
                                <div class="text-[11.5px] font-semibold capitalize"><?= $e(str_replace('_', ' ', $ev['event_type'])) ?></div>
                                <div class="text-[10.5px] text-ink-400"><?= $e($ev['actor_name'] ?? ucfirst($ev['actor_type'])) ?> · <?= $e(date('d M, H:i', strtotime($ev['created_at']))) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Send modal -->
<div id="sendModal" class="hidden fixed inset-0 z-50 grid place-items-center p-4" style="background:rgba(15,13,24,.6);backdrop-filter:blur(4px)">
    <div class="card card-pad w-full max-w-md" style="background:white">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[16px] flex items-center gap-2"><i class="lucide lucide-send text-sky-600"></i> Enviar cotización</div>
            <button type="button" onclick="document.getElementById('sendModal').classList.add('hidden')" class="text-ink-500"><i class="lucide lucide-x"></i></button>
        </div>
        <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/' . $quoteId . '/send') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <p class="text-[12.5px] text-ink-500">Le enviamos al cliente un email con un link único para revisar, descargar el PDF y aceptar la cotización online.</p>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email del destinatario</label>
                <input type="email" required class="input" name="email" value="<?= $e($quote['client_email'] ?? '') ?>">
            </div>
            <div class="rounded-lg p-2 text-[11px]" style="background:#f3f0ff;color:#5a3aff">
                <i class="lucide lucide-link text-[11px]"></i> Link público: <code class="text-[10.5px] break-all"><?= $e($publicUrl) ?></code>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('sendModal').classList.add('hidden')" class="btn btn-soft btn-sm">Cancelar</button>
                <button class="btn btn-primary btn-sm" style="background:linear-gradient(135deg,#3b82f6,#0ea5e9)"><i class="lucide lucide-send"></i> Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
const ITEMS_INIT = <?= json_encode($items) ?>;
const DECIMALS = <?= (int)($settings['decimals'] ?? 2) ?>;

function fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:DECIMALS}); }
function escAttr(s) { return String(s ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }
function escHtml(s) { return String(s ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

let _idx = 0;
function rowTpl(idx, d = {}) {
    const taxable = parseInt(d.is_taxable) === 0 ? '' : 'checked';
    return `
    <tr data-idx="${idx}" class="item-row align-top border-b border-[#ececef]">
        <td class="py-1.5">
            <input class="input it-title" name="items[${idx}][title]" value="${escAttr(d.title || '')}" required>
            <textarea class="input it-desc mt-1 text-[11.5px]" name="items[${idx}][description]" rows="1">${escHtml(d.description || '')}</textarea>
        </td>
        <td class="py-1.5 px-1"><input type="number" step="0.001" min="0.001" class="input it-qty text-right" name="items[${idx}][quantity]" value="${d.quantity || 1}" oninput="recalc()"></td>
        <td class="py-1.5 px-1">
            <select class="input it-unit" name="items[${idx}][unit]">
                ${['unit','hour','license','service','project','month','custom'].map(u => `<option value="${u}" ${d.unit===u?'selected':''}>${u}</option>`).join('')}
            </select>
        </td>
        <td class="py-1.5 px-1"><input type="number" step="0.01" min="0" class="input it-price text-right" name="items[${idx}][unit_price]" value="${d.unit_price || 0}" oninput="recalc()"></td>
        <td class="py-1.5 px-1"><input type="number" step="0.01" min="0" max="100" class="input it-disc text-right" name="items[${idx}][discount_pct]" value="${d.discount_pct || 0}" oninput="recalc()"></td>
        <td class="py-1.5 px-1 text-center"><input type="checkbox" name="items[${idx}][is_taxable]" value="1" class="rounded it-tax" ${taxable} onchange="recalc()"></td>
        <td class="py-1.5 px-1 text-right font-mono font-bold it-sub">0.00</td>
        <td class="py-1.5"><button type="button" onclick="this.closest('tr').remove(); recalc();" class="text-rose-600"><i class="lucide lucide-trash-2 text-[14px]"></i></button></td>
    </tr>`;
}
function addRow(d = {}) {
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', rowTpl(_idx++, d));
    if (window.lucide) window.lucide.createIcons();
    recalc();
}
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
(function init() {
    if (ITEMS_INIT && ITEMS_INIT.length) ITEMS_INIT.forEach(it => addRow(it));
    else addRow();
})();
</script>
