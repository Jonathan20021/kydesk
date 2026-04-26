<?php
$base = rtrim($app->config['app']['url'], '/');
$apiBase = $base . '/api/v1';
?>
<style>
.docs-layout { display:grid; grid-template-columns: 260px 1fr; gap: 32px; max-width: 1280px; margin: 0 auto; padding: 32px 24px 96px; }
.docs-sidebar { position: sticky; top: 80px; align-self: start; max-height: calc(100vh - 100px); overflow-y: auto; padding-right: 8px; }
.docs-sidebar::-webkit-scrollbar { width: 4px; }
.docs-sidebar::-webkit-scrollbar-thumb { background: rgba(56,189,248,.20); border-radius: 4px; }
.docs-sec { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .14em; color: #475569; padding: 16px 8px 6px; }
.docs-link { display: block; padding: 6px 10px; border-radius: 8px; font-size: 13px; color: #94a3b8; transition: all .12s; border-left: 2px solid transparent; }
.docs-link:hover { color: #e2e8f0; background: rgba(56,189,248,.05); }
.docs-link.active { color: #7dd3fc; background: rgba(14,165,233,.08); border-left-color: #0ea5e9; font-weight: 600; }
.docs-content { min-width: 0; }
.docs-content h2 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:30px; color:#fff; letter-spacing:-.02em; margin: 48px 0 12px; padding-top: 12px; scroll-margin-top: 100px; }
.docs-content h2:first-child { margin-top: 0; }
.docs-content h3 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:18px; color:#e2e8f0; letter-spacing:-.015em; margin: 32px 0 10px; scroll-margin-top: 100px; }
.docs-content p { color:#94a3b8; line-height: 1.75; margin: 0 0 14px; font-size: 14.5px; }
.docs-content code { background: rgba(56,189,248,.10); color: #bae6fd; padding: 2px 6px; border-radius: 5px; font-size: 12.5px; font-family: 'Geist Mono', monospace; }
.docs-content ul { color:#94a3b8; line-height: 1.85; padding-left: 24px; margin: 0 0 14px; }
.docs-content ul li { margin-bottom: 4px; }
.docs-content ul li code { color: #bae6fd; }
.docs-content table { width: 100%; border-collapse: collapse; margin: 14px 0; font-size: 13px; }
.docs-content table th { text-align: left; padding: 10px 14px; border-bottom: 1px solid rgba(56,189,248,.15); color: #cbd5e1; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: .08em; }
.docs-content table td { padding: 10px 14px; border-bottom: 1px solid rgba(56,189,248,.06); color: #94a3b8; vertical-align: top; }
.docs-content table td code { font-size: 11.5px; }
.docs-endpoint { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: rgba(15,16,24,.6); border: 1px solid rgba(56,189,248,.10); border-radius: 10px; margin: 8px 0; font-family: 'Geist Mono', monospace; }
.docs-method { padding: 3px 9px; border-radius: 5px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0; }
.method-get    { background: rgba(16,185,129,.18); color: #6ee7b7; }
.method-post   { background: rgba(14,165,233,.18); color: #7dd3fc; }
.method-patch  { background: rgba(245,158,11,.18); color: #fcd34d; }
.method-put    { background: rgba(245,158,11,.18); color: #fcd34d; }
.method-delete { background: rgba(239,68,68,.18); color: #fca5a5; }
.docs-callout { border-radius: 14px; padding: 16px 18px; margin: 18px 0; border: 1px solid rgba(56,189,248,.15); background: rgba(14,165,233,.04); color: #cbd5e1; font-size: 13.5px; line-height: 1.65; }
.docs-callout strong { color: #fff; }
.docs-callout-warn { border-color: rgba(245,158,11,.30); background: rgba(245,158,11,.05); }
.docs-callout-warn strong { color: #fcd34d; }
.docs-tabs { display:flex; border-bottom: 1px solid rgba(56,189,248,.10); margin-bottom: 0; font-family: 'Geist Mono', monospace; font-size: 12px; }
.docs-tab { padding: 8px 14px; border-bottom: 2px solid transparent; cursor: pointer; color: #64748b; transition: all .12s; background: transparent; border-left: none; border-right: none; border-top: none; }
.docs-tab:hover { color: #cbd5e1; }
.docs-tab.active { color: #7dd3fc; border-bottom-color: #0ea5e9; }
@media (max-width: 900px) { .docs-layout { grid-template-columns: 1fr; padding: 20px 16px 60px; } .docs-sidebar { position: static; max-height: none; } }
</style>

<div class="docs-layout">

    <aside class="docs-sidebar" x-data="{ active: window.location.hash || '#introduction' }">
        <input type="search" placeholder="Buscar en docs..." class="dev-input mb-3" style="height:38px; font-size:12.5px" oninput="filterDocs(this.value)" id="docsSearch">

        <div class="docs-sec">Empieza aquí</div>
        <a href="#introduction" class="docs-link" :class="active === '#introduction' ? 'active' : ''" @click="active='#introduction'">Introducción</a>
        <a href="#authentication" class="docs-link" :class="active === '#authentication' ? 'active' : ''" @click="active='#authentication'">Autenticación</a>
        <a href="#quickstart" class="docs-link" :class="active === '#quickstart' ? 'active' : ''" @click="active='#quickstart'">Quickstart</a>
        <a href="#errors" class="docs-link" :class="active === '#errors' ? 'active' : ''" @click="active='#errors'">Manejo de errores</a>
        <a href="#pagination" class="docs-link" :class="active === '#pagination' ? 'active' : ''" @click="active='#pagination'">Paginación</a>
        <a href="#filtering" class="docs-link" :class="active === '#filtering' ? 'active' : ''" @click="active='#filtering'">Filtros y orden</a>
        <a href="#expansion" class="docs-link" :class="active === '#expansion' ? 'active' : ''" @click="active='#expansion'">Expansión</a>
        <a href="#idempotency" class="docs-link" :class="active === '#idempotency' ? 'active' : ''" @click="active='#idempotency'">Idempotencia</a>
        <a href="#rate-limits" class="docs-link" :class="active === '#rate-limits' ? 'active' : ''" @click="active='#rate-limits'">Rate limits & cuotas</a>
        <a href="#webhooks" class="docs-link" :class="active === '#webhooks' ? 'active' : ''" @click="active='#webhooks'">Webhooks</a>

        <div class="docs-sec">Recursos</div>
        <a href="#tickets" class="docs-link">Tickets</a>
        <a href="#comments" class="docs-link">Comentarios</a>
        <a href="#companies" class="docs-link">Companies</a>
        <a href="#categories" class="docs-link">Categories</a>
        <a href="#users" class="docs-link">Users</a>
        <a href="#kb" class="docs-link">Knowledge Base</a>
        <a href="#sla" class="docs-link">SLA</a>
        <a href="#automations" class="docs-link">Automations</a>
        <a href="#assets" class="docs-link">Assets</a>
        <a href="#search" class="docs-link">Búsqueda global</a>
        <a href="#stats" class="docs-link">Estadísticas</a>

        <div class="docs-sec">Herramientas</div>
        <a href="<?= $url('/developers/console') ?>" class="docs-link"><i class="lucide lucide-terminal text-[12px] mr-1"></i> API Console</a>
        <a href="<?= $url('/developers/ai') ?>" class="docs-link"><i class="lucide lucide-bot text-[12px] mr-1"></i> AI Studio</a>
        <a href="<?= $apiBase ?>/openapi.json" target="_blank" class="docs-link"><i class="lucide lucide-file-json text-[12px] mr-1"></i> OpenAPI spec</a>
        <a href="<?= $apiBase ?>/postman.json" class="docs-link"><i class="lucide lucide-download text-[12px] mr-1"></i> Postman collection</a>
    </aside>

    <main class="docs-content">

        <h2 id="introduction">Documentación API · v1</h2>
        <p>API REST de Kydesk Helpdesk. JSON sobre HTTPS, Bearer tokens, paginación uniforme, idempotencia y cuotas por plan. Diseñada para que construyas integraciones, automatizaciones y aplicaciones encima de tu workspace de helpdesk.</p>
        <div class="docs-callout">
            <strong>Base URL:</strong> <code><?= $e($apiBase) ?></code><br>
            Todas las llamadas usan TLS. Tokens expirados o inválidos devuelven <code>401</code>. Versionado en URL (<code>/v1</code>) y en header <code>X-API-Version</code>.
        </div>

        <h2 id="authentication">Autenticación</h2>
        <p>La API usa Bearer tokens. Genera un token desde <a href="<?= $url('/developers/apps') ?>" class="dev-link">tu panel de apps</a> y envíalo en el header <code>Authorization</code>:</p>
        <pre class="code-block">Authorization: Bearer kyd_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</pre>
        <p>Hay dos tipos de token:</p>
        <table>
            <thead><tr><th>Tipo</th><th>Origen</th><th>Cuotas</th></tr></thead>
            <tbody>
                <tr><td><strong>Developer</strong></td><td>Generado en <code>/developers/apps/{id}</code></td><td>Aplica el plan dev (rate limit + quota mensual)</td></tr>
                <tr><td><strong>Tenant</strong></td><td>Generado en <code>/t/{slug}/api-docs</code></td><td>Aplica las cuotas de la licencia del tenant</td></tr>
            </tbody>
        </table>
        <h3>Scopes</h3>
        <p>Cada token tiene scopes (<code>read</code>, <code>write</code>, <code>*</code>). Las llamadas <code>GET</code> requieren <code>read</code>. Las llamadas que modifican datos (<code>POST</code>, <code>PATCH</code>, <code>DELETE</code>) requieren <code>write</code>.</p>

        <h2 id="quickstart">Quickstart</h2>
        <p>Crea tu primer ticket en menos de un minuto.</p>
        <div x-data="{tab:'curl'}">
            <div class="docs-tabs">
                <?php foreach (['curl'=>'cURL','node'=>'Node.js','python'=>'Python','php'=>'PHP','go'=>'Go'] as $k=>$lbl): ?>
                    <button @click="tab='<?= $k ?>'" :class="tab==='<?= $k ?>' ? 'active' : ''" class="docs-tab"><?= $lbl ?></button>
                <?php endforeach; ?>
            </div>
            <pre x-show="tab==='curl'" class="code-block !rounded-t-none !mt-0"><span class="k">curl</span> -X POST <?= $apiBase ?>/tickets \
  -H <span class="s">"Authorization: Bearer kyd_xxxx"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d '{
    <span class="n">"subject"</span>: <span class="s">"Servidor caído"</span>,
    <span class="n">"priority"</span>: <span class="s">"urgent"</span>,
    <span class="n">"requester_email"</span>: <span class="s">"alice@example.com"</span>
  }'</pre>
            <pre x-show="tab==='node'" class="code-block !rounded-t-none !mt-0" x-cloak><span class="k">const</span> resp = <span class="k">await</span> fetch(<span class="s">'<?= $apiBase ?>/tickets'</span>, {
  method: <span class="s">'POST'</span>,
  headers: {
    <span class="s">'Authorization'</span>: <span class="s">'Bearer kyd_xxxx'</span>,
    <span class="s">'Content-Type'</span>: <span class="s">'application/json'</span>,
  },
  body: JSON.stringify({
    subject: <span class="s">'Servidor caído'</span>,
    priority: <span class="s">'urgent'</span>,
    requester_email: <span class="s">'alice@example.com'</span>,
  }),
});
<span class="k">const</span> { data: ticket } = <span class="k">await</span> resp.json();
console.log(ticket.code); <span class="c">// TK-01-00042</span></pre>
            <pre x-show="tab==='python'" class="code-block !rounded-t-none !mt-0" x-cloak><span class="k">import</span> requests

resp = requests.post(
    <span class="s">"<?= $apiBase ?>/tickets"</span>,
    headers={<span class="s">"Authorization"</span>: <span class="s">"Bearer kyd_xxxx"</span>},
    json={
        <span class="n">"subject"</span>: <span class="s">"Servidor caído"</span>,
        <span class="n">"priority"</span>: <span class="s">"urgent"</span>,
        <span class="n">"requester_email"</span>: <span class="s">"alice@example.com"</span>,
    },
)
ticket = resp.json()[<span class="s">"data"</span>]
print(ticket[<span class="s">"code"</span>])  <span class="c"># TK-01-00042</span></pre>
            <pre x-show="tab==='php'" class="code-block !rounded-t-none !mt-0" x-cloak><span class="k">$ch</span> = curl_init(<span class="s">"<?= $apiBase ?>/tickets"</span>);
curl_setopt_array(<span class="k">$ch</span>, [
    CURLOPT_RETURNTRANSFER => <span class="k">true</span>,
    CURLOPT_HTTPHEADER => [
        <span class="s">"Authorization: Bearer kyd_xxxx"</span>,
        <span class="s">"Content-Type: application/json"</span>,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        <span class="n">"subject"</span> => <span class="s">"Servidor caído"</span>,
        <span class="n">"priority"</span> => <span class="s">"urgent"</span>,
    ]),
]);
<span class="k">$resp</span> = json_decode(curl_exec(<span class="k">$ch</span>), <span class="k">true</span>);
echo <span class="k">$resp</span>[<span class="s">'data'</span>][<span class="s">'code'</span>];</pre>
            <pre x-show="tab==='go'" class="code-block !rounded-t-none !mt-0" x-cloak><span class="k">import</span> (
    <span class="s">"bytes"</span>
    <span class="s">"encoding/json"</span>
    <span class="s">"net/http"</span>
)

body, _ := json.Marshal(map[<span class="k">string</span>]<span class="k">any</span>{
    <span class="s">"subject"</span>: <span class="s">"Servidor caído"</span>,
    <span class="s">"priority"</span>: <span class="s">"urgent"</span>,
})
req, _ := http.NewRequest(<span class="s">"POST"</span>, <span class="s">"<?= $apiBase ?>/tickets"</span>, bytes.NewReader(body))
req.Header.Set(<span class="s">"Authorization"</span>, <span class="s">"Bearer kyd_xxxx"</span>)
req.Header.Set(<span class="s">"Content-Type"</span>, <span class="s">"application/json"</span>)
resp, _ := http.DefaultClient.Do(req)
<span class="k">defer</span> resp.Body.Close()</pre>
        </div>

        <h2 id="errors">Manejo de errores</h2>
        <p>Todas las respuestas de error siguen el mismo formato:</p>
        <pre class="code-block">{
  <span class="n">"error"</span>: {
    <span class="n">"type"</span>: <span class="s">"validation_error"</span>,
    <span class="n">"message"</span>: <span class="s">"Faltan campos requeridos: subject"</span>,
    <span class="n">"status"</span>: 422,
    <span class="n">"request_id"</span>: <span class="s">"a1b2c3d4e5f6"</span>,
    <span class="n">"missing"</span>: [<span class="s">"subject"</span>]
  }
}</pre>
        <table>
            <thead><tr><th>type</th><th>HTTP</th><th>Cuándo ocurre</th></tr></thead>
            <tbody>
                <tr><td><code>unauthorized</code></td><td>401</td><td>Token ausente o inválido</td></tr>
                <tr><td><code>insufficient_scope</code></td><td>403</td><td>El token no tiene el scope requerido</td></tr>
                <tr><td><code>developer_suspended</code></td><td>403</td><td>Cuenta de developer suspendida</td></tr>
                <tr><td><code>app_suspended</code></td><td>403</td><td>App suspendida o archivada</td></tr>
                <tr><td><code>no_subscription</code></td><td>403</td><td>Sin suscripción activa</td></tr>
                <tr><td><code>not_found</code></td><td>404</td><td>Recurso no existe</td></tr>
                <tr><td><code>precondition_failed</code></td><td>412</td><td>If-Match no coincide</td></tr>
                <tr><td><code>validation_error</code></td><td>422</td><td>Body inválido o campos requeridos faltantes</td></tr>
                <tr><td><code>quota_exceeded</code></td><td>429</td><td>Cuota mensual agotada</td></tr>
                <tr><td><code>rate_limit_exceeded</code></td><td>429</td><td>Rate limit superado · revisa <code>Retry-After</code></td></tr>
            </tbody>
        </table>

        <h2 id="pagination">Paginación</h2>
        <p>Todos los endpoints de listado soportan paginación offset:</p>
        <ul>
            <li><code>?page=1&per_page=25</code> — basado en página (default)</li>
            <li><code>?offset=50&limit=25</code> — basado en offset</li>
            <li><code>per_page</code> max <strong>100</strong></li>
        </ul>
        <p>La respuesta incluye <code>meta</code> y <code>links</code> para navegación:</p>
        <pre class="code-block">{
  <span class="n">"data"</span>: [...],
  <span class="n">"meta"</span>: { <span class="n">"total"</span>: 482, <span class="n">"limit"</span>: 25, <span class="n">"offset"</span>: 0, <span class="n">"has_more"</span>: <span class="k">true</span> },
  <span class="n">"links"</span>: { <span class="n">"first"</span>: <span class="s">"..."</span>, <span class="n">"next"</span>: <span class="s">"..."</span>, <span class="n">"prev"</span>: <span class="k">null</span>, <span class="n">"last"</span>: <span class="s">"..."</span> }
}</pre>

        <h2 id="filtering">Filtros y orden</h2>
        <p>Cada listado expone filtros específicos. Por ejemplo, en <code>/tickets</code>:</p>
        <ul>
            <li><code>?status=open,in_progress</code></li>
            <li><code>?priority=urgent</code></li>
            <li><code>?q=servidor</code> — búsqueda en subject/code/description</li>
            <li><code>?assigned_to=7</code></li>
            <li><code>?created_after=2025-01-01</code></li>
        </ul>
        <p>Orden con <code>?sort=campo</code>. Prefijo <code>-</code> para descendente:</p>
        <pre class="code-block">GET /tickets?sort=-created_at
GET /companies?sort=name</pre>

        <h2 id="expansion">Expansión de relaciones</h2>
        <p>Usa <code>?expand=</code> (o <code>?include=</code>) para incluir relaciones en línea. Ahorra round-trips:</p>
        <pre class="code-block">GET /tickets/42?expand=company,assignee,comments</pre>
        <table>
            <thead><tr><th>Recurso</th><th>Relaciones disponibles</th></tr></thead>
            <tbody>
                <tr><td>tickets</td><td><code>company</code>, <code>category</code>, <code>assignee</code>, <code>comments</code></td></tr>
                <tr><td>companies</td><td><code>contacts</code>, <code>tickets</code></td></tr>
                <tr><td>kb articles</td><td><code>category</code></td></tr>
            </tbody>
        </table>
        <p>Selección de campos con <code>?fields=id,subject,status</code>.</p>

        <h2 id="idempotency">Idempotencia</h2>
        <p>Los endpoints <code>POST</code> aceptan el header <code>Idempotency-Key</code>. Si reenvías la misma key dentro de 24h, recibes la respuesta original sin crear duplicados.</p>
        <pre class="code-block">curl -X POST <?= $apiBase ?>/tickets \
  -H <span class="s">"Authorization: Bearer kyd_xxxx"</span> \
  -H <span class="s">"Idempotency-Key: a-uuid-único-por-operación"</span> \
  -d '{ "subject": "..." }'</pre>
        <div class="docs-callout">
            <strong>Recomendación:</strong> usa un UUID v4 generado en el cliente. Reintentos con la misma key son seguros — perfecto para webhooks o jobs.
        </div>

        <h2 id="rate-limits">Rate limits & cuotas</h2>
        <p>Cada token de developer aplica los límites de su plan suscrito. Cada respuesta incluye headers informativos:</p>
        <table>
            <thead><tr><th>Header</th><th>Significado</th></tr></thead>
            <tbody>
                <tr><td><code>X-RateLimit-Limit</code></td><td>Requests permitidos por minuto</td></tr>
                <tr><td><code>X-Quota-Used</code></td><td>Requests consumidos este mes</td></tr>
                <tr><td><code>X-Quota-Limit</code></td><td>Cuota mensual del plan</td></tr>
                <tr><td><code>X-Quota-Pct</code></td><td>% de cuota usada</td></tr>
                <tr><td><code>X-Quota-Warning</code></td><td><code>approaching-limit</code> al pasar 80%</td></tr>
                <tr><td><code>Retry-After</code></td><td>Segundos a esperar tras un 429</td></tr>
            </tbody>
        </table>
        <p>Si superas el rate limit, recibes <code>429 rate_limit_exceeded</code>. Si superas la cuota mensual, recibes <code>429 quota_exceeded</code> (o sigue funcionando con header <code>X-Quota-Status: overage</code> si tu plan tiene overage habilitado).</p>

        <h2 id="webhooks">Webhooks</h2>
        <p>Suscríbete a eventos para que Kydesk te notifique en tiempo real. Configúralos desde <a href="<?= $url('/developers/webhooks') ?>" class="dev-link">tu panel</a>.</p>
        <h3>Eventos disponibles</h3>
        <ul>
            <li><code>ticket.created</code></li>
            <li><code>ticket.updated</code></li>
            <li><code>ticket.assigned</code></li>
            <li><code>ticket.resolved</code></li>
            <li><code>ticket.escalated</code></li>
            <li><code>sla.breach</code></li>
            <li><code>comment.created</code></li>
        </ul>
        <h3>Verificación</h3>
        <p>Cada webhook se firma con HMAC-SHA256 usando tu secret. Verifica el header <code>X-Kydesk-Signature</code>:</p>
        <pre class="code-block"><span class="c">// Node.js</span>
<span class="k">const</span> crypto = <span class="k">require</span>(<span class="s">'crypto'</span>);
<span class="k">const</span> sig = req.headers[<span class="s">'x-kydesk-signature'</span>];
<span class="k">const</span> expected = crypto.createHmac(<span class="s">'sha256'</span>, secret).update(rawBody).digest(<span class="s">'hex'</span>);
<span class="k">if</span> (sig !== expected) <span class="k">throw new</span> Error(<span class="s">'Invalid signature'</span>);</pre>
        <h3>Reintentos</h3>
        <p>Reintentos exponenciales (1m, 5m, 30m, 1h, 6h) ante respuestas distintas a 2xx. Después de 5 fallos se desactiva el webhook.</p>

        <h2 id="tickets">Tickets</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/tickets</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/tickets</div>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/tickets/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/tickets/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/tickets/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/tickets/{id}/assign</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/tickets/{id}/escalate</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/tickets/batch</div>
        <p>Campos del recurso:</p>
        <table>
            <thead><tr><th>Campo</th><th>Tipo</th><th>Notas</th></tr></thead>
            <tbody>
                <tr><td><code>subject</code></td><td>string</td><td>Requerido al crear</td></tr>
                <tr><td><code>description</code></td><td>string</td><td>Markdown soportado</td></tr>
                <tr><td><code>status</code></td><td>enum</td><td>open · in_progress · on_hold · resolved · closed</td></tr>
                <tr><td><code>priority</code></td><td>enum</td><td>low · medium · high · urgent</td></tr>
                <tr><td><code>channel</code></td><td>enum</td><td>portal · email · phone · chat · internal</td></tr>
                <tr><td><code>requester_email</code></td><td>string</td><td>Email del solicitante</td></tr>
                <tr><td><code>company_id</code></td><td>integer</td><td>FK opcional</td></tr>
                <tr><td><code>category_id</code></td><td>integer</td><td>FK opcional</td></tr>
                <tr><td><code>assigned_to</code></td><td>integer</td><td>User ID</td></tr>
                <tr><td><code>tags</code></td><td>string</td><td>Lista separada por comas</td></tr>
            </tbody>
        </table>

        <h2 id="comments">Comentarios</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/tickets/{id}/comments</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/tickets/{id}/comments</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/tickets/{id}/comments/{cid}</div>

        <h2 id="companies">Companies</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/companies</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/companies</div>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/companies/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/companies/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/companies/{id}</div>

        <h2 id="categories">Categories</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/categories</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/categories</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/categories/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/categories/{id}</div>

        <h2 id="users">Users</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/users</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/users</div>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/users/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/users/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/users/{id}</div>

        <h2 id="kb">Knowledge Base</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/kb/articles</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/kb/articles</div>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/kb/articles/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/kb/articles/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/kb/articles/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/kb/categories</div>

        <h2 id="sla">SLA</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/sla</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/sla</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/sla/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/sla/{id}</div>

        <h2 id="automations">Automations</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/automations</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/automations</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/automations/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/automations/{id}</div>
        <p>Triggers disponibles: <code>ticket.created</code>, <code>ticket.updated</code>, <code>ticket.sla_breach</code>, <code>ticket.escalated</code>, <code>ticket.resolved</code>.</p>

        <h2 id="assets">Assets</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/assets</div>
        <div class="docs-endpoint"><span class="docs-method method-post">POST</span> /api/v1/assets</div>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/assets/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-patch">PATCH</span> /api/v1/assets/{id}</div>
        <div class="docs-endpoint"><span class="docs-method method-delete">DELETE</span> /api/v1/assets/{id}</div>

        <h2 id="search">Búsqueda global</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/search?q=consulta</div>
        <p>Devuelve resultados agrupados por recurso (tickets, companies, users, kb_articles).</p>

        <h2 id="stats">Estadísticas</h2>
        <div class="docs-endpoint"><span class="docs-method method-get">GET</span> /api/v1/stats</div>
        <p>Métricas agregadas: tickets por status/priority, SLA compliance, CSAT, conteos de recursos.</p>

        <div class="docs-callout" style="margin-top: 64px">
            <strong>¿Algo no funciona?</strong> Escribe a <a href="mailto:developers@kyrosrd.com" class="dev-link">developers@kyrosrd.com</a> o usa el <a href="<?= $url('/developers/console') ?>" class="dev-link">API Console</a> para diagnosticar.
        </div>

    </main>
</div>

<script>
function filterDocs(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.docs-link').forEach(el => {
        if (!q) { el.style.display = ''; return; }
        const txt = el.textContent.toLowerCase();
        el.style.display = txt.includes(q) ? '' : 'none';
    });
}
// Highlight active section on scroll
window.addEventListener('scroll', () => {
    const sections = document.querySelectorAll('.docs-content h2[id]');
    let current = '';
    sections.forEach(s => {
        if (s.getBoundingClientRect().top < 120) current = '#' + s.id;
    });
    if (current) {
        document.querySelectorAll('.docs-link').forEach(a => a.classList.toggle('active', a.getAttribute('href') === current));
    }
}, { passive: true });
</script>
