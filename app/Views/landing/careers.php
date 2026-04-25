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
                    <span class="aura-pill-tag"><i class="lucide lucide-rocket"></i> CARRERAS</span>
                    <span class="text-ink-700 font-medium">Remote-first · LATAM friendly</span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)">Construí el helpdesk del <span class="gradient-shift">futuro</span>.</h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed">Equipo distribuido. Trabajo profundo. Producto que mucha gente usa todos los días. Sin BS corporativo.</p>
        </div>
    </div>
</section>

<!-- VALUES -->
<section class="pb-20">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <?php $values = [
                ['heart','Cuidamos el oficio','Code review estricto. Pair programming. Tiempos realistas, no apurones absurdos.'],
                ['globe','100% remoto','Trabajamos desde donde estemos cómodos. Importan los resultados, no el horario.'],
                ['sparkles','Aprendizaje constante','Stipend mensual de aprendizaje. Conferencias pagas. Tiempo dedicado a side-projects.'],
            ]; foreach ($values as [$ic,$t,$d]): ?>
                <div class="rounded-2xl p-7 bg-white border border-[#ececef]">
                    <div class="w-12 h-12 rounded-2xl grid place-items-center mb-5" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 20px -6px rgba(124,92,255,.45)"><i class="lucide lucide-<?= $ic ?> text-[20px]"></i></div>
                    <h3 class="font-display font-bold text-[18px] tracking-[-0.015em]"><?= $e($t) ?></h3>
                    <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed"><?= $e($d) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">BENEFICIOS</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem)">Que sumen, no que estorben.</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php $benefits = [
                ['globe-2','Remoto'], ['banknote','Salario competitivo'], ['heart-pulse','Salud privada'],
                ['plane','30 días vacaciones'], ['laptop','Setup remoto pagado'], ['graduation-cap','Stipend learning'],
                ['baby','Licencia parental'], ['gift','Stock options'],
            ]; foreach ($benefits as [$ic,$lbl]): ?>
                <div class="rounded-xl p-5 bg-white border border-[#ececef] flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg grid place-items-center" style="background:#f3f0ff;color:#5a3aff"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                    <span class="text-[13.5px] font-semibold"><?= $e($lbl) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- OPEN ROLES -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">VACANTES ABIERTAS</div>
            <h2 class="display-xl" style="font-size:clamp(2.2rem,4vw + 1rem,4rem)">Roles abiertos.</h2>
        </div>

        <div class="space-y-3">
            <?php $roles = [
                ['Senior Backend Engineer', 'Engineering', 'Full-time · Remoto · LATAM', 'PHP, MySQL, sistemas distribuidos. 5+ años.'],
                ['Product Designer', 'Design', 'Full-time · Remoto', 'Figma, sistemas de diseño, UX research. Portfolio requerido.'],
                ['Customer Success Manager', 'Customer Success', 'Full-time · Remoto · ES/EN', 'Onboarding empresarial, expansion, retención.'],
                ['DevOps Engineer', 'Infrastructure', 'Full-time · Remoto', 'AWS, Kubernetes, observabilidad, SRE.'],
                ['Sales Development Rep', 'Sales', 'Full-time · CDMX o Remoto', 'Outbound, BDR, mercado SMB-MidMarket.'],
            ]; foreach ($roles as [$title, $dept, $meta, $desc]): ?>
                <a href="<?= $url('/contact') ?>" class="block rounded-2xl p-6 bg-white border border-[#ececef] hover:border-brand-300 hover:shadow-[0_12px_30px_-12px_rgba(124,92,255,.18)] transition">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10.5px] font-bold uppercase tracking-[0.14em] px-2 py-0.5 rounded-full" style="background:#f3f0ff;color:#5a3aff"><?= $e($dept) ?></span>
                            </div>
                            <h3 class="font-display font-bold text-[18px] tracking-[-0.015em]"><?= $e($title) ?></h3>
                            <div class="text-[12px] text-ink-400 mt-1"><?= $e($meta) ?></div>
                            <p class="text-[13.5px] text-ink-500 mt-3 leading-relaxed"><?= $e($desc) ?></p>
                        </div>
                        <div class="text-brand-700 flex-shrink-0"><i class="lucide lucide-arrow-right text-[18px]"></i></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-10 rounded-2xl p-6 text-center" style="background:#f3f0ff;border:1px solid #cdbfff">
            <p class="text-[13.5px] text-ink-700">¿No ves tu rol? Mandanos un CV a <a href="mailto:carreras@kydesk.com" class="font-semibold text-brand-700 hover:underline">carreras@kydesk.com</a>. Siempre buscamos talento.</p>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
