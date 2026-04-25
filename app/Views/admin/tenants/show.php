<?php use App\Core\Helpers; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="admin-card admin-card-pad lg:col-span-2">
        <div class="flex items-center gap-4 mb-4">
            <div style="width:60px;height:60px;border-radius:14px;background:<?= Helpers::colorFor($t['slug']) ?>;color:white;display:grid;place-items:center;font-weight:800;font-size:22px"><?= Helpers::initials($t['name']) ?></div>
            <div style="flex:1">
                <div class="text-[12px] font-semibold text-ink-400">Empresa #<?= (int)$t['id'] ?></div>
                <div class="admin-h1"><?= $e($t['name']) ?></div>
                <div class="text-[12.5px] text-ink-500 mt-1">
                    <span class="font-mono"><?= $e($t['slug']) ?></span> · Creada <?= Helpers::ago($t['created_at']) ?>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <a href="<?= $url('/t/' . $t['slug']) ?>" target="_blank" class="admin-btn admin-btn-soft"><i class="lucide lucide-external-link"></i> Ver workspace</a>
                <?php if ($t['is_active']): ?>
                    <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/impersonate') ?>" onsubmit="return confirm('¿Acceder como propietario?')">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="admin-btn admin-btn-primary" style="width:100%"><i class="lucide lucide-log-in"></i> Acceder como Owner</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-3 md:grid-cols-7 gap-3">
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">Tickets</div><div style="font-weight:700; font-size:18px"><?= (int)$stats['tickets'] ?></div></div>
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">Abiertos</div><div style="font-weight:700; font-size:18px"><?= (int)$stats['open'] ?></div></div>
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">Resueltos</div><div style="font-weight:700; font-size:18px"><?= (int)$stats['resolved'] ?></div></div>
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">Empresas</div><div style="font-weight:700; font-size:18px"><?= (int)$stats['companies'] ?></div></div>
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">Activos</div><div style="font-weight:700; font-size:18px"><?= (int)$stats['assets'] ?></div></div>
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">KB</div><div style="font-weight:700; font-size:18px"><?= (int)$stats['kb'] ?></div></div>
            <div><div class="text-[10.5px] uppercase font-bold text-ink-400">Ingresos</div><div style="font-weight:700; font-size:18px">$<?= number_format($stats['paid'], 0) ?></div></div>
        </div>
    </div>

    <div class="admin-card admin-card-pad" x-data="{licenseTab: 'activate'}">
        <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400">Licencia</div>
        <?php
        $licState = $license['state'] ?? 'none';
        $licPillMap = [
            'active'    => ['admin-pill-green', 'Activa', 'check-circle'],
            'trial'     => ['admin-pill-purple', 'En prueba', 'rocket'],
            'past_due'  => ['admin-pill-amber', 'Pago vencido', 'alert-triangle'],
            'expired'   => ['admin-pill-red', 'Expirada', 'x-circle'],
            'suspended' => ['admin-pill-red', 'Suspendida', 'pause-circle'],
            'cancelled' => ['admin-pill-red', 'Cancelada', 'x-circle'],
            'none'      => ['admin-pill-gray', 'Sin licencia', 'help-circle'],
        ];
        [$licClass, $licLabel, $licIcon] = $licPillMap[$licState] ?? $licPillMap['none'];
        ?>
        <div class="flex items-center gap-2 mt-2 flex-wrap">
            <span class="admin-pill <?= $licClass ?>"><i class="lucide lucide-<?= $licIcon ?> text-[10px]"></i> <?= $licLabel ?></span>
            <?php if (!empty($license['plan_name'])): ?>
                <span class="admin-pill admin-pill-purple"><?= $e($license['plan_name']) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($license['is_trial'] && $license['trial_ends_at']): ?>
            <div class="text-[11.5px] text-ink-500 mt-2">
                Prueba expira: <span class="font-mono"><?= $e($license['trial_ends_at']) ?></span>
                <?php if ($license['days_left'] !== null): ?>
                    <span class="admin-pill admin-pill-<?= $license['days_left'] <= 3 ? 'amber' : 'gray' ?>" style="margin-left:4px"><?= max(0,(int)$license['days_left']) ?>d</span>
                <?php endif; ?>
            </div>
        <?php elseif ($licState === 'active' && $license['period_end']): ?>
            <div class="text-[11.5px] text-ink-500 mt-2">Próximo cobro: <span class="font-mono"><?= $e($license['period_end']) ?></span></div>
        <?php elseif ($licState === 'expired' || $licState === 'cancelled'): ?>
            <div class="text-[11.5px] text-ink-500 mt-2"><?= $e($license['message']) ?></div>
        <?php endif; ?>

        <div class="admin-tabs mt-4" style="font-size:11.5px">
            <button type="button" @click="licenseTab='activate'" :class="licenseTab==='activate' && 'active'" class="admin-tab" style="padding:6px 10px"><i class="lucide lucide-shield-check text-[12px]"></i> Activar</button>
            <button type="button" @click="licenseTab='trial'" :class="licenseTab==='trial' && 'active'" class="admin-tab" style="padding:6px 10px"><i class="lucide lucide-timer text-[12px]"></i> Prueba</button>
            <button type="button" @click="licenseTab='danger'" :class="licenseTab==='danger' && 'active'" class="admin-tab" style="padding:6px 10px"><i class="lucide lucide-shield-off text-[12px]"></i> Riesgo</button>
        </div>

        <div x-show="licenseTab==='activate'" class="mt-3">
            <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/license/activate') ?>" class="space-y-2">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="admin-label">Plan</label>
                    <select name="plan_id" class="admin-select">
                        <?php foreach ($plans as $p): $selected = $subscription && (int)$subscription['plan_id'] === (int)$p['id']; ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $selected?'selected':'' ?>><?= $e($p['name']) ?> — $<?= number_format($p['price_monthly'],0) ?>/mes</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="admin-label">Ciclo</label>
                        <select name="cycle" class="admin-select">
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Monto</label>
                        <input name="amount" type="number" step="0.01" placeholder="auto" class="admin-input">
                    </div>
                </div>
                <button class="admin-btn admin-btn-primary" style="width:100%"><i class="lucide lucide-shield-check"></i> Activar licencia</button>
            </form>
        </div>

        <div x-show="licenseTab==='trial'" x-cloak class="mt-3">
            <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/license/extend') ?>" class="space-y-2">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <label class="admin-label">Extender prueba (días)</label>
                <div class="flex gap-2">
                    <input name="days" type="number" min="1" value="14" class="admin-input">
                    <button class="admin-btn admin-btn-soft"><i class="lucide lucide-plus"></i></button>
                </div>
                <div class="flex gap-1">
                    <?php foreach ([7, 14, 30] as $d): ?>
                        <button name="days" value="<?= $d ?>" class="admin-btn admin-btn-soft" style="flex:1; padding:6px 4px; font-size:11.5px">+<?= $d ?>d</button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <div x-show="licenseTab==='danger'" x-cloak class="mt-3 space-y-2">
            <?php if ($t['suspended_at']): ?>
                <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/activate') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="admin-btn admin-btn-soft" style="width:100%"><i class="lucide lucide-power"></i> Reactivar empresa</button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/suspend') ?>" onsubmit="return confirm('¿Suspender empresa?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input name="reason" placeholder="Razón…" class="admin-input">
                    <button class="admin-btn admin-btn-soft" style="width:100%; color:#b91c1c; margin-top:6px"><i class="lucide lucide-pause-circle"></i> Suspender empresa</button>
                </form>
            <?php endif; ?>

            <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/license/revoke') ?>" onsubmit="return confirm('Revocar la licencia bloqueará el acceso del cliente. ¿Continuar?')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="admin-btn admin-btn-danger" style="width:100%"><i class="lucide lucide-shield-off"></i> Revocar licencia</button>
            </form>

            <form method="POST" action="<?= $url('/admin/tenants/' . $t['id'] . '/delete') ?>" onsubmit="return confirm('ELIMINAR empresa y TODOS sus datos. Esta acción no es reversible.')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="admin-btn admin-btn-danger" style="width:100%"><i class="lucide lucide-trash-2"></i> Eliminar empresa</button>
            </form>
        </div>
    </div>
</div>

<!-- Tabs -->
<div x-data="{tab:'info'}">
    <div class="admin-tabs mb-4">
        <?php foreach ([
            'info'=>['Información','info'],
            'subscription'=>['Suscripción','repeat'],
            'users'=>['Usuarios ('.count($users).')','users'],
            'invoices'=>['Facturas ('.count($invoices).')','file-text'],
            'payments'=>['Pagos ('.count($payments).')','wallet'],
        ] as $key => [$lbl,$ic]): ?>
            <button type="button" @click="tab='<?= $key ?>'" :class="tab==='<?= $key ?>' && 'active'" class="admin-tab"><i class="lucide lucide-<?= $ic ?> text-[13px]"></i> <?= $e($lbl) ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Info tab -->
    <div x-show="tab==='info'">
        <form method="POST" action="<?= $url('/admin/tenants/' . $t['id']) ?>" class="admin-card admin-card-pad">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="admin-label">Nombre</label><input name="name" value="<?= $e($t['name']) ?>" class="admin-input"></div>
                <div><label class="admin-label">Plan (legacy)</label>
                    <select name="plan" class="admin-select">
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= $e($p['slug']) ?>" <?= $t['plan']===$p['slug']?'selected':'' ?>><?= $e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="admin-label">Email soporte</label><input name="support_email" value="<?= $e($t['support_email']) ?>" class="admin-input"></div>
                <div><label class="admin-label">Email facturación</label><input name="billing_email" value="<?= $e($t['billing_email']) ?>" class="admin-input"></div>
                <div><label class="admin-label">Sitio web</label><input name="website" value="<?= $e($t['website']) ?>" class="admin-input"></div>
                <div><label class="admin-label">País</label><input name="country" value="<?= $e($t['country']) ?>" class="admin-input"></div>
                <div><label class="admin-label">Zona horaria</label><input name="timezone" value="<?= $e($t['timezone']) ?>" class="admin-input"></div>
                <div class="md:col-span-2"><label class="admin-label">Notas</label><textarea name="notes" rows="3" class="admin-textarea"><?= $e($t['notes']) ?></textarea></div>
            </div>
            <div class="mt-4"><button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar cambios</button></div>
        </form>
    </div>

    <!-- Subscription tab -->
    <div x-show="tab==='subscription'" x-cloak>
        <?php if ($subscription): ?>
            <form method="POST" action="<?= $url('/admin/subscriptions/' . $subscription['id']) ?>" class="admin-card admin-card-pad">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="admin-label">Plan</label>
                        <select name="plan_id" class="admin-select">
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= $subscription['plan_id']==$p['id']?'selected':'' ?>><?= $e($p['name']) ?> — $<?= number_format($p['price_monthly'],0) ?>/mes</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="admin-label">Estado</label>
                        <select name="status" class="admin-select">
                            <?php foreach (['trial','active','past_due','suspended','cancelled','expired'] as $s): ?>
                                <option value="<?= $s ?>" <?= $subscription['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="admin-label">Ciclo</label>
                        <select name="billing_cycle" class="admin-select">
                            <option value="monthly" <?= $subscription['billing_cycle']==='monthly'?'selected':'' ?>>Mensual</option>
                            <option value="yearly" <?= $subscription['billing_cycle']==='yearly'?'selected':'' ?>>Anual</option>
                            <option value="lifetime" <?= $subscription['billing_cycle']==='lifetime'?'selected':'' ?>>Lifetime</option>
                        </select>
                    </div>
                    <div><label class="admin-label">Monto ($)</label><input name="amount" type="number" step="0.01" value="<?= $e($subscription['amount']) ?>" class="admin-input"></div>
                    <div><label class="admin-label">Próximo cobro</label><input name="current_period_end" type="datetime-local" value="<?= $subscription['current_period_end'] ? str_replace(' ', 'T', $subscription['current_period_end']) : '' ?>" class="admin-input"></div>
                    <div><label class="admin-label flex items-center gap-2"><input type="checkbox" name="auto_renew" value="1" <?= $subscription['auto_renew']?'checked':'' ?>> Auto-renovación</label></div>
                </div>
                <div class="mt-4 flex gap-2"><button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar</button>
                    <button formaction="<?= $url('/admin/subscriptions/' . $subscription['id'] . '/cancel') ?>" class="admin-btn admin-btn-danger" onclick="return confirm('¿Cancelar suscripción?')"><i class="lucide lucide-x-circle"></i> Cancelar suscripción</button>
                </div>
            </form>
        <?php else: ?>
            <div class="admin-card admin-card-pad text-center text-ink-400">Sin suscripción registrada para esta empresa.</div>
        <?php endif; ?>
    </div>

    <!-- Users tab -->
    <div x-show="tab==='users'" x-cloak>
        <div class="admin-card">
            <div class="flex items-center justify-between p-5">
                <h2 class="admin-h2">Usuarios de <?= $e($t['name']) ?></h2>
                <a href="<?= $url('/admin/users/create?tenant_id=' . $t['id']) ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-user-plus"></i> Nuevo usuario</a>
            </div>
            <table class="admin-table">
                <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $e($u['name']) ?></td>
                        <td class="text-[12px] font-mono"><?= $e($u['email']) ?></td>
                        <td><span class="admin-pill admin-pill-gray"><?= $e($u['role_name'] ?? '—') ?></span></td>
                        <td><?= $u['is_active'] ? '<span class="admin-pill admin-pill-green">Activo</span>' : '<span class="admin-pill admin-pill-gray">Inactivo</span>' ?></td>
                        <td><a href="<?= $url('/admin/users/' . $u['id']) ?>" class="admin-btn admin-btn-soft" style="padding:5px 10px"><i class="lucide lucide-edit-3 text-[13px]"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?><tr><td colspan="5" style="text-align:center; padding:20px; color:#8e8e9a">Sin usuarios.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invoices tab -->
    <div x-show="tab==='invoices'" x-cloak>
        <div class="admin-card">
            <div class="flex items-center justify-between p-5">
                <h2 class="admin-h2">Facturas</h2>
                <a href="<?= $url('/admin/invoices/create?tenant_id=' . $t['id']) ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus"></i> Nueva factura</a>
            </div>
            <table class="admin-table">
                <thead><tr><th>Número</th><th>Total</th><th>Pagado</th><th>Estado</th><th>Vencimiento</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $i): ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/admin/invoices/' . $i['id']) ?>'">
                        <td class="font-mono text-[12px]"><?= $e($i['invoice_number']) ?></td>
                        <td>$<?= number_format($i['total'], 2) ?></td>
                        <td>$<?= number_format($i['amount_paid'], 2) ?></td>
                        <td><span class="admin-pill admin-pill-gray"><?= $e($i['status']) ?></span></td>
                        <td class="text-[11.5px] text-ink-500"><?= $e($i['due_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?><tr><td colspan="5" style="text-align:center; padding:20px; color:#8e8e9a">Sin facturas.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payments tab -->
    <div x-show="tab==='payments'" x-cloak>
        <div class="admin-card">
            <div class="p-5"><h2 class="admin-h2">Pagos</h2></div>
            <table class="admin-table">
                <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Factura</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="text-[11.5px] text-ink-500"><?= $e($p['paid_at']) ?></td>
                        <td>$<?= number_format($p['amount'], 2) ?></td>
                        <td><span class="admin-pill admin-pill-gray"><?= $e($p['method']) ?></span></td>
                        <td class="text-[12px] font-mono"><?= $e($p['reference']) ?></td>
                        <td>#<?= (int)$p['invoice_id'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?><tr><td colspan="5" style="text-align:center; padding:20px; color:#8e8e9a">Sin pagos registrados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
