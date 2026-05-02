<?php
/**
 * Plantilla PDF corporativa de cotización (dompdf 3.x)
 * Diseño minimalista profesional inspirado en SAP / Zoho / FreshBooks.
 *
 * Variables: $quote, $items, $settings, $tenant
 */
$primary = $settings['primary_color'] ?: '#1e3a8a';
$accent  = $settings['accent_color'] ?: '#0f172a';
$decimals = (int)($settings['decimals'] ?? 2);
$sym = $settings['currency_symbol'] ?: ($quote['currency_symbol'] ?: 'RD$');

$bizName    = $settings['business_name'] ?: $tenant->name;
$bizDoc     = $settings['business_doc'] ?? '';
$bizAddress = $settings['business_address'] ?? '';
$bizPhone   = $settings['business_phone'] ?? '';
$bizEmail   = $settings['business_email'] ?? '';
$bizWeb     = $settings['business_website'] ?? '';
$logo       = $settings['logo_url'] ?? '';

if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') === 0) {
    $logoFs = BASE_PATH . $logo;
    if (is_file($logoFs)) $logo = $logoFs;
}

$statusLabels = [
    'draft' => 'Borrador', 'sent' => 'Enviada', 'viewed' => 'Vista',
    'accepted' => 'Aceptada', 'rejected' => 'Rechazada', 'expired' => 'Expirada',
    'revised' => 'Revisada', 'converted' => 'Convertida',
];
$statusColors = [
    'draft' => '#64748b', 'sent' => '#1d4ed8', 'viewed' => '#0891b2',
    'accepted' => '#15803d', 'rejected' => '#b91c1c', 'expired' => '#b45309',
    'revised' => '#6d28d9', 'converted' => '#15803d',
];
$stColor = $statusColors[$quote['status']] ?? '#64748b';
$stLabel = $statusLabels[$quote['status']] ?? ucfirst($quote['status']);

function fmt($v, $d = 2) { return number_format((float)$v, $d, '.', ','); }
function nlbr($t) { return nl2br(htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8')); }
function ee($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$codeJson = json_encode((string)$quote['code']);
$footerLeftJson = json_encode((string)($settings['footer_text'] ?: $bizName));

$issuedDate = $quote['issued_at'] ? date('d \d\e F \d\e Y', strtotime((string)$quote['issued_at'])) : '';
$validDate = $quote['valid_until'] ? date('d \d\e F \d\e Y', strtotime((string)$quote['valid_until'])) : '';
// Convert month names to spanish (PHP locale-independent)
$months = ['January'=>'enero','February'=>'febrero','March'=>'marzo','April'=>'abril','May'=>'mayo','June'=>'junio','July'=>'julio','August'=>'agosto','September'=>'septiembre','October'=>'octubre','November'=>'noviembre','December'=>'diciembre'];
$issuedDate = strtr($issuedDate, $months);
$validDate = strtr($validDate, $months);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotización <?= ee($quote['code']) ?></title>
<style>
    @page { margin: 18mm 16mm 26mm 16mm; }

    * { margin: 0; padding: 0; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #0f172a;
        font-size: 9.5pt;
        line-height: 1.55;
    }

    /* ─── HEADER (logo + título) ───────────────────────────── */
    .header { width: 100%; margin-bottom: 6mm; }
    .header td { vertical-align: top; padding: 0; }

    .logo-cell { width: 50%; }
    .logo-cell img { max-height: 55px; max-width: 200px; }
    .logo-cell .ini {
        display: inline-block;
        width: 52px; height: 52px;
        background: <?= $primary ?>;
        color: #ffffff;
        font-size: 24pt;
        font-weight: bold;
        text-align: center;
        line-height: 52px;
    }

    .title-cell { width: 50%; text-align: right; }
    .doc-title {
        font-size: 28pt;
        font-weight: bold;
        color: #0f172a;
        letter-spacing: -0.8pt;
        line-height: 1;
        text-transform: uppercase;
    }
    .doc-num {
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 11pt;
        color: #64748b;
        margin-top: 6px;
        letter-spacing: 0.4pt;
    }

    /* ─── DIVISOR PRINCIPAL ─────────────────────────────── */
    .divider-main {
        width: 100%;
        height: 3px;
        background: <?= $primary ?>;
        margin-bottom: 8mm;
    }

    /* ─── BLOQUE DE INFO (de / para / quote info) ────── */
    .info-block { width: 100%; margin-bottom: 8mm; }
    .info-block > tbody > tr > td {
        vertical-align: top;
        padding: 0 14px 0 0;
    }
    .info-block > tbody > tr > td:last-child { padding-right: 0; }

    .info-h {
        font-size: 7.5pt;
        letter-spacing: 1.8pt;
        text-transform: uppercase;
        color: <?= $primary ?>;
        font-weight: bold;
        margin-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 3px;
    }
    .info-name {
        font-size: 11pt;
        font-weight: bold;
        color: #0f172a;
        line-height: 1.3;
        margin-bottom: 4px;
    }
    .info-body {
        font-size: 9pt;
        color: #475569;
        line-height: 1.65;
    }
    .info-body strong { color: #0f172a; font-weight: bold; }

    /* ─── TABLA DE DETALLES (quote#/date/etc) ──────── */
    .quote-details {
        width: 100%;
        border-collapse: collapse;
    }
    .quote-details td {
        padding: 7px 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 9pt;
    }
    .quote-details tr:last-child td { border-bottom: 0; }
    .quote-details td.lbl {
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1pt;
        font-size: 7.5pt;
        font-weight: bold;
        width: 50%;
    }
    .quote-details td.val {
        text-align: right;
        font-weight: bold;
        color: #0f172a;
    }
    .quote-details td.val.accent { color: <?= $primary ?>; }
    .status-badge {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 9pt;
        font-size: 7.5pt;
        font-weight: bold;
        letter-spacing: 0.6pt;
        text-transform: uppercase;
        background: <?= $stColor ?>;
        color: #ffffff;
    }

    /* ─── ASUNTO (si existe) ──────────────────────────── */
    .subject-row {
        background: #f8fafc;
        border-left: 3px solid <?= $primary ?>;
        padding: 10px 14px;
        margin-bottom: 7mm;
    }
    .subject-row .lbl {
        font-size: 7pt;
        letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: #64748b;
        font-weight: bold;
    }
    .subject-row .val {
        font-size: 11pt;
        font-weight: bold;
        color: #0f172a;
        margin-top: 1px;
    }

    /* ─── INTRO ───────────────────────────────────────── */
    .intro {
        font-size: 9.5pt;
        color: #475569;
        line-height: 1.7;
        margin-bottom: 7mm;
    }

    /* ─── ITEMS TABLE ────────────────────────────────── */
    .items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5mm;
    }
    .items thead th {
        background: #0f172a;
        color: #ffffff;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 0.9pt;
        font-weight: bold;
        text-align: left;
        padding: 11px 10px;
    }
    .items thead th.r { text-align: right; }
    .items thead th.c { text-align: center; }

    .items tbody td {
        padding: 13px 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 9.5pt;
        vertical-align: top;
        color: #0f172a;
    }
    .items tbody td.r {
        text-align: right;
        font-family: 'DejaVu Sans Mono', monospace;
        white-space: nowrap;
    }
    .items tbody td.c { text-align: center; }
    .items tbody tr:last-child td { border-bottom: 2px solid #0f172a; }

    .it-num { color: #94a3b8; font-weight: bold; font-size: 9pt; }
    .it-title { font-weight: bold; color: #0f172a; font-size: 10pt; }
    .it-desc { color: #64748b; font-size: 8.5pt; margin-top: 3px; line-height: 1.55; }
    .it-exempt {
        color: #0891b2; font-size: 7.5pt; margin-top: 4px;
        font-weight: bold; text-transform: uppercase; letter-spacing: 0.8pt;
    }
    .it-unit { color: #94a3b8; font-size: 8pt; }

    /* ─── TOTALES ───────────────────────────────────── */
    .totals-row { width: 100%; }
    .totals-row > tbody > tr > td { vertical-align: top; padding: 0; }

    .totals-table {
        width: 100%;
        border-collapse: collapse;
    }
    .totals-table td {
        padding: 8px 14px;
        font-size: 9.5pt;
    }
    .totals-table td.l { color: #475569; }
    .totals-table td.v {
        text-align: right;
        font-family: 'DejaVu Sans Mono', monospace;
        font-weight: bold;
        color: #0f172a;
        white-space: nowrap;
    }
    .totals-table tr.line td { border-bottom: 1px solid #e2e8f0; }
    .totals-table .disc { color: #b91c1c; }

    .grand-total {
        width: 100%;
        background: #0f172a;
        margin-top: 4px;
    }
    .grand-total td {
        padding: 14px 14px;
        color: #ffffff;
    }
    .grand-total .l {
        font-size: 9.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1.4pt;
    }
    .grand-total .v {
        text-align: right;
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 16pt;
        font-weight: bold;
        white-space: nowrap;
    }

    /* ─── TÉRMINOS / NOTAS / BANCO ─────────────────── */
    .section {
        margin-top: 9mm;
        page-break-inside: avoid;
    }
    .section-h {
        font-size: 8pt;
        letter-spacing: 1.8pt;
        text-transform: uppercase;
        color: <?= $primary ?>;
        font-weight: bold;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 4px;
        margin-bottom: 8px;
    }
    .section-body {
        font-size: 9pt;
        color: #475569;
        line-height: 1.7;
    }
    .section-body strong { color: #0f172a; }

    /* ─── ACEPTADA ───────────────────────────────────── */
    .accepted-banner {
        margin-top: 10mm;
        background: #f0fdf4;
        border: 1px solid #86efac;
        padding: 14px 18px;
    }
    .accepted-banner .h {
        font-size: 11pt; font-weight: bold; color: #14532d;
    }
    .accepted-banner .sub {
        font-size: 9pt; color: #166534; margin-top: 4px;
    }

    /* ─── FIRMAS ────────────────────────────────────── */
    .signatures { width: 100%; margin-top: 16mm; }
    .signatures td { vertical-align: bottom; padding: 0 12px 0 0; }
    .signatures td:last-child { padding-right: 0; padding-left: 12px; }

    .sig-block .line {
        border-top: 1px solid #0f172a;
        padding-top: 6px;
        margin-top: 28px;
    }
    .sig-block .name {
        font-size: 9.5pt;
        font-weight: bold;
        color: #0f172a;
    }
    .sig-block .role {
        font-size: 8pt;
        color: #64748b;
        margin-top: 1px;
    }
    .sig-block .lbl {
        font-size: 7pt;
        letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: #94a3b8;
        font-weight: bold;
        margin-top: 6px;
    }
</style>
</head>
<body>

<!-- ═══════════════ HEADER ═══════════════ -->
<table class="header">
    <tr>
        <td class="logo-cell">
            <?php if ($logo): ?>
                <img src="<?= ee($logo) ?>" alt="logo">
            <?php else: ?>
                <span class="ini"><?= ee(strtoupper(substr($bizName, 0, 1))) ?></span>
            <?php endif; ?>
        </td>
        <td class="title-cell">
            <div class="doc-title">Cotización</div>
            <div class="doc-num">N° <?= ee($quote['code']) ?></div>
        </td>
    </tr>
</table>

<div class="divider-main"></div>

<!-- ═══════════════ DE / PARA / DETALLES ═══════════════ -->
<table class="info-block">
    <tr>
        <!-- DE -->
        <td style="width:33%">
            <div class="info-h">DE</div>
            <div class="info-name"><?= ee($bizName) ?></div>
            <div class="info-body">
                <?php if ($bizDoc): ?>RNC: <?= ee($bizDoc) ?><br><?php endif; ?>
                <?php if ($bizAddress): ?><?= nlbr($bizAddress) ?><br><?php endif; ?>
                <?php if ($bizPhone): ?>Tel: <?= ee($bizPhone) ?><br><?php endif; ?>
                <?php if ($bizEmail): ?><?= ee($bizEmail) ?><br><?php endif; ?>
                <?php if ($bizWeb): ?><?= ee($bizWeb) ?><?php endif; ?>
            </div>
        </td>
        <!-- PARA -->
        <td style="width:34%">
            <div class="info-h">COTIZADO PARA</div>
            <div class="info-name"><?= ee($quote['client_name']) ?></div>
            <div class="info-body">
                <?php if (!empty($quote['client_doc'])): ?>RNC: <?= ee($quote['client_doc']) ?><br><?php endif; ?>
                <?php if (!empty($quote['client_contact'])): ?>Atn: <strong><?= ee($quote['client_contact']) ?></strong><br><?php endif; ?>
                <?php if (!empty($quote['client_address'])): ?><?= ee($quote['client_address']) ?><br><?php endif; ?>
                <?php if (!empty($quote['client_phone'])): ?>Tel: <?= ee($quote['client_phone']) ?><br><?php endif; ?>
                <?php if (!empty($quote['client_email'])): ?><?= ee($quote['client_email']) ?><?php endif; ?>
            </div>
        </td>
        <!-- DETALLES DE LA COTIZACIÓN -->
        <td style="width:33%">
            <div class="info-h">DETALLES</div>
            <table class="quote-details">
                <tr>
                    <td class="lbl">Emitida</td>
                    <td class="val"><?= ee(date('d/m/Y', strtotime((string)($quote['issued_at'] ?: 'now')))) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Válida hasta</td>
                    <td class="val accent"><?= ee(date('d/m/Y', strtotime((string)$quote['valid_until']))) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Moneda</td>
                    <td class="val"><?= ee($quote['currency']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Estado</td>
                    <td class="val"><span class="status-badge"><?= ee($stLabel) ?></span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php if (!empty($quote['title'])): ?>
<div class="subject-row">
    <div class="lbl">ASUNTO</div>
    <div class="val"><?= ee($quote['title']) ?></div>
</div>
<?php endif; ?>

<?php if (!empty($quote['intro'])): ?>
    <div class="intro"><?= nlbr($quote['intro']) ?></div>
<?php endif; ?>

<!-- ═══════════════ ITEMS ═══════════════ -->
<table class="items">
    <thead>
        <tr>
            <th class="c" style="width:5%">#</th>
            <th style="width:47%">Descripción</th>
            <th class="c" style="width:11%">Cantidad</th>
            <th class="r" style="width:14%">Precio Unit.</th>
            <th class="c" style="width:8%">Desc.</th>
            <th class="r" style="width:15%">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $it):
            $unitLabel = $it['unit_label'] ?: $it['unit'];
            $isInt = (float)$it['quantity'] == (int)$it['quantity'];
        ?>
            <tr>
                <td class="c it-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></td>
                <td>
                    <div class="it-title"><?= ee($it['title']) ?></div>
                    <?php if (!empty($it['description'])): ?>
                        <div class="it-desc"><?= nlbr($it['description']) ?></div>
                    <?php endif; ?>
                    <?php if ((int)$it['is_taxable'] === 0): ?>
                        <div class="it-exempt">Exento de impuestos</div>
                    <?php endif; ?>
                </td>
                <td class="c">
                    <?= fmt($it['quantity'], $isInt ? 0 : 2) ?>
                    <span class="it-unit"><?= ee($unitLabel) ?></span>
                </td>
                <td class="r"><?= ee($sym) ?> <?= fmt($it['unit_price'], $decimals) ?></td>
                <td class="c">
                    <?= (float)$it['discount_pct'] > 0
                        ? '<span style="color:#b91c1c;font-weight:bold">' . fmt($it['discount_pct'], 1) . '%</span>'
                        : '<span style="color:#cbd5e1">—</span>' ?>
                </td>
                <td class="r" style="font-weight:bold"><?= ee($sym) ?> <?= fmt($it['line_subtotal'], $decimals) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- ═══════════════ TOTALES ═══════════════ -->
<table class="totals-row">
    <tr>
        <td style="width:50%; padding-right:14px">
            <!-- columna izquierda libre, se rellena con datos de pago si hay -->
        </td>
        <td style="width:50%">
            <table class="totals-table">
                <tr class="line">
                    <td class="l">Subtotal</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['subtotal'], $decimals) ?></td>
                </tr>
                <?php if ((float)$quote['discount_amount'] > 0): ?>
                <tr class="line">
                    <td class="l">Descuento (<?= fmt($quote['discount_pct'], 1) ?>%)</td>
                    <td class="v disc">− <?= ee($sym) ?> <?= fmt($quote['discount_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['tax_rate'] > 0): ?>
                <tr class="line">
                    <td class="l"><?= ee($quote['tax_label']) ?> (<?= fmt($quote['tax_rate'], 1) ?>%)</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['tax_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['shipping_amount'] > 0): ?>
                <tr class="line">
                    <td class="l">Envío</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['shipping_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['other_charges_amount'] > 0): ?>
                <tr class="line">
                    <td class="l"><?= ee($quote['other_charges_label'] ?: 'Otros') ?></td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['other_charges_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <table class="grand-total">
                <tr>
                    <td class="l">Total a pagar</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['total'], $decimals) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php if (!empty($settings['bank_info'])): ?>
<div class="section">
    <div class="section-h">Datos de pago</div>
    <div class="section-body"><?= nlbr($settings['bank_info']) ?></div>
</div>
<?php endif; ?>

<?php if (!empty($quote['notes'])): ?>
<div class="section">
    <div class="section-h">Notas adicionales</div>
    <div class="section-body"><?= nlbr($quote['notes']) ?></div>
</div>
<?php endif; ?>

<?php if (!empty($quote['terms'])): ?>
<div class="section">
    <div class="section-h">Términos y condiciones</div>
    <div class="section-body"><?= nlbr($quote['terms']) ?></div>
</div>
<?php endif; ?>

<?php if ($quote['status'] === 'accepted' && !empty($quote['accepted_at'])): ?>
    <div class="accepted-banner">
        <div class="h">✓ Cotización aceptada</div>
        <div class="sub">
            <?php if (!empty($quote['accepted_by_name'])): ?>Aceptada por <strong><?= ee($quote['accepted_by_name']) ?></strong><?php endif; ?>
            <?php if (!empty($quote['accepted_by_email'])): ?> &lt;<?= ee($quote['accepted_by_email']) ?>&gt;<?php endif; ?>
            el <strong><?= ee(date('d/m/Y H:i', strtotime($quote['accepted_at']))) ?></strong>.
        </div>
    </div>
<?php elseif ((int)($settings['show_signature'] ?? 1) === 1): ?>
    <table class="signatures">
        <tr>
            <td style="width:50%">
                <div class="sig-block">
                    <div class="line">
                        <div class="name"><?= ee($settings['signature_name'] ?: $bizName) ?></div>
                        <?php if (!empty($settings['signature_role'])): ?>
                            <div class="role"><?= ee($settings['signature_role']) ?></div>
                        <?php endif; ?>
                        <div class="lbl">Por <?= ee($bizName) ?></div>
                    </div>
                </div>
            </td>
            <td style="width:50%">
                <div class="sig-block">
                    <div class="line">
                        <div class="name">&nbsp;</div>
                        <div class="role">&nbsp;</div>
                        <div class="lbl">Aceptado por el cliente · Firma y fecha</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
<?php endif; ?>

<script type="text/php">
if (isset($pdf)) {
    $w = $pdf->get_width();
    $h = $pdf->get_height();
    $marginX = 16 * 2.834; // 16mm en pt
    $y = $h - 36;

    $fontReg = $fontMetrics->getFont('DejaVu Sans', 'normal');
    $fontBold = $fontMetrics->getFont('DejaVu Sans', 'bold');

    // Línea separadora
    $pdf->line($marginX, $y - 2, $w - $marginX, $y - 2, [0.85, 0.88, 0.93], 0.5);

    // Footer izq: nombre comercial / mensaje
    $left = json_decode('<?= $footerLeftJson ?>');
    $pdf->page_text($marginX, $y + 6, $left, $fontReg, 7.5, [0.40, 0.45, 0.55]);

    // Centro: código de cotización
    $code = json_decode('<?= $codeJson ?>');
    $codeW = $fontMetrics->getTextWidth($code, $fontBold, 7.5);
    $pdf->page_text(($w - $codeW) / 2, $y + 6, $code, $fontBold, 7.5, [0.20, 0.25, 0.35]);

    // Derecha: paginación
    $right = "Página {PAGE_NUM} de {PAGE_COUNT}";
    $rightSample = "Página 99 de 99";
    $rightW = $fontMetrics->getTextWidth($rightSample, $fontReg, 7.5);
    $pdf->page_text($w - $marginX - $rightW, $y + 6, $right, $fontReg, 7.5, [0.40, 0.45, 0.55]);

    // Subnota
    $sub = "Documento generado electrónicamente";
    $subW = $fontMetrics->getTextWidth($sub, $fontReg, 6.5);
    $pdf->page_text(($w - $subW) / 2, $y + 18, $sub, $fontReg, 6.5, [0.60, 0.65, 0.72]);
}
</script>

</body>
</html>
