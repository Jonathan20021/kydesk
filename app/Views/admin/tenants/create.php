<form method="POST" action="<?= $url('/admin/tenants') ?>" class="max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Datos de la empresa</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Nombre *</label>
                <input name="name" required class="admin-input" placeholder="Acme Inc.">
            </div>
            <div>
                <label class="admin-label">Slug (URL) *</label>
                <input name="slug" class="admin-input" placeholder="acme">
                <div class="text-[11px] text-ink-400 mt-1">Auto-generado desde el nombre si lo dejas vacío.</div>
            </div>
            <div>
                <label class="admin-label">País</label>
                <input name="country" class="admin-input" placeholder="México">
            </div>
            <div>
                <label class="admin-label">Sitio web</label>
                <input name="website" class="admin-input" placeholder="https://...">
            </div>
            <div>
                <label class="admin-label">Email facturación</label>
                <input name="billing_email" type="email" class="admin-input" placeholder="billing@empresa.com">
            </div>
            <div class="md:col-span-2">
                <label class="admin-label">Notas internas</label>
                <textarea name="notes" rows="2" class="admin-textarea"></textarea>
            </div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Plan y facturación</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($plans as $p): ?>
                <label style="cursor:pointer;display:block">
                    <input type="radio" name="plan_id" value="<?= (int)$p['id'] ?>" <?= $p['slug']==='pro'?'checked':'' ?> style="display:none" class="plan-radio">
                    <div class="plan-card" style="padding:16px;border:2px solid #ececef;border-radius:14px;transition:all .15s">
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:30px;height:30px;border-radius:8px;background:<?= $e($p['color']) ?>;color:white;display:grid;place-items:center"><i class="lucide lucide-<?= $e($p['icon']) ?> text-[14px]"></i></div>
                            <div style="font-weight:700; font-size:14px"><?= $e($p['name']) ?></div>
                        </div>
                        <div style="font-family:'Plus Jakarta Sans';font-weight:800;font-size:24px;margin-top:8px">$<?= number_format($p['price_monthly'],0) ?><span style="font-size:12px;color:#8e8e9a">/mes</span></div>
                        <div class="text-[11px] text-ink-400 mt-1"><?= (int)$p['max_users'] ?> usuarios · <?= (int)$p['trial_days'] ?>d trial</div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
        <style>.plan-radio:checked + .plan-card { border-color:#d946ef; background:#fdf4ff; box-shadow:0 4px 14px -4px rgba(217,70,239,.3) }</style>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="admin-label">Ciclo de cobro</label>
                <select name="billing_cycle" class="admin-select">
                    <option value="monthly">Mensual</option>
                    <option value="yearly">Anual (descuento)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Owner (cuenta principal)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Nombre completo *</label>
                <input name="owner_name" required class="admin-input">
            </div>
            <div>
                <label class="admin-label">Email *</label>
                <input name="owner_email" type="email" required class="admin-input">
            </div>
            <div>
                <label class="admin-label">Contraseña inicial *</label>
                <input name="owner_password" type="text" required class="admin-input" placeholder="Mínimo 6 caracteres">
                <div class="text-[11px] text-ink-400 mt-1">El owner deberá cambiarla en su primer acceso.</div>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-check"></i> Crear empresa</button>
        <a href="<?= $url('/admin/tenants') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
