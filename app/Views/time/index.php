<?php $slug = $tenant->slug; ?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Time Tracking</h1>
        <p class="text-[13px] text-ink-400">Cronómetro por ticket integrado a Igualas · descuenta horas automáticamente</p>
    </div>
</div>

<?php if ($running): ?>
    <div class="card card-pad mb-4" style="background:linear-gradient(135deg,#fef3c7,#fef9c3);border-color:#fde68a">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="w-11 h-11 rounded-xl bg-amber-500 text-white grid place-items-center"><i class="lucide lucide-timer text-[18px]"></i></div>
            <div class="flex-1 min-w-0">
                <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-amber-700">Timer activo</div>
                <div class="font-display font-bold text-[14.5px] truncate"><?= $e($running['description'] ?: 'Sin descripción') ?></div>
                <?php if ($running['ticket_code']): ?>
                    <div class="text-[12px] text-ink-500 mt-0.5">
                        <a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$running['ticket_id']) ?>" class="font-mono text-brand-700"><?= $e($running['ticket_code']) ?></a>
                        · <?= $e($running['ticket_subject']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="font-mono font-bold text-[20px] text-amber-700 tabular-nums" x-data="{secs: Math.floor((Date.now() - new Date('<?= str_replace(' ','T',$running['started_at']) ?>')) / 1000), label:'00:00:00'}" x-init="setInterval(()=>{secs++; const h=Math.floor(secs/3600).toString().padStart(2,'0'); const m=Math.floor((secs%3600)/60).toString().padStart(2,'0'); const s=(secs%60).toString().padStart(2,'0'); label=h+':'+m+':'+s;}, 1000)" x-text="label"></div>
            <form method="POST" action="<?= $url('/t/' . $slug . '/time/' . (int)$running['id'] . '/stop') ?>">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-primary"><i class="lucide lucide-square"></i> Detener</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Total horas</div><div class="font-display font-extrabold text-[26px]"><?= number_format($totalHours, 2) ?>h</div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Facturables</div><div class="font-display font-extrabold text-[26px] text-emerald-600"><?= number_format($billableHours, 2) ?>h</div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Monto total</div><div class="font-display font-extrabold text-[26px] text-brand-700">$<?= number_format($totalAmount, 2) ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Entradas</div><div class="font-display font-extrabold text-[26px]"><?= count($entries) ?></div></div>
</div>

<form method="GET" class="card card-pad mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">
    <div><label class="label">Desde</label><input name="from" type="date" value="<?= $e($from) ?>" class="input"></div>
    <div><label class="label">Hasta</label><input name="to" type="date" value="<?= $e($to) ?>" class="input"></div>
    <div>
        <label class="label">Usuario</label>
        <select name="user_id" class="input">
            <option value="">Todos</option>
            <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $userId===(int)$u['id']?'selected':'' ?>><?= $e($u['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="label">Facturable</label>
        <select name="billable" class="input">
            <option value="">Todos</option>
            <option value="yes" <?= $billable==='yes'?'selected':'' ?>>Sí</option>
            <option value="no" <?= $billable==='no'?'selected':'' ?>>No</option>
        </select>
    </div>
    <div class="flex items-end"><button class="btn btn-primary w-full">Filtrar</button></div>
</form>

<div class="card overflow-hidden">
    <div class="p-4 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
        <h3 class="font-display font-bold text-[15px]">Entradas</h3>
        <details class="relative">
            <summary class="btn btn-soft btn-sm cursor-pointer list-none"><i class="lucide lucide-plus text-[12px]"></i> Registrar manual</summary>
            <form method="POST" action="<?= $url('/t/' . $slug . '/time/manual') ?>" class="absolute right-0 mt-2 w-80 card card-pad space-y-2 z-20" style="box-shadow:0 8px 30px -8px rgba(22,21,27,.18)">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">Horas</label><input name="hours" type="number" step="0.25" min="0.25" required class="input"></div>
                    <div><label class="label">Tarifa</label><input name="rate" type="number" step="0.01" value="0" class="input"></div>
                </div>
                <div><label class="label">Inicio</label><input name="started_at" type="datetime-local" value="<?= date('Y-m-d\TH:i') ?>" class="input"></div>
                <div><label class="label">Descripción</label><input name="description" class="input"></div>
                <label class="flex items-center gap-2 text-[12.5px]"><input type="checkbox" name="billable" value="1" checked> Facturable</label>
                <button class="btn btn-primary w-full">Registrar</button>
            </form>
        </details>
    </div>
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Ticket</th><th>Iguala</th><th>Descripción</th><th class="text-right">Horas</th><th class="text-right">Monto</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($entries as $e_):
            $isRunning = (int)$e_['is_running'] === 1;
        ?>
            <tr<?= $isRunning ? ' style="background:#fef3c7"' : '' ?>>
                <td class="text-[11.5px] text-ink-500 font-mono"><?= $e($e_['started_at']) ?></td>
                <td class="text-[12px]"><?= $e($e_['user_name'] ?? '—') ?></td>
                <td>
                    <?php if (!empty($e_['ticket_code'])): ?>
                        <a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$e_['ticket_id']) ?>" class="text-brand-700 font-mono text-[11.5px]"><?= $e($e_['ticket_code']) ?></a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($e_['retainer_code'])): ?>
                        <a href="<?= $url('/t/' . $slug . '/retainers/' . (int)$e_['retainer_id']) ?>" class="text-emerald-700 font-mono text-[11px]"><?= $e($e_['retainer_code']) ?></a>
                    <?php else: ?><span class="text-ink-400">—</span><?php endif; ?>
                </td>
                <td class="text-[12px] text-ink-700 max-w-xs line-clamp-1"><?= $e($e_['description'] ?: '—') ?></td>
                <td class="text-right font-mono">
                    <?php if ($isRunning): ?>
                        <span class="text-amber-700 font-bold">en curso</span>
                    <?php else: ?>
                        <strong><?= number_format((float)$e_['hours'], 2) ?>h</strong>
                    <?php endif; ?>
                    <?php if (!$e_['billable']): ?><span class="badge badge-gray ml-1">No fact.</span><?php endif; ?>
                </td>
                <td class="text-right font-mono">$<?= number_format((float)$e_['amount'], 2) ?></td>
                <td>
                    <?php if ($isRunning): ?>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/time/' . (int)$e_['id'] . '/stop') ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="btn btn-soft btn-xs"><i class="lucide lucide-square text-[10px]"></i></button></form>
                    <?php else: ?>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/time/' . (int)$e_['id'] . '/delete') ?>" onsubmit="return confirm('Eliminar?')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="btn btn-soft btn-xs" style="color:#b91c1c"><i class="lucide lucide-trash-2 text-[10px]"></i></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($entries)): ?>
            <tr><td colspan="8" style="text-align:center;padding:24px;color:#8e8e9a">Sin entradas en este rango.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
