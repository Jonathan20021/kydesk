<?php
$primary = $settings['primary_color'] ?: '#7c5cff';
$accent  = $settings['accent_color'] ?: '#16a34a';
$decimals = (int)($settings['decimals'] ?? 2);
$sym = $settings['currency_symbol'] ?: ($quote['currency_symbol'] ?: 'RD$');
$bizName = $settings['business_name'] ?: $tenant->name;
$logo = $settings['logo_url'] ?? '';
$basePath = \App\Core\Application::get()->config['app']['url'] ?? '';
if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') === 0) {
    $logo = rtrim($basePath, '/') . $logo;
}

$canRespond = in_array($quote['status'], ['sent','viewed','revised'], true);
$accepted = $quote['status'] === 'accepted';
$rejected = $quote['status'] === 'rejected';

function pubFmt($v, $d = 2) { return number_format((float)$v, $d, '.', ','); }
function pubNlbr($t) { return nl2br(htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8')); }

$flash = [
    'success' => \App\Core\Application::get()->session->flash('success'),
    'error'   => \App\Core\Application::get()->session->flash('error'),
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cotización <?= htmlspecialchars($quote['code']) ?> · <?= htmlspecialchars($bizName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { theme: { extend: { fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] } } } }</script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<style>
    body { background: #fafafb; font-family: 'Inter', sans-serif; }
    .brand-bar { background: linear-gradient(135deg, <?= $primary ?>, <?= $primary ?>cc); }
    .pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100% { opacity:1 } 50% { opacity:.5 } }
</style>
</head>
<body>

<div class="brand-bar text-white py-4 px-6">
    <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <?php if ($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($bizName) ?>" class="h-9 max-w-[120px] bg-white rounded-lg p-1">
            <?php else: ?>
                <div class="w-9 h-9 grid place-items-center bg-white rounded-lg font-bold text-lg" style="color:<?= $primary ?>"><?= strtoupper(substr($bizName, 0, 1)) ?></div>
            <?php endif; ?>
            <div>
                <div class="font-display font-extrabold text-[15px] leading-tight"><?= htmlspecialchars($bizName) ?></div>
                <div class="text-[10.5px] opacity-80 font-mono"><?= htmlspecialchars($quote['code']) ?></div>
            </div>
        </div>
        <div class="text-right text-[11px] opacity-80">
            <?php if ($settings['business_email']): ?><div><?= htmlspecialchars($settings['business_email']) ?></div><?php endif; ?>
            <?php if ($settings['business_phone']): ?><div><?= htmlspecialchars($settings['business_phone']) ?></div><?php endif; ?>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <?php if ($flash['success']): ?>
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4"></i> <?= htmlspecialchars($flash['success']) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash['error']): ?>
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4"></i> <?= htmlspecialchars($flash['error']) ?>
        </div>
    <?php endif; ?>

    <!-- HERO -->
    <div class="bg-white rounded-3xl border border-[#ececef] overflow-hidden shadow-sm">
        <div class="p-6 sm:p-8 border-b border-[#ececef]">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.18em]" style="color:<?= $primary ?>">COTIZACIÓN</div>
                    <h1 class="font-display font-extrabold text-[28px] sm:text-[32px] tracking-[-0.025em] mt-1 leading-tight"><?= htmlspecialchars($quote['title'] ?: ('Cotización ' . $quote['code'])) ?></h1>
                    <div class="text-[12.5px] text-slate-500 mt-2">Para <strong class="text-slate-900"><?= htmlspecialchars($quote['client_name']) ?></strong></div>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-slate-400">Total</div>
                    <div class="font-display font-extrabold text-[32px] tracking-[-0.02em]" style="color:<?= $accent ?>"><?= htmlspecialchars($sym) ?> <?= pubFmt($quote['total'], $decimals) ?></div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        Válida hasta <strong><?= htmlspecialchars(date('d M Y', strtotime($quote['valid_until']))) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($accepted): ?>
            <div class="p-4 sm:p-6 bg-emerald-50 border-b border-emerald-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center bg-emerald-600 text-white"><i data-lucide="check-circle-2"></i></div>
                <div class="flex-1">
                    <div class="font-display font-bold text-[14px] text-emerald-900">¡Cotización aceptada!</div>
                    <div class="text-[12px] text-emerald-700">Aceptada por <strong><?= htmlspecialchars($quote['accepted_by_name'] ?? '') ?></strong> el <?= htmlspecialchars(date('d M Y, H:i', strtotime($quote['accepted_at']))) ?>. El equipo se contactará para los próximos pasos.</div>
                </div>
            </div>
        <?php elseif ($rejected): ?>
            <div class="p-4 sm:p-6 bg-rose-50 border-b border-rose-200">
                <div class="font-display font-bold text-[14px] text-rose-900">Esta cotización fue rechazada.</div>
                <?php if (!empty($quote['rejected_reason'])): ?>
                    <div class="text-[12px] text-rose-700 mt-1">Motivo: <?= htmlspecialchars($quote['rejected_reason']) ?></div>
                <?php endif; ?>
            </div>
        <?php elseif ($quote['status'] === 'expired'): ?>
            <div class="p-4 sm:p-6 bg-amber-50 border-b border-amber-200">
                <div class="font-display font-bold text-[14px] text-amber-900">Esta cotización está expirada.</div>
                <div class="text-[12px] text-amber-700 mt-1">Contactá al equipo para solicitar una versión actualizada.</div>
            </div>
        <?php endif; ?>

        <!-- INTRO -->
        <?php if (!empty($quote['intro'])): ?>
            <div class="p-6 sm:p-8 text-[14px] text-slate-600 leading-relaxed border-b border-[#ececef]">
                <?= pubNlbr($quote['intro']) ?>
            </div>
        <?php endif; ?>

        <!-- ITEMS -->
        <div class="p-4 sm:p-8">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-slate-400 mb-3">DETALLE</div>
            <div class="space-y-2">
                <?php foreach ($items as $i => $it):
                    $unitLabel = $it['unit_label'] ?: $it['unit'];
                ?>
                    <div class="border border-[#ececef] rounded-xl p-4 hover:border-[#cdbfff] transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10.5px] font-mono font-bold text-slate-400">#<?= $i + 1 ?></span>
                                    <div class="font-display font-bold text-[14px]"><?= htmlspecialchars($it['title']) ?></div>
                                </div>
                                <?php if (!empty($it['description'])): ?>
                                    <div class="text-[12.5px] text-slate-500 leading-relaxed mt-1"><?= pubNlbr($it['description']) ?></div>
                                <?php endif; ?>
                                <div class="text-[11.5px] text-slate-400 mt-2 font-mono">
                                    <?= pubFmt($it['quantity'], (float)$it['quantity'] == (int)$it['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($unitLabel) ?>
                                    × <?= htmlspecialchars($sym) ?> <?= pubFmt($it['unit_price'], $decimals) ?>
                                    <?php if ((float)$it['discount_pct'] > 0): ?>
                                        <span class="text-rose-600">· − <?= pubFmt($it['discount_pct'], 1) ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-extrabold text-[16px]"><?= htmlspecialchars($sym) ?> <?= pubFmt($it['line_subtotal'], $decimals) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TOTALS -->
        <div class="p-6 sm:p-8 bg-[#fafafb] border-t border-[#ececef]">
            <div class="max-w-md ml-auto space-y-2 text-[13.5px]">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-mono font-bold"><?= htmlspecialchars($sym) ?> <?= pubFmt($quote['subtotal'], $decimals) ?></span></div>
                <?php if ((float)$quote['discount_amount'] > 0): ?>
                    <div class="flex justify-between text-rose-600"><span>Descuento (<?= pubFmt($quote['discount_pct'], 1) ?>%)</span><span class="font-mono">− <?= htmlspecialchars($sym) ?> <?= pubFmt($quote['discount_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <?php if ((float)$quote['tax_rate'] > 0): ?>
                    <div class="flex justify-between"><span class="text-slate-500"><?= htmlspecialchars($quote['tax_label']) ?> (<?= pubFmt($quote['tax_rate'], 1) ?>%)</span><span class="font-mono"><?= htmlspecialchars($sym) ?> <?= pubFmt($quote['tax_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <?php if ((float)$quote['shipping_amount'] > 0): ?>
                    <div class="flex justify-between"><span class="text-slate-500">Envío</span><span class="font-mono"><?= htmlspecialchars($sym) ?> <?= pubFmt($quote['shipping_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <?php if ((float)$quote['other_charges_amount'] > 0): ?>
                    <div class="flex justify-between"><span class="text-slate-500"><?= htmlspecialchars($quote['other_charges_label'] ?: 'Otros cargos') ?></span><span class="font-mono"><?= htmlspecialchars($sym) ?> <?= pubFmt($quote['other_charges_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <div class="border-t pt-2 flex justify-between text-[18px]"><span class="font-display font-bold">TOTAL</span><span class="font-mono font-extrabold" style="color:<?= $accent ?>"><?= htmlspecialchars($sym) ?> <?= pubFmt($quote['total'], $decimals) ?></span></div>
            </div>
        </div>

        <?php if (!empty($quote['terms'])): ?>
        <div class="p-6 sm:p-8 border-t border-[#ececef]">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-slate-400 mb-2">TÉRMINOS Y CONDICIONES</div>
            <div class="text-[12.5px] text-slate-600 leading-relaxed"><?= pubNlbr($quote['terms']) ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($settings['bank_info'])): ?>
        <div class="p-6 sm:p-8 border-t border-[#ececef]" style="background:#fafafb">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] mb-2" style="color:<?= $accent ?>">DATOS DE PAGO</div>
            <div class="text-[12.5px] text-slate-600 leading-relaxed font-mono"><?= pubNlbr($settings['bank_info']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ACTIONS -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <a href="<?= htmlspecialchars($basePath . '/q/' . $quote['public_token'] . '/pdf') ?>" class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white border-2 border-[#ececef] hover:border-[#cdbfff] font-semibold text-[13.5px] transition">
            <i data-lucide="download" class="w-4 h-4"></i> Descargar PDF
        </a>
        <?php if ($canRespond): ?>
        <button onclick="document.getElementById('acceptModal').classList.remove('hidden')" class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white font-semibold text-[13.5px] shadow-lg shadow-emerald-200 hover:scale-[1.02] transition" style="background:linear-gradient(135deg,<?= $accent ?>,#10b981)">
            <i data-lucide="check-circle-2" class="w-4 h-4"></i> Aceptar cotización
        </button>
        <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white border-2 border-rose-200 text-rose-700 hover:bg-rose-50 font-semibold text-[13.5px] transition">
            <i data-lucide="x-circle" class="w-4 h-4"></i> Rechazar
        </button>
        <?php endif; ?>
    </div>

    <div class="mt-8 text-center text-[11px] text-slate-400">
        <?= htmlspecialchars($settings['footer_text'] ?: 'Cotización generada por ' . $bizName) ?>
    </div>
</div>

<!-- Accept modal -->
<div id="acceptModal" class="hidden fixed inset-0 z-50 grid place-items-center p-4" style="background:rgba(15,13,24,.7);backdrop-filter:blur(6px)">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-extrabold text-[18px]">Aceptar cotización</div>
            <button onclick="document.getElementById('acceptModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-900"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <p class="text-[13px] text-slate-500 mb-4">Para registrar la aceptación necesitamos tu nombre y email. El equipo de <strong><?= htmlspecialchars($bizName) ?></strong> recibirá el aviso al instante.</p>
        <form method="POST" action="<?= htmlspecialchars($basePath . '/q/' . $quote['public_token'] . '/accept') ?>" class="space-y-3">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400 block mb-1">Nombre completo</label>
                <input required class="w-full px-3 py-2 border border-slate-200 rounded-lg" name="name" value="<?= htmlspecialchars($quote['client_contact'] ?: $quote['client_name']) ?>">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400 block mb-1">Email</label>
                <input required type="email" class="w-full px-3 py-2 border border-slate-200 rounded-lg" name="email" value="<?= htmlspecialchars($quote['client_email'] ?? '') ?>">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('acceptModal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-slate-200 text-[13px] font-semibold">Cancelar</button>
                <button class="px-4 py-2 rounded-lg text-white text-[13px] font-semibold" style="background:linear-gradient(135deg,<?= $accent ?>,#10b981)">Confirmar aceptación</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 grid place-items-center p-4" style="background:rgba(15,13,24,.7);backdrop-filter:blur(6px)">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-extrabold text-[18px]">Rechazar cotización</div>
            <button onclick="document.getElementById('rejectModal').classList.add('hidden')" class="text-slate-400"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" action="<?= htmlspecialchars($basePath . '/q/' . $quote['public_token'] . '/reject') ?>" class="space-y-3">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400 block mb-1">Motivo (opcional)</label>
                <textarea class="w-full px-3 py-2 border border-slate-200 rounded-lg" name="reason" rows="3" placeholder="Precio, alcance, plazos…"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-slate-200 text-[13px] font-semibold">Cancelar</button>
                <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-[13px] font-semibold">Confirmar rechazo</button>
            </div>
        </form>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
