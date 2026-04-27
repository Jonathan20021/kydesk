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
    <div class="px-3 pb-3 mt-2 space-y-2">
        <?php
        $colMap = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
        $hum = function($mins){ if($mins<60) return $mins.' min'; if($mins<1440) return round($mins/60,1).' h'; return round($mins/1440,1).' días'; };
        foreach ($policies as $p):
            $col = $colMap[$p['priority']] ?? '#7c5cff'; ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/sla/' . $p['id']) ?>" class="rounded-2xl border border-[#ececef] hover:border-[#d4d4dc] transition p-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div class="flex flex-wrap items-center gap-2.5 mb-3">
                    <div class="w-9 h-9 rounded-xl grid place-items-center shrink-0" style="background:<?= $col ?>15;color:<?= $col ?>;border:1px solid <?= $col ?>30"><i class="lucide lucide-gauge text-[15px]"></i></div>
                    <span class="status-pill priority-<?= $p['priority'] ?>"><?= ucfirst($p['priority']) ?></span>
                    <input name="name" value="<?= $e($p['name']) ?>" class="input flex-1" style="height:36px;border-radius:10px;font-weight:600;min-width:200px">
                    <?php if (!empty($departments) && !empty($hasDeptCol)): ?>
                        <select name="department_id" class="input shrink-0" style="height:36px;border-radius:10px;max-width:200px">
                            <option value="0">— Global —</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (int)($p['department_id']??0)===(int)$d['id']?'selected':'' ?>><?= $e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <label class="flex items-center gap-1.5 text-[12px] shrink-0"><input type="checkbox" name="active" value="1" <?= $p['active']?'checked':'' ?>> Activa</label>
                </div>
                <?php if (!empty($p['department_name'])): ?>
                    <div class="text-[11px] mb-2 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full" style="background:<?= $e($p['department_color']??'#3b82f6') ?>15;color:<?= $e($p['department_color']??'#3b82f6') ?>">
                        <i class="lucide lucide-layers text-[10px]"></i> Específica para: <?= $e($p['department_name']) ?>
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11.5px] font-semibold text-ink-700 flex items-center gap-1.5"><i class="lucide lucide-message-square-reply text-[13px] text-emerald-600"></i> Tiempo de primera respuesta</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input name="response_minutes" type="number" min="1" value="<?= (int)$p['response_minutes'] ?>" class="input font-mono" style="max-width:120px">
                            <span class="text-[12px] text-ink-400">min · ≈ <?= $hum((int)$p['response_minutes']) ?></span>
                        </div>
                    </div>
                    <div>
                        <label class="text-[11.5px] font-semibold text-ink-700 flex items-center gap-1.5"><i class="lucide lucide-flag text-[13px] text-rose-600"></i> Tiempo de resolución</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input name="resolve_minutes" type="number" min="1" value="<?= (int)$p['resolve_minutes'] ?>" class="input font-mono" style="max-width:120px">
                            <span class="text-[12px] text-ink-400">min · ≈ <?= $hum((int)$p['resolve_minutes']) ?></span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($p['description'])): ?>
                    <p class="text-[12px] text-ink-400 mt-3 italic"><?= $e($p['description']) ?></p>
                <?php endif; ?>
                <div class="mt-3 flex justify-end">
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-check text-[13px]"></i> Guardar cambios</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<div class="card card-pad" style="background:#f3f0ff;border-color:#cdbfff">
    <div class="flex items-start gap-2">
        <i class="lucide lucide-info text-brand-700 text-[16px] mt-0.5"></i>
        <div class="text-[12.5px] text-ink-700 leading-relaxed">
            <strong>¿Cómo funcionan?</strong> Cuando se crea un ticket, Kydesk asigna automáticamente la política SLA según su prioridad y calcula los tiempos límite. Los tickets que se acercan al vencimiento aparecen <strong>en riesgo</strong>; los que lo superan, como <strong>brechados</strong>.
        </div>
    </div>
</div>
