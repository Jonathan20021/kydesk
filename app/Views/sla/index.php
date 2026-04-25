<?php $slug = $tenant->slug; ?>

<div>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Políticas SLA</h1>
    <p class="text-[13px] text-ink-400">Tiempos de respuesta y resolución por prioridad</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#d1fae5;color:#16a34a"><i class="lucide lucide-circle-check text-[18px]"></i></div><div class="flex-1"><div class="stat-mini-meta">Cumplimiento</div><div class="stat-mini-title"><?= $compliance ?></div></div></div>
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#fef3c7;color:#b45309"><i class="lucide lucide-alert-triangle text-[18px]"></i></div><div class="flex-1"><div class="stat-mini-meta">En riesgo</div><div class="stat-mini-title"><?= $atRisk ?></div></div></div>
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#fee2e2;color:#b91c1c"><i class="lucide lucide-circle-x text-[18px]"></i></div><div class="flex-1"><div class="stat-mini-meta">Brechados</div><div class="stat-mini-title"><?= $breached ?></div></div></div>
</div>

<div class="card overflow-hidden">
    <div class="px-6 pt-5">
        <h3 class="section-title">Políticas</h3>
        <p class="text-[12px] mt-0.5 text-ink-400">Tiempos por prioridad</p>
    </div>
    <div class="px-3 py-3 mt-2">
        <?php foreach ($policies as $p):
            $col = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'][$p['priority']] ?? '#7c5cff'; ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/sla/' . $p['id']) ?>" class="flex flex-wrap items-center gap-3 p-3 rounded-2xl hover:bg-[#f3f4f6]">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <span class="dot" style="background:<?= $col ?>"></span>
                <input name="name" value="<?= $e($p['name']) ?>" class="input" style="height:36px;border-radius:10px;max-width:240px;font-weight:600">
                <span class="status-pill priority-<?= $p['priority'] ?>"><?= ucfirst($p['priority']) ?></span>
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] text-ink-400">Respuesta</label>
                    <input name="response_minutes" type="number" value="<?= (int)$p['response_minutes'] ?>" class="input font-mono" style="width:80px;height:36px;border-radius:10px">
                    <span class="text-[11px] text-ink-400">min</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] text-ink-400">Resolución</label>
                    <input name="resolve_minutes" type="number" value="<?= (int)$p['resolve_minutes'] ?>" class="input font-mono" style="width:80px;height:36px;border-radius:10px">
                    <span class="text-[11px] text-ink-400">min</span>
                </div>
                <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox" name="active" value="1" <?= $p['active']?'checked':'' ?>> Activa</label>
                <button class="btn btn-primary btn-xs ml-auto"><i class="lucide lucide-check text-[12px]"></i> Guardar</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
