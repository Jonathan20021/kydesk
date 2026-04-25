<div class="flex items-center justify-end mb-4">
    <a href="<?= $url('/admin/plans/create') ?>" class="admin-btn admin-btn-primary"><i class="lucide lucide-plus"></i> Nuevo plan</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($plans as $p):
        $features = json_decode($p['features'] ?? '[]', true) ?: [];
    ?>
        <div class="admin-card admin-card-pad relative overflow-hidden" style="<?= !$p['is_active'] ? 'opacity:.55' : '' ?>">
            <div style="position:absolute;top:0;left:0;width:100%;height:4px;background:<?= $e($p['color']) ?>"></div>
            <?php if ($p['is_featured']): ?>
                <div class="admin-pill admin-pill-purple" style="position:absolute;top:14px;right:14px"><i class="lucide lucide-star text-[10px]"></i> Destacado</div>
            <?php endif; ?>
            <div style="display:flex; align-items:center; gap:10px; margin-top:10px">
                <div style="width:38px;height:38px;border-radius:10px;background:<?= $e($p['color']) ?>;color:white;display:grid;place-items:center"><i class="lucide lucide-<?= $e($p['icon']) ?> text-[16px]"></i></div>
                <div>
                    <div style="font-weight:700; font-size:15px"><?= $e($p['name']) ?></div>
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.14em] text-ink-400"><?= $e($p['slug']) ?></div>
                </div>
            </div>
            <div style="font-family:'Plus Jakarta Sans';font-weight:800;font-size:30px;letter-spacing:-.025em;margin-top:14px">$<?= number_format($p['price_monthly'],0) ?><span style="font-size:13px;color:#8e8e9a">/mes</span></div>
            <div class="text-[12px] text-ink-500 mt-1">$<?= number_format($p['price_yearly'],0) ?>/año (anual)</div>

            <div class="text-[11.5px] text-ink-500 mt-3 space-y-1">
                <div><i class="lucide lucide-users text-[12px]"></i> <?= (int)$p['max_users'] ?> usuarios</div>
                <div><i class="lucide lucide-inbox text-[12px]"></i> <?= number_format($p['max_tickets_month']) ?> tickets/mes</div>
                <div><i class="lucide lucide-book-open text-[12px]"></i> <?= (int)$p['max_kb_articles'] ?> artículos KB</div>
                <div><i class="lucide lucide-clock text-[12px]"></i> <?= (int)$p['trial_days'] ?> días trial</div>
            </div>

            <div class="mt-3 pt-3 border-t border-[#ececef] grid grid-cols-2 gap-2 text-[11px]">
                <div><span class="text-ink-400">Activas:</span> <strong><?= (int)$p['active_subs'] ?></strong></div>
                <div><span class="text-ink-400">Total:</span> <strong><?= (int)$p['total_subs'] ?></strong></div>
            </div>

            <div class="flex gap-1.5 mt-3">
                <a href="<?= $url('/admin/plans/' . $p['id']) ?>" class="admin-btn admin-btn-soft" style="flex:1; justify-content:center"><i class="lucide lucide-edit-3 text-[13px]"></i> Editar</a>
                <form method="POST" action="<?= $url('/admin/plans/' . $p['id'] . '/toggle') ?>" style="flex:1">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="admin-btn admin-btn-soft" style="width:100%; justify-content:center"><i class="lucide lucide-power text-[13px]"></i></button>
                </form>
                <?php if ($p['total_subs'] == 0): ?>
                    <form method="POST" action="<?= $url('/admin/plans/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar plan?')">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="admin-btn admin-btn-danger" style="padding:8px 10px"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($plans)): ?>
        <div class="admin-card admin-card-pad text-center text-ink-400 lg:col-span-4">No hay planes definidos.</div>
    <?php endif; ?>
</div>
