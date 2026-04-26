<section class="relative overflow-hidden">
    <div class="absolute inset-0 dev-grid-bg opacity-50"></div>
    <div class="absolute inset-0" style="background:radial-gradient(800px 400px at 50% 0%, rgba(14,165,233,.20), transparent 70%)"></div>
    <div class="relative max-w-[1180px] mx-auto px-6 pt-24 pb-20">
        <div class="text-center max-w-[820px] mx-auto">
            <span class="dev-pill mb-7"><i class="lucide lucide-zap text-[11px]"></i> API REST · <?= $e($portalName ?? 'Kydesk Developers') ?></span>
            <h1 class="dev-h1 mb-6">
                Construye tus apps de helpdesk<br>
                <span class="bg-gradient-to-r from-sky-300 to-indigo-300 bg-clip-text text-transparent">con la API de Kydesk</span>
            </h1>
            <p class="text-[18px] dev-muted leading-[1.65] mb-10 max-w-[680px] mx-auto">
                <?= $e($tagline ?? 'API helpdesk para construir tus apps') ?>. Crea tickets, automatizaciones, KB, SLA — todo desde tus propias aplicaciones. Suscríbete por uso y escala como necesites.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <?php if (!empty($allowRegistration)): ?>
                    <a href="<?= $url('/developers/register') ?>" class="dev-btn dev-btn-primary">
                        <i class="lucide lucide-rocket"></i> Crear cuenta gratis
                    </a>
                <?php endif; ?>
                <a href="<?= $url('/developers/docs') ?>" class="dev-btn dev-btn-ghost">
                    <i class="lucide lucide-book-open"></i> Ver documentación
                </a>
            </div>
            <div class="flex items-center justify-center gap-6 mt-8 text-[12.5px] dev-muted">
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check-circle text-emerald-400"></i> Plan free 10K requests/mes</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check-circle text-emerald-400"></i> Bearer token auth</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check-circle text-emerald-400"></i> Sandbox aislado por app</span>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mt-16 max-w-[1100px] mx-auto">
            <div class="dev-feature">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center bg-sky-500/15 border border-sky-500/20 text-sky-300"><i class="lucide lucide-terminal"></i></div>
                    <h3 class="font-display font-bold text-white text-[18px]">REST API completa</h3>
                </div>
                <p class="text-[13.5px] dev-muted leading-[1.65]">Tickets, KB, SLA, automations, companies, users. JSON, OAuth tokens y rate limiting estándar.</p>
            </div>
            <div class="dev-feature">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center bg-indigo-500/15 border border-indigo-500/20 text-indigo-300"><i class="lucide lucide-shield-check"></i></div>
                    <h3 class="font-display font-bold text-white text-[18px]">Aislamiento por app</h3>
                </div>
                <p class="text-[13.5px] dev-muted leading-[1.65]">Cada app crea un workspace dedicado. Sin contaminación entre proyectos, sin acceso cruzado.</p>
            </div>
            <div class="dev-feature">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center bg-emerald-500/15 border border-emerald-500/20 text-emerald-300"><i class="lucide lucide-line-chart"></i></div>
                    <h3 class="font-display font-bold text-white text-[18px]">Métricas en vivo</h3>
                </div>
                <p class="text-[13.5px] dev-muted leading-[1.65]">Dashboard con requests, errores, latencia. Métricas por app, por token, por día.</p>
            </div>
            <div class="dev-feature">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center bg-amber-500/15 border border-amber-500/20 text-amber-300"><i class="lucide lucide-credit-card"></i></div>
                    <h3 class="font-display font-bold text-white text-[18px]">Facturación clara</h3>
                </div>
                <p class="text-[13.5px] dev-muted leading-[1.65]">Suscríbete por mes/año. Sin sorpresas: el portal te muestra cuota usada y costos.</p>
            </div>
        </div>

        <div class="mt-16 max-w-[920px] mx-auto">
            <div class="dev-card dev-glow p-8">
                <div class="flex items-center gap-2 mb-5">
                    <span class="dev-pill"><i class="lucide lucide-code-2 text-[11px]"></i> Quickstart</span>
                    <span class="text-[12.5px] dev-muted">Crea tu primer ticket en 30 segundos</span>
                </div>
                <pre class="code-block"><span class="c"># 1. Crea una cuenta y un app, luego un token</span>
<span class="c"># 2. Apunta a la API:</span>
<span class="k">curl</span> -X POST https://kydesk.kyrosrd.com/api/v1/tickets \
  -H <span class="s">"Authorization: Bearer kyd_xxxxxxxxxxxxxxxxxxxxxxxx"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d '{
    <span class="n">"subject"</span>: <span class="s">"Mi primer ticket desde la API"</span>,
    <span class="n">"description"</span>: <span class="s">"Funcionando!"</span>,
    <span class="n">"priority"</span>: <span class="s">"high"</span>,
    <span class="n">"requester_email"</span>: <span class="s">"user@example.com"</span>
  }'</pre>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($plans)): ?>
<section class="relative">
    <div class="max-w-[1180px] mx-auto px-6 py-16">
        <div class="text-center mb-10">
            <span class="dev-pill mb-3"><i class="lucide lucide-tag text-[11px]"></i> Planes</span>
            <h2 class="text-white font-display font-bold text-[34px] tracking-tight">Elige el plan que necesitas</h2>
            <p class="dev-muted mt-2">Empieza gratis. Escala cuando crezcas.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-<?= count($plans) > 4 ? '5' : count($plans) ?> gap-5">
            <?php foreach ($plans as $p):
                $features = json_decode((string)$p['features'], true) ?: [];
            ?>
                <div class="dev-feature relative <?= $p['is_featured'] ? 'ring-2 ring-sky-400/40' : '' ?>">
                    <?php if ($p['is_featured']): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 dev-pill bg-sky-500 text-white border-sky-400">Más popular</div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-9 h-9 rounded-xl grid place-items-center text-white" style="background:<?= $e($p['color']) ?>"><i class="lucide lucide-<?= $e($p['icon']) ?>"></i></div>
                        <div>
                            <div class="font-display font-bold text-white text-[16px]"><?= $e($p['name']) ?></div>
                            <div class="text-[11.5px] dev-muted"><?= $e($p['slug']) ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="font-display font-bold text-white text-[28px] leading-none">$<?= number_format((float)$p['price_monthly'], 0) ?><span class="text-[14px] dev-muted font-normal">/mes</span></div>
                        <?php if ((float)$p['price_yearly'] > 0): ?>
                            <div class="text-[11.5px] dev-muted mt-1">o $<?= number_format((float)$p['price_yearly'], 0) ?>/año</div>
                        <?php endif; ?>
                    </div>
                    <p class="text-[12.5px] dev-muted mb-4 leading-[1.55]"><?= $e($p['description']) ?></p>
                    <ul class="space-y-1.5 text-[12.5px] mb-4">
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= number_format((int)$p['max_requests_month']) ?> requests/mes</span></li>
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= (int)$p['max_apps'] ?> app<?= (int)$p['max_apps'] === 1 ? '' : 's' ?></span></li>
                        <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= (int)$p['rate_limit_per_min'] ?> req/min</span></li>
                        <?php foreach (array_slice($features, 0, 4) as $f): ?>
                            <li class="flex items-center gap-2"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <span class="text-slate-300"><?= $e(ucfirst(str_replace('_',' ',$f))) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= $url('/developers/register?plan=' . urlencode($p['slug'])) ?>" class="dev-btn <?= $p['is_featured'] ? 'dev-btn-primary' : 'dev-btn-ghost' ?> w-full">
                        <?= (float)$p['price_monthly'] == 0 ? 'Empezar gratis' : 'Comenzar' ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
