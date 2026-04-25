<?php use App\Core\Helpers; ?>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #f59e0b"><div class="admin-stat-label">Abiertos</div><div class="admin-stat-value"><?= number_format($stats['open']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #7c5cff"><div class="admin-stat-label">En progreso</div><div class="admin-stat-value"><?= number_format($stats['in_progress']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #22c55e"><div class="admin-stat-label">Resueltos</div><div class="admin-stat-value"><?= number_format($stats['resolved']) ?></div></div>
</div>

<form method="GET" class="admin-card admin-card-pad mb-4">
    <div class="flex flex-wrap gap-2">
        <?php foreach (['' => 'Todos','open'=>'Abiertos','in_progress'=>'En progreso','waiting'=>'En espera','resolved'=>'Resueltos','closed'=>'Cerrados'] as $val => $lbl): ?>
            <a href="?status=<?= $val ?>" class="admin-btn <?= $status===$val?'admin-btn-primary':'admin-btn-soft' ?>"><?= $e($lbl) ?></a>
        <?php endforeach; ?>
    </div>
</form>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Código</th><th>Asunto</th><th>Empresa</th><th>Prioridad</th><th>Estado</th><th>Creado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td class="font-mono text-[12px]"><?= $e($t['code']) ?></td>
                <td><?= $e($t['subject']) ?></td>
                <td><a href="<?= $url('/admin/tenants/' . $t['tenant_id']) ?>" style="color:inherit"><?= $e($t['tenant_name']) ?></a></td>
                <td>
                    <?php
                    $pcols = ['low'=>'gray','medium'=>'blue','high'=>'amber','urgent'=>'red'];
                    $pc = $pcols[$t['priority']] ?? 'gray';
                    ?>
                    <span class="admin-pill admin-pill-<?= $pc ?>"><?= $e(ucfirst($t['priority'])) ?></span>
                </td>
                <td>
                    <form method="POST" action="<?= $url('/admin/support/' . $t['id']) ?>" style="display:inline-flex; gap:4px">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input type="hidden" name="priority" value="<?= $e($t['priority']) ?>">
                        <select name="status" onchange="this.form.submit()" class="admin-select" style="padding:5px 10px; font-size:11.5px">
                            <?php foreach (['open','in_progress','waiting','resolved','closed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $t['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="text-[11.5px] text-ink-500"><?= Helpers::ago($t['created_at']) ?></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?><tr><td colspan="7" style="text-align:center; padding:30px; color:#8e8e9a">Sin tickets de soporte.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
