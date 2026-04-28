<?php
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$brandLight = '#f3f0ff';
$prMap = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$stMap = ['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'];
$prColors = ['urgent'=>'#dc2626','high'=>'#f59e0b','medium'=>'#3b82f6','low'=>'#94a3b8'];
$stColors = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','on_hold'=>'#94a3b8','resolved'=>'#16a34a','closed'=>'#475569'];

$activeFilters = array_filter($filters, fn($v) => $v !== '' && $v !== 0 && $v !== null);
$total = count($tickets);
$open = 0; $resolved = 0; $urgent = 0; $unassigned = 0;
foreach ($tickets as $t) {
    if (in_array($t['status'], ['open','in_progress','on_hold'], true)) $open++;
    if (in_array($t['status'], ['resolved','closed'], true)) $resolved++;
    if ($t['priority'] === 'urgent') $urgent++;
    if (empty($t['assigned_name'])) $unassigned++;
}
$reportCode = 'TKT-' . strtoupper(dechex(crc32($tenantPublic->slug . '·' . $company['id'] . '·' . date('YmdHi'))));
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Listado de tickets · <?= $e($company['name']) ?></title>
<style>
    @page { margin: 22mm 12mm 18mm 12mm; header: page-header; footer: page-footer; }

    body { font-family: dejavusans, sans-serif; color: #1a1a25; font-size: 8.5pt; line-height: 1.45; }

    /* Header / Footer */
    .page-header { width: 100%; }
    .page-header td { padding: 0; vertical-align: middle; font-size: 7.5pt; color: #94a3b8; border-bottom: 0.5pt solid #ececef; padding-bottom: 4mm; }
    .page-header .logo { width: 18pt; height: 18pt; background: <?= $brand ?>; color: #fff; text-align: center; line-height: 18pt; font-weight: bold; font-size: 10pt; border-radius: 3pt; }
    .page-header .ttl { font-weight: bold; color: #1a1a25; font-size: 8pt; }
    .page-footer { width: 100%; padding-top: 3mm; border-top: 0.5pt solid #ececef; font-size: 7pt; color: #94a3b8; }
    .page-footer .code-mini { font-family: dejavusansmono, monospace; }

    /* Section header */
    .section-eyebrow { font-size: 7.5pt; letter-spacing: 2pt; text-transform: uppercase; color: <?= $brand ?>; font-weight: bold; }
    .section-title { font-size: 22pt; color: #1a1a25; margin: 1pt 0 4pt 0; font-weight: bold; letter-spacing: -0.5pt; line-height: 1.05; }
    .section-lead { font-size: 9pt; color: #475569; margin-bottom: 5mm; max-width: 200mm; line-height: 1.55; }

    /* KPI grid */
    table.kpis { width: 100%; border-collapse: separate; border-spacing: 5pt 0; margin-bottom: 4mm; }
    table.kpis td { width: 25%; padding: 0; vertical-align: top; }
    .kpi { background: #fff; border: 0.6pt solid #ececef; border-radius: 6pt; padding: 10pt 12pt 11pt 12pt; }
    .kpi.accent { border-top: 2.5pt solid <?= $brand ?>; }
    .kpi.amber { border-top: 2.5pt solid #f59e0b; }
    .kpi.green { border-top: 2.5pt solid #16a34a; }
    .kpi.red { border-top: 2.5pt solid #dc2626; }
    .kpi .lbl { font-size: 7pt; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.8pt; font-weight: bold; }
    .kpi .val { font-size: 22pt; font-weight: bold; color: #1a1a25; line-height: 1.05; margin-top: 2pt; letter-spacing: -0.5pt; }
    .kpi .sub { font-size: 7.5pt; color: #6b6b78; margin-top: 2pt; }

    /* Filters */
    .filters { background: <?= $brandLight ?>; border-left: 3pt solid <?= $brand ?>; border-radius: 0 5pt 5pt 0; padding: 7pt 12pt; margin-bottom: 4mm; font-size: 8.5pt; color: #475569; }
    .filters .h { font-size: 7pt; letter-spacing: 1.5pt; text-transform: uppercase; color: <?= $brand ?>; font-weight: bold; margin-right: 6pt; }
    .filters .chip { display: inline-block; background: #fff; border: 0.5pt solid #d8d0ff; border-radius: 8pt; padding: 1.5pt 7pt; margin-right: 4pt; margin-top: 1pt; font-size: 7.5pt; color: #1a1a25; }

    /* Tickets table */
    table.tickets { width: 100%; border-collapse: collapse; }
    table.tickets th { text-align: left; font-size: 7pt; text-transform: uppercase; padding: 7pt 6pt; background: #fafafb; color: #475569; border-bottom: 1pt solid #1a1a25; font-weight: bold; letter-spacing: 0.5pt; }
    table.tickets td { padding: 6pt 6pt; border-bottom: 0.4pt solid #ececef; vertical-align: top; font-size: 8.5pt; }
    table.tickets tr.zebra td { background: #fafafb; }
    .pill { display: inline-block; padding: 1.5pt 6pt; border-radius: 8pt; font-size: 7pt; font-weight: bold; color: #fff; }
    .code { font-family: dejavusansmono, monospace; font-size: 7.5pt; color: #6b6b78; }
    .muted { color: #6b6b78; font-size: 7.2pt; }
</style>
</head>
<body>

<htmlpageheader name="page-header">
    <table class="page-header" cellspacing="0" cellpadding="0"><tr>
        <td style="width:24pt"><div class="logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div></td>
        <td style="padding-left:6pt"><span class="ttl"><?= $e($company['name']) ?></span> · Listado de tickets</td>
        <td style="text-align:right"><span class="muted"><?= $e($generatedAt) ?> · <?= $e($generatedBy) ?></span></td>
    </tr></table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table class="page-footer" cellspacing="0" cellpadding="0"><tr>
        <td><?= $e($tenantPublic->name) ?> · <span class="code-mini"><?= $reportCode ?></span></td>
        <td style="text-align:right">Página {PAGENO} de {nbpg}</td>
    </tr></table>
</htmlpagefooter>

<div class="section-eyebrow">Listado de tickets · <?= $e($company['name']) ?></div>
<h1 class="section-title"><?= number_format($total) ?> ticket<?= $total===1?'':'s' ?> en este reporte</h1>
<div class="section-lead">
    Listado completo ordenado por fecha de creación (más recientes primero).
    <?php if ($open > 0): ?><strong><?= $open ?></strong> abierto<?= $open===1?'':'s' ?>,<?php endif; ?>
    <strong><?= $resolved ?></strong> resuelto<?= $resolved===1?'':'s' ?><?php if ($urgent > 0): ?>, <strong style="color:#dc2626"><?= $urgent ?></strong> de prioridad urgente<?php endif; ?>.
</div>

<table class="kpis" cellspacing="0" cellpadding="0">
    <tr>
        <td><div class="kpi accent"><div class="lbl">Total</div><div class="val"><?= number_format($total) ?></div><div class="sub">en este reporte</div></div></td>
        <td><div class="kpi amber"><div class="lbl">Abiertos</div><div class="val"><?= number_format($open) ?></div><div class="sub">requieren atención</div></div></td>
        <td><div class="kpi green"><div class="lbl">Resueltos</div><div class="val"><?= number_format($resolved) ?></div><div class="sub">cerrados o resueltos</div></div></td>
        <td><div class="kpi red"><div class="lbl">Urgentes</div><div class="val"><?= number_format($urgent) ?></div><div class="sub">prioridad máxima</div></div></td>
    </tr>
</table>

<?php if (!empty($activeFilters)): ?>
    <div class="filters">
        <span class="h">Filtros:</span>
        <?php
        if (!empty($filters['q']))        echo '<span class="chip">Búsqueda: ' . $e($filters['q']) . '</span>';
        if (!empty($filters['status']))   echo '<span class="chip">Estado: ' . $e($stMap[$filters['status']] ?? $filters['status']) . '</span>';
        if (!empty($filters['priority'])) echo '<span class="chip">Prioridad: ' . $e($prMap[$filters['priority']] ?? $filters['priority']) . '</span>';
        if (!empty($filters['from']))     echo '<span class="chip">Desde: ' . $e($filters['from']) . '</span>';
        if (!empty($filters['to']))       echo '<span class="chip">Hasta: ' . $e($filters['to']) . '</span>';
        ?>
    </div>
<?php endif; ?>

<table class="tickets" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="width:55pt">Código</th>
            <th>Asunto</th>
            <th style="width:55pt">Estado</th>
            <th style="width:48pt">Prior.</th>
            <th style="width:80pt">Categoría</th>
            <th style="width:130pt">Solicitante</th>
            <th style="width:90pt">Asignado</th>
            <th style="width:50pt">Creado</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($tickets)): ?>
        <tr><td colspan="8" style="text-align:center;padding:18pt;color:#94a3b8">No hay tickets que coincidan con los filtros aplicados.</td></tr>
    <?php endif; ?>
    <?php $i=0; foreach ($tickets as $t):
        $stCol = $stColors[$t['status']] ?? '#475569';
        $prCol = $prColors[$t['priority']] ?? '#475569';
    ?>
        <tr <?= ($i++%2)?'class="zebra"':'' ?>>
            <td class="code"><?= $e($t['code']) ?></td>
            <td><strong><?= $e($t['subject']) ?></strong></td>
            <td><span class="pill" style="background:<?= $stCol ?>"><?= $e($stMap[$t['status']] ?? $t['status']) ?></span></td>
            <td><span class="pill" style="background:<?= $prCol ?>"><?= $e($prMap[$t['priority']] ?? $t['priority']) ?></span></td>
            <td><?= $e($t['category_name'] ?: '—') ?></td>
            <td>
                <?= $e($t['requester_name']) ?><br>
                <span class="muted"><?= $e($t['requester_email']) ?></span>
            </td>
            <td><?= $e($t['assigned_name'] ?: '—') ?></td>
            <td class="muted"><?= $e(substr((string)$t['created_at'], 0, 10)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
