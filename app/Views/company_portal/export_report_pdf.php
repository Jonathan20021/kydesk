<?php
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$brandDark = '#5a3aff';
$brandLight = '#f3f0ff';

$prLabels = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$stLabels = ['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'];
$prColors = ['urgent'=>'#dc2626','high'=>'#f59e0b','medium'=>'#3b82f6','low'=>'#94a3b8'];
$stColors = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','on_hold'=>'#94a3b8','resolved'=>'#16a34a','closed'=>'#475569'];

/* ─── Helpers ─── */
$delta = function ($curr, $prev) {
    $curr = (float)$curr; $prev = (float)$prev;
    if ($prev <= 0) return ['label' => $curr > 0 ? 'nuevo' : '—', 'color' => '#94a3b8', 'arrow' => '→'];
    $pct = (($curr - $prev) / $prev) * 100;
    $sign = $pct >= 0 ? '+' : '';
    return [
        'label' => $sign . round($pct) . '%',
        'color' => $pct >= 0 ? '#16a34a' : '#dc2626',
        'arrow' => $pct >= 0 ? '▲' : '▼',
    ];
};
$bar = function ($value, $max) {
    if ($max <= 0) return 0;
    return max(2, round(($value / $max) * 100));
};

/* ─── SVG Donut chart ─── */
$svgDonut = function (array $data, array $colors, int $size = 110, int $thickness = 22) use ($brand) {
    $total = array_sum($data);
    if ($total <= 0) {
        $cx = $size / 2;
        return '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">'
            . '<circle cx="' . $cx . '" cy="' . $cx . '" r="' . ($cx - $thickness/2) . '" fill="none" stroke="#f0f0f5" stroke-width="' . $thickness . '"/>'
            . '<text x="' . $cx . '" y="' . ($cx + 4) . '" text-anchor="middle" font-size="11" fill="#94a3b8" font-family="DejaVuSans">Sin datos</text>'
            . '</svg>';
    }
    $cx = $size / 2;
    $cy = $size / 2;
    $r = $cx - $thickness / 2;
    $circumference = 2 * M_PI * $r;
    $offset = 0;
    $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg" style="transform:rotate(-90deg)">';
    // Background ring
    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="#f0f0f5" stroke-width="' . $thickness . '"/>';
    foreach ($data as $i => $value) {
        if ($value <= 0) continue;
        $portion = $value / $total;
        $dash = $portion * $circumference;
        $color = $colors[$i] ?? $brand;
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '"'
              . ' fill="none" stroke="' . htmlspecialchars($color) . '"'
              . ' stroke-width="' . $thickness . '"'
              . ' stroke-dasharray="' . $dash . ' ' . ($circumference - $dash) . '"'
              . ' stroke-dashoffset="' . (-$offset) . '"'
              . ' stroke-linecap="butt"/>';
        $offset += $dash;
    }
    $svg .= '</svg>';
    return $svg;
};

/* ─── Datos y cálculos ─── */
$totalN = (int)($totals['total'] ?? 0);
$resolvedN = (int)($totals['resolved'] ?? 0);
$openN = (int)($totals['open'] ?? 0);
$breachedN = (int)($totals['breached'] ?? 0);
$resolvedPct = $totalN > 0 ? round(($resolvedN / $totalN) * 100) : 0;
$reportCode = 'RPT-' . strtoupper(dechex(crc32($tenantPublic->slug . '·' . $company['id'] . '·' . $rangeDays . '·' . date('Ymd'))));

/* Insights automáticos */
$insights = [];
if ($totalN > 0) {
    if (!empty($byPriority)) {
        $top = $byPriority[0];
        foreach ($byPriority as $p) if ((int)$p['n'] > (int)$top['n']) $top = $p;
        $insights[] = 'La prioridad <strong>' . ($prLabels[$top['priority']] ?? $top['priority']) . '</strong> concentró el ' . round(((int)$top['n'] / $totalN) * 100) . '% de los tickets (' . (int)$top['n'] . ' de ' . $totalN . ').';
    }
    if (!empty($byCategory)) {
        $topCat = $byCategory[0];
        $insights[] = 'La categoría más solicitada fue <strong>' . htmlspecialchars($topCat['name']) . '</strong> con ' . (int)$topCat['n'] . ' tickets.';
    }
    if ($breachedN > 0) {
        $insights[] = 'Se incumplió el SLA en <strong>' . $breachedN . '</strong> ticket' . ($breachedN === 1 ? '' : 's') . ' (' . round(($breachedN / $totalN) * 100) . '% del total).';
    }
    if (!empty($totals['resolve_t']) && !empty($prev['resolve_t'])) {
        $diff = round((float)$totals['resolve_t'] - (float)$prev['resolve_t'], 1);
        if (abs($diff) >= 0.5) {
            $verb = $diff < 0 ? 'mejoró' : 'empeoró';
            $insights[] = 'El tiempo de resolución <strong>' . $verb . '</strong> en ' . abs($diff) . 'h respecto del período anterior.';
        }
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte ejecutivo · <?= $e($company['name']) ?></title>
<style>
    @page coverpage { margin: 0mm; }
    @page mainpage { margin: 22mm 14mm 18mm 14mm; header: page-header; footer: page-footer; }

    body { font-family: dejavusans, sans-serif; color: #1a1a25; font-size: 9.5pt; line-height: 1.5; }

    /* ─── Cover ─── */
    .cover-wrap { width: 210mm; height: 297mm; position: relative; padding: 0; margin: 0; background: <?= $brand ?>; color: #fff; }
    .cover-inner { padding: 28mm 22mm 22mm 22mm; }
    .cover-logo { width: 56pt; height: 56pt; background: rgba(255,255,255,0.15); color: #fff; font-weight: bold; font-size: 28pt; text-align: center; line-height: 56pt; border-radius: 14pt; }
    .cover-tag { font-size: 8.5pt; letter-spacing: 4pt; text-transform: uppercase; opacity: 0.85; margin-top: 32mm; }
    .cover-title { font-size: 38pt; font-weight: bold; line-height: 1.05; letter-spacing: -1pt; margin: 4pt 0; }
    .cover-sub { font-size: 14pt; opacity: 0.92; margin-top: 6pt; max-width: 130mm; }
    .cover-rule { width: 40pt; height: 3pt; background: #fff; opacity: 0.45; margin: 14mm 0 8mm 0; }
    .cover-meta { font-size: 9pt; opacity: 0.85; line-height: 1.9; }
    .cover-meta strong { display: inline-block; min-width: 90pt; opacity: 0.7; font-weight: normal; letter-spacing: 1pt; text-transform: uppercase; font-size: 7.5pt; }
    .cover-foot { position: absolute; bottom: 18mm; left: 22mm; right: 22mm; font-size: 8pt; opacity: 0.7; padding-top: 8pt; border-top: 0.5pt solid rgba(255,255,255,0.25); }
    .cover-code { font-family: dejavusansmono, monospace; letter-spacing: 1.5pt; }

    /* ─── Page header / footer ─── */
    .page-header { width: 100%; }
    .page-header td { padding: 0; vertical-align: middle; font-size: 7.5pt; color: #94a3b8; border-bottom: 0.5pt solid #ececef; padding-bottom: 4mm; }
    .page-header .logo { width: 18pt; height: 18pt; background: <?= $brand ?>; color: #fff; text-align: center; line-height: 18pt; font-weight: bold; font-size: 10pt; border-radius: 3pt; }
    .page-header .ttl { font-weight: bold; color: #1a1a25; font-size: 8pt; }
    .page-footer { width: 100%; padding-top: 3mm; border-top: 0.5pt solid #ececef; font-size: 7pt; color: #94a3b8; }
    .page-footer .code-mini { font-family: dejavusansmono, monospace; }

    /* ─── Section titles ─── */
    .section-eyebrow { font-size: 7.5pt; letter-spacing: 2pt; text-transform: uppercase; color: <?= $brand ?>; font-weight: bold; margin-top: 6mm; }
    .section-title { font-size: 18pt; color: #1a1a25; margin: 1pt 0 2mm 0; font-weight: bold; letter-spacing: -0.4pt; line-height: 1.1; }
    .section-lead { font-size: 9.5pt; color: #475569; margin-bottom: 6mm; max-width: 160mm; line-height: 1.55; }

    /* ─── Insight box ─── */
    .insights { background: <?= $brandLight ?>; border-left: 3pt solid <?= $brand ?>; border-radius: 0 5pt 5pt 0; padding: 10pt 14pt; margin: 4mm 0 8mm 0; }
    .insights .h { font-size: 7.5pt; letter-spacing: 1.5pt; text-transform: uppercase; color: <?= $brandDark ?>; font-weight: bold; margin-bottom: 4pt; }
    .insights ul { margin: 0; padding: 0 0 0 14pt; }
    .insights li { font-size: 9pt; color: #1a1a25; line-height: 1.7; margin-bottom: 2pt; }

    /* ─── KPIs hero ─── */
    table.kpis-hero { width: 100%; border-collapse: separate; border-spacing: 5pt 0; margin-bottom: 6mm; }
    table.kpis-hero td { width: 33.33%; padding: 0; vertical-align: top; }
    .kpi-hero { background: #fff; border: 0.6pt solid #ececef; border-radius: 6pt; padding: 12pt 14pt 14pt 14pt; }
    .kpi-hero .lbl { font-size: 7pt; text-transform: uppercase; color: #94a3b8; letter-spacing: 1pt; font-weight: bold; }
    .kpi-hero .val { font-size: 30pt; font-weight: bold; color: #1a1a25; line-height: 1; margin-top: 4pt; letter-spacing: -1pt; }
    .kpi-hero .sub { font-size: 8pt; color: #6b6b78; margin-top: 4pt; }
    .kpi-hero .chip { display: inline-block; padding: 1pt 5pt; border-radius: 4pt; font-size: 7pt; font-weight: bold; margin-top: 2pt; }

    /* ─── KPIs grid ─── */
    table.kpis { width: 100%; border-collapse: separate; border-spacing: 4pt 0; margin-bottom: 4mm; }
    table.kpis td { width: 25%; padding: 0; vertical-align: top; }
    .kpi { background: #fff; border: 0.6pt solid #ececef; border-radius: 5pt; padding: 8pt 10pt; }
    .kpi.accent { border-top: 2pt solid <?= $brand ?>; }
    .kpi.green { border-top: 2pt solid #16a34a; }
    .kpi.amber { border-top: 2pt solid #f59e0b; }
    .kpi.red { border-top: 2pt solid #dc2626; }
    .kpi .lbl { font-size: 6.5pt; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.7pt; font-weight: bold; }
    .kpi .val { font-size: 18pt; font-weight: bold; color: #1a1a25; margin-top: 2pt; line-height: 1.05; letter-spacing: -0.5pt; }
    .kpi .delta { font-size: 7.5pt; color: #6b6b78; margin-top: 3pt; }

    /* ─── Donut sections ─── */
    table.donuts { width: 100%; border-collapse: separate; border-spacing: 6pt 0; }
    table.donuts > tr > td { width: 50%; padding: 0; vertical-align: top; }
    .donut-card { border: 0.6pt solid #ececef; border-radius: 6pt; padding: 10pt 12pt; background: #fff; }
    .donut-card h3 { font-size: 10pt; color: #1a1a25; margin: 0 0 6pt 0; font-weight: bold; }
    .donut-grid { width: 100%; }
    .donut-grid td { padding: 0; vertical-align: middle; }
    .donut-grid .legend { font-size: 8pt; padding-left: 8pt; }
    .donut-grid .legend .row { margin-bottom: 4pt; }
    .donut-grid .legend .dot { display: inline-block; width: 8pt; height: 8pt; border-radius: 2pt; margin-right: 5pt; vertical-align: middle; }
    .donut-grid .legend .lbl { font-weight: bold; color: #1a1a25; }
    .donut-grid .legend .num { color: #475569; font-size: 7.5pt; }

    /* ─── Data tables ─── */
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { text-align: left; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.6pt; padding: 7pt 6pt; background: #fafafb; color: #475569; border-bottom: 1pt solid #1a1a25; font-weight: bold; }
    table.data td { padding: 6pt 6pt; border-bottom: 0.5pt solid #ececef; vertical-align: middle; font-size: 9pt; }
    table.data td.right { text-align: right; font-weight: bold; }
    table.data tr.zebra td { background: #fafafb; }
    .muted { color: #6b6b78; font-size: 8pt; }

    /* ─── Bars ─── */
    .barwrap { width: 100%; height: 8pt; background: #f0f0f5; border-radius: 4pt; }
    .barfill { height: 8pt; background: <?= $brand ?>; border-radius: 4pt; }

    /* ─── Pills ─── */
    .pill { display: inline-block; padding: 1.5pt 6pt; border-radius: 8pt; font-size: 7.5pt; font-weight: bold; color: #fff; }

    /* ─── Avatars ─── */
    .avatar { display: inline-block; width: 22pt; height: 22pt; background: <?= $brand ?>; color: #fff; font-weight: bold; font-size: 10pt; text-align: center; line-height: 22pt; border-radius: 11pt; }

    /* ─── Two-column row ─── */
    table.row2 { width: 100%; border-collapse: separate; border-spacing: 8pt 0; }
    table.row2 > tr > td { width: 50%; vertical-align: top; padding: 0; }

    .col-label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 1pt; color: #94a3b8; font-weight: bold; margin-bottom: 4pt; }
</style>
</head>
<body>

<htmlpageheader name="page-header">
    <table class="page-header" cellspacing="0" cellpadding="0"><tr>
        <td style="width:24pt"><div class="logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div></td>
        <td style="padding-left:6pt"><span class="ttl"><?= $e($company['name']) ?></span> · Reporte ejecutivo · Últimos <?= (int)$rangeDays ?> días</td>
        <td style="text-align:right"><span class="muted"><?= $e($generatedAt) ?></span></td>
    </tr></table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table class="page-footer" cellspacing="0" cellpadding="0"><tr>
        <td><?= $e($tenantPublic->name) ?> · <span class="code-mini"><?= $reportCode ?></span></td>
        <td style="text-align:right">Página {PAGENO} de {nbpg}</td>
    </tr></table>
</htmlpagefooter>

<!-- ──────────────── COVER ──────────────── -->
<pagebreak sheet-size="A4" type="NEXT-ODD" />
<sethtmlpageheader name="page-header" value="off" show-this-page="0" />
<sethtmlpagefooter name="page-footer" value="off" show-this-page="0" />
<div class="cover-wrap"><div class="cover-inner">
    <div class="cover-logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div>
    <div class="cover-tag">Reporte ejecutivo</div>
    <div class="cover-title"><?= $e($company['name']) ?></div>
    <div class="cover-sub">Resumen de soporte y métricas operativas</div>
    <div class="cover-rule"></div>
    <div class="cover-meta">
        <div><strong>Período</strong> <?= $e(date('d M Y', strtotime((string)$since))) ?> → <?= $e(date('d M Y')) ?></div>
        <div><strong>Generado</strong> <?= $e($generatedAt) ?></div>
        <div><strong>Por</strong> <?= $e($generatedBy) ?></div>
        <div><strong>Workspace</strong> <?= $e($tenantPublic->name) ?></div>
        <div><strong>Código</strong> <span class="cover-code"><?= $reportCode ?></span></div>
    </div>
    <div class="cover-foot">
        <?= number_format($totalN) ?> tickets · <?= $resolvedPct ?>% resueltos · <?= !empty($totals['resolve_t']) ? round((float)$totals['resolve_t'], 1) . 'h promedio' : '—' ?>
    </div>
</div></div>

<pagebreak resetpagenum="1" />
<sethtmlpageheader name="page-header" value="on" />
<sethtmlpagefooter name="page-footer" value="on" />

<!-- ──────────────── PAGE 2: HIGHLIGHTS + KPIs ──────────────── -->
<div class="section-eyebrow">01 · Resumen</div>
<h1 class="section-title">Lo más importante de los últimos <?= (int)$rangeDays ?> días</h1>
<div class="section-lead">
    <strong><?= $e($company['name']) ?></strong> generó <strong><?= number_format($totalN) ?></strong> ticket<?= $totalN===1?'':'s' ?>,
    de los cuales <strong><?= number_format($resolvedN) ?> (<?= $resolvedPct ?>%)</strong> fueron resueltos
    <?php if (!empty($totals['resolve_t'])): ?>en un tiempo medio de <strong><?= round((float)$totals['resolve_t'], 1) ?>h</strong><?php endif; ?>.
    <?php if ($totals['csat'] !== null): ?>El CSAT promedio se ubicó en <strong><?= round((float)$totals['csat'], 2) ?>/5</strong>.<?php endif; ?>
    <?php if ($breachedN > 0): ?>Se registraron <strong style="color:#dc2626"><?= $breachedN ?> incumplimientos de SLA</strong>.<?php endif; ?>
</div>

<?php if (!empty($insights)): ?>
    <div class="insights">
        <div class="h">Insights del período</div>
        <ul>
            <?php foreach ($insights as $ins): ?><li><?= $ins ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<table class="kpis-hero" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <div class="kpi-hero">
                <div class="lbl">Tickets totales</div>
                <div class="val"><?= number_format($totalN) ?></div>
                <?php $d = $delta($totalN, $prev['total'] ?? 0); ?>
                <div class="sub">vs <?= number_format((int)($prev['total'] ?? 0)) ?> anterior</div>
                <div class="chip" style="background:<?= $d['color'] ?>15;color:<?= $d['color'] ?>"><?= $d['arrow'] ?> <?= $d['label'] ?></div>
            </div>
        </td>
        <td>
            <div class="kpi-hero">
                <div class="lbl">Tasa de resolución</div>
                <div class="val"><?= $resolvedPct ?>%</div>
                <div class="sub"><?= number_format($resolvedN) ?> de <?= number_format($totalN) ?> resueltos</div>
                <div class="chip" style="background:#dcfce7;color:#15803d"><?= number_format($resolvedN) ?> resueltos</div>
            </div>
        </td>
        <td>
            <div class="kpi-hero">
                <div class="lbl">Tiempo de resolución</div>
                <div class="val"><?= $totals['resolve_t'] !== null ? round((float)$totals['resolve_t'], 1) : '—' ?><?php if ($totals['resolve_t'] !== null): ?><span style="font-size:14pt;color:#94a3b8"> h</span><?php endif; ?></div>
                <?php if (!empty($prev['resolve_t'])):
                    $d = $delta($totals['resolve_t'] ?? 0, $prev['resolve_t']); ?>
                    <div class="sub">vs <?= round((float)$prev['resolve_t'], 1) ?>h anterior</div>
                    <div class="chip" style="background:<?= $d['color'] ?>15;color:<?= $d['color'] ?>"><?= $d['arrow'] ?> <?= $d['label'] ?></div>
                <?php else: ?>
                    <div class="sub">tiempo medio</div>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>

<table class="kpis" cellspacing="0" cellpadding="0">
    <tr>
        <td><div class="kpi accent"><div class="lbl">1ª respuesta</div><div class="val"><?= $totals['first_resp'] !== null ? round((float)$totals['first_resp']) : '—' ?><?php if ($totals['first_resp'] !== null): ?><span style="font-size:9pt;color:#94a3b8"> min</span><?php endif; ?></div><div class="delta">tiempo promedio</div></div></td>
        <td><div class="kpi amber"><div class="lbl">Abiertos</div><div class="val"><?= number_format($openN) ?></div><div class="delta">al cierre del período</div></div></td>
        <td><div class="kpi red"><div class="lbl">SLA breach</div><div class="val"><?= number_format($breachedN) ?></div><div class="delta"><?= $totalN > 0 ? round(($breachedN/$totalN)*100, 1) . '% del total' : '—' ?></div></div></td>
        <td><div class="kpi green"><div class="lbl">CSAT</div><div class="val"><?= $totals['csat'] !== null ? round((float)$totals['csat'], 2) : '—' ?><?php if ($totals['csat'] !== null): ?><span style="font-size:9pt;color:#94a3b8"> / 5</span><?php endif; ?></div><div class="delta">satisfacción</div></div></td>
    </tr>
</table>

<pagebreak />

<!-- ──────────────── PAGE 3: DONUTS ──────────────── -->
<div class="section-eyebrow">02 · Distribución</div>
<h1 class="section-title">Cómo se distribuyeron los tickets</h1>
<div class="section-lead">Análisis por estado, prioridad, categoría y canal de origen del período.</div>

<?php
$stData = []; $stColorList = []; $stLabelList = [];
foreach ($byStatus as $r) { $stData[] = (int)$r['n']; $stColorList[] = $stColors[$r['status']] ?? $brand; $stLabelList[] = $stLabels[$r['status']] ?? $r['status']; }
$prData = []; $prColorList = []; $prLabelList = [];
foreach ($byPriority as $r) { $prData[] = (int)$r['n']; $prColorList[] = $prColors[$r['priority']] ?? $brand; $prLabelList[] = $prLabels[$r['priority']] ?? $r['priority']; }
?>

<table class="donuts" cellspacing="0" cellpadding="0"><tr>
    <td>
        <div class="donut-card">
            <h3>Por estado</h3>
            <table class="donut-grid" cellspacing="0" cellpadding="0"><tr>
                <td style="width:120pt"><?= $svgDonut($stData, $stColorList, 110, 22) ?></td>
                <td class="legend">
                    <?php foreach ($byStatus as $i => $r): $col = $stColorList[$i]; $lbl = $stLabelList[$i]; $pct = $totalN > 0 ? round(((int)$r['n'] / $totalN) * 100) : 0; ?>
                        <div class="row">
                            <span class="dot" style="background:<?= $col ?>"></span>
                            <span class="lbl"><?= $e($lbl) ?></span>
                            <div class="num" style="margin-left:13pt"><?= number_format((int)$r['n']) ?> · <?= $pct ?>%</div>
                        </div>
                    <?php endforeach; if (empty($byStatus)): ?><div class="muted">Sin datos</div><?php endif; ?>
                </td>
            </tr></table>
        </div>
    </td>
    <td>
        <div class="donut-card">
            <h3>Por prioridad</h3>
            <table class="donut-grid" cellspacing="0" cellpadding="0"><tr>
                <td style="width:120pt"><?= $svgDonut($prData, $prColorList, 110, 22) ?></td>
                <td class="legend">
                    <?php foreach ($byPriority as $i => $r): $col = $prColorList[$i]; $lbl = $prLabelList[$i]; $pct = $totalN > 0 ? round(((int)$r['n'] / $totalN) * 100) : 0; ?>
                        <div class="row">
                            <span class="dot" style="background:<?= $col ?>"></span>
                            <span class="lbl"><?= $e($lbl) ?></span>
                            <div class="num" style="margin-left:13pt"><?= number_format((int)$r['n']) ?> · <?= $pct ?>%</div>
                        </div>
                    <?php endforeach; if (empty($byPriority)): ?><div class="muted">Sin datos</div><?php endif; ?>
                </td>
            </tr></table>
        </div>
    </td>
</tr></table>

<div class="section-eyebrow" style="margin-top:8mm">Top categorías</div>
<table class="data" cellspacing="0" cellpadding="0" style="margin-top:3mm">
    <thead><tr><th>Categoría</th><th style="width:60pt;text-align:right">Tickets</th><th style="width:50pt;text-align:right">%</th><th style="width:50%">Distribución</th></tr></thead>
    <tbody>
    <?php $maxCat = max(array_column($byCategory, 'n') ?: [1]); $i=0; foreach ($byCategory as $r):
        $w = $bar((int)$r['n'], $maxCat);
        $pct = $totalN > 0 ? round(((int)$r['n'] / $totalN) * 100) : 0; ?>
        <tr <?= ($i++%2)?'class="zebra"':'' ?>>
            <td><strong><?= $e($r['name']) ?></strong></td>
            <td class="right"><?= number_format((int)$r['n']) ?></td>
            <td class="right"><?= $pct ?>%</td>
            <td><div class="barwrap"><div class="barfill" style="width:<?= $w ?>%;background:<?= $e($r['color'] ?: $brand) ?>"></div></div></td>
        </tr>
    <?php endforeach; if (empty($byCategory)): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:18pt">Sin datos</td></tr><?php endif; ?>
    </tbody>
</table>

<?php if (!empty($byChannel)): ?>
<div class="section-eyebrow" style="margin-top:8mm">Por canal de origen</div>
<table class="data" cellspacing="0" cellpadding="0" style="margin-top:3mm">
    <thead><tr><th>Canal</th><th style="width:60pt;text-align:right">Tickets</th><th style="width:50pt;text-align:right">%</th><th style="width:50%">Distribución</th></tr></thead>
    <tbody>
    <?php $maxCh = max(array_column($byChannel, 'n') ?: [1]); $i=0; foreach ($byChannel as $r):
        $w = $bar((int)$r['n'], $maxCh);
        $pct = $totalN > 0 ? round(((int)$r['n'] / $totalN) * 100) : 0; ?>
        <tr <?= ($i++%2)?'class="zebra"':'' ?>>
            <td><strong><?= $e(ucfirst($r['channel'])) ?></strong></td>
            <td class="right"><?= number_format((int)$r['n']) ?></td>
            <td class="right"><?= $pct ?>%</td>
            <td><div class="barwrap"><div class="barfill" style="width:<?= $w ?>%"></div></div></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<pagebreak />

<!-- ──────────────── PAGE 4: PERSONAS ──────────────── -->
<div class="section-eyebrow">03 · Personas</div>
<h1 class="section-title">Quiénes participaron en el período</h1>
<div class="section-lead">Top 10 solicitantes y agentes asignados durante los últimos <?= (int)$rangeDays ?> días.</div>

<table class="row2" cellspacing="0" cellpadding="0"><tr>
    <td>
        <div class="col-label">Top solicitantes</div>
        <table class="data" cellspacing="0" cellpadding="0">
            <thead><tr><th>Persona</th><th style="width:48pt;text-align:right">Tickets</th></tr></thead>
            <tbody>
            <?php $i=0; foreach ($byRequester as $r):
                $name = $r['name'] ?: $r['email'];
                $initial = strtoupper(substr(trim($name), 0, 1)); ?>
                <tr <?= ($i++%2)?'class="zebra"':'' ?>>
                    <td>
                        <table cellspacing="0" cellpadding="0" style="width:100%"><tr>
                            <td style="width:30pt"><div class="avatar"><?= $e($initial) ?></div></td>
                            <td style="padding-left:6pt">
                                <strong><?= $e($name) ?></strong><br>
                                <span class="muted"><?= $e($r['email']) ?></span>
                            </td>
                        </tr></table>
                    </td>
                    <td class="right"><?= number_format((int)$r['n']) ?></td>
                </tr>
            <?php endforeach; if (empty($byRequester)): ?><tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:18pt">Sin datos</td></tr><?php endif; ?>
            </tbody>
        </table>
    </td>
    <td>
        <div class="col-label">Agentes asignados</div>
        <table class="data" cellspacing="0" cellpadding="0">
            <thead><tr><th>Agente</th><th style="width:48pt;text-align:right">Tickets</th></tr></thead>
            <tbody>
            <?php $i=0; foreach ($byAgent as $r):
                $initial = strtoupper(substr(trim($r['name']), 0, 1)); ?>
                <tr <?= ($i++%2)?'class="zebra"':'' ?>>
                    <td>
                        <table cellspacing="0" cellpadding="0" style="width:100%"><tr>
                            <td style="width:30pt"><div class="avatar" style="background:#475569"><?= $e($initial) ?></div></td>
                            <td style="padding-left:6pt"><strong><?= $e($r['name']) ?></strong><br><span class="muted">Agente del equipo</span></td>
                        </tr></table>
                    </td>
                    <td class="right"><?= number_format((int)$r['n']) ?></td>
                </tr>
            <?php endforeach; if (empty($byAgent)): ?><tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:18pt">Sin datos</td></tr><?php endif; ?>
            </tbody>
        </table>
    </td>
</tr></table>

</body>
</html>
