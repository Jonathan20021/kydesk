<?php use App\Core\Helpers; $slug = $tenant->slug;
$actionIcons = ['ticket.created'=>'plus-circle','ticket.updated'=>'edit-3','ticket.assigned'=>'user-plus','ticket.escalated'=>'trending-up','ticket.deleted'=>'trash-2']; ?>

<div>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Auditoría</h1>
    <p class="text-[13px] text-ink-400">Registro de eventos del sistema</p>
</div>

<form method="GET" class="flex gap-3">
    <select name="action" class="input" style="max-width:240px;height:44px;border-radius:999px">
        <option value="">Cualquier acción</option>
        <?php foreach (array_keys($actionIcons) as $a): ?><option value="<?= $a ?>" <?= $actionFilter===$a?'selected':'' ?>><?= str_replace('.',' → ', $a) ?></option><?php endforeach; ?>
    </select>
    <select name="entity" class="input" style="max-width:200px;height:44px;border-radius:999px">
        <option value="">Cualquier entidad</option>
        <?php foreach (['ticket','user','company','asset'] as $en): ?><option value="<?= $en ?>" <?= $entityFilter===$en?'selected':'' ?>><?= ucfirst($en) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm">Filtrar</button>
</form>

<div class="card overflow-hidden">
    <table class="table">
        <thead><tr><th></th><th>Usuario</th><th>Acción</th><th>Detalles</th><th>IP</th><th>Cuándo</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $l): $ic = $actionIcons[$l['action']] ?? 'activity'; ?>
                <tr>
                    <td><div class="w-10 h-10 rounded-xl bg-[#f3f4f6] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $ic ?> text-[14px]"></i></div></td>
                    <td>
                        <?php if ($l['user_name']): ?>
                            <div class="flex items-center gap-2">
                                <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($l['user_email'] ?? '') ?>;color:white"><?= Helpers::initials($l['user_name']) ?></div>
                                <span class="text-[12.5px] font-medium"><?= $e($l['user_name']) ?></span>
                            </div>
                        <?php else: ?><span class="text-[12px] text-ink-400">Sistema</span><?php endif; ?>
                    </td>
                    <td><span class="badge badge-purple font-mono"><?= $e($l['action']) ?></span></td>
                    <td>
                        <div class="text-[12.5px]"><?= $e($l['entity'] ?? '') ?> <?php if ($l['entity_id']): ?><span class="text-ink-400">#<?= (int)$l['entity_id'] ?></span><?php endif; ?></div>
                        <?php if ($l['meta']): ?><div class="text-[11px] font-mono truncate max-w-[400px] text-ink-400"><?= $e($l['meta']) ?></div><?php endif; ?>
                    </td>
                    <td class="font-mono text-[11.5px] text-ink-400"><?= $e($l['ip'] ?? '—') ?></td>
                    <td class="text-[11.5px] text-ink-400"><?= Helpers::ago($l['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?><tr><td colspan="6" class="text-center py-12 text-ink-400">Sin registros</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
