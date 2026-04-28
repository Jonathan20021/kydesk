<?php $slug = $tenant->slug; ?>

<div class="mb-5">
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Usuarios del Portal</h1>
    <p class="text-[13px] text-ink-400">Clientes con login al portal autenticado</p>
</div>

<div class="card overflow-hidden">
    <table class="admin-table">
        <thead><tr><th>Nombre</th><th>Email</th><th>Empresa</th><th>Rol</th><th>Estado</th><th>Último login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div class="font-display font-bold text-[13px]"><?= $e($u['name']) ?></div>
                    <?php if (!empty($u['phone'])): ?><div class="text-[11px] text-ink-400"><?= $e($u['phone']) ?></div><?php endif; ?>
                </td>
                <td class="font-mono text-[12px]"><?= $e($u['email']) ?></td>
                <td><?= $e($u['company_name'] ?? '—') ?></td>
                <td>
                    <?php if (!empty($u['is_company_manager'])): ?>
                        <span class="admin-pill admin-pill-purple" style="background:#f3f0ff;color:#5a3aff">Manager</span>
                    <?php elseif (!empty($u['company_id'])): ?>
                        <span class="admin-pill admin-pill-gray">Miembro</span>
                    <?php else: ?>
                        <span class="admin-pill admin-pill-gray">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="admin-pill admin-pill-green">Activo</span>
                    <?php else: ?>
                        <span class="admin-pill admin-pill-gray">Inactivo</span>
                    <?php endif; ?>
                    <?php if ($u['email_verified_at']): ?><span class="admin-pill admin-pill-blue">Verificado</span><?php endif; ?>
                </td>
                <td class="text-[11.5px] text-ink-500"><?= $e($u['last_login_at'] ?: '—') ?></td>
                <td>
                    <div class="flex gap-1">
                        <?php if (!empty($u['company_id'])): ?>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/portal-users/' . $u['id'] . '/manager') ?>" data-tooltip="<?= !empty($u['is_company_manager']) ? 'Quitar manager' : 'Marcar como manager' ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="admin-btn <?= !empty($u['is_company_manager']) ? 'admin-btn-primary' : 'admin-btn-soft' ?>" style="padding:5px 10px"><i class="lucide lucide-shield text-[12px]"></i></button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/portal-users/' . $u['id'] . '/toggle') ?>" data-tooltip="Activar/Desactivar">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-power text-[12px]"></i></button>
                        </form>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/portal-users/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('Eliminar usuario?')">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="admin-btn admin-btn-danger" style="padding:5px 10px"><i class="lucide lucide-trash-2 text-[12px]"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
            <tr><td colspan="7" style="text-align:center;padding:24px;color:#8e8e9a">Sin usuarios registrados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
