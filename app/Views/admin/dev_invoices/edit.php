<form method="POST" action="<?= $url('/admin/dev-invoices') ?>" class="admin-card admin-card-pad max-w-[820px] space-y-5">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <h2 class="admin-h2">Crear factura para developer</h2>

    <div>
        <label class="admin-label">Developer</label>
        <select name="developer_id" required class="admin-select">
            <?php foreach ($developers as $d): ?>
                <option value="<?= $d['id'] ?>"><?= $e($d['name']) ?> · <?= $e($d['email']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="admin-label">Descripción</label>
        <input type="text" name="description" class="admin-input" placeholder="Servicios API agosto 2025">
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="admin-label">Subtotal ($)</label>
            <input type="number" step="0.01" name="subtotal" class="admin-input" required value="0">
        </div>
        <div>
            <label class="admin-label">Tasa de impuesto (%)</label>
            <input type="number" step="0.01" name="tax_rate" class="admin-input" value="0">
        </div>
        <div>
            <label class="admin-label">Descuento ($)</label>
            <input type="number" step="0.01" name="discount" class="admin-input" value="0">
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="admin-label">Moneda</label>
            <input type="text" name="currency" class="admin-input" value="USD">
        </div>
        <div>
            <label class="admin-label">Fecha emisión</label>
            <input type="date" name="issue_date" class="admin-input" value="<?= date('Y-m-d') ?>">
        </div>
        <div>
            <label class="admin-label">Fecha vencimiento</label>
            <input type="date" name="due_date" class="admin-input" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>
    </div>

    <div>
        <label class="admin-label">Estado</label>
        <select name="status" class="admin-select">
            <option value="pending">Pendiente</option>
            <option value="draft">Borrador</option>
            <option value="paid">Pagada</option>
            <option value="overdue">Vencida</option>
        </select>
    </div>

    <div>
        <label class="admin-label">Notas</label>
        <textarea name="notes" class="admin-textarea" rows="3"></textarea>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="admin-btn admin-btn-primary"><i class="lucide lucide-save text-[13px]"></i> Crear factura</button>
        <a href="<?= $url('/admin/dev-invoices') ?>" class="admin-btn admin-btn-soft">Cancelar</a>
    </div>
</form>
