<?php
$slug = $tenantPublic->slug;
$isManager = !empty($portalUser['is_company_manager']);

$catalogJson = [];
foreach ($catalog ?? [] as $c) {
    $catalogJson[(int)$c['id']] = [
        'name' => $c['name'],
        'description' => (string)$c['description'],
        'category_id' => (int)$c['category_id'],
        'sla_minutes' => (int)$c['sla_minutes'],
        'requires_approval' => (int)$c['requires_approval'],
    ];
}

ob_start(); ?>
<a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 mb-3"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>

<div class="max-w-3xl mx-auto" x-data="catalogForm()">
    <div class="mb-4">
        <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em]">Nuevo ticket</h1>
        <p class="text-[12.5px] text-ink-500">Será asociado automáticamente a <strong><?= $e($company['name']) ?></strong>.</p>
    </div>

    <?php if (!empty($catalog)): ?>
        <div class="card card-pad mb-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div>
                    <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold text-ink-400">Catálogo de servicios</div>
                    <h3 class="font-display font-bold text-[15px]">¿Qué servicio necesitás?</h3>
                </div>
                <button type="button" @click="clearItem()" x-show="selected" x-cloak class="btn btn-soft btn-xs"><i class="lucide lucide-x text-[12px]"></i> Quitar</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($catalog as $item): ?>
                    <button type="button"
                            @click="pickItem(<?= (int)$item['id'] ?>)"
                            :class="selected === <?= (int)$item['id'] ?> ? 'ring-2 ring-brand-400 bg-brand-50/40' : 'hover:bg-[#fafafb]'"
                            class="text-left p-3 rounded-2xl border border-[#ececef] transition flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:<?= $e($item['color']) ?>15;color:<?= $e($item['color']) ?>"><i class="lucide lucide-<?= $e($item['icon']) ?> text-[16px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-[13px]"><?= $e($item['name']) ?></div>
                            <?php if (!empty($item['description'])): ?><div class="text-[11.5px] text-ink-500 line-clamp-2 mt-0.5"><?= $e($item['description']) ?></div><?php endif; ?>
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                <?php if (!empty($item['requires_approval'])): ?><span class="badge badge-amber text-[10px]">Aprobación</span><?php endif; ?>
                                <?php if (!empty($item['sla_minutes'])): ?><span class="badge badge-blue text-[10px]">SLA <?= (int)$item['sla_minutes'] ?>min</span><?php endif; ?>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="text-[11px] text-ink-400 mt-3">¿No encontrás lo que buscás? Dejá vacío y describí tu necesidad libremente abajo.</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $url('/portal/' . $slug . '/company/tickets') ?>" enctype="multipart/form-data" class="card card-pad space-y-4">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="catalog_item_id" :value="selected || ''">

        <template x-if="selectedItem && selectedItem.requires_approval">
            <div class="flex items-start gap-2 p-3 rounded-xl" style="background:#fef3c7;border:1px solid #fcd34d">
                <i class="lucide lucide-shield-alert text-[15px] mt-0.5" style="color:#b45309"></i>
                <div class="text-[11.5px] text-ink-900">Este servicio requiere aprobación. Cuando lo envíes, quedará en espera hasta que el responsable apruebe o rechace.</div>
            </div>
        </template>

        <?php if ($isManager && !empty($contacts)): ?>
            <div>
                <label class="label">Crear en nombre de <span class="text-ink-400 font-normal">(opcional)</span></label>
                <select name="on_behalf_of" class="input">
                    <option value="0">Yo mismo (<?= $e($portalUser['name']) ?>)</option>
                    <?php foreach ($contacts as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?> &lt;<?= $e($c['email']) ?>&gt;<?= $c['title'] ? ' · ' . $e($c['title']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-ink-400 mt-1">Como manager podés registrar tickets en nombre de otros contactos de tu empresa.</p>
            </div>
        <?php endif; ?>

        <div>
            <label class="label">Asunto <span class="text-rose-600">*</span></label>
            <input name="subject" required class="input" placeholder="Resumen breve del problema" x-ref="subject">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label">Categoría</label>
                <select name="category_id" class="input" x-ref="category">
                    <option value="0">Sin categoría</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Prioridad</label>
                <select name="priority" class="input">
                    <option value="low">Baja</option>
                    <option value="medium" selected>Media</option>
                    <option value="high">Alta</option>
                    <option value="urgent">Urgente</option>
                </select>
            </div>
        </div>

        <div>
            <label class="label">Descripción <span class="text-rose-600">*</span></label>
            <textarea name="description" required rows="6" class="input" placeholder="Describe el problema con todo el detalle posible…" x-ref="description"></textarea>
        </div>

        <div x-data="{files:[]}">
            <label class="label">Adjuntos</label>
            <div x-show="files.length > 0" x-cloak class="mb-2 flex flex-wrap gap-1.5">
                <template x-for="(f, i) in files" :key="i">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] bg-brand-50 text-brand-700 border border-brand-200">
                        <i class="lucide lucide-paperclip text-[11px]"></i>
                        <span class="truncate max-w-[180px]" x-text="f.name"></span>
                    </span>
                </template>
            </div>
            <label class="btn btn-outline btn-sm cursor-pointer">
                <i class="lucide lucide-paperclip text-[13px]"></i>
                <span x-text="files.length === 0 ? 'Adjuntar archivos' : files.length + ' archivo' + (files.length === 1 ? '' : 's')"></span>
                <input type="file" name="attachments[]" multiple class="hidden" @change="files = Array.from($event.target.files)">
            </label>
            <p class="text-[10.5px] text-ink-400 mt-1.5">Hasta 10 archivos · 25 MB cada uno.</p>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#ececef] pt-4">
            <a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="btn btn-soft btn-sm">Cancelar</a>
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> <span x-text="selectedItem && selectedItem.requires_approval ? 'Enviar para aprobación' : 'Crear ticket'"></span></button>
        </div>
    </form>
</div>

<script>
function catalogForm() {
    return {
        catalog: <?= json_encode($catalogJson, JSON_UNESCAPED_UNICODE) ?>,
        selected: <?= (int)($catalogItemId ?? 0) ?: 'null' ?>,
        get selectedItem() { return this.selected ? this.catalog[this.selected] : null; },
        init() { if (this.selected) this.applyItem(); },
        pickItem(id) { this.selected = id; this.applyItem(); },
        clearItem() { this.selected = null; },
        applyItem() {
            const item = this.selectedItem;
            if (!item) return;
            if (this.$refs.subject && !this.$refs.subject.value) this.$refs.subject.value = item.name;
            if (this.$refs.description && !this.$refs.description.value && item.description) this.$refs.description.value = item.description;
            if (this.$refs.category && item.category_id) this.$refs.category.value = String(item.category_id);
        },
    };
}
</script>
<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
