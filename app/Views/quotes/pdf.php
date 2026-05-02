<?php
/**
 * Plantilla PDF profesional de cotización (mPDF).
 * Variables disponibles: $quote, $items, $settings, $tenant
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

if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') === 0) {
    // Es una ruta relativa; convertir a absoluta del filesystem para mPDF
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
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotización <?= htmlspecialchars($quote['code']) ?></title>
<style>
    @page {
        margin: 38mm 12mm 22mm 12mm;
        header: page-header;
        footer: page-footer;
    }

    body { font-family: dejavusans, sans-serif; color: #1a1a25; font-size: 9pt; line-height: 1.5; }

    /* HEADER (cada página) */
    .ph-wrap { width:100%; }
    .ph-wrap td { vertical-align: top; }
    .ph-logo { max-height: 42pt; max-width: 130pt; }
    .ph-noimg {
        display:inline-block; width:42pt; height:42pt; line-height:42pt;
        background: <?= $primary ?>; color: #fff; text-align:center;
        font-size:18pt; font-weight:bold; border-radius:6pt;
    }
    .biz-name { font-size:13pt; font-weight:bold; color:#1a1a25; line-height:1.15; }
    .biz-meta { font-size:7.5pt; color:#6b6b78; line-height:1.45; margin-top:1pt; }

    .doc-stamp {
        text-align:right;
    }
    .doc-eyebrow {
        font-size:7pt; letter-spacing:2pt; text-transform:uppercase;
        color:<?= $primary ?>; font-weight:bold;
    }
    .doc-title {
        font-size:18pt; font-weight:bold; letter-spacing:-0.4pt;
        color:#1a1a25; line-height:1.05; margin-top:1pt;
    }
    .doc-code {
        font-family: dejavusansmono, monospace;
        font-size:9.5pt; color:#475569; margin-top:2pt;
    }
    .doc-status {
        display:inline-block; margin-top:4pt; padding:2pt 8pt;
        border-radius:8pt; background:<?= $stColor ?>; color:#fff;
        font-size:7pt; font-weight:bold; letter-spacing:1pt;
    }

    .ph-bar { height:2pt; background:<?= $primary ?>; margin-top:6pt; }

    /* FOOTER */
    .pf-wrap {
        width:100%; padding-top:3mm; border-top:0.5pt solid #ececef;
        font-size:6.8pt; color:#94a3b8;
    }
    .pf-wrap td { vertical-align:middle; }

    /* BLOQUES DE INFO */
    .meta-row { width:100%; margin-bottom:4mm; }
    .meta-row td {
        vertical-align:top; padding:0;
    }
    .meta-card {
        background:#fafafb; border:0.6pt solid #ececef;
        border-radius:5pt; padding:8pt 10pt;
    }
    .meta-card .lbl {
        font-size:6.5pt; letter-spacing:1.4pt; text-transform:uppercase;
        color:#94a3b8; font-weight:bold;
    }
    .meta-card .val {
        font-size:9pt; color:#1a1a25; margin-top:2pt; font-weight:bold;
    }
    .meta-card .sub {
        font-size:7.5pt; color:#475569; margin-top:1pt; line-height:1.4;
    }

    .client-card {
        border:0.6pt solid #ececef; border-radius:5pt;
        padding:9pt 12pt; background:#fff;
        border-left: 3pt solid <?= $primary ?>;
    }
    .client-card .h {
        font-size:6.5pt; letter-spacing:1.4pt; text-transform:uppercase;
        color:<?= $primary ?>; font-weight:bold; margin-bottom:3pt;
    }
    .client-card .name {
        font-size:11pt; font-weight:bold; color:#1a1a25;
    }
    .client-card .meta { font-size:8pt; color:#475569; margin-top:2pt; line-height:1.5; }

    /* INTRO */
    .intro {
        margin: 4mm 0;
        font-size:8.5pt; color:#475569; line-height:1.6;
    }

    /* TABLA DE ITEMS */
    table.items { width:100%; border-collapse:collapse; margin-top:2mm; }
    table.items th {
        text-align:left; font-size:7pt; text-transform:uppercase;
        letter-spacing:0.6pt; padding:7pt 6pt; background:#1a1a25; color:#fff;
        font-weight:bold; border-bottom: 1pt solid #1a1a25;
    }
    table.items th.r { text-align:right; }
    table.items th.c { text-align:center; }
    table.items td {
        padding:7pt 6pt; vertical-align:top;
        border-bottom: 0.5pt solid #ececef; font-size:8.5pt;
    }
    table.items td.r { text-align:right; font-family:dejavusansmono, monospace; }
    table.items td.c { text-align:center; }
    table.items tr.zebra td { background:#fafafb; }
    table.items td .it-title { font-weight:bold; color:#1a1a25; }
    table.items td .it-desc { color:#6b6b78; font-size:7.5pt; margin-top:1pt; line-height:1.4; }
    table.items td .it-disc { color:#dc2626; font-size:7pt; margin-top:1pt; }

    /* TOTALES */
    .totals-wrap { width:100%; margin-top:4mm; }
    .totals-wrap td { vertical-align:top; padding:0; }
    .totals-card {
        border:0.8pt solid #ececef; border-radius:5pt;
        padding:9pt 12pt; background:#fff;
    }
    .totals-card .row {
        font-size:8.5pt; color:#475569; padding:2.5pt 0;
        border-bottom:0.4pt solid #ececef;
    }
    .totals-card .row:last-child { border-bottom:0; }
    .totals-card .row.lbl { color:#475569; }
    .totals-card .row .v { float:right; font-family:dejavusansmono, monospace; color:#1a1a25; font-weight:bold; }
    .totals-card .row .v.disc { color:#dc2626; }

    .grand-total {
        background:<?= $primary ?>; color:#fff;
        border-radius:5pt; padding:10pt 14pt; margin-top:6pt;
    }
    .grand-total .lbl { font-size:7.5pt; letter-spacing:1.5pt; text-transform:uppercase; opacity:.85; font-weight:bold; }
    .grand-total .val { font-size:18pt; font-family:dejavusansmono, monospace; font-weight:bold; margin-top:1pt; }

    .terms {
        margin-top:8mm; border-top:1pt solid #ececef; padding-top:6mm;
    }
    .terms-h {
        font-size:7pt; letter-spacing:1.5pt; text-transform:uppercase;
        color:<?= $primary ?>; font-weight:bold; margin-bottom:2pt;
    }
    .terms-body { font-size:8pt; color:#475569; line-height:1.55; }

    .signature {
        margin-top:12mm; padding-top:10mm; border-top:1pt dashed #94a3b8;
        max-width:50%;
    }
    .signature .line { border-bottom:0.6pt solid #1a1a25; height:14mm; }
    .signature .name { font-size:9pt; font-weight:bold; color:#1a1a25; margin-top:3pt; }
    .signature .role { font-size:7.5pt; color:#6b6b78; }

    .accepted-badge {
        background:#ecfdf5; border:1pt solid #16a34a; border-radius:6pt;
        padding:8pt 12pt; color:#15803d; font-size:8.5pt; margin-top:6mm;
    }
    .accepted-badge strong { color:#14532d; }

    .bank-card {
        margin-top:6mm; background:#fafafb; border:0.6pt solid #ececef;
        border-left:3pt solid <?= $accent ?>; border-radius:5pt;
        padding:9pt 12pt; font-size:8pt; color:#475569; line-height:1.55;
    }
    .bank-card .h {
        font-size:7pt; letter-spacing:1.4pt; text-transform:uppercase;
        color:<?= $accent ?>; font-weight:bold; margin-bottom:3pt;
    }
</style>
</head>
<body>

<htmlpageheader name="page-header">
    <table class="ph-wrap" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:55%">
                <?php if ($logo): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" class="ph-logo" alt="logo">
                <?php else: ?>
                    <div class="ph-noimg"><?= strtoupper(substr($bizName, 0, 1)) ?></div>
                <?php endif; ?>
                <div style="margin-top:4pt">
                    <div class="biz-name"><?= htmlspecialchars($bizName) ?></div>
                    <div class="biz-meta">
                        <?php if ($bizDoc): ?>RNC/ID: <?= htmlspecialchars($bizDoc) ?><br><?php endif; ?>
                        <?php if ($bizAddress): ?><?= htmlspecialchars($bizAddress) ?><br><?php endif; ?>
                        <?php if ($bizPhone): ?>Tel: <?= htmlspecialchars($bizPhone) ?> <?php endif; ?>
                        <?php if ($bizEmail): ?>· <?= htmlspecialchars($bizEmail) ?><?php endif; ?>
                        <?php if ($bizWeb): ?><br><?= htmlspecialchars($bizWeb) ?><?php endif; ?>
                    </div>
                </div>
            </td>
            <td class="doc-stamp" style="width:45%">
                <div class="doc-eyebrow">COTIZACIÓN</div>
                <div class="doc-title">Quotation</div>
                <div class="doc-code"><?= htmlspecialchars($quote['code']) ?></div>
                <div class="doc-status"><?= htmlspecialchars($stLabel) ?></div>
            </td>
        </tr>
    </table>
    <div class="ph-bar"></div>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table class="pf-wrap" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:60%"><?= htmlspecialchars($settings['footer_text'] ?: ($bizName . ' · Cotización ' . $quote['code'])) ?></td>
            <td style="width:40%; text-align:right">Página {PAGENO} de {nbpg}</td>
        </tr>
    </table>
</htmlpagefooter>

<!-- META + CLIENT -->
<table class="meta-row" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width:62%; padding-right:8pt">
            <div class="client-card">
                <div class="h">CLIENTE</div>
                <div class="name"><?= htmlspecialchars($quote['client_name']) ?></div>
                <div class="meta">
                    <?php if (!empty($quote['client_doc'])): ?>RNC/ID: <?= htmlspecialchars($quote['client_doc']) ?><br><?php endif; ?>
                    <?php if (!empty($quote['client_contact'])): ?>Atn: <?= htmlspecialchars($quote['client_contact']) ?><br><?php endif; ?>
                    <?php if (!empty($quote['client_email'])): ?><?= htmlspecialchars($quote['client_email']) ?><?php endif; ?>
                    <?php if (!empty($quote['client_phone'])): ?> · <?= htmlspecialchars($quote['client_phone']) ?><?php endif; ?>
                    <?php if (!empty($quote['client_address'])): ?><br><?= htmlspecialchars($quote['client_address']) ?><?php endif; ?>
                </div>
            </div>
        </td>
        <td style="width:38%">
            <div class="meta-card">
                <table cellspacing="0" cellpadding="0" style="width:100%">
                    <tr>
                        <td style="padding-bottom:4pt">
                            <div class="lbl">Emitida</div>
                            <div class="val"><?= htmlspecialchars((string)($quote['issued_at'] ?: date('Y-m-d'))) ?></div>
                        </td>
                        <td style="padding-bottom:4pt; text-align:right">
                            <div class="lbl">Válida hasta</div>
                            <div class="val" style="color:<?= $accent ?>"><?= htmlspecialchars((string)$quote['valid_until']) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="lbl">Moneda</div>
                            <div class="sub"><?= htmlspecialchars($quote['currency']) ?> (<?= htmlspecialchars($sym) ?>)</div>
                        </td>
                        <?php if (!empty($quote['title'])): ?>
                        <td style="text-align:right">
                            <div class="lbl">Asunto</div>
                            <div class="sub"><?= htmlspecialchars($quote['title']) ?></div>
                        </td>
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

<!-- ITEMS TABLE -->
<table class="items" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="width:6%">#</th>
            <th style="width:48%">Descripción</th>
            <th class="c" style="width:10%">Cant.</th>
            <th class="r" style="width:13%">Precio</th>
            <th class="c" style="width:8%">Desc.</th>
            <th class="r" style="width:15%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $it):
            $unitLabel = $it['unit_label'] ?: $it['unit'];
        ?>
            <tr <?= $i % 2 === 1 ? 'class="zebra"' : '' ?>>
                <td class="c"><?= $i + 1 ?></td>
                <td>
                    <div class="it-title"><?= htmlspecialchars($it['title']) ?></div>
                    <?php if (!empty($it['description'])): ?>
                        <div class="it-desc"><?= nlbr($it['description']) ?></div>
                    <?php endif; ?>
                    <?php if ((int)$it['is_taxable'] === 0): ?>
                        <div class="it-disc" style="color:#0ea5e9">Exento de impuestos</div>
                    <?php endif; ?>
                </td>
                <td class="c"><?= fmt($it['quantity'], (float)$it['quantity'] == (int)$it['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($unitLabel) ?></td>
                <td class="r"><?= htmlspecialchars($sym) ?> <?= fmt($it['unit_price'], $decimals) ?></td>
                <td class="c"><?= (float)$it['discount_pct'] > 0 ? fmt($it['discount_pct'], 1) . '%' : '—' ?></td>
                <td class="r"><strong><?= htmlspecialchars($sym) ?> <?= fmt($it['line_subtotal'], $decimals) ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- TOTALES -->
<table class="totals-wrap" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width:55%; padding-right:8pt">
            <?php if (!empty($settings['bank_info'])): ?>
                <div class="bank-card">
                    <div class="h">DATOS DE PAGO</div>
                    <?= nlbr($settings['bank_info']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($quote['notes'])): ?>
                <div class="bank-card" style="border-left-color:#94a3b8">
                    <div class="h" style="color:#475569">NOTAS</div>
                    <?= nlbr($quote['notes']) ?>
                </div>
            <?php endif; ?>
        </td>
        <td style="width:45%">
            <div class="totals-card">
                <div class="row lbl">Subtotal <span class="v"><?= htmlspecialchars($sym) ?> <?= fmt($quote['subtotal'], $decimals) ?></span></div>
                <?php if ((float)$quote['discount_amount'] > 0): ?>
                    <div class="row lbl">Descuento (<?= fmt($quote['discount_pct'], 1) ?>%) <span class="v disc">− <?= htmlspecialchars($sym) ?> <?= fmt($quote['discount_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <?php if ((float)$quote['tax_rate'] > 0): ?>
                    <div class="row lbl"><?= htmlspecialchars($quote['tax_label']) ?> (<?= fmt($quote['tax_rate'], 1) ?>%) <span class="v"><?= htmlspecialchars($sym) ?> <?= fmt($quote['tax_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <?php if ((float)$quote['shipping_amount'] > 0): ?>
                    <div class="row lbl">Envío / logística <span class="v"><?= htmlspecialchars($sym) ?> <?= fmt($quote['shipping_amount'], $decimals) ?></span></div>
                <?php endif; ?>
                <?php if ((float)$quote['other_charges_amount'] > 0): ?>
                    <div class="row lbl"><?= htmlspecialchars($quote['other_charges_label'] ?: 'Otros cargos') ?> <span class="v"><?= htmlspecialchars($sym) ?> <?= fmt($quote['other_charges_amount'], $decimals) ?></span></div>
                <?php endif; ?>
            </div>

            <div class="grand-total">
                <div class="lbl">TOTAL A PAGAR</div>
                <div class="val"><?= htmlspecialchars($sym) ?> <?= fmt($quote['total'], $decimals) ?></div>
            </div>
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
    <div class="accepted-badge">
        ✓ <strong>Cotización aceptada</strong>
        <?php if (!empty($quote['accepted_by_name'])): ?> por <strong><?= htmlspecialchars($quote['accepted_by_name']) ?></strong><?php endif; ?>
        el <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($quote['accepted_at']))) ?></strong>.
    </div>
<?php elseif ((int)($settings['show_signature'] ?? 1) === 1): ?>
    <div class="signature">
        <div class="line"></div>
        <div class="name"><?= htmlspecialchars($settings['signature_name'] ?: $bizName) ?></div>
        <?php if (!empty($settings['signature_role'])): ?>
            <div class="role"><?= htmlspecialchars($settings['signature_role']) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>
