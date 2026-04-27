<?php use App\Core\Helpers; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-3 mb-4">
    <div class="admin-stat" style="border-top:3px solid #7c5cff">
        <div class="admin-stat-label">Tenants asignados</div>
        <div class="admin-stat-value"><?= $stats['assigned_tenants'] ?></div>
        <div class="admin-stat-icon" style="background:#f3f0ff;color:#7c5cff"><i class="lucide lucide-sparkles text-[16px]"></i></div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #10b981">
        <div class="admin-stat-label">Completions este mes</div>
        <div class="admin-stat-value"><?= number_format($stats['completions']) ?></div>
        <div class="admin-stat-icon" style="background:#ecfdf5;color:#10b981"><i class="lucide lucide-zap text-[16px]"></i></div>
    </div>
    <div class="admin-stat" style="border-top:3px solid #0ea5e9">
        <div class="admin-stat-label">Tokens consumidos</div>
        <div class="admin-stat-value"><?= number_format($stats['tokens']) ?></div>
        <div class="admin-stat-icon" style="background:#e0f2fe;color:#0ea5e9"><i class="lucide lucide-cpu text-[16px]"></i></div>
    </div>
    <div class="admin-stat" style="border-top:3px solid <?= $cfg['ai_global_enabled']==='1'?'#16a34a':'#dc2626' ?>">
        <div class="admin-stat-label">Estado global</div>
        <div class="admin-stat-value" style="font-size:18px"><?= $cfg['ai_global_enabled']==='1' ? '✓ Habilitado' : '✗ Deshabilitado' ?></div>
        <div class="admin-stat-icon" style="background:<?= $cfg['ai_global_enabled']==='1'?'#d1fae5':'#fee2e2' ?>;color:<?= $cfg['ai_global_enabled']==='1'?'#16a34a':'#dc2626' ?>"><i class="lucide lucide-power text-[16px]"></i></div>
    </div>
</div>

<!-- Global config -->
<div class="admin-card mb-4">
    <div class="admin-card-head">
        <h2 class="admin-h2"><i class="lucide lucide-settings text-brand-600"></i> Configuración global de IA</h2>
        <span class="admin-pill admin-pill-purple"><i class="lucide lucide-shield"></i> Solo super admin</span>
    </div>
    <form method="POST" action="<?= $url('/admin/ai/settings') ?>" class="admin-card-pad grid grid-cols-1 md:grid-cols-2 gap-3">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div>
            <label class="admin-label">Proveedor</label>
            <select name="ai_provider" class="admin-select">
                <option value="anthropic" <?= $cfg['ai_provider']==='anthropic'?'selected':'' ?>>Anthropic (Claude)</option>
                <option value="disabled" <?= $cfg['ai_provider']==='disabled'?'selected':'' ?>>Deshabilitado</option>
            </select>
        </div>
        <div><label class="admin-label">Modelo por defecto</label><input name="ai_default_model" value="<?= $e($cfg['ai_default_model']) ?>" class="admin-input" placeholder="claude-haiku-4-5"></div>
        <div class="md:col-span-2">
            <label class="admin-label">API Key</label>
            <input name="ai_api_key" type="password" class="admin-input" placeholder="<?= !empty($cfg['ai_api_key']) ? '•••••••••• guardada · dejar vacío para conservar' : 'sk-ant-...' ?>">
            <p class="text-[11.5px] text-ink-500 mt-1">Esta key se usa para TODOS los tenants asignados. Obtenela en <a href="https://console.anthropic.com" target="_blank" class="text-brand-700 font-semibold">console.anthropic.com</a></p>
        </div>
        <div>
            <label class="admin-label">Cuota mensual por defecto (al asignar)</label>
            <input name="ai_default_quota" type="number" min="0" value="<?= (int)$cfg['ai_default_quota'] ?>" class="admin-input">
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 text-[13px] pb-2">
                <input type="checkbox" name="ai_global_enabled" value="1" <?= $cfg['ai_global_enabled']==='1'?'checked':'' ?>>
                <strong>Habilitar IA globalmente</strong> (kill switch)
            </label>
        </div>
        <div class="md:col-span-2 flex justify-end pt-2" style="border-top:1px solid var(--border)">
            <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar configuración global</button>
        </div>
    </form>
</div>

<!-- Tenants table -->
<div class="admin-card mb-4">
    <div class="admin-card-head">
        <h2 class="admin-h2">Tenants y asignación de IA</h2>
        <p class="text-[12px] text-ink-500">IA solo está disponible en plan <strong>Enterprise</strong>. Activá la asignación por tenant.</p>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Plan</th>
                    <th>Estado IA</th>
                    <th>Uso este mes</th>
                    <th>Cuota</th>
                    <th>Acciones</th>
                    <th>Asignación</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t):
                $isEnterprise = ($t['plan_slug'] ?? '') === 'enterprise';
                $isAssigned = (int)$t['is_assigned'] === 1;
                $isEnabled = (int)$t['is_enabled'] === 1;
                $used = (int)$t['used_this_month'];
                $quota = (int)$t['monthly_quota'];
                $pct = $quota > 0 ? min(100, ($used / $quota) * 100) : 0;
                $enabledActions = [];
                if ($t['suggest_replies']) $enabledActions[] = 'Sugerir';
                if ($t['auto_summarize']) $enabledActions[] = 'Resumen';
                if ($t['auto_categorize']) $enabledActions[] = 'Cat.';
                if ($t['detect_sentiment']) $enabledActions[] = 'Sentiment';
                if ($t['auto_translate']) $enabledActions[] = 'Trad.';
            ?>
                <tr>
                    <td>
                        <a href="<?= $url('/admin/tenants/' . $t['id']) ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
                            <div style="width:32px;height:32px;border-radius:8px;background:<?= Helpers::colorFor($t['slug']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:12px"><?= Helpers::initials($t['name']) ?></div>
                            <div>
                                <div style="font-weight:700;font-size:13px"><?= $e($t['name']) ?></div>
                                <div class="text-[11px] text-ink-400 font-mono"><?= $e($t['slug']) ?></div>
                            </div>
                        </a>
                    </td>
                    <td>
                        <?php if ($isEnterprise): ?>
                            <span class="admin-pill admin-pill-purple"><i class="lucide lucide-crown"></i> Enterprise</span>
                        <?php else: ?>
                            <span class="admin-pill admin-pill-gray"><?= $e($t['plan_name'] ?? ucfirst($t['plan_slug'] ?? 'starter')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$isAssigned): ?>
                            <span class="admin-pill admin-pill-gray">Sin asignar</span>
                        <?php elseif (!$isEnabled): ?>
                            <span class="admin-pill admin-pill-amber">Asignada pero pausada por tenant</span>
                        <?php else: ?>
                            <span class="admin-pill admin-pill-green"><i class="lucide lucide-check-circle"></i> Activa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isAssigned): ?>
                            <div class="text-[12px] font-mono"><strong><?= $used ?></strong> / <?= $quota ?></div>
                            <div style="height:4px;background:#f3f4f6;border-radius:999px;overflow:hidden;width:100px;margin-top:3px">
                                <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct>=90?'#dc2626':($pct>=70?'#f59e0b':'#10b981') ?>"></div>
                            </div>
                        <?php else: ?>
                            <span class="text-ink-400 text-[12px]">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isAssigned): ?>
                            <form method="POST" action="<?= $url('/admin/ai/tenants/' . $t['id'] . '/update') ?>" class="flex gap-1">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <input name="monthly_quota" type="number" min="0" value="<?= $quota ?>" class="admin-input" style="width:90px;height:32px;font-size:12px;padding:0 8px">
                                <button class="admin-btn admin-btn-soft" style="padding:0 10px;height:32px"><i class="lucide lucide-save text-[12px]"></i></button>
                            </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isAssigned): ?>
                            <span class="text-[11px] text-ink-500"><?= implode(', ', $enabledActions) ?: '—' ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-[11px] text-ink-500">
                        <?php if ($isAssigned): ?>
                            <?= $e($t['assigned_at']) ?><br>
                            <span class="text-ink-400">por <?= $e($t['admin_name'] ?? '—') ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$isAssigned): ?>
                            <form method="POST" action="<?= $url('/admin/ai/tenants/' . $t['id'] . '/assign') ?>" onsubmit="<?= !$isEnterprise ? "return confirm('Este tenant no es Enterprise. ¿Forzar asignación de IA?')" : "" ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <?php if (!$isEnterprise): ?><input type="hidden" name="force" value="1"><?php endif; ?>
                                <button class="admin-btn admin-btn-primary" style="padding:5px 12px;font-size:12px"><i class="lucide lucide-sparkles text-[12px]"></i> Asignar IA</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?= $url('/admin/ai/tenants/' . $t['id'] . '/unassign') ?>" onsubmit="return confirm('Quitar IA a este tenant?')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="admin-btn admin-btn-danger" style="padding:5px 12px;font-size:12px"><i class="lucide lucide-power-off text-[12px]"></i> Desasignar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tenants)): ?><tr><td colspan="8" style="text-align:center;padding:24px;color:#8e8e9a">Sin tenants registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent logs -->
<div class="admin-card">
    <div class="admin-card-head"><h2 class="admin-h2">Actividad reciente · cross-tenant</h2></div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Fecha</th><th>Tenant</th><th>Acción</th><th>Usuario</th><th>Ticket</th><th>Tokens</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($recentLogs as $l): ?>
                <tr>
                    <td class="text-[11.5px] font-mono text-ink-500"><?= $e($l['created_at']) ?></td>
                    <td class="text-[12px]"><?= $e($l['tenant_name'] ?? '—') ?></td>
                    <td><span class="admin-pill admin-pill-purple"><?= $e($l['action']) ?></span></td>
                    <td class="text-[12px]"><?= $e($l['user_name'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($l['ticket_code'])): ?><span class="font-mono text-[11.5px]"><?= $e($l['ticket_code']) ?></span><?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="font-mono text-[12px]"><?= (int)$l['tokens_in'] ?> / <?= (int)$l['tokens_out'] ?></td>
                    <td>
                        <?php if ($l['status']==='ok'): ?><span class="admin-pill admin-pill-green">OK</span>
                        <?php else: ?><span class="admin-pill admin-pill-red" data-tooltip="<?= $e($l['error']) ?>">Error</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentLogs)): ?><tr><td colspan="7" style="text-align:center;padding:24px;color:#8e8e9a">Sin actividad de IA aún.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
