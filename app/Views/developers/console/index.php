<?php
$base = rtrim($app->config['app']['url'], '/');
$apiBase = $base . '/api/v1';
$endpoints = [
    'GET /me'             => ['method' => 'GET', 'path' => '/me', 'desc' => 'Identidad del token'],
    'GET /health'         => ['method' => 'GET', 'path' => '/health', 'desc' => 'Health check (sin auth)'],
    'GET /stats'          => ['method' => 'GET', 'path' => '/stats', 'desc' => 'Estadísticas'],
    'GET /search?q='      => ['method' => 'GET', 'path' => '/search?q=ejemplo', 'desc' => 'Búsqueda global'],
    'GET /tickets'        => ['method' => 'GET', 'path' => '/tickets?per_page=10', 'desc' => 'Listar tickets'],
    'POST /tickets'       => ['method' => 'POST', 'path' => '/tickets', 'desc' => 'Crear ticket', 'body' => "{\n  \"subject\": \"Servidor caído\",\n  \"priority\": \"urgent\",\n  \"requester_email\": \"alice@example.com\"\n}"],
    'GET /tickets/{id}'   => ['method' => 'GET', 'path' => '/tickets/1?expand=company,assignee,comments', 'desc' => 'Ticket detalle'],
    'PATCH /tickets/{id}' => ['method' => 'PATCH', 'path' => '/tickets/1', 'desc' => 'Actualizar ticket', 'body' => "{\n  \"status\": \"in_progress\",\n  \"priority\": \"high\"\n}"],
    'POST /tickets/{id}/comments' => ['method' => 'POST', 'path' => '/tickets/1/comments', 'desc' => 'Añadir comentario', 'body' => "{\n  \"body\": \"Trabajando en ello.\"\n}"],
    'GET /companies'      => ['method' => 'GET', 'path' => '/companies', 'desc' => 'Listar empresas'],
    'POST /companies'     => ['method' => 'POST', 'path' => '/companies', 'desc' => 'Crear empresa', 'body' => "{\n  \"name\": \"Acme Corp\",\n  \"industry\": \"SaaS\"\n}"],
    'GET /categories'     => ['method' => 'GET', 'path' => '/categories', 'desc' => 'Listar categorías'],
    'GET /users'          => ['method' => 'GET', 'path' => '/users', 'desc' => 'Listar usuarios'],
    'GET /kb/articles'    => ['method' => 'GET', 'path' => '/kb/articles', 'desc' => 'Listar KB'],
    'GET /sla'            => ['method' => 'GET', 'path' => '/sla', 'desc' => 'Políticas SLA'],
    'GET /automations'    => ['method' => 'GET', 'path' => '/automations', 'desc' => 'Listar automations'],
    'GET /assets'         => ['method' => 'GET', 'path' => '/assets', 'desc' => 'Listar activos'],
    'GET /tickets.csv'    => ['method' => 'GET', 'path' => '/tickets.csv', 'desc' => 'Export CSV'],
    'GET /events/recent'  => ['method' => 'GET', 'path' => '/events/recent', 'desc' => 'Eventos recientes'],
];
?>

<div class="dev-card dev-card-pad" style="border-color:rgba(245,158,11,.30); background:rgba(245,158,11,.04)">
    <div class="flex items-start gap-3">
        <i class="lucide lucide-flask-conical text-amber-300 mt-1"></i>
        <div>
            <div class="font-display font-bold text-white text-[14.5px]">API Console — para developers</div>
            <p class="text-[12.5px] text-slate-300 mt-1 leading-[1.6]">Las llamadas se ejecutan <strong>desde tu navegador directo a la API</strong> con el token que selecciones. Las respuestas son reales — afectan los datos del workspace de la app elegida.</p>
        </div>
    </div>
</div>

<div class="dev-card" x-data="{
    method: 'GET',
    path: '/tickets',
    body: '',
    token: localStorage.getItem('kydesk_console_token') || '',
    response: '',
    status: '',
    duration: 0,
    headers: {},
    loading: false,
    history: JSON.parse(localStorage.getItem('kydesk_console_history') || '[]'),
    activeTab: 'response',
    saveDialog: false,
    saveName: '',
    init() {
        this.$watch('token', v => localStorage.setItem('kydesk_console_token', v || ''));
    },
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
            this.history.unshift({ method: this.method, path: this.path, status: resp.status, duration: this.duration, at: new Date().toISOString() });
            this.history = this.history.slice(0, 25);
            localStorage.setItem('kydesk_console_history', JSON.stringify(this.history));
        } catch (e) {
            this.response = 'ERROR: ' + e.message;
            this.status = 0;
        } finally {
            this.loading = false;
        }
    },
    setEndpoint(method, path, body) { this.method = method; this.path = path; this.body = body || ''; },
    curl() {
        let c = `curl -X ${this.method} '<?= $apiBase ?>${this.path}' \\\n  -H 'Authorization: Bearer ${this.token || 'kyd_xxxx'}' \\\n  -H 'Content-Type: application/json'`;
        if (['POST','PATCH','PUT','DELETE'].includes(this.method) && this.body.trim()) {
            c += ` \\\n  -d '${this.body.replace(/\n\s*/g, ' ')}'`;
        }
        return c;
    },
    async saveCurrent() {
        if (!this.saveName.trim()) return;
        const fd = new FormData();
        fd.append('_csrf', '<?= $e($csrf) ?>');
        fd.append('name', this.saveName);
        fd.append('method', this.method);
        fd.append('path', this.path);
        fd.append('body', this.body);
        await fetch('<?= $url('/developers/console/save') ?>', { method: 'POST', body: fd });
        location.reload();
    },
    fromHistory(h) { this.method = h.method; this.path = h.path; }
}">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Construye tu request</h2>
            <p class="text-[12px] text-slate-400">Token persistido localmente · Historial · Favoritos</p>
        </div>
    </div>

    <div class="p-5 grid lg:grid-cols-3 gap-4 border-b" style="border-color:rgba(56,189,248,.06)">
        <div class="lg:col-span-1">
            <label class="dev-label">Token API</label>
            <input x-model="token" type="password" class="dev-input font-mono mb-2" placeholder="Pega tu kyd_xxxx aquí" style="font-size:11.5px">
            <div class="text-[10.5px] text-slate-500"><i class="lucide lucide-shield-check text-[10px]"></i> Guardado solo en tu navegador</div>
            <?php if (empty($tokens)): ?>
                <p class="text-[11px] text-amber-300 mt-2">Crea una <a href="<?= $url('/developers/apps') ?>" class="dev-link">app</a> para generar tokens.</p>
            <?php else: ?>
                <div class="text-[11px] text-slate-500 mt-2">Disponibles: <?= count($tokens) ?> tokens (cópialos desde <a href="<?= $url('/developers/apps') ?>" class="dev-link">tu app</a>)</div>
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

        <div class="space-y-2">
            <label class="dev-label">Acciones</label>
            <button @click="send()" class="dev-btn dev-btn-primary w-full" :disabled="loading">
                <i class="lucide lucide-send text-[13px]"></i>
                <span x-show="!loading">Ejecutar</span>
                <span x-show="loading">Ejecutando...</span>
            </button>
            <button @click="navigator.clipboard.writeText(curl()); $event.target.innerHTML='<i class=\'lucide lucide-check text-[13px]\'></i> Copiado'; setTimeout(()=>$event.target.innerHTML='<i class=\'lucide lucide-copy text-[13px]\'></i> Copiar como cURL', 1500); window.lucide && window.lucide.createIcons()" class="dev-btn dev-btn-soft w-full"><i class="lucide lucide-copy text-[13px]"></i> Copiar como cURL</button>
            <button @click="saveDialog = !saveDialog" class="dev-btn dev-btn-soft w-full"><i class="lucide lucide-bookmark text-[13px]"></i> Guardar</button>
        </div>
    </div>

    <div x-show="saveDialog" x-cloak class="px-5 pb-5" @click.away="saveDialog = false">
        <div class="dev-feature flex items-center gap-2">
            <input x-model="saveName" type="text" class="dev-input flex-1" placeholder="Nombre del request guardado" style="height:38px">
            <button @click="saveCurrent()" class="dev-btn dev-btn-primary text-[12px]">Guardar</button>
        </div>
    </div>

    <div class="px-5 pt-3">
        <div class="flex gap-1 border-b" style="border-color:rgba(56,189,248,.10)">
            <button @click="activeTab='response'" :class="activeTab === 'response' ? 'text-sky-300 border-sky-400' : 'text-slate-500 border-transparent'" class="px-4 py-2 border-b-2 text-[13px] transition">Respuesta</button>
            <button @click="activeTab='headers'" :class="activeTab === 'headers' ? 'text-sky-300 border-sky-400' : 'text-slate-500 border-transparent'" class="px-4 py-2 border-b-2 text-[13px] transition">Headers</button>
            <button @click="activeTab='history'" :class="activeTab === 'history' ? 'text-sky-300 border-sky-400' : 'text-slate-500 border-transparent'" class="px-4 py-2 border-b-2 text-[13px] transition">Historial</button>
            <button @click="activeTab='saved'" :class="activeTab === 'saved' ? 'text-sky-300 border-sky-400' : 'text-slate-500 border-transparent'" class="px-4 py-2 border-b-2 text-[13px] transition">Favoritos (<?= count($saved) ?>)</button>
        </div>
    </div>

    <div class="p-5">
        <div x-show="activeTab === 'response'">
            <div class="flex items-center justify-end gap-2 mb-2 text-[11.5px]" x-show="status">
                <span class="dev-pill" :class="status >= 500 ? 'dev-pill-red' : status >= 400 ? 'dev-pill-amber' : status >= 200 ? 'dev-pill-emerald' : 'dev-pill-gray'" x-text="'HTTP ' + status"></span>
                <span class="text-slate-400" x-text="duration + 'ms'"></span>
            </div>
            <pre class="dev-code" style="min-height:300px; max-height:500px; overflow:auto; font-size:11.5px; white-space:pre-wrap" x-text="response || 'Aún no has ejecutado ninguna request...'"></pre>
        </div>
        <div x-show="activeTab === 'headers'" x-cloak>
            <pre class="dev-code" style="min-height:300px; max-height:500px; overflow:auto; font-size:11px"><template x-for="(v, k) in headers" :key="k"><span><span class="text-sky-300" x-text="k"></span>: <span x-text="v"></span>
</span></template><template x-if="!Object.keys(headers).length"><span class="text-slate-500">Headers aparecerán aquí tras ejecutar...</span></template></pre>
        </div>
        <div x-show="activeTab === 'history'" x-cloak>
            <template x-if="history.length === 0">
                <p class="text-center text-[13px] text-slate-400 py-6">El historial es local y se borra con el cache del navegador.</p>
            </template>
            <div class="space-y-1">
                <template x-for="(h, i) in history" :key="i">
                    <button @click="fromHistory(h)" class="ai-prompt-card w-full !p-3 text-left flex items-center gap-3 hover:border-sky-400/40">
                        <span class="dev-pill !text-[9.5px]" :class="h.method === 'GET' ? 'dev-pill-emerald' : (h.method === 'POST' ? 'dev-pill-sky' : 'dev-pill-amber')" x-text="h.method"></span>
                        <span class="font-mono text-[11.5px] text-slate-200 flex-1 truncate" x-text="h.path"></span>
                        <span class="dev-pill !text-[9.5px]" :class="h.status >= 500 ? 'dev-pill-red' : h.status >= 400 ? 'dev-pill-amber' : 'dev-pill-emerald'" x-text="h.status"></span>
                        <span class="text-[10.5px] text-slate-500" x-text="h.duration + 'ms'"></span>
                    </button>
                </template>
            </div>
        </div>
        <div x-show="activeTab === 'saved'" x-cloak>
            <?php if (empty($saved)): ?>
                <p class="text-center text-[13px] text-slate-400 py-6">No has guardado requests todavía. Click "Guardar" después de configurar uno.</p>
            <?php else: ?>
                <div class="space-y-1">
                    <?php foreach ($saved as $s): ?>
                        <div class="ai-prompt-card !p-3 flex items-center gap-3">
                            <span class="dev-pill !text-[9.5px] <?= $s['method'] === 'GET' ? 'dev-pill-emerald' : ($s['method'] === 'POST' ? 'dev-pill-sky' : 'dev-pill-amber') ?>"><?= $s['method'] ?></span>
                            <button type="button" @click='setEndpoint(<?= json_encode($s['method']) ?>, <?= json_encode($s['path']) ?>, <?= json_encode((string)($s['body'] ?? '')) ?>)' class="flex-1 text-left">
                                <div class="font-display font-bold text-white text-[12.5px]"><?= $e($s['name']) ?></div>
                                <div class="font-mono text-[11px] text-slate-400 truncate"><?= $e($s['path']) ?></div>
                            </button>
                            <button type="button" onclick="if(confirm('¿Eliminar?')){fetch('<?= $url('/developers/console/saved/' . $s['id'] . '/delete') ?>', {method:'POST', body: new URLSearchParams({_csrf:'<?= $e($csrf) ?>'})}).then(()=>location.reload())}" class="dev-btn dev-btn-soft dev-btn-icon !w-7 !h-7"><i class="lucide lucide-x text-[11px]"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
