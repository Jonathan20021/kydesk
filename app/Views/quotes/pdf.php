<?php
/**
 * Corporate quote PDF template for dompdf.
 *
 * Variables: $quote, $items, $settings, $tenant
 */
if (!function_exists('quotePdfColor')) {
    function quotePdfColor($value, string $fallback): string
    {
        $value = trim((string)$value);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
    }
}

if (!function_exists('quotePdfFmt')) {
    function quotePdfFmt($value, int $decimals = 2): string
    {
        return number_format((float)$value, $decimals, '.', ',');
    }
}

if (!function_exists('quotePdfDate')) {
    function quotePdfDate($value, bool $long = false): string
    {
        if (!$value) return '-';
        $ts = strtotime((string)$value);
        if (!$ts) return '-';

        if (!$long) return date('d/m/Y', $ts);

        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return date('d', $ts) . ' de ' . $months[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
    }
}

if (!function_exists('quotePdfEe')) {
    function quotePdfEe($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('quotePdfNlbr')) {
    function quotePdfNlbr($value): string
    {
        return nl2br(quotePdfEe($value));
    }
}

$primary = quotePdfColor($settings['primary_color'] ?? '', '#3147ff');
$accent = quotePdfColor($settings['accent_color'] ?? '', '#16a34a');
$ink = '#111827';
$muted = '#64748b';
$soft = '#f8fafc';
$line = '#dbe3ee';
$navy = '#0f172a';

$decimals = (int)($settings['decimals'] ?? 2);
$sym = $settings['currency_symbol'] ?: ($quote['currency_symbol'] ?: 'RD$');

$bizName = $settings['business_name'] ?: $tenant->name;
$bizDoc = $settings['business_doc'] ?? '';
$bizAddress = $settings['business_address'] ?? '';
$bizPhone = $settings['business_phone'] ?? '';
$bizEmail = $settings['business_email'] ?? '';
$bizWeb = $settings['business_website'] ?? '';
$logo = $settings['logo_url'] ?? '';
$brandWords = preg_split('/\s+/', trim((string)$bizName));
$firstInitial = substr(preg_replace('/[^A-Za-z0-9]/', '', $brandWords[0] ?? ''), 0, 1);
$secondInitial = substr(preg_replace('/[^A-Za-z0-9]/', '', $brandWords[1] ?? ($brandWords[0] ?? '')), 0, 1);
$brandInitials = strtoupper(($firstInitial ?: 'K') . ($secondInitial ?: 'Y'));

if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') === 0) {
    $logoFs = BASE_PATH . $logo;
    if (is_file($logoFs)) $logo = realpath($logoFs) ?: $logoFs;
}

$statusLabels = [
    'draft' => 'Borrador',
    'sent' => 'Enviada',
    'viewed' => 'Vista',
    'accepted' => 'Aceptada',
    'rejected' => 'Rechazada',
    'expired' => 'Expirada',
    'revised' => 'Revisada',
    'converted' => 'Convertida',
];

$statusColors = [
    'draft' => '#64748b',
    'sent' => '#2563eb',
    'viewed' => '#0891b2',
    'accepted' => '#15803d',
    'rejected' => '#b91c1c',
    'expired' => '#b45309',
    'revised' => '#7c3aed',
    'converted' => '#15803d',
];

$stLabel = $statusLabels[$quote['status']] ?? ucfirst((string)$quote['status']);
$stColor = $statusColors[$quote['status']] ?? '#64748b';

$hasDiscounts = (float)($quote['discount_amount'] ?? 0) > 0;
foreach ($items as $item) {
    if ((float)($item['discount_pct'] ?? 0) > 0) {
        $hasDiscounts = true;
        break;
    }
}

$issuedDate = quotePdfDate($quote['issued_at'] ?: date('Y-m-d'));
$issuedDateLong = quotePdfDate($quote['issued_at'] ?: date('Y-m-d'), true);
$validDate = quotePdfDate($quote['valid_until'] ?? null);
$validDateLong = quotePdfDate($quote['valid_until'] ?? null, true);
$termsInSummary = empty($settings['bank_info']) && !empty($quote['terms']);

$codeJson = json_encode((string)$quote['code']);
$footerLeftJson = json_encode((string)($settings['footer_text'] ?: $bizName));
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotizacion <?= quotePdfEe($quote['code']) ?></title>
<style>
    @page { margin: 7mm 7mm 16mm 7mm; }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: <?= $ink ?>;
        font-size: 7.9pt;
        line-height: 1.42;
        background: #ffffff;
    }

    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }

    .document {
        padding: 7mm 12mm 7mm 12mm;
    }

    .letterhead {
        border-bottom: 1px solid <?= $line ?>;
        margin-bottom: 4.5mm;
    }
    .letterhead td { padding-bottom: 4.5mm; }
    .brand-cell { width: 58%; }
    .quote-cell { width: 42%; text-align: right; }

    .brand-table td { padding: 0; vertical-align: middle; }
    .logo-box {
        width: 58px;
        height: 42px;
        border: 1px solid <?= $line ?>;
        background: <?= $soft ?>;
        text-align: center;
        vertical-align: middle;
        padding: 5px;
    }
    .logo-box img { max-width: 48px; max-height: 32px; }
    .logo-initial {
        display: inline-block;
        width: 46px;
        height: 30px;
        line-height: 30px;
        color: <?= $primary ?>;
        font-size: 15pt;
        font-weight: 900;
        letter-spacing: .7pt;
        text-align: center;
    }
    .brand-copy { padding-left: 11px !important; }
    .brand-name {
        font-size: 13pt;
        font-weight: 800;
        color: <?= $ink ?>;
        line-height: 1.18;
        margin-bottom: 2px;
    }
    .brand-meta {
        color: #475569;
        font-size: 6.9pt;
        line-height: 1.35;
    }

    .doc-title {
        color: <?= $navy ?>;
        font-size: 19pt;
        line-height: 1;
        font-weight: 900;
        letter-spacing: .2pt;
        text-transform: uppercase;
    }
    .doc-code {
        font-family: DejaVu Sans Mono, monospace;
        color: <?= $muted ?>;
        font-size: 7.1pt;
        margin-top: 3px;
    }
    .status-badge {
        display: inline-block;
        margin-top: 5px;
        padding: 2px 8px;
        background: <?= $stColor ?>;
        color: #ffffff;
        font-size: 6.3pt;
        font-weight: 800;
        letter-spacing: .6pt;
        text-transform: uppercase;
    }

    .hero {
        border: 1px solid <?= $line ?>;
        margin-bottom: 4.5mm;
        page-break-inside: avoid;
    }
    .hero-left {
        width: 63%;
        padding: 9px 12px;
        border-left: 5px solid <?= $primary ?>;
    }
    .hero-right {
        width: 37%;
        background: <?= $navy ?>;
        color: #ffffff;
        padding: 10px 12px;
        text-align: right;
    }
    .label {
        color: <?= $primary ?>;
        font-size: 6.3pt;
        font-weight: 900;
        letter-spacing: 1.35pt;
        text-transform: uppercase;
        margin-bottom: 3px;
    }
    .hero-title {
        color: <?= $ink ?>;
        font-size: 12.5pt;
        line-height: 1.22;
        font-weight: 900;
    }
    .hero-sub {
        color: <?= $muted ?>;
        font-size: 7.1pt;
        margin-top: 3px;
    }
    .total-label {
        color: #cbd5e1;
        font-size: 6.2pt;
        font-weight: 800;
        letter-spacing: 1.1pt;
        text-transform: uppercase;
    }
    .total-amount {
        color: <?= $accent ?>;
        font-family: DejaVu Sans Mono, monospace;
        font-size: 14.5pt;
        line-height: 1.15;
        font-weight: 900;
        margin-top: 3px;
        white-space: nowrap;
    }
    .total-note {
        color: #cbd5e1;
        font-size: 6.8pt;
        margin-top: 6px;
    }

    .intro {
        color: #334155;
        font-size: 7.8pt;
        line-height: 1.45;
        margin: 0 0 4.5mm 0;
    }

    .info-grid { margin-bottom: 4.5mm; page-break-inside: avoid; }
    .info-grid td { padding: 0; }
    .gap { width: 10px; }
    .card {
        border: 1px solid <?= $line ?>;
        background: #ffffff;
    }
    .card-inner { padding: 8px 10px; }
    .card-name {
        color: <?= $ink ?>;
        font-size: 8.8pt;
        line-height: 1.25;
        font-weight: 900;
        margin-bottom: 3px;
    }
    .card-body {
        color: #475569;
        font-size: 7.2pt;
        line-height: 1.38;
    }
    .card-body strong { color: <?= $ink ?>; }

    .detail-row { border-bottom: 1px solid <?= $line ?>; }
    .detail-row:last-child { border-bottom: 0; }
    .detail-row td {
        padding: 3px 0;
        font-size: 7.1pt;
    }
    .detail-row .k {
        color: <?= $muted ?>;
        font-size: 6.2pt;
        font-weight: 900;
        letter-spacing: .65pt;
        text-transform: uppercase;
        width: 44%;
    }
    .detail-row .v {
        color: <?= $ink ?>;
        font-weight: 800;
        text-align: right;
    }

    .section-title {
        color: <?= $primary ?>;
        font-size: 6.4pt;
        font-weight: 900;
        letter-spacing: 1.3pt;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .items {
        margin-bottom: 4.5mm;
        page-break-inside: auto;
    }
    .items thead th {
        background: <?= $navy ?>;
        color: #ffffff;
        padding: 6px 8px;
        font-size: 6.3pt;
        font-weight: 900;
        letter-spacing: .7pt;
        text-align: left;
        text-transform: uppercase;
    }
    .items thead th.c { text-align: center; }
    .items thead th.r { text-align: right; }
    .items tbody td {
        padding: 7px 8px;
        border-bottom: 1px solid <?= $line ?>;
        color: <?= $ink ?>;
        font-size: 7.4pt;
    }
    .items tbody tr:nth-child(even) td { background: #fbfdff; }
    .items .c { text-align: center; }
    .items .r {
        text-align: right;
        font-family: DejaVu Sans Mono, monospace;
        white-space: nowrap;
    }
    .item-num {
        color: <?= $muted ?>;
        font-family: DejaVu Sans Mono, monospace;
        font-weight: 800;
    }
    .item-title {
        color: <?= $ink ?>;
        font-size: 7.8pt;
        font-weight: 900;
        line-height: 1.35;
    }
    .item-desc {
        color: <?= $muted ?>;
        font-size: 7pt;
        line-height: 1.38;
        margin-top: 2px;
    }
    .item-unit {
        display: block;
        color: #94a3b8;
        font-size: 6.3pt;
        margin-top: 1px;
    }
    .item-exempt {
        color: #0e7490;
        font-size: 6.8pt;
        font-weight: 900;
        letter-spacing: .55pt;
        margin-top: 4px;
        text-transform: uppercase;
    }
    .discount { color: #b91c1c; font-weight: 900; }
    .empty-dash { color: #cbd5e1; }

    .summary {
        margin-bottom: 4.5mm;
        page-break-inside: avoid;
    }
    .summary-left { width: 53%; padding-right: 10px !important; }
    .summary-right { width: 47%; }
    .note-box {
        border: 1px solid <?= $line ?>;
        background: <?= $soft ?>;
        padding: 8px 10px;
        min-height: 54px;
    }
    .note-body {
        color: #475569;
        font-size: 7.1pt;
        line-height: 1.38;
    }
    .note-body.mono {
        font-family: DejaVu Sans Mono, monospace;
        font-size: 6.9pt;
    }

    .totals {
        border: 1px solid <?= $line ?>;
        background: #ffffff;
    }
    .totals td {
        padding: 5px 10px;
        border-bottom: 1px solid <?= $line ?>;
        font-size: 7.4pt;
    }
    .totals .k { color: #475569; }
    .totals .v {
        color: <?= $ink ?>;
        font-family: DejaVu Sans Mono, monospace;
        font-weight: 900;
        text-align: right;
        white-space: nowrap;
    }
    .totals .disc { color: #b91c1c; }
    .total-row td {
        background: <?= $navy ?>;
        border-bottom: 0;
        color: #ffffff;
        padding: 8px 10px;
    }
    .total-row .k {
        color: #ffffff;
        font-size: 7.2pt;
        font-weight: 900;
        letter-spacing: 1pt;
        text-transform: uppercase;
    }
    .total-row .v {
        color: #ffffff;
        font-size: 11.5pt;
    }

    .text-block {
        margin-top: 3mm;
        page-break-inside: avoid;
    }
    .text-body {
        border-top: 1px solid <?= $line ?>;
        color: #475569;
        font-size: 6.9pt;
        line-height: 1.35;
        padding-top: 4px;
    }

    .accepted-banner {
        margin-top: 9mm;
        border: 1px solid #86efac;
        background: #f0fdf4;
        padding: 12px 14px;
        page-break-inside: avoid;
    }
    .accepted-banner .h {
        color: #14532d;
        font-size: 10pt;
        font-weight: 900;
    }
    .accepted-banner .sub {
        color: #166534;
        font-size: 8pt;
        margin-top: 3px;
    }

    .signatures {
        margin-top: 5mm;
        margin-bottom: 3mm;
        page-break-inside: avoid;
    }
    .signatures td { width: 50%; padding: 0 12px 0 0; }
    .signatures td:last-child { padding-right: 0; padding-left: 12px; }
    .sig-line {
        border-top: 1px solid <?= $ink ?>;
        padding-top: 5px;
    }
    .sig-name {
        color: <?= $ink ?>;
        font-size: 7.6pt;
        font-weight: 900;
        line-height: 1.25;
        min-height: 10px;
    }
    .sig-role {
        color: <?= $muted ?>;
        font-size: 6.8pt;
        min-height: 9px;
    }
    .sig-label {
        color: #94a3b8;
        font-size: 5.9pt;
        font-weight: 900;
        letter-spacing: 1.05pt;
        margin-top: 3px;
        text-transform: uppercase;
    }
</style>
</head>
<body>
<div class="document">

<table class="letterhead">
    <tr>
        <td class="brand-cell">
            <table class="brand-table">
                <tr>
                    <td class="logo-box">
                        <?php if ($logo): ?>
                            <img src="<?= quotePdfEe($logo) ?>" alt="Logo">
                        <?php else: ?>
                            <span class="logo-initial"><?= quotePdfEe($brandInitials) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="brand-copy">
                        <div class="brand-name"><?= quotePdfEe($bizName) ?></div>
                        <div class="brand-meta">
                            <?php if ($bizDoc): ?>RNC / ID: <?= quotePdfEe($bizDoc) ?><br><?php endif; ?>
                            <?php if ($bizAddress): ?><?= quotePdfEe($bizAddress) ?><br><?php endif; ?>
                            <?php if ($bizPhone): ?>Tel: <?= quotePdfEe($bizPhone) ?><?php endif; ?>
                            <?php if ($bizPhone && ($bizEmail || $bizWeb)): ?> &middot; <?php endif; ?>
                            <?php if ($bizEmail): ?><?= quotePdfEe($bizEmail) ?><?php endif; ?>
                            <?php if ($bizEmail && $bizWeb): ?> &middot; <?php endif; ?>
                            <?php if ($bizWeb): ?><?= quotePdfEe($bizWeb) ?><?php endif; ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td class="quote-cell">
            <div class="doc-title">Cotizaci&oacute;n</div>
            <div class="doc-code">N&deg; <?= quotePdfEe($quote['code']) ?></div>
            <span class="status-badge"><?= quotePdfEe($stLabel) ?></span>
        </td>
    </tr>
</table>

<table class="hero">
    <tr>
        <td class="hero-left">
            <div class="label">Propuesta comercial</div>
            <div class="hero-title"><?= quotePdfEe($quote['title'] ?: ('Cotizacion ' . $quote['code'])) ?></div>
            <div class="hero-sub">Emitida el <?= quotePdfEe($issuedDateLong) ?> &middot; Vigente hasta el <?= quotePdfEe($validDateLong) ?></div>
        </td>
        <td class="hero-right">
            <div class="total-label">Total estimado</div>
            <div class="total-amount"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['total'], $decimals) ?></div>
            <div class="total-note">Moneda: <?= quotePdfEe($quote['currency']) ?></div>
        </td>
    </tr>
</table>

<table class="info-grid">
    <tr>
        <td style="width:61%">
            <div class="card">
                <div class="card-inner">
                    <div class="label">Cliente</div>
                    <div class="card-name"><?= quotePdfEe($quote['client_name']) ?></div>
                    <div class="card-body">
                        <?php if (!empty($quote['client_doc'])): ?>RNC / ID: <?= quotePdfEe($quote['client_doc']) ?><br><?php endif; ?>
                        <?php if (!empty($quote['client_contact'])): ?>Atn: <strong><?= quotePdfEe($quote['client_contact']) ?></strong><br><?php endif; ?>
                        <?php if (!empty($quote['client_phone'])): ?>Tel: <?= quotePdfEe($quote['client_phone']) ?><br><?php endif; ?>
                        <?php if (!empty($quote['client_email'])): ?><?= quotePdfEe($quote['client_email']) ?><br><?php endif; ?>
                        <?php if (!empty($quote['client_address'])): ?><?= quotePdfNlbr($quote['client_address']) ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </td>
        <td class="gap"></td>
        <td style="width:39%">
            <div class="card">
                <div class="card-inner">
                    <div class="label">Datos de la cotizaci&oacute;n</div>
                    <table>
                        <tr class="detail-row">
                            <td class="k">Emisi&oacute;n</td>
                            <td class="v"><?= quotePdfEe($issuedDate) ?></td>
                        </tr>
                        <tr class="detail-row">
                            <td class="k">Validez</td>
                            <td class="v"><?= quotePdfEe($validDate) ?></td>
                        </tr>
                        <tr class="detail-row">
                            <td class="k">Moneda</td>
                            <td class="v"><?= quotePdfEe($quote['currency']) ?></td>
                        </tr>
                        <tr class="detail-row">
                            <td class="k">Estado</td>
                            <td class="v"><?= quotePdfEe($stLabel) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </td>
    </tr>
</table>

<?php if (!empty($quote['intro'])): ?>
    <div class="intro"><?= quotePdfNlbr($quote['intro']) ?></div>
<?php endif; ?>

<div class="section-title">Detalle de servicios / productos</div>
<table class="items">
    <thead>
        <tr>
            <th class="c" style="width:6%">#</th>
            <th style="width:<?= $hasDiscounts ? '45' : '51' ?>%">Descripci&oacute;n</th>
            <th class="c" style="width:11%">Cant.</th>
            <th class="r" style="width:15%">Precio unit.</th>
            <?php if ($hasDiscounts): ?>
                <th class="c" style="width:8%">Desc.</th>
            <?php endif; ?>
            <th class="r" style="width:15%">Importe</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $it):
            $unitLabel = $it['unit_label'] ?: $it['unit'];
            $isInt = (float)$it['quantity'] == (int)$it['quantity'];
        ?>
            <tr>
                <td class="c item-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></td>
                <td>
                    <div class="item-title"><?= quotePdfEe($it['title']) ?></div>
                    <?php if (!empty($it['description'])): ?>
                        <div class="item-desc"><?= quotePdfNlbr($it['description']) ?></div>
                    <?php endif; ?>
                    <?php if ((int)$it['is_taxable'] === 0): ?>
                        <div class="item-exempt">Exento de impuestos</div>
                    <?php endif; ?>
                </td>
                <td class="c">
                    <?= quotePdfFmt($it['quantity'], $isInt ? 0 : 2) ?>
                    <span class="item-unit"><?= quotePdfEe($unitLabel) ?></span>
                </td>
                <td class="r"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($it['unit_price'], $decimals) ?></td>
                <?php if ($hasDiscounts): ?>
                    <td class="c">
                        <?= (float)$it['discount_pct'] > 0
                            ? '<span class="discount">' . quotePdfFmt($it['discount_pct'], 1) . '%</span>'
                            : '<span class="empty-dash">&mdash;</span>' ?>
                    </td>
                <?php endif; ?>
                <td class="r" style="font-weight:900"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($it['line_subtotal'], $decimals) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td class="summary-left">
            <div class="note-box">
                <?php if (!empty($settings['bank_info'])): ?>
                    <div class="section-title">Datos de pago</div>
                    <div class="note-body mono"><?= quotePdfNlbr($settings['bank_info']) ?></div>
                <?php elseif ($termsInSummary): ?>
                    <div class="section-title">T&eacute;rminos y condiciones</div>
                    <div class="note-body"><?= quotePdfNlbr($quote['terms']) ?></div>
                <?php else: ?>
                    <div class="section-title">Condiciones comerciales</div>
                    <div class="note-body">
                        Esta propuesta est&aacute; expresada en <strong><?= quotePdfEe($quote['currency']) ?></strong> y mantiene validez hasta el <strong><?= quotePdfEe($validDateLong) ?></strong>.
                        Los tiempos de entrega se confirman al aprobar la cotizaci&oacute;n.
                    </div>
                <?php endif; ?>
            </div>
        </td>
        <td class="summary-right">
            <table class="totals">
                <tr>
                    <td class="k">Subtotal</td>
                    <td class="v"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['subtotal'], $decimals) ?></td>
                </tr>
                <?php if ((float)$quote['discount_amount'] > 0): ?>
                    <tr>
                        <td class="k">Descuento (<?= quotePdfFmt($quote['discount_pct'], 1) ?>%)</td>
                        <td class="v disc">- <?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['discount_amount'], $decimals) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ((float)$quote['tax_rate'] > 0): ?>
                    <tr>
                        <td class="k"><?= quotePdfEe($quote['tax_label']) ?> (<?= quotePdfFmt($quote['tax_rate'], 1) ?>%)</td>
                        <td class="v"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['tax_amount'], $decimals) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ((float)$quote['shipping_amount'] > 0): ?>
                    <tr>
                        <td class="k">Env&iacute;o</td>
                        <td class="v"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['shipping_amount'], $decimals) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ((float)$quote['other_charges_amount'] > 0): ?>
                    <tr>
                        <td class="k"><?= quotePdfEe($quote['other_charges_label'] ?: 'Otros') ?></td>
                        <td class="v"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['other_charges_amount'], $decimals) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="k">Total a pagar</td>
                    <td class="v"><?= quotePdfEe($sym) ?> <?= quotePdfFmt($quote['total'], $decimals) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php if (!empty($quote['notes'])): ?>
    <div class="text-block">
        <div class="section-title">Notas adicionales</div>
        <div class="text-body"><?= quotePdfNlbr($quote['notes']) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($quote['terms']) && !$termsInSummary): ?>
    <div class="text-block">
        <div class="section-title">T&eacute;rminos y condiciones</div>
        <div class="text-body"><?= quotePdfNlbr($quote['terms']) ?></div>
    </div>
<?php endif; ?>

<?php if ($quote['status'] === 'accepted' && !empty($quote['accepted_at'])): ?>
    <div class="accepted-banner">
        <div class="h">Cotizaci&oacute;n aceptada</div>
        <div class="sub">
            <?php if (!empty($quote['accepted_by_name'])): ?>Aceptada por <strong><?= quotePdfEe($quote['accepted_by_name']) ?></strong><?php endif; ?>
            <?php if (!empty($quote['accepted_by_email'])): ?> &lt;<?= quotePdfEe($quote['accepted_by_email']) ?>&gt;<?php endif; ?>
            el <strong><?= quotePdfEe(date('d/m/Y H:i', strtotime($quote['accepted_at']))) ?></strong>.
        </div>
    </div>
<?php elseif ((int)($settings['show_signature'] ?? 1) === 1): ?>
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">
                    <div class="sig-name"><?= quotePdfEe($settings['signature_name'] ?: $bizName) ?></div>
                    <div class="sig-role"><?= quotePdfEe($settings['signature_role'] ?: 'Representante autorizado') ?></div>
                    <div class="sig-label">Por <?= quotePdfEe($bizName) ?></div>
                </div>
            </td>
            <td>
                <div class="sig-line">
                    <div class="sig-name">&nbsp;</div>
                    <div class="sig-role">&nbsp;</div>
                    <div class="sig-label">Aceptado por el cliente / firma y fecha</div>
                </div>
            </td>
        </tr>
    </table>
<?php endif; ?>

</div>

<script type="text/php">
if (isset($pdf)) {
    $w = $pdf->get_width();
    $h = $pdf->get_height();
    $marginX = 22 * 2.834;
    $y = $h - 42;

    $fontReg = $fontMetrics->getFont('DejaVu Sans', 'normal');
    $fontBold = $fontMetrics->getFont('DejaVu Sans', 'bold');

    $pdf->line($marginX, $y - 2, $w - $marginX, $y - 2, [0.86, 0.89, 0.93], 0.45);

    $left = json_decode('<?= $footerLeftJson ?>');
    $pdf->page_text($marginX, $y + 7, $left, $fontReg, 7, [0.40, 0.45, 0.55]);

    $code = json_decode('<?= $codeJson ?>');
    $codeW = $fontMetrics->getTextWidth($code, $fontBold, 7);
    $pdf->page_text(($w - $codeW) / 2, $y + 7, $code, $fontBold, 7, [0.17, 0.22, 0.31]);

    $right = "Pagina {PAGE_NUM} de {PAGE_COUNT}";
    $rightSample = "Pagina 99 de 99";
    $rightW = $fontMetrics->getTextWidth($rightSample, $fontReg, 7);
    $pdf->page_text($w - $marginX - $rightW, $y + 7, $right, $fontReg, 7, [0.40, 0.45, 0.55]);

    $sub = "Documento generado electronicamente";
    $subW = $fontMetrics->getTextWidth($sub, $fontReg, 6.1);
    $pdf->page_text(($w - $subW) / 2, $y + 18, $sub, $fontReg, 6.1, [0.58, 0.63, 0.70]);
}
</script>

</body>
</html>
