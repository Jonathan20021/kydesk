<div class="grid lg:grid-cols-3 gap-5">
    <div class="dev-stat lg:col-span-1">
        <div class="dev-stat-label">Estado</div>
        <div class="dev-stat-value text-[20px]"><span class="dev-pill <?= $devApp['status']==='active'?'dev-pill-emerald':'dev-pill-red' ?>"><?= $e($devApp['status']) ?></span></div>
        <div class="text-[11.5px] text-slate-400 mt-2">Entorno: <?= $e($devApp['environment']) ?></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Requests este mes</div>
        <div class="dev-stat-value"><?= number_format($monthRequests) ?></div>
        <div class="dev-stat-icon"><i class="lucide lucide-activity text-[15px]"></i></div>
    </div>
    <div class="dev-stat">
        <div class="dev-stat-label">Tokens activos</div>
        <div class="dev-stat-value"><?= count(array_filter($tokens, fn($t) => empty($t['revoked_at']))) ?></div>
        <div class="dev-stat-icon" style="background:rgba(124,92,255,.10); color:#c4b5fd"><i class="lucide lucide-key text-[15px]"></i></div>
    </div>
</div>

<?php if (!empty($newToken)): ?>
<div class="dev-card dev-card-pad" style="border-color:rgba(245,158,11,.4); background:rgba(245,158,11,.05)">
    <div class="flex items-center gap-2 mb-2"><i class="lucide lucide-alert-triangle text-amber-300"></i><span class="dev-pill dev-pill-amber">Token recién creado</span></div>
    <p class="text-[13px] text-slate-300 mb-3">Cópialo ahora — no se mostrará otra vez.</p>
    <div class="dev-code flex items-center justify-between gap-3">
        <code id="newTokenValue" class="break-all"><?= $e($newToken) ?></code>
        <button onclick="navigator.clipboard.writeText(document.getElementById('newTokenValue').textContent); this.textContent='✓ Copiado';" class="dev-btn dev-btn-soft text-[12px] flex-shrink-0">Copiar</button>
    </div>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="dev-card lg:col-span-2">
        <div class="dev-card-head">
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Tokens API</h2>
                <p class="text-[12px] text-slate-400">Bearer tokens para autenticar tu app</p>
            </div>
        </div>
        <div class="p-4">
            <form method="POST" action="<?= $url('/developers/apps/' . $devApp['id'] . '/tokens') ?>" class="flex items-end gap-2 mb-4 flex-wrap">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div class="flex-1 min-w-[180px]">
                    <label class="dev-label">Nombre del token</label>
                    <input type="text" name="name" class="dev-input" placeholder="Ej: producción" required>
                </div>
                <div style="width:160px">
                    <label class="dev-label">Scopes</label>
                    <select name="scopes" class="dev-input">
                        <option value="read">read</option>
                        <option value="read,write" selected>read,write</option>
                        <option value="*">* (full)</option>
                    </select>
                </div>
                <button type="submit" class="dev-btn dev-btn-primary"><i class="lucide lucide-plus text-[13px]"></i> Generar</button>
            </form>

            <?php if (empty($tokens)): ?>
                <p class="text-center text-[13px] text-slate-400 py-6">Aún no has generado tokens.</p>
            <?php else: ?>
                <div style="overflow-x:auto">
                    <table class="dev-table">
                        <thead><tr><th>Nombre</th><th>Token</th><th>Scopes</th><th>Último uso</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($tokens as $t): ?>
                                <tr>
                                    <td class="text-white font-display font-bold"><?= $e($t['name']) ?></td>
                                    <td class="font-mono text-[12px] text-slate-400"><?= $e($t['token_preview']) ?></td>
                                    <td class="text-[12px] text-slate-400"><?= $e($t['scopes']) ?></td>
                                    <td class="text-[12px] text-slate-400"><?= $t['last_used_at'] ? $e($t['last_used_at']) : '<span class="text-slate-500">Nunca</span>' ?></td>
                                    <td>
                                        <?php if (!empty($t['revoked_at'])): ?>
                                            <span class="dev-pill dev-pill-red">Revocado</span>
                                        <?php else: ?>
                                            <span class="dev-pill dev-pill-emerald">Activo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($t['revoked_at'])): ?>
                                            <form method="POST" action="<?= $url('/developers/apps/' . $devApp['id'] . '/tokens/' . $t['id'] . '/revoke') ?>" onsubmit="return confirm('¿Revocar este token?');">
                                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                                <button type="submit" class="dev-btn dev-btn-danger dev-btn-icon" title="Revocar"><i class="lucide lucide-x text-[14px]"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dev-card">
        <div class="dev-card-head">
            <h2 class="font-display font-bold text-white text-[16px]">Editar app</h2>
        </div>
        <form method="POST" action="<?= $url('/developers/apps/' . $devApp['id'] . '/update') ?>" class="p-4 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div>
                <label class="dev-label">Nombre</label>
                <input type="text" name="name" class="dev-input" value="<?= $e($devApp['name']) ?>" required>
            </div>
            <div>
                <label class="dev-label">Descripción</label>
                <textarea name="description" class="dev-textarea" rows="2"><?= $e($devApp['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="dev-label">Homepage URL</label>
                <input type="url" name="homepage_url" class="dev-input" value="<?= $e($devApp['homepage_url'] ?? '') ?>">
            </div>
            <div>
                <label class="dev-label">Callback URL</label>
                <input type="url" name="callback_url" class="dev-input" value="<?= $e($devApp['callback_url'] ?? '') ?>">
            </div>
            <div>
                <label class="dev-label">Entorno</label>
                <select name="environment" class="dev-input">
                    <?php foreach (['development','staging','production'] as $env): ?>
                        <option value="<?= $env ?>" <?= $devApp['environment'] === $env ? 'selected' : '' ?>><?= ucfirst($env) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="dev-btn dev-btn-primary flex-1"><i class="lucide lucide-save text-[13px]"></i> Guardar</button>
            </div>
        </form>
        <div class="px-4 pb-4 pt-2 border-t" style="border-color:rgba(56,189,248,.06)">
            <form method="POST" action="<?= $url('/developers/apps/' . $devApp['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar esta app y todos sus datos? Esta acción es irreversible.');">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button type="submit" class="dev-btn dev-btn-danger w-full"><i class="lucide lucide-trash-2 text-[13px]"></i> Eliminar app</button>
            </form>
        </div>
    </div>
</div>

<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Uso últimos 30 días</h2>
        </div>
    </div>
    <div class="p-5"><canvas id="appUsageChart" height="80"></canvas></div>
</div>

<script>
(function(){
    const data = <?= json_encode($usage ?? []) ?>;
    const labels = data.map(d => d.period_date.substring(5));
    const values = data.map(d => parseInt(d.requests || 0));
    const ctx = document.getElementById('appUsageChart');
    if (!ctx) return;
    new Chart(ctx, {
        type:'bar',
        data: { labels, datasets:[{ label:'Requests', data:values, backgroundColor:'rgba(14,165,233,.6)', borderColor:'#0ea5e9', borderWidth:1 }] },
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
