<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<section class="relative pt-36 pb-20 overflow-hidden">
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
                    <span class="aura-pill-tag"><i class="lucide lucide-rocket"></i> DEMO EN VIVO</span>
                    <span class="text-ink-700 font-medium">Sin registro · Sin tarjeta · Datos efímeros</span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance">Probá Kydesk<br><span class="gradient-shift">tal como lo usás en tu equipo</span>.</h1>
            <p class="mt-7 text-[18px] max-w-xl mx-auto leading-relaxed text-ink-500">Elegí un plan y entrás a un workspace pre-cargado con tickets, equipo y métricas. Tus datos se borran automáticamente en <strong class="text-ink-900"><?= $ttl ?> horas</strong>.</p>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto items-stretch">
            <?php
            $iconMap = [
                'starter' => ['rocket', '#dbeafe', '#1d4ed8'],
                'pro' => ['zap', '', ''],
                'enterprise' => ['crown', '#fef3c7', '#b45309'],
            ];
            foreach ($plans as $key => $cfg):
                [$ic, $bg, $col] = $iconMap[$key] ?? ['package','#f3f4f6','#6b6b78'];
                $featured = $key === 'pro';
                $limits = $cfg['limits'];
                $rows = [
                    ['users','Técnicos', $limits['users'] >= 999 ? 'Ilimitados' : (string)$limits['users']],
                    ['inbox','Tickets/mes', $limits['tickets_per_month'] >= 99999 ? 'Ilimitados' : number_format($limits['tickets_per_month'])],
                    ['radio','Canales', is_array($limits['channels']) ? count($limits['channels']) . ' (' . implode(', ', array_map('ucfirst', $limits['channels'])) . ')' : 'no'],
                    ['workflow','Automatizaciones IA', ($limits['automations'] ?? 0) > 0 ? 'incluido' : 'no'],
                    ['gauge','SLA + Escalamientos', ($limits['sla'] ?? 0) > 0 ? 'incluido' : 'no'],
                    ['shield-check','Auditoría', ($limits['audit'] ?? 0) > 0 ? ($limits['audit_retention'] ?? 'incluido') : 'no'],
                    ['code','API + Webhooks', ($limits['api'] ?? 0) > 0 ? 'incluido' : 'no'],
                    ['key-round','SSO + SAML', ($limits['sso'] ?? 0) > 0 ? 'incluido' : 'no'],
                    ['palette','Marca personalizada', ($limits['branding'] ?? 0) > 0 ? 'incluido' : 'no'],
                    ['life-buoy','Soporte', $limits['support'] ?? '—'],
                    ['shield','SLA garantizado', $limits['sla_guarantee'] ?? '—'],
                    ['user-cog','Customer Success Manager', ($limits['success_manager'] ?? 0) > 0 ? 'incluido' : 'no'],
                    ['globe','Residencia de datos', $limits['data_residency'] ?? '—'],
                ];
            ?>
                <div class="relative rounded-[28px] p-9 transition-all duration-300 hover:-translate-y-1.5 flex flex-col <?= $featured ? 'text-white' : 'bg-white border border-[#ececef] hover:shadow-[0_30px_60px_-20px_rgba(124,92,255,0.18)]' ?>" <?= $featured ? 'style="background:linear-gradient(180deg,#1a1825 0%,#16151b 100%);box-shadow:0 30px 60px -20px rgba(124,92,255,.45)"' : '' ?>>

                    <?php if ($featured): ?>
                        <span class="absolute inset-0 rounded-[28px] pointer-events-none" style="padding:1.5px;background:linear-gradient(135deg,#7c5cff,#d946ef);-webkit-mask:linear-gradient(white,white) content-box,linear-gradient(white,white);-webkit-mask-composite:xor;mask-composite:exclude"></span>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3.5 py-1 rounded-full text-[10.5px] font-extrabold tracking-[0.16em] text-white whitespace-nowrap z-10" style="background:linear-gradient(135deg,#7c5cff,#d946ef);box-shadow:0 6px 16px -4px rgba(124,92,255,.55)">RECOMENDADO</span>
                    <?php endif; ?>

                    <div class="relative flex flex-col flex-1">
                        <div class="w-14 h-14 rounded-2xl grid place-items-center" style="<?= $featured ? 'background:rgba(124,92,255,.22);color:#fff;box-shadow:0 8px 20px -6px rgba(124,92,255,.5)' : 'background:'.$bg.';color:'.$col.';box-shadow:0 8px 20px -6px '.$col.'40' ?>"><i class="lucide lucide-<?= $ic ?> text-[26px]"></i></div>

                        <div class="mt-6">
                            <div class="text-[11px] uppercase tracking-[0.18em] font-bold <?= $featured?'text-brand-300':'text-ink-400' ?>"><?= $e($cfg['label']) ?></div>
                            <h3 class="font-display font-extrabold text-[30px] mt-2 tracking-[-0.025em] leading-none"><?= $e($cfg['label']) ?></h3>
                            <p class="text-[13.5px] mt-2 <?= $featured?'text-white/65':'text-ink-500' ?>"><?= $e($cfg['tagline']) ?></p>
                        </div>

                        <div class="mt-7 pt-6 space-y-0.5 flex-1 <?= $featured?'border-t border-white/10':'border-t border-[#ececef]' ?>">
                            <?php foreach ($rows as [$ic2, $label, $val]):
                                $isPositive = !in_array($val, ['no', '—', 0, '0'], true);
                                $iconStyle = $isPositive
                                    ? ($featured ? 'background:rgba(124,92,255,.22);color:#c4b5fd' : 'background:#f3f0ff;color:#5a3aff')
                                    : ($featured ? 'background:rgba(255,255,255,.05);color:rgba(255,255,255,.25)' : 'background:#f3f4f6;color:#b8b8c4');
                                $valColor = $isPositive
                                    ? ($featured ? 'text-white font-semibold' : 'text-ink-900 font-semibold')
                                    : ($featured ? 'text-white/35' : 'text-ink-400');
                                $labelColor = $featured ? 'text-white/85' : 'text-ink-700';
                                $iconName = $isPositive ? $ic2 : 'minus';
                                $valDisplay = match (true) {
                                    $val === 'no' => 'No',
                                    $val === 'incluido' => 'Sí',
                                    default => (string)$val,
                                };
                            ?>
                                <div class="flex items-start gap-3 py-2 text-[12.5px]">
                                    <span class="w-6 h-6 rounded-md grid place-items-center flex-shrink-0 mt-0.5" style="<?= $iconStyle ?>"><i class="lucide lucide-<?= $iconName ?> text-[12px]"></i></span>
                                    <span class="<?= $labelColor ?> flex-shrink-0"><?= $e($label) ?></span>
                                    <span class="ml-auto text-right text-[11.5px] leading-snug <?= $valColor ?>"><?= $e($valDisplay) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" action="<?= $url('/demo/start/' . $key) ?>" class="mt-8">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl font-semibold text-[14px] transition-all" <?= $featured ? 'style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.65)"' : 'style="background:#16151b;color:white"' ?> onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="lucide lucide-play text-[14px]"></i> Probar <?= $e($cfg['label']) ?>
                            </button>
                        </form>
                        <div class="text-center mt-4 text-[11.5px] <?= $featured?'text-white/45':'text-ink-400' ?>">Workspace activo por <?= $ttl ?>h · Sin registro</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-20 max-w-3xl mx-auto rounded-[24px] p-7 flex items-start gap-5" style="background:linear-gradient(135deg,#f3f0ff 0%,#fafafb 100%);border:1px solid #cdbfff">
            <div class="w-12 h-12 rounded-2xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 20px -6px rgba(124,92,255,.5)"><i class="lucide lucide-info text-[20px]"></i></div>
            <div class="flex-1">
                <h3 class="font-display font-bold text-[16px] tracking-[-0.015em]">Tu workspace de demostración</h3>
                <p class="text-[13.5px] text-ink-500 mt-2 leading-relaxed">Cada demo es un <strong class="text-ink-900">tenant aislado</strong> con su propio slug, usuarios, tickets y categorías. Después de <?= $ttl ?>h, todo se elimina automáticamente: registros en la base de datos, archivos subidos y sesiones. Si querés conservarlo, podés <a href="<?= $url('/auth/register') ?>" class="text-brand-700 font-semibold underline">crear una cuenta real</a> antes de que expire.</p>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
