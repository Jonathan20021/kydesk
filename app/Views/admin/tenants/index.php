<?php use App\Core\Helpers; ?>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #22c55e">
        <div class="admin-stat-label">Activas</div>
        <div class="admin-stat-value"><?= number_format($totalActive) ?></div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #f59e0b">
        <div class="admin-stat-label">Suspendidas</div>
        <div class="admin-stat-value"><?= number_format($totalSuspended) ?></div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #d946ef">
        <div class="admin-stat-label">Demo</div>
        <div class="admin-stat-value"><?= number_format($totalDemo) ?></div>
    </div>
</div>

<div class="admin-card admin-card-pad mb-4">
    <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
        <div class="flex-1">
            <label class="admin-label">Buscar</label>
            <input name="q" value="<?= $e($q) ?>" placeholder="Nombre, slug o email…" class="admin-input">
        </div>
        <div>
            <label class="admin-label">Estado</label>
            <select name="status" class="admin-select">
                <option value="">Todos</option>
                <option value="active" <?= $status==='active'?'selected':'' ?>>Activas</option>
                <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspendidas</option>
                <option value="demo" <?= $status==='demo'?'selected':'' ?>>Demo</option>
            </select>
        </div>
        <div>
            <label class="admin-label">Plan</label>
            <select name="plan" class="admin-select">
                <option value="">Todos</option>
                <?php foreach ($plans as $p): ?>
                    <option value="<?= $e($p['slug']) ?>" <?= $plan===$p['slug']?'selected':'' ?>><?= $e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="admin-btn admin-btn-soft"><i class="lucide lucide-search"></i> Filtrar</button>
            <a href="<?= $url('/admin/tenants/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus"></i> Nueva empresa</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Plan</th>
                <th>Suscripción</th>
                <th>Usuarios</th>
                <th>Tickets</th>
                <th>Creada</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tenants as $t): ?>
            <tr>
                <td>
                    <a href="<?= $url('/admin/tenants/' . $t['id']) ?>" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit">
                        <div style="width:34px;height:34px;border-radius:9px;background:<?= Helpers::colorFor($t['slug']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:13px"><?= Helpers::initials($t['name']) ?></div>
                        <div style="min-width:0">
                            <div style="font-weight:700; font-size:13px"><?= $e($t['name']) ?></div>
                            <div class="text-[11px] text-ink-400 font-mono"><?= $e($t['slug']) ?></div>
                        </div>
                    </a>
                </td>
                <td>
                    <span class="admin-pill admin-pill-purple"><?= $e($t['plan_name'] ?? ucfirst($t['plan'])) ?></span>
                </td>
                <td>
                    <?php if ($t['sub_status']): ?>
                        <div class="text-[12px]">$<?= number_format((float)$t['sub_amount'], 0) ?>/<?= $t['billing_cycle']==='yearly'?'año':'mes' ?></div>
                        <div class="text-[10.5px] text-ink-400"><?= $e($t['sub_status']) ?></div>
                    <?php else: ?>
                        <span class="text-[12px] text-ink-400">—</span>
                    <?php endif; ?>
                </td>
                <td><?= (int)$t['users_count'] ?></td>
                <td><?= (int)$t['tickets_count'] ?></td>
                <td class="text-[11.5px] text-ink-500"><?= Helpers::ago($t['created_at']) ?></td>
                <td>
                    <?php if ($t['is_demo']): ?><span class="admin-pill admin-pill-amber">Demo</span>
                    <?php elseif ($t['suspended_at']): ?><span class="admin-pill admin-pill-red">Suspendida</span>
                    <?php elseif ($t['is_active']): ?><span class="admin-pill admin-pill-green">Activa</span>
                    <?php else: ?><span class="admin-pill admin-pill-gray">Inactiva</span><?php endif; ?>
                </td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?= $url('/admin/tenants/' . $t['id']) ?>" class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-eye text-[13px]"></i></a>
                        <?php if ($t['is_active']): ?>
                            <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/impersonate') ?>" onsubmit="return confirm('¿Acceder como propietario de esta empresa?')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="admin-btn admin-btn-soft" style="padding:5px 10px" title="Impersonar"><i class="lucide lucide-log-in text-[13px]"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($tenants)): ?>
            <tr><td colspan="8" style="text-align:center; padding:30px; color:#8e8e9a">No hay empresas con esos filtros.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
