<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<section class="pt-32 pb-20">
    <div class="max-w-[1240px] mx-auto px-6 text-center">
        <div class="text-[11.5px] font-bold uppercase tracking-[0.14em] text-brand-600 mb-3">PRODUCTO</div>
        <h1 class="heading-xl" style="text-wrap:balance">Hecho para equipos<br>que <span class="text-gradient-purple">resuelven en serio</span>.</h1>
    </div>

    <div class="max-w-[1100px] mx-auto px-6 mt-20 flex flex-col gap-20">
        <?php
        $blocks = [
            ['Bandeja multi-canal','Portal, email, teléfono, chat e interno en una vista.','inbox','#dbeafe','#1d4ed8',['Filtros rápidos','Atajos de teclado','Vista densa']],
            ['Tablero Kanban','Drag & drop entre estados.','kanban-square','#f3e8ff','#7e22ce',['Columnas por estado','Drag & drop','Contador por columna']],
            ['Escalamientos','N1 → N4 con razón y registro.','trending-up','#fee2e2','#b91c1c',['Niveles configurables','Comentarios automáticos','Reasignación']],
            ['SLA','Tiempos de respuesta y resolución.','gauge','#fef3c7','#b45309',['Reloj SLA visible','Alertas proactivas','Reportes']],
            ['Automatizaciones','Reglas con triggers.','workflow','#f3f0ff','#5a3aff',['Auto-asignación','Cierre automático','VIP']],
            ['Conocimiento','Artículos públicos e internos.','book-open','#d1fae5','#047857',['Categorías','Sugeridos en tickets','Markdown']],
        ];
        foreach ($blocks as $i => [$t,$d,$ic,$bg,$col,$bs]): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-5 <?= $i%2?'lg:order-2':'' ?>">
                    <div class="w-13 h-13 rounded-2xl grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>;width:52px;height:52px;">
                        <i class="lucide lucide-<?= $ic ?> text-[22px]"></i>
                    </div>
                    <h2 class="heading-md mt-4"><?= $t ?></h2>
                    <p class="mt-3 text-[15px] leading-relaxed text-ink-500"><?= $d ?></p>
                    <ul class="mt-6 space-y-2.5 text-[14px]">
                        <?php foreach ($bs as $b): ?><li class="flex items-start gap-2"><i class="lucide lucide-check text-emerald-600 mt-0.5"></i> <?= $b ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <div class="lg:col-span-7 <?= $i%2?'lg:order-1':'' ?>">
                    <div class="card grid place-items-center" style="aspect-ratio:16/10;background:linear-gradient(135deg,<?= $bg ?>,white);">
                        <i class="lucide lucide-<?= $ic ?> text-[120px] opacity-60" style="color:<?= $col ?>"></i>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
