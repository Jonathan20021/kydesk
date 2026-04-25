<?php $slug = $tenant->slug; ?>

<div x-data="{showForm:false}" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Roles y permisos</h1>
            <p class="text-[13px] text-ink-400">Define qué puede hacer cada rol en tu workspace</p>
        </div>
        <?php if ($auth->can('roles.create')): ?>
            <button @click="showForm=!showForm" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo rol</button>
        <?php endif; ?>
    </div>

    <form x-show="showForm" x-cloak x-transition method="POST" action="<?= $url('/t/' . $slug . '/roles') ?>" class="card card-pad">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div class="section-head">
            <div class="section-head-icon"><i class="lucide lucide-shield-plus text-[16px]"></i></div>
            <div>
                <h3 class="section-title">Crear nuevo rol</h3>
                <div class="section-head-meta">Configura permisos en el siguiente paso</div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div><label class="label">Nombre</label><input name="name" required placeholder="Ej. Supervisor" class="input"></div>
            <div><label class="label">Descripción</label><input name="description" placeholder="Breve resumen del rol" class="input"></div>
        </div>
        <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-[#ececef]">
            <button type="button" @click="showForm=false" class="btn btn-outline btn-sm">Cancelar</button>
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-check"></i> Crear rol</button>
        </div>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($roles as $r):
            $accentClass = $r['slug']==='owner' ? 'owner' : ($r['is_system'] ? 'system' : '');
            $iconBg = $r['slug']==='owner' ? '#fee2e2' : ($r['is_system'] ? '#fef3c7' : 'var(--brand-50)');
            $iconColor = $r['slug']==='owner' ? '#b91c1c' : ($r['is_system'] ? '#b45309' : 'var(--brand-700)');
            $iconName = $r['slug']==='owner' ? 'crown' : ($r['is_system'] ? 'shield-check' : 'shield');
        ?>
            <div class="role-card">
                <div class="role-card-accent <?= $accentClass ?>"></div>
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl grid place-items-center" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>"><i class="lucide lucide-<?= $iconName ?> text-base"></i></div>
                    <?php if ($r['slug']==='owner'): ?>
                        <span class="badge badge-rose">Acceso total</span>
                    <?php elseif ($r['is_system']): ?>
                        <span class="badge badge-amber">Sistema</span>
                    <?php else: ?>
                        <span class="badge badge-purple">Personalizado</span>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <div class="font-display font-bold text-[16px] tracking-[-0.015em]"><?= $e($r['name']) ?></div>
                    <div class="text-[12.5px] mt-1 line-clamp-2 text-ink-400 min-h-[34px]"><?= $e($r['description'] ?? 'Sin descripción') ?></div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-[#fafafb] p-2.5 border border-[#ececef]">
                        <div class="text-[10.5px] text-ink-400 font-bold uppercase tracking-wider">Usuarios</div>
                        <div class="font-display font-bold text-[16px] mt-0.5 flex items-center gap-1.5"><i class="lucide lucide-users text-[13px] text-ink-400"></i><?= (int)$r['users_count'] ?></div>
                    </div>
                    <div class="rounded-xl bg-[#fafafb] p-2.5 border border-[#ececef]">
                        <div class="text-[10.5px] text-ink-400 font-bold uppercase tracking-wider">Permisos</div>
                        <div class="font-display font-bold text-[16px] mt-0.5 flex items-center gap-1.5"><i class="lucide lucide-key text-[13px] text-ink-400"></i><?= $r['slug']==='owner'?'Todos':(int)$r['perm_count'] ?></div>
                    </div>
                </div>
                <div class="mt-4 pt-4 flex justify-end gap-1.5 border-t border-[#ececef]">
                    <?php if ($auth->can('roles.edit')): ?>
                        <a href="<?= $url('/t/' . $slug . '/roles/' . $r['id']) ?>" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i> Editar</a>
                    <?php endif; ?>
                    <?php if ($auth->can('roles.delete') && !$r['is_system']): ?>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/roles/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar este rol?')">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="btn btn-outline btn-xs" style="color:#ef4444;border-color:#fecaca"><i class="lucide lucide-trash-2 text-[12px]"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
