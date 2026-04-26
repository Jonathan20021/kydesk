<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Log de requests API · últimas 500</h2>
        <a href="<?= $url('/admin/dev-audit') ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-history text-[13px]"></i> Auditoría general</a>
    </div>
    <form method="GET" action="<?= $url('/admin/dev-audit/requests') ?>" class="admin-card-pad grid sm:grid-cols-3 gap-3 border-b" style="border-color:var(--border)">
        <div>
            <label class="admin-label">Developer</label>
            <select name="developer_id" class="admin-select">
                <option value="0">— Todos —</option>
                <?php foreach ($developers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $devId === (int)$d['id'] ? 'selected' : '' ?>><?= $e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="admin-label">App ID</label>
            <input type="number" name="app_id" class="admin-input" value="<?= $appId ?: '' ?>">
        </div>
        <div class="flex items-end"><button class="admin-btn admin-btn-soft"><i class="lucide lucide-filter text-[13px]"></i> Filtrar</button></div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Fecha</th><th>Developer</th><th>App</th><th>Método</th><th>Path</th><th>Status</th><th>Latencia</th><th>IP</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center py-10 text-ink-400">Sin requests todavía.</td></tr>
                <?php else: foreach ($logs as $l):
                    $sc = (int)$l['status_code'];
                    $cls = $sc >= 500 ? 'admin-pill-red' : ($sc >= 400 ? 'admin-pill-amber' : 'admin-pill-green');
                ?>
                    <tr>
                        <td class="text-[11.5px] font-mono"><?= $e(substr($l['created_at'], 0, 19)) ?></td>
                        <td class="text-[12px]"><?= $e($l['dev_name'] ?? '—') ?></td>
                        <td class="text-[12px]"><?= $e($l['app_name'] ?? '—') ?></td>
                        <td><span class="admin-pill admin-pill-purple text-[10px]"><?= $e($l['method']) ?></span></td>
                        <td class="text-[12px] font-mono"><?= $e($l['path']) ?></td>
                        <td><span class="admin-pill <?= $cls ?>"><?= $sc ?></span></td>
                        <td class="text-[11.5px] text-ink-400"><?= (int)$l['duration_ms'] ?>ms</td>
                        <td class="text-[11.5px] text-ink-400"><?= $e($l['ip'] ?? '') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
