<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Auditoría del Developer Portal</h2>
        <a href="<?= $url('/admin/dev-audit/requests') ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-activity text-[13px]"></i> Ver requests API</a>
    </div>
    <form method="GET" action="<?= $url('/admin/dev-audit') ?>" class="admin-card-pad grid sm:grid-cols-3 gap-3 border-b" style="border-color:var(--border)">
        <div>
            <label class="admin-label">Developer</label>
            <select name="developer_id" class="admin-select">
                <option value="0">— Todos —</option>
                <?php foreach ($developers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $devId === (int)$d['id'] ? 'selected' : '' ?>><?= $e($d['name']) ?> · <?= $e($d['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="admin-label">Acción contiene</label>
            <input type="text" name="action" class="admin-input" value="<?= $e($action) ?>" placeholder="ej: token.create">
        </div>
        <div class="flex items-end"><button class="admin-btn admin-btn-soft"><i class="lucide lucide-filter text-[13px]"></i> Filtrar</button></div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Fecha</th><th>Developer</th><th>Acción</th><th>Entidad</th><th>Meta</th><th>IP</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-10 text-ink-400">Sin actividad.</td></tr>
                <?php else: foreach ($logs as $l): ?>
                    <tr>
                        <td class="text-[12px] font-mono"><?= $e($l['created_at']) ?></td>
                        <td>
                            <?php if ($l['dev_name']): ?>
                                <a href="<?= $url('/admin/developers/' . $l['developer_id']) ?>" class="font-medium hover:text-brand-700"><?= $e($l['dev_name']) ?></a>
                                <div class="text-[11px] text-ink-400"><?= $e($l['dev_email']) ?></div>
                            <?php else: ?>
                                <span class="text-ink-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="admin-pill admin-pill-purple"><?= $e($l['action']) ?></span></td>
                        <td class="text-[12px]"><?= $e($l['entity'] ?? '') ?> <?php if ($l['entity_id']): ?>#<?= (int)$l['entity_id'] ?><?php endif; ?></td>
                        <td class="text-[11px] font-mono text-ink-500" style="max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= $e(mb_strimwidth((string)($l['meta'] ?? ''), 0, 80, '…')) ?></td>
                        <td class="text-[11.5px] text-ink-400"><?= $e($l['ip'] ?? '') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
