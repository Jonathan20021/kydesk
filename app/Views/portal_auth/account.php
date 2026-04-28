<?php
$prMap = ['urgent'=>['#dc2626','Urgente'],'high'=>['#f59e0b','Alta'],'medium'=>['#3b82f6','Media'],'low'=>['#6b7280','Baja']];
$stMap = [
    'open' => ['#3b82f6','Abierto','circle'],
    'in_progress' => ['#f59e0b','En progreso','play-circle'],
    'on_hold' => ['#6b7280','En espera','pause-circle'],
    'resolved' => ['#16a34a','Resuelto','check-circle'],
    'closed' => ['#0f172a','Cerrado','x-circle'],
];
?>

<div class="min-h-screen" style="background:#fafafb">
    <nav class="bg-white border-b border-[#ececef]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[14px]"><?= $e(strtoupper(substr($tenantPublic->name,0,1))) ?></div>
                <span class="font-display font-bold text-[15px]"><?= $e($tenantPublic->name) ?></span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= $url('/portal/' . $tenantPublic->slug . '/new') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo ticket</a>
                <form method="POST" action="<?= $url('/portal/' . $tenantPublic->slug . '/logout') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-soft btn-sm" data-tooltip="Salir"><i class="lucide lucide-log-out text-[13px]"></i></button>
                </form>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl text-white grid place-items-center font-display font-bold text-[18px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)"><?= $e(strtoupper(substr($portalUser['name'],0,1))) ?></div>
            <div>
                <div class="font-display font-extrabold text-[22px] tracking-[-0.02em]">Hola, <?= $e(explode(' ', $portalUser['name'])[0]) ?></div>
                <p class="text-[13px] text-ink-500"><?= $e($portalUser['email']) ?></p>
            </div>
        </div>

        <?php if (!empty($portalUser['company_id'])): ?>
            <a href="<?= $url('/portal/' . $tenantPublic->slug . '/company') ?>" class="card card-pad mb-6 flex items-center justify-between gap-3 hover:shadow-md transition" style="text-decoration:none;color:inherit;background:linear-gradient(135deg,#f3f0ff,#fff)">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-brand-500 text-white grid place-items-center shrink-0"><i class="lucide lucide-building-2 text-[18px]"></i></div>
                    <div class="min-w-0">
                        <div class="font-display font-bold text-[14px]"><?= !empty($portalUser['is_company_manager']) ? 'Portal de tu empresa · Manager' : 'Portal de tu empresa' ?></div>
                        <div class="text-[12px] text-ink-500 truncate">Accedé al dashboard, tickets y reportes de tu empresa.</div>
                    </div>
                </div>
                <span class="btn btn-primary btn-sm">Ir al portal <i class="lucide lucide-arrow-right text-[13px]"></i></span>
            </a>
        <?php endif; ?>

        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="card card-pad text-center"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Total</div><div class="font-display font-extrabold text-[28px]"><?= $stats['total'] ?></div></div>
            <div class="card card-pad text-center"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Abiertos</div><div class="font-display font-extrabold text-[28px] text-amber-600"><?= $stats['open'] ?></div></div>
            <div class="card card-pad text-center"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Resueltos</div><div class="font-display font-extrabold text-[28px] text-emerald-600"><?= $stats['resolved'] ?></div></div>
        </div>

        <h2 class="font-display font-bold text-[16px] mb-3">Mis tickets</h2>
        <?php if (empty($tickets)): ?>
            <div class="card card-pad text-center py-12">
                <i class="lucide lucide-inbox text-[24px] text-ink-300"></i>
                <h3 class="font-display font-bold mt-3">Sin tickets aún</h3>
                <a href="<?= $url('/portal/' . $tenantPublic->slug . '/new') ?>" class="btn btn-primary btn-sm mt-3 inline-flex"><i class="lucide lucide-plus"></i> Crear el primero</a>
            </div>
        <?php else: ?>
            <div class="card overflow-hidden">
                <?php foreach ($tickets as $idx => $t):
                    [$prCol, $prLbl] = $prMap[$t['priority']] ?? ['#6b7280', $t['priority']];
                    [$stCol, $stLbl, $stIc] = $stMap[$t['status']] ?? ['#6b7280', $t['status'], 'circle'];
                ?>
                    <div class="flex items-center gap-3 p-4 hover:bg-[#fafafb] transition <?= $idx > 0 ? 'border-t' : '' ?>" style="border-color:var(--border)">
                        <div class="w-9 h-9 rounded-xl grid place-items-center" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><i class="lucide lucide-<?= $stIc ?> text-[14px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-[11.5px] text-ink-500"><?= $e($t['code']) ?></span>
                                <div class="font-display font-bold text-[13.5px] truncate"><?= $e($t['subject']) ?></div>
                            </div>
                            <div class="flex items-center gap-2 text-[11.5px] text-ink-400 mt-0.5">
                                <span><?= $e($t['created_at']) ?></span>
                                <span class="badge" style="background:<?= $prCol ?>15;color:<?= $prCol ?>"><?= $prLbl ?></span>
                                <span class="badge" style="background:<?= $stCol ?>15;color:<?= $stCol ?>"><?= $stLbl ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Profile -->
        <div class="card card-pad mt-6">
            <h2 class="font-display font-bold text-[16px] mb-3">Mi perfil</h2>
            <form method="POST" action="<?= $url('/portal/' . $tenantPublic->slug . '/account/profile') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre</label><input name="name" value="<?= $e($portalUser['name']) ?>" class="input"></div>
                <div><label class="label">Teléfono</label><input name="phone" value="<?= $e($portalUser['phone']) ?>" class="input"></div>
                <div class="md:col-span-2"><label class="label">Cambiar contraseña (opcional, mín 6)</label><input name="new_password" type="password" minlength="6" class="input"></div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="btn btn-primary btn-sm">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
