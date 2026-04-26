<?php $slug = $tenant->slug;
$icons = ['tag','cpu','box','wifi','key','folder','shield','globe','mail','phone','life-buoy','server','wrench','bug','file-text','book','users','building-2','printer','monitor','wifi-off','lock','credit-card','headphones'];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Categorías de tickets</h1>
        <p class="text-[13px] text-ink-400">Organiza los tickets por tipo de incidencia</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Crear -->
    <div class="card card-pad lg:col-span-1">
        <h3 class="font-display font-bold text-[15px] mb-3"><i class="lucide lucide-plus text-brand-600"></i> Nueva categoría</h3>
        <form method="POST" action="<?= $url('/t/' . $slug . '/categories') ?>" class="space-y-3" x-data="{color:'#7c5cff', icon:'tag'}">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div>
                <label class="text-[11.5px] font-semibold text-ink-700">Nombre</label>
                <input name="name" required maxlength="80" class="input mt-1" placeholder="Ej: Acceso VPN">
            </div>
            <div>
                <label class="text-[11.5px] font-semibold text-ink-700">Color</label>
                <div class="mt-1.5 flex items-center gap-2">
                    <input type="color" name="color" x-model="color" class="w-10 h-10 rounded-lg border border-[#ececef] cursor-pointer" style="padding:2px">
                    <input type="text" x-model="color" maxlength="7" class="input font-mono" style="max-width:120px">
                </div>
            </div>
            <div>
                <label class="text-[11.5px] font-semibold text-ink-700">Icono</label>
                <div class="mt-1.5 grid grid-cols-8 gap-1">
                    <?php foreach ($icons as $ic): ?>
                        <button type="button" @click="icon='<?= $ic ?>'" :class="icon==='<?= $ic ?>' ? 'bg-brand-50 border-brand-300 text-brand-700' : 'border-[#ececef] text-ink-500 hover:text-ink-900'" class="w-9 h-9 grid place-items-center rounded-lg border transition" :style="icon==='<?= $ic ?>' ? 'background:'+color+'12;border-color:'+color+'80;color:'+color : ''">
                            <i class="lucide lucide-<?= $ic ?> text-[14px]"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="icon" :value="icon">
            </div>
            <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear categoría</button>
        </form>
    </div>

    <!-- Listado -->
    <div class="lg:col-span-2 space-y-2">
        <?php if (empty($categories)): ?>
            <div class="card card-pad text-center py-16">
                <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-tags text-[22px] text-ink-400"></i></div>
                <div class="font-display font-bold">Sin categorías</div>
                <p class="text-[13px] text-ink-400 mt-1">Crea la primera con el formulario de la izquierda</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $c): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/categories/' . $c['id']) ?>" class="card card-pad flex flex-wrap items-center gap-3" x-data="{editing:false}">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0" style="background:<?= $e($c['color']) ?>15;color:<?= $e($c['color']) ?>;border:1px solid <?= $e($c['color']) ?>30">
                        <i class="lucide lucide-<?= $e($c['icon']) ?> text-[16px]"></i>
                    </div>
                    <div class="flex-1 min-w-[180px]" x-show="!editing">
                        <div class="font-display font-bold text-[14.5px]"><?= $e($c['name']) ?></div>
                        <div class="text-[11.5px] text-ink-400"><?= number_format((int)$c['ticket_count']) ?> tickets · <code class="font-mono"><?= $e($c['color']) ?></code></div>
                    </div>
                    <div x-show="editing" x-cloak class="flex flex-wrap items-center gap-2 flex-1">
                        <input name="name" value="<?= $e($c['name']) ?>" required class="input" style="max-width:240px;font-weight:600">
                        <input type="color" name="color" value="<?= $e($c['color']) ?>" class="w-10 h-10 rounded-lg border border-[#ececef]">
                        <input name="icon" value="<?= $e($c['icon']) ?>" class="input font-mono" style="max-width:120px">
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" @click="editing=!editing" class="btn btn-outline btn-sm"><i class="lucide lucide-pencil text-[12px]"></i> <span x-text="editing ? 'Cancelar' : 'Editar'"></span></button>
                        <button x-show="editing" x-cloak class="btn btn-primary btn-sm"><i class="lucide lucide-check text-[12px]"></i> Guardar</button>
                    </div>
                </form>
            <?php endforeach; ?>
            <?php foreach ($categories as $c): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/categories/' . $c['id'] . '/delete') ?>" class="hidden" id="del-cat-<?= $c['id'] ?>" onsubmit="return confirm('¿Eliminar esta categoría? Los tickets que la usaban quedarán sin categoría.')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
