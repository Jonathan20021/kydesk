<?php
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$prLabels = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$stLabels = ['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'];

$delta = function ($curr, $prev) {
    $curr = (float)$curr; $prev = (float)$prev;
    if ($prev <= 0) return $curr > 0 ? 'nuevo' : '—';
    $pct = (($curr - $prev) / $prev) * 100;
    return ($pct >= 0 ? '+' : '') . round($pct) . '%';
};
$bar = function ($value, $max) {
    if ($max <= 0) return 0;
    return max(2, round(($value / $max) * 100));
};
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte · <?= $e($company['name']) ?></title>
<style>
    @page { margin: 18mm 14mm 14mm 14mm; }
    body { font-family: "DejaVu Sans", sans-serif; color: #16151b; margin: 0; font-size: 10.5px; }
    h1 { font-size: 20px; margin: 0 0 2px; font-weight: bold; }
    h2 { font-size: 12px; margin: 16px 0 8px; color: #2a2a33; border-bottom: 2px solid <?= $brand ?>; padding-bottom: 3px; }
    .muted { color: #6b6b78; font-size: 10px; }

    table { width: 100%; border-collapse: collapse; }

    table.header { margin-bottom: 12px; border-bottom: 1px solid #ececef; padding-bottom: 8px; }
    table.header td { padding: 0; vertical-align: middle; border: 0; }
    .logo { width: 38px; height: 38px; background: <?= $brand ?>; color: #ffffff; text-align: center; line-height: 38px; font-weight: bold; font-size: 16px; border-radius: 8px; }
    .meta { text-align: right; font-size: 9.5px; color: #6b6b78; }
    .meta strong { color: #16151b; }

    table.kpis { margin-bottom: 8px; }
    table.kpis td { width: 25%; padding: 0 4px; vertical-align: top; }
    .kpi { border: 1px solid #ececef; border-radius: 8px; padding: 8px 10px; }
    .kpi .lbl { font-size: 8.5px; text-transform: uppercase; color: #8e8e9a; font-weight: bold; }
    .kpi .val { font-size: 19px; font-weight: bold; margin-top: 2px; }
    .kpi .delta { font-size: 9px; color: #6b6b78; margin-top: 2px; }

    table.row { margin-top: 4px; }
    table.row > tr > td { width: 50%; padding: 0 6px; vertical-align: top; }

    table.data th { text-align: left; font-size: 8.5px; text-transform: uppercase; padding: 6px 4px; border-bottom: 1.5px solid #16151b; color: #2a2a33; background: #fafafa; }
    table.data td { padding: 5px 4px; border-bottom: 1px solid #ececef; vertical-align: middle; font-size: 10px; }
    table.data td.right { text-align: right; font-weight: bold; }

    .bar-wrap { width: 100%; background: #f3f4f6; border-radius: 3px; height: 6px; overflow: hidden; }
    .bar-fill { height: 6px; background: <?= $brand ?>; }
</style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:46px"><div class="logo"><?= strtoupper(substr($tenantPublic->name, 0, 1)) ?></div></td>
        <td>
            <h1>Reporte ejecutivo</h1>
            <div class="muted"><?= $e($company['name']) ?> · <?= $e($tenantPublic->name) ?> · Últimos <?= (int)$rangeDays ?> días</div>
        </td>
        <td class="meta" style="width:200px">
            <div>Generado: <strong><?= $e($generatedAt) ?></strong></div>
            <div>Por: <?= $e($generatedBy) ?></div>
        </td>
    </tr>
</table>

<table class="kpis">
    <tr>
        <td><div class="kpi"><div class="lbl">Tickets</div><div class="val"><?= number_format((int)($totals['total'] ?? 0)) ?></div><div class="delta">vs <?= number_format((int)($prev['total'] ?? 0)) ?> · <?= $delta($totals['total'] ?? 0, $prev['total'] ?? 0) ?></div></div></td>
        <td><div class="kpi"><div class="lbl">Resueltos</div><div class="val"><?= number_format((int)($totals['resolved'] ?? 0)) ?></div><div class="delta">vs <?= number_format((int)($prev['resolved'] ?? 0)) ?> · <?= $delta($totals['resolved'] ?? 0, $prev['resolved'] ?? 0) ?></div></div></td>
        <td><div class="kpi"><div class="lbl">SLA breach</div><div class="val"><?= number_format((int)($totals['breached'] ?? 0)) ?></div><div class="delta">en el período</div></div></td>
        <td><div class="kpi"><div class="lbl">CSAT</div><div class="val"><?= $totals['csat'] !== null ? round((float)$totals['csat'], 2) : '—' ?></div><div class="delta">/ 5 estrellas</div></div></td>
    </tr>
</table>
<table class="kpis">
    <tr>
        <td><div class="kpi"><div class="lbl">Primera respuesta</div><div class="val"><?= $totals['first_resp'] !== null ? round((float)$totals['first_resp']) . ' min' : '—' ?></div></div></td>
        <td><div class="kpi"><div class="lbl">Tiempo de resolución</div><div class="val"><?= $totals['resolve_t'] !== null ? round((float)$totals['resolve_t'], 1) . ' h' : '—' ?></div><div class="delta"><?= !empty($prev['resolve_t']) ? 'vs ' . round((float)$prev['resolve_t'], 1) . 'h · ' . $delta($totals['resolve_t'] ?? 0, $prev['resolve_t']) : '' ?></div></div></td>
        <td><div class="kpi"><div class="lbl">Abiertos</div><div class="val"><?= number_format((int)($totals['open'] ?? 0)) ?></div></div></td>
        <td><div class="kpi"><div class="lbl">Período</div><div class="val"><?= (int)$rangeDays ?>d</div><div class="delta">desde <?= $e(substr((string)$since, 0, 10)) ?></div></div></td>
    </tr>
</table>

<h2>Distribución por estado y prioridad</h2>
<table class="row">
    <tr>
        <td>
            <table class="data">
                <thead><tr><th>Estado</th><th style="width:50px;text-align:right">Tickets</th><th style="width:120px">Volumen</th></tr></thead>
                <tbody>
                <?php $maxSt = max(array_column($byStatus, 'n') ?: [1]); foreach ($byStatus as $r):
                    $w = $bar((int)$r['n'], $maxSt); ?>
                    <tr>
                        <td><?= $e($stLabels[$r['status']] ?? $r['status']) ?></td>
                        <td class="right"><?= (int)$r['n'] ?></td>
                        <td><div class="bar-wrap"><div class="bar-fill" style="width:<?= $w ?>%"></div></div></td>
                    </tr>
                <?php endforeach; if (empty($byStatus)): ?><tr><td colspan="3" style="text-align:center;color:#8e8e9a">Sin datos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </td>
        <td>
            <table class="data">
                <thead><tr><th>Prioridad</th><th style="width:50px;text-align:right">Tickets</th><th style="width:120px">Volumen</th></tr></thead>
                <tbody>
                <?php $maxPr = max(array_column($byPriority, 'n') ?: [1]); foreach ($byPriority as $r):
                    $w = $bar((int)$r['n'], $maxPr); ?>
                    <tr>
                        <td><?= $e($prLabels[$r['priority']] ?? $r['priority']) ?></td>
                        <td class="right"><?= (int)$r['n'] ?></td>
                        <td><div class="bar-wrap"><div class="bar-fill" style="width:<?= $w ?>%"></div></div></td>
                    </tr>
                <?php endforeach; if (empty($byPriority)): ?><tr><td colspan="3" style="text-align:center;color:#8e8e9a">Sin datos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<h2>Top categorías</h2>
<table class="data">
    <thead><tr><th>Categoría</th><th style="width:60px;text-align:right">Tickets</th><th style="width:55%">Volumen</th></tr></thead>
    <tbody>
    <?php $maxCat = max(array_column($byCategory, 'n') ?: [1]); foreach ($byCategory as $r):
        $w = $bar((int)$r['n'], $maxCat); ?>
        <tr>
            <td><?= $e($r['name']) ?></td>
            <td class="right"><?= (int)$r['n'] ?></td>
            <td><div class="bar-wrap"><div class="bar-fill" style="width:<?= $w ?>%;background:<?= $e($r['color'] ?: $brand) ?>"></div></div></td>
        </tr>
    <?php endforeach; if (empty($byCategory)): ?><tr><td colspan="3" style="text-align:center;color:#8e8e9a">Sin datos</td></tr><?php endif; ?>
    </tbody>
</table>

<table class="row" style="margin-top:10px">
    <tr>
        <td>
            <h2>Top solicitantes</h2>
            <table class="data">
                <thead><tr><th>Persona</th><th style="width:50px;text-align:right">Tickets</th></tr></thead>
                <tbody>
                <?php foreach ($byRequester as $r): ?>
                    <tr>
                        <td>
                            <strong><?= $e($r['name']) ?></strong><br>
                            <span class="muted"><?= $e($r['email']) ?></span>
                        </td>
                        <td class="right"><?= (int)$r['n'] ?></td>
                    </tr>
                <?php endforeach; if (empty($byRequester)): ?><tr><td colspan="2" style="text-align:center;color:#8e8e9a">Sin datos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </td>
        <td>
            <h2>Agentes asignados</h2>
            <table class="data">
                <thead><tr><th>Agente</th><th style="width:50px;text-align:right">Tickets</th></tr></thead>
                <tbody>
                <?php foreach ($byAgent as $r): ?>
                    <tr><td><?= $e($r['name']) ?></td><td class="right"><?= (int)$r['n'] ?></td></tr>
                <?php endforeach; if (empty($byAgent)): ?><tr><td colspan="2" style="text-align:center;color:#8e8e9a">Sin datos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
