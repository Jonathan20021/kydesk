<?php use App\Core\Helpers; ?>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #d946ef"><div class="admin-stat-label">Total</div><div class="admin-stat-value"><?= number_format($stats['total']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #22c55e"><div class="admin-stat-label">Activos</div><div class="admin-stat-value"><?= number_format($stats['active']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #7c5cff"><div class="admin-stat-label">Técnicos</div><div class="admin-stat-value"><?= number_format($stats['technicians']) ?></div></div>
</div>

<div class="admin-card admin-card-pad mb-4">
    <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
        <div class="flex-1"><label class="admin-label">Buscar</label><input name="q" value="<?= $e($q) ?>" placeholder="Nombre o email…" class="admin-input"></div>
        <div>
            <label class="admin-label">Empresa</label>
            <select name="tenant_id" class="admin-select">
                <option value="">Todas</option>
                <?php foreach ($tenants as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $tenantId==$t['id']?'selected':'' ?>><?= $e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="admin-label">Rol</label>
            <select name="role" class="admin-select">
                <option value="">Todos</option>
                <option value="owner" <?= $role==='owner'?'selected':'' ?>>Owner</option>
                <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
                <option value="supervisor" <?= $role==='supervisor'?'selected':'' ?>>Supervisor</option>
                <option value="technician" <?= $role==='technician'?'selected':'' ?>>Técnico</option>
                <option value="agent" <?= $role==='agent'?'selected':'' ?>>Agente</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button class="admin-btn admin-btn-soft"><i class="lucide lucide-search"></i></button>
            <a href="<?= $url('/admin/users/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-user-plus"></i> Nuevo usuario</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>Usuario</th><th>Empresa</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:30px;height:30px;border-radius:8px;background:<?= Helpers::colorFor($u['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:12px"><?= Helpers::initials($u['name']) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:13px"><?= $e($u['name']) ?></div>
                            <div class="text-[11px] text-ink-400 font-mono"><?= $e($u['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td><a href="<?= $url('/admin/tenants/' . $u['tenant_id']) ?>" style="color:inherit; text-decoration:none"><?= $e($u['tenant_name']) ?></a></td>
                <td><span class="admin-pill admin-pill-gray"><?= $e($u['role_name'] ?? '—') ?></span></td>
                <td class="text-[11.5px] text-ink-500"><?= $u['last_login_at'] ? Helpers::ago($u['last_login_at']) : 'Nunca' ?></td>
                <td><?= $u['is_active'] ? '<span class="admin-pill admin-pill-green">Activo</span>' : '<span class="admin-pill admin-pill-gray">Inactivo</span>' ?></td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?= $url('/admin/users/' . $u['id']) ?>" class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-edit-3 text-[13px]"></i></a>
                        <form method="POST" action="<?= $url('/admin/users/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar usuario?')">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="admin-btn admin-btn-danger" style="padding:5px 10px"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?><tr><td colspan="6" style="text-align:center; padding:30px; color:#8e8e9a">Sin usuarios.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
