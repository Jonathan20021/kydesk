<?php
$base = rtrim($app->config['app']['url'], '/');
$apiBase = $base . '/api/v1';

$models = [
    [
        'id' => 'claude',
        'name' => 'Claude (Anthropic)',
        'icon' => 'sparkles',
        'color' => '#d97706',
        'gradient' => 'linear-gradient(135deg,#f97316,#d97706)',
        'desc' => 'Claude 4.5 / Sonnet 4.6 / Opus 4.7. Soporte para tool use, prompt caching y system prompts largos.',
        'best_for' => 'Razonamiento complejo, refactor de código, análisis de specs.',
        'how' => 'Pega el "System prompt" en el campo `system` del API o en Claude Desktop / Claude Code.',
    ],
    [
        'id' => 'gpt',
        'name' => 'GPT (OpenAI)',
        'icon' => 'bot',
        'color' => '#10b981',
        'gradient' => 'linear-gradient(135deg,#34d399,#10b981)',
        'desc' => 'GPT-4o / GPT-5 / o3 / o4-mini. Excelente para generación de código y function calling.',
        'best_for' => 'Function calling, integración con tools, generación de código rápida.',
        'how' => 'Usa el "System prompt" como `messages[0].role=system`. Para Codex CLI, guárdalo en `.codex/instructions.md`.',
    ],
    [
        'id' => 'gemini',
        'name' => 'Gemini (Google)',
        'icon' => 'gem',
        'color' => '#3b82f6',
        'gradient' => 'linear-gradient(135deg,#60a5fa,#3b82f6)',
        'desc' => 'Gemini 2.5 Pro / Flash. Contexto de hasta 2M tokens — ideal para inyectar la spec OpenAPI completa.',
        'best_for' => 'Procesamiento masivo de docs, análisis con OpenAPI completo en contexto.',
        'how' => 'Pega el system prompt + el contenido de openapi.json directamente como `system_instruction`.',
    ],
    [
        'id' => 'codex',
        'name' => 'Codex CLI',
        'icon' => 'terminal',
        'color' => '#a78bfa',
        'gradient' => 'linear-gradient(135deg,#c4b5fd,#a78bfa)',
        'desc' => 'Codex CLI de OpenAI. Lee instrucciones de `AGENTS.md` o `.codex/instructions.md`.',
        'best_for' => 'Scripting CLI, refactor de tests, generación de migraciones.',
        'how' => 'Descarga el archivo `AGENTS.md` y colócalo en la raíz del repo.',
    ],
    [
        'id' => 'cursor',
        'name' => 'Cursor',
        'icon' => 'mouse-pointer',
        'color' => '#0ea5e9',
        'gradient' => 'linear-gradient(135deg,#7dd3fc,#0ea5e9)',
        'desc' => 'Cursor IDE lee `.cursorrules` para conocer las convenciones del proyecto.',
        'best_for' => 'Edición inline con contexto, refactor multi-archivo.',
        'how' => 'Descarga `.cursorrules` y colócalo en la raíz del repo. Cursor lo lee automáticamente.',
    ],
    [
        'id' => 'copilot',
        'name' => 'GitHub Copilot',
        'icon' => 'github',
        'color' => '#64748b',
        'gradient' => 'linear-gradient(135deg,#94a3b8,#64748b)',
        'desc' => 'Copilot Chat / Workspace lee `.github/copilot-instructions.md`.',
        'best_for' => 'Sugerencias inline, completion contextual.',
        'how' => 'Guarda el system prompt en `.github/copilot-instructions.md`.',
    ],
    [
        'id' => 'continue',
        'name' => 'Continue.dev',
        'icon' => 'chevron-right',
        'color' => '#7c5cff',
        'gradient' => 'linear-gradient(135deg,#a78bfa,#7c5cff)',
        'desc' => 'Continue es una extensión open-source para VS Code/JetBrains.',
        'best_for' => 'Workflow personalizable, free + self-host.',
        'how' => 'Añade el system prompt en `~/.continue/config.json` bajo `systemMessage`.',
    ],
    [
        'id' => 'cline',
        'name' => 'Cline / Roo Code',
        'icon' => 'code-2',
        'color' => '#f59e0b',
        'gradient' => 'linear-gradient(135deg,#fbbf24,#f59e0b)',
        'desc' => 'Agente autónomo en VS Code que ejecuta tareas multi-paso.',
        'best_for' => 'Tareas autónomas: "implementa X feature", "arregla este bug".',
        'how' => 'Pega el system prompt en la configuración del modelo dentro de Cline.',
    ],
];

$prompts = [
    [
        'category' => 'Setup inicial',
        'icon' => 'rocket',
        'items' => [
            ['Crear cliente API en mi stack', 'Genera un cliente API para Kydesk en {LENGUAJE} con manejo de errores, retries con backoff exponencial, y un método por cada recurso (tickets, companies, users, kb, sla). Usa el OpenAPI spec en ' . $apiBase . '/openapi.json'],
            ['Configurar variables de entorno', 'Crea un archivo .env.example y la configuración para cargar KYDESK_TOKEN y KYDESK_BASE_URL en mi proyecto {LENGUAJE}. Añade validación de que las vars existan al iniciar.'],
            ['Auth middleware', 'Crea un middleware/interceptor que añada el header Authorization a todas las llamadas hacia ' . $apiBase . ', y maneje 401 refrescando o lanzando excepción descriptiva.'],
        ],
    ],
    [
        'category' => 'Tickets',
        'icon' => 'inbox',
        'items' => [
            ['Sync bidireccional', 'Implementa sincronización bidireccional entre mi base de datos y los tickets de Kydesk. Usa el endpoint /tickets con paginación (page+per_page), filtra por updated_after, y para creaciones envía Idempotency-Key con un UUID generado del registro local.'],
            ['Auto-creación de ticket desde formulario', 'Recibo un formulario web con asunto, email y descripción. Genera el código que crea un ticket vía POST /tickets con priority=medium, channel=portal, e Idempotency-Key del form ID.'],
            ['Dashboard de tickets', 'Construye una vista que liste tickets con filtros: status, priority, assigned_to. Usa expand=company,assignee para evitar N+1.'],
        ],
    ],
    [
        'category' => 'Webhooks',
        'icon' => 'webhook',
        'items' => [
            ['Receptor de webhooks', 'Crea un endpoint en {STACK} que reciba webhooks de Kydesk. Verifica HMAC-SHA256 en el header X-Kydesk-Signature usando mi WEBHOOK_SECRET. Responde 200 inmediatamente y procesa async.'],
            ['Re-disparar webhooks fallidos', 'Implementa un cron que cada 5 minutos relee mi tabla local de webhooks fallidos y los re-procesa con backoff.'],
            ['Slack notifications', 'Cuando llegue un webhook ticket.created con priority=urgent, envía una notificación a Slack en mi canal #soporte. Incluye link al ticket.'],
        ],
    ],
    [
        'category' => 'Automation',
        'icon' => 'zap',
        'items' => [
            ['Auto-asignación', 'Crea una automatización que cuando llegue un ticket de category="hardware", se asigne al técnico user_id=7. Usa POST /automations con trigger_event=ticket.created.'],
            ['Escalación SLA', 'Cuando un ticket lleve más de 4h sin respuesta y priority=urgent, escala automáticamente al supervisor y notifica por email.'],
            ['Batch update', 'Selecciona todos los tickets resueltos de hace +30 días y márcalos como closed usando POST /tickets/batch.'],
        ],
    ],
    [
        'category' => 'Reportes / Analytics',
        'icon' => 'bar-chart-3',
        'items' => [
            ['Reporte CSV mensual', 'Genera un script que descargue todos los tickets resueltos del mes anterior (paginado) y exporte un CSV con: code, subject, requester_email, created_at, resolved_at, time_to_resolve_minutes.'],
            ['KPI dashboard', 'Construye una página que llame /stats y muestre: tickets abiertos, SLA compliance %, CSAT promedio, resolución media. Refresca cada 60s.'],
            ['Top empresas', 'Lista las 10 empresas con más tickets este mes, con conteo y CSAT promedio.'],
        ],
    ],
];
?>

<style>
.ai-card { background:#0f1018; border:1px solid rgba(56,189,248,.10); border-radius:18px; transition:all .15s; cursor:pointer; }
.ai-card:hover { border-color:rgba(56,189,248,.30); transform:translateY(-2px); }
.ai-card.selected { border-color:rgba(56,189,248,.50); box-shadow:0 0 0 3px rgba(14,165,233,.12); }
.ai-step { background:rgba(15,16,24,.5); border:1px solid rgba(56,189,248,.08); border-radius:14px; padding:18px 20px; }
.ai-prompt-card { background:#0f1018; border:1px solid rgba(56,189,248,.10); border-radius:14px; padding:16px 18px; transition:border-color .15s; }
.ai-prompt-card:hover { border-color:rgba(56,189,248,.25); }
.ai-tag { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.10em; background:rgba(14,165,233,.08); color:#7dd3fc; border:1px solid rgba(56,189,248,.15); }
</style>

<!-- Hero -->
<div class="dev-card overflow-hidden relative">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(600px 250px at 30% 0%, rgba(14,165,233,.18), transparent 70%)"></div>
    <div class="relative p-7 grid lg:grid-cols-2 gap-6 items-center">
        <div>
            <span class="dev-pill mb-3"><i class="lucide lucide-bot text-[11px]"></i> Beta · AI Studio</span>
            <h2 class="font-display font-bold text-white text-[26px] tracking-tight mb-2">Construye tu app con IA, sin onboarding</h2>
            <p class="dev-muted text-[14px] leading-[1.65]">
                Genera prompts, system instructions y archivos de configuración listos para Claude, GPT, Gemini, Cursor, Copilot, Continue y más. Tu agente IA conocerá la API completa antes de escribir la primera línea.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <a href="<?= $apiBase ?>/openapi.json" target="_blank" class="ai-step hover:border-sky-400/40 transition flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center bg-sky-500/15 text-sky-300 border border-sky-500/20"><i class="lucide lucide-file-json text-[15px]"></i></div>
                <div><div class="text-white font-display font-bold text-[13px]">OpenAPI 3.1</div><div class="text-[11.5px] text-slate-400">spec completa</div></div>
            </a>
            <a href="<?= $url('/developers/ai/digest') ?>" target="_blank" class="ai-step hover:border-sky-400/40 transition flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center bg-emerald-500/15 text-emerald-300 border border-emerald-500/20"><i class="lucide lucide-file-text text-[15px]"></i></div>
                <div><div class="text-white font-display font-bold text-[13px]">Markdown digest</div><div class="text-[11.5px] text-slate-400">para context window</div></div>
            </a>
            <a href="<?= $url('/developers/ai/cursorrules') ?>" target="_blank" class="ai-step hover:border-sky-400/40 transition flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center bg-purple-500/15 text-purple-300 border border-purple-500/20"><i class="lucide lucide-mouse-pointer text-[15px]"></i></div>
                <div><div class="text-white font-display font-bold text-[13px]">.cursorrules</div><div class="text-[11.5px] text-slate-400">para Cursor IDE</div></div>
            </a>
            <a href="<?= $apiBase ?>/postman.json" class="ai-step hover:border-sky-400/40 transition flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center bg-amber-500/15 text-amber-300 border border-amber-500/20"><i class="lucide lucide-package text-[15px]"></i></div>
                <div><div class="text-white font-display font-bold text-[13px]">Postman</div><div class="text-[11.5px] text-slate-400">collection</div></div>
            </a>
        </div>
    </div>
</div>

<!-- Step 1: choose AI -->
<div class="dev-card">
    <div class="dev-card-head">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl grid place-items-center bg-sky-500/15 text-sky-300 font-display font-bold text-[13px]">1</div>
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Elige tu modelo o herramienta IA</h2>
                <p class="text-[12px] text-slate-400">Cada modelo lee instrucciones de manera distinta — selecciona el tuyo</p>
            </div>
        </div>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px p-px" x-data="{selected:'claude'}" id="aiModels" style="background:rgba(56,189,248,.06)">
        <?php foreach ($models as $m): ?>
            <div class="ai-card !rounded-none" :class="selected==='<?= $m['id'] ?>' ? 'selected' : ''" @click="selected='<?= $m['id'] ?>'; window.selectedModel='<?= $m['id'] ?>'; document.dispatchEvent(new CustomEvent('ai-model-changed'))" style="background:#0f1018">
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:<?= $m['gradient'] ?>"><i class="lucide lucide-<?= $m['icon'] ?> text-[15px]"></i></div>
                        <div class="font-display font-bold text-white text-[14.5px]"><?= $e($m['name']) ?></div>
                    </div>
                    <p class="text-[12px] text-slate-400 leading-[1.55] mb-3"><?= $e($m['desc']) ?></p>
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.12em] text-slate-500 mb-1">Mejor para</div>
                    <div class="text-[11.5px] text-slate-300 mb-3"><?= $e($m['best_for']) ?></div>
                    <div class="text-[10.5px] uppercase font-bold tracking-[0.12em] text-slate-500 mb-1">Cómo usar</div>
                    <div class="text-[11.5px] text-slate-300"><?= $e($m['how']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Step 2: System Prompt builder -->
<div class="dev-card">
    <div class="dev-card-head">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl grid place-items-center bg-sky-500/15 text-sky-300 font-display font-bold text-[13px]">2</div>
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Genera tu System Prompt</h2>
                <p class="text-[12px] text-slate-400">Personaliza y copia. Funciona con cualquier modelo.</p>
            </div>
        </div>
    </div>
    <div class="p-5 grid lg:grid-cols-2 gap-5" x-data="{appName: 'Mi App', tone: 'profesional', loading: false, prompt: ''}">
        <div class="space-y-4">
            <div>
                <label class="dev-label">Nombre de tu proyecto</label>
                <input x-model="appName" type="text" class="dev-input" placeholder="Ej: Acme Helpdesk Bot">
            </div>
            <div>
                <label class="dev-label">Tono de la IA</label>
                <select x-model="tone" class="dev-input">
                    <option value="profesional">Profesional y conciso</option>
                    <option value="amigable">Amigable y didáctico</option>
                    <option value="senior">Senior engineer (sin azúcar)</option>
                    <option value="explicativo">Explica cada paso en detalle</option>
                </select>
            </div>
            <button @click="loading=true; fetch('<?= $url('/developers/ai/system-prompt') ?>?app_name=' + encodeURIComponent(appName) + '&tone=' + encodeURIComponent(tone)).then(r=>r.text()).then(t=>{prompt=t;loading=false;})" class="dev-btn dev-btn-primary w-full">
                <i class="lucide lucide-sparkles text-[13px]"></i>
                <span x-show="!loading">Generar system prompt</span>
                <span x-show="loading">Generando...</span>
            </button>
            <div class="ai-step">
                <div class="text-[11.5px] uppercase font-bold tracking-[0.12em] text-sky-300 mb-2">Cómo usarlo</div>
                <div class="text-[12.5px] text-slate-300 space-y-2">
                    <p><strong class="text-white">Claude:</strong> ponlo en el campo <code class="font-mono text-amber-300">system</code> de la API o en Claude Desktop.</p>
                    <p><strong class="text-white">GPT/OpenAI:</strong> primer mensaje con <code class="font-mono text-amber-300">role: "system"</code>.</p>
                    <p><strong class="text-white">Gemini:</strong> campo <code class="font-mono text-amber-300">system_instruction</code>.</p>
                    <p><strong class="text-white">Cursor/Copilot:</strong> guárdalo en <code class="font-mono text-amber-300">.cursorrules</code> o <code class="font-mono text-amber-300">.github/copilot-instructions.md</code>.</p>
                </div>
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="dev-label !mb-0">System prompt generado</label>
                <button @click="navigator.clipboard.writeText(prompt); $event.target.textContent='✓ Copiado'; setTimeout(()=>$event.target.textContent='Copiar', 2000)" class="dev-btn dev-btn-soft text-[11.5px] !h-7" :disabled="!prompt"><i class="lucide lucide-copy text-[12px]"></i> Copiar</button>
            </div>
            <textarea x-model="prompt" class="dev-textarea font-mono" rows="22" placeholder="Click en 'Generar system prompt' para crear el prompt personalizado..." style="font-size:11.5px; line-height:1.65"></textarea>
        </div>
    </div>
</div>

<!-- Step 3: Prompt Library -->
<div class="dev-card">
    <div class="dev-card-head">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl grid place-items-center bg-sky-500/15 text-sky-300 font-display font-bold text-[13px]">3</div>
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Biblioteca de prompts</h2>
                <p class="text-[12px] text-slate-400">Click para copiar — pégalo en tu IA preferida</p>
            </div>
        </div>
    </div>
    <div class="p-5 space-y-6">
        <?php foreach ($prompts as $sec): ?>
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="lucide lucide-<?= $sec['icon'] ?> text-sky-300 text-[15px]"></i>
                    <h3 class="font-display font-bold text-white text-[14.5px]"><?= $e($sec['category']) ?></h3>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <?php foreach ($sec['items'] as [$title, $prompt]):
                        $promptId = 'p_' . substr(md5($prompt), 0, 8); ?>
                        <div class="ai-prompt-card group">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="font-display font-bold text-white text-[13.5px] flex-1"><?= $e($title) ?></div>
                                <button onclick="navigator.clipboard.writeText(document.getElementById('<?= $promptId ?>').textContent); this.textContent='✓'; setTimeout(()=>this.innerHTML='<i class=\'lucide lucide-copy text-[12px]\'></i>', 2000); window.lucide && window.lucide.createIcons()" class="dev-btn dev-btn-soft dev-btn-icon !w-7 !h-7 flex-shrink-0" title="Copiar"><i class="lucide lucide-copy text-[12px]"></i></button>
                            </div>
                            <p id="<?= $promptId ?>" class="text-[12px] text-slate-400 leading-[1.6] line-clamp-3 group-hover:line-clamp-none cursor-text"><?= $e($prompt) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Step 4: Inject context for any AI -->
<div class="dev-card">
    <div class="dev-card-head">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl grid place-items-center bg-sky-500/15 text-sky-300 font-display font-bold text-[13px]">4</div>
            <div>
                <h2 class="font-display font-bold text-white text-[16px]">Inyecta contexto en tu IA</h2>
                <p class="text-[12px] text-slate-400">3 formas de darle a la IA conocimiento completo de la API</p>
            </div>
        </div>
    </div>
    <div class="p-5 grid md:grid-cols-3 gap-4">
        <div class="ai-step">
            <div class="flex items-center gap-2 mb-2">
                <span class="ai-tag"><i class="lucide lucide-zap text-[10px]"></i> Más simple</span>
            </div>
            <h3 class="font-display font-bold text-white text-[14.5px] mb-2">Pega el digest en el system prompt</h3>
            <p class="text-[12.5px] text-slate-400 mb-3 leading-[1.6]">Markdown compacto (~3KB) con la API completa. Cabe en cualquier context window.</p>
            <a href="<?= $url('/developers/ai/digest') ?>" target="_blank" class="dev-btn dev-btn-soft w-full"><i class="lucide lucide-arrow-up-right text-[12px]"></i> Abrir digest</a>
        </div>
        <div class="ai-step">
            <div class="flex items-center gap-2 mb-2">
                <span class="ai-tag"><i class="lucide lucide-target text-[10px]"></i> Más preciso</span>
            </div>
            <h3 class="font-display font-bold text-white text-[14.5px] mb-2">Inyecta el OpenAPI completo</h3>
            <p class="text-[12.5px] text-slate-400 mb-3 leading-[1.6]">Spec OpenAPI 3.1 con todos los schemas, validaciones y ejemplos. Ideal para Gemini (2M tokens) o Claude.</p>
            <a href="<?= $apiBase ?>/openapi.json" target="_blank" class="dev-btn dev-btn-soft w-full"><i class="lucide lucide-arrow-up-right text-[12px]"></i> Ver OpenAPI</a>
        </div>
        <div class="ai-step">
            <div class="flex items-center gap-2 mb-2">
                <span class="ai-tag"><i class="lucide lucide-folder text-[10px]"></i> Para tu repo</span>
            </div>
            <h3 class="font-display font-bold text-white text-[14.5px] mb-2">Descarga .cursorrules</h3>
            <p class="text-[12.5px] text-slate-400 mb-3 leading-[1.6]">Archivo con convenciones para Cursor, Copilot, Continue, Claude Code y similares. Drop-in en tu repo.</p>
            <a href="<?= $url('/developers/ai/cursorrules') ?>" class="dev-btn dev-btn-soft w-full"><i class="lucide lucide-download text-[12px]"></i> Descargar .cursorrules</a>
        </div>
    </div>
</div>

<!-- Quick references -->
<div class="dev-card dev-card-pad">
    <h3 class="font-display font-bold text-white text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-lightbulb text-amber-300"></i> Tips para sacar el máximo provecho</h3>
    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 text-[13px] text-slate-300 leading-[1.65]">
        <div class="flex gap-2"><i class="lucide lucide-check text-emerald-400 text-[13px] mt-0.5 flex-shrink-0"></i><span>Empieza con el <strong>system prompt</strong> + un ejemplo concreto de lo que quieres construir.</span></div>
        <div class="flex gap-2"><i class="lucide lucide-check text-emerald-400 text-[13px] mt-0.5 flex-shrink-0"></i><span>Si la IA inventa endpoints, recuérdale leer <code class="text-amber-300 font-mono text-[11.5px]">/openapi.json</code>.</span></div>
        <div class="flex gap-2"><i class="lucide lucide-check text-emerald-400 text-[13px] mt-0.5 flex-shrink-0"></i><span>Para Cursor/Copilot, ten <code class="text-amber-300 font-mono text-[11.5px]">.cursorrules</code> en la raíz del repo.</span></div>
        <div class="flex gap-2"><i class="lucide lucide-check text-emerald-400 text-[13px] mt-0.5 flex-shrink-0"></i><span>Pídele que use <code class="text-amber-300 font-mono text-[11.5px]">Idempotency-Key</code> en POSTs y respete <code class="text-amber-300 font-mono text-[11.5px]">Retry-After</code>.</span></div>
        <div class="flex gap-2"><i class="lucide lucide-check text-emerald-400 text-[13px] mt-0.5 flex-shrink-0"></i><span>Para Claude Code, guarda el system prompt en <code class="text-amber-300 font-mono text-[11.5px]">CLAUDE.md</code> en la raíz.</span></div>
        <div class="flex gap-2"><i class="lucide lucide-check text-emerald-400 text-[13px] mt-0.5 flex-shrink-0"></i><span>Si trabajas con un modelo de bajo contexto, usa el <strong>digest</strong> (3KB) en lugar del OpenAPI completo.</span></div>
    </div>
</div>
