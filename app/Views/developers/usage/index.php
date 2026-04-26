<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="dev-stat">
        <div class="dev-stat-label">Requests este mes</div>
        <div class="dev-stat-value"><?= number_format($monthRequests) ?></div>
        <?php if ($quota > 0): ?>
            <div class="text-[11.5px] text-slate-400 mt-1">de <?= number_format($quota) ?> · <?= $pct ?>%</div>
            <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:rgba(56,189,248,.10)"><div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,#0ea5e9,#6366f1)"></div></div>
        <?php endif; ?>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Errores</div>
        <div class="dev-stat-value"><?= number_format($monthErrors) ?></div>
        <div class="dev-stat-icon" style="background:rgba(239,68,68,.10); color:#fca5a5"><i class="lucide lucide-alert-octagon text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Total histórico</div>
        <div class="dev-stat-value"><?= number_format($totalRequests) ?></div>
        <div class="dev-stat-icon"><i class="lucide lucide-database text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Apps activas</div>
        <div class="dev-stat-value"><?= count($byApp) ?></div>
        <div class="dev-stat-icon" style="background:rgba(124,92,255,.10); color:#c4b5fd"><i class="lucide lucide-boxes text-[15px]"></i></div>
    </div>
</div>

<div class="dev-card">
    <div class="dev-card-head">
        <h2 class="font-display font-bold text-white text-[16px]">Últimos 90 días</h2>
    </div>
    <div class="p-5"><canvas id="usageChart" height="80"></canvas></div>
</div>

<div class="dev-card">
    <div class="dev-card-head">
        <h2 class="font-display font-bold text-white text-[16px]">Por app</h2>
    </div>
    <?php if (empty($byApp)): ?>
        <div class="p-6 text-center text-[13px] text-slate-400">Aún no tienes apps con uso.</div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="dev-table">
                <thead><tr><th>App</th><th>Entorno</th><th>Requests/mes</th><th>Errores</th><th>Total histórico</th></tr></thead>
                <tbody>
                    <?php foreach ($byApp as $a): ?>
                        <tr>
                            <td><a href="<?= $url('/developers/apps/' . $a['id']) ?>" class="dev-link font-display font-bold"><?= $e($a['name']) ?></a></td>
                            <td><span class="dev-pill dev-pill-gray"><?= $e($a['environment']) ?></span></td>
                            <td class="text-white"><?= number_format((int)$a['month_requests']) ?></td>
                            <td class="text-red-300"><?= number_format((int)$a['month_errors']) ?></td>
                            <td class="text-slate-400"><?= number_format((int)$a['total_requests']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
(function(){
    const data = <?= json_encode($daily ?? []) ?>;
    const labels = data.map(d => d.period_date.substring(5));
    const values = data.map(d => parseInt(d.requests || 0));
    const errs = data.map(d => parseInt(d.errors || 0));
    const ctx = document.getElementById('usageChart');
    if (!ctx) return;
    new Chart(ctx, {
        type:'line',
        data: { labels, datasets:[
            { label:'Requests', data:values, borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,.18)', tension:.3, fill:true, pointRadius:1 },
            { label:'Errores', data:errs, borderColor:'#f87171', backgroundColor:'rgba(239,68,68,.06)', tension:.3, fill:false, pointRadius:1 }
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
