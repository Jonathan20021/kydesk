<div class="grid lg:grid-cols-3 gap-5">
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
        <div class="admin-stat-label">Plan actual</div>
        <div class="admin-stat-value text-[20px]"><?= $sub ? $e($sub['plan_name']) : 'Sin plan' ?></div>
        <?php if ($sub): ?><div class="text-[11px] mt-2 text-ink-400">Estado: <?= $e($sub['status']) ?></div><?php endif; ?>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-label">Requests este mes</div>
        <div class="admin-stat-value"><?= number_format($monthRequests) ?></div>
        <div class="text-[11px] mt-2 text-ink-400"><?= count($apps) ?> apps · <?= count($tokens) ?> tokens</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="admin-card lg:col-span-2">
        <div class="admin-card-head">
            <h2 class="admin-h2">Apps</h2>
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
        <div class="admin-card-head"><h2 class="admin-h2">Asignar plan</h2></div>
        <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/plan') ?>" class="admin-card-pad space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div>
                <label class="admin-label">Plan</label>
                <select name="plan_id" class="admin-select" required>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($sub && (int)$sub['plan_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= $e($p['name']) ?></option>
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

        <div class="border-t" style="border-color:var(--border)">
            <div class="admin-card-pad">
                <h3 class="font-display font-bold text-[13px] mb-2">Acciones</h3>
                <div class="flex flex-col gap-2">
                    <a href="<?= $url('/admin/developers/' . $d['id'] . '/edit-form') ?>" onclick="event.preventDefault();document.getElementById('editForm').classList.toggle('hidden')" class="admin-btn admin-btn-soft w-full"><i class="lucide lucide-edit text-[13px]"></i> Editar datos</a>

                    <?php if ($d['suspended_at']): ?>
                        <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/activate') ?>">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button type="submit" class="admin-btn admin-btn-soft w-full"><i class="lucide lucide-check text-[13px]"></i> Reactivar</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/suspend') ?>" onsubmit="return confirm('¿Suspender este developer?')">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button type="submit" class="admin-btn admin-btn-danger w-full"><i class="lucide lucide-pause text-[13px]"></i> Suspender</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" action="<?= $url('/admin/developers/' . $d['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar developer y todo su contenido? Esta acción es irreversible.')">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button type="submit" class="admin-btn admin-btn-danger w-full"><i class="lucide lucide-trash-2 text-[13px]"></i> Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="editForm" method="POST" action="<?= $url('/admin/developers/' . $d['id']) ?>" class="admin-card admin-card-pad space-y-4 hidden">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <h3 class="admin-h2">Datos del developer</h3>
    <div class="grid sm:grid-cols-2 gap-3">
        <div><label class="admin-label">Nombre</label><input type="text" name="name" class="admin-input" value="<?= $e($d['name']) ?>"></div>
        <div><label class="admin-label">Empresa</label><input type="text" name="company" class="admin-input" value="<?= $e($d['company'] ?? '') ?>"></div>
        <div><label class="admin-label">Website</label><input type="url" name="website" class="admin-input" value="<?= $e($d['website'] ?? '') ?>"></div>
        <div><label class="admin-label">País</label><input type="text" name="country" class="admin-input" value="<?= $e($d['country'] ?? '') ?>"></div>
        <div><label class="admin-label">Teléfono</label><input type="text" name="phone" class="admin-input" value="<?= $e($d['phone'] ?? '') ?>"></div>
        <div><label class="admin-label">Reset password</label><input type="password" name="password" class="admin-input" placeholder="Dejar vacío"></div>
    </div>
    <div><label class="admin-label">Notas</label><textarea name="notes" class="admin-textarea" rows="2"><?= $e($d['notes'] ?? '') ?></textarea></div>
    <div class="grid sm:grid-cols-2 gap-3">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= (int)$d['is_active']===1?'checked':'' ?>> <span class="text-[13px]">Activo</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_verified" value="1" <?= !empty($d['is_verified'])?'checked':'' ?>> <span class="text-[13px]">Verificado</span></label>
    </div>
    <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Guardar</button>
</form>

<div class="grid lg:grid-cols-2 gap-5">
    <div class="admin-card">
        <div class="admin-card-head"><h2 class="admin-h2">Tokens API</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>App</th><th>Nombre</th><th>Preview</th><th>Estado</th><th>Último uso</th></tr></thead>
                <tbody>
                    <?php if (empty($tokens)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-ink-400">Sin tokens</td></tr>
                    <?php else: foreach ($tokens as $t): ?>
                        <tr>
                            <td class="text-[12px]"><?= $e($t['app_name'] ?? '—') ?></td>
                            <td class="font-display font-bold text-[13px]"><?= $e($t['name']) ?></td>
                            <td class="font-mono text-[11.5px]"><?= $e($t['token_preview']) ?></td>
                            <td><span class="admin-pill <?= empty($t['revoked_at']) ? 'admin-pill-green' : 'admin-pill-red' ?>"><?= empty($t['revoked_at']) ? 'Activo' : 'Revocado' ?></span></td>
                            <td class="text-[11.5px] text-ink-400"><?= $t['last_used_at'] ? $e($t['last_used_at']) : 'Nunca' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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
</div>
