<?php
$base = rtrim($app->config['app']['url'], '/');
$apiBase = $base . '/api/v1';
?>
<div class="dev-card dev-card-pad" style="border-color:rgba(99,102,241,.30); background:rgba(99,102,241,.04)">
    <div class="flex items-start gap-3">
        <i class="lucide lucide-bot text-indigo-300 mt-1 text-[18px]"></i>
        <div>
            <div class="font-display font-bold text-white text-[14.5px]">AI Chat (BYO key)</div>
            <p class="text-[12.5px] text-slate-300 mt-1 leading-[1.6]">Conecta tu API key de OpenAI/Anthropic <strong>directamente desde el navegador</strong>. La IA usa tu token Kydesk para responder preguntas reales sobre tu workspace (tickets, métricas, búsquedas). <strong>Tus keys nunca tocan nuestros servidores</strong> — solo tu navegador.</p>
        </div>
    </div>
</div>

<div class="dev-card" x-data='{
    provider: localStorage.getItem("aichat_provider") || "openai",
    apiKey: localStorage.getItem("aichat_apikey") || "",
    model: localStorage.getItem("aichat_model") || "gpt-4o-mini",
    kydeskToken: localStorage.getItem("aichat_kydesk") || "",
    history: JSON.parse(localStorage.getItem("aichat_history") || "[]"),
    input: "",
    loading: false,
    autoToolCall: true,
    init() {
        this.$watch("provider", v => localStorage.setItem("aichat_provider", v));
        this.$watch("apiKey", v => localStorage.setItem("aichat_apikey", v));
        this.$watch("model", v => localStorage.setItem("aichat_model", v));
        this.$watch("kydeskToken", v => localStorage.setItem("aichat_kydesk", v));
        // Auto-suggest models based on provider
        this.$watch("provider", v => {
            if (v === "openai") this.model = "gpt-4o-mini";
            if (v === "anthropic") this.model = "claude-sonnet-4-5";
            if (v === "google") this.model = "gemini-2.0-flash";
        });
    },
    apiBase() { return "<?= $apiBase ?>"; },
    systemPrompt() {
        return `You are an assistant that helps a developer query their Kydesk Helpdesk workspace.
You have a tool: kydesk_api(method, path, body?). It calls the Kydesk REST API. Examples:
- kydesk_api("GET", "/me")
- kydesk_api("GET", "/tickets?status=open&per_page=10")
- kydesk_api("GET", "/stats")
- kydesk_api("GET", "/search?q=servidor")
- kydesk_api("POST", "/tickets", { subject: "...", priority: "high" })

When the user asks a question:
1. Decide which API call(s) you need.
2. Output them as JSON in the EXACT format:
   <tool>{"method":"GET","path":"/tickets?status=open"}</tool>
3. After receiving the result, write a clean answer in the user language using the data.
4. Multiple tool calls: do them one at a time.
5. NEVER make up data — always use the API.

Base URL: ${this.apiBase()}. Auth handled automatically.`;
    },
    async callKydesk(method, path, body) {
        if (!this.kydeskToken) throw new Error("Falta el Kydesk Token");
        const opts = {
            method,
            headers: { "Authorization": "Bearer " + this.kydeskToken, "Content-Type": "application/json" }
        };
        if (body) opts.body = JSON.stringify(body);
        const r = await fetch(this.apiBase() + path, opts);
        const text = await r.text();
        try { return { status: r.status, data: JSON.parse(text) }; }
        catch { return { status: r.status, data: text }; }
    },
    async callLLM(messages) {
        if (!this.apiKey) throw new Error("Falta la API key del proveedor");
        if (this.provider === "openai") {
            const r = await fetch("https://api.openai.com/v1/chat/completions", {
                method: "POST",
                headers: { "Authorization": "Bearer " + this.apiKey, "Content-Type": "application/json" },
                body: JSON.stringify({ model: this.model, messages, temperature: 0.3 })
            });
            const j = await r.json();
            if (j.error) throw new Error(j.error.message);
            return j.choices[0].message.content;
        }
        if (this.provider === "anthropic") {
            const sys = messages.find(m => m.role === "system")?.content || "";
            const rest = messages.filter(m => m.role !== "system");
            const r = await fetch("https://api.anthropic.com/v1/messages", {
                method: "POST",
                headers: { "x-api-key": this.apiKey, "anthropic-version": "2023-06-01", "anthropic-dangerous-direct-browser-access": "true", "Content-Type": "application/json" },
                body: JSON.stringify({ model: this.model, max_tokens: 2048, system: sys, messages: rest })
            });
            const j = await r.json();
            if (j.error) throw new Error(j.error.message);
            return j.content[0].text;
        }
        if (this.provider === "google") {
            const r = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${this.model}:generateContent?key=${this.apiKey}`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    systemInstruction: { parts: [{ text: messages.find(m => m.role === "system")?.content || "" }] },
                    contents: messages.filter(m => m.role !== "system").map(m => ({ role: m.role === "assistant" ? "model" : "user", parts: [{ text: m.content }] }))
                })
            });
            const j = await r.json();
            if (j.error) throw new Error(j.error.message);
            return j.candidates[0].content.parts[0].text;
        }
        throw new Error("Provider no soportado");
    },
    async send() {
        if (!this.input.trim() || this.loading) return;
        const userMsg = this.input.trim();
        this.input = "";
        this.history.push({ role: "user", content: userMsg, at: Date.now() });
        this.loading = true;
        try {
            let messages = [{ role: "system", content: this.systemPrompt() }];
            messages = messages.concat(this.history.map(h => ({ role: h.role, content: h.content })));

            // Loop: ask LLM, if response contains <tool>{...}</tool>, run it, append, ask again. Max 4 iterations.
            for (let i = 0; i < 4; i++) {
                const resp = await this.callLLM(messages);
                this.history.push({ role: "assistant", content: resp, at: Date.now() });

                const m = resp.match(/<tool>(.+?)<\/tool>/s);
                if (!m) break;
                if (!this.autoToolCall) break;
                try {
                    const call = JSON.parse(m[1]);
                    const result = await this.callKydesk(call.method || "GET", call.path || "/", call.body);
                    const toolResult = `<tool_result>${JSON.stringify(result).slice(0, 6000)}</tool_result>`;
                    this.history.push({ role: "user", content: toolResult, at: Date.now() });
                    messages.push({ role: "assistant", content: resp });
                    messages.push({ role: "user", content: toolResult });
                } catch (e) {
                    this.history.push({ role: "user", content: `<tool_error>${e.message}</tool_error>`, at: Date.now() });
                    break;
                }
            }
            localStorage.setItem("aichat_history", JSON.stringify(this.history.slice(-30)));
        } catch (e) {
            this.history.push({ role: "assistant", content: "❌ Error: " + e.message, at: Date.now() });
        } finally {
            this.loading = false;
            this.$nextTick(() => { const el = document.getElementById("chatScroll"); if (el) el.scrollTop = el.scrollHeight; });
        }
    },
    clear() { this.history = []; localStorage.setItem("aichat_history", "[]"); },
    suggestions: [
        "¿Cuántos tickets hay abiertos?",
        "Muéstrame los 5 tickets más recientes",
        "¿Qué empresas tienen más actividad?",
        "Lista los tickets urgentes sin asignar",
        "Resume las estadísticas del mes",
        "Busca tickets sobre \"servidor\""
    ]
}'>
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Chatea con tu workspace</h2>
            <p class="text-[12px] text-slate-400">La IA ejecuta llamadas reales a tu API en tu nombre</p>
        </div>
        <button @click="clear()" class="dev-btn dev-btn-soft text-[12px]"><i class="lucide lucide-rotate-ccw text-[12px]"></i> Limpiar</button>
    </div>

    <div class="grid lg:grid-cols-4 gap-px" style="background:rgba(56,189,248,.06)">
        <div class="lg:col-span-1 p-5 space-y-3" style="background:#0f1018">
            <div>
                <label class="dev-label">Proveedor</label>
                <select x-model="provider" class="dev-input">
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic (Claude)</option>
                    <option value="google">Google (Gemini)</option>
                </select>
            </div>
            <div>
                <label class="dev-label">Modelo</label>
                <input x-model="model" type="text" class="dev-input" placeholder="gpt-4o-mini">
            </div>
            <div>
                <label class="dev-label">API Key del proveedor</label>
                <input x-model="apiKey" type="password" class="dev-input font-mono" placeholder="sk-... / sk-ant-..." style="font-size:11px">
                <div class="text-[10.5px] text-slate-500 mt-1">Solo en tu navegador (localStorage)</div>
            </div>
            <div>
                <label class="dev-label">Kydesk Token</label>
                <input x-model="kydeskToken" type="password" class="dev-input font-mono" placeholder="kyd_..." style="font-size:11px">
                <?php if (count($tokens) > 0): ?>
                    <div class="text-[10.5px] text-slate-500 mt-1"><?= count($tokens) ?> token(s) disponibles en <a href="<?= $url('/developers/apps') ?>" class="dev-link">tus apps</a></div>
                <?php endif; ?>
            </div>
            <label class="flex items-center gap-2 mt-2">
                <input type="checkbox" x-model="autoToolCall">
                <span class="text-[11.5px] text-slate-300">Ejecutar tool calls automáticamente</span>
            </label>

            <div class="border-t pt-3 mt-3" style="border-color:rgba(56,189,248,.10)">
                <div class="text-[10.5px] uppercase font-bold tracking-[0.12em] text-slate-500 mb-2">Sugerencias</div>
                <div class="space-y-1">
                    <template x-for="s in suggestions" :key="s">
                        <button @click="input = s; send()" class="ai-prompt-card !p-2 w-full text-left text-[11.5px] text-slate-300 hover:border-sky-400/40">
                            <span x-text="s"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 flex flex-col" style="background:#0f1018; min-height:600px">
            <div id="chatScroll" class="flex-1 overflow-y-auto p-5 space-y-4" style="max-height:60vh">
                <template x-if="history.length === 0">
                    <div class="text-center py-10">
                        <div class="w-14 h-14 mx-auto rounded-2xl grid place-items-center mb-3" style="background:rgba(99,102,241,.12); color:#a5b4fc"><i class="lucide lucide-bot text-[22px]"></i></div>
                        <div class="font-display font-bold text-white text-[16px] mb-1">Empieza la conversación</div>
                        <p class="text-[13px] text-slate-400">Pregunta cualquier cosa sobre tu workspace.</p>
                    </div>
                </template>
                <template x-for="(m, i) in history" :key="i">
                    <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="m.role === 'user' ? 'bg-sky-500/15 border-sky-500/25 text-slate-100' : 'bg-slate-800/40 border-slate-700/40 text-slate-200'" class="max-w-[85%] rounded-xl border px-4 py-3 text-[13.5px] leading-[1.65]">
                            <div class="text-[10px] uppercase font-bold tracking-[0.14em] mb-1 opacity-60" x-text="m.role === 'user' ? 'Tú' : 'IA'"></div>
                            <div x-html="m.content.replace(/<tool>(.+?)<\/tool>/s, (_,j)=>('<pre class=\'dev-code !p-2 !text-[10.5px] !my-1\'>tool: '+j.replace(/[<>&]/g, c=>({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))+'</pre>')).replace(/<tool_result>(.+?)<\/tool_result>/s, (_,j)=>('<pre class=\'dev-code !p-2 !text-[10.5px] !my-1\' style=\'max-height:150px;overflow:auto\'>result: '+j.slice(0,500).replace(/[<>&]/g, c=>({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))+(j.length>500?'...':'')+'</pre>'))"></div>
                        </div>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="flex justify-start">
                        <div class="bg-slate-800/40 border border-slate-700/40 rounded-xl px-4 py-3 text-[13px] text-slate-400">
                            <i class="lucide lucide-loader text-[13px] animate-spin"></i> Pensando...
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t p-4" style="border-color:rgba(56,189,248,.10); background:rgba(0,0,0,.2)">
                <form @submit.prevent="send()" class="flex gap-2">
                    <input x-model="input" type="text" class="dev-input flex-1" placeholder="¿Qué quieres saber sobre tu workspace?" :disabled="loading">
                    <button type="submit" class="dev-btn dev-btn-primary" :disabled="loading || !input.trim()">
                        <i class="lucide lucide-send text-[13px]"></i> Enviar
                    </button>
                </form>
                <div class="text-[10.5px] text-slate-500 mt-2 flex items-center gap-1.5">
                    <i class="lucide lucide-shield text-[10px]"></i>
                    Tus credenciales y conversaciones nunca tocan nuestros servidores. Llamadas directas browser → proveedor / Kydesk.
                </div>
            </div>
        </div>
    </div>
</div>
