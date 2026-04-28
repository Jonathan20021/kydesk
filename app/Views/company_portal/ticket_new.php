<?php
$slug = $tenantPublic->slug;
$isManager = !empty($portalUser['is_company_manager']);
ob_start(); ?>
<a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 mb-3"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>

<div class="max-w-3xl mx-auto">
    <div class="mb-4">
        <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em]">Nuevo ticket</h1>
        <p class="text-[12.5px] text-ink-500">Será asociado automáticamente a <strong><?= $e($company['name']) ?></strong>.</p>
    </div>

    <form method="POST" action="<?= $url('/portal/' . $slug . '/company/tickets') ?>" enctype="multipart/form-data" class="card card-pad space-y-4" x-data="{files:[]}">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

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
            <input name="subject" required class="input" placeholder="Resumen breve del problema">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label">Categoría</label>
                <select name="category_id" class="input">
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
            <textarea name="description" required rows="6" class="input" placeholder="Describe el problema con todo el detalle posible…"></textarea>
        </div>

        <div>
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
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Crear ticket</button>
        </div>
    </form>
</div>
<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
