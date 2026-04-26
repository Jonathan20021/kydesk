<?php
$pct = $effective['max_requests_month'] > 0 ? min(100, round(($monthRequests / $effective['max_requests_month']) * 100)) : 0;
$alertColor = $pct >= 95 ? '#dc2626' : ($pct >= 80 ? '#d97706' : '#0ea5e9');
?>
<div class="grid lg:grid-cols-4 gap-4">
    <div class="admin-stat">
        <div class="admin-stat-label">Estado</div>
        <div class="admin-stat-value text-[20px]">
            <?php if ($d['suspended_at']): ?>
                <span class="admin-pill admin-pill-red">Suspendido</span>
            <?php elseif ((int)$d['is_active'] === 1): ?>
                <span class="admin-pill admin-pill-green">Activo</span>
            <?php else: ?>
                <span class="admin-pill admin-pill-gray">Inactivo</span>
            <?php endif; ?>
        </div>
        <div class="text-[11px] mt-2 text-ink-400"><?= $e($d['email']) ?></div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Plan + override</div>
        <div class="admin-stat-value text-[20px]"><?= $sub ? $e($sub['plan_name']) : '<span class="text-ink-400">Sin plan</span>' ?></div>
        <?php if (!empty($effective['has_custom_overrides'])): ?>
            <div class="mt-1.5"><span class="admin-pill admin-pill-amber">Overrides activos</span></div>
        <?php elseif ($sub): ?>
            <div class="text-[11px] mt-2 text-ink-400">Estado: <?= $e($sub['status']) ?></div>
        <?php endif; ?>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Requests este mes</div>
        <div class="admin-stat-value"><?= number_format($monthRequests) ?></div>
        <?php if ($effective['max_requests_month'] > 0): ?>
            <div class="text-[11px] mt-1 text-ink-400">de <?= number_format($effective['max_requests_month']) ?> · <?= $pct ?>%</div>
            <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:var(--bg)"><div style="width:<?= $pct ?>%; height:100%; background:<?= $alertColor ?>"></div></div>
        <?php endif; ?>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Recursos</div>
        <div class="admin-stat-value text-[16px]"><?= count($apps) ?>/<?= $effective['max_apps'] ?: '∞' ?> apps · <?= count($tokens) ?> tokens</div>
        <div class="text-[11px] mt-2 text-ink-400">RPM: <?= $effective['rate_limit_per_min'] ?: '∞' ?></div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="admin-card lg:col-span-2">
        <div class="admin-card-head">
            <h2 class="admin-h2">Apps y workspaces aislados</h2>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nombre</th><th>Slug</th><th>Entorno</th><th>Estado</th><th>Tenant</th></tr></thead>
                <tbody>
                    <?php if (empty($apps)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-ink-400">Sin apps</td></tr>
                    <?php else: foreach ($apps as $a): ?>
                        <tr>
                            <td class="font-display font-bold"><?= $e($a['name']) ?></td>
                            <td class="font-mono text-[12px]"><?= $e($a['slug']) ?></td>
                            <td><span class="admin-pill admin-pill-gray"><?= $e($a['environment']) ?></span></td>
                            <td><span class="admin-pill <?= $a['status']==='active'?'admin-pill-green':'admin-pill-red' ?>"><?= $e($a['status']) ?></span></td>
                            <td class="text-[12px] text-ink-400">#<?= $a['tenant_id'] ?? '—' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head"><h2 class="admin-h2">Asignar / cambiar plan</h2></div>
        <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/plan') ?>" class="admin-card-pad space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div>
                <label class="admin-label">Plan</label>
                <select name="plan_id" class="admin-select" required>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($sub && (int)$sub['plan_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= $e($p['name']) ?> · $<?= number_format((float)$p['price_monthly'], 0) ?>/mes</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="admin-label">Ciclo</label>
                <select name="billing_cycle" class="admin-select">
                    <option value="monthly">Mensual</option>
                    <option value="yearly">Anual</option>
                    <option value="lifetime">Lifetime</option>
                </select>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary w-full"><i class="lucide lucide-tag text-[13px]"></i> Asignar</button>
        </form>
    </div>
</div>

<!-- Override de cuotas: super admin puede sobrescribir limits del plan -->
<form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/overrides') ?>" class="admin-card admin-card-pad space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h2 class="admin-h2 flex items-center gap-2"><i class="lucide lucide-sliders-horizontal text-brand-700"></i> Overrides de cuota</h2>
            <p class="text-[12px] text-ink-400 mt-1">Sobrescribe los límites del plan para este developer. Vacío = usar default del plan.</p>
        </div>
        <?php if (!empty($effective['has_custom_overrides'])): ?>
            <span class="admin-pill admin-pill-amber"><i class="lucide lucide-zap text-[10px]"></i> Hay overrides activos</span>
        <?php endif; ?>
    </div>
    <div class="grid sm:grid-cols-4 gap-3">
        <div>
            <label class="admin-label">Max apps <span class="text-ink-400 font-normal">(plan: <?= $sub['max_apps'] ?? '—' ?>)</span></label>
            <input type="number" name="custom_max_apps" class="admin-input" value="<?= $e($d['custom_max_apps'] ?? '') ?>" placeholder="—">
        </div>
        <div>
            <label class="admin-label">Max requests/mes <span class="text-ink-400 font-normal">(plan: <?= number_format((int)($sub['max_requests_month'] ?? 0)) ?>)</span></label>
            <input type="number" name="custom_max_requests_month" class="admin-input" value="<?= $e($d['custom_max_requests_month'] ?? '') ?>" placeholder="—">
        </div>
        <div>
            <label class="admin-label">Tokens/app <span class="text-ink-400 font-normal">(plan: <?= $sub['max_tokens_per_app'] ?? '—' ?>)</span></label>
            <input type="number" name="custom_max_tokens_per_app" class="admin-input" value="<?= $e($d['custom_max_tokens_per_app'] ?? '') ?>" placeholder="—">
        </div>
        <div>
            <label class="admin-label">Rate limit/min <span class="text-ink-400 font-normal">(plan: <?= $sub['rate_limit_per_min'] ?? '—' ?>)</span></label>
            <input type="number" name="custom_rate_limit_per_min" class="admin-input" value="<?= $e($d['custom_rate_limit_per_min'] ?? '') ?>" placeholder="—">
        </div>
    </div>
    <div>
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Guardar overrides</button>
    </div>
</form>

<form method="POST" action="<?= $url('/admin/developers/' . $d['id']) ?>" class="admin-card admin-card-pad space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="flex items-center gap-2"><i class="lucide lucide-user text-brand-700"></i><h2 class="admin-h2">Datos del developer</h2></div>
    <div class="grid sm:grid-cols-2 gap-3">
        <div><label class="admin-label">Nombre</label><input type="text" name="name" class="admin-input" value="<?= $e($d['name']) ?>"></div>
        <div><label class="admin-label">Empresa</label><input type="text" name="company" class="admin-input" value="<?= $e($d['company'] ?? '') ?>"></div>
        <div><label class="admin-label">Website</label><input type="url" name="website" class="admin-input" value="<?= $e($d['website'] ?? '') ?>"></div>
        <div><label class="admin-label">País</label><input type="text" name="country" class="admin-input" value="<?= $e($d['country'] ?? '') ?>"></div>
        <div><label class="admin-label">Teléfono</label><input type="text" name="phone" class="admin-input" value="<?= $e($d['phone'] ?? '') ?>"></div>
        <div><label class="admin-label">Reset password</label><input type="password" name="password" class="admin-input" placeholder="Dejar vacío para conservar"></div>
    </div>
    <div><label class="admin-label">Bio</label><textarea name="bio" class="admin-textarea" rows="2"><?= $e($d['bio'] ?? '') ?></textarea></div>
    <div><label class="admin-label">Notas internas</label><textarea name="notes" class="admin-textarea" rows="2"><?= $e($d['notes'] ?? '') ?></textarea></div>
    <div class="grid sm:grid-cols-3 gap-3">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= (int)$d['is_active']===1?'checked':'' ?>> <span class="text-[13px]">Activo</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_verified" value="1" <?= !empty($d['is_verified'])?'checked':'' ?>> <span class="text-[13px]">Verificado</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="quota_alerts_enabled" value="1" <?= (int)($d['quota_alerts_enabled'] ?? 1)===1?'checked':'' ?>> <span class="text-[13px]">Alertas de cuota</span></label>
    </div>
    <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Guardar datos</button>
</form>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="admin-card admin-card-pad">
        <h3 class="admin-h2 flex items-center gap-2"><i class="lucide lucide-shield-alert text-amber-600"></i> Acciones</h3>
        <div class="flex flex-col gap-2 mt-3">
            <?php if ($d['suspended_at']): ?>
                <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/activate') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button type="submit" class="admin-btn admin-btn-soft w-full"><i class="lucide lucide-check text-[13px]"></i> Reactivar developer</button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/suspend') ?>" onsubmit="return confirm('¿Suspender este developer? No podrá usar la API ni el portal.')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button type="submit" class="admin-btn admin-btn-danger w-full"><i class="lucide lucide-pause text-[13px]"></i> Suspender</button>
                </form>
            <?php endif; ?>
            <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar developer y todo su contenido? Irreversible.')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button type="submit" class="admin-btn admin-btn-danger w-full"><i class="lucide lucide-trash-2 text-[13px]"></i> Eliminar developer</button>
            </form>
        </div>
    </div>

    <div class="admin-card lg:col-span-2">
        <div class="admin-card-head"><h2 class="admin-h2">Tokens API</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>App</th><th>Nombre</th><th>Preview</th><th>Estado</th><th>Último uso</th><th></th></tr></thead>
                <tbody>
                    <?php if (empty($tokens)): ?>
                        <tr><td colspan="6" class="text-center py-6 text-ink-400">Sin tokens</td></tr>
                    <?php else: foreach ($tokens as $t): ?>
                        <tr>
                            <td class="text-[12px]"><?= $e($t['app_name'] ?? '—') ?></td>
                            <td class="font-display font-bold text-[13px]"><?= $e($t['name']) ?></td>
                            <td class="font-mono text-[11.5px]"><?= $e($t['token_preview']) ?></td>
                            <td><span class="admin-pill <?= empty($t['revoked_at']) ? 'admin-pill-green' : 'admin-pill-red' ?>"><?= empty($t['revoked_at']) ? 'Activo' : 'Revocado' ?></span></td>
                            <td class="text-[11.5px] text-ink-400"><?= $t['last_used_at'] ? $e($t['last_used_at']) : 'Nunca' ?></td>
                            <td>
                                <?php if (empty($t['revoked_at'])): ?>
                                    <form method="POST" action="<?= $url('/admin/dev-tokens/' . $t['id'] . '/revoke') ?>" onsubmit="return confirm('¿Revocar?')">
                                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                        <button class="admin-btn admin-btn-danger admin-btn-icon" title="Revocar"><i class="lucide lucide-x text-[13px]"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <div class="admin-card">
        <div class="admin-card-head"><h2 class="admin-h2">Facturas</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Número</th><th>Total</th><th>Estado</th><th>Vence</th></tr></thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="4" class="text-center py-6 text-ink-400">Sin facturas</td></tr>
                    <?php else: foreach ($invoices as $i): ?>
                        <tr>
                            <td><a href="<?= $url('/admin/dev-invoices/' . $i['id']) ?>" class="font-mono text-[12px]"><?= $e($i['invoice_number']) ?></a></td>
                            <td class="font-display font-bold">$<?= number_format((float)$i['total'], 2) ?></td>
                            <td><span class="admin-pill <?= $i['status']==='paid'?'admin-pill-green':($i['status']==='overdue'?'admin-pill-red':'admin-pill-amber') ?>"><?= $e($i['status']) ?></span></td>
                            <td class="text-[12px]"><?= $e($i['due_date'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head"><h2 class="admin-h2">Últimos requests API</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Hora</th><th>Method</th><th>Path</th><th>Status</th><th>Latencia</th></tr></thead>
                <tbody>
                    <?php if (empty($recentRequests)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-ink-400">Sin actividad</td></tr>
                    <?php else: foreach ($recentRequests as $r):
                        $sc = (int)$r['status_code'];
                        $cls = $sc >= 500 ? 'admin-pill-red' : ($sc >= 400 ? 'admin-pill-amber' : 'admin-pill-green');
                    ?>
                        <tr>
                            <td class="text-[11.5px] font-mono"><?= $e(substr($r['created_at'], 11, 8)) ?></td>
                            <td><span class="admin-pill admin-pill-purple text-[10px]"><?= $e($r['method']) ?></span></td>
                            <td class="font-mono text-[11.5px]"><?= $e($r['path']) ?></td>
                            <td><span class="admin-pill <?= $cls ?>"><?= $sc ?></span></td>
                            <td class="text-[11.5px] text-ink-400"><?= (int)$r['duration_ms'] ?>ms</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
