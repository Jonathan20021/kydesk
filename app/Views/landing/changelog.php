<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<!-- HERO -->
<section class="relative pt-36 pb-12 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grid-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex justify-center">
                <div class="aura-pill">
                    <span class="aura-pill-tag"><i class="lucide lucide-sparkles"></i> CHANGELOG</span>
                    <span class="text-ink-700 font-medium">Lo nuevo, semana a semana</span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.4rem,5vw + 1rem,4.5rem)">Mejoramos <span class="gradient-shift">cada semana</span>.</h1>
            <p class="mt-7 text-[16px] text-ink-500 max-w-xl mx-auto leading-relaxed">Cada cambio que hacemos en el producto. Features, fixes y mejoras. Sin asteriscos.</p>
        </div>
    </div>
</section>

<!-- TIMELINE -->
<section class="pb-24">
    <div class="max-w-[850px] mx-auto px-6">
        <?php
        $releases = [
            [date('Y-m-d'), 'v3.0.0', 'major', 'Panel Super Admin para SaaS', [
                ['feature','Panel completo de gestión SaaS: tenants, planes, suscripciones, facturas, pagos, usuarios cross-tenant.'],
                ['feature','Sistema de roles para super admins: owner, admin, support, billing.'],
                ['feature','Auditoría completa de acciones del super admin con IP y user-agent.'],
                ['feature','Reportes SaaS con MRR, ARR, ARPU, churn rate y top 10 tenants por revenue.'],
                ['feature','Impersonación: super admins pueden acceder al workspace de cualquier tenant como owner.'],
            ]],
            [date('Y-m-d', strtotime('-7 days')), 'v2.4.1', 'patch', 'Mejoras de UI y performance', [
                ['fix','Layout del dashboard ya no se estira con tablas grandes.'],
                ['fix','Charts de Chart.js con altura controlada — sin loops infinitos de redraw.'],
                ['improvement','Sidebar admin con flex layout estable en pantallas chicas.'],
            ]],
            [date('Y-m-d', strtotime('-14 days')), 'v2.4.0', 'minor', 'Planes dinámicos en landing', [
                ['feature','La página /pricing y el home pricing ahora leen planes desde la BD.'],
                ['feature','Cualquier cambio del super admin se refleja al instante en la landing.'],
                ['feature','Toggle Mensual/Anual con switch dinámico de precios.'],
            ]],
            [date('Y-m-d', strtotime('-21 days')), 'v2.3.0', 'minor', 'Multi-tenant + branding', [
                ['feature','Cada tenant puede tener color primario, logo y dominio custom.'],
                ['feature','Aislamiento de datos a nivel foreign key con tenant_id en cada tabla.'],
                ['improvement','Selector de workspace con switching rápido sin re-login.'],
            ]],
            [date('Y-m-d', strtotime('-30 days')), 'v2.2.0', 'minor', 'Automatizaciones IA', [
                ['feature','Reglas IFTTT con disparadores: ticket creado, comentado, escalado, SLA risk, resuelto.'],
                ['feature','Acciones encadenadas: asignar, etiquetar, notificar, cambiar estado, webhook HTTP.'],
                ['feature','Sugerencias automáticas de reglas según patrones del equipo.'],
            ]],
            [date('Y-m-d', strtotime('-45 days')), 'v2.1.0', 'minor', 'Reloj SLA en vivo', [
                ['feature','Cada ticket arranca un cronómetro visible con color por urgencia.'],
                ['feature','Alertas pre-brecha al 80% del tiempo restante.'],
                ['feature','Escalación automática al llegar al 100%.'],
                ['feature','Pausa de SLA cuando esperás respuesta del cliente.'],
            ]],
            [date('Y-m-d', strtotime('-60 days')), 'v2.0.0', 'major', 'Tablero Kanban + Macros', [
                ['feature','Tablero kanban drag & drop entre columnas con audit log.'],
                ['feature','Macros (plantillas de respuesta) con shortcuts y categorías.'],
                ['feature','Atajos de teclado en todas partes: ⌘K, J/K, A, R, E.'],
            ]],
            [date('Y-m-d', strtotime('-90 days')), 'v1.0.0', 'major', 'Lanzamiento público', [
                ['feature','Bandeja unificada de tickets, multi-canal.'],
                ['feature','Roles granulares: 30+ permisos por módulo.'],
                ['feature','Base de conocimiento con editor markdown y portal público.'],
            ]],
        ];

        $typeColors = [
            'feature' => ['#7c5cff','#f3f0ff','feature'],
            'fix' => ['#f59e0b','#fef3c7','fix'],
            'improvement' => ['#22c55e','#dcfce7','mejora'],
        ];
        $tagColors = [
            'major' => ['#d946ef','#fae8ff','MAJOR'],
            'minor' => ['#7c5cff','#f3f0ff','MINOR'],
            'patch' => ['#22c55e','#dcfce7','PATCH'],
        ];
        foreach ($releases as $i => [$date, $version, $tag, $title, $changes]):
            [$tCol, $tBg, $tLbl] = $tagColors[$tag];
        ?>
            <div class="relative pl-10 <?= $i < count($releases)-1 ? 'pb-10' : '' ?>">
                <?php if ($i < count($releases)-1): ?>
                    <div class="absolute left-3 top-3 bottom-0 w-px" style="background:linear-gradient(180deg,#cdbfff,#ececef)"></div>
                <?php endif; ?>
                <div class="absolute left-0 top-1 w-6 h-6 rounded-full grid place-items-center" style="background:<?= $tCol ?>;box-shadow:0 0 0 4px <?= $tBg ?>"><i class="lucide lucide-sparkles text-[12px] text-white"></i></div>

                <div class="rounded-2xl p-7 bg-white border border-[#ececef]">
                    <div class="flex items-center gap-3 mb-3 flex-wrap">
                        <span class="font-mono text-[12px] font-bold"><?= $e($version) ?></span>
                        <span class="text-[10.5px] font-bold uppercase tracking-[0.16em] px-2 py-0.5 rounded-full" style="background:<?= $tBg ?>;color:<?= $tCol ?>"><?= $tLbl ?></span>
                        <span class="text-[12px] text-ink-400"><?= $e($date) ?></span>
                    </div>
                    <h2 class="font-display font-extrabold text-[22px] tracking-[-0.02em]"><?= $e($title) ?></h2>
                    <ul class="mt-5 space-y-3">
                        <?php foreach ($changes as [$type, $text]):
                            [$cCol, $cBg, $cLbl] = $typeColors[$type];
                        ?>
                            <li class="flex items-start gap-3">
                                <span class="text-[10px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full flex-shrink-0 mt-0.5" style="background:<?= $cBg ?>;color:<?= $cCol ?>"><?= $e($cLbl) ?></span>
                                <span class="text-[13.5px] text-ink-700 leading-relaxed"><?= $e($text) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
