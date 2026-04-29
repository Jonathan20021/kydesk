<?php
$slug = $tenant->slug;
$publicSlug = $settings['public_slug'] ?: $slug;
$publicUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($publicSlug);
$timezones = ['America/Santo_Domingo','America/Mexico_City','America/Bogota','America/Lima','America/Argentina/Buenos_Aires','America/Santiago','America/New_York','America/Los_Angeles','Europe/Madrid','Europe/London','Europe/Paris','UTC'];
$aiAvailable = \App\Core\MeetingAi::guard($tenant)['ok'];
// Consumo IA del módulo de reuniones (último mes en curso)
$meetingAiUsage = $app->db->one(
    "SELECT COUNT(*) AS reqs, IFNULL(SUM(tokens_in),0) AS tin, IFNULL(SUM(tokens_out),0) AS tout
     FROM ai_completions
     WHERE tenant_id = ? AND status = 'ok'
       AND action LIKE 'meeting_%'
       AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",
    [$tenant->id]
);
?>

<div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
    <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
    <span>Ajustes</span>
</div>
<h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em] mb-5">Página pública</h1>

<form method="POST" action="<?= $url('/t/' . $slug . '/meetings/settings') ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-[15px]">URL pública</h3>
                <label class="inline-flex items-center gap-2 text-[12px]">
                    <input type="checkbox" name="is_enabled" value="1" <?= (int)$settings['is_enabled']?'checked':'' ?>>
                    <span>Activa</span>
                </label>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Slug público</label>
                <div class="flex items-center gap-2">
                    <span class="text-[12px] text-ink-400 font-mono"><?= $e(rtrim($app->config['app']['url'], '/')) ?>/book/</span>
                    <input name="public_slug" class="input" value="<?= $e($publicSlug) ?>" placeholder="<?= $e($slug) ?>">
                </div>
                <p class="text-[11px] text-ink-400 mt-1">URL actual: <a href="<?= $e($publicUrl) ?>" target="_blank" class="text-brand-700 underline"><?= $e($publicUrl) ?></a></p>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Branding y mensajes</h3>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Título de la página</label>
                <input name="page_title" class="input" value="<?= $e($settings['page_title'] ?? '') ?>" placeholder="Agenda una reunión con <?= $e($tenant->name) ?>">
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Descripción</label>
                <textarea name="page_description" rows="2" class="input" style="height:auto;padding:12px 16px"><?= $e($settings['page_description'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Logo URL (opcional)</label>
                    <input name="logo_url" class="input" value="<?= $e($settings['logo_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Color primario</label>
                    <input type="color" name="primary_color" value="<?= $e($settings['primary_color'] ?? '#7c5cff') ?>" class="w-full h-11 rounded-2xl border" style="border-color:var(--border)">
                </div>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Mensaje de bienvenida</label>
                <textarea name="welcome_message" rows="3" class="input" style="height:auto;padding:12px 16px" placeholder="Hola, gracias por visitar nuestra agenda..."><?= $e($settings['welcome_message'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Mensaje de éxito (post-reserva)</label>
                <textarea name="success_message" rows="3" class="input" style="height:auto;padding:12px 16px" placeholder="¡Reserva confirmada! Te enviamos un email..."><?= $e($settings['success_message'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Información del negocio</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Nombre del negocio</label>
                    <input name="business_name" class="input" value="<?= $e($settings['business_name'] ?? $tenant->name) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Email de contacto</label>
                    <input type="email" name="business_email" class="input" value="<?= $e($settings['business_email'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Teléfono</label>
                    <input name="business_phone" class="input" value="<?= $e($settings['business_phone'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Dirección</label>
                    <input name="business_address" class="input" value="<?= $e($settings['business_address'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Zona horaria</h3>
            <select name="timezone" class="input">
                <?php foreach ($timezones as $tz): ?>
                    <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? '')===$tz?'selected':'' ?>><?= $tz ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-[11px] text-ink-400">Los horarios se mostrarán al cliente en esta zona.</p>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Campos del formulario</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Pedir teléfono (obligatorio)</span>
                <input type="checkbox" name="require_phone" value="1" <?= !empty($settings['require_phone']) ? 'checked' : '' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Pedir empresa (obligatorio)</span>
                <input type="checkbox" name="require_company" value="1" <?= !empty($settings['require_company']) ? 'checked' : '' ?>>
            </label>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Notificaciones internas</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Notificarme las nuevas reservas</span>
                <input type="checkbox" name="notify_new_booking" value="1" <?= (int)$settings['notify_new_booking']?'checked':'' ?>>
            </label>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Emails extra (separados por coma)</label>
                <textarea name="notify_emails" rows="2" class="input" style="height:auto;padding:12px 16px" placeholder="ventas@empresa.com, ana@empresa.com"><?= $e($settings['notify_emails'] ?? '') ?></textarea>
                <p class="text-[11px] text-ink-400 mt-1">Si está vacío usamos el email del host asignado al tipo.</p>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Footer</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Mostrar "Powered by Kydesk"</span>
                <input type="checkbox" name="show_powered_by" value="1" <?= (int)$settings['show_powered_by']?'checked':'' ?>>
            </label>
        </div>

        <div class="card card-pad space-y-3" x-data="{ provider: '<?= $e($settings['conference_provider'] ?? 'jitsi') ?>', advanced: false }">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-[15px]">Video conferencia</h3>
                <label class="inline-flex items-center gap-2 text-[12px]">
                    <input type="checkbox" name="conference_enabled" value="1" <?= (int)($settings['conference_enabled'] ?? 1) ? 'checked' : '' ?>>
                    <span>Activa</span>
                </label>
            </div>
            <p class="text-[11.5px] text-ink-400">Auto-crea rooms para reuniones <strong>virtuales</strong> y <strong>llamadas de audio</strong>. Host y cliente entran desde el mismo panel.</p>

            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-2 block">Provider</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer flex items-start gap-2 p-3 rounded-xl border-2 transition" :style="provider === 'jitsi' ? 'border-color:var(--brand-300);background:var(--brand-50)' : 'border-color:var(--border);background:white'">
                        <input type="radio" name="conference_provider" value="jitsi" x-model="provider" class="mt-0.5">
                        <div>
                            <div class="font-display font-bold text-[12.5px] flex items-center gap-1.5">Jitsi Meet <span class="badge badge-emerald text-[9px]">GRATIS</span></div>
                            <div class="text-[10.5px] text-ink-500 mt-0.5">meet.jit.si o self-host</div>
                        </div>
                    </label>
                    <label class="cursor-pointer flex items-start gap-2 p-3 rounded-xl border-2 transition" :style="provider === 'livekit' ? 'border-color:var(--brand-300);background:var(--brand-50)' : 'border-color:var(--border);background:white'">
                        <input type="radio" name="conference_provider" value="livekit" x-model="provider" class="mt-0.5">
                        <div>
                            <div class="font-display font-bold text-[12.5px] flex items-center gap-1.5">LiveKit <span class="badge badge-amber text-[9px]">BETA</span></div>
                            <div class="text-[10.5px] text-ink-500 mt-0.5">SDK propio · grabación + transcripción</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Jitsi config -->
            <script>
            window.__kydeskJitsiConfig = window.__kydeskJitsiConfig || function (init) {
                return {
                    domain: init.domain || 'meet.jit.si',
                    appId: init.appId || '',
                    kid: init.kid || '',
                    appSecret: init.appSecret || '',
                    advanced: !!init.appId,
                    testUrl: init.testUrl,
                    csrf: init.csrf,
                    testing: false,
                    testResult: null,
                    onAppIdInput(v) {
                        this.appId = v;
                        if (v.startsWith('vpaas-magic-cookie-') && this.domain === 'meet.jit.si') {
                            this.domain = '8x8.vc';
                        }
                    },
                    isJaaS() { return this.appId.startsWith('vpaas-magic-cookie-') || this.domain.includes('8x8.vc'); },
                    isProduction() { return this.domain !== 'meet.jit.si' || !!this.appId; },
                    isPemKey() {
                        return this.appSecret && (this.appSecret.includes('BEGIN PRIVATE KEY') || this.appSecret.includes('BEGIN RSA PRIVATE KEY'));
                    },
                    async testConfig() {
                        this.testing = true; this.testResult = null;
                        try {
                            const fd = new FormData();
                            fd.append('_csrf', this.csrf);
                            fd.append('domain', this.domain);
                            fd.append('app_id', this.appId);
                            fd.append('kid', this.kid);
                            fd.append('app_secret', this.appSecret);
                            const r = await fetch(this.testUrl, { method: 'POST', body: fd });
                            this.testResult = await r.json();
                        } catch (e) { this.testResult = { ok: false, error: 'Error de red' }; }
                        this.testing = false;
                    },
                };
            };
            </script>
            <div x-show="provider === 'jitsi'" x-cloak class="space-y-3 pt-2"
                 x-data='__kydeskJitsiConfig(<?= htmlspecialchars(json_encode([
                     'domain'    => $settings['jitsi_domain'] ?? 'meet.jit.si',
                     'appId'     => $settings['jitsi_app_id'] ?? '',
                     'kid'       => $settings['jitsi_kid'] ?? '',
                     'appSecret' => $settings['jitsi_app_secret'] ?? '',
                     'testUrl'   => $url('/t/' . $slug . '/meetings/conference/test'),
                     'csrf'      => $csrf,
                 ]), ENT_QUOTES, 'UTF-8') ?>)'
                 style="border-top:1px solid var(--border)">

                <!-- Status banner -->
                <div class="rounded-xl p-3 text-[12px] flex items-start gap-2" x-show="!isProduction()" :style="'background:#fffbeb;border:1px solid #fde68a;color:#92400e'">
                    <i class="lucide lucide-alert-triangle text-[14px] flex-shrink-0 mt-0.5"></i>
                    <div>
                        <strong>Modo demo (meet.jit.si gratis):</strong> embebido se corta a los 5 min · auto-fallback a "abrir en pestaña". Para producción seguí los pasos abajo.
                    </div>
                </div>
                <div class="rounded-xl p-3 text-[12px] flex items-start gap-2" x-show="isProduction()" x-cloak :style="'background:#ecfdf5;border:1px solid #a7f3d0;color:#047857'">
                    <i class="lucide lucide-check-circle text-[14px] flex-shrink-0 mt-0.5"></i>
                    <div>
                        <strong>Producción:</strong> embebido sin restricciones <span x-show="appId" x-cloak>· JWT activo · roles host/guest · grabación habilitada para hosts</span>
                    </div>
                </div>

                <!-- Setup wizard 8x8.vc -->
                <details class="rounded-xl border" style="border-color:var(--border)" <?= empty($settings['jitsi_app_id']) ? 'open' : '' ?>>
                    <summary class="cursor-pointer px-3 py-2.5 flex items-center justify-between gap-2 text-[12.5px] font-semibold" style="background:#f3f0ff">
                        <span class="flex items-center gap-2"><i class="lucide lucide-rocket text-[13px] text-brand-600"></i> Setup en 3 minutos · Jitsi as a Service</span>
                        <i class="lucide lucide-chevron-down text-[12px]"></i>
                    </summary>
                    <ol class="p-4 space-y-3 text-[12.5px] text-ink-700">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 text-brand-700 grid place-items-center font-bold text-[11px]">1</span>
                            <div>
                                <div class="font-semibold mb-0.5">Creá una cuenta gratis en JaaS</div>
                                <div class="text-[11.5px] text-ink-500">Tier gratuito: 25 usuarios/mes incluidos · sin tarjeta requerida.</div>
                                <a href="https://jaas.8x8.vc/#/start-guide" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-brand-700 mt-1">
                                    Abrir jaas.8x8.vc <i class="lucide lucide-external-link text-[10px]"></i>
                                </a>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 text-brand-700 grid place-items-center font-bold text-[11px]">2</span>
                            <div>
                                <div class="font-semibold mb-0.5">Copiá el App ID</div>
                                <div class="text-[11.5px] text-ink-500">Está en el dashboard, formato <code class="font-mono text-[11px] bg-ink-100 px-1 rounded">vpaas-magic-cookie-...</code>. Pegalo en el campo App ID abajo · el dominio cambia automáticamente a <code class="font-mono">8x8.vc</code>.</div>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 text-brand-700 grid place-items-center font-bold text-[11px]">3</span>
                            <div>
                                <div class="font-semibold mb-0.5">Generá un Key Pair</div>
                                <div class="text-[11.5px] text-ink-500">En el dashboard "API Keys" → "Add API Key" → descargá la clave privada (.pk). Pegá <strong>todo el contenido del .pk</strong> en el campo App Secret abajo (incluyendo <code class="font-mono text-[11px]">-----BEGIN PRIVATE KEY-----</code>).</div>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 grid place-items-center font-bold text-[11px]">✓</span>
                            <div>
                                <div class="font-semibold mb-0.5">Test + Guardar</div>
                                <div class="text-[11.5px] text-ink-500">Pulsá "Probar configuración" para validar el JWT. Si todo OK, guardá los ajustes y las próximas reuniones usarán 8x8.vc embebido.</div>
                            </div>
                        </li>
                    </ol>
                </details>

                <!-- Form -->
                <div class="space-y-2">
                    <div>
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Dominio Jitsi</label>
                        <div class="flex gap-1">
                            <button type="button" @click="domain='meet.jit.si'" :class="domain === 'meet.jit.si' ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-[#ececef] text-ink-500'" class="flex-1 px-3 py-2 rounded-lg border text-[12px] font-semibold transition">meet.jit.si</button>
                            <button type="button" @click="domain='8x8.vc'" :class="domain === '8x8.vc' ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-[#ececef] text-ink-500'" class="flex-1 px-3 py-2 rounded-lg border text-[12px] font-semibold transition">8x8.vc</button>
                            <input name="jitsi_domain" :value="domain" @input="domain = $event.target.value" class="input flex-1" style="height:38px" placeholder="meet.example.com">
                        </div>
                        <p class="text-[10.5px] text-ink-400 mt-1">Self-hosted: pegá tu dominio en el campo de texto (ej. <code class="font-mono">meet.tuempresa.com</code>).</p>
                    </div>

                    <div>
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block">
                            App ID <span class="text-ink-400 font-normal">(obligatorio para 8x8.vc · opcional self-host)</span>
                        </label>
                        <input name="jitsi_app_id" :value="appId" @input="onAppIdInput($event.target.value)" class="input font-mono" style="font-size:11.5px" placeholder="vpaas-magic-cookie-1a2b3c... o tu app_id de Jitsi self-hosted">
                        <p class="text-[10.5px] mt-1" :style="isJaaS() ? 'color:#047857' : 'color:#8e8e9a'" x-text="isJaaS() ? '✓ JaaS detectado · firmamos JWT con RS256' : 'Si pegás un app_id de JaaS, cambiamos el dominio automáticamente.'"></p>
                    </div>

                    <div x-show="isJaaS()" x-cloak :class="!kid ? 'p-3 -mx-1 rounded-xl' : ''" :style="!kid ? 'background:#fef2f2;border:1px solid #fecaca' : ''">
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block flex items-center gap-1.5 flex-wrap">
                            <span>API Key ID <span class="text-ink-400 font-normal">(kid)</span></span>
                            <span class="badge badge-purple text-[9px]">JaaS · OBLIGATORIO</span>
                            <span x-show="!kid" x-cloak class="badge badge-rose text-[9px]"><i class="lucide lucide-alert-triangle text-[9px]"></i> Falta</span>
                        </label>
                        <input name="jitsi_kid" x-model="kid" class="input font-mono" style="font-size:11.5px" placeholder="abc1234d-5678-90ef-1234-567890abcdef" :style="!kid ? 'border-color:#ef4444' : ''">
                        <p class="text-[10.5px] mt-1" :class="!kid ? 'text-rose-700' : 'text-ink-400'">
                            <span x-show="!kid" x-cloak><strong>Sin esto, 8x8.vc rechaza la conexión con "Authentication failed: Missing Key ID".</strong><br></span>
                            Andá a <a href="https://jaas.8x8.vc/#/apikeys" target="_blank" rel="noopener" class="underline font-semibold">jaas.8x8.vc → API Keys</a>, copiá el UUID que aparece en la columna "Key ID" (no el App ID).
                        </p>
                    </div>

                    <div>
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block">
                            App Secret · Private Key
                            <span x-show="isPemKey()" x-cloak class="text-emerald-600">· RSA detectado → RS256</span>
                            <span x-show="appSecret && !isPemKey()" x-cloak class="text-ink-500">· shared secret → HS256</span>
                        </label>
                        <textarea name="jitsi_app_secret" x-model="appSecret" rows="4" class="input font-mono" style="height:auto;padding:10px 12px;font-size:11px;line-height:1.5" placeholder="-----BEGIN PRIVATE KEY-----&#10;MIIE...&#10;-----END PRIVATE KEY-----&#10;&#10;O dejá vacío para usar el actual"></textarea>
                        <p class="text-[10.5px] text-ink-400 mt-1">Para JaaS: pegá el contenido completo del archivo <code class="font-mono">.pk</code>. Para self-hosted: tu shared secret HS256.</p>
                    </div>

                    <label class="flex items-center justify-between gap-2 text-[13px]">
                        <div>
                            <div>Solo audio por defecto</div>
                            <div class="text-[11px] text-ink-400">Inicia con la cámara apagada · útil para llamadas tipo "phone call"</div>
                        </div>
                        <input type="checkbox" name="jitsi_audio_only" value="1" <?= (int)($settings['jitsi_audio_only'] ?? 0) ? 'checked' : '' ?>>
                    </label>

                    <!-- Test button -->
                    <div class="flex items-center gap-2 pt-2" x-show="appId" x-cloak>
                        <button type="button" @click="testConfig()" :disabled="testing" class="btn btn-soft btn-sm">
                            <i class="lucide lucide-flask-conical text-[13px]" x-show="!testing"></i>
                            <i class="lucide lucide-loader-2 text-[13px] animate-spin" x-show="testing" x-cloak></i>
                            <span x-text="testing ? 'Probando...' : 'Probar configuración'"></span>
                        </button>
                        <span x-show="testResult && testResult.ok" x-cloak class="text-[12px] font-semibold inline-flex items-center gap-1" style="color:#047857">
                            <i class="lucide lucide-check-circle-2 text-[13px]"></i> JWT firmado correctamente
                        </span>
                        <span x-show="testResult && !testResult.ok" x-cloak class="text-[12px] font-semibold inline-flex items-center gap-1" style="color:#b91c1c" x-text="'⚠ ' + (testResult ? testResult.error : '')"></span>
                    </div>
                    <div x-show="testResult && testResult.ok && testResult.preview" x-cloak class="rounded-lg p-3 text-[10.5px] font-mono" style="background:#0f0d18;color:#a7f3d0;word-break:break-all">
                        <div class="text-[10px] uppercase tracking-[0.14em] mb-1" style="color:#86efac">JWT preview · valid for 2h</div>
                        <span x-text="testResult.preview"></span>
                    </div>
                </div>
            </div>

            <!-- LiveKit config (placeholder · stub) -->
            <div x-show="provider === 'livekit'" x-cloak class="space-y-2 pt-2" style="border-top:1px solid var(--border)">
                <div class="rounded-xl p-3 text-[12px]" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e">
                    <i class="lucide lucide-clock text-[12px]"></i> <strong>LiveKit en BETA.</strong> Las llamadas se firman correctamente pero el frontend SDK aún no está embebido. Si configurás keys + URL, el sistema hace fallback a Jitsi automáticamente.
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">URL del cliente LiveKit</label>
                    <input name="livekit_url" class="input" value="<?= $e($settings['livekit_url'] ?? '') ?>" placeholder="https://tu-app.livekit.cloud">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block">API Key</label>
                        <input name="livekit_api_key" class="input" value="<?= $e($settings['livekit_api_key'] ?? '') ?>" placeholder="API_xxxx">
                    </div>
                    <div>
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block">API Secret</label>
                        <input type="password" name="livekit_api_secret" class="input" value="<?= $e($settings['livekit_api_secret'] ?? '') ?>" placeholder="••••">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-pad space-y-3" style="<?= !$aiAvailable ? 'opacity:.6' : '' ?>">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-[15px] flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-[0.14em]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white">
                        <i class="lucide lucide-sparkles text-[10px]"></i> Kyros IA
                    </span>
                </h3>
                <?php if (!$aiAvailable): ?>
                    <span class="badge badge-amber text-[10px]">No asignada</span>
                <?php endif; ?>
            </div>
            <?php if (!$aiAvailable): ?>
                <p class="text-[12px] text-ink-500">Kyros IA requiere plan Enterprise + asignación del equipo Kydesk. Las opciones quedan deshabilitadas hasta entonces.</p>
            <?php endif; ?>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <div>
                    <div>Análisis automático al reservar</div>
                    <div class="text-[11px] text-ink-400">Detecta intent, sentiment, urgencia y resumen al instante</div>
                </div>
                <input type="checkbox" name="ai_auto_analyze" value="1" <?= (int)($settings['ai_auto_analyze'] ?? 1)?'checked':'' ?> <?= !$aiAvailable?'disabled':'' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <div>
                    <div>Suggester en página pública</div>
                    <div class="text-[11px] text-ink-400">Cliente describe necesidad, IA recomienda tipo</div>
                </div>
                <input type="checkbox" name="ai_public_suggester" value="1" <?= (int)($settings['ai_public_suggester'] ?? 1)?'checked':'' ?> <?= !$aiAvailable?'disabled':'' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <div>
                    <div>Briefing pre-meeting on-demand</div>
                    <div class="text-[11px] text-ink-400">Botón en el detalle para generar brief con IA</div>
                </div>
                <input type="checkbox" name="ai_briefing_enabled" value="1" <?= (int)($settings['ai_briefing_enabled'] ?? 1)?'checked':'' ?> <?= !$aiAvailable?'disabled':'' ?>>
            </label>

            <?php if ($aiAvailable && $meetingAiUsage): ?>
                <div class="pt-3 mt-2" style="border-top:1px solid var(--border)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-2">Consumo IA del módulo este mes</div>
                    <div class="grid grid-cols-3 gap-2 text-[12px]">
                        <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                            <div class="text-ink-400 text-[10px] uppercase tracking-[0.1em]">Llamadas</div>
                            <div class="font-mono font-bold text-ink-700"><?= number_format((int)$meetingAiUsage['reqs']) ?></div>
                        </div>
                        <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                            <div class="text-ink-400 text-[10px] uppercase tracking-[0.1em]">Tokens in</div>
                            <div class="font-mono font-bold text-ink-700"><?= number_format((int)$meetingAiUsage['tin']) ?></div>
                        </div>
                        <div class="rounded-lg p-2" style="background:#fafafb;border:1px solid var(--border)">
                            <div class="text-ink-400 text-[10px] uppercase tracking-[0.1em]">Tokens out</div>
                            <div class="font-mono font-bold text-ink-700"><?= number_format((int)$meetingAiUsage['tout']) ?></div>
                        </div>
                    </div>
                    <p class="text-[11px] text-ink-400 mt-2">Se descuentan de la cuota IA asignada al workspace · <a href="<?= $url('/t/' . $slug . '/ai') ?>" class="text-brand-700">ver detalles</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex gap-2 sticky bottom-4">
            <a href="<?= $e($publicUrl) ?>" target="_blank" class="btn btn-outline btn-sm flex-1"><i class="lucide lucide-eye"></i> Vista previa</a>
            <button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-check"></i> Guardar</button>
        </div>
    </div>
</form>
