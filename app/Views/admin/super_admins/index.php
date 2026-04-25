<?php use App\Core\Helpers; ?>

<div class="flex items-center justify-end mb-4">
    <a href="<?= $url('/admin/super-admins/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-shield-plus"></i> Nuevo super admin</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Admin</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:<?= Helpers::colorFor($a['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:13px"><?= Helpers::initials($a['name']) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:13px"><?= $e($a['name']) ?></div>
                            <div class="text-[11px] text-ink-400 font-mono"><?= $e($a['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <?php
                    $roleColors = ['owner'=>'purple','admin'=>'blue','support'=>'amber','billing'=>'green'];
                    $rc = $roleColors[$a['role']] ?? 'gray';
                    ?>
                    <span class="admin-pill admin-pill-<?= $rc ?>"><i class="lucide lucide-<?= $a['role']==='owner'?'crown':'shield' ?> text-[10px]"></i> <?= $e(ucfirst($a['role'])) ?></span>
                </td>
                <td class="text-[11.5px] text-ink-500">
                    <?= $a['last_login_at'] ? Helpers::ago($a['last_login_at']) : 'Nunca' ?>
                    <?php if ($a['last_login_ip']): ?><div class="font-mono text-[10.5px] text-ink-400"><?= $e($a['last_login_ip']) ?></div><?php endif; ?>
                </td>
                <td><?= $a['is_active'] ? '<span class="admin-pill admin-pill-green">Activo</span>' : '<span class="admin-pill admin-pill-red">Inactivo</span>' ?></td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?= $url('/admin/super-admins/' . $a['id']) ?>" class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-edit-3 text-[13px]"></i></a>
                        <?php if ($a['id'] != $superAdmin['id']): ?>
                            <form method="POST" action="<?= $url('/admin/super-admins/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar este super admin? Esta acción no se puede deshacer.')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="admin-btn admin-btn-danger" style="padding:5px 10px"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
