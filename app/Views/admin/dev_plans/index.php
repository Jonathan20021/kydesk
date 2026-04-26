<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Planes para developers</h2>
        <a href="<?= $url('/admin/dev-plans/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus text-[13px]"></i> Nuevo plan</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Plan</th><th>Slug</th><th>Precio mensual</th><th>Apps</th><th>Requests/mes</th><th>Suscripciones</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($plans)): ?>
                    <tr><td colspan="8" class="text-center py-10 text-ink-400">Sin planes.</td></tr>
                <?php else: foreach ($plans as $p): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg grid place-items-center text-white" style="background:<?= $e($p['color']) ?>"><i class="lucide lucide-<?= $e($p['icon']) ?> text-[12px]"></i></div>
                                <div>
                                    <div class="font-display font-bold"><?= $e($p['name']) ?></div>
                                    <div class="text-[11px] text-ink-400"><?= $e($p['description']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="font-mono text-[12px]"><?= $e($p['slug']) ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$p['price_monthly'], 2) ?></td>
                        <td><?= (int)$p['max_apps'] ?></td>
                        <td><?= number_format((int)$p['max_requests_month']) ?></td>
                        <td><?= (int)$p['active_subs'] ?> / <?= (int)$p['total_subs'] ?></td>
                        <td><span class="admin-pill <?= (int)$p['is_active']===1?'admin-pill-green':'admin-pill-gray' ?>"><?= (int)$p['is_active']===1?'Activo':'Inactivo' ?></span></td>
                        <td class="flex gap-1">
                            <a href="<?= $url('/admin/dev-plans/' . $p['id']) ?>" class="admin-btn admin-btn-soft admin-btn-icon" title="Editar"><i class="lucide lucide-edit text-[13px]"></i></a>
                            <form method="POST" action="<?= $url('/admin/dev-plans/' . $p['id'] . '/toggle') ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="admin-btn admin-btn-soft admin-btn-icon" title="Activar/desactivar"><i class="lucide lucide-power text-[13px]"></i></button></form>
                            <form method="POST" action="<?= $url('/admin/dev-plans/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar plan?')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="admin-btn admin-btn-danger admin-btn-icon" title="Eliminar"><i class="lucide lucide-trash-2 text-[13px]"></i></button></form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
