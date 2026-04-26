<?php $slug = $tenant->slug; ?>

<div>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Centro de ayuda</h1>
    <p class="text-[13px] text-ink-400">Aprende a usar Kydesk Helpdesk al máximo</p>
</div>

<!-- Hero search -->
<div class="card card-pad text-center" style="background:linear-gradient(135deg,#f3f0ff 0%,#fff 60%);border:1px solid #cdbfff80">
    <div class="w-14 h-14 rounded-2xl bg-brand-500 text-white grid place-items-center mx-auto" style="box-shadow:0 16px 32px -8px rgba(124,92,255,.4)"><i class="lucide lucide-life-buoy text-[22px]"></i></div>
    <h2 class="font-display font-extrabold text-[22px] mt-4 tracking-[-0.02em]">¿Cómo podemos ayudarte?</h2>
    <p class="text-[13px] text-ink-500 mt-1.5 max-w-md mx-auto">Encuentra guías, tutoriales y respuestas a las preguntas más frecuentes.</p>
    <div class="mt-5 max-w-lg mx-auto">
        <div class="flex items-center gap-2 bg-white rounded-2xl border border-[#ececef] px-4 py-3 shadow-sm">
            <i class="lucide lucide-search text-ink-400"></i>
            <input id="help-search" placeholder="Buscar en la ayuda..." class="flex-1 bg-transparent outline-none text-[14px]">
        </div>
    </div>
</div>

<!-- Quick actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="<?= $url('/t/' . $slug . '/api-docs') ?>" class="card card-pad block hover:shadow-lg transition group">
        <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-code-2 text-[20px]"></i></div>
        <h3 class="font-display font-bold text-[15px] mt-4">Documentación API</h3>
        <p class="text-[12.5px] text-ink-400 mt-1">Conecta tus sistemas con la API REST.</p>
        <div class="text-[12px] font-semibold text-brand-700 mt-3 flex items-center gap-1">Ver docs <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-0.5 transition"></i></div>
    </a>
    <a href="<?= $url('/t/' . $slug . '/support') ?>" class="card card-pad block hover:shadow-lg transition group">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 grid place-items-center"><i class="lucide lucide-message-circle text-[20px]"></i></div>
        <h3 class="font-display font-bold text-[15px] mt-4">Contactar soporte</h3>
        <p class="text-[12.5px] text-ink-400 mt-1">Comunicación directa con el equipo Kydesk.</p>
        <div class="text-[12px] font-semibold text-emerald-700 mt-3 flex items-center gap-1">Abrir ticket <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-0.5 transition"></i></div>
    </a>
    <a href="<?= $url('/t/' . $slug . '/kb') ?>" class="card card-pad block hover:shadow-lg transition group">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 grid place-items-center"><i class="lucide lucide-book-open text-[20px]"></i></div>
        <h3 class="font-display font-bold text-[15px] mt-4">Base de conocimiento</h3>
        <p class="text-[12.5px] text-ink-400 mt-1">Artículos creados por tu equipo para tus clientes.</p>
        <div class="text-[12px] font-semibold text-amber-700 mt-3 flex items-center gap-1">Ver artículos <i class="lucide lucide-arrow-right text-[12px] group-hover:translate-x-0.5 transition"></i></div>
    </a>
</div>

<!-- Topics -->
<div>
    <h2 class="font-display font-extrabold text-[18px] tracking-[-0.02em]">Temas populares</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-3" id="help-topics">
        <?php
        $topics = [
            ['Crear y gestionar tickets',   'inbox',          [['¿Cómo creo mi primer ticket?','Ve a Tickets → Nuevo ticket o desde el portal público.'],['¿Puedo asignar tickets a un técnico?','Sí, desde la vista de detalle del ticket o con automatizaciones.'],['¿Cómo escalar un ticket?','Click en "Escalar" en el ticket; se crea un registro en el historial.']]],
            ['Plantillas (macros)',         'zap',            [['¿Qué son las macros?','Respuestas predefinidas que tus técnicos insertan con 1 click.'],['¿Cómo creo una macro?','Menú Plantillas → Nueva. Usa variables como {{ticket.code}}.']]],
            ['SLA y prioridades',           'gauge',          [['¿Cómo se calcula el SLA?','Cada política asigna minutos por prioridad. El sistema marca los tickets en riesgo o brechados.'],['¿Puedo personalizar los tiempos?','Sí, en SLA puedes editar respuesta y resolución por nivel.']]],
            ['Automatizaciones',            'workflow',       [['¿Qué dispara una automatización?','Eventos como ticket creado, actualizado, escalado o SLA en riesgo.'],['¿Puedo combinar condiciones?','Sí: prioridad + categoría + palabra clave.']]],
            ['Categorías y empresas',       'tags',           [['¿Cómo organizo tickets?','Por categoría (tipo de incidencia) y por empresa cliente.'],['¿Categorías personalizadas?','Sí, en el menú Categorías puedes crear con color e icono propios.']]],
            ['Portal público de soporte',   'globe',          [['¿Mis clientes necesitan cuenta?','No. Comparte /portal/{slug} y pueden crear tickets directamente.'],['¿Reciben actualizaciones?','Sí, por email cuando un agente responde.']]],
            ['Base de conocimiento',        'book-open',      [['¿Quién ve los artículos?','Si son públicos, aparecen en /portal/{slug}/kb.'],['¿Markdown soportado?','Sí, los artículos aceptan formato enriquecido.']]],
            ['Activos (CMDB)',              'server',         [['¿Para qué sirve?','Inventariar equipos, software o licencias y vincularlos a tickets.']]],
            ['API y integraciones',         'code-2',         [['¿Cómo obtengo un token?','En Documentación API puedes generar y revocar tokens.'],['¿Hay rate limiting?','Sí, 60 req/minuto por defecto.']]],
        ];
        foreach ($topics as [$title, $icon, $faqs]): ?>
            <details class="card card-pad group" data-topic="<?= htmlspecialchars(strtolower($title)) ?>">
                <summary class="cursor-pointer flex items-center gap-3 list-none">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-<?= $icon ?> text-[16px]"></i></div>
                    <div class="flex-1">
                        <div class="font-display font-bold text-[14px]"><?= $e($title) ?></div>
                        <div class="text-[11.5px] text-ink-400"><?= count($faqs) ?> preguntas</div>
                    </div>
                    <i class="lucide lucide-chevron-down text-ink-400 group-open:rotate-180 transition"></i>
                </summary>
                <div class="mt-3 space-y-3 border-t border-[#ececef] pt-3">
                    <?php foreach ($faqs as [$q, $a]): ?>
                        <div>
                            <div class="font-semibold text-[13px] text-ink-900"><?= $e($q) ?></div>
                            <div class="text-[12.5px] text-ink-500 mt-1 leading-relaxed"><?= $e($a) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</div>

<!-- CTA -->
<div class="card card-pad text-center" style="background:linear-gradient(135deg,#16151b 0%,#2a2a33 100%);color:white;border:0">
    <i class="lucide lucide-headphones text-[28px] text-brand-300"></i>
    <h3 class="font-display font-extrabold text-[18px] mt-2">¿No encontraste lo que buscabas?</h3>
    <p class="text-[13px] opacity-80 mt-1.5 max-w-md mx-auto">Nuestro equipo responde en menos de 24h hábiles.</p>
    <a href="<?= $url('/t/' . $slug . '/support') ?>" class="inline-flex items-center gap-1.5 mt-4 bg-white text-ink-900 font-semibold text-[12.5px] px-4 py-2 rounded-full">
        <i class="lucide lucide-message-circle text-[14px]"></i> Contactar soporte
    </a>
</div>

<script>
(function(){
    const inp = document.getElementById('help-search');
    if (!inp) return;
    inp.addEventListener('input', () => {
        const q = inp.value.toLowerCase().trim();
        document.querySelectorAll('#help-topics > details').forEach(d => {
            const text = (d.textContent || '').toLowerCase();
            d.style.display = !q || text.includes(q) ? '' : 'none';
            if (q && text.includes(q)) d.open = true;
        });
    });
})();
</script>
