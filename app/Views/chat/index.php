<?php $slug = $tenant->slug; ?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Live Chat</h1>
        <p class="text-[13px] text-ink-400">Conversaciones en vivo desde el widget embebible</p>
    </div>
    <a href="<?= $url('/t/' . $slug . '/chat/widgets') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-settings-2"></i> Configurar widget</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Abiertos</div><div class="font-display font-extrabold text-[26px] text-emerald-600"><?= $stats['open'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Cerrados hoy</div><div class="font-display font-extrabold text-[26px]"><?= $stats['closed_today'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Total histórico</div><div class="font-display font-extrabold text-[26px] text-brand-700"><?= $stats['total'] ?></div></div>
</div>

<div class="card overflow-hidden">
    <table class="admin-table">
        <thead><tr><th>Estado</th><th>Visitante</th><th>Último mensaje</th><th>Mensajes</th><th>Asignado</th><th>Iniciado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($convos as $c): ?>
            <tr style="cursor:pointer" onclick="location='<?= $url('/t/' . $slug . '/chat/' . (int)$c['id']) ?>'">
                <td>
                    <?php if ($c['status'] === 'open'): ?><span class="admin-pill admin-pill-amber">Sin asignar</span>
                    <?php elseif ($c['status'] === 'assigned'): ?><span class="admin-pill admin-pill-blue">Asignada</span>
                    <?php else: ?><span class="admin-pill admin-pill-gray">Cerrada</span><?php endif; ?>
                </td>
                <td>
                    <div class="font-display font-bold text-[13px]"><?= $e($c['visitor_name'] ?: 'Visitante') ?></div>
                    <?php if (!empty($c['visitor_email'])): ?><div class="text-[11px] font-mono text-ink-400"><?= $e($c['visitor_email']) ?></div><?php endif; ?>
                </td>
                <td class="text-[12px] text-ink-700 max-w-md line-clamp-1"><?= $e($c['last_message'] ?: '—') ?></td>
                <td class="text-[12px]"><?= (int)$c['msg_count'] ?></td>
                <td class="text-[12px]"><?= $e($c['assigned_name'] ?? '—') ?></td>
                <td class="text-[11.5px] font-mono text-ink-500"><?= $e($c['started_at']) ?></td>
                <td>
                    <a href="<?= $url('/t/' . $slug . '/chat/' . (int)$c['id']) ?>" onclick="event.stopPropagation()" class="btn btn-soft btn-xs"><i class="lucide lucide-arrow-right text-[12px]"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($convos)): ?>
            <tr><td colspan="7" style="text-align:center;padding:24px;color:#8e8e9a">Sin conversaciones aún. <a href="<?= $url('/t/' . $slug . '/chat/widgets') ?>" class="text-brand-700 font-semibold">Configurá tu widget</a> para empezar.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
