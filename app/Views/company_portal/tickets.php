<?php
use App\Core\Helpers;

$slug = $tenantPublic->slug;
$isManager = !empty($portalUser['is_company_manager']);
$totalPages = max(1, (int)ceil($total / $perPage));

$prMap = ['urgent'=>['#dc2626','Urgente'],'high'=>['#f59e0b','Alta'],'medium'=>['#3b82f6','Media'],'low'=>['#6b7280','Baja']];
$stMap = [
    'open'        => ['#3b82f6','Abierto','circle-dot'],
    'in_progress' => ['#f59e0b','En progreso','play-circle'],
    'on_hold'     => ['#6b7280','En espera','pause-circle'],
    'resolved'    => ['#16a34a','Resuelto','check-circle'],
    'closed'      => ['#0f172a','Cerrado','x-circle'],
];

ob_start(); ?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="font-display font-extrabold text-[22px] tracking-[-0.02em]">Tickets de la empresa</h1>
        <p class="text-[12.5px] text-ink-500"><?= number_format($total) ?> resultados · ordenados por más recientes</p>
    </div>
    <div class="flex gap-2">
        <?php if ($isManager): ?>
            <a href="<?= $url('/portal/' . $slug . '/company/export/tickets.csv?' . http_build_query($filters)) ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-download"></i> CSV</a>
            <a href="<?= $url('/portal/' . $slug . '/company/export/tickets.pdf?' . http_build_query($filters)) ?>" target="_blank" rel="noopener" class="btn btn-soft btn-sm"><i class="lucide lucide-printer"></i> PDF</a>
        <?php endif; ?>
        <a href="<?= $url('/portal/' . $slug . '/company/tickets/new') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo</a>
    </div>
</div>

<form method="GET" class="card card-pad mb-4">
    <div class="grid grid-cols-2 md:grid-cols-6 gap-2.5">
        <div class="md:col-span-2">
            <label class="label">Buscar</label>
            <input name="q" value="<?= $e($filters['q']) ?>" class="input" placeholder="Código, asunto, email…">
        </div>
        <div>
            <label class="label">Estado</label>
            <select name="status" class="input">
                <option value="">Todos</option>
                <?php foreach (['open'=>'Abierto','in_progress'=>'En progreso','on_hold'=>'En espera','resolved'=>'Resuelto','closed'=>'Cerrado'] as $k=>$lbl): ?>
                    <option value="<?= $k ?>" <?= $filters['status']===$k?'selected':'' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label">Prioridad</label>
            <select name="priority" class="input">
                <option value="">Todas</option>
                <?php foreach (['low'=>'Baja','medium'=>'Media','high'=>'Alta','urgent'=>'Urgente'] as $k=>$lbl): ?>
                    <option value="<?= $k ?>" <?= $filters['priority']===$k?'selected':'' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label">Categoría</label>
            <select name="category" class="input">
                <option value="0">Todas</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $filters['category']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label">Desde</label>
            <input type="date" name="from" value="<?= $e($filters['from']) ?>" class="input">
        </div>
        <div class="md:col-span-1">
            <label class="label">Hasta</label>
            <input type="date" name="to" value="<?= $e($filters['to']) ?>" class="input">
        </div>
        <div class="md:col-span-5 flex items-end justify-end gap-2">
            <a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="btn btn-ghost btn-sm">Limpiar</a>
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-filter"></i> Filtrar</button>
        </div>
    </div>
</form>

<div class="card overflow-hidden">
    <?php if (empty($tickets)): ?>
        <div class="text-center py-16">
            <i class="lucide lucide-inbox text-[28px] text-ink-300"></i>
            <h3 class="font-display font-bold mt-3">Sin resultados</h3>
            <p class="text-[12.5px] text-ink-400 mb-3">Probá ajustar los filtros o crear un nuevo ticket.</p>
            <a href="<?= $url('/portal/' . $slug . '/company/tickets/new') ?>" class="btn btn-primary btn-sm inline-flex"><i class="lucide lucide-plus"></i> Crear ticket</a>
        </div>
    <?php else: ?>
        <table class="w-full text-[13px]">
            <thead>
                <tr class="border-b border-[#ececef] bg-[#fafafb]">
                    <th class="text-left py-3 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Ticket</th>
                    <th class="text-left py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden md:table-cell">Solicitante</th>
                    <th class="text-left py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden lg:table-cell">Categoría</th>
                    <th class="text-left py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Estado</th>
                    <th class="text-left py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden sm:table-cell">Prioridad</th>
                    <th class="text-right py-3 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Creado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $t):
                [$prCol, $prLbl] = $prMap[$t['priority']] ?? ['#6b7280', $t['priority']];
                [$stCol, $stLbl, $stIc] = $stMap[$t['status']] ?? ['#6b7280', $t['status'], 'circle'];
            ?>
                <tr class="border-b border-[#ececef] hover:bg-[#fafafb] transition">
                    <td class="py-3 px-4">
                        <a href="<?= $url('/portal/' . $slug . '/company/tickets/' . $t['id']) ?>" class="flex items-center gap-2.5 min-w-0" style="text-decoration:none;color:inherit">
                            <div class="w-8 h-8 rounded-lg grid place-items-center shrink-0" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><i class="lucide lucide-<?= $stIc ?> text-[13px]"></i></div>
                            <div class="min-w-0">
                                <div class="font-mono text-[11px] text-ink-500"><?= $e($t['code']) ?></div>
                                <div class="font-display font-bold text-[13px] truncate max-w-[280px]"><?= $e($t['subject']) ?></div>
                            </div>
                        </a>
                    </td>
                    <td class="py-3 px-2 hidden md:table-cell">
                        <div class="text-[12.5px]"><?= $e($t['requester_name']) ?></div>
                        <div class="text-[11px] text-ink-400 truncate max-w-[180px]"><?= $e($t['requester_email']) ?></div>
                    </td>
                    <td class="py-3 px-2 hidden lg:table-cell">
                        <?php if ($t['category_name']): ?>
                            <span class="badge" style="background:<?= $e($t['category_color'] ?? '#94a3b8') ?>15;color:<?= $e($t['category_color'] ?? '#94a3b8') ?>"><?= $e($t['category_name']) ?></span>
                        <?php else: ?><span class="text-ink-300">—</span><?php endif; ?>
                    </td>
                    <td class="py-3 px-2"><span class="badge" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><?= $stLbl ?></span></td>
                    <td class="py-3 px-2 hidden sm:table-cell"><span class="badge" style="background:<?= $prCol ?>15;color:<?= $prCol ?>"><?= $prLbl ?></span></td>
                    <td class="py-3 px-4 text-right text-[11.5px] text-ink-500"><?= Helpers::ago($t['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1):
    $qs = $filters; ?>
    <div class="flex items-center justify-between mt-4 text-[12.5px]">
        <div class="text-ink-400">Página <?= $page ?> de <?= $totalPages ?></div>
        <div class="flex gap-1.5">
            <?php if ($page > 1): $qs['page'] = $page - 1; ?>
                <a href="?<?= http_build_query($qs) ?>" class="btn btn-soft btn-sm">← Anterior</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): $qs['page'] = $page + 1; ?>
                <a href="?<?= http_build_query($qs) ?>" class="btn btn-soft btn-sm">Siguiente →</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
