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
                    <span class="aura-pill-tag"><i class="lucide lucide-heart"></i> CLIENTES</span>
                    <span class="text-ink-700 font-medium">+12,000 técnicos resuelven con Kydesk</span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)">Equipos que <span class="gradient-shift">eligen Kydesk</span>.</h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed">Desde startups en Y Combinator hasta operaciones MSP con cientos de técnicos. Esto es lo que dicen.</p>
        </div>
    </div>
</section>

<!-- LOGO WALL -->
<section class="pb-16">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center text-[11px] font-bold uppercase tracking-[0.18em] text-ink-400 mb-8">EMPRESAS QUE NOS ELIGEN</div>
        <div class="grid grid-cols-3 md:grid-cols-6 gap-6 items-center">
            <?php
            $logos = ['Acme Corp','GlobeX','Stark Ind.','Wayne Tech','Soylent','Pied Piper','Hooli','Initech','Umbrella','Cyberdyne','Vandelay','Massive Dyn.'];
            foreach ($logos as $logo): ?>
                <div class="aspect-[3/1] grid place-items-center rounded-xl bg-white border border-[#ececef] hover:border-brand-300 hover:shadow-[0_8px_20px_-8px_rgba(124,92,255,.15)] transition">
                    <div class="font-display font-extrabold text-[14px] tracking-[-0.02em] text-ink-500 hover:text-brand-700 transition"><?= $e($logo) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">TESTIMONIOS</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem)">Lo dicen ellos, no nosotros.</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <?php
            $testimonials = [
                ['Bajamos el tiempo de respuesta de 4 horas a 12 minutos en 3 semanas. El SLA visual le cambió la cabeza al equipo.', 'María García', 'Head of Support · Acme Corp', '#ec4899'],
                ['Probamos 4 helpdesks. Kydesk es el único que entendió cómo trabajamos como MSP. Multi-tenant nativo es magia.', 'Jorge Salinas', 'CTO · TechSolutions MSP', '#7c5cff'],
                ['Las automatizaciones nos quitaron el 30% del trabajo manual. Mi equipo agradece.', 'Lucía Méndez', 'Operations Manager · GlobeX', '#22c55e'],
                ['El portal público redujo nuestros tickets repetitivos un 40%. Los clientes encuentran respuesta antes de abrir ticket.', 'Ricardo Pineda', 'IT Director · Stark Industries', '#f59e0b'],
                ['Migramos de Zendesk en 2 días sin perder un solo dato. El soporte fue impecable.', 'Ana Torres', 'CIO · Wayne Tech', '#0ea5e9'],
                ['Por fin un helpdesk que no parece de los 2010. La UX es brutal y los atajos hacen la diferencia.', 'Miguel Romero', 'Sr. Engineer · Pied Piper', '#d946ef'],
            ];
            foreach ($testimonials as [$quote, $name, $role, $color]): ?>
                <div class="rounded-2xl p-7 bg-white border border-[#ececef] hover:shadow-[0_20px_40px_-15px_rgba(124,92,255,.18)] transition flex flex-col">
                    <div class="text-[12px] text-amber-500 mb-4">★★★★★</div>
                    <p class="text-[14px] leading-relaxed text-ink-700 flex-1">&ldquo;<?= $e($quote) ?>&rdquo;</p>
                    <div class="mt-5 pt-5 border-t border-[#ececef] flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full grid place-items-center text-white font-display font-bold text-[13px]" style="background:<?= $color ?>"><?= mb_substr($name,0,1) . mb_substr(explode(' ',$name)[1] ?? '',0,1) ?></div>
                        <div>
                            <div class="font-display font-bold text-[13.5px] tracking-[-0.01em]"><?= $e($name) ?></div>
                            <div class="text-[11.5px] text-ink-400"><?= $e($role) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CASE STUDIES -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">CASE STUDIES</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem)">Resultados reales.</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <?php
            $cases = [
                ['58%', 'reducción de tiempo de resolución', 'Acme Corp implementó SLAs y automatizaciones en 2 semanas.','#ec4899'],
                ['12,000+', 'tickets/mes manejados', 'TechSolutions MSP centralizó 23 clientes en un solo workspace.','#7c5cff'],
                ['99.97%', 'cumplimiento SLA', 'GlobeX mantuvo el SLA estricto durante un año fiscal.','#22c55e'],
            ];
            foreach ($cases as [$kpi, $kpiLabel, $desc, $col]): ?>
                <div class="rounded-2xl p-8 bg-white border border-[#ececef]">
                    <div class="font-display font-extrabold text-[56px] tracking-[-0.03em] leading-none" style="color:<?= $col ?>"><?= $e($kpi) ?></div>
                    <div class="text-[12px] uppercase tracking-[0.14em] font-bold text-ink-400 mt-2"><?= $e($kpiLabel) ?></div>
                    <p class="text-[13.5px] text-ink-500 mt-5"><?= $e($desc) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20">
    <div class="max-w-[1100px] mx-auto px-6">
        <div class="rounded-[32px] p-12 md:p-16 text-center text-white relative overflow-hidden" style="background:linear-gradient(135deg,#1a1825,#16151b);box-shadow:0 30px 60px -20px rgba(124,92,255,.4)">
            <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 0% 0%,rgba(124,92,255,.4),transparent 60%),radial-gradient(circle at 100% 100%,rgba(217,70,239,.3),transparent 60%)"></div>
            <div class="relative">
                <h2 class="font-display font-extrabold tracking-[-0.025em]" style="font-size:clamp(2rem,4vw + 1rem,3.5rem)">Sumate al lado bueno del soporte.</h2>
                <p class="text-[16px] text-white/65 mt-5 max-w-xl mx-auto leading-relaxed">14 días gratis. Sin tarjeta. Cancelás cuando quieras.</p>
                <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-lg" style="background:white;color:#16151b">Empezar gratis <i class="lucide lucide-arrow-right"></i></a>
                    <a href="<?= $url('/contact') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)">Hablar con ventas</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
