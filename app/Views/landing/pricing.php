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
                    <span class="aura-pill-tag"><i class="lucide lucide-tag"></i> PRECIOS</span>
                    <span class="text-ink-700 font-medium">14 días gratis · Sin tarjeta</span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.6rem,5vw + 1rem,5rem)">Planes simples.<br><span class="gradient-shift">Sin sorpresas.</span></h1>
            <p class="mt-7 text-[18px] text-ink-500 max-w-xl mx-auto leading-relaxed">Empezá gratis. Escalá cuando crezcas. Cancelá cuando quieras.</p>

        </div>
    </div>
</section>

<!-- PRICING CARDS -->
<section class="pb-24 relative" x-data="{period:'monthly'}">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="flex justify-center -mt-2 mb-10">
            <div class="inline-flex p-1 rounded-full" style="background:#f3f4f6;border:1px solid #ececef">
                <button @click="period='monthly'" :class="period==='monthly' ? 'bg-white shadow-sm text-ink-900' : 'text-ink-500'" class="px-5 py-2 rounded-full text-[12.5px] font-semibold transition">Mensual</button>
                <button @click="period='yearly'" :class="period==='yearly' ? 'bg-white shadow-sm text-ink-900' : 'text-ink-500'" class="px-5 py-2 rounded-full text-[12.5px] font-semibold transition inline-flex items-center gap-2">Anual <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full" style="background:#d1fae5;color:#047857">Ahorrá</span></button>
            </div>
        </div>
        <?php
        $featureLabels = [
            'tickets' => 'Sistema de tickets completo',
            'kb' => 'Base de conocimiento',
            'notes' => 'Notas internas',
            'todos' => 'Gestión de tareas',
            'companies' => 'Empresas y contactos',
            'assets' => 'Inventario de activos',
            'reports' => 'Reportes y métricas',
            'users' => 'Gestión de usuarios',
            'roles' => 'Roles y permisos',
            'settings' => 'Ajustes personalizables',
            'automations' => 'Automatizaciones con IA',
            'sla' => 'SLA + Escalamientos',
            'audit' => 'Auditoría completa',
            'departments' => 'Departamentos y enrutamiento',
            'integrations' => 'Integraciones (Slack, Discord, Teams, +9)',
            'sso' => 'SSO + SAML 2.0',
            'custom_branding' => 'Marca personalizada (white-label)',
        ];
        $plansList = $plans ?? [];
        $cols = max(2, min(4, count($plansList) ?: 3));
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?= $cols ?> gap-5 max-w-6xl mx-auto items-stretch">
            <?php foreach ($plansList as $plan):
                $featured = (int)$plan['is_featured'] === 1;
                $featuresArr = json_decode($plan['features'] ?? '[]', true) ?: [];
                $color = $plan['color'] ?: '#7c5cff';
                $icon = $plan['icon'] ?: 'rocket';
                $hasFree = (float)$plan['price_monthly'] === 0.0;
            ?>
                <div class="relative rounded-[28px] p-9 transition-all duration-300 hover:-translate-y-1.5 flex flex-col <?= $featured ? 'text-white' : 'bg-white border border-[#ececef] hover:shadow-[0_30px_60px_-20px_rgba(124,92,255,0.18)]' ?>" <?= $featured ? 'style="background:linear-gradient(180deg,#1a1825 0%,#16151b 100%);box-shadow:0 30px 60px -20px rgba(124,92,255,.45)"' : '' ?>>

                    <?php if ($featured): ?>
                        <span class="absolute inset-0 rounded-[28px] pointer-events-none" style="padding:1.5px;background:linear-gradient(135deg,#7c5cff,#d946ef);-webkit-mask:linear-gradient(white,white) content-box,linear-gradient(white,white);-webkit-mask-composite:xor;mask-composite:exclude"></span>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3.5 py-1 rounded-full text-[10.5px] font-extrabold tracking-[0.16em] text-white whitespace-nowrap z-10" style="background:linear-gradient(135deg,#7c5cff,#d946ef);box-shadow:0 6px 16px -4px rgba(124,92,255,.55)">RECOMENDADO</span>
                    <?php endif; ?>

                    <div class="relative flex flex-col flex-1">
                        <div class="w-14 h-14 rounded-2xl grid place-items-center" style="<?= $featured ? 'background:rgba(124,92,255,.22);color:#fff;box-shadow:0 8px 20px -6px rgba(124,92,255,.5)' : 'background:'.$color.'22;color:'.$color.';box-shadow:0 8px 20px -6px '.$color.'40' ?>"><i class="lucide lucide-<?= $e($icon) ?> text-[26px]"></i></div>

                        <div class="mt-6">
                            <div class="text-[11px] uppercase tracking-[0.18em] font-bold <?= $featured?'text-brand-300':'text-ink-400' ?>"><?= $e($plan['name']) ?></div>
                            <div class="mt-3 flex items-baseline gap-1.5">
                                <span class="font-display font-extrabold text-[48px] tracking-[-0.03em] leading-none <?= $featured?'gradient-shift':'' ?>" x-text="period==='yearly' ? '$<?= number_format($plan['price_yearly'],0) ?>' : '$<?= number_format($plan['price_monthly'],0) ?>'">$<?= number_format($plan['price_monthly'], 0) ?></span>
                                <span class="text-[12.5px] <?= $featured?'text-white/55':'text-ink-400' ?>" x-text="period==='yearly' ? '/ año' : '/ mes'">/ mes</span>
                            </div>
                            <?php if (!empty($plan['description'])): ?>
                                <p class="text-[13.5px] mt-2 <?= $featured?'text-white/65':'text-ink-500' ?>"><?= $e($plan['description']) ?></p>
                            <?php endif; ?>
                            <?php if ((int)$plan['trial_days'] > 0): ?>
                                <div class="text-[11.5px] mt-1.5 <?= $featured?'text-brand-300':'text-brand-700' ?> font-semibold"><i class="lucide lucide-gift text-[12px]"></i> <?= (int)$plan['trial_days'] ?> días de prueba gratis</div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="<?= $url('/demo/start/' . $e($plan['slug'])) ?>" class="mt-7">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl font-semibold text-[14px] transition" <?= $featured ? 'style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.65)"' : 'style="background:#16151b;color:white"' ?> onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="lucide lucide-play text-[14px]"></i> <?= $hasFree ? 'Empezar gratis' : 'Probar ' . $e($plan['name']) ?>
                            </button>
                        </form>
                        <a href="<?= $url('/auth/register') ?>" class="block text-center text-[12px] mt-3 <?= $featured?'text-white/60':'text-ink-500' ?> hover:underline">o crear cuenta real →</a>

                        <div class="mt-7 pt-6 space-y-2.5 flex-1 <?= $featured?'border-t border-white/10':'border-t border-[#ececef]' ?>">
                            <?php
                            $limits = [];
                            if ((int)$plan['max_users'] >= 9999) $limits[] = 'Usuarios ilimitados';
                            else $limits[] = 'Hasta ' . (int)$plan['max_users'] . ' usuarios';
                            if ((int)$plan['max_tickets_month'] >= 99999) $limits[] = 'Tickets ilimitados';
                            else $limits[] = number_format($plan['max_tickets_month']) . ' tickets/mes';
                            if ((int)$plan['max_kb_articles'] >= 999) $limits[] = 'Artículos KB ilimitados';
                            else $limits[] = (int)$plan['max_kb_articles'] . ' artículos KB';
                            foreach ($limits as $lim): ?>
                                <div class="flex items-start gap-3 text-[13px]">
                                    <span class="w-5 h-5 rounded-md grid place-items-center flex-shrink-0 mt-0.5" style="<?= $featured?'background:rgba(124,92,255,.22);color:#c4b5fd':'background:#f3f0ff;color:#5a3aff' ?>"><i class="lucide lucide-check text-[11px]"></i></span>
                                    <span class="<?= $featured?'text-white/90':'text-ink-700' ?>"><?= $e($lim) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($featuresArr as $fkey):
                                $lbl = $featureLabels[$fkey] ?? ucfirst($fkey);
                            ?>
                                <div class="flex items-start gap-3 text-[13px]">
                                    <span class="w-5 h-5 rounded-md grid place-items-center flex-shrink-0 mt-0.5" style="<?= $featured?'background:rgba(124,92,255,.22);color:#c4b5fd':'background:#f3f0ff;color:#5a3aff' ?>"><i class="lucide lucide-check text-[11px]"></i></span>
                                    <span class="<?= $featured?'text-white/90':'text-ink-700' ?>"><?= $e($lbl) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($plansList)): ?>
                <div class="md:col-span-3 text-center py-16 text-ink-400">No hay planes activos. Configura planes en el panel super admin.</div>
            <?php endif; ?>
        </div>

        <!-- Comparativa breve -->
        <div class="mt-14 max-w-3xl mx-auto rounded-[24px] p-6 flex items-start gap-5" style="background:linear-gradient(135deg,#f3f0ff 0%,#fafafb 100%);border:1px solid #cdbfff">
            <div class="w-12 h-12 rounded-2xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 20px -6px rgba(124,92,255,.5)"><i class="lucide lucide-shield-check text-[20px]"></i></div>
            <div class="flex-1">
                <h3 class="font-display font-bold text-[16px] tracking-[-0.015em]">Garantía de 30 días</h3>
                <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed">Si no te enamoraste de Kydesk en 30 días, te devolvemos el 100%. Sin preguntas. Estamos seguros porque sabemos que tu equipo no querrá volver atrás.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-3">PREGUNTAS DE PRECIOS</div>
            <h2 class="display-xl" style="font-size:clamp(1.8rem,2.8vw + 1rem,2.4rem)">Todo lo que querés saber</h2>
        </div>
        <div x-data="{open:0}">
            <?php $faqs = [
                ['¿Qué pasa si supero los técnicos del plan Starter?','Te avisamos antes y podés sumarlos al siguiente ciclo. Sin sorpresas en la factura.'],
                ['¿Puedo cambiar de plan en cualquier momento?','Sí. Cambiás de plan al instante. Si bajás, lo aplicamos al siguiente ciclo. Si subís, prorrateamos.'],
                ['¿Qué incluye el SLA garantizado?','Pro: 99.9% uptime mensual. Enterprise: 99.99% con créditos de servicio si fallamos.'],
                ['¿Cómo funciona la facturación anual?','Te ahorrás 20% pagando 12 meses por adelantado. Podés cancelar y obtener prorrateo del saldo.'],
                ['¿El demo cuenta como suscripción?','No. Los demos son workspaces efímeros que se borran a las 24h. Para guardar tus datos creá una cuenta real.'],
            ]; foreach ($faqs as $i => [$q,$a]): ?>
                <div class="faq-item" :class="open===<?= $i ?> ? 'open' : ''" @click="open = open===<?= $i ?> ? -1 : <?= $i ?>">
                    <div class="faq-q"><?= $e($q) ?><div class="faq-icon"><i class="lucide lucide-plus text-[16px]"></i></div></div>
                    <div class="faq-a"><?= $e($a) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="hero-card text-center glow-purple" style="padding:72px 48px;border-radius:32px;">
            <div class="hero-stars" style="top:24px;right:24px;transform:none;opacity:.45"><svg viewBox="0 0 280 200"><path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/><path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/></svg></div>
            <div class="relative max-w-2xl mx-auto">
                <h2 class="display-xl text-white" style="font-size:clamp(2rem,3.5vw + 1rem,3.4rem);text-wrap:balance">¿Listo para probar?</h2>
                <p class="mt-5 text-[16px] text-white/85">Workspace pre-cargado · Sin tarjeta · Se borra en 24h</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= $url('/demo') ?>" class="btn btn-lg" style="background:white;color:#16151b"><i class="lucide lucide-play"></i> Probar demo</a>
                    <a href="<?= $url('/contact') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)">Hablar con ventas</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
