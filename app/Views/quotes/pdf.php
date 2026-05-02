<?php
/**
 * Plantilla PDF profesional de cotización (dompdf 3.x)
 * Diseño premium estilo factura corporativa moderna.
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
    'draft' => '#64748b', 'sent' => '#2563eb', 'viewed' => '#0891b2',
    'accepted' => '#16a34a', 'rejected' => '#dc2626', 'expired' => '#d97706',
    'revised' => '#7c3aed', 'converted' => '#16a34a',
];
$stColor = $statusColors[$quote['status']] ?? '#64748b';
$stLabel = $statusLabels[$quote['status']] ?? strtoupper($quote['status']);

function fmt($v, $d = 2) { return number_format((float)$v, $d, '.', ','); }
function nlbr($t) { return nl2br(htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8')); }
function ee($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$codeJson = json_encode((string)$quote['code']);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotización <?= ee($quote['code']) ?></title>
<style>
    /* Margenes de página: dompdf respeta perfectamente y evita overflow */
    @page { margin: 14mm 14mm 22mm 14mm; }

    * { margin: 0; padding: 0; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #0f172a;
        font-size: 9.5pt;
        line-height: 1.5;
    }

    /* ─── HERO HEADER ────────────────────────────────────── */
    .hero {
        width: 100%;
        background: <?= $primary ?>;
        border-radius: 8px;
    }
    .hero td { padding: 16px 22px; vertical-align: middle; color: #ffffff; }
    .hero .logo-cell {
        background: #ffffff;
        border-radius: 8px 0 0 8px;
        text-align: center;
        width: 32%;
    }
    .hero .logo-cell img { max-height: 50px; max-width: 130px; }
    .hero .logo-cell .ini {
        font-size: 28pt; font-weight: bold; color: <?= $primary ?>;
        line-height: 1;
    }
    .hero .stamp-cell {
        text-align: right;
        padding: 14px 22px;
    }
    .hero .eyebrow {
        font-size: 7.5pt; letter-spacing: 2pt;
        text-transform: uppercase; opacity: 0.85;
        font-weight: bold;
    }
    .hero .title {
        font-size: 18pt; font-weight: bold;
        letter-spacing: -0.4pt; line-height: 1.05;
        margin-top: 1px;
    }
    .hero .code {
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 9pt; opacity: 0.92;
        margin-top: 4px;
    }
    .hero .stamp-cell .status {
        display: inline-block;
        background: rgba(0,0,0,0.22);
        padding: 3px 9px;
        border-radius: 9pt;
        font-size: 7.5pt;
        letter-spacing: 1.2pt;
        font-weight: bold;
        margin-top: 6px;
    }

    /* ─── BIZ INFO + META ─────────────────────────────────── */
    .biz-row { width: 100%; margin-top: 8mm; }
    .biz-row td { vertical-align: top; padding: 0; }

    .biz-block .name {
        font-size: 12.5pt; font-weight: bold;
        color: #0f172a; margin-bottom: 3px;
    }
    .biz-block .meta {
        font-size: 8.5pt; color: #64748b; line-height: 1.65;
    }

    .meta-grid {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
    }
    .meta-grid table { width: 100%; }
    .meta-grid td {
        padding: 9px 12px;
        vertical-align: top;
        border-bottom: 1px solid #e2e8f0;
    }
    .meta-grid tr:last-child td { border-bottom: 0; }
    .meta-grid td.split { border-right: 1px solid #e2e8f0; }
    .meta-grid .lbl {
        font-size: 7pt; letter-spacing: 1.4pt;
        text-transform: uppercase;
        color: #94a3b8; font-weight: bold;
    }
    .meta-grid .val {
        font-size: 9.5pt; color: #0f172a;
        font-weight: bold; margin-top: 2px;
    }
    .meta-grid .val.green { color: <?= $accent ?>; }
    .meta-grid .sub {
        font-size: 8pt; color: #475569; margin-top: 1px;
    }

    /* ─── CLIENTE ─────────────────────────────────────────── */
    .client-section { margin-top: 8mm; }
    .client-h {
        font-size: 7.5pt; letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: <?= $primary ?>; font-weight: bold;
        margin-bottom: 5px;
    }
    .client-card {
        background: #faf5ff;
        border: 1px solid #e9d5ff;
        border-left: 4px solid <?= $primary ?>;
        padding: 14px 18px;
        border-radius: 6px;
    }
    .client-card .name {
        font-size: 13pt; font-weight: bold;
        color: #0f172a; line-height: 1.2;
    }
    .client-card .meta {
        font-size: 9pt; color: #475569;
        margin-top: 6px; line-height: 1.65;
    }
    .client-card .meta strong { color: #1e293b; }

    .intro {
        margin-top: 7mm;
        font-size: 9.5pt; color: #475569; line-height: 1.65;
    }

    /* ─── ITEMS TABLE ──────────────────────────────────── */
    .items {
        width: 100%; margin-top: 6mm;
        border-collapse: collapse;
    }
    .items thead th {
        background: #0f172a; color: #ffffff;
        font-size: 7.5pt; text-transform: uppercase;
        letter-spacing: 0.8pt; font-weight: bold;
        text-align: left; padding: 10px 10px;
    }
    .items thead th.r { text-align: right; }
    .items thead th.c { text-align: center; }
    .items tbody td {
        padding: 11px 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 9pt; vertical-align: top;
        color: #1e293b;
    }
    .items tbody td.r {
        text-align: right;
        font-family: 'DejaVu Sans Mono', monospace;
        white-space: nowrap;
    }
    .items tbody td.c { text-align: center; }
    .items tbody tr.zebra td { background: #f8fafc; }
    .items tbody tr:last-child td { border-bottom: 2px solid #0f172a; }

    .it-num { color: #94a3b8; font-weight: bold; font-size: 9pt; }
    .it-title { font-weight: bold; color: #0f172a; font-size: 9.5pt; }
    .it-desc { color: #64748b; font-size: 8.5pt; margin-top: 3px; line-height: 1.5; }
    .it-exempt {
        color: #0891b2; font-size: 7.5pt; margin-top: 4px;
        font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.6pt;
    }
    .it-unit { color: #94a3b8; font-size: 8pt; }

    /* ─── BOTTOM (notas + totales) ────────────────────── */
    .bottom { width: 100%; margin-top: 6mm; }
    .bottom > tbody > tr > td { vertical-align: top; padding: 0; }

    .bank-card {
        background: #f0fdf4;
        border-left: 4px solid <?= $accent ?>;
        padding: 12px 14px;
        border-radius: 4px;
        font-size: 8.5pt; color: #1f2937;
        line-height: 1.6;
    }
    .bank-card.notes { background: #fefce8; border-left-color: #eab308; }
    .bank-card .h {
        font-size: 7pt; letter-spacing: 1.4pt;
        text-transform: uppercase;
        color: <?= $accent ?>; font-weight: bold;
        margin-bottom: 5px;
    }
    .bank-card.notes .h { color: #a16207; }

    .totals-box {
        width: 100%; border-collapse: collapse;
    }
    .totals-box td {
        padding: 8px 14px; font-size: 9.5pt;
        border-bottom: 1px solid #e2e8f0;
    }
    .totals-box tr:last-child td { border-bottom: 0; }
    .totals-box td.l { color: #475569; }
    .totals-box td.v {
        text-align: right;
        font-family: 'DejaVu Sans Mono', monospace;
        font-weight: bold; color: #0f172a;
        white-space: nowrap;
    }
    .totals-box .disc { color: #dc2626; }

    .grand {
        width: 100%; margin-top: 8px;
        background: <?= $primary ?>;
        border-radius: 6px;
    }
    .grand td { padding: 14px 18px; color: #ffffff; }
    .grand .lbl {
        font-size: 7.5pt; letter-spacing: 2pt;
        text-transform: uppercase; font-weight: bold;
        opacity: 0.92;
    }
    .grand .val {
        font-size: 18pt; font-weight: bold;
        font-family: 'DejaVu Sans Mono', monospace;
        line-height: 1.15; margin-top: 3px;
        white-space: nowrap;
    }

    /* ─── TÉRMINOS ───────────────────────────────────── */
    .terms {
        margin-top: 9mm;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 14px 18px;
    }
    .terms-h {
        font-size: 7.5pt; letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: <?= $primary ?>; font-weight: bold;
        margin-bottom: 6px;
    }
    .terms-body {
        font-size: 8.5pt; color: #475569;
        line-height: 1.7;
    }

    /* ─── FIRMA / ACEPTADA ───────────────────────────── */
    .accepted-banner {
        margin-top: 9mm;
        background: #ecfdf5;
        border: 2px solid <?= $accent ?>;
        border-radius: 6px;
        padding: 14px 18px;
    }
    .accepted-banner .h {
        font-size: 11pt; font-weight: bold; color: #14532d;
    }
    .accepted-banner .sub {
        font-size: 8.5pt; color: #166534; margin-top: 3px;
    }

    .signature-table { width: 100%; margin-top: 14mm; }
    .signature-table td { vertical-align: bottom; padding: 0; }
    .sig-box { padding-right: 20px; }
    .sig-line { border-top: 1px solid #0f172a; padding-top: 6px; }
    .sig-name { font-size: 9.5pt; font-weight: bold; color: #0f172a; }
    .sig-role { font-size: 8pt; color: #64748b; margin-top: 1px; }
</style>
</head>
<body>

<!-- HERO -->
<table class="hero">
    <tr>
        <td class="logo-cell">
            <?php if ($logo): ?>
                <img src="<?= ee($logo) ?>" alt="logo">
            <?php else: ?>
                <div class="ini"><?= ee(strtoupper(substr($bizName, 0, 1))) ?></div>
            <?php endif; ?>
        </td>
        <td class="stamp-cell">
            <div class="eyebrow">COTIZACIÓN</div>
            <div class="title">Quotation</div>
            <div class="code"><?= ee($quote['code']) ?></div>
            <span class="status"><?= ee($stLabel) ?></span>
        </td>
    </tr>
</table>

<!-- BIZ INFO + META -->
<table class="biz-row">
    <tr>
        <td style="width:55%; padding-right:14px">
            <div class="biz-block">
                <div class="name"><?= ee($bizName) ?></div>
                <div class="meta">
                    <?php if ($bizDoc): ?>RNC/ID: <?= ee($bizDoc) ?><br><?php endif; ?>
                    <?php if ($bizAddress): ?><?= ee($bizAddress) ?><br><?php endif; ?>
                    <?php
                        $cb = [];
                        if ($bizPhone) $cb[] = $bizPhone;
                        if ($bizEmail) $cb[] = $bizEmail;
                        if ($cb) echo ee(implode(' · ', $cb)) . '<br>';
                    ?>
                    <?php if ($bizWeb): ?><?= ee($bizWeb) ?><?php endif; ?>
                </div>
            </div>
        </td>
        <td style="width:45%">
            <div class="meta-grid">
                <table>
                    <tr>
                        <td class="split" style="width:50%">
                            <div class="lbl">Emitida</div>
                            <div class="val"><?= ee(date('d/m/Y', strtotime((string)($quote['issued_at'] ?: 'now')))) ?></div>
                        </td>
                        <td style="width:50%">
                            <div class="lbl">Válida hasta</div>
                            <div class="val green"><?= ee(date('d/m/Y', strtotime((string)$quote['valid_until']))) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="split">
                            <div class="lbl">Moneda</div>
                            <div class="val"><?= ee($quote['currency']) ?> <span style="color:#94a3b8;font-weight:normal">(<?= ee($sym) ?>)</span></div>
                        </td>
                        <td>
                            <div class="lbl">Total</div>
                            <div class="val green"><?= ee($sym) ?> <?= fmt($quote['total'], $decimals) ?></div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- CLIENTE -->
<div class="client-section">
    <table style="width:100%">
        <tr>
            <td>
                <div class="client-h">FACTURAR A · CLIENTE</div>
                <div class="client-card">
                    <div class="name"><?= ee($quote['client_name']) ?></div>
                    <div class="meta">
                        <?php
                            $line1 = [];
                            if (!empty($quote['client_doc'])) $line1[] = 'RNC/ID: <strong>' . ee($quote['client_doc']) . '</strong>';
                            if (!empty($quote['client_contact'])) $line1[] = 'Atn: <strong>' . ee($quote['client_contact']) . '</strong>';
                            if ($line1) echo implode(' &nbsp;·&nbsp; ', $line1) . '<br>';

                            $line2 = [];
                            if (!empty($quote['client_email'])) $line2[] = ee($quote['client_email']);
                            if (!empty($quote['client_phone'])) $line2[] = ee($quote['client_phone']);
                            if ($line2) echo implode(' &nbsp;·&nbsp; ', $line2) . '<br>';
                        ?>
                        <?php if (!empty($quote['client_address'])): ?><?= ee($quote['client_address']) ?><?php endif; ?>
                        <?php if (!empty($quote['title'])): ?>
                            <br><span style="color:#94a3b8;font-size:7pt;letter-spacing:1.4pt;text-transform:uppercase;font-weight:bold">ASUNTO</span>
                            &nbsp; <strong style="font-size:9.5pt;color:#0f172a"><?= ee($quote['title']) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<?php if (!empty($quote['intro'])): ?>
    <div class="intro"><?= nlbr($quote['intro']) ?></div>
<?php endif; ?>

<!-- ITEMS -->
<table class="items">
    <thead>
        <tr>
            <th class="c" style="width:5%">#</th>
            <th style="width:46%">Descripción</th>
            <th class="c" style="width:11%">Cantidad</th>
            <th class="r" style="width:14%">Precio Unit.</th>
            <th class="c" style="width:8%">Desc.</th>
            <th class="r" style="width:16%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $it):
            $unitLabel = $it['unit_label'] ?: $it['unit'];
            $isInt = (float)$it['quantity'] == (int)$it['quantity'];
        ?>
            <tr <?= $i % 2 === 1 ? 'class="zebra"' : '' ?>>
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
                        ? '<span style="color:#dc2626;font-weight:bold">' . fmt($it['discount_pct'], 1) . '%</span>'
                        : '<span style="color:#cbd5e1">—</span>' ?>
                </td>
                <td class="r" style="font-weight:bold; font-size:9.5pt"><?= ee($sym) ?> <?= fmt($it['line_subtotal'], $decimals) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- BOTTOM: notas + totales -->
<table class="bottom">
    <tr>
        <td style="width:50%; padding-right:14px">
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
        <td style="width:50%">
            <table class="totals-box">
                <tr>
                    <td class="l">Subtotal</td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['subtotal'], $decimals) ?></td>
                </tr>
                <?php if ((float)$quote['discount_amount'] > 0): ?>
                <tr>
                    <td class="l">Descuento <span style="color:#94a3b8">(<?= fmt($quote['discount_pct'], 1) ?>%)</span></td>
                    <td class="v disc">− <?= ee($sym) ?> <?= fmt($quote['discount_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$quote['tax_rate'] > 0): ?>
                <tr>
                    <td class="l"><?= ee($quote['tax_label']) ?> <span style="color:#94a3b8">(<?= fmt($quote['tax_rate'], 1) ?>%)</span></td>
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
                    <td class="l"><?= ee($quote['other_charges_label'] ?: 'Otros cargos') ?></td>
                    <td class="v"><?= ee($sym) ?> <?= fmt($quote['other_charges_amount'], $decimals) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <table class="grand">
                <tr>
                    <td>
                        <div class="lbl">TOTAL A PAGAR</div>
                        <div class="val"><?= ee($sym) ?> <?= fmt($quote['total'], $decimals) ?></div>
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
        <div class="h">✓ Cotización aceptada</div>
        <div class="sub">
            <?php if (!empty($quote['accepted_by_name'])): ?>Aceptada por <strong><?= ee($quote['accepted_by_name']) ?></strong><?php endif; ?>
            <?php if (!empty($quote['accepted_by_email'])): ?> &lt;<?= ee($quote['accepted_by_email']) ?>&gt;<?php endif; ?>
            el <strong><?= ee(date('d/m/Y H:i', strtotime($quote['accepted_at']))) ?></strong>.
        </div>
    </div>
<?php elseif ((int)($settings['show_signature'] ?? 1) === 1): ?>
    <table class="signature-table">
        <tr>
            <td style="width:50%" class="sig-box">
                <div style="height:40px"></div>
                <div class="sig-line">
                    <div class="sig-name"><?= ee($settings['signature_name'] ?: $bizName) ?></div>
                    <?php if (!empty($settings['signature_role'])): ?>
                        <div class="sig-role"><?= ee($settings['signature_role']) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width:50%"></td>
        </tr>
    </table>
<?php endif; ?>

<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
    $size = 7.5;
    $w = $pdf->get_width();
    $h = $pdf->get_height();
    $code = json_decode('<?= $codeJson ?>');
    $rightTpl = $code . "  ·  Página {PAGE_NUM} de {PAGE_COUNT}";
    $rightSample = $code . "  ·  Página 99 de 99";
    $rightW = $fontMetrics->getTextWidth($rightSample, $font, $size);
    $marginRight = 14 * 2.834;
    $x = $w - $marginRight - $rightW;
    $y = $h - 28;
    $pdf->page_text($x, $y, $rightTpl, $font, $size, [0.58, 0.64, 0.72]);
    $footerLeft = "<?= addslashes((string)($settings['footer_text'] ?: $bizName)) ?>";
    $pdf->page_text($marginRight, $y, $footerLeft, $font, $size, [0.58, 0.64, 0.72]);
    $pdf->line($marginRight, $y - 6, $w - $marginRight, $y - 6, [0.89, 0.91, 0.94], 0.5);
}
</script>

</body>
</html>
