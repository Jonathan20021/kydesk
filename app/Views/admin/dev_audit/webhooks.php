<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Webhook deliveries · todos los developers</h2>
        <a href="<?= $url('/admin/dev-audit') ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-arrow-left text-[13px]"></i> Auditoría</a>
    </div>
    <form method="GET" action="<?= $url('/admin/dev-audit/webhooks') ?>" class="admin-card-pad grid sm:grid-cols-3 gap-3 border-b" style="border-color:var(--border)">
        <div>
            <label class="admin-label">Developer</label>
            <select name="developer_id" class="admin-select">
                <option value="0">— Todos —</option>
                <?php foreach ($developers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $devId === (int)$d['id'] ? 'selected' : '' ?>><?= $e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end"><button class="admin-btn admin-btn-soft"><i class="lucide lucide-filter text-[13px]"></i> Filtrar</button></div>
    </form>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Hora</th><th>Developer</th><th>Webhook</th><th>Evento</th><th>URL</th><th>Status</th><th>Intento</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-10 text-ink-400">Sin entregas.</td></tr>
                <?php else: foreach ($logs as $l):
                    $sc = (int)$l['status_code'];
                    $cls = $sc >= 500 ? 'admin-pill-red' : ($sc >= 400 ? 'admin-pill-amber' : ($sc >= 200 ? 'admin-pill-green' : 'admin-pill-gray'));
                ?>
                    <tr>
                        <td class="text-[11.5px] font-mono"><?= $e(substr($l['created_at'], 0, 19)) ?></td>
                        <td>
                            <div class="font-display font-bold text-[12.5px]"><?= $e($l['dev_name']) ?></div>
                            <div class="text-[11px] text-ink-400"><?= $e($l['dev_email']) ?></div>
                        </td>
                        <td class="text-[12.5px]"><?= $e($l['webhook_name']) ?></td>
                        <td class="font-mono text-[11.5px]"><?= $e($l['event']) ?></td>
                        <td class="font-mono text-[11px] text-ink-400" style="max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= $e($l['url']) ?></td>
                        <td><span class="admin-pill <?= $cls ?>"><?= $sc ?: '—' ?></span></td>
                        <td class="text-[12px]"><?= (int)$l['attempt'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
