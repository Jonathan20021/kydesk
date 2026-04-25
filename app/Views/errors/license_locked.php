<?php
$lic = $license;
$state = $lic['state'];
$isTrialExpired = $lic['is_trial'] || in_array($state, ['expired'], true);
$badge = match (true) {
    $state === 'expired' && $lic['is_trial']      => ['Período de prueba expirado', 'amber'],
    $state === 'expired'                          => ['Suscripción expirada', 'amber'],
    $state === 'cancelled'                        => ['Suscripción cancelada', 'red'],
    $state === 'suspended'                        => ['Cuenta suspendida', 'red'],
    $state === 'past_due'                         => ['Pago pendiente', 'amber'],
    $state === 'none'                             => ['Sin licencia', 'red'],
    default                                       => ['Licencia inactiva', 'red'],
};
$badgeColors = [
    'amber' => 'background:rgba(245,158,11,.16);color:#fbbf24;border:1px solid rgba(245,158,11,.4)',
    'red'   => 'background:rgba(239,68,68,.16);color:#fca5a5;border:1px solid rgba(239,68,68,.4)',
];
?>
<div class="min-h-screen relative overflow-hidden grid place-items-center px-4 py-10" style="background:#0c0a1a; color:white;">

    <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute" style="width:720px;height:720px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.42),transparent 70%);top:-220px;left:-160px;filter:blur(90px);"></div>
        <div class="absolute" style="width:620px;height:620px;border-radius:50%;background:radial-gradient(circle,rgba(217,70,239,.22),transparent 70%);bottom:-220px;right:-120px;filter:blur(90px);"></div>
        <div class="absolute inset-0 opacity-50" style="background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="w-full max-w-xl">
        <div class="text-center mb-7">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-5" style="background:linear-gradient(135deg,#7c5cff 0%,#a78bfa 60%,#d946ef 100%);box-shadow:0 18px 40px -10px rgba(124,92,255,.55)">
                <i class="lucide lucide-lock text-[26px]"></i>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-[0.2em]" style="<?= $badgeColors[$badge[1]] ?>">
                <i class="lucide lucide-alert-circle text-[10px]"></i> <?= $e($badge[0]) ?>
            </div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] leading-tight mt-4"><?= $e($tenant->name) ?></h1>
            <p class="text-[14px] mt-2" style="color:rgba(255,255,255,.65)"><?= $e($lic['message']) ?></p>
        </div>

        <div class="rounded-3xl p-7 relative" style="background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);box-shadow:0 30px 60px -20px rgba(0,0,0,.5)">

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="rounded-xl p-4" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.16em]" style="color:rgba(255,255,255,.45)">Plan</div>
                    <div class="font-display font-bold text-[16px] mt-1"><?= $e($lic['plan_name']) ?></div>
                </div>
                <div class="rounded-xl p-4" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.16em]" style="color:rgba(255,255,255,.45)">Estado</div>
                    <div class="font-display font-bold text-[16px] mt-1"><?= $e(ucfirst($state)) ?></div>
                </div>
                <?php if ($lic['trial_ends_at']): ?>
                <div class="col-span-2 rounded-xl p-4" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.16em]" style="color:rgba(255,255,255,.45)">Prueba terminó</div>
                    <div class="font-mono text-[13px] mt-1" style="color:#fde68a"><?= $e($lic['trial_ends_at']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="rounded-xl p-4 mb-5" style="background:rgba(124,92,255,.08);border:1px solid rgba(124,92,255,.25)">
                <div class="flex items-start gap-3">
                    <i class="lucide lucide-shield text-[18px] mt-0.5" style="color:#a78bfa"></i>
                    <div class="text-[13px] leading-relaxed" style="color:rgba(255,255,255,.78)">
                        Tu organización quedó pausada hasta que el equipo de <strong><?= $e($saasName) ?></strong> active tu licencia.
                        Escríbenos para coordinar la activación o renovación.
                    </div>
                </div>
            </div>

            <div class="space-y-2.5">
                <a href="mailto:<?= $e($billingEmail) ?>?subject=<?= rawurlencode('Activación de licencia · ' . $tenant->name) ?>" class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-xl font-semibold text-[14px] transition" style="background:linear-gradient(135deg,#7c5cff,#6c47ff);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.55)">
                    <i class="lucide lucide-mail"></i> Contactar al equipo de licencias
                </a>
                <a href="<?= $url('/pricing') ?>" target="_blank" class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-xl font-semibold text-[13.5px] transition" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:white">
                    <i class="lucide lucide-tag text-[14px]"></i> Ver planes y precios
                </a>
            </div>

            <div class="mt-5 pt-5 border-t" style="border-color:rgba(255,255,255,.08)">
                <div class="text-[12px] grid grid-cols-1 sm:grid-cols-2 gap-3" style="color:rgba(255,255,255,.55)">
                    <div>
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] mb-1" style="color:rgba(255,255,255,.4)">Soporte</div>
                        <a href="mailto:<?= $e($supportEmail) ?>" style="color:#c4b5fd"><?= $e($supportEmail) ?></a>
                    </div>
                    <div>
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] mb-1" style="color:rgba(255,255,255,.4)">Facturación</div>
                        <a href="mailto:<?= $e($billingEmail) ?>" style="color:#c4b5fd"><?= $e($billingEmail) ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-6 flex items-center justify-center gap-4">
            <form method="POST" action="<?= $url('/auth/logout') ?>">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="text-[12.5px] inline-flex items-center gap-1.5 transition" style="color:rgba(255,255,255,.5)">
                    <i class="lucide lucide-log-out text-[12px]"></i> Cerrar sesión
                </button>
            </form>
            <a href="<?= $url('/') ?>" class="text-[12.5px] inline-flex items-center gap-1.5 transition" style="color:rgba(255,255,255,.5)">
                <i class="lucide lucide-arrow-left text-[12px]"></i> Volver al sitio
            </a>
        </div>
    </div>

</div>
