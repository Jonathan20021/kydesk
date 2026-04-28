<?php
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$brandLight = '#f3f0ff';
$prLabels = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$stLabels = ['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'];
$prColors = ['urgent'=>'#dc2626','high'=>'#f59e0b','medium'=>'#3b82f6','low'=>'#94a3b8'];
$stColors = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','on_hold'=>'#94a3b8','resolved'=>'#16a34a','closed'=>'#475569'];

$delta = function ($curr, $prev) {
    $curr = (float)$curr; $prev = (float)$prev;
    if ($prev <= 0) return ['label' => $curr > 0 ? 'nuevo' : '—', 'color' => '#94a3b8'];
    $pct = (($curr - $prev) / $prev) * 100;
    $sign = $pct >= 0 ? '+' : '';
    return ['label' => $sign . round($pct) . '%', 'color' => $pct >= 0 ? '#16a34a' : '#dc2626'];
};
$bar = function ($value, $max) {
    if ($max <= 0) return 0;
    return max(2, round(($value / $max) * 100));
};
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte ejecutivo · <?= $e($company['name']) ?></title>
<style>
    @page {
        margin: 16mm 12mm 18mm 12mm;
        header: page-header;
        footer: page-footer;
    }
    body { font-family: dejavusans, sans-serif; color: #1a1a25; font-size: 9.5pt; line-height: 1.45; }

    /* ─── Header / Footer ─── */
    .page-header { width: 100%; border-bottom: 0.5pt solid #e5e7eb; padding-bottom: 4mm; }
    .page-header td { padding: 0; vertical-align: middle; font-size: 8pt; color: #6b6b78; }
    .page-header .logo { width: 24px; height: 24px; background: <?= $brand ?>; color: #fff; text-align: center; line-height: 24px; font-weight: bold; font-size: 12pt; border-radius: 4pt; }
    .page-header .ttl { font-weight: bold; color: #1a1a25; font-size: 9pt; }
    .page-footer { width: 100%; border-top: 0.5pt solid #e5e7eb; padding-top: 3mm; font-size: 7.5pt; color: #94a3b8; }

    /* ─── Cover ─── */
    .cover { background: <?= $brand ?>; color: #fff; padding: 14mm 12mm 12mm 12mm; margin: -2mm -3mm 8mm -3mm; border-radius: 6pt; }
    .cover .meta { font-size: 8pt; opacity: 0.85; letter-spacing: 1pt; text-transform: uppercase; }
    .cover h1 { font-size: 24pt; line-height: 1.05; margin: 4pt 0 6pt 0; font-weight: bold; letter-spacing: -0.5pt; }
    .cover .sub { font-size: 11pt; opacity: 0.92; }
    .cover .strip { margin-top: 10mm; }
    .cover .strip td { font-size: 8pt; opacity: 0.9; padding-right: 8mm; }
    .cover .strip strong { display: block; font-size: 10pt; opacity: 1; margin-top: 2pt; }

    /* ─── Section heads ─── */
    h2.section { font-size: 11pt; color: #1a1a25; margin: 8mm 0 3mm 0; padding-bottom: 2pt; border-bottom: 1.5pt solid <?= $brand ?>; }
    h2.section .num { display: inline-block; background: <?= $brand ?>; color: #fff; width: 16pt; height: 16pt; border-radius: 3pt; text-align: center; line-height: 16pt; font-size: 8.5pt; margin-right: 4pt; }

    /* ─── KPI cards ─── */
    table.kpis { width: 100%; border-collapse: separate; border-spacing: 4pt 0; margin-bottom: 4mm; }
    table.kpis td { width: 25%; padding: 0; vertical-align: top; }
    .kpi { border: 0.6pt solid #e5e7eb; border-left: 3pt solid <?= $brand ?>; border-radius: 4pt; padding: 8pt 10pt; background: #fafafb; }
    .kpi.green { border-left-color: #16a34a; }
    .kpi.amber { border-left-color: #f59e0b; }
    .kpi.red   { border-left-color: #dc2626; }
    .kpi .lbl { font-size: 7pt; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.6pt; font-weight: bold; }
    .kpi .val { font-size: 18pt; font-weight: bold; color: #1a1a25; margin-top: 1pt; line-height: 1.1; }
    .kpi .delta { font-size: 7.5pt; color: #6b6b78; margin-top: 2pt; }
    .kpi .delta .pct { font-weight: bold; }

    /* ─── Data tables ─── */
    table.data { width: 100%; border-collapse: collapse; margin-top: 1pt; }
    table.data th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.4pt; padding: 6pt 5pt; background: <?= $brandLight ?>; color: <?= $brand ?>; border-bottom: 1pt solid <?= $brand ?>; font-weight: bold; }
    table.data td { padding: 5pt 5pt; border-bottom: 0.4pt solid #ececef; vertical-align: middle; font-size: 9pt; }
    table.data td.right { text-align: right; font-weight: bold; }
    table.data tr.zebra td { background: #fafafa; }
    .muted { color: #6b6b78; font-size: 8pt; }

    /* ─── Bars ─── */
    .barwrap { width: 100%; height: 7pt; background: #f0f0f5; border-radius: 3pt; }
    .barfill { height: 7pt; background: <?= $brand ?>; border-radius: 3pt; }

    /* ─── Two-column row ─── */
    table.row2 { width: 100%; border-collapse: separate; border-spacing: 8pt 0; }
    table.row2 > tr > td { width: 50%; vertical-align: top; padding: 0; }

    /* ─── Pills ─── */
    .pill { display: inline-block; padding: 1pt 5pt; border-radius: 8pt; font-size: 7.5pt; font-weight: bold; color: #fff; }

    /* ─── Cover stat (resumen ejecutivo) ─── */
    .summary-box { background: #fafafb; border: 0.5pt solid #e5e7eb; border-radius: 5pt; padding: 8pt 10pt; margin-bottom: 6mm; font-size: 9pt; line-height: 1.6; color: #475569; }
    .summary-box strong { color: #1a1a25; }
</style>
</head>
<body>

<htmlpageheader name="page-header">
    <table class="page-header" cellspacing="0" cellpadding="0"><tr>
        <td style="width:30pt"><div class="logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div></td>
        <td style="padding-left:6pt"><span class="ttl"><?= $e($company['name']) ?></span> · Reporte ejecutivo</td>
        <td style="text-align:right"><?= $e($generatedAt) ?></td>
    </tr></table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table class="page-footer" cellspacing="0" cellpadding="0"><tr>
        <td>Generado por Kydesk · <?= $e($tenantPublic->name) ?></td>
        <td style="text-align:right">Página {PAGENO} de {nbpg}</td>
    </tr></table>
</htmlpagefooter>

<div class="cover">
    <div class="meta">Reporte ejecutivo · Últimos <?= (int)$rangeDays ?> días</div>
    <h1><?= $e($company['name']) ?></h1>
    <div class="sub">Resumen de soporte y métricas operativas</div>
    <table class="strip" cellspacing="0" cellpadding="0"><tr>
        <td>Período<strong><?= $e(date('d M Y', strtotime((string)$since))) ?> → <?= $e(date('d M Y')) ?></strong></td>
        <td>Generado por<strong><?= $e($generatedBy) ?></strong></td>
        <td>Workspace<strong><?= $e($tenantPublic->name) ?></strong></td>
    </tr></table>
</div>

<?php
$totalN = (int)($totals['total'] ?? 0);
$resolvedN = (int)($totals['resolved'] ?? 0);
$resolvedPct = $totalN > 0 ? round(($resolvedN / $totalN) * 100) : 0;
?>
<div class="summary-box">
    <strong>Resumen ejecutivo.</strong>
    En los últimos <?= (int)$rangeDays ?> días, <strong><?= $e($company['name']) ?></strong> generó
    <strong><?= number_format($totalN) ?></strong> tickets, de los cuales <strong><?= number_format($resolvedN) ?> (<?= $resolvedPct ?>%)</strong>
    fueron resueltos. <?php if (!empty($totals['resolve_t'])): ?>El tiempo medio de resolución fue de
    <strong><?= round((float)$totals['resolve_t'], 1) ?>h</strong>.<?php endif; ?>
    <?php if (!empty($totals['breached']) && (int)$totals['breached'] > 0): ?>
    Se registraron <strong style="color:#dc2626"><?= (int)$totals['breached'] ?> incumplimientos de SLA</strong>.
    <?php endif; ?>
    <?php if ($totals['csat'] !== null): ?>El CSAT promedio fue <strong><?= round((float)$totals['csat'], 2) ?>/5</strong>.<?php endif; ?>
</div>

<h2 class="section"><span class="num">1</span>Indicadores principales</h2>

<table class="kpis" cellspacing="0" cellpadding="0">
    <tr>
        <td><div class="kpi"><div class="lbl">Tickets</div><div class="val"><?= number_format($totalN) ?></div>
            <?php $d = $delta($totalN, $prev['total'] ?? 0); ?>
            <div class="delta">vs <?= number_format((int)($prev['total'] ?? 0)) ?> · <span class="pct" style="color:<?= $d['color'] ?>"><?= $d['label'] ?></span></div></div></td>
        <td><div class="kpi green"><div class="lbl">Resueltos</div><div class="val"><?= number_format($resolvedN) ?></div>
            <?php $d = $delta($resolvedN, $prev['resolved'] ?? 0); ?>
            <div class="delta">vs <?= number_format((int)($prev['resolved'] ?? 0)) ?> · <span class="pct" style="color:<?= $d['color'] ?>"><?= $d['label'] ?></span></div></div></td>
        <td><div class="kpi red"><div class="lbl">SLA breach</div><div class="val"><?= number_format((int)($totals['breached'] ?? 0)) ?></div><div class="delta">en el período</div></div></td>
        <td><div class="kpi amber"><div class="lbl">CSAT</div><div class="val"><?= $totals['csat'] !== null ? round((float)$totals['csat'], 2) : '—' ?></div><div class="delta">/ 5 estrellas</div></div></td>
    </tr>
</table>

<table class="kpis" cellspacing="0" cellpadding="0">
    <tr>
        <td><div class="kpi"><div class="lbl">1ª respuesta</div><div class="val"><?= $totals['first_resp'] !== null ? round((float)$totals['first_resp']) . '<span style="font-size:10pt"> min</span>' : '—' ?></div><div class="delta">tiempo promedio</div></div></td>
        <td><div class="kpi"><div class="lbl">Resolución</div><div class="val"><?= $totals['resolve_t'] !== null ? round((float)$totals['resolve_t'], 1) . '<span style="font-size:10pt"> h</span>' : '—' ?></div>
            <?php if (!empty($prev['resolve_t'])): $d = $delta($totals['resolve_t'] ?? 0, $prev['resolve_t']); ?>
                <div class="delta">vs <?= round((float)$prev['resolve_t'], 1) ?>h · <span class="pct" style="color:<?= $d['color'] ?>"><?= $d['label'] ?></span></div>
            <?php else: ?><div class="delta">tiempo promedio</div><?php endif; ?>
        </div></td>
        <td><div class="kpi amber"><div class="lbl">Abiertos</div><div class="val"><?= number_format((int)($totals['open'] ?? 0)) ?></div><div class="delta">al cierre del período</div></div></td>
        <td><div class="kpi"><div class="lbl">Período</div><div class="val"><?= (int)$rangeDays ?><span style="font-size:10pt"> días</span></div><div class="delta">desde <?= $e(date('d M', strtotime((string)$since))) ?></div></div></td>
    </tr>
</table>

<h2 class="section"><span class="num">2</span>Distribución por estado y prioridad</h2>

<table class="row2" cellspacing="0" cellpadding="0"><tr>
<td>
    <table class="data" cellspacing="0" cellpadding="0">
        <thead><tr><th>Estado</th><th style="width:48pt;text-align:right">N°</th><th style="width:120pt">Volumen</th></tr></thead>
        <tbody>
        <?php $maxSt = max(array_column($byStatus, 'n') ?: [1]); $i=0; foreach ($byStatus as $r):
            $w = $bar((int)$r['n'], $maxSt); $col = $stColors[$r['status']] ?? $brand; ?>
            <tr <?= ($i++%2)?'class="zebra"':'' ?>>
                <td><span class="pill" style="background:<?= $col ?>"><?= $e($stLabels[$r['status']] ?? $r['status']) ?></span></td>
                <td class="right"><?= number_format((int)$r['n']) ?></td>
                <td><div class="barwrap"><div class="barfill" style="width:<?= $w ?>%;background:<?= $col ?>"></div></div></td>
            </tr>
        <?php endforeach; if (empty($byStatus)): ?><tr><td colspan="3" style="text-align:center;color:#94a3b8">Sin datos</td></tr><?php endif; ?>
        </tbody>
    </table>
</td>
<td>
    <table class="data" cellspacing="0" cellpadding="0">
        <thead><tr><th>Prioridad</th><th style="width:48pt;text-align:right">N°</th><th style="width:120pt">Volumen</th></tr></thead>
        <tbody>
        <?php $maxPr = max(array_column($byPriority, 'n') ?: [1]); $i=0; foreach ($byPriority as $r):
            $w = $bar((int)$r['n'], $maxPr); $col = $prColors[$r['priority']] ?? $brand; ?>
            <tr <?= ($i++%2)?'class="zebra"':'' ?>>
                <td><span class="pill" style="background:<?= $col ?>"><?= $e($prLabels[$r['priority']] ?? $r['priority']) ?></span></td>
                <td class="right"><?= number_format((int)$r['n']) ?></td>
                <td><div class="barwrap"><div class="barfill" style="width:<?= $w ?>%;background:<?= $col ?>"></div></div></td>
            </tr>
        <?php endforeach; if (empty($byPriority)): ?><tr><td colspan="3" style="text-align:center;color:#94a3b8">Sin datos</td></tr><?php endif; ?>
        </tbody>
    </table>
</td>
</tr></table>

<h2 class="section"><span class="num">3</span>Top categorías</h2>
<table class="data" cellspacing="0" cellpadding="0">
    <thead><tr><th>Categoría</th><th style="width:60pt;text-align:right">Tickets</th><th style="width:60%">Distribución</th></tr></thead>
    <tbody>
    <?php $maxCat = max(array_column($byCategory, 'n') ?: [1]); $i=0; foreach ($byCategory as $r):
        $w = $bar((int)$r['n'], $maxCat); ?>
        <tr <?= ($i++%2)?'class="zebra"':'' ?>>
            <td><strong><?= $e($r['name']) ?></strong></td>
            <td class="right"><?= number_format((int)$r['n']) ?></td>
            <td><div class="barwrap"><div class="barfill" style="width:<?= $w ?>%;background:<?= $e($r['color'] ?: $brand) ?>"></div></div></td>
        </tr>
    <?php endforeach; if (empty($byCategory)): ?><tr><td colspan="3" style="text-align:center;color:#94a3b8">Sin datos</td></tr><?php endif; ?>
    </tbody>
</table>

<h2 class="section"><span class="num">4</span>Personas involucradas</h2>
<table class="row2" cellspacing="0" cellpadding="0"><tr>
<td>
    <div style="font-size:8pt;text-transform:uppercase;letter-spacing:0.5pt;color:#94a3b8;font-weight:bold;margin-bottom:3pt">Top solicitantes</div>
    <table class="data" cellspacing="0" cellpadding="0">
        <thead><tr><th>Persona</th><th style="width:48pt;text-align:right">Tickets</th></tr></thead>
        <tbody>
        <?php $i=0; foreach ($byRequester as $r): ?>
            <tr <?= ($i++%2)?'class="zebra"':'' ?>>
                <td>
                    <strong><?= $e($r['name']) ?></strong><br>
                    <span class="muted"><?= $e($r['email']) ?></span>
                </td>
                <td class="right"><?= number_format((int)$r['n']) ?></td>
            </tr>
        <?php endforeach; if (empty($byRequester)): ?><tr><td colspan="2" style="text-align:center;color:#94a3b8">Sin datos</td></tr><?php endif; ?>
        </tbody>
    </table>
</td>
<td>
    <div style="font-size:8pt;text-transform:uppercase;letter-spacing:0.5pt;color:#94a3b8;font-weight:bold;margin-bottom:3pt">Agentes asignados</div>
    <table class="data" cellspacing="0" cellpadding="0">
        <thead><tr><th>Agente</th><th style="width:48pt;text-align:right">Tickets</th></tr></thead>
        <tbody>
        <?php $i=0; foreach ($byAgent as $r): ?>
            <tr <?= ($i++%2)?'class="zebra"':'' ?>>
                <td><strong><?= $e($r['name']) ?></strong></td>
                <td class="right"><?= number_format((int)$r['n']) ?></td>
            </tr>
        <?php endforeach; if (empty($byAgent)): ?><tr><td colspan="2" style="text-align:center;color:#94a3b8">Sin datos</td></tr><?php endif; ?>
        </tbody>
    </table>
</td>
</tr></table>

</body>
</html>
