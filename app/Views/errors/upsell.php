<?php
$featureLabels = [
    'automations' => ['Automatizaciones','workflow','Crea reglas que ejecutan acciones solas: auto-asignar, escalar, cerrar, notificar.','#7c5cff'],
    'sla' => ['SLA + Escalamientos','gauge','Define políticas de respuesta y resolución por prioridad. Alertas antes de la brecha.','#f59e0b'],
    'audit' => ['Auditoría completa','history','Registro inmutable de cada acción del equipo: quién, qué, cuándo y desde dónde.','#16a34a'],
    'departments' => ['Departamentos','layers','Organiza tu equipo en departamentos con asignación automática de tickets, SLAs propios y reportes independientes.','#3b82f6'],
    'integrations' => ['Integraciones','plug','Conecta Kydesk con Slack, Discord, Telegram, Teams, Zapier y más. Notifica eventos automáticamente.','#0ea5e9'],
    'sso' => ['SSO + SAML','key-round','Inicio de sesión único corporativo con SAML 2.0 y aprovisionamiento SCIM.','#ec4899'],
];
[$label, $icon, $desc, $color] = $featureLabels[$feature] ?? [ucfirst($feature), 'lock', 'Disponible en planes superiores', '#7c5cff'];
$planLabel = ['starter'=>'Starter','free'=>'Starter','pro'=>'Pro','business'=>'Pro','enterprise'=>'Enterprise'][$requiredPlan] ?? 'Pro';
?>

<div class="max-w-3xl mx-auto py-8">
    <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition mb-4"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>

    <div class="relative rounded-[28px] overflow-hidden p-12 text-white" style="background:linear-gradient(135deg,#1a1825 0%,#2a1f3d 50%,#1a1825 100%)">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute" style="width:480px;height:480px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.4),transparent 65%);top:-160px;right:-100px;filter:blur(40px)"></div>
            <div class="absolute" style="width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(217,70,239,.3),transparent 65%);bottom:-160px;left:-100px;filter:blur(40px)"></div>
        </div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em]" style="background:rgba(124,92,255,.2);color:#c4b5fd;border:1px solid rgba(124,92,255,.4)">
                <i class="lucide lucide-lock text-[12px]"></i> FUNCIÓN BLOQUEADA
            </div>

            <div class="flex items-start gap-5 mt-6">
                <div class="w-20 h-20 rounded-3xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>aa);box-shadow:0 16px 40px -10px <?= $color ?>88">
                    <i class="lucide lucide-<?= $icon ?> text-[36px]"></i>
                </div>
                <div class="flex-1">
                    <h1 class="font-display font-extrabold text-[36px] tracking-[-0.025em] leading-tight"><?= $e($label) ?></h1>
                    <p class="text-[15px] mt-2.5 max-w-xl" style="color:rgba(255,255,255,.75)"><?= $e($desc) ?></p>
                </div>
            </div>

            <div class="mt-9 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php foreach ([
                    ['Tu plan actual', ucfirst((string)$currentPlan), '#fff', 'rgba(255,255,255,.06)','rgba(255,255,255,.1)'],
                    ['Disponible en', $planLabel, '#fff', 'rgba(124,92,255,.18)','rgba(124,92,255,.5)'],
                    ['Activación', 'Inmediata', '#86efac', 'rgba(34,197,94,.12)','rgba(34,197,94,.3)'],
                ] as [$l,$v,$col,$bg,$bord]): ?>
                    <div class="rounded-2xl px-4 py-3.5" style="background:<?= $bg ?>;border:1px solid <?= $bord ?>">
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em]" style="color:rgba(255,255,255,.5)"><?= $l ?></div>
                        <div class="font-display font-extrabold text-[18px] mt-1.5 tracking-[-0.02em]" style="color:<?= $col ?>"><?= $e($v) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="<?= $url('/pricing') ?>" class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-xl font-semibold text-[14px] transition" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.6)" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="lucide lucide-zap"></i> Hacer upgrade a <?= $e($planLabel) ?>
                </a>
                <a href="<?= $url('/contact') ?>" class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-xl font-semibold text-[14px]" style="background:rgba(255,255,255,.08);color:white;border:1px solid rgba(255,255,255,.12)">
                    <i class="lucide lucide-message-circle"></i> Hablar con ventas
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl p-5 flex items-start gap-4" style="background:#fafafb;border:1px solid #ececef">
        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 grid place-items-center flex-shrink-0"><i class="lucide lucide-info text-[16px]"></i></div>
        <div class="text-[13px] text-ink-500 leading-relaxed">Estás en un workspace <strong class="text-ink-900"><?= $e(ucfirst((string)$currentPlan)) ?></strong>. Esta función está incluida en planes <strong class="text-ink-900"><?= $e($planLabel) ?></strong> en adelante. Si estás probando un demo, podés cerrar sesión y abrir un demo del plan <?= $e($planLabel) ?> para verlo en acción.</div>
    </div>
</div>
