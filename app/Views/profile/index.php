<?php use App\Core\Helpers; $slug = $tenant->slug; $u = $auth->user(); ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Mi perfil</h1>
        <p class="text-[13px] text-ink-400">Administra tu información personal y de seguridad</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="badge badge-emerald inline-flex"><span class="pulse"></span> Cuenta activa</span>
    </div>
</div>

<div class="profile-card max-w-4xl">
    <div class="cover-banner"></div>
    <div class="profile-card-body">
        <div class="avatar avatar-xl" style="background:<?= Helpers::colorFor($u['email']) ?>;color:white;border-radius:24px"><?= Helpers::initials($u['name']) ?></div>
        <div class="min-w-0 pb-2 flex-1">
            <h2 class="font-display font-extrabold text-[22px] tracking-[-0.022em] leading-tight"><?= $e($u['name']) ?></h2>
            <div class="text-[13px] text-ink-400 mt-0.5"><?= $e($u['email']) ?></div>
            <div class="mt-2.5 flex items-center gap-1.5 flex-wrap">
                <span class="badge badge-purple"><i class="lucide lucide-shield text-[11px]"></i> <?= $e($u['role_name'] ?? 'Miembro') ?></span>
                <?php if ($u['is_technician']): ?><span class="badge badge-gray"><i class="lucide lucide-wrench text-[11px]"></i> Técnico</span><?php endif; ?>
                <?php if (!empty($u['title'])): ?><span class="badge badge-blue"><i class="lucide lucide-briefcase text-[11px]"></i> <?= $e($u['title']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/profile') ?>" class="card card-pad max-w-4xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="section-head">
        <div class="section-head-icon"><i class="lucide lucide-user text-[16px]"></i></div>
        <div>
            <h3 class="section-title">Información personal</h3>
            <div class="section-head-meta">Datos visibles para tu equipo</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="label">Nombre</label><input name="name" value="<?= $e($u['name']) ?>" class="input"></div>
        <div><label class="label">Email</label><input value="<?= $e($u['email']) ?>" disabled class="input"></div>
        <div><label class="label">Cargo</label><input name="title" value="<?= $e($u['title']) ?>" placeholder="Ej. Soporte N2" class="input"></div>
        <div><label class="label">Teléfono</label><input name="phone" value="<?= $e($u['phone']) ?>" placeholder="+502 0000 0000" class="input"></div>
    </div>

    <div class="section-head mt-8">
        <div class="section-head-icon"><i class="lucide lucide-lock text-[16px]"></i></div>
        <div>
            <h3 class="section-title">Seguridad</h3>
            <div class="section-head-meta">Mantén tu cuenta segura con una contraseña fuerte</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="label">Cambiar contraseña</label>
            <input name="password" type="password" minlength="6" placeholder="Vacío para no cambiar" class="input">
            <p class="text-[11.5px] text-ink-400 mt-1.5">Mínimo 6 caracteres</p>
        </div>
        <div class="hidden md:flex items-end">
            <div class="w-full p-4 rounded-2xl border border-[#ececef] bg-[#fafafb] flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 grid place-items-center flex-shrink-0"><i class="lucide lucide-shield-check text-[16px]"></i></div>
                <div class="text-[12px] text-ink-500"><span class="font-display font-bold text-ink-900 block mb-0.5 text-[13px]">Cuenta protegida</span>Tu última actualización fue exitosa.</div>
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-6 mt-6 gap-2 border-t border-[#ececef]">
        <a href="<?= $url('/t/' . $slug . '/dashboard') ?>" class="btn btn-outline btn-sm">Cancelar</a>
        <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar cambios</button>
    </div>
</form>
