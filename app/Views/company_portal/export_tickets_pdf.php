<?php
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$prMap = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$stMap = ['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'];

$activeFilters = array_filter($filters, fn($v) => $v !== '' && $v !== 0 && $v !== null);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tickets · <?= $e($company['name']) ?></title>
<style>
    @page { margin: 18mm 14mm 16mm 14mm; }
    body { font-family: "DejaVu Sans", sans-serif; color: #16151b; margin: 0; font-size: 10px; }
    h1 { font-size: 18px; margin: 0 0 2px; font-weight: bold; }
    h2 { font-size: 12px; margin: 14px 0 6px; color: #2a2a33; border-bottom: 2px solid <?= $brand ?>; padding-bottom: 3px; }
    .muted { color: #6b6b78; font-size: 9.5px; }

    table { width: 100%; border-collapse: collapse; }

    table.header { margin-bottom: 10px; border-bottom: 1px solid #ececef; padding-bottom: 8px; }
    table.header td { padding: 0; vertical-align: middle; border: 0; }
    .logo { width: 36px; height: 36px; background: <?= $brand ?>; color: #ffffff; text-align: center; line-height: 36px; font-weight: bold; font-size: 16px; border-radius: 8px; }
    .meta { text-align: right; font-size: 9px; color: #6b6b78; }
    .meta strong { color: #16151b; }

    table.tickets th { text-align: left; font-size: 8.5px; text-transform: uppercase; padding: 6px 4px; border-bottom: 1.5px solid #16151b; color: #2a2a33; background: #fafafa; }
    table.tickets td { padding: 5px 4px; border-bottom: 1px solid #ececef; vertical-align: top; }

    .pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px; font-weight: bold; }
    .pill-low      { background: #f3f4f6; color: #4b5563; }
    .pill-medium   { background: #dbeafe; color: #1d4ed8; }
    .pill-high     { background: #fef3c7; color: #b45309; }
    .pill-urgent   { background: #fee2e2; color: #b91c1c; }
    .pill-open     { background: #dbeafe; color: #1d4ed8; }
    .pill-in_progress { background: #fef3c7; color: #b45309; }
    .pill-on_hold  { background: #f3f4f6; color: #4b5563; }
    .pill-resolved { background: #dcfce7; color: #15803d; }
    .pill-closed   { background: #e5e7eb; color: #1f2937; }
    .code { font-family: "DejaVu Sans Mono", monospace; font-size: 9px; color: #6b6b78; }

    .filters { background: #fafafa; border: 1px solid #ececef; border-radius: 6px; padding: 6px 10px; margin-bottom: 10px; font-size: 9.5px; }
    .filters strong { color: #2a2a33; }
</style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:46px"><div class="logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div></td>
        <td>
            <h1>Reporte de tickets</h1>
            <div class="muted"><?= $e($company['name']) ?> · <?= $e($tenantPublic->name) ?></div>
        </td>
        <td class="meta" style="width:200px">
            <div>Generado: <strong><?= $e($generatedAt) ?></strong></div>
            <div>Por: <?= $e($generatedBy) ?></div>
            <div>Total: <strong><?= number_format(count($tickets)) ?></strong> tickets</div>
        </td>
    </tr>
</table>

<?php if (!empty($activeFilters)): ?>
    <div class="filters">
        <strong>Filtros aplicados:</strong>
        <?php $parts = [];
        if (!empty($filters['q'])) $parts[] = 'búsqueda: "' . $e($filters['q']) . '"';
        if (!empty($filters['status'])) $parts[] = 'estado: ' . ($stMap[$filters['status']] ?? $filters['status']);
        if (!empty($filters['priority'])) $parts[] = 'prioridad: ' . ($prMap[$filters['priority']] ?? $filters['priority']);
        if (!empty($filters['from'])) $parts[] = 'desde: ' . $e($filters['from']);
        if (!empty($filters['to'])) $parts[] = 'hasta: ' . $e($filters['to']);
        echo implode(' · ', $parts);
        ?>
    </div>
<?php endif; ?>

<table class="tickets">
    <thead>
        <tr>
            <th style="width:62px">Código</th>
            <th>Asunto</th>
            <th style="width:62px">Estado</th>
            <th style="width:54px">Prioridad</th>
            <th style="width:75px">Categoría</th>
            <th style="width:120px">Solicitante</th>
            <th style="width:80px">Asignado</th>
            <th style="width:54px">Creado</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($tickets)): ?>
        <tr><td colspan="8" style="text-align:center;padding:14px;color:#8e8e9a">No hay tickets que coincidan con los filtros.</td></tr>
    <?php endif; ?>
    <?php foreach ($tickets as $t): ?>
        <tr>
            <td class="code"><?= $e($t['code']) ?></td>
            <td><?= $e($t['subject']) ?></td>
            <td><span class="pill pill-<?= $e($t['status']) ?>"><?= $e($stMap[$t['status']] ?? $t['status']) ?></span></td>
            <td><span class="pill pill-<?= $e($t['priority']) ?>"><?= $e($prMap[$t['priority']] ?? $t['priority']) ?></span></td>
            <td><?= $e($t['category_name'] ?: '—') ?></td>
            <td>
                <?= $e($t['requester_name']) ?><br>
                <span class="muted"><?= $e($t['requester_email']) ?></span>
            </td>
            <td><?= $e($t['assigned_name'] ?: 'Sin asignar') ?></td>
            <td><?= $e(substr((string)$t['created_at'], 0, 10)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
