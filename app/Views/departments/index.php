<?php
$slug = $tenant->slug;
$icons = ['layers','life-buoy','headphones','wrench','heart-handshake','users','user-cog','briefcase','building-2','warehouse','graduation-cap','shield','wallet','credit-card','trending-up','megaphone','box','truck','book-open','code-2','flask-conical','sparkles','globe','zap'];
$colors = ['#3b82f6','#7c5cff','#22c55e','#f59e0b','#ec4899','#0ea5e9','#10b981','#a855f7','#ef4444','#14b8a6'];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] px-2.5 py-0.5 rounded-full" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe">
                <i class="lucide lucide-crown text-[11px]"></i> PRO
            </span>
            <span class="text-[11px] text-ink-400">Función incluida en tu plan</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Departamentos</h1>
        <p class="text-[13px] text-ink-400">Organiza el equipo, enruta tickets automáticamente y mide rendimiento por área</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php foreach ([
        ['Departamentos', $stats['total'], 'layers', '#3b82f6', '#eff6ff'],
        ['Activos',       $stats['active'], 'check-circle-2', '#16a34a', '#f0fdf4'],
        ['Agentes',       $stats['agents'], 'users', '#7c5cff', '#f3f0ff'],
        ['Sin departamento', $stats['unassigned'], 'alert-circle', '#f59e0b', '#fffbeb'],
    ] as [$lbl,$val,$ic,$col,$bg]): ?>
        <div class="card card-pad flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $e($lbl) ?></div>
                <div class="font-display font-extrabold text-[22px] tracking-[-0.02em]"><?= number_format((int)$val) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Crear -->
    <div class="card card-pad lg:col-span-1" x-data="{color:'#3b82f6', icon:'layers'}">
        <h3 class="font-display font-bold text-[15px] mb-1 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo departamento</h3>
        <p class="text-[11.5px] text-ink-400 mb-3">Define un área funcional con su propio equipo</p>
        <form method="POST" action="<?= $url('/t/' . $slug . '/departments') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div>
                <label class="label">Nombre</label>
                <input name="name" required maxlength="120" class="input" placeholder="Ej: Soporte Técnico Nivel 2">
            </div>
            <div>
                <label class="label">Descripción</label>
                <textarea name="description" rows="2" class="input" placeholder="¿De qué se ocupa este departamento?"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="label">Color</label>
                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                        <?php foreach ($colors as $col): ?>
                            <button type="button" @click="color='<?= $col ?>'" :class="color==='<?= $col ?>' ? 'ring-2 ring-offset-2' : ''" class="w-7 h-7 rounded-lg" style="background:<?= $col ?>;<?= $col === '#3b82f6' ? '--tw-ring-color:'.$col : '' ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="color" x-model="color" class="mt-1.5 w-full h-9 rounded-lg border border-[#ececef]">
                    <input type="hidden" name="color" :value="color">
                </div>
                <div>
                    <label class="label">Email (opcional)</label>
                    <input name="email" type="email" class="input" placeholder="soporte@empresa.com">
                </div>
            </div>
            <div>
                <label class="label">Icono</label>
                <div class="mt-1 grid grid-cols-8 gap-1">
                    <?php foreach ($icons as $ic): ?>
                        <button type="button" @click="icon='<?= $ic ?>'" :class="icon==='<?= $ic ?>' ? 'border-2' : 'border'" class="w-9 h-9 grid place-items-center rounded-lg transition" :style="icon==='<?= $ic ?>' ? 'background:'+color+'12;border-color:'+color+';color:'+color : 'border-color:#ececef;color:#8e8e9a'">
                            <i class="lucide lucide-<?= $ic ?> text-[14px]"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="icon" :value="icon">
            </div>
            <div>
                <label class="label">Líder / Manager (opcional)</label>
                <select name="manager_user_id" class="input">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($technicians as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= $e($t['name']) ?><?= !empty($t['title']) ? ' — '.$e($t['title']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="flex items-center gap-2 text-[13px]">
                <input type="checkbox" name="is_active" value="1" checked> Activar inmediatamente
            </label>
            <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear departamento</button>
        </form>
    </div>

    <!-- Listado -->
    <div class="lg:col-span-2 space-y-3">
        <?php if (empty($departments)): ?>
            <div class="card card-pad text-center py-16">
                <div class="w-14 h-14 rounded-2xl bg-[#eff6ff] grid place-items-center mx-auto mb-3"><i class="lucide lucide-layers text-[22px] text-[#3b82f6]"></i></div>
                <div class="font-display font-bold">Sin departamentos</div>
                <p class="text-[13px] text-ink-400 mt-1 max-w-sm mx-auto">Crea el primer departamento para empezar a enrutar tickets y organizar a tu equipo</p>
            </div>
        <?php else: ?>
            <?php foreach ($departments as $d): ?>
                <a href="<?= $url('/t/' . $slug . '/departments/' . $d['id']) ?>" class="card card-pad flex items-start gap-3 hover:shadow-md transition" style="text-decoration:none;color:inherit">
                    <div class="w-12 h-12 rounded-xl grid place-items-center shrink-0" style="background:<?= $e($d['color']) ?>15;color:<?= $e($d['color']) ?>;border:1px solid <?= $e($d['color']) ?>30">
                        <i class="lucide lucide-<?= $e($d['icon']) ?> text-[18px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="font-display font-bold text-[15px]"><?= $e($d['name']) ?></div>
                            <?php if (!(int)$d['is_active']): ?>
                                <span class="text-[10px] uppercase tracking-[0.14em] px-1.5 py-0.5 rounded-full" style="background:#f3f4f6;color:#6b7280">Inactivo</span>
                            <?php endif; ?>
                            <?php if (!empty($d['manager_name'])): ?>
                                <span class="text-[11px] text-ink-500"><i class="lucide lucide-user-check text-[11px]"></i> <?= $e($d['manager_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($d['description'])): ?>
                            <p class="text-[12.5px] text-ink-500 mt-0.5 line-clamp-2"><?= $e($d['description']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center gap-3 mt-2 text-[11.5px] text-ink-400">
                            <span><i class="lucide lucide-users text-[11px]"></i> <?= number_format((int)$d['agents_count']) ?> agentes</span>
                            <span class="text-ink-300">·</span>
                            <span><i class="lucide lucide-inbox text-[11px]"></i> <?= number_format((int)$d['tickets_count']) ?> tickets</span>
                            <?php if ((int)$d['open_count'] > 0): ?>
                                <span class="text-ink-300">·</span>
                                <span style="color:#b45309"><i class="lucide lucide-circle-dot text-[11px]"></i> <?= number_format((int)$d['open_count']) ?> abiertos</span>
                            <?php endif; ?>
                            <?php if (!empty($d['email'])): ?>
                                <span class="text-ink-300">·</span>
                                <span><i class="lucide lucide-mail text-[11px]"></i> <?= $e($d['email']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center text-ink-300">
                        <i class="lucide lucide-chevron-right text-[16px]"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
