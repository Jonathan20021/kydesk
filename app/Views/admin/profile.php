<?php use App\Core\Helpers; $a = $superAdmin; ?>

<form method="POST" action="<?= $url('/admin/profile') ?>" class="max-w-xl">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="admin-card admin-card-pad mb-4">
        <div class="flex items-center gap-4 mb-5">
            <div style="width:60px;height:60px;border-radius:14px;background:<?= Helpers::colorFor($a['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:20px"><?= Helpers::initials($a['name']) ?></div>
            <div>
                <div style="font-family:'Plus Jakarta Sans';font-weight:800;font-size:20px"><?= $e($a['name']) ?></div>
                <div class="text-[12px] text-ink-500"><?= $e($a['email']) ?> · <span class="admin-pill admin-pill-purple"><?= $e($a['role']) ?></span></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="admin-label">Nombre</label><input name="name" value="<?= $e($a['name']) ?>" class="admin-input"></div>
            <div><label class="admin-label">Email</label><input name="email" type="email" value="<?= $e($a['email']) ?>" class="admin-input"></div>
            <div class="md:col-span-2"><label class="admin-label">Teléfono</label><input name="phone" value="<?= $e($a['phone']) ?>" class="admin-input"></div>
        </div>
    </div>

    <div class="admin-card admin-card-pad mb-4">
        <h2 class="admin-h2 mb-4">Cambiar contraseña</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="admin-label">Contraseña actual</label><input name="current_password" type="password" class="admin-input" placeholder="Solo si cambias la contraseña"></div>
            <div><label class="admin-label">Nueva contraseña</label><input name="new_password" type="password" class="admin-input" placeholder="Mín. 8 caracteres"></div>
        </div>
    </div>

    <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar perfil</button>
</form>
