<?php
use App\Core\Helpers;

$tierLabels = [
    'core'       => ['Núcleo',     '#64748b', '#f1f5f9'],
    'pro'        => ['Pro',        '#7c5cff', '#f3f0ff'],
    'business'   => ['Business',   '#10b981', '#ecfdf5'],
    'enterprise' => ['Enterprise', '#d946ef', '#fdf4ff'],
];
// Agrupar el catálogo por tier
$grouped = [];
foreach ($catalog as $key => [$lbl, $ic, $desc, $tier]) {
    $grouped[$tier][$key] = [$lbl, $ic, $desc];
}
$tierOrder = ['core','pro','business','enterprise'];
?>

<div class="mb-4 flex items-center gap-2 flex-wrap">
    <a href="<?= $url('/admin/tenants/' . (int)$t['id']) ?>" class="admin-btn admin-btn-soft"><i class="lucide lucide-arrow-left"></i> Volver al tenant</a>
    <span class="admin-pill admin-pill-purple"><i class="lucide lucide-package"></i> Plan actual: <?= $e($planLabel) ?></span>
    <?php if (!empty($license['plan_name'])): ?>
        <span class="admin-pill admin-pill-blue"><i class="lucide lucide-shield"></i> Suscripción: <?= $e($license['plan_name']) ?> · <?= $e($license['state']) ?></span>
    <?php endif; ?>
</div>

<div class="admin-card admin-card-pad mb-4" style="background:linear-gradient(135deg,#1a1825 0%,#2a1f3d 100%);color:white;border:none">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl grid place-items-center" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);box-shadow:0 8px 20px -8px rgba(124,92,255,.6)">
            <i class="lucide lucide-toggle-right text-[20px]"></i>
        </div>
        <div class="flex-1">
            <div class="font-display font-extrabold text-[20px] tracking-[-0.02em]">Control de módulos por empresa</div>
            <p class="text-[12.5px] mt-1" style="color:rgba(255,255,255,.7)">Heredar usa el plan actual del tenant. <strong style="color:#86efac">Activar</strong> habilita un módulo aunque el plan no lo incluya. <strong style="color:#fda4af">Desactivar</strong> oculta un módulo aunque el plan lo incluya. Los cambios son inmediatos.</p>
        </div>
    </div>
</div>

<form method="POST" action="<?= $url('/admin/tenants/' . (int)$t['id'] . '/modules') ?>" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <?php foreach ($tierOrder as $tier):
        if (empty($grouped[$tier])) continue;
        [$tlbl, $tcol, $tbg] = $tierLabels[$tier];
    ?>
        <div class="admin-card">
            <div class="admin-card-head">
                <div class="flex items-center gap-2">
                    <span class="admin-pill" style="background:<?= $tbg ?>;color:<?= $tcol ?>;border-color:<?= $tcol ?>33"><?= $tlbl ?></span>
                    <h2 class="admin-h2">Módulos del nivel <?= strtolower($tlbl) ?></h2>
                </div>
                <span class="text-[11.5px] text-ink-400"><?= count($grouped[$tier]) ?> módulos</span>
            </div>

            <div class="divide-y" style="border-color:var(--border)">
                <?php foreach ($grouped[$tier] as $feature => [$flbl, $fic, $fdesc]):
                    $inPlan = in_array($feature, $planFeatures, true);
                    $override = $overrides[$feature] ?? null;
                    $current = $override ? $override['state'] : 'inherit';
                ?>
                    <div class="p-4 flex items-start gap-4 flex-wrap" style="border-bottom:1px solid var(--border)">
                        <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:<?= $tbg ?>;color:<?= $tcol ?>"><i class="lucide lucide-<?= $fic ?> text-[16px]"></i></div>
                        <div class="flex-1 min-w-[220px]">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="font-display font-bold text-[14px]"><?= $e($flbl) ?></div>
                                <?php if ($inPlan): ?>
                                    <span class="admin-pill admin-pill-green"><i class="lucide lucide-check text-[10px]"></i> En plan</span>
                                <?php else: ?>
                                    <span class="admin-pill admin-pill-gray"><i class="lucide lucide-lock text-[10px]"></i> Fuera de plan</span>
                                <?php endif; ?>
                                <span class="admin-pill admin-pill-gray font-mono text-[10px]"><?= $e($feature) ?></span>
                            </div>
                            <p class="text-[12px] text-ink-500 mt-1"><?= $e($fdesc) ?></p>
                            <input type="text" name="reason[<?= $e($feature) ?>]" value="<?= $e($override['reason'] ?? '') ?>" placeholder="Motivo del override (opcional, para auditoría)" class="admin-input mt-2" style="height:34px; font-size:12px">
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0" role="radiogroup">
                            <label class="cursor-pointer">
                                <input type="radio" name="module[<?= $e($feature) ?>]" value="inherit" <?= $current==='inherit'?'checked':'' ?> class="sr-only">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold border transition" style="border-color:var(--border); color:var(--ink-500)">
                                    <i class="lucide lucide-link text-[12px]"></i> Heredar
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="module[<?= $e($feature) ?>]" value="on" <?= $current==='on'?'checked':'' ?> class="sr-only">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold border transition" style="border-color:#a7f3d0; background:#d1fae5; color:#047857">
                                    <i class="lucide lucide-power text-[12px]"></i> Activar
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="module[<?= $e($feature) ?>]" value="off" <?= $current==='off'?'checked':'' ?> class="sr-only">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold border transition" style="border-color:#fecaca; background:#fee2e2; color:#b91c1c">
                                    <i class="lucide lucide-power-off text-[12px]"></i> Desactivar
                                </span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center justify-between sticky bottom-4 admin-card admin-card-pad" style="z-index:10;box-shadow:0 -8px 24px -8px rgba(22,21,27,.08)">
        <div class="text-[12.5px] text-ink-500">Los cambios entran en vigor inmediatamente para los usuarios del tenant.</div>
        <div class="flex gap-2">
            <a href="<?= $url('/admin/tenants/' . (int)$t['id']) ?>" class="admin-btn admin-btn-soft">Cancelar</a>
            <button class="admin-btn admin-btn-primary"><i class="lucide lucide-save"></i> Guardar overrides</button>
        </div>
    </div>
</form>

<style>
/* Visual feedback para radios seleccionados */
input[type="radio"]:checked + span {
    box-shadow: 0 0 0 2px currentColor inset, 0 4px 10px -4px rgba(22,21,27,.15);
    transform: translateY(-1px);
}
input[type="radio"]:not(:checked) + span {
    opacity: .55;
}
input[type="radio"]:not(:checked) + span:hover {
    opacity: .85;
}
</style>
<script>
// Click handler para que el span entero active el radio
document.querySelectorAll('label > input[type="radio"]').forEach(input => {
    const span = input.parentElement.querySelector('span');
    if (span) span.addEventListener('click', () => { input.checked = true; });
});
</script>
