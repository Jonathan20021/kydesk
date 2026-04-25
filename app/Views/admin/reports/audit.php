<?php use App\Core\Helpers; ?>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Cuándo</th><th>Admin</th><th>Acción</th><th>Entidad</th><th>IP</th><th>Meta</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td class="text-[11.5px] text-ink-500"><?= $e($l['created_at']) ?> <div class="text-[10.5px] text-ink-400"><?= Helpers::ago($l['created_at']) ?></div></td>
                <td>
                    <div style="font-weight:600; font-size:12.5px"><?= $e($l['admin_name'] ?? '—') ?></div>
                    <div class="text-[11px] text-ink-400 font-mono"><?= $e($l['admin_email'] ?? '') ?></div>
                </td>
                <td><span class="admin-pill admin-pill-purple font-mono text-[10.5px]"><?= $e($l['action']) ?></span></td>
                <td class="text-[12px]"><?= $l['entity'] ? $e($l['entity']) . ($l['entity_id'] ? ' #' . (int)$l['entity_id'] : '') : '—' ?></td>
                <td class="text-[11px] font-mono text-ink-500"><?= $e($l['ip']) ?></td>
                <td class="text-[11px] font-mono text-ink-400" style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= $e($l['meta'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?><tr><td colspan="6" style="text-align:center; padding:30px; color:#8e8e9a">Sin actividad registrada.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
