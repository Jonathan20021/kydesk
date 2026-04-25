<form method="POST" action="<?= $url('/admin/invoices') ?>" class="max-w-3xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Empresa *</label>
                <select name="tenant_id" required class="admin-select">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $tenantId==$t['id']?'selected':'' ?>><?= $e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="admin-label">Estado inicial</label>
                <select name="status" class="admin-select">
                    <option value="pending">Pendiente</option>
                    <option value="draft">Borrador</option>
                    <option value="paid">Pagada</option>
                </select>
            </div>
            <div><label class="admin-label">Subtotal ($)</label><input name="subtotal" type="number" step="0.01" required class="admin-input"></div>
            <div><label class="admin-label">Descuento ($)</label><input name="discount" type="number" step="0.01" value="0" class="admin-input"></div>
            <div><label class="admin-label">Tasa de impuesto (%)</label><input name="tax_rate" type="number" step="0.01" value="0" class="admin-input"></div>
            <div><label class="admin-label">Moneda</label><input name="currency" value="USD" class="admin-input"></div>
            <div><label class="admin-label">Fecha emisión</label><input name="issue_date" type="date" value="<?= date('Y-m-d') ?>" class="admin-input"></div>
            <div><label class="admin-label">Vence</label><input name="due_date" type="date" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" class="admin-input"></div>
            <div class="md:col-span-2"><label class="admin-label">Descripción</label><input name="description" class="admin-input" placeholder="Suscripción Pro - Mes de marzo"></div>
            <div class="md:col-span-2"><label class="admin-label">Notas</label><textarea name="notes" rows="3" class="admin-textarea"></textarea></div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="admin-btn admin-btn-primary"><i class="lucide lucide-check"></i> Crear factura</button>
        <a href="<?= $url('/admin/invoices') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
