<?php
$planLabels = ['free'=>'Free / Starter','pro'=>'Pro','business'=>'Business','enterprise'=>'Enterprise'];
$planColors = ['free'=>'#6b7280','pro'=>'#7c5cff','business'=>'#0ea5e9','enterprise'=>'#16a34a'];
?>
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="admin-stat" style="border-top:3px solid #7c5cff"><div class="admin-stat-label">Integraciones</div><div class="admin-stat-value"><?= number_format((int)$totals['integrations']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #16a34a"><div class="admin-stat-label">Activas</div><div class="admin-stat-value"><?= number_format((int)$totals['active']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #0ea5e9"><div class="admin-stat-label">Tenants con integraciones</div><div class="admin-stat-value"><?= number_format((int)$totals['tenants']) ?></div></div>
    <div class="admin-stat" style="border-top:3px solid #f59e0b"><div class="admin-stat-label">Eventos últimas 24h</div><div class="admin-stat-value"><?= number_format((int)$totals['logs_24h']) ?></div></div>
</div>

<form method="POST" action="<?= $url('/admin/integration-limits') ?>" class="admin-card">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <div class="admin-card-head">
        <div>
            <h2 class="admin-h2">Límites por plan</h2>
            <p class="text-[12.5px] text-ink-400">Define cuántas integraciones puede instalar cada plan y qué proveedores quedan disponibles</p>
        </div>
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar cambios</button>
    </div>
    <div class="admin-card-pad">
        <div class="space-y-5">
            <?php foreach ($plans as $plan):
                $color = $planColors[$plan] ?? '#7c5cff';
                $allowed = $providersByPlan[$plan];
            ?>
                <div class="rounded-2xl p-5" style="border:1px solid #ececef;background:linear-gradient(135deg,#fff,<?= $color ?>05)">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $color ?>15;color:<?= $color ?>;border:1px solid <?= $color ?>30"><i class="lucide lucide-zap text-[16px]"></i></div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-[15px]"><?= $e($planLabels[$plan] ?? ucfirst($plan)) ?></h3>
                            <p class="text-[11.5px] text-ink-400">Slug: <code class="font-mono"><?= $e($plan) ?></code></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400">Máx. integraciones</label>
                            <input name="max_<?= $e($plan) ?>" type="number" min="0" value="<?= (int)$limits[$plan] ?>" class="admin-input mt-1">
                            <p class="text-[10.5px] text-ink-400 mt-1">0 = deshabilitado · valores altos = sin límite efectivo</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400">Proveedores permitidos</label>
                            <p class="text-[10.5px] text-ink-400 mt-1 mb-2">Sin selección = todos disponibles · selecciona para limitar</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                                <?php foreach ($allProviders as $provSlug => $def): ?>
                                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg hover:bg-white text-[12px] cursor-pointer transition" style="border:1px solid #ececef">
                                        <input type="checkbox" name="providers_<?= $e($plan) ?>[]" value="<?= $e($provSlug) ?>" <?= in_array($provSlug, $allowed, true)?'checked':'' ?>>
                                        <span class="w-5 h-5 rounded grid place-items-center flex-shrink-0" style="background:<?= $e($def['color']) ?>15;color:<?= $e($def['color']) ?>"><i class="lucide lucide-<?= $e($def['icon']) ?> text-[10px]"></i></span>
                                        <span class="truncate font-medium"><?= $e($def['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<div class="admin-card mt-6">
    <div class="admin-card-head">
        <h2 class="admin-h2">Uso por tenant</h2>
        <p class="text-[12px] text-ink-400">Top tenants por cantidad de integraciones instaladas</p>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Tenant</th><th>Plan</th><th>Total</th><th>Activas</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($usage as $row): ?>
                    <tr>
                        <td><a href="<?= $url('/admin/tenants/' . (int)$row['id']) ?>" style="color:inherit;font-weight:600"><?= $e($row['name']) ?></a></td>
                        <td><span class="admin-pill admin-pill-purple"><?= $e($row['plan']) ?></span></td>
                        <td class="font-mono"><?= number_format((int)$row['count_total']) ?></td>
                        <td class="font-mono" style="color:#16a34a;font-weight:600"><?= number_format((int)$row['count_active']) ?></td>
                        <td><a href="<?= $url('/admin/tenants/' . (int)$row['id']) ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-arrow-right text-[12px]"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usage)): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:#8e8e9a">Aún no hay tenants con integraciones</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
