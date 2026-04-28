<?php
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$brandLight = '#f3f0ff';
$prMap = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$stMap = ['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'];
$prColors = ['urgent'=>'#dc2626','high'=>'#f59e0b','medium'=>'#3b82f6','low'=>'#94a3b8'];
$stColors = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','on_hold'=>'#94a3b8','resolved'=>'#16a34a','closed'=>'#475569'];

$activeFilters = array_filter($filters, fn($v) => $v !== '' && $v !== 0 && $v !== null);

// Métricas rápidas
$total = count($tickets);
$open = 0; $resolved = 0; $urgent = 0;
foreach ($tickets as $t) {
    if (in_array($t['status'], ['open','in_progress','on_hold'], true)) $open++;
    if (in_array($t['status'], ['resolved','closed'], true)) $resolved++;
    if ($t['priority'] === 'urgent') $urgent++;
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Listado de tickets · <?= $e($company['name']) ?></title>
<style>
    @page {
        margin: 16mm 10mm 18mm 10mm;
        header: page-header;
        footer: page-footer;
    }
    body { font-family: dejavusans, sans-serif; color: #1a1a25; font-size: 8.5pt; line-height: 1.4; }

    .page-header { width: 100%; border-bottom: 0.5pt solid #e5e7eb; padding-bottom: 4mm; }
    .page-header td { padding: 0; vertical-align: middle; font-size: 7.5pt; color: #6b6b78; }
    .page-header .logo { width: 22px; height: 22px; background: <?= $brand ?>; color: #fff; text-align: center; line-height: 22px; font-weight: bold; font-size: 11pt; border-radius: 3pt; }
    .page-header .ttl { font-weight: bold; color: #1a1a25; font-size: 8.5pt; }
    .page-footer { width: 100%; border-top: 0.5pt solid #e5e7eb; padding-top: 3mm; font-size: 7pt; color: #94a3b8; }

    .cover { background: <?= $brand ?>; color: #fff; padding: 10mm 10mm 8mm 10mm; margin: -2mm -1mm 6mm -1mm; border-radius: 5pt; }
    .cover .meta { font-size: 7.5pt; opacity: 0.85; letter-spacing: 1pt; text-transform: uppercase; }
    .cover h1 { font-size: 20pt; line-height: 1.05; margin: 3pt 0 4pt 0; font-weight: bold; letter-spacing: -0.4pt; }
    .cover .sub { font-size: 9.5pt; opacity: 0.92; }
    .cover .strip { margin-top: 6mm; }
    .cover .strip td { font-size: 7pt; opacity: 0.9; padding-right: 6mm; }
    .cover .strip strong { display: block; font-size: 9pt; margin-top: 2pt; }

    table.kpis { width: 100%; border-collapse: separate; border-spacing: 4pt 0; margin-bottom: 4mm; }
    table.kpis td { width: 25%; padding: 0; vertical-align: top; }
    .kpi { border: 0.6pt solid #e5e7eb; border-left: 3pt solid <?= $brand ?>; border-radius: 4pt; padding: 6pt 8pt; background: #fafafb; }
    .kpi.green { border-left-color: #16a34a; }
    .kpi.amber { border-left-color: #f59e0b; }
    .kpi.red   { border-left-color: #dc2626; }
    .kpi .lbl { font-size: 6.5pt; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5pt; font-weight: bold; }
    .kpi .val { font-size: 16pt; font-weight: bold; color: #1a1a25; line-height: 1.05; margin-top: 1pt; }

    .filters { background: #fafafb; border: 0.5pt solid #e5e7eb; border-radius: 4pt; padding: 5pt 8pt; margin-bottom: 4mm; font-size: 8pt; color: #475569; }
    .filters strong { color: #1a1a25; font-weight: bold; }
    .filters .chip { display: inline-block; background: #fff; border: 0.5pt solid #e5e7eb; border-radius: 8pt; padding: 1pt 6pt; margin-right: 3pt; margin-top: 1pt; font-size: 7.5pt; }

    table.tickets { width: 100%; border-collapse: collapse; }
    table.tickets th { text-align: left; font-size: 7pt; text-transform: uppercase; padding: 5pt 4pt; background: <?= $brandLight ?>; color: <?= $brand ?>; border-bottom: 1pt solid <?= $brand ?>; font-weight: bold; letter-spacing: 0.4pt; }
    table.tickets td { padding: 4.5pt 4pt; border-bottom: 0.4pt solid #ececef; vertical-align: top; font-size: 8.2pt; }
    table.tickets tr.zebra td { background: #fafafa; }
    .pill { display: inline-block; padding: 1pt 5pt; border-radius: 8pt; font-size: 7pt; font-weight: bold; color: #fff; }
    .code { font-family: dejavusansmono, monospace; font-size: 7.5pt; color: #6b6b78; }
    .muted { color: #6b6b78; font-size: 7.2pt; }
</style>
</head>
<body>

<htmlpageheader name="page-header">
    <table class="page-header" cellspacing="0" cellpadding="0"><tr>
        <td style="width:28pt"><div class="logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div></td>
        <td style="padding-left:6pt"><span class="ttl"><?= $e($company['name']) ?></span> · Listado de tickets</td>
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
    <div class="meta">Listado de tickets</div>
    <h1><?= $e($company['name']) ?></h1>
    <div class="sub"><?= number_format($total) ?> tickets · ordenados por más recientes</div>
    <table class="strip" cellspacing="0" cellpadding="0"><tr>
        <td>Generado por<strong><?= $e($generatedBy) ?></strong></td>
        <td>Workspace<strong><?= $e($tenantPublic->name) ?></strong></td>
        <td>Fecha<strong><?= $e($generatedAt) ?></strong></td>
    </tr></table>
</div>

<table class="kpis" cellspacing="0" cellpadding="0">
    <tr>
        <td><div class="kpi"><div class="lbl">Total</div><div class="val"><?= number_format($total) ?></div></div></td>
        <td><div class="kpi amber"><div class="lbl">Abiertos</div><div class="val"><?= number_format($open) ?></div></div></td>
        <td><div class="kpi green"><div class="lbl">Resueltos</div><div class="val"><?= number_format($resolved) ?></div></div></td>
        <td><div class="kpi red"><div class="lbl">Urgentes</div><div class="val"><?= number_format($urgent) ?></div></div></td>
    </tr>
</table>

<?php if (!empty($activeFilters)): ?>
    <div class="filters">
        <strong>Filtros aplicados:</strong>
        <?php
        if (!empty($filters['q'])) echo '<span class="chip">Búsqueda: ' . $e($filters['q']) . '</span>';
        if (!empty($filters['status'])) echo '<span class="chip">Estado: ' . $e($stMap[$filters['status']] ?? $filters['status']) . '</span>';
        if (!empty($filters['priority'])) echo '<span class="chip">Prioridad: ' . $e($prMap[$filters['priority']] ?? $filters['priority']) . '</span>';
        if (!empty($filters['from'])) echo '<span class="chip">Desde: ' . $e($filters['from']) . '</span>';
        if (!empty($filters['to'])) echo '<span class="chip">Hasta: ' . $e($filters['to']) . '</span>';
        ?>
    </div>
<?php endif; ?>

<table class="tickets" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="width:55pt">Código</th>
            <th>Asunto</th>
            <th style="width:55pt">Estado</th>
            <th style="width:46pt">Prior.</th>
            <th style="width:75pt">Categoría</th>
            <th style="width:115pt">Solicitante</th>
            <th style="width:78pt">Asignado</th>
            <th style="width:48pt">Creado</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($tickets)): ?>
        <tr><td colspan="8" style="text-align:center;padding:14pt;color:#94a3b8">No hay tickets que coincidan con los filtros.</td></tr>
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
