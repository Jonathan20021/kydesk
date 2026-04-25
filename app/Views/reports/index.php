<?php use App\Core\Helpers; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Reportes</h1>
        <p class="text-[13px] text-ink-400">Métricas y rendimiento de tu equipo</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="date-range"><i class="lucide lucide-calendar text-[14px]"></i> Últimos 30 días</span>
        <button class="btn btn-outline btn-sm"><i class="lucide lucide-download"></i> Exportar CSV</button>
    </div>
</div>

<?php
$total = array_sum(array_column($byStatus, 'c'));
$resolved = 0; $open = 0;
foreach ($byStatus as $s) { if (in_array($s['status'], ['resolved','closed'])) $resolved += (int)$s['c']; if ($s['status'] === 'open') $open = (int)$s['c']; }
$rate = $total ? round($resolved * 100 / $total) : 0;
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <?php foreach ([
        ['Tickets totales', number_format($total), 'inbox', '#f3e8ff','#7e22ce', '+12%'],
        ['Tasa resolución', $rate . '%', 'target', '#d1fae5','#047857', '+4%'],
        ['Abiertos ahora', number_format($open), 'activity', '#dbeafe','#1d4ed8', null],
        ['Tiempo medio', round($avgResolve) . ' h', 'clock', '#fef3c7','#b45309', '-8%'],
    ] as [$l,$v,$ic,$bg,$col,$delta]): ?>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
            <div class="min-w-0 flex-1">
                <div class="stat-mini-meta"><?= $l ?></div>
                <div class="flex items-baseline gap-2">
                    <div class="stat-mini-title"><?= $v ?></div>
                    <?php if ($delta): ?><span class="text-[10.5px] font-bold <?= str_starts_with($delta,'-')?'text-rose-600':'text-emerald-600' ?>"><?= $delta ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card card-pad">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="section-title">Creados vs resueltos</h3>
                <p class="text-[12px] mt-0.5 text-ink-400">Evolución diaria · Últimos 30 días</p>
            </div>
            <div class="flex items-center gap-3 text-[11.5px]">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#7c5cff"></span> Creados</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#16a34a"></span> Resueltos</span>
            </div>
        </div>
        <div class="h-64 mt-4"><canvas id="chartCR"></canvas></div>
    </div>
    <div class="card card-pad">
        <h3 class="section-title">Por canal</h3>
        <p class="text-[12px] mt-0.5 text-ink-400">Origen de los tickets</p>
        <div class="h-56 mt-4"><canvas id="chartChannel"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card card-pad">
        <h3 class="section-title">Estado</h3>
        <p class="text-[12px] mt-0.5 text-ink-400">Distribución actual</p>
        <div class="h-48 mt-4"><canvas id="chartSt"></canvas></div>
    </div>
    <div class="card card-pad">
        <h3 class="section-title">Prioridad</h3>
        <p class="text-[12px] mt-0.5 text-ink-400">Por nivel de urgencia</p>
        <div class="h-48 mt-4"><canvas id="chartPr"></canvas></div>
    </div>
    <div class="card card-pad">
        <h3 class="section-title">Categorías</h3>
        <p class="text-[12px] mt-0.5 text-ink-400">Tipos de incidencia</p>
        <div class="h-48 mt-4"><canvas id="chartCat"></canvas></div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="px-7 pt-6 pb-2 flex items-center justify-between">
        <div>
            <h3 class="section-title">Desempeño de técnicos</h3>
            <p class="text-[12px] mt-0.5 text-ink-400">Ranking por tasa de resolución</p>
        </div>
        <span class="badge badge-purple"><i class="lucide lucide-trophy text-[11px]"></i> <?= count($agentPerf) ?> técnicos</span>
    </div>
    <table class="table">
        <thead><tr><th>Técnico</th><th>Total</th><th>Resueltos</th><th>Tiempo medio</th><th class="w-[280px]">% Resolución</th></tr></thead>
        <tbody>
            <?php foreach ($agentPerf as $a): $pct = $a['total'] ? round($a['resolved'] * 100 / $a['total']) : 0; ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="avatar avatar-md" style="background:<?= Helpers::colorFor($a['name']) ?>;color:white"><?= Helpers::initials($a['name']) ?></div>
                            <span class="font-display font-bold text-[13px]"><?= $e($a['name']) ?></span>
                        </div>
                    </td>
                    <td class="font-mono text-[12.5px]"><?= (int)$a['total'] ?></td>
                    <td class="font-mono text-[12.5px] text-emerald-600 font-bold"><?= (int)$a['resolved'] ?></td>
                    <td class="font-mono text-[12.5px]"><?= round((float)$a['avg_hours']) ?> h</td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="progress flex-1 max-w-[200px]"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $pct>=75?'#16a34a':($pct>=50?'#7c5cff':'#f59e0b') ?>"></div></div>
                            <span class="font-mono text-[12px] font-bold text-ink-700 w-10 text-right"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($agentPerf)): ?><tr><td colspan="5" class="text-center py-12 text-ink-400"><i class="lucide lucide-bar-chart-3 text-[24px] block mb-2 text-ink-300"></i> Sin métricas aún</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<script>
(function(){
    Chart.defaults.font.family='Inter';
    Chart.defaults.color='#8e8e9a';
    Chart.defaults.font.size=11;
    const series = <?= json_encode($series) ?>;
    const dates = Object.keys(series);
    const labels = dates.map(d=>{const p=d.split('-'); return p[2]+'/'+p[1];});
    const created = dates.map(d=>series[d].created);
    const resolved = dates.map(d=>series[d].resolved);

    const c1=document.getElementById('chartCR');
    if(c1){
        const g1=c1.getContext('2d').createLinearGradient(0,0,0,260);g1.addColorStop(0,'rgba(124,92,255,.22)');g1.addColorStop(1,'rgba(124,92,255,0)');
        const g2=c1.getContext('2d').createLinearGradient(0,0,0,260);g2.addColorStop(0,'rgba(22,163,74,.22)');g2.addColorStop(1,'rgba(22,163,74,0)');
        new Chart(c1,{type:'line',data:{labels,datasets:[
            {label:'Creados',data:created,borderColor:'#7c5cff',backgroundColor:g1,fill:true,tension:.4,borderWidth:2.5,pointRadius:0,pointHoverRadius:5,pointHoverBackgroundColor:'#7c5cff'},
            {label:'Resueltos',data:resolved,borderColor:'#16a34a',backgroundColor:g2,fill:true,tension:.4,borderWidth:2.5,pointRadius:0,pointHoverRadius:5,pointHoverBackgroundColor:'#16a34a'},
        ]},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:false},tooltip:{backgroundColor:'#16151b',padding:10,cornerRadius:10,boxPadding:4,titleFont:{weight:'700'}}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,grid:{color:'#ececef'},border:{display:false},ticks:{precision:0}}}}});
    }

    function donut(id,data,colors){const el=document.getElementById(id);if(!el)return;new Chart(el,{type:'doughnut',data:{labels:data.map(d=>d[0]),datasets:[{data:data.map(d=>+d[1]),backgroundColor:colors,borderWidth:0,hoverOffset:8}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{position:'bottom',labels:{boxWidth:8,padding:10,usePointStyle:true,font:{size:11}}},tooltip:{backgroundColor:'#16151b',padding:10,cornerRadius:10}}}});}
    donut('chartSt',<?= json_encode(array_map(fn($s)=>[$s['status'],$s['c']],$byStatus)) ?>,['#3b82f6','#f59e0b','#9ca3af','#16a34a','#9ca3af']);
    donut('chartPr',<?= json_encode(array_map(fn($s)=>[$s['priority'],$s['c']],$byPriority)) ?>,['#9ca3af','#7c5cff','#f59e0b','#ef4444']);
    donut('chartCat',<?= json_encode(array_map(fn($s)=>[$s['name'],$s['c']],$byCategory)) ?>,<?= json_encode(array_column($byCategory,'color')) ?>);

    const ch=document.getElementById('chartChannel');
    if(ch){const d=<?= json_encode($byChannel) ?>;new Chart(ch,{type:'bar',data:{labels:d.map(x=>x.channel),datasets:[{data:d.map(x=>+x.c),backgroundColor:'#7c5cff',borderRadius:10,barThickness:26,hoverBackgroundColor:'#6c47ff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#16151b',padding:10,cornerRadius:10}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,grid:{color:'#ececef'},border:{display:false},ticks:{precision:0}}}}});}
})();
</script>
