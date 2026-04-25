<?php use App\Core\Helpers; use App\Core\Plan; $slug = $tenant->slug;
$maxUsers = Plan::limit($tenant, 'users');
$atLimit = is_int($maxUsers) && count($users) >= $maxUsers;
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Usuarios</h1>
        <p class="text-[13px] text-ink-400"><?= count($users) ?><?= is_int($maxUsers) && $maxUsers < 999 ? ' / ' . $maxUsers . ' permitidos en plan ' . Plan::label($tenant) : '' ?> · <?= count($users) ?> miembros</p>
    </div>
    <?php if ($auth->can('users.create')): ?>
        <?php if ($atLimit): ?>
            <a href="<?= $url('/pricing') ?>" class="btn btn-soft btn-sm" data-tooltip="Límite alcanzado"><i class="lucide lucide-lock"></i> Hacer upgrade</a>
        <?php else: ?>
            <a href="<?= $url('/t/' . $slug . '/users/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-user-plus"></i> Invitar</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <table class="table">
        <thead><tr><th>Miembro</th><th>Rol</th><th>Cargo</th><th>Tickets</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-md" style="background:<?= Helpers::colorFor($u['email']) ?>;color:white"><?= Helpers::initials($u['name']) ?></div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-bold text-[13px] truncate"><?= $e($u['name']) ?></span>
                                    <?php if ($u['is_technician']): ?><span class="badge badge-purple"><i class="lucide lucide-wrench text-[10px]"></i> Técnico</span><?php endif; ?>
                                </div>
                                <div class="text-[11.5px] truncate text-ink-400"><?= $e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-gray"><?= $e($u['role_name'] ?? 'Sin rol') ?></span></td>
                    <td class="text-[12.5px] text-ink-500"><?= $e($u['title'] ?? '—') ?></td>
                    <td class="font-mono text-[12.5px]"><?= (int)$u['tickets_count'] ?></td>
                    <td class="text-[11.5px] text-ink-400"><?= $u['last_login_at'] ? Helpers::ago($u['last_login_at']) : 'Nunca' ?></td>
                    <td>
                        <?php if ($u['is_active']): ?><span class="status-pill status-resolved">Activo</span>
                        <?php else: ?><span class="status-pill status-hold">Inactivo</span><?php endif; ?>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <?php if ($auth->can('users.edit')): ?>
                                <a href="<?= $url('/t/' . $slug . '/users/' . $u['id']) ?>" class="table-action"><i class="lucide lucide-edit-3 text-[13px]"></i></a>
                            <?php endif; ?>
                            <?php if ($auth->can('users.delete') && (int)$u['id'] !== $auth->userId()): ?>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/users/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="table-action" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
