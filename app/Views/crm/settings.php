<?php $slug = $tenant->slug; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <a href="<?= $url('/t/' . $slug . '/crm') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver al CRM</a>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Configuración del CRM</h1>
        <p class="text-[12.5px] text-ink-400">Personalizá pipelines, etapas, orígenes de leads y tags. Los cambios afectan inmediatamente al kanban y a los formularios.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <!-- Pipelines y Stages -->
    <div class="card card-pad lg:col-span-2">
        <div class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-target text-brand-600"></i> Pipelines y etapas</div>

        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/pipelines') ?>" class="grid grid-cols-1 sm:grid-cols-6 gap-2 mb-5">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input class="input sm:col-span-2" name="name" placeholder="Nombre del pipeline (ej: Onboarding)" required>
            <input class="input sm:col-span-2" name="description" placeholder="Descripción corta">
            <input class="input" name="icon" placeholder="icon (lucide)" value="target">
            <div class="flex gap-1"><input type="color" class="input" name="color" value="#7c5cff" style="width:40px;padding:2px"><button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-plus"></i></button></div>
        </form>

        <div class="space-y-4">
            <?php if (empty($pipelines)): ?>
                <div class="text-center py-8 text-[12.5px] text-ink-400">Aún no hay pipelines configurados.</div>
            <?php else: foreach ($pipelines as $p):
                $stages = $stagesByPipeline[(int)$p['id']] ?? [];
            ?>
                <div class="border border-[#ececef] rounded-xl overflow-hidden">
                    <div class="p-3 flex items-center gap-3" style="background:<?= $e($p['color']) ?>0d">
                        <div class="w-8 h-8 rounded-lg grid place-items-center" style="background:<?= $e($p['color']) ?>;color:white"><i class="lucide lucide-<?= $e($p['icon']) ?> text-[13px]"></i></div>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/pipelines/' . (int)$p['id']) ?>" class="flex-1 grid grid-cols-1 sm:grid-cols-6 gap-2 items-center">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <input class="input sm:col-span-2" name="name" value="<?= $e($p['name']) ?>">
                            <input class="input sm:col-span-2" name="description" value="<?= $e($p['description'] ?? '') ?>">
                            <input class="input" name="icon" value="<?= $e($p['icon']) ?>">
                            <input type="color" class="input" name="color" value="<?= $e($p['color']) ?>" style="width:40px;padding:2px">
                            <label class="text-[11px] inline-flex items-center gap-1.5"><input type="checkbox" name="is_default" value="1" <?= (int)$p['is_default']===1?'checked':'' ?> class="rounded"> Default</label>
                            <label class="text-[11px] inline-flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" <?= (int)$p['is_active']===1?'checked':'' ?> class="rounded"> Activo</label>
                            <button class="btn btn-soft btn-sm sm:col-span-1"><i class="lucide lucide-save"></i></button>
                        </form>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/pipelines/' . (int)$p['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar pipeline?')">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="text-[11px] text-rose-600"><i class="lucide lucide-trash-2"></i></button>
                        </form>
                    </div>

                    <div class="p-3">
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400 mb-2">Etapas (<?= count($stages) ?>)</div>
                        <table class="admin-table" style="width:100%">
                            <thead>
                                <tr><th>Nombre</th><th class="text-right">Prob.</th><th>Color</th><th>Won</th><th>Lost</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stages as $st): ?>
                                    <tr>
                                        <td>
                                            <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/stages/' . (int)$st['id']) ?>" class="inline-flex items-center gap-2 w-full">
                                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                                <input class="input" name="name" value="<?= $e($st['name']) ?>" style="max-width:220px">
                                                <input type="number" min="0" max="100" class="input" name="probability" value="<?= (int)$st['probability'] ?>" style="width:80px">
                                                <input type="color" class="input" name="color" value="<?= $e($st['color']) ?>" style="width:40px;padding:2px">
                                                <label class="text-[11px] inline-flex items-center gap-1"><input type="checkbox" name="is_won" value="1" <?= (int)$st['is_won']===1?'checked':'' ?> class="rounded"> W</label>
                                                <label class="text-[11px] inline-flex items-center gap-1"><input type="checkbox" name="is_lost" value="1" <?= (int)$st['is_lost']===1?'checked':'' ?> class="rounded"> L</label>
                                                <button class="text-[11px] text-brand-700"><i class="lucide lucide-save text-[13px]"></i></button>
                                            </form>
                                        </td>
                                        <td colspan="4"></td>
                                        <td class="text-right">
                                            <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/stages/' . (int)$st['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar etapa?')">
                                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                                <button class="text-[11px] text-rose-600"><i class="lucide lucide-x"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/stages') ?>" class="grid grid-cols-1 sm:grid-cols-6 gap-2 mt-2 pt-2 border-t border-[#ececef]">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <input type="hidden" name="pipeline_id" value="<?= (int)$p['id'] ?>">
                            <input class="input sm:col-span-2" name="name" placeholder="Nombre de etapa" required>
                            <input type="number" min="0" max="100" class="input" name="probability" placeholder="Prob. %" value="0">
                            <input type="color" class="input" name="color" value="#94a3b8" style="padding:2px">
                            <label class="text-[11px] inline-flex items-center gap-1"><input type="checkbox" name="is_won" value="1" class="rounded"> Ganada</label>
                            <button class="btn btn-soft btn-sm"><i class="lucide lucide-plus"></i> Etapa</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Sources -->
    <div class="card card-pad">
        <div class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-radar text-sky-600"></i> Orígenes de leads</div>
        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/sources') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-2 mb-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input class="input sm:col-span-2" name="name" placeholder="Nombre (ej: Webinar)" required>
            <input class="input" name="icon" placeholder="globe" value="globe">
            <input type="color" class="input" name="color" value="#6366f1" style="padding:2px">
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i></button>
        </form>
        <div class="space-y-1.5">
            <?php foreach ($sources as $s): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/sources/' . (int)$s['id']) ?>" class="flex items-center gap-2">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div class="w-7 h-7 rounded-lg grid place-items-center flex-shrink-0" style="background:<?= $e($s['color']) ?>1a;color:<?= $e($s['color']) ?>"><i class="lucide lucide-<?= $e($s['icon']) ?> text-[12px]"></i></div>
                    <input class="input" name="name" value="<?= $e($s['name']) ?>" style="max-width:180px">
                    <input class="input" name="icon" value="<?= $e($s['icon']) ?>" style="max-width:120px">
                    <input type="color" class="input" name="color" value="<?= $e($s['color']) ?>" style="width:40px;padding:2px">
                    <label class="text-[11px] inline-flex items-center gap-1"><input type="checkbox" name="is_active" value="1" <?= (int)$s['is_active']===1?'checked':'' ?> class="rounded"> On</label>
                    <button class="text-[11px] text-brand-700"><i class="lucide lucide-save text-[13px]"></i></button>
                    <button type="button" onclick="if(confirm('¿Eliminar origen?')){this.closest('form').nextElementSibling.submit()}" class="text-[11px] text-rose-600"><i class="lucide lucide-x"></i></button>
                </form>
                <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/sources/' . (int)$s['id'] . '/delete') ?>" class="hidden">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tags -->
    <div class="card card-pad">
        <div class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-tags text-violet-600"></i> Tags</div>
        <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/tags') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-2 mb-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input class="input sm:col-span-2" name="name" placeholder="Nombre del tag" required>
            <input type="color" class="input" name="color" value="#7c5cff" style="padding:2px">
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i></button>
        </form>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($tags as $t): ?>
                <div class="inline-flex items-center gap-1 text-[12px] font-semibold px-2.5 py-1 rounded-full" style="background:<?= $e($t['color']) ?>1a;color:<?= $e($t['color']) ?>;border:1px solid <?= $e($t['color']) ?>33">
                    <i class="lucide lucide-tag text-[10px]"></i> <?= $e($t['name']) ?>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/crm/settings/tags/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar tag?')" class="inline">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="ml-1"><i class="lucide lucide-x text-[10px]"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
