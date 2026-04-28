<?php
use App\Core\Plan;
$slug = $tenant->slug;
$preselectedCompany = (int)($_GET['company_id'] ?? 0);
$preselectedAsset = (int)($_GET['asset_id'] ?? 0);
$allowedChannels = Plan::LIMITS[Plan::tenantPlan($tenant)]['channels'] ?? ['portal','email'];

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
?>

<a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a tickets</a>

<div>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Nuevo ticket</h1>
    <p class="text-[13px] text-ink-400">Los campos con * son obligatorios</p>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/tickets') ?>" enctype="multipart/form-data" class="card card-pad space-y-5 max-w-3xl"
      x-data="{files:[], catalog: <?= htmlspecialchars(json_encode($catalogJson, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>, selected: <?= (int)($catalogItemId ?? 0) ?: 'null' ?>,
               get selectedItem() { return this.selected ? this.catalog[this.selected] : null; },
               pick(id) {
                   this.selected = id;
                   const item = this.catalog[id]; if (!item) return;
                   if (this.$refs.subject && !this.$refs.subject.value) this.$refs.subject.value = item.name;
                   if (this.$refs.description && !this.$refs.description.value && item.description) this.$refs.description.value = item.description;
                   if (this.$refs.category && item.category_id) this.$refs.category.value = String(item.category_id);
               },
               clear() { this.selected = null; }}">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <input type="hidden" name="catalog_item_id" :value="selected || ''">

    <?php if (!empty($catalog)): ?>
        <div class="rounded-2xl p-4" style="background:linear-gradient(135deg,#fafafb,#f3f0ff);border:1px solid #cdbfff">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div>
                    <div class="text-[10.5px] uppercase tracking-[0.14em] font-bold" style="color:#5a3aff">Catálogo de servicios</div>
                    <h3 class="font-display font-bold text-[14px]">Atajo · seleccioná un servicio para autocompletar</h3>
                </div>
                <button type="button" @click="clear()" x-show="selected" x-cloak class="btn btn-soft btn-xs"><i class="lucide lucide-x text-[12px]"></i> Quitar</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($catalog as $item): ?>
                    <button type="button" @click="pick(<?= (int)$item['id'] ?>)"
                            :class="selected === <?= (int)$item['id'] ?> ? 'ring-2 ring-brand-400 bg-white' : 'bg-white/70 hover:bg-white'"
                            class="text-left p-3 rounded-xl border border-[#ececef] transition flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg grid place-items-center flex-shrink-0" style="background:<?= $e($item['color']) ?>15;color:<?= $e($item['color']) ?>"><i class="lucide lucide-<?= $e($item['icon']) ?> text-[15px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-[12.5px]"><?= $e($item['name']) ?></div>
                            <?php if (!empty($item['description'])): ?><div class="text-[11px] text-ink-500 line-clamp-2 mt-0.5"><?= $e($item['description']) ?></div><?php endif; ?>
                            <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                <?php if (!empty($item['requires_approval'])): ?><span class="badge badge-amber text-[10px]">Aprobación</span><?php endif; ?>
                                <?php if (!empty($item['sla_minutes'])): ?><span class="badge badge-blue text-[10px]">SLA <?= (int)$item['sla_minutes'] ?>min</span><?php endif; ?>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <template x-if="selectedItem && selectedItem.requires_approval">
            <div class="flex items-start gap-2 p-3 rounded-xl" style="background:#fef3c7;border:1px solid #fcd34d">
                <i class="lucide lucide-shield-alert text-[14px] mt-0.5" style="color:#b45309"></i>
                <div class="text-[11.5px] text-ink-900">Este servicio requiere aprobación del responsable. El ticket quedará en espera hasta que se decida.</div>
            </div>
        </template>
    <?php endif; ?>

    <div>
        <label class="label">Asunto *</label>
        <input name="subject" required class="input" placeholder="Breve descripción del problema" x-ref="subject">
    </div>
    <div>
        <label class="label">Descripción</label>
        <textarea name="description" rows="6" class="input" placeholder="Explica con detalle…" x-ref="description"></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-<?= !empty($departments) ? '4' : '3' ?> gap-4">
        <?php if (!empty($departments)): ?>
            <div>
                <label class="label flex items-center gap-1.5">Departamento <span class="text-[10px] uppercase tracking-[0.14em] px-1.5 py-0.5 rounded-full" style="background:#eff6ff;color:#1d4ed8">PRO</span></label>
                <select name="department_id" class="input">
                    <option value="0">— Sin departamento —</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int)$d['id'] ?>"><?= $e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10.5px] text-ink-400 mt-1">Asigna automáticamente a un líder del equipo</p>
            </div>
        <?php endif; ?>
        <div>
            <label class="label">Categoría</label>
            <select name="category_id" class="input" x-ref="category">
                <option value="0">Sin categoría</option>
                <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label">Prioridad</label>
            <select name="priority" class="input">
                <option value="low">Baja</option><option value="medium" selected>Media</option><option value="high">Alta</option><option value="urgent">Urgente</option>
            </select>
        </div>
        <div>
            <label class="label">Canal</label>
            <select name="channel" class="input">
                <?php foreach ([['internal','Interno'],['phone','Teléfono'],['email','Email'],['chat','Chat'],['portal','Portal']] as [$v,$l]):
                    $blocked = !in_array($v, $allowedChannels, true);
                ?><option value="<?= $v ?>" <?= $blocked?'disabled':'' ?>><?= $l ?><?= $blocked?' · plan superior':'' ?></option><?php endforeach; ?>
            </select>
            <?php if (count($allowedChannels) < 5): ?>
                <p class="text-[11px] text-ink-400 mt-1.5"><i class="lucide lucide-lock text-[10px]"></i> Tu plan permite: <?= implode(', ', $allowedChannels) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="pt-4 border-t border-[#ececef]">
        <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-3 text-ink-400">Solicitante</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">Empresa</label>
                <select name="company_id" class="input">
                    <option value="0">Selecciona…</option>
                    <?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $preselectedCompany === (int)$c['id'] ? 'selected' : '' ?>><?= $e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="label">Nombre</label><input name="requester_name" class="input"></div>
            <div><label class="label">Email</label><input name="requester_email" type="email" class="input"></div>
            <div><label class="label">Teléfono</label><input name="requester_phone" class="input"></div>
        </div>
    </div>
    <?php if ($auth->can('tickets.assign')): ?>
        <div>
            <label class="label">Asignar a</label>
            <select name="assigned_to" class="input">
                <option value="0">Sin asignar</option>
                <?php foreach ($technicians as $t): ?><option value="<?= (int)$t['id'] ?>"><?= $e($t['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div>
        <label class="label flex items-center gap-1.5">
            <i class="lucide lucide-paperclip text-[12px]"></i> Adjuntos <span class="text-[10.5px] text-ink-400 font-normal">(opcional · hasta 10 archivos · 25 MB c/u)</span>
        </label>
        <label class="block cursor-pointer rounded-2xl border-2 border-dashed transition" style="border-color:#e5e7eb">
            <input type="file" name="attachments[]" multiple class="sr-only" @change="files = Array.from($event.target.files)">
            <div x-show="files.length === 0" class="text-center py-5 px-4">
                <i class="lucide lucide-upload-cloud text-[24px]" style="color:#9ca3af"></i>
                <div class="text-[12.5px] mt-1.5 font-semibold text-ink-700">Click o arrastrá archivos</div>
                <div class="text-[11px] text-ink-400">Imágenes, PDF, Word, Excel, ZIP</div>
            </div>
            <div x-show="files.length > 0" x-cloak class="p-3 space-y-1">
                <template x-for="(f, i) in files" :key="i">
                    <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg bg-[#fafafb]">
                        <i class="lucide text-[13px]" :class="f.type.startsWith('image/') ? 'lucide-image' : (f.type === 'application/pdf' ? 'lucide-file-text' : 'lucide-file')" style="color:#6b6b78"></i>
                        <div class="flex-1 min-w-0 text-[12px] font-semibold truncate" x-text="f.name"></div>
                        <span class="text-[10.5px] text-ink-400" x-text="(f.size < 1024*1024 ? Math.round(f.size/1024) + ' KB' : (f.size/(1024*1024)).toFixed(1) + ' MB')"></span>
                    </div>
                </template>
            </div>
        </label>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Crear ticket</button>
    </div>
</form>
