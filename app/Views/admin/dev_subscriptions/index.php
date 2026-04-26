<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-h2">Suscripciones de developers</h2>
        <form method="GET" action="<?= $url('/admin/dev-subscriptions') ?>" class="flex gap-2">
            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach (['trial','active','past_due','suspended','cancelled','expired'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Developer</th><th>Plan</th><th>Estado</th><th>Ciclo</th><th>Monto</th><th>Próx. renov.</th><th>Auto-renew</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($subs)): ?>
                    <tr><td colspan="8" class="text-center py-10 text-ink-400">Sin suscripciones.</td></tr>
                <?php else: foreach ($subs as $s): ?>
                    <tr>
                        <td>
                            <a href="<?= $url('/admin/developers/' . $s['developer_id']) ?>" class="font-display font-bold hover:text-brand-700"><?= $e($s['dev_name']) ?></a>
                            <div class="text-[11.5px] text-ink-400"><?= $e($s['dev_email']) ?></div>
                        </td>
                        <td><span class="admin-pill admin-pill-purple"><?= $e($s['plan_name']) ?></span></td>
                        <td><span class="admin-pill <?= $s['status']==='active'?'admin-pill-green':($s['status']==='trial'?'admin-pill-amber':($s['status']==='cancelled'?'admin-pill-red':'admin-pill-gray')) ?>"><?= $e($s['status']) ?></span></td>
                        <td class="text-[12.5px]"><?= $e($s['billing_cycle']) ?></td>
                        <td class="font-display font-bold">$<?= number_format((float)$s['amount'], 2) ?></td>
                        <td class="text-[12.5px]"><?= $e($s['current_period_end'] ?? '—') ?></td>
                        <td><?= (int)$s['auto_renew']===1 ? '✓' : '—' ?></td>
                        <td>
                            <?php if ($s['status']!=='cancelled'): ?>
                                <form method="POST" action="<?= $url('/admin/dev-subscriptions/' . $s['id'] . '/cancel') ?>" onsubmit="return confirm('¿Cancelar suscripción?')">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="admin-btn admin-btn-danger admin-btn-icon" title="Cancelar"><i class="lucide lucide-x text-[13px]"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
