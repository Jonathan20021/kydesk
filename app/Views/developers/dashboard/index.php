<?php
$pct = $quota > 0 ? min(100, round(($monthRequests / $quota) * 100)) : 0;
$pctMinute = $rateLimit > 0 ? min(100, round(($minuteRequests / $rateLimit) * 100)) : 0;
$errorRate = $monthRequests > 0 ? round(($monthErrors / $monthRequests) * 100, 2) : 0;
$pctColor = $pct >= 95 ? '#f87171' : ($pct >= 80 ? '#fbbf24' : '#0ea5e9');
?>

<!-- Quota banner if approaching/exceeding -->
<?php if ($pct >= 80): ?>
<div class="dev-card dev-card-pad" style="border-color:<?= $pct >= 95 ? 'rgba(239,68,68,.4)' : 'rgba(245,158,11,.4)' ?>; background:<?= $pct >= 95 ? 'rgba(239,68,68,.05)' : 'rgba(245,158,11,.05)' ?>">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $pct >= 95 ? 'rgba(239,68,68,.15)' : 'rgba(245,158,11,.15)' ?>; color:<?= $pct >= 95 ? '#fca5a5' : '#fcd34d' ?>"><i class="lucide lucide-alert-triangle"></i></div>
        <div class="flex-1">
            <div class="font-display font-bold text-white text-[14.5px]"><?= $pct >= 100 ? 'Cuota mensual agotada' : 'Acercándote al límite mensual' ?></div>
            <div class="text-[12.5px] text-slate-300 mt-0.5">Has usado <?= number_format($monthRequests) ?> de <?= number_format($quota) ?> requests (<?= $pct ?>%). <?= $pct >= 100 ? 'La API puede empezar a rechazar requests.' : 'Considera mejorar tu plan o monitorear el uso.' ?></div>
        </div>
        <a href="<?= $url('/developers/billing/plans') ?>" class="dev-btn dev-btn-primary"><i class="lucide lucide-arrow-up text-[13px]"></i> Mejorar plan</a>
    </div>
</div>
<?php endif; ?>

<!-- Hero stats grid -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="dev-stat group cursor-pointer" onclick="location='<?= $url('/developers/usage') ?>'">
        <div class="flex items-center justify-between mb-1">
            <div class="dev-stat-label">Cuota mensual</div>
            <i class="lucide lucide-activity text-sky-300/60 text-[14px]"></i>
        </div>
        <div class="dev-stat-value"><?= number_format($monthRequests) ?></div>
        <?php if ($quota > 0): ?>
            <div class="text-[11px] text-slate-400 mt-1">de <?= number_format($quota) ?> · <?= $pct ?>%</div>
            <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:rgba(56,189,248,.10)">
                <div class="h-full transition-all" style="width:<?= $pct ?>%; background:<?= $pctColor ?>"></div>
            </div>
        <?php else: ?>
            <div class="text-[11px] text-slate-400 mt-1">Sin límite</div>
        <?php endif; ?>
    </div>

    <div class="dev-stat group">
        <div class="flex items-center justify-between mb-1">
            <div class="dev-stat-label">Hoy</div>
            <i class="lucide lucide-zap text-emerald-300/60 text-[14px]"></i>
        </div>
        <div class="dev-stat-value"><?= number_format($todayRequests) ?></div>
        <div class="text-[11px] text-slate-400 mt-1">requests del día</div>
    </div>

    <div class="dev-stat group">
        <div class="flex items-center justify-between mb-1">
            <div class="dev-stat-label">Rate último min</div>
            <i class="lucide lucide-gauge text-indigo-300/60 text-[14px]"></i>
        </div>
        <div class="dev-stat-value"><?= number_format($minuteRequests) ?><?php if ($rateLimit > 0): ?><span class="text-[14px] text-slate-400 font-normal"> / <?= number_format($rateLimit) ?></span><?php endif; ?></div>
        <?php if ($rateLimit > 0): ?>
            <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:rgba(56,189,248,.10)">
                <div class="h-full transition-all" style="width:<?= $pctMinute ?>%; background:<?= $pctMinute >= 80 ? '#fbbf24' : '#6366f1' ?>"></div>
            </div>
        <?php else: ?>
            <div class="text-[11px] text-slate-400 mt-1">Sin rate limit</div>
        <?php endif; ?>
    </div>

    <div class="dev-stat group">
        <div class="flex items-center justify-between mb-1">
            <div class="dev-stat-label">Latencia (7d)</div>
            <i class="lucide lucide-timer text-rose-300/60 text-[14px]"></i>
        </div>
        <div class="dev-stat-value"><?= number_format($avgLatency) ?><span class="text-[14px] text-slate-400 font-normal">ms</span></div>
        <div class="text-[11px] text-slate-400 mt-1">promedio · <?= $errorRate ?>% errores</div>
    </div>
</div>

<!-- Usage chart + sidebar -->
<div class="grid lg:grid-cols-3 gap-5">
    <div class="dev-card lg:col-span-2">
        <div class="dev-card-head">
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Actividad últimos 30 días</h2>
                <p class="text-[12px] text-slate-400">Requests y errores agregados por día</p>
            </div>
            <a href="<?= $url('/developers/usage') ?>" class="dev-btn dev-btn-soft text-[12px]">Ver detalle <i class="lucide lucide-arrow-right text-[12px]"></i></a>
        </div>
        <div class="p-5"><canvas id="usageChart" height="80"></canvas></div>
    </div>

    <div class="dev-card">
        <div class="dev-card-head"><h2 class="font-display font-bold text-white text-[16px]">Quick actions</h2></div>
        <div class="p-4 grid grid-cols-2 gap-2">
            <a href="<?= $url('/developers/apps/create') ?>" class="dev-feature !p-3 text-center group cursor-pointer">
                <div class="w-9 h-9 mx-auto rounded-xl grid place-items-center mb-2" style="background:rgba(14,165,233,.12); border:1px solid rgba(56,189,248,.20); color:#7dd3fc"><i class="lucide lucide-plus text-[14px]"></i></div>
                <div class="text-[11.5px] font-semibold text-slate-200">Nueva app</div>
            </a>
            <a href="<?= $url('/developers/billing/plans') ?>" class="dev-feature !p-3 text-center group cursor-pointer">
                <div class="w-9 h-9 mx-auto rounded-xl grid place-items-center mb-2" style="background:rgba(245,158,11,.12); border:1px solid rgba(245,158,11,.20); color:#fcd34d"><i class="lucide lucide-arrow-up text-[14px]"></i></div>
                <div class="text-[11.5px] font-semibold text-slate-200">Cambiar plan</div>
            </a>
            <a href="<?= $url('/developers/docs') ?>" class="dev-feature !p-3 text-center group cursor-pointer">
                <div class="w-9 h-9 mx-auto rounded-xl grid place-items-center mb-2" style="background:rgba(99,102,241,.12); border:1px solid rgba(129,140,248,.20); color:#a5b4fc"><i class="lucide lucide-book-open text-[14px]"></i></div>
                <div class="text-[11.5px] font-semibold text-slate-200">Docs</div>
            </a>
            <a href="<?= $url('/developers/usage') ?>" class="dev-feature !p-3 text-center group cursor-pointer">
                <div class="w-9 h-9 mx-auto rounded-xl grid place-items-center mb-2" style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.20); color:#86efac"><i class="lucide lucide-bar-chart-3 text-[14px]"></i></div>
                <div class="text-[11.5px] font-semibold text-slate-200">Ver métricas</div>
            </a>
        </div>

        <?php if ($unpaidInvoices > 0): ?>
        <div class="px-4 pb-4">
            <a href="<?= $url('/developers/billing') ?>" class="block dev-feature !p-3" style="border-color:rgba(245,158,11,.30); background:rgba(245,158,11,.06)">
                <div class="flex items-center gap-2">
                    <i class="lucide lucide-receipt text-amber-300"></i>
                    <div class="flex-1">
                        <div class="text-[12.5px] font-semibold text-amber-100"><?= $unpaidInvoices ?> factura<?= $unpaidInvoices > 1 ? 's' : '' ?> pendiente<?= $unpaidInvoices > 1 ? 's' : '' ?></div>
                        <div class="text-[11px] text-amber-200/70">Revisa y completa el pago</div>
                    </div>
                    <i class="lucide lucide-chevron-right text-amber-300/60"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Apps grid -->
<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Tus apps</h2>
            <p class="text-[12px] text-slate-400"><?= count($apps) ?> en total</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= $url('/developers/apps') ?>" class="dev-btn dev-btn-soft text-[12px]">Ver todas</a>
            <a href="<?= $url('/developers/apps/create') ?>" class="dev-btn dev-btn-primary text-[12px]"><i class="lucide lucide-plus text-[12px]"></i> Crear app</a>
        </div>
    </div>
    <?php if (empty($apps)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-2xl mx-auto grid place-items-center mb-4" style="background:rgba(14,165,233,.10); border:1px solid rgba(56,189,248,.20); color:#7dd3fc"><i class="lucide lucide-boxes text-[24px]"></i></div>
            <div class="font-display font-bold text-white text-[18px] mb-2">Crea tu primera app</div>
            <p class="text-[13px] text-slate-400 mb-5 max-w-[400px] mx-auto">Una app es un workspace aislado para tu integración. Recibirás un Bearer token para llamar a la API.</p>
            <a href="<?= $url('/developers/apps/create') ?>" class="dev-btn dev-btn-primary inline-flex"><i class="lucide lucide-plus text-[13px]"></i> Crear primera app</a>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-px" style="background:rgba(56,189,248,.06)">
            <?php foreach ($apps as $a): ?>
                <a href="<?= $url('/developers/apps/' . $a['id']) ?>" class="block p-5 hover:bg-sky-500/5 transition" style="background:#0f1018">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center text-sky-300" style="background:rgba(14,165,233,.12); border:1px solid rgba(56,189,248,.20)"><i class="lucide lucide-box text-[15px]"></i></div>
                        <span class="dev-pill <?= $a['status']==='active'?'dev-pill-emerald':'dev-pill-red' ?> text-[9px]"><?= $e($a['environment']) ?></span>
                    </div>
                    <div class="font-display font-bold text-white text-[14.5px] mb-1 truncate"><?= $e($a['name']) ?></div>
                    <div class="text-[11.5px] text-slate-400 font-mono mb-3 truncate"><?= $e($a['slug']) ?></div>
                    <div class="grid grid-cols-2 gap-2 pt-3 border-t" style="border-color:rgba(56,189,248,.06)">
                        <div>
                            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500">Tokens</div>
                            <div class="text-white font-display font-bold text-[14px] mt-0.5"><?= (int)$a['active_tokens'] ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500">Requests</div>
                            <div class="text-white font-display font-bold text-[14px] mt-0.5"><?= number_format((int)$a['month_requests']) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Tokens & Recent activity -->
<div class="grid lg:grid-cols-2 gap-5">
    <div class="dev-card">
        <div class="dev-card-head">
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Tokens activos</h2>
                <p class="text-[12px] text-slate-400">Bearer tokens en uso</p>
            </div>
        </div>
        <?php if (empty($tokens)): ?>
            <div class="p-8 text-center">
                <div class="w-12 h-12 rounded-xl mx-auto grid place-items-center mb-3" style="background:rgba(14,165,233,.10); color:#7dd3fc"><i class="lucide lucide-key text-[18px]"></i></div>
                <p class="text-[13px] text-slate-400">Crea una app para generar tu primer token.</p>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($tokens as $t): ?>
                    <div class="flex items-center gap-3 p-4 hover:bg-sky-500/5 transition" style="border-bottom:1px solid rgba(56,189,248,.06)">
                        <div class="w-9 h-9 rounded-xl grid place-items-center bg-indigo-500/15 text-indigo-300 border border-indigo-500/20"><i class="lucide lucide-key text-[14px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-white text-[13.5px] truncate"><?= $e($t['name']) ?> <?php if ($t['app_name']): ?><span class="text-slate-500 text-[11px] font-normal">· <?= $e($t['app_name']) ?></span><?php endif; ?></div>
                            <div class="text-[11px] font-mono text-slate-500 truncate"><?= $e($t['token_preview']) ?></div>
                        </div>
                        <div class="text-right text-[11px]">
                            <?php if ($t['last_used_at']): ?>
                                <div class="text-emerald-300 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Activo</div>
                                <div class="text-slate-500 mt-0.5"><?= $e(substr($t['last_used_at'], 5, 11)) ?></div>
                            <?php else: ?>
                                <div class="text-slate-500">Sin uso</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dev-card">
        <div class="dev-card-head">
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Actividad reciente</h2>
                <p class="text-[12px] text-slate-400">Últimas requests a la API</p>
            </div>
        </div>
        <?php if (empty($recentActivity)): ?>
            <div class="p-8 text-center">
                <div class="w-12 h-12 rounded-xl mx-auto grid place-items-center mb-3" style="background:rgba(14,165,233,.10); color:#7dd3fc"><i class="lucide lucide-activity text-[18px]"></i></div>
                <p class="text-[13px] text-slate-400">Aún no has llamado a la API.</p>
                <a href="<?= $url('/developers/docs') ?>" class="dev-btn dev-btn-soft mt-3 inline-flex"><i class="lucide lucide-arrow-right text-[12px]"></i> Ver quickstart</a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($recentActivity as $r):
                    $sc = (int)$r['status_code'];
                    $cls = $sc >= 500 ? 'dev-pill-red' : ($sc >= 400 ? 'dev-pill-amber' : 'dev-pill-emerald');
                ?>
                    <div class="flex items-center gap-3 p-3 hover:bg-sky-500/5 transition" style="border-bottom:1px solid rgba(56,189,248,.06)">
                        <span class="dev-pill dev-pill-sky !text-[9.5px] flex-shrink-0"><?= $e($r['method']) ?></span>
                        <div class="flex-1 min-w-0 font-mono text-[12px] text-slate-300 truncate"><?= $e($r['path']) ?></div>
                        <span class="dev-pill <?= $cls ?> !text-[9.5px] flex-shrink-0"><?= $sc ?></span>
                        <span class="text-[10.5px] text-slate-500 flex-shrink-0 font-mono"><?= (int)$r['duration_ms'] ?>ms</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    const data = <?= json_encode($usageDaily ?? []) ?>;
    const labels = data.map(d => d.period_date.substring(5));
    const values = data.map(d => parseInt(d.requests || 0));
    const errors = data.map(d => parseInt(d.errors || 0));
    const ctx = document.getElementById('usageChart');
    if (!ctx || !window.Chart) return;
    const grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
    grad.addColorStop(0, 'rgba(14,165,233,.55)');
    grad.addColorStop(1, 'rgba(14,165,233,.04)');

    new Chart(ctx, {
        type:'line',
        data: { labels, datasets:[
            { label:'Requests', data:values, borderColor:'#0ea5e9', backgroundColor:grad, tension:.4, fill:true, pointBackgroundColor:'#0ea5e9', pointBorderColor:'#020308', pointRadius:3, borderWidth:2.5 },
            { label:'Errores', data:errors, borderColor:'#f87171', backgroundColor:'rgba(239,68,68,.06)', tension:.4, fill:false, borderDash:[4,3], pointRadius:0, borderWidth:2 }
        ] },
        options: {
            plugins: { legend: { labels: { color:'#cbd5e1', usePointStyle:true, padding:14 } }, tooltip: { backgroundColor:'#0a0c18', borderColor:'rgba(56,189,248,.20)', borderWidth:1, padding:10, titleColor:'#f8fafc', bodyColor:'#cbd5e1' } },
            scales: {
                x: { ticks: { color:'#475569', font:{size:11} }, grid:{ color:'rgba(56,189,248,.06)', drawBorder:false } },
                y: { ticks: { color:'#475569', font:{size:11} }, grid:{ color:'rgba(56,189,248,.06)', drawBorder:false }, beginAtZero:true }
            },
            maintainAspectRatio: false,
        }
    });
})();
</script>
