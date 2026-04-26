<div class="admin-card">
    <div class="admin-card-head">
        <div class="flex-1">
            <form method="GET" action="<?= $url('/admin/dev-apps') ?>" class="max-w-[400px]">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Buscar app o developer…" class="admin-input">
            </form>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>App</th><th>Developer</th><th>Slug / Tenant</th><th>Entorno</th><th>Tokens</th><th>Requests/mes</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($apps)): ?>
                    <tr><td colspan="8" class="text-center py-10 text-ink-400">Sin apps.</td></tr>
                <?php else: foreach ($apps as $a): ?>
                    <tr>
                        <td>
                            <div class="font-display font-bold"><?= $e($a['name']) ?></div>
                            <?php if ($a['description']): ?><div class="text-[11.5px] text-ink-400"><?= $e(mb_strimwidth($a['description'], 0, 60, '…')) ?></div><?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $url('/admin/developers/' . $a['developer_id']) ?>" class="hover:text-brand-700 font-medium"><?= $e($a['dev_name']) ?></a>
                            <div class="text-[11px] text-ink-400"><?= $e($a['dev_email']) ?></div>
                        </td>
                        <td>
                            <div class="font-mono text-[11.5px]"><?= $e($a['slug']) ?></div>
                            <?php if (!empty($a['tenant_id'])): ?><div class="text-[10px] text-ink-400 mt-0.5">tenant #<?= (int)$a['tenant_id'] ?></div><?php endif; ?>
                        </td>
                        <td><span class="admin-pill admin-pill-gray"><?= $e($a['environment']) ?></span></td>
                        <td class="font-display font-bold"><?= (int)$a['active_tokens'] ?></td>
                        <td class="font-display font-bold"><?= number_format((int)$a['month_requests']) ?></td>
                        <td><span class="admin-pill <?= $a['status']==='active'?'admin-pill-green':'admin-pill-red' ?>"><?= $e($a['status']) ?></span></td>
                        <td class="flex gap-1">
                            <?php if ($a['status']==='active'): ?>
                                <form method="POST" action="<?= $url('/admin/dev-apps/' . $a['id'] . '/suspend') ?>" onsubmit="return confirm('¿Suspender app?')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="admin-btn admin-btn-danger admin-btn-icon" title="Suspender"><i class="lucide lucide-pause text-[13px]"></i></button></form>
                            <?php else: ?>
                                <form method="POST" action="<?= $url('/admin/dev-apps/' . $a['id'] . '/activate') ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="admin-btn admin-btn-soft admin-btn-icon" title="Reactivar"><i class="lucide lucide-play text-[13px]"></i></button></form>
                            <?php endif; ?>
                            <form method="POST" action="<?= $url('/admin/dev-apps/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar app y su workspace?')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="admin-btn admin-btn-danger admin-btn-icon" title="Eliminar"><i class="lucide lucide-trash-2 text-[13px]"></i></button></form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
