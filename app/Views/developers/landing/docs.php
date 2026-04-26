<section class="max-w-[920px] mx-auto px-6 py-16">
    <span class="dev-pill mb-4"><i class="lucide lucide-book-open text-[11px]"></i> API Reference</span>
    <h1 class="dev-h1 mb-3">Documentación</h1>
    <p class="dev-muted mb-10 text-[15px]">Endpoints, autenticación y ejemplos para construir contra la API de Kydesk.</p>

    <div class="dev-card dev-card-pad mb-6">
        <h2 class="font-display font-bold text-white text-[20px] mb-3 flex items-center gap-2"><i class="lucide lucide-key text-sky-300"></i> Autenticación</h2>
        <p class="dev-muted text-[13.5px] leading-[1.65] mb-3">
            Todas las llamadas requieren un Bearer token. Genera tokens desde el panel de tu app.
        </p>
        <pre class="code-block">Authorization: Bearer kyd_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</pre>
    </div>

    <div class="dev-card dev-card-pad mb-6">
        <h2 class="font-display font-bold text-white text-[20px] mb-3 flex items-center gap-2"><i class="lucide lucide-list text-sky-300"></i> Endpoints</h2>
        <ul class="space-y-1.5 text-[13px] font-mono">
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/me</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/tickets</span></li>
            <li><span class="dev-pill dev-pill-sky">POST</span> <span class="text-slate-300">/api/v1/tickets</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/tickets/{id}</span></li>
            <li><span class="dev-pill dev-pill-amber">PATCH</span> <span class="text-slate-300">/api/v1/tickets/{id}</span></li>
            <li><span class="dev-pill dev-pill-red">DELETE</span> <span class="text-slate-300">/api/v1/tickets/{id}</span></li>
            <li><span class="dev-pill dev-pill-sky">POST</span> <span class="text-slate-300">/api/v1/tickets/{id}/comments</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/categories</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/companies</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/users</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/kb/articles</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/sla</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/automations</span></li>
            <li><span class="dev-pill dev-pill-emerald">GET</span> <span class="text-slate-300">/api/v1/stats</span></li>
        </ul>
    </div>

    <div class="dev-card dev-card-pad mb-6">
        <h2 class="font-display font-bold text-white text-[20px] mb-3 flex items-center gap-2"><i class="lucide lucide-code-2 text-sky-300"></i> Ejemplo: crear ticket</h2>
        <pre class="code-block"><span class="k">curl</span> -X POST https://kydesk.kyrosrd.com/api/v1/tickets \
  -H <span class="s">"Authorization: Bearer kyd_xxxx"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d '{
    <span class="n">"subject"</span>: <span class="s">"Falla de servidor"</span>,
    <span class="n">"description"</span>: <span class="s">"El servidor X no responde"</span>,
    <span class="n">"priority"</span>: <span class="s">"urgent"</span>,
    <span class="n">"requester_email"</span>: <span class="s">"jane@example.com"</span>,
    <span class="n">"channel"</span>: <span class="s">"portal"</span>
  }'</pre>
    </div>

    <div class="dev-card dev-card-pad mb-6">
        <h2 class="font-display font-bold text-white text-[20px] mb-3 flex items-center gap-2"><i class="lucide lucide-shield text-sky-300"></i> Aislamiento por app</h2>
        <p class="dev-muted text-[13.5px] leading-[1.65]">
            Cada app que crees tiene su propio workspace dedicado. Tus tokens solo acceden a los datos de su app — no se mezcla con otras apps tuyas ni con los tenants de Kydesk.
        </p>
    </div>

    <div class="text-center mt-10">
        <a href="<?= $url('/developers/register') ?>" class="dev-btn dev-btn-primary">
            <i class="lucide lucide-rocket"></i> Crear cuenta y empezar
        </a>
    </div>
</section>
