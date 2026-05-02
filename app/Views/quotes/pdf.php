<?php
/**
 * Plantilla PDF profesional de cotización (dompdf 3.x).
 *
 * Reglas que respetamos para que dompdf renderice limpio:
 *   · Layouts basados en <table> (no flex/grid). Anchos en %.
 *   · Backgrounds en tablas/td se respetan; en divs sueltos a veces no.
 *   · No usar inline-block; usar table-cell.
 *   · No usar @page header/footer (eso es mPDF). Para footer fijo usamos position:fixed.
 *   · Logos: preferir ruta absoluta del filesystem cuando es upload local.
 *
 * Variables: $quote, $items, $settings, $tenant
 */
$primary = $settings['primary_color'] ?: '#7c5cff';
$accent  = $settings['accent_color'] ?: '#16a34a';
$decimals = (int)($settings['decimals'] ?? 2);
$sym = $settings['currency_symbol'] ?: ($quote['currency_symbol'] ?: 'RD$');

$bizName    = $settings['business_name'] ?: $tenant->name;
$bizDoc     = $settings['business_doc'] ?? '';
$bizAddress = $settings['business_address'] ?? '';
$bizPhone   = $settings['business_phone'] ?? '';
$bizEmail   = $settings['business_email'] ?? '';
$bizWeb     = $settings['business_website'] ?? '';
$logo       = $settings['logo_url'] ?? '';

// Logo: convertir ruta relativa pública a absoluta del filesystem para dompdf
if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') === 0) {
    $logoFs = BASE_PATH . $logo;
    if (is_file($logoFs)) $logo = $logoFs;
}

$statusLabels = [
    'draft' => 'BORRADOR', 'sent' => 'ENVIADA', 'viewed' => 'VISTA',
    'accepted' => 'ACEPTADA', 'rejected' => 'RECHAZADA', 'expired' => 'EXPIRADA',
    'revised' => 'REVISADA', 'converted' => 'CONVERTIDA',
];
$statusColors = [
    'draft' => '#94a3b8', 'sent' => '#3b82f6', 'viewed' => '#0ea5e9',
    'accepted' => '#16a34a', 'rejected' => '#dc2626', 'expired' => '#f59e0b',
    'revised' => '#7c5cff', 'converted' => '#16a34a',
];
$stColor = $statusColors[$quote['status']] ?? '#94a3b8';
$stLabel = $statusLabels[$quote['status']] ?? strtoupper($quote['status']);

function fmt($v, $d = 2) { return number_format((float)$v, $d, '.', ','); }
function nlbr($t) { return nl2br(htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8')); }
function ee($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotización <?= ee($quote['code']) ?></title>
<style>
    @page { margin: 14mm 12mm 18mm 12mm; }

    * { box-sizing: border-box; }
    html, body {
        margin: 0; padding: 0;
        font-family: 'DejaVu Sans', sans-serif;
        color: #1a1a25;
        font-size: 10pt;
        line-height: 1.45;
    }

    /* HEADER en flujo (no fixed) — dompdf renderiza tablas perfectamente */
    table { border-collapse: collapse; }
    .header-table { width: 100%; }
    .header-table td { vertical-align: top; padding: 0; }

    .logo-cell { width: 60%; }
    .logo-cell img { max-height: 50px; max-width: 180px; }
    .logo-fallback {
        width: 50px; height: 50px;
        background: <?= $primary ?>; color: #fff;
        font-size: 22pt; font-weight: bold;
        text-align: center; line-height: 50px;
        border-radius: 6px;
    }
    .biz-name {
        font-size: 14pt; font-weight: bold;
        color: #1a1a25; line-height: 1.2;
        margin-top: 6px;
    }
    .biz-meta {
        font-size: 8pt; color: #6b6b78;
        line-height: 1.5;
    }

    .stamp-cell { width: 40%; text-align: right; }
    .stamp-eyebrow {
        font-size: 8pt; letter-spacing: 2pt;
        text-transform: uppercase;
        color: <?= $primary ?>; font-weight: bold;
    }
    .stamp-title {
        font-size: 22pt; font-weight: bold;
        letter-spacing: -0.5pt;
        color: #1a1a25; line-height: 1; margin-top: 2px;
    }
    .stamp-code {
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 10pt; color: #475569; margin-top: 4px;
    }
    .stamp-status {
        display: inline-block;
        margin-top: 6px;
        padding: 3px 10px;
        background: <?= $stColor ?>;
        color: #fff; font-size: 8pt;
        font-weight: bold; letter-spacing: 1pt;
        border-radius: 9pt;
    }

    .divider {
        height: 3px; background: <?= $primary ?>;
        margin: 14px 0 16px 0;
    }

    /* CLIENT + META */
    .meta-table { width: 100%; }
    .meta-table > tbody > tr > td { vertical-align: top; padding: 0; }

    .client-card {
        background: #fafafb;
        border-left: 4px solid <?= $primary ?>;
        padding: 12px 14px;
        border-radius: 4px;
    }
    .client-h {
        font-size: 7.5pt; letter-spacing: 1.4pt;
        text-transform: uppercase;
        color: <?= $primary ?>; font-weight: bold;
        margin-bottom: 5px;
    }
    .client-name {
        font-size: 12pt; font-weight: bold;
        color: #1a1a25; line-height: 1.2;
    }
    .client-meta {
        font-size: 8.5pt; color: #475569;
        margin-top: 4px; line-height: 1.55;
    }

    .meta-card {
        background: #fff;
        border: 1px solid #ececef;
        border-radius: 4px;
        padding: 12px 14px;
    }
    .meta-card .lbl {
        font-size: 7pt; letter-spacing: 1.2pt;
        text-transform: uppercase;
        color: #94a3b8; font-weight: bold;
    }
    .meta-card .val {
        font-size: 10pt; color: #1a1a25;
        font-weight: bold; margin-top: 1px;
    }
    .meta-card .val.green { color: <?= $accent ?>; }

    /* INTRO */
    .intro {
        margin-top: 16px;
        font-size: 9.5pt; color: #475569;
        line-height: 1.6;
    }

    /* ITEMS */
    .items {
        width: 100%; margin-top: 14px;
        border-collapse: collapse;
    }
    .items th {
        background: #1a1a25;
        color: #fff;
        text-align: left;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        font-weight: bold;
        padding: 9px 8px;
    }
    .items th.r { text-align: right; }
    .items th.c { text-align: center; }
    .items td {
        padding: 10px 8px;
        border-bottom: 1px solid #ececef;
        font-size: 9pt;
        vertical-align: top;
    }
    .items td.r { text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
    .items td.c { text-align: center; }
    .items tr.zebra td { background: #fafafb; }
    .it-title { font-weight: bold; color: #1a1a25; }
    .it-desc { color: #6b6b78; font-size: 8pt; margin-top: 2px; line-height: 1.45; }
    .it-exempt {
        color: #0ea5e9; font-size: 7.5pt; margin-top: 2px;
        font-weight: bold;
    }

    /* TOTALES — dompdf-friendly: tabla con dos columnas */
    .totals-wrap {
        width: 100%; margin-top: 16px;
    }
    .totals-wrap > tbody > tr > td { vertical-align: top; padding: 0; }

    .totals-side {
        width: 100%;
        border: 1px solid #ececef;
        border-radius: 4px;
    }
    .totals-side td {
        padding: 7px 12px;
        font-size: 9.5pt;
    }
    .totals-side td.l { color: #475569; }
    .totals-side td.v {
        text-align: right;
        font-family: 'DejaVu Sans Mono', monospace;
        font-weight: bold; color: #1a1a25;
    }
    .totals-side tr { border-bottom: 1px solid #f3f4f6; }
    .totals-side .disc { color: #dc2626; }

    .grand-total-table {
        width: 100%;
        margin-top: 8px;
        background: <?= $primary ?>;
        border-radius: 4px;
    }
    .grand-total-table td { padding: 14px 16px; color: #fff; }
    .grand-total-lbl {
        font-size: 8pt; letter-spacing: 1.6pt;
        text-transform: uppercase;
        font-weight: bold; opacity: 0.9;
    }
    .grand-total-val {
        font-size: 22pt; font-weight: bold;
        font-family: 'DejaVu Sans Mono', monospace;
        line-height: 1.1; margin-top: 2px;
    }

    .bank-card {
        background: #fafafb;
        border-left: 3px solid <?= $accent ?>;
        padding: 11px 13px;
        border-radius: 3px;
        font-size: 8.5pt;
        color: #475569;
        line-height: 1.55;
    }
    .bank-card.notes { border-left-color: #94a3b8; }
    .bank-card .h {
        font-size: 7pt; letter-spacing: 1.4pt;
        text-transform: uppercase;
        color: <?= $accent ?>; font-weight: bold;
        margin-bottom: 4px;
    }
    .bank-card.notes .h { color: #475569; }

    /* TERMS */
    .terms {
        margin-top: 22px;
        padding-top: 12px;
        border-top: 1px solid #ececef;
    }
    .terms-h {
        font-size: 8pt; letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: <?= $primary ?>; font-weight: bold;
        margin-bottom: 4px;
    }
    .terms-body {
        font-size: 8.5pt; color: #475569;
        line-height: 1.6;
    }

    /* SIGNATURE */
    .signature-table { width: 100%; margin-top: 28px; }
    .signature-table td { vertical-align: top; padding: 0; }
    .sig-box { width: 50%; }
    .sig-line {
        border-top: 1px solid #1a1a25;
        padding-top: 4px;
    }
    .sig-name { font-size: 9pt; font-weight: bold; color: #1a1a25; }
    .sig-role { font-size: 8pt; color: #6b6b78; }

    .accepted-banner {
        margin-top: 24px;
        padding: 12px 14px;
        background: #ecfdf5;
        border: 1px solid <?= $accent ?>;
        border-radius: 4px;
        font-size: 9pt;
        color: #15803d;
    }
    .accepted-banner strong { color: #14532d; }

    /* FOOTER (último elemento; dompdf permite position:fixed pero es frágil) */
    .footer {
        margin-top: 30px;
        padding-top: 8px;
        border-top: 1px solid #ececef;
        font-size: 7.5pt;
        color: #94a3b8;
        text-align: center;
    }
</style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td class="logo-cell">
            <?php if ($logo): ?>
                <img src="<?= ee($logo) ?>" alt="logo">
            <?php else: ?>
                <div class="logo-fallback"><?= ee(strtoupper(substr($bizName, 0, 1))) ?></div>
            <?php endif; ?>
            <div class="biz-name"><?= ee($bizName) ?></div>
            <div class="biz-meta">
                <?php if ($bizDoc): ?>RNC/ID: <?= ee($bizDoc) ?><br><?php endif; ?>
                <?php if ($bizAddress): ?><?= ee($bizAddress) ?><br><?php endif; ?>
                <?php
                    $contactBits = [];
                    if ($bizPhone) $contactBits[] = 'Tel: ' . $bizPhone;
                    if ($bizEmail) $contactBits[] = $bizEmail;
                    if ($contactBits) echo ee(implode(' · ', $contactBits)) . '<br>';
                ?>
                <?php if ($bizWeb): ?><?= ee($bizWeb) ?><?php endif; ?>
            </div>
        </td>
        <td class="stamp-cell">
            <div class="stamp-eyebrow">COTIZACIÓN</div>
            <div class="stamp-title">Quotation</div>
            <div class="stamp-code"><?= ee($quote['code']) ?></div>
            <div class="stamp-status"><?= ee($stLabel) ?></div>
        </td>
    </tr>
</table>

<div class="divider"></div>

<!-- CLIENT + META -->
<table class="meta-table">
    <tr>
        <td style="width:60%; padding-right:8px">
            <div class="client-card">
                <div class="client-h">CLIENTE</div>
                <div class="client-name"><?= ee($quote['client_name']) ?></div>
                <div class="client-meta">
                    <?php if (!empty($quote['client_doc'])): ?>RNC/ID: <?= ee($quote['client_doc']) ?><br><?php endif; ?>
                    <?php if (!empty($quote['client_contact'])): ?>Atn: <?= ee($quote['client_contact']) ?><br><?php endif; ?>
                    <?php
                        $cm = [];
                        if (!empty($quote['client_email'])) $cm[] = $quote['client_email'];
                        if (!empty($quote['client_phone'])) $cm[] = $quote['client_phone'];
                        if ($cm) echo ee(implode(' · ', $cm)) . '<br>';
                    ?>
                    <?php if (!empty($quote['client_address'])): ?><?= ee($quote['client_address']) ?><?php endif; ?>
                </div>
            </div>
        </td>
        <td style="width:40%">
            <div class="meta-card">
                <table style="width:100%">
                    <tr>
                        <td style="padding:0 0 6px 0">
                            <div class="lbl">Emitida</div>
                            <div class="val"><?= ee((string)($quote['issued_at'] ?: date('Y-m-d'))) ?></div>
                        </td>
                        <td style="padding:0 0 6px 0; text-align:right">
                            <div class="lbl">Válida hasta</div>
                            <div class="val green"><?= ee((string)$quote['valid_until']) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0 0 0; border-top:1px solid #ececef">
                            <div class="lbl">Moneda</div>
                            <div class="val" style="font-size:9pt"><?= ee($quote['currency']) ?> (<?= ee($sym) ?>)</div>
                        </td>
                        <?php if (!empty($quote['title'])): ?>
                        <td style="padding:6px 0 0 0; border-top:1px solid #ececef; text-align:right">
                            <div class="lbl">Asunto</div>
                            <div class="val" style="font-size:8.5pt"><?= ee($quote['title']) ?></div>
                        </td>
                        <?php else: ?>
                        <td></td>
                        <?php endif; ?>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<?php if (!empty($quote['intro'])): ?>
    <div class="intro"><?= nlbr($quote['intro']) ?></div>
<?php endif; ?>

<!-- ITEMS -->
<table class="items">
    <thead>
        <tr>
            <th class="c" style="width:5%">#</th>
            <th style="width:48%">Descripción</th>
            <th class="c" style="width:11%">Cant.</th>
            <th class="r" style="width:13%">Precio</th>
            <th class="c" style="width:8%">Desc.</th>
            <th class="r" style="width:15%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $it):
            $unitLabel = $it['unit_label'] ?: $it['unit'];
            $isInt = (float)$it['quantity'] == (int)$it['quantity'];
        ?>
            <tr <?= $i % 2 === 1 ? 'class="zebra"' : '' ?>>
                <td class="c" style="color:#94a3b8;font-weight:bold"><?= $i + 1 ?></td>
                <td>
                    <div class="it-title"><?= ee($it['title']) ?></div>
                    <?php if (!empty($it['description'])): ?>
                        <div class="it-desc"><?= nlbr($it['description']) ?></div>
                    <?php endif; ?>
                    <?php if ((int)$it['is_taxable'] === 0): ?>
                        <div class="it-exempt">Exento de impuestos</div>
                    <?php endif; ?>
                </td>
                <td class="c"><?= fmt($it['quantity'], $isInt ? 0 : 2) ?> <?= ee($unitLabel) ?></td>
                <td class="r"><?= ee($sym) ?> <?= fmt($it['unit_price'], $decimals) ?></td>
                <td class="c"><?= (float)$it['discount_pct'] > 0 ? fmt($it['discount_pct'], 1) . '%' : '—' ?></td>
                <td class="r" style="font-weight:bold"><?= ee($sym) ?> <?= fmt($it['line_subtotal'], $decimals) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- TOTALS -->
<table class="totals-wrap">
    <tr>
        <td style="width:55%; padding-right:8px">
            <?php if (!empty($settings['bank_info'])): ?>
                <div class="bank-card">
                    <div class="h">DATOS DE PAGO</div>
                    <?= nlbr($settings['bank_info']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($quote['notes'])): ?>
                <div class="bank-card notes" style="margin-top:<?= !empty($settings['bank_info']) ? '8px' : '0' ?>">
                    <div class="h">NOTAS</div>
                    <?= nlbr($quote['notes']) ?>
                </div>
            <?php endif; ?>
        </td>
        <td style="width:45%">
            <table class="totals-side">
                <tr>
                    <td class="l">Subtotal</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['subtotal'], $decimals) ?></td>
                </tr>
                <?php if ((float)$quote['discount_amount'] > 0): ?>
                <tr>
                    <td class="l">Descuento (<?= fmt($quote['discount_pct'], 1) ?>%)</td>
                    <td class="v disc">− <?= ee($sym) ?> <?= fmt($quote['discount_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['tax_rate'] > 0): ?>
                <tr>
                    <td class="l"><?= ee($quote['tax_label']) ?> (<?= fmt($quote['tax_rate'], 1) ?>%)</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['tax_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['shipping_amount'] > 0): ?>
                <tr>
                    <td class="l">Envío</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['shipping_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['other_charges_amount'] > 0): ?>
                <tr>
                    <td class="l"><?= ee($quote['other_charges_label'] ?: 'Otros') ?></td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['other_charges_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <table class="grand-total-table">
                <tr>
                    <td>
                        <div class="grand-total-lbl">TOTAL A PAGAR</div>
                        <div class="grand-total-val"><?= ee($sym) ?> <?= fmt($quote['total'], $decimals) ?></div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php if (!empty($quote['terms'])): ?>
    <div class="terms">
        <div class="terms-h">TÉRMINOS Y CONDICIONES</div>
        <div class="terms-body"><?= nlbr($quote['terms']) ?></div>
    </div>
<?php endif; ?>

<?php if ($quote['status'] === 'accepted' && !empty($quote['accepted_at'])): ?>
    <div class="accepted-banner">
        ✓ <strong>Cotización aceptada</strong>
        <?php if (!empty($quote['accepted_by_name'])): ?> por <strong><?= ee($quote['accepted_by_name']) ?></strong><?php endif; ?>
        el <strong><?= ee(date('d/m/Y H:i', strtotime($quote['accepted_at']))) ?></strong>.
    </div>
<?php elseif ((int)($settings['show_signature'] ?? 1) === 1): ?>
    <table class="signature-table">
        <tr>
            <td class="sig-box">
                <div style="height:36px"></div>
                <div class="sig-line">
                    <div class="sig-name"><?= ee($settings['signature_name'] ?: $bizName) ?></div>
                    <?php if (!empty($settings['signature_role'])): ?>
                        <div class="sig-role"><?= ee($settings['signature_role']) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td></td>
        </tr>
    </table>
<?php endif; ?>

<div class="footer">
    <?= ee($settings['footer_text'] ?: ($bizName . ' · Cotización ' . $quote['code'])) ?>
</div>

</body>
</html>
