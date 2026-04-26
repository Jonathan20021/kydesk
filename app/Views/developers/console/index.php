<?php
$base = rtrim($app->config['app']['url'], '/');
$apiBase = $base . '/api/v1';
$endpoints = [
    'GET /me' => ['method' => 'GET', 'path' => '/me', 'desc' => 'Identidad del token'],
    'GET /health' => ['method' => 'GET', 'path' => '/health', 'desc' => 'Health check (sin auth)'],
    'GET /stats' => ['method' => 'GET', 'path' => '/stats', 'desc' => 'Estadísticas'],
    'GET /search?q=' => ['method' => 'GET', 'path' => '/search?q=ejemplo', 'desc' => 'Búsqueda global'],
    'GET /tickets' => ['method' => 'GET', 'path' => '/tickets?per_page=10', 'desc' => 'Listar tickets'],
    'POST /tickets' => ['method' => 'POST', 'path' => '/tickets', 'desc' => 'Crear ticket', 'body' => "{\n  \"subject\": \"Servidor caído\",\n  \"priority\": \"urgent\",\n  \"requester_email\": \"alice@example.com\"\n}"],
    'GET /tickets/{id}' => ['method' => 'GET', 'path' => '/tickets/1?expand=company,assignee,comments', 'desc' => 'Ticket detalle'],
    'PATCH /tickets/{id}' => ['method' => 'PATCH', 'path' => '/tickets/1', 'desc' => 'Actualizar ticket', 'body' => "{\n  \"status\": \"in_progress\",\n  \"priority\": \"high\"\n}"],
    'POST /tickets/{id}/comments' => ['method' => 'POST', 'path' => '/tickets/1/comments', 'desc' => 'Añadir comentario', 'body' => "{\n  \"body\": \"Trabajando en ello.\"\n}"],
    'GET /companies' => ['method' => 'GET', 'path' => '/companies', 'desc' => 'Listar empresas'],
    'POST /companies' => ['method' => 'POST', 'path' => '/companies', 'desc' => 'Crear empresa', 'body' => "{\n  \"name\": \"Acme Corp\",\n  \"industry\": \"SaaS\"\n}"],
    'GET /categories' => ['method' => 'GET', 'path' => '/categories', 'desc' => 'Listar categorías'],
    'GET /users' => ['method' => 'GET', 'path' => '/users', 'desc' => 'Listar usuarios'],
    'GET /kb/articles' => ['method' => 'GET', 'path' => '/kb/articles', 'desc' => 'Listar KB'],
    'GET /sla' => ['method' => 'GET', 'path' => '/sla', 'desc' => 'Políticas SLA'],
    'GET /automations' => ['method' => 'GET', 'path' => '/automations', 'desc' => 'Listar automations'],
    'GET /assets' => ['method' => 'GET', 'path' => '/assets', 'desc' => 'Listar activos'],
];
?>

<div class="dev-card dev-card-pad" style="border-color:rgba(245,158,11,.30); background:rgba(245,158,11,.04)">
    <div class="flex items-start gap-3">
        <i class="lucide lucide-flask-conical text-amber-300 mt-1"></i>
        <div>
            <div class="font-display font-bold text-white text-[14.5px]">API Console — para developers</div>
            <p class="text-[12.5px] text-slate-300 mt-1 leading-[1.6]">Las llamadas se ejecutan <strong>desde tu navegador directo a la API</strong> con el token que selecciones. Las respuestas son reales — afectan los datos del workspace de la app elegida. Úsalo en <strong>environment=development</strong> para probar antes de codear.</p>
        </div>
    </div>
</div>

<div class="dev-card" x-data="{
    method: 'GET',
    path: '/tickets',
    body: '',
    token: '',
    response: '',
    status: '',
    duration: 0,
    headers: {},
    loading: false,
    async send() {
        if (!this.token) { alert('Selecciona o pega un token primero'); return; }
        this.loading = true; this.response = ''; this.status = ''; this.duration = 0; this.headers = {};
        const start = performance.now();
        try {
            const opts = {
                method: this.method,
                headers: {
                    'Authorization': 'Bearer ' + this.token,
                    'Content-Type': 'application/json',
                    'Idempotency-Key': crypto.randomUUID(),
                },
            };
            if (['POST','PATCH','PUT','DELETE'].includes(this.method) && this.body.trim()) {
                opts.body = this.body;
            }
            const resp = await fetch('<?= $apiBase ?>' + this.path, opts);
            this.duration = Math.round(performance.now() - start);
            this.status = resp.status;
            resp.headers.forEach((v, k) => { this.headers[k] = v; });
            const text = await resp.text();
            try { this.response = JSON.stringify(JSON.parse(text), null, 2); } catch { this.response = text; }
        } catch (e) {
            this.response = 'ERROR: ' + e.message;
            this.status = 0;
        } finally {
            this.loading = false;
        }
    },
    setEndpoint(method, path, body) {
        this.method = method;
        this.path = path;
        this.body = body || '';
    },
    curl() {
        let c = `curl -X ${this.method} '<?= $apiBase ?>${this.path}' \\\n  -H 'Authorization: Bearer ${this.token || 'kyd_xxxx'}' \\\n  -H 'Content-Type: application/json'`;
        if (['POST','PATCH','PUT','DELETE'].includes(this.method) && this.body.trim()) {
            c += ` \\\n  -d '${this.body.replace(/\n\s*/g, ' ')}'`;
        }
        return c;
    }
}">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Construye tu request</h2>
            <p class="text-[12px] text-slate-400">Elige un token, ajusta el endpoint y ejecuta</p>
        </div>
    </div>

    <div class="p-5 grid lg:grid-cols-3 gap-4 border-b" style="border-color:rgba(56,189,248,.06)">
        <div>
            <label class="dev-label">Token API</label>
            <select x-model="token" class="dev-input">
                <option value="">— Selecciona un token —</option>
                <?php foreach ($tokens as $t): ?>
                    <option value=""><?= $e($t['name']) ?> · <?= $e($t['token_preview']) ?> <?= $t['app_name'] ? '(' . $e($t['app_name']) . ')' : '' ?> · pega manualmente</option>
                <?php endforeach; ?>
            </select>
            <input x-model="token" type="text" class="dev-input mt-2 font-mono" placeholder="Pega tu kyd_xxxx aquí" style="font-size:11.5px">
            <?php if (empty($tokens)): ?>
                <p class="text-[11.5px] text-amber-300 mt-2">No tienes tokens. <a href="<?= $url('/developers/apps/create') ?>" class="dev-link">Crea una app</a> para generar uno.</p>
            <?php endif; ?>
        </div>

        <div>
            <label class="dev-label">Método</label>
            <select x-model="method" class="dev-input">
                <option>GET</option><option>POST</option><option>PATCH</option><option>PUT</option><option>DELETE</option>
            </select>
        </div>

        <div>
            <label class="dev-label">Ruta (sin base URL)</label>
            <input x-model="path" type="text" class="dev-input font-mono" placeholder="/tickets" style="font-size:12px">
        </div>

        <div class="lg:col-span-2">
            <label class="dev-label">Body JSON (POST/PATCH)</label>
            <textarea x-model="body" class="dev-textarea font-mono" rows="6" placeholder='{ "subject": "..." }' style="font-size:12px; line-height:1.6"></textarea>
        </div>

        <div>
            <label class="dev-label">Acciones</label>
            <button @click="send()" class="dev-btn dev-btn-primary w-full mb-2" :disabled="loading">
                <i class="lucide lucide-send text-[13px]"></i>
                <span x-show="!loading">Ejecutar</span>
                <span x-show="loading">Ejecutando...</span>
            </button>
            <button @click="navigator.clipboard.writeText(curl()); $event.target.textContent='✓ cURL copiado'; setTimeout(()=>$event.target.innerHTML='<i class=\'lucide lucide-copy text-[13px]\'></i> Copiar como cURL', 2000); window.lucide && window.lucide.createIcons()" class="dev-btn dev-btn-soft w-full"><i class="lucide lucide-copy text-[13px]"></i> Copiar como cURL</button>
        </div>
    </div>

    <div class="p-5 grid lg:grid-cols-2 gap-4">
        <div>
            <div class="flex items-center justify-between mb-2">
                <div class="text-[12px] uppercase font-bold tracking-[0.12em] text-slate-500">Respuesta</div>
                <div class="flex items-center gap-2 text-[11.5px]" x-show="status">
                    <span class="dev-pill" :class="status >= 500 ? 'dev-pill-red' : status >= 400 ? 'dev-pill-amber' : status >= 200 ? 'dev-pill-emerald' : 'dev-pill-gray'" x-text="status"></span>
                    <span class="text-slate-400" x-text="duration + 'ms'"></span>
                </div>
            </div>
            <pre class="dev-code" style="min-height:300px; max-height:500px; overflow:auto; font-size:11.5px; white-space:pre-wrap" x-text="response || 'Aún no has ejecutado ninguna request...'"></pre>
        </div>
        <div>
            <div class="text-[12px] uppercase font-bold tracking-[0.12em] text-slate-500 mb-2">Response headers</div>
            <pre class="dev-code" style="min-height:300px; max-height:500px; overflow:auto; font-size:11px"><template x-for="(v, k) in headers" :key="k"><span><span class="text-sky-300" x-text="k"></span>: <span x-text="v"></span>
</span></template><template x-if="!Object.keys(headers).length"><span class="text-slate-500">Headers aparecerán aquí tras ejecutar...</span></template></pre>
        </div>
    </div>

    <!-- Snippets -->
    <div class="border-t" style="border-color:rgba(56,189,248,.06)">
        <div class="dev-card-head !border-b-0"><h3 class="font-display font-bold text-white text-[14.5px]">Snippets rápidos</h3></div>
        <div class="px-5 pb-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <?php foreach ($endpoints as $label => $ep):
                $bodyJson = isset($ep['body']) ? json_encode($ep['body']) : "''"; ?>
                <button type="button" @click="setEndpoint('<?= $ep['method'] ?>', '<?= $ep['path'] ?>', <?= $bodyJson ?>)" class="ai-prompt-card !p-3 text-left hover:border-sky-400/40">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="dev-pill !text-[9.5px] <?= $ep['method'] === 'GET' ? 'dev-pill-emerald' : ($ep['method'] === 'POST' ? 'dev-pill-sky' : 'dev-pill-amber') ?>"><?= $ep['method'] ?></span>
                        <span class="font-mono text-[11.5px] text-slate-200 truncate"><?= $e($ep['path']) ?></span>
                    </div>
                    <div class="text-[11px] text-slate-400 truncate"><?= $e($ep['desc']) ?></div>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
