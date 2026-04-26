<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="dev-stat">
        <div class="dev-stat-label">Requests este mes</div>
        <div class="dev-stat-value"><?= number_format($monthRequests) ?></div>
        <div class="dev-stat-icon"><i class="lucide lucide-activity text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Errores</div>
        <div class="dev-stat-value"><?= number_format($monthErrors) ?></div>
        <div class="dev-stat-icon" style="background:rgba(239,68,68,.10); color:#fca5a5"><i class="lucide lucide-alert-octagon text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Apps activas</div>
        <div class="dev-stat-value"><?= count($apps) ?></div>
        <div class="dev-stat-icon" style="background:rgba(124,92,255,.10); color:#c4b5fd"><i class="lucide lucide-boxes text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Facturas pendientes</div>
        <div class="dev-stat-value"><?= number_format($unpaidInvoices) ?></div>
        <div class="dev-stat-icon" style="background:rgba(245,158,11,.10); color:#fcd34d"><i class="lucide lucide-receipt text-[15px]"></i></div>
    </div>
</div>

<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Uso últimos 30 días</h2>
            <p class="text-[12px] text-slate-400">Requests por día</p>
        </div>
    </div>
    <div class="p-5">
        <canvas id="usageChart" height="80"></canvas>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <div class="dev-card">
        <div class="dev-card-head">
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Tus apps</h2>
                <p class="text-[12px] text-slate-400"><?= count($apps) ?> en total</p>
            </div>
            <a href="<?= $url('/developers/apps') ?>" class="dev-btn dev-btn-soft">Ver todas</a>
        </div>
        <?php if (empty($apps)): ?>
            <div class="p-6 text-center text-[13px] text-slate-400">
                Aún no tienes apps. <a href="<?= $url('/developers/apps/create') ?>" class="dev-link">Crea la primera</a>.
            </div>
        <?php else: ?>
            <div class="divide-y" style="border-color:rgba(56,189,248,.06)">
                <?php foreach ($apps as $a): ?>
                    <a href="<?= $url('/developers/apps/' . $a['id']) ?>" class="flex items-center gap-3 p-4 hover:bg-sky-500/5 transition" style="border-bottom:1px solid rgba(56,189,248,.06)">
                        <div class="w-9 h-9 rounded-xl grid place-items-center bg-sky-500/15 text-sky-300 border border-sky-500/20"><i class="lucide lucide-box text-[14px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-white text-[14px] truncate"><?= $e($a['name']) ?></div>
                            <div class="text-[11.5px] text-slate-400 truncate"><?= $e($a['slug']) ?></div>
                        </div>
                        <span class="dev-pill <?= $a['status'] === 'active' ? 'dev-pill-emerald' : 'dev-pill-red' ?>"><?= $e($a['status']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dev-card">
        <div class="dev-card-head">
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Tokens recientes</h2>
                <p class="text-[12px] text-slate-400">Activos ahora</p>
            </div>
        </div>
        <?php if (empty($tokens)): ?>
            <div class="p-6 text-center text-[13px] text-slate-400">No hay tokens. Crea una app para generar uno.</div>
        <?php else: ?>
            <div>
                <?php foreach ($tokens as $t): ?>
                    <div class="flex items-center gap-3 p-4" style="border-bottom:1px solid rgba(56,189,248,.06)">
                        <div class="w-9 h-9 rounded-xl grid place-items-center bg-indigo-500/15 text-indigo-300 border border-indigo-500/20"><i class="lucide lucide-key text-[14px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-white text-[14px] truncate"><?= $e($t['name']) ?></div>
                            <div class="text-[11px] font-mono text-slate-400 truncate"><?= $e($t['token_preview']) ?></div>
                        </div>
                        <div class="text-[11px] text-slate-400 text-right">
                            <?= $t['last_used_at'] ? '<i class="lucide lucide-circle text-emerald-400 text-[8px]"></i> Usado' : '<i class="lucide lucide-circle text-slate-600 text-[8px]"></i> Sin uso' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($invoices)): ?>
<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Facturas recientes</h2>
        </div>
        <a href="<?= $url('/developers/billing') ?>" class="dev-btn dev-btn-soft">Ver todas</a>
    </div>
    <div style="overflow-x:auto">
        <table class="dev-table">
            <thead><tr><th>Número</th><th>Estado</th><th>Total</th><th>Vence</th></tr></thead>
            <tbody>
                <?php foreach ($invoices as $i): ?>
                    <tr>
                        <td><a href="<?= $url('/developers/billing/invoices/' . $i['id']) ?>" class="dev-link font-mono"><?= $e($i['invoice_number']) ?></a></td>
                        <td><span class="dev-pill <?= $i['status']==='paid'?'dev-pill-emerald':($i['status']==='overdue'?'dev-pill-red':'dev-pill-amber') ?>"><?= $e($i['status']) ?></span></td>
                        <td class="text-white font-display font-bold">$<?= number_format((float)$i['total'], 2) ?></td>
                        <td><?= $e($i['due_date'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
(function(){
    const data = <?= json_encode($usageDaily ?? []) ?>;
    const labels = data.map(d => d.period_date.substring(5));
    const values = data.map(d => parseInt(d.requests || 0));
    const errors = data.map(d => parseInt(d.errors || 0));
    const ctx = document.getElementById('usageChart');
    if (!ctx) return;
    new Chart(ctx, {
        type:'line',
        data: { labels, datasets:[
            { label:'Requests', data:values, borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,.15)', tension:.3, fill:true, pointRadius:2 },
            { label:'Errores', data:errors, borderColor:'#f87171', backgroundColor:'rgba(239,68,68,.08)', tension:.3, fill:false, pointRadius:1 }
        ] },
        options: {
            plugins: { legend: { labels: { color:'#cbd5e1' } } },
            scales: {
                x: { ticks: { color:'#64748b' }, grid:{ color:'rgba(56,189,248,.06)' } },
                y: { ticks: { color:'#64748b' }, grid:{ color:'rgba(56,189,248,.06)' } }
            }
        }
    });
})();
</script>
