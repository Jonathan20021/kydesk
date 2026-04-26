<?php
$totalRequests = 0;
$totalDevs = 0;
try {
    $totalRequests = (int)\App\Core\Application::get()->db->val("SELECT IFNULL(SUM(requests),0) FROM dev_api_usage");
    $totalDevs = (int)\App\Core\Application::get()->db->val("SELECT COUNT(*) FROM developers WHERE is_active=1");
} catch (\Throwable $_landingErr) {}
?>

<!-- ═════════════════ HERO ═════════════════ -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 dev-grid-bg opacity-40"></div>
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(900px 500px at 50% -10%, rgba(14,165,233,.25), transparent 70%)"></div>
    <div class="absolute top-32 left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full pointer-events-none" style="background:radial-gradient(circle, rgba(99,102,241,.15) 0%, transparent 60%); filter:blur(80px)"></div>

    <div class="relative max-w-[1180px] mx-auto px-6 pt-20 pb-24">
        <div class="text-center max-w-[860px] mx-auto">
            <a href="<?= $url('/changelog') ?>" class="inline-flex items-center gap-2 mb-7 px-3.5 py-1.5 rounded-full text-[12px] font-medium" style="background:rgba(56,189,248,.08); border:1px solid rgba(56,189,248,.20); color:#bae6fd">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-emerald-300 font-bold tracking-wide uppercase text-[10px]">Live</span>
                <span>API v1 ahora soporta webhooks y rate limits granulares</span>
                <i class="lucide lucide-arrow-right text-[11px]"></i>
            </a>

            <h1 class="dev-h1 mb-6">
                La API de helpdesk<br>
                <span class="bg-gradient-to-r from-sky-300 via-indigo-300 to-fuchsia-300 bg-clip-text text-transparent">para developers</span>
            </h1>

            <p class="text-[18px] dev-muted leading-[1.65] mb-10 max-w-[700px] mx-auto">
                Crea tickets, automatizaciones, KB y SLA desde tu propia app. Cada proyecto tiene su <span class="text-white font-semibold">workspace aislado</span>, su quota propia y telemetría en vivo. <span class="text-sky-300">Empieza gratis, escala como necesites.</span>
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <?php if (!empty($allowRegistration)): ?>
                    <a href="<?= $url('/developers/register') ?>" class="dev-btn dev-btn-primary">
                        <i class="lucide lucide-rocket"></i> Crear cuenta gratis
                    </a>
                <?php endif; ?>
                <a href="<?= $url('/developers/docs') ?>" class="dev-btn dev-btn-ghost">
                    <i class="lucide lucide-book-open"></i> Leer la documentación
                </a>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 mt-8 text-[12.5px] dev-muted">
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check-circle text-emerald-400"></i> 10K requests/mes en plan free</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check-circle text-emerald-400"></i> Sin tarjeta de crédito</span>
                <span class="flex items-center gap-1.5"><i class="lucide lucide-check-circle text-emerald-400"></i> Setup en 30 segundos</span>
            </div>
        </div>

        <!-- Terminal mockup -->
        <div class="mt-16 max-w-[900px] mx-auto">
            <div class="dev-card dev-glow overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 border-b" style="border-color:rgba(56,189,248,.10); background:rgba(0,0,0,.2)">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#ff5f57]"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-[#febc2e]"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-[#28c840]"></span>
                    <div class="flex-1 text-center text-[11px] font-mono" style="color:#475569">curl · creating ticket</div>
                </div>
                <div x-data="{tab:'curl'}" class="bg-[#040510]">
                    <div class="flex border-b text-[12px] font-mono" style="border-color:rgba(56,189,248,.08)">
                        <?php foreach (['curl'=>'curl','node'=>'Node','python'=>'Python','php'=>'PHP'] as $k=>$lbl): ?>
                            <button @click="tab='<?= $k ?>'" :class="tab==='<?= $k ?>' ? 'text-sky-300 border-sky-400' : 'text-slate-500 border-transparent hover:text-slate-300'" class="px-4 py-2 border-b-2 transition"><?= $lbl ?></button>
                        <?php endforeach; ?>
                    </div>

                    <pre x-show="tab==='curl'" class="code-block !rounded-none !border-0 !bg-transparent !m-0 !p-5 text-[12.5px] leading-[1.7]"><span class="c"># 1. Crea cuenta y app — obtienes un token "kyd_..."</span>
<span class="c"># 2. Llama la API — tu app tiene su propio workspace aislado</span>

<span class="k">curl</span> -X POST https://kydesk.kyrosrd.com/api/v1/tickets \
  -H <span class="s">"Authorization: Bearer kyd_xxxxxxxxxxxxxxxx"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d '{
    <span class="n">"subject"</span>: <span class="s">"Mi primer ticket"</span>,
    <span class="n">"priority"</span>: <span class="s">"high"</span>,
    <span class="n">"requester_email"</span>: <span class="s">"user@example.com"</span>
  }'</pre>

                    <pre x-show="tab==='node'" class="code-block !rounded-none !border-0 !bg-transparent !m-0 !p-5 text-[12.5px] leading-[1.7]" x-cloak><span class="k">import</span> { Kydesk } <span class="k">from</span> <span class="s">"@kydesk/sdk"</span>;

<span class="k">const</span> client = <span class="k">new</span> Kydesk({ token: <span class="s">"kyd_xxxxxxxxxxxxxxxx"</span> });

<span class="k">const</span> ticket = <span class="k">await</span> client.tickets.create({
  subject: <span class="s">"Mi primer ticket"</span>,
  priority: <span class="s">"high"</span>,
  requester_email: <span class="s">"user@example.com"</span>,
});</pre>

                    <pre x-show="tab==='python'" class="code-block !rounded-none !border-0 !bg-transparent !m-0 !p-5 text-[12.5px] leading-[1.7]" x-cloak><span class="k">import</span> requests

resp = requests.post(
    <span class="s">"https://kydesk.kyrosrd.com/api/v1/tickets"</span>,
    headers={<span class="s">"Authorization"</span>: <span class="s">"Bearer kyd_xxxxxxxxxxxxxxxx"</span>},
    json={
        <span class="n">"subject"</span>: <span class="s">"Mi primer ticket"</span>,
        <span class="n">"priority"</span>: <span class="s">"high"</span>,
        <span class="n">"requester_email"</span>: <span class="s">"user@example.com"</span>,
    },
)
ticket = resp.json()[<span class="s">"data"</span>]</pre>

                    <pre x-show="tab==='php'" class="code-block !rounded-none !border-0 !bg-transparent !m-0 !p-5 text-[12.5px] leading-[1.7]" x-cloak><?php ob_start(); ?><span class="k">$ch</span> = curl_init(<span class="s">"https://kydesk.kyrosrd.com/api/v1/tickets"</span>);
curl_setopt_array(<span class="k">$ch</span>, [
    CURLOPT_RETURNTRANSFER => <span class="k">true</span>,
    CURLOPT_HTTPHEADER => [
        <span class="s">"Authorization: Bearer kyd_xxxxxxxxxxxxxxxx"</span>,
        <span class="s">"Content-Type: application/json"</span>,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        <span class="n">"subject"</span> => <span class="s">"Mi primer ticket"</span>,
        <span class="n">"priority"</span> => <span class="s">"high"</span>,
    ]),
]);
<span class="k">$resp</span> = json_decode(curl_exec(<span class="k">$ch</span>), <span class="k">true</span>);<?php echo ob_get_clean(); ?></pre>
                </div>
                <div class="bg-[#020308] px-5 py-3 border-t flex items-center gap-3 text-[11.5px] font-mono" style="border-color:rgba(56,189,248,.08); color:#94a3b8">
                    <span class="dev-pill dev-pill-emerald !text-[9.5px]">200 OK</span>
                    <span>{ "data": { "id": 42, "code": "TK-01-00042", ... } }</span>
                </div>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-px mt-12 max-w-[900px] mx-auto rounded-2xl overflow-hidden" style="background:rgba(56,189,248,.10); border:1px solid rgba(56,189,248,.18)">
            <div class="dev-card !rounded-none !border-0 px-5 py-6 text-center">
                <div class="font-display font-extrabold text-[26px] text-white"><?= $totalRequests > 1000 ? round($totalRequests/1000, 1) . 'K' : number_format($totalRequests) ?></div>
                <div class="text-[10.5px] uppercase font-bold tracking-[0.16em] mt-1" style="color:#7dd3fc">requests servidas</div>
            </div>
            <div class="dev-card !rounded-none !border-0 px-5 py-6 text-center">
                <div class="font-display font-extrabold text-[26px] text-white"><?= number_format(max(1, $totalDevs)) ?></div>
                <div class="text-[10.5px] uppercase font-bold tracking-[0.16em] mt-1" style="color:#7dd3fc">developers activos</div>
            </div>
            <div class="dev-card !rounded-none !border-0 px-5 py-6 text-center">
                <div class="font-display font-extrabold text-[26px] text-white">99.95%</div>
                <div class="text-[10.5px] uppercase font-bold tracking-[0.16em] mt-1" style="color:#7dd3fc">uptime SLA</div>
            </div>
            <div class="dev-card !rounded-none !border-0 px-5 py-6 text-center">
                <div class="font-display font-extrabold text-[26px] text-white">~80ms</div>
                <div class="text-[10.5px] uppercase font-bold tracking-[0.16em] mt-1" style="color:#7dd3fc">latencia promedio</div>
            </div>
        </div>
    </div>
</section>

<!-- ═════════════════ FEATURES GRID ═════════════════ -->
<section class="max-w-[1180px] mx-auto px-6 py-24">
    <div class="text-center mb-12">
        <span class="dev-pill mb-3"><i class="lucide lucide-layers text-[11px]"></i> Capabilities</span>
        <h2 class="text-white font-display font-bold text-[40px] tracking-tight leading-[1.1]">Todo lo que necesitas para<br>integrar helpdesk en tu app</h2>
        <p class="dev-muted mt-3 text-[15px] max-w-[640px] mx-auto">REST API limpia, OAuth tokens, webhooks, métricas en vivo. Diseñada para developers, no para enterprise architects.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php
        $features = [
            ['terminal','REST API completa','sky','Tickets, KB, SLA, automations, companies, users. JSON estándar, sin SDKs propietarios obligatorios.'],
            ['shield-check','Workspaces aislados','indigo','Cada app crea automáticamente su propio tenant. Sin contaminación entre tus proyectos.'],
            ['key','Bearer tokens','emerald','Genera tantos tokens como permita tu plan. Rota o revoca al instante. Scopes granulares.'],
            ['gauge','Rate limit predecible','amber','Headers <code class="text-amber-300 font-mono text-[11px]">X-RateLimit-*</code> en cada respuesta. Sabes exactamente cuándo retroceder.'],
            ['line-chart','Métricas en vivo','rose','Dashboard con requests/error rate/latencia por día y por app. Drill-down a cada llamada.'],
            ['webhook','Webhooks','fuchsia','Suscríbete a eventos: ticket.created, ticket.updated, sla.breach. Reintentos automáticos.'],
            ['terminal-square','Sandbox aislado','sky','Cada app tiene un workspace propio para testing. Seguro experimentar sin afectar prod.'],
            ['zap','Sin cold start','indigo','API caliente 24/7 — tu primer request es tan rápida como la millonésima.'],
            ['credit-card','Pricing transparente','emerald','Solo pagas el plan. Overage opcional con precio público. Sin sorpresas en la factura.'],
        ];
        foreach ($features as [$ic, $title, $color, $desc]):
            $bg = ['sky'=>'rgba(14,165,233,.12)','indigo'=>'rgba(99,102,241,.12)','emerald'=>'rgba(16,185,129,.12)','amber'=>'rgba(245,158,11,.12)','rose'=>'rgba(244,63,94,.12)','fuchsia'=>'rgba(217,70,239,.12)'][$color];
            $bd = ['sky'=>'rgba(56,189,248,.25)','indigo'=>'rgba(129,140,248,.25)','emerald'=>'rgba(16,185,129,.25)','amber'=>'rgba(245,158,11,.25)','rose'=>'rgba(244,63,94,.25)','fuchsia'=>'rgba(217,70,239,.25)'][$color];
            $tx = ['sky'=>'#7dd3fc','indigo'=>'#a5b4fc','emerald'=>'#86efac','amber'=>'#fcd34d','rose'=>'#fda4af','fuchsia'=>'#f0abfc'][$color];
        ?>
            <div class="dev-feature group">
                <div class="w-11 h-11 rounded-xl grid place-items-center mb-4 transition-transform group-hover:scale-110" style="background:<?= $bg ?>; border:1px solid <?= $bd ?>; color:<?= $tx ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
                <h3 class="font-display font-bold text-white text-[17px] mb-2"><?= $title ?></h3>
                <p class="text-[13px] dev-muted leading-[1.65]"><?= $desc ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═════════════════ USE CASES ═════════════════ -->
<section class="max-w-[1180px] mx-auto px-6 py-20">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <span class="dev-pill mb-3"><i class="lucide lucide-sparkles text-[11px]"></i> Ideal para</span>
            <h2 class="text-white font-display font-bold text-[34px] tracking-tight leading-[1.1] mb-5">¿Qué construyes con la API?</h2>
            <p class="dev-muted text-[15px] leading-[1.7] mb-6">Cualquier app que necesite ticketing, soporte o gestión de incidencias. Aquí algunas ideas de developers que ya usan Kydesk.</p>
            <div class="space-y-3">
                <?php
                $cases = [
                    ['shopping-bag','E-commerce con soporte integrado','Tu tienda crea tickets automáticamente cuando un cliente abre una disputa o pide un reembolso.'],
                    ['cpu','SaaS multi-tenant','Cada uno de tus clientes tiene su workspace, sus tickets y sus métricas — sin compartir datos.'],
                    ['plug','Plataformas IoT','Reporta incidentes detectados por sensores como tickets críticos con escalación SLA.'],
                    ['users-round','Marketplaces','Soporte entre vendedores y compradores con threads aislados por transacción.'],
                ];
                foreach ($cases as [$ic, $t, $d]): ?>
                    <div class="dev-feature flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:rgba(14,165,233,.12); border:1px solid rgba(56,189,248,.20); color:#7dd3fc"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                        <div>
                            <div class="font-display font-bold text-white text-[14.5px]"><?= $t ?></div>
                            <p class="text-[12.5px] dev-muted mt-0.5"><?= $d ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- API response visualization -->
        <div class="dev-card dev-glow overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between" style="border-color:rgba(56,189,248,.10); background:rgba(0,0,0,.2)">
                <div class="flex items-center gap-2 text-[12px] font-mono" style="color:#7dd3fc">
                    <i class="lucide lucide-arrow-right text-[12px]"></i> GET /api/v1/tickets/42
                </div>
                <span class="dev-pill dev-pill-emerald !text-[9.5px]">200 · 67ms</span>
            </div>
            <pre class="code-block !rounded-none !border-0 !bg-transparent !m-0 !p-5 text-[12px] leading-[1.7]">{
  <span class="n">"data"</span>: {
    <span class="n">"id"</span>: <span class="s">42</span>,
    <span class="n">"code"</span>: <span class="s">"TK-01-00042"</span>,
    <span class="n">"subject"</span>: <span class="s">"Servidor sin respuesta"</span>,
    <span class="n">"status"</span>: <span class="s">"in_progress"</span>,
    <span class="n">"priority"</span>: <span class="s">"urgent"</span>,
    <span class="n">"sla_due_at"</span>: <span class="s">"2026-04-26T18:00:00Z"</span>,
    <span class="n">"requester_email"</span>: <span class="s">"alice@example.com"</span>,
    <span class="n">"assigned_to"</span>: <span class="s">7</span>,
    <span class="n">"created_at"</span>: <span class="s">"2026-04-26T14:30:00Z"</span>
  },
  <span class="n">"meta"</span>: { <span class="n">"rate_limit_remaining"</span>: <span class="s">238</span> }
}</pre>
        </div>
    </div>
</section>

<!-- ═════════════════ PRICING ═════════════════ -->
<?php if (!empty($plans)): ?>
<section id="pricing" class="relative">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(600px 300px at 50% 0%, rgba(99,102,241,.10), transparent 70%)"></div>
    <div class="relative max-w-[1180px] mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <span class="dev-pill mb-3"><i class="lucide lucide-tag text-[11px]"></i> Pricing</span>
            <h2 class="text-white font-display font-bold text-[36px] tracking-tight">Planes pensados para escalar</h2>
            <p class="dev-muted mt-3 text-[15px]">Empieza gratis. Mejora cuando lo necesites. Cancela cuando quieras.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-<?= count($plans) > 4 ? '5' : count($plans) ?> gap-4">
            <?php foreach ($plans as $p):
                $features = json_decode((string)$p['features'], true) ?: [];
            ?>
                <div class="dev-feature relative <?= $p['is_featured'] ? 'ring-2 ring-sky-400/40' : '' ?>">
                    <?php if ($p['is_featured']): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 dev-pill !bg-sky-500 !text-white !border-sky-400">Más popular</div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:<?= $e($p['color']) ?>"><i class="lucide lucide-<?= $e($p['icon']) ?> text-[16px]"></i></div>
                        <div>
                            <div class="font-display font-bold text-white text-[17px]"><?= $e($p['name']) ?></div>
                            <div class="text-[10.5px] text-slate-500 font-mono"><?= $e($p['slug']) ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="font-display font-bold text-white text-[32px] leading-none">$<?= number_format((float)$p['price_monthly'], 0) ?><span class="text-[13px] dev-muted font-normal">/mes</span></div>
                        <?php if ((float)$p['price_yearly'] > 0): ?>
                            <div class="text-[11.5px] text-emerald-300 mt-1">Anual: $<?= number_format((float)$p['price_yearly'], 0) ?> · ahorra <?= round((1 - ($p['price_yearly'] / 12) / max(0.01, $p['price_monthly'])) * 100) ?>%</div>
                        <?php endif; ?>
                    </div>
                    <p class="text-[12.5px] dev-muted mb-4 leading-[1.55]"><?= $e($p['description']) ?></p>
                    <ul class="space-y-1.5 text-[12.5px] mb-5">
                        <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <strong class="text-white"><?= number_format((int)$p['max_requests_month']) ?></strong> requests/mes</li>
                        <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <strong class="text-white"><?= (int)$p['max_apps'] ?></strong> app<?= (int)$p['max_apps'] === 1 ? '' : 's' ?></li>
                        <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <strong class="text-white"><?= (int)$p['rate_limit_per_min'] ?></strong> req/min</li>
                        <?php if ((float)$p['overage_price_per_1k'] > 0): ?>
                            <li class="flex items-center gap-2 text-amber-300"><i class="lucide lucide-zap text-[12px]"></i> Overage $<?= number_format((float)$p['overage_price_per_1k'], 4) ?>/1k</li>
                        <?php endif; ?>
                        <?php foreach (array_slice($features, 0, 3) as $f): ?>
                            <li class="flex items-center gap-2 text-slate-300"><i class="lucide lucide-check text-emerald-400 text-[12px]"></i> <?= $e(ucfirst(str_replace('_',' ',$f))) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= $url('/developers/register?plan=' . urlencode($p['slug'])) ?>" class="dev-btn <?= $p['is_featured'] ? 'dev-btn-primary' : 'dev-btn-ghost' ?> w-full">
                        <?= (float)$p['price_monthly'] == 0 ? 'Empezar gratis' : 'Suscribirse' ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-8 text-[12.5px] dev-muted">
            <i class="lucide lucide-info text-[12px]"></i> ¿Necesitas algo personalizado? <a href="<?= $url('/contact') ?>" class="text-sky-300 hover:text-sky-200 underline">Hablemos</a>.
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═════════════════ FAQ ═════════════════ -->
<section class="max-w-[860px] mx-auto px-6 py-20">
    <div class="text-center mb-10">
        <span class="dev-pill mb-3"><i class="lucide lucide-help-circle text-[11px]"></i> FAQ</span>
        <h2 class="text-white font-display font-bold text-[34px] tracking-tight">Preguntas frecuentes</h2>
    </div>

    <div class="space-y-3" x-data="{open:0}">
        <?php
        $faqs = [
            ['¿Cómo funciona el aislamiento por app?', 'Cada app que creas obtiene automáticamente un tenant dedicado en Kydesk. Tus tokens solo acceden a los datos de su app — sin posibilidad de leer otros proyectos tuyos ni de otros developers.'],
            ['¿Qué pasa si supero la cuota mensual?', 'Si tu plan tiene overage habilitado, las requests siguen funcionando y se facturan al precio listado por cada 1.000 extras. Si no, recibes 429 hasta el siguiente ciclo o hasta que mejores el plan.'],
            ['¿Hay rate limits?', 'Sí. Cada plan tiene un rate limit por minuto. Los headers <code class="text-amber-300 font-mono text-[11px]">X-RateLimit-Limit</code> y <code class="text-amber-300 font-mono text-[11px]">X-Quota-Used</code> te informan en tiempo real.'],
            ['¿Puedo cancelar cuando quiera?', 'Sí. Cancelas desde el panel y mantienes acceso hasta el final del periodo facturado. No hay permanencia.'],
            ['¿Soportan webhooks?', 'Sí, en planes Starter+. Suscríbete a eventos como ticket.created, ticket.updated o sla.breach. Reintentos automáticos con backoff exponencial.'],
            ['¿Qué pasa si dejo de usar mi cuenta?', 'Si tu suscripción es free no pasa nada. Si tienes plan de pago y dejas de pagar, suspendemos las llamadas API tras la fecha de vencimiento, pero conservamos tus datos 60 días.'],
        ];
        foreach ($faqs as $i => [$q, $a]): ?>
            <div class="dev-feature !p-0">
                <button @click="open === <?= $i ?> ? open = -1 : open = <?= $i ?>" class="w-full flex items-center justify-between text-left p-5">
                    <span class="font-display font-bold text-white text-[14.5px]"><?= $e($q) ?></span>
                    <i class="lucide lucide-chevron-down text-sky-300 transition-transform" :class="open === <?= $i ?> ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open === <?= $i ?>" x-collapse class="px-5 pb-5 text-[13.5px] dev-muted leading-[1.7] -mt-1"><?= $a ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═════════════════ CTA ═════════════════ -->
<section class="max-w-[1180px] mx-auto px-6 py-20">
    <div class="dev-card dev-glow text-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(600px 300px at 50% 0%, rgba(14,165,233,.18), transparent 70%)"></div>
        <div class="relative">
            <h2 class="text-white font-display font-bold text-[34px] mb-3 tracking-tight">¿Listo para construir?</h2>
            <p class="dev-muted text-[15px] mb-7 max-w-[520px] mx-auto">Crea tu cuenta de developer en menos de 30 segundos. Plan free incluye 10K requests al mes — suficiente para prototipar y lanzar tu primera app.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?= $url('/developers/register') ?>" class="dev-btn dev-btn-primary"><i class="lucide lucide-rocket"></i> Crear cuenta gratis</a>
                <a href="<?= $url('/developers/docs') ?>" class="dev-btn dev-btn-ghost"><i class="lucide lucide-book-open"></i> Ver docs</a>
            </div>
        </div>
    </div>
</section>
