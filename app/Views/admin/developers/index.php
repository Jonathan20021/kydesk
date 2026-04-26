<div class="admin-card">
    <div class="admin-card-head">
        <div class="flex items-center gap-3 flex-1 flex-wrap">
            <form method="GET" action="<?= $url('/admin/developers') ?>" class="flex-1 max-w-[400px]">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Buscar developer (nombre, email, empresa)…" class="admin-input">
            </form>
        </div>
        <a href="<?= $url('/admin/developers/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus text-[13px]"></i> Nuevo developer</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Developer</th><th>Plan</th><th>Apps</th><th>Tokens</th><th>Requests/mes</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($developers)): ?>
                    <tr><td colspan="7" class="text-center py-10 text-ink-400">No hay developers.</td></tr>
                <?php else: foreach ($developers as $d): ?>
                    <tr>
                        <td>
                            <div class="font-display font-bold"><a href="<?= $url('/admin/developers/' . $d['id']) ?>" class="text-ink-900 hover:text-brand-700"><?= $e($d['name']) ?></a></div>
                            <div class="text-[11.5px] text-ink-400"><?= $e($d['email']) ?> <?= $d['company'] ? '· ' . $e($d['company']) : '' ?></div>
                        </td>
                        <td><?= $d['plan_name'] ? '<span class="admin-pill admin-pill-purple">' . $e($d['plan_name']) . '</span>' : '<span class="text-ink-400">—</span>' ?></td>
                        <td class="font-display font-bold"><?= (int)$d['apps_count'] ?></td>
                        <td><?= (int)$d['active_tokens'] ?></td>
                        <td class="font-display font-bold"><?= number_format((int)$d['month_requests']) ?></td>
                        <td>
                            <?php if ($d['suspended_at']): ?>
                                <span class="admin-pill admin-pill-red">Suspendido</span>
                            <?php elseif ((int)$d['is_active'] === 1): ?>
                                <span class="admin-pill admin-pill-green">Activo</span>
                            <?php else: ?>
                                <span class="admin-pill admin-pill-gray">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?= $url('/admin/developers/' . $d['id']) ?>" class="admin-btn admin-btn-soft admin-btn-icon"><i class="lucide lucide-arrow-right text-[14px]"></i></a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
