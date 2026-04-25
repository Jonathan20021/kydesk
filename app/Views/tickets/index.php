<?php use App\Core\Helpers; $slug = $tenant->slug;
$priColor = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
$catBgMap = ['Hardware'=>['#fef3c7','#b45309'],'Software'=>['#dbeafe','#1d4ed8'],'Red e infraestructura'=>['#d1fae5','#047857'],'Cuentas y accesos'=>['#fce7f3','#be185d'],'Seguridad'=>['#fee2e2','#b91c1c'],'Otros'=>['#f3f4f6','#6b6b78']]; ?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Tickets</h1>
        <p class="text-[13px] text-ink-400"><?= number_format($counts['all']) ?> en total · <?= number_format($counts['open'] + $counts['in_progress']) ?> activos</p>
    </div>
    <div class="flex items-center gap-2">
        <div class="segmented">
            <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="active"><i class="lucide lucide-list text-[13px]"></i> Lista</a>
            <a href="<?= $url('/t/' . $slug . '/tickets/board') ?>"><i class="lucide lucide-kanban-square text-[13px]"></i> Tablero</a>
        </div>
        <?php if ($auth->can('tickets.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/tickets/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo ticket</a>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs estado tipo segmented largo -->
<div class="card card-pad" style="padding:14px 16px">
    <div class="flex flex-wrap gap-1.5">
        <?php
        $tabs = [
            ['', 'Todos', $counts['all']],
            ['open', 'Abiertos', $counts['open']],
            ['in_progress', 'En progreso', $counts['in_progress']],
            ['on_hold', 'En espera', $counts['on_hold']],
            ['resolved', 'Resueltos', $counts['resolved']],
            ['closed', 'Cerrados', $counts['closed']],
        ];
        foreach ($tabs as [$v, $l, $c]):
            $active = ($filters['status'] ?? '') === $v; ?>
            <a href="<?= $url('/t/' . $slug . '/tickets' . ($v ? '?status=' . $v : '')) ?>" class="<?= $active ? 'btn btn-dark btn-sm' : 'btn btn-ghost btn-sm' ?>">
                <?= $l ?>
                <span class="kbd <?= $active?'':'' ?>" style="<?= $active?'background:rgba(255,255,255,.18);color:white;border-color:transparent':'' ?>"><?= $c ?></span>
            </a>
        <?php endforeach; ?>
        <a href="<?= $url('/t/' . $slug . '/tickets?assigned=me') ?>" class="<?= ($filters['assigned']??'')==='me' ? 'btn btn-primary btn-sm ml-auto' : 'btn btn-soft btn-sm ml-auto' ?>">
            <i class="lucide lucide-user text-[13px]"></i> Míos · <?= $counts['mine'] ?>
        </a>
    </div>
</div>

<!-- Filtros -->
<form method="GET" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="search-pill" style="max-width:none"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($filters['q']) ?>" placeholder="Buscar por asunto, código, email…"></div>
    <select name="priority" class="input">
        <option value="">Cualquier prioridad</option>
        <?php foreach ([['low','Baja'],['medium','Media'],['high','Alta'],['urgent','Urgente']] as [$v,$l]): ?>
            <option value="<?= $v ?>" <?= $filters['priority']===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
    </select>
    <select name="category" class="input">
        <option value="0">Cualquier categoría</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$filters['category']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="assigned" class="input">
        <option value="">Cualquier técnico</option>
        <option value="me" <?= $filters['assigned']==='me'?'selected':'' ?>>Míos</option>
        <option value="unassigned" <?= $filters['assigned']==='unassigned'?'selected':'' ?>>Sin asignar</option>
        <?php foreach ($technicians as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $filters['assigned']===(string)$t['id']?'selected':'' ?>><?= $e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= $e($filters['status']) ?>"><?php endif; ?>
</form>

<!-- Lista (tabla con avatares y acción) -->
<div class="card overflow-hidden">
    <?php if (empty($tickets)): ?>
        <div class="empty-state py-20">
            <div class="empty-illust"><i class="lucide lucide-inbox text-[28px]"></i></div>
            <div class="empty-state-title">Sin tickets</div>
            <p class="empty-state-text mb-5">Ajusta filtros o crea un nuevo ticket</p>
            <?php if ($auth->can('tickets.create')): ?>
                <a href="<?= $url('/t/' . $slug . '/tickets/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo ticket</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Asignado</th>
                    <th>Empresa</th>
                    <th class="text-right">Actualizado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t):
                    [$bg, $col] = $catBgMap[$t['category_name'] ?? 'Otros'] ?? ['#f3e8ff','#7e22ce']; ?>
                    <tr onclick="location.href='<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>'" style="cursor:pointer">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-ticket text-base"></i></div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-[10.5px] font-mono text-ink-400"><?= $e($t['code']) ?></span>
                                        <?php if ((int)$t['escalation_level'] > 0): ?>
                                            <span class="badge badge-rose">N<?= (int)$t['escalation_level']+1 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="font-display font-bold text-[13.5px] truncate max-w-[340px]"><?= $e($t['subject']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= Helpers::statusBadge($t['status']) ?></td>
                        <td><?= Helpers::priorityBadge($t['priority']) ?></td>
                        <td>
                            <?php if ($t['assigned_name']): ?>
                                <div class="flex items-center gap-2.5">
                                    <div class="avatar avatar-sm" style="background: <?= Helpers::colorFor($t['assigned_email'] ?? '') ?>;color:white;"><?= Helpers::initials($t['assigned_name']) ?></div>
                                    <span class="text-[12.5px] font-medium"><?= $e($t['assigned_name']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-[12px] text-ink-400">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-[12.5px] text-ink-500"><?= $e($t['company_name'] ?? '—') ?></td>
                        <td class="text-[11.5px] text-right text-ink-400"><?= Helpers::ago($t['updated_at']) ?></td>
                        <td><span class="table-action"><i class="lucide lucide-arrow-up-right text-[13px]"></i></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
