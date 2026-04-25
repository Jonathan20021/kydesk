<?php use App\Core\Helpers; $slug = $tenant->slug;
$triggerLabels = ['ticket.created'=>'Ticket creado','ticket.updated'=>'Ticket actualizado','ticket.sla_breach'=>'SLA en riesgo','ticket.escalated'=>'Ticket escalado','ticket.resolved'=>'Ticket resuelto']; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Automatizaciones</h1>
        <p class="text-[13px] text-ink-400">Reglas que ejecutan acciones sobre tus tickets</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#f3f0ff;color:#6c47ff"><i class="lucide lucide-workflow text-[18px]"></i></div><div class="flex-1"><div class="stat-mini-meta">Total</div><div class="stat-mini-title"><?= $stats['total'] ?></div></div></div>
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#d1fae5;color:#16a34a"><i class="lucide lucide-circle-check text-[18px]"></i></div><div class="flex-1"><div class="stat-mini-meta">Activas</div><div class="stat-mini-title"><?= $stats['active'] ?></div></div></div>
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#fef3c7;color:#b45309"><i class="lucide lucide-zap text-[18px]"></i></div><div class="flex-1"><div class="stat-mini-meta">Ejecuciones</div><div class="stat-mini-title"><?= number_format($stats['runs']) ?></div></div></div>
</div>

<div class="space-y-3">
    <?php foreach ($automations as $a): ?>
        <div class="card card-pad flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl grid place-items-center shrink-0" style="background:<?= $a['active']?'#d1fae5':'#f3f4f6' ?>;color:<?= $a['active']?'#16a34a':'#8e8e9a' ?>">
                <i class="lucide lucide-zap text-[18px]"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="font-display font-bold text-[15px]"><?= $e($a['name']) ?></h3>
                    <span class="badge badge-purple font-mono"><?= $e($triggerLabels[$a['trigger_event']] ?? $a['trigger_event']) ?></span>
                    <?php if (!$a['active']): ?><span class="badge badge-gray">Pausada</span><?php endif; ?>
                </div>
                <p class="text-[13px] mt-1.5 text-ink-500"><?= $e($a['description'] ?? '') ?></p>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-[11.5px] text-ink-400">
                    <span class="inline-flex items-center gap-1"><i class="lucide lucide-filter text-[12px]"></i> <code class="font-mono"><?= $e($a['conditions'] ?? '{}') ?></code></span>
                    <span class="inline-flex items-center gap-1"><i class="lucide lucide-arrow-right text-[12px]"></i> <code class="font-mono"><?= $e($a['actions'] ?? '{}') ?></code></span>
                </div>
                <div class="mt-2 flex items-center gap-3 text-[11.5px] text-ink-400">
                    <span><i class="lucide lucide-repeat text-[11px]"></i> <?= number_format($a['run_count']) ?> ejecuciones</span>
                    <?php if ($a['last_run_at']): ?><span><i class="lucide lucide-clock text-[11px]"></i> <?= Helpers::ago($a['last_run_at']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($auth->can('automations.edit')): ?>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/automations/' . $a['id'] . '/toggle') ?>">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="btn btn-outline btn-sm"><?= $a['active']?'Pausar':'Activar' ?></button>
                    </form>
                <?php endif; ?>
                <?php if ($auth->can('automations.delete')): ?>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/automations/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="btn btn-outline btn-icon-sm" style="color:#ef4444;border-color:#fecaca;background:#fef2f2"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($automations)): ?>
        <div class="card card-pad text-center py-16">
            <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-workflow text-[22px] text-ink-400"></i></div>
            <div class="font-display font-bold">Sin automatizaciones</div>
        </div>
    <?php endif; ?>
</div>
