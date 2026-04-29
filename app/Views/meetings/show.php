<?php
use App\Controllers\MeetingController;
$slug = $tenant->slug;
$m = $meeting;
[$lbl, $cls, $ico] = MeetingController::STATUS_LABELS[$m['status']] ?? [ucfirst($m['status']), 'badge-gray', 'circle'];
$when = strtotime($m['scheduled_at']);
$ends = strtotime($m['ends_at']);
$customAnswers = !empty($m['custom_answers']) ? json_decode($m['custom_answers'], true) : [];
$publicSlug = $app->db->val('SELECT public_slug FROM meeting_settings WHERE tenant_id=?', [$tenant->id]) ?: $slug;
$manageUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($publicSlug) . '/manage/' . $m['public_token'];
?>

<?php if ($conferenceConfig): ?>
<script>
function conferencePanel(cfg) {
    return {
        cfg: cfg,
        api: null,
        open: false,
        ready: false,
        copied: false,
        joinUrl() {
            return cfg.joinUrl || '';
        },
        copyLink() {
            navigator.clipboard.writeText(this.joinUrl());
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 1500);
        },
        async join() {
            if (cfg.provider !== 'jitsi') {
                alert(cfg.message || 'Provider no soportado');
                return;
            }
            // meet.jit.si gratis no soporta embed en producción → abrir en pestaña nueva
            if (cfg.embedMode === 'new_tab') {
                window.open(this.joinUrl(), '_blank', 'noopener');
                return;
            }
            // Embed con SDK (8x8.vc o self-hosted)
            this.open = true;
            this.ready = false;
            if (!window.JitsiMeetExternalAPI) {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://' + cfg.domain + '/external_api.js';
                    s.onload = resolve;
                    s.onerror = () => reject(new Error('No se pudo cargar Jitsi'));
                    document.head.appendChild(s);
                });
            }
            await this.$nextTick();
            const opts = {
                roomName: cfg.roomName,
                parentNode: this.$refs.container,
                width: '100%',
                height: '100%',
                userInfo: cfg.userInfo,
                configOverwrite: cfg.configOverwrite || {},
                interfaceConfigOverwrite: cfg.interfaceConfigOverwrite || {},
            };
            if (cfg.jwt) opts.jwt = cfg.jwt;
            this.api = new window.JitsiMeetExternalAPI(cfg.domain, opts);
            this.api.addEventListener('videoConferenceJoined', () => { this.ready = true; });
            this.api.addEventListener('readyToClose', () => { this.leave(); });
        },
        leave() {
            if (this.api) { try { this.api.dispose(); } catch (e) {} this.api = null; }
            this.open = false;
            this.ready = false;
        },
    };
}
</script>
<?php endif; ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="flex items-center gap-2 text-[12px] text-ink-400">
        <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
        <a href="<?= $url('/t/' . $slug . '/meetings/list') ?>" class="hover:text-ink-700">Lista</a> /
        <span class="font-mono"><?= $e($m['code']) ?></span>
    </div>
    <span class="badge <?= $cls ?>"><i class="lucide lucide-<?= $ico ?> text-[11px]"></i> <?= $lbl ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <?php if ($conferenceConfig):
            $providerLabel = $conferenceConfig['provider'] === 'livekit' ? 'LiveKit' : 'Jitsi Meet';
            $isAudioOnly = !empty($conferenceConfig['audioOnly']);
            $whenTs = strtotime($m['scheduled_at']);
            $endTs  = strtotime($m['ends_at']);
            $minutesToStart = (int)(($whenTs - time()) / 60);
            $canJoin = !in_array($m['status'], ['cancelled','no_show','completed'], true);
        ?>
        <div class="card overflow-hidden" x-data="conferencePanel(<?= htmlspecialchars(json_encode($conferenceConfig), ENT_QUOTES) ?>)">
            <div class="px-5 py-4 flex items-center justify-between gap-3 flex-wrap" style="background:linear-gradient(135deg,#0f0d18 0%,#1a1530 60%,#2a1f3d 100%);color:white">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl grid place-items-center" style="background:rgba(167,139,250,.18);color:#c4b5fd;border:1px solid rgba(167,139,250,.3)">
                        <i class="lucide lucide-<?= $isAudioOnly ? 'phone' : 'video' ?> text-[18px]"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-display font-extrabold text-[16px]"><?= $isAudioOnly ? 'Llamada de audio' : 'Video conferencia' ?></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-[0.14em]" style="background:rgba(167,139,250,.18);color:#c4b5fd;border:1px solid rgba(167,139,250,.3)"><?= $e($providerLabel) ?></span>
                        </div>
                        <div class="text-[11.5px]" style="color:rgba(255,255,255,.6)">
                            <?php if ($minutesToStart > 60): ?>
                                Empieza <?= $minutesToStart < 1440 ? 'en ' . floor($minutesToStart / 60) . 'h ' . ($minutesToStart % 60) . 'min' : 'el ' . date('d/m', $whenTs) . ' a las ' . date('H:i', $whenTs) ?>
                            <?php elseif ($minutesToStart > 0): ?>
                                Empieza en <?= $minutesToStart ?> min · room ya disponible
                            <?php elseif (time() < $endTs): ?>
                                <span style="color:#86efac">● En curso</span>
                            <?php else: ?>
                                Reunión finalizada · podés volver a entrar
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($canJoin):
                        $newTabMode = ($conferenceConfig['embedMode'] ?? 'iframe') === 'new_tab';
                    ?>
                        <button @click="join()" class="inline-flex items-center gap-2 h-10 px-5 rounded-xl font-semibold text-[13.5px] transition" style="background:white;color:#0f0d18;box-shadow:0 4px 14px -4px rgba(255,255,255,.3)">
                            <i class="lucide lucide-<?= $newTabMode ? 'external-link' : 'video' ?> text-[14px]"></i>
                            <?= $newTabMode ? 'Abrir conferencia' : 'Iniciar conferencia' ?>
                        </button>
                        <button @click="copyLink()" class="inline-flex items-center justify-center h-10 w-10 rounded-xl" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.12)" :data-tooltip="copied ? '¡Copiado!' : 'Copiar enlace'">
                            <i class="lucide lucide-copy text-[13px]" x-show="!copied"></i>
                            <i class="lucide lucide-check text-[13px]" x-show="copied" x-cloak></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (($conferenceConfig['embedMode'] ?? 'iframe') === 'new_tab'): ?>
                <div class="px-5 py-2.5 text-[11.5px] flex items-center gap-2" style="background:#fffbeb;border-bottom:1px solid #fde68a;color:#92400e">
                    <i class="lucide lucide-info text-[12px]"></i>
                    <span><strong>meet.jit.si gratis:</strong> abrimos en pestaña nueva para evitar el límite de 5 min en embed. Para embebido sin cortes configurá <a href="<?= $url('/t/' . $slug . '/meetings/settings') ?>" class="underline font-semibold">8x8.vc o self-hosted</a>.</span>
                </div>
            <?php endif; ?>
            <?php if (!empty($m['meeting_url'])): ?>
                <div class="px-5 py-2.5 text-[11.5px] flex items-center gap-2 truncate" style="background:#fafafb;border-bottom:1px solid var(--border);color:var(--ink-500)">
                    <i class="lucide lucide-link-2 text-[12px]"></i>
                    <span class="font-mono truncate"><?= $e($m['meeting_url']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Embed area (toggled) -->
            <div x-show="open" x-cloak x-transition>
                <div class="relative" style="background:#000;height:600px">
                    <div x-show="!ready" class="absolute inset-0 grid place-items-center text-white">
                        <div class="text-center">
                            <i class="lucide lucide-loader-2 text-[28px] animate-spin block mb-3"></i>
                            <div class="text-[13px]" style="color:rgba(255,255,255,.7)">Cargando conferencia...</div>
                        </div>
                    </div>
                    <div id="conf-host-container" x-ref="container" class="absolute inset-0"></div>
                </div>
                <div class="px-5 py-3 flex items-center justify-between" style="background:#fafafb;border-top:1px solid var(--border)">
                    <div class="text-[12px] text-ink-500"><i class="lucide lucide-shield-check text-[12px]"></i> Conexión cifrada peer-to-peer · sin grabación automática</div>
                    <button @click="leave()" class="text-[12px] font-semibold text-ink-700 hover:text-ink-900">Salir y minimizar</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card card-pad">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 rounded-2xl grid place-items-center flex-shrink-0" style="background:<?= $e($m['type_color'] ?? '#7c5cff') ?>22;color:<?= $e($m['type_color'] ?? '#7c5cff') ?>">
                    <i class="lucide lucide-<?= $e($m['type_icon'] ?? 'video') ?> text-[24px]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em]"><?= $e($m['type_name'] ?? 'Reunión') ?></h1>
                    <div class="text-[13px] text-ink-500"><?= (int)$m['duration_minutes'] ?> minutos</div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-xl p-3" style="background:var(--bg)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Cuándo</div>
                    <div class="font-display font-bold text-[16px] mt-0.5"><?= date('l j M Y', $when) ?></div>
                    <div class="text-[13px] text-ink-700"><?= date('H:i', $when) ?> - <?= date('H:i', $ends) ?></div>
                    <div class="text-[11px] text-ink-400"><?= $e($m['timezone']) ?></div>
                </div>
                <div class="rounded-xl p-3" style="background:var(--bg)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Dónde</div>
                    <div class="font-display font-bold text-[14px] mt-0.5"><?= ['virtual'=>'Virtual','phone'=>'Llamada','in_person'=>'Presencial','custom'=>'Custom'][$m['location_type']] ?? $m['location_type'] ?></div>
                    <?php if (!empty($m['meeting_url'])): ?>
                        <a href="<?= $e($m['meeting_url']) ?>" target="_blank" class="text-[12px] text-brand-700 truncate inline-block max-w-full"><i class="lucide lucide-external-link text-[11px]"></i> Abrir enlace</a>
                    <?php elseif (!empty($m['location_value'])): ?>
                        <div class="text-[12px] text-ink-700"><?= $e($m['location_value']) ?></div>
                    <?php else: ?>
                        <div class="text-[12px] text-ink-400 italic">Sin definir</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cliente -->
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Cliente</h3>
            <div class="grid grid-cols-2 gap-3 text-[13px]">
                <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Nombre</span><div class="font-semibold"><?= $e($m['customer_name']) ?></div></div>
                <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Email</span><div class="font-semibold"><a href="mailto:<?= $e($m['customer_email']) ?>" class="text-brand-700"><?= $e($m['customer_email']) ?></a></div></div>
                <?php if (!empty($m['customer_phone'])): ?>
                    <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Teléfono</span><div class="font-semibold"><?= $e($m['customer_phone']) ?></div></div>
                <?php endif; ?>
                <?php if (!empty($m['customer_company'])): ?>
                    <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Empresa</span><div class="font-semibold"><?= $e($m['customer_company']) ?></div></div>
                <?php endif; ?>
                <?php if (!empty($m['company_name'])): ?>
                    <div><span class="text-[11px] text-ink-400 uppercase tracking-wide">Empresa vinculada</span><div class="font-semibold"><?= $e($m['company_name']) ?></div></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($m['notes'])): ?>
                <div>
                    <span class="text-[11px] text-ink-400 uppercase tracking-wide">Mensaje</span>
                    <div class="mt-1 p-3 rounded-xl text-[13px] text-ink-700" style="background:var(--bg);white-space:pre-wrap"><?= $e($m['notes']) ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($customAnswers)): ?>
                <div class="space-y-2">
                    <span class="text-[11px] text-ink-400 uppercase tracking-wide">Respuestas</span>
                    <?php foreach ($customAnswers as $ans): ?>
                        <div class="p-3 rounded-xl" style="background:var(--bg)">
                            <div class="text-[11px] text-ink-400"><?= $e($ans['label'] ?? '') ?></div>
                            <div class="text-[13px] text-ink-700 mt-0.5"><?= $e($ans['value'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($aiAvailable):
            $intentColors = [
                'sales'       => ['#10b981','#ecfdf5','rocket','Ventas'],
                'support'     => ['#0ea5e9','#e0f2fe','life-buoy','Soporte'],
                'demo'        => ['#7c5cff','#f3f0ff','play-circle','Demo'],
                'consultation'=> ['#f59e0b','#fffbeb','message-circle','Consulta'],
                'complaint'   => ['#ef4444','#fef2f2','alert-triangle','Queja'],
                'partnership' => ['#a855f7','#faf5ff','handshake','Partnership'],
                'other'       => ['#6b7280','#f3f4f6','help-circle','Otro'],
            ];
            $sentimentColors = [
                'positive' => ['#10b981','#ecfdf5','smile','Positivo'],
                'neutral'  => ['#6b7280','#f3f4f6','minus-circle','Neutral'],
                'negative' => ['#ef4444','#fef2f2','frown','Negativo'],
            ];
            $urgencyColors = [
                'low'    => ['#10b981','#ecfdf5','Bajo'],
                'medium' => ['#f59e0b','#fffbeb','Medio'],
                'high'   => ['#ef4444','#fef2f2','Alto'],
            ];
        ?>
        <!-- AI Insights -->
        <div class="card overflow-hidden"
             x-data="meetingAi(<?= htmlspecialchars(json_encode([
                 'analyzeUrl'  => $url('/t/' . $slug . '/meetings/' . $m['id'] . '/ai/analyze'),
                 'briefingUrl' => $url('/t/' . $slug . '/meetings/' . $m['id'] . '/ai/briefing'),
                 'followupUrl' => $url('/t/' . $slug . '/meetings/' . $m['id'] . '/ai/followup'),
                 'csrf' => $csrf,
                 'briefing' => $m['ai_briefing'] ?? '',
                 'followup' => $m['ai_followup'] ?? '',
             ]), ENT_QUOTES) ?>)">
            <div class="px-5 py-3.5 flex items-center justify-between" style="border-bottom:1px solid var(--border);background:linear-gradient(180deg,#fafafb,white)">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg grid place-items-center" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white"><i class="lucide lucide-sparkles text-[13px]"></i></div>
                    <h3 class="font-display font-bold text-[15px]">Kyros IA · Insights</h3>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="reanalyze()" :disabled="loading.analyze" class="text-[11.5px] font-medium px-2.5 py-1 rounded-lg border transition inline-flex items-center gap-1 disabled:opacity-50" style="border-color:#cdbfff;color:#5a3aff;background:#f3f0ff" data-tooltip="Re-analizar (consume cuota)">
                        <i class="lucide lucide-refresh-cw text-[11px]" :class="loading.analyze && 'animate-spin'"></i>
                        <span x-text="loading.analyze ? 'Analizando…' : 'Re-analizar'"></span>
                    </button>
                </div>
            </div>
            <div class="p-5 space-y-4">
                <!-- Pills: intent + sentiment + urgency -->
                <?php if (!empty($m['ai_processed_at']) || !empty($m['ai_intent'])): ?>
                    <div class="flex flex-wrap gap-2" x-show="!loading.analyze">
                        <?php if (!empty($m['ai_intent']) && isset($intentColors[$m['ai_intent']])):
                            [$ic, $ibg, $iico, $ilbl] = $intentColors[$m['ai_intent']];
                        ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold" style="background:<?= $ibg ?>;color:<?= $ic ?>;border:1px solid <?= $ic ?>33"><i class="lucide lucide-<?= $iico ?> text-[11px]"></i> Intent: <?= $e($ilbl) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($m['ai_sentiment']) && isset($sentimentColors[$m['ai_sentiment']])):
                            [$sc, $sbg, $sico, $slbl] = $sentimentColors[$m['ai_sentiment']];
                        ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold" style="background:<?= $sbg ?>;color:<?= $sc ?>;border:1px solid <?= $sc ?>33"><i class="lucide lucide-<?= $sico ?> text-[11px]"></i> <?= $e($slbl) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($m['ai_urgency']) && isset($urgencyColors[$m['ai_urgency']])):
                            [$uc, $ubg, $ulbl] = $urgencyColors[$m['ai_urgency']];
                        ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold" style="background:<?= $ubg ?>;color:<?= $uc ?>;border:1px solid <?= $uc ?>33"><i class="lucide lucide-zap text-[11px]"></i> Urgencia: <?= $e($ulbl) ?></span>
                        <?php endif; ?>
                        <?php foreach ($aiTopics as $topic): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium" style="background:#f3f0ff;color:#5a3aff"><?= $e($topic) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($m['ai_summary'])): ?>
                        <div class="rounded-xl p-3 text-[13px] text-ink-700 leading-relaxed" style="background:#fafafb;border:1px solid var(--border);white-space:pre-wrap"><?= $e($m['ai_summary']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($m['ai_processed_at'])): ?>
                        <div class="text-[10.5px] text-ink-400">Analizado el <?= date('d/m/Y H:i', strtotime($m['ai_processed_at'])) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-[12.5px] text-ink-500 italic">Sin análisis IA todavía. Pulsá "Re-analizar" para generar uno.</div>
                <?php endif; ?>

                <!-- Briefing pre-meeting -->
                <div class="pt-4" style="border-top:1px solid var(--border)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[12px] font-bold uppercase tracking-[0.14em] text-ink-500 inline-flex items-center gap-1.5">
                            <i class="lucide lucide-file-text text-[12px]"></i> Briefing pre-meeting
                        </div>
                        <button @click="generateBriefing()" :disabled="loading.briefing" class="text-[11.5px] font-medium px-2.5 py-1 rounded-lg inline-flex items-center gap-1 disabled:opacity-50" style="background:#7c5cff;color:white">
                            <i class="lucide lucide-sparkles text-[11px]" x-show="!loading.briefing"></i>
                            <i class="lucide lucide-loader-2 text-[11px] animate-spin" x-show="loading.briefing" x-cloak></i>
                            <span x-text="loading.briefing ? 'Generando…' : (briefing ? 'Re-generar' : 'Generar briefing')"></span>
                        </button>
                    </div>
                    <div x-show="briefing" x-cloak class="rounded-xl p-3 text-[13px] text-ink-700 leading-relaxed" style="background:#f3f0ff;border:1px solid #cdbfff;white-space:pre-wrap;font-family:'Inter',sans-serif" x-text="briefing"></div>
                    <p x-show="!briefing" x-cloak class="text-[12px] text-ink-400 italic">Generá un briefing personalizado con: resumen del cliente, contexto de la solicitud, preguntas sugeridas y action items previos.</p>
                </div>

                <!-- Follow-up post-meeting -->
                <?php if (in_array($m['status'], ['completed','no_show'], true)): ?>
                <div class="pt-4" style="border-top:1px solid var(--border)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[12px] font-bold uppercase tracking-[0.14em] text-ink-500 inline-flex items-center gap-1.5">
                            <i class="lucide lucide-mail text-[12px]"></i> Email de follow-up
                        </div>
                        <button @click="generateFollowup()" :disabled="loading.followup" class="text-[11.5px] font-medium px-2.5 py-1 rounded-lg inline-flex items-center gap-1 disabled:opacity-50" style="background:#10b981;color:white">
                            <i class="lucide lucide-sparkles text-[11px]" x-show="!loading.followup"></i>
                            <i class="lucide lucide-loader-2 text-[11px] animate-spin" x-show="loading.followup" x-cloak></i>
                            <span x-text="loading.followup ? 'Generando…' : (followup ? 'Re-generar' : 'Redactar follow-up')"></span>
                        </button>
                    </div>
                    <textarea x-show="!loading.followup && !showFollowupForm && !followup" x-cloak @click="showFollowupForm = true" rows="2" class="input mb-2" style="height:auto;padding:10px 12px;font-size:12.5px" placeholder="Notas opcionales sobre la reunión (alimentan al follow-up)..."></textarea>
                    <textarea x-show="showFollowupForm" x-model="hostNotes" x-cloak rows="3" class="input mb-2" style="height:auto;padding:10px 12px;font-size:12.5px" placeholder="Notas opcionales sobre la reunión (qué se discutió, próximos pasos)..."></textarea>
                    <div x-show="followup" x-cloak class="rounded-xl p-3 text-[13px] text-ink-700 leading-relaxed" style="background:#ecfdf5;border:1px solid #a7f3d0;white-space:pre-wrap" x-text="followup"></div>
                    <div x-show="followup" x-cloak class="mt-2 flex gap-2">
                        <button type="button" @click="copyFollowup()" class="text-[11.5px] font-medium px-2.5 py-1 rounded-lg inline-flex items-center gap-1" style="background:#fafafb;border:1px solid var(--border);color:var(--ink-700)">
                            <i class="lucide lucide-copy text-[11px]"></i>
                            <span x-text="copied ? 'Copiado!' : 'Copiar'"></span>
                        </button>
                        <a :href="'mailto:' + encodeURIComponent('<?= $e($m['customer_email']) ?>') + '?subject=' + encodeURIComponent('Follow-up: <?= $e($m['type_name'] ?? 'Reunión') ?>') + '&body=' + encodeURIComponent(followup)" class="text-[11.5px] font-medium px-2.5 py-1 rounded-lg inline-flex items-center gap-1" style="background:#10b981;color:white">
                            <i class="lucide lucide-send text-[11px]"></i> Abrir en email
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div x-show="error" x-cloak class="text-[12px] px-3 py-2 rounded-lg" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca" x-text="error"></div>
            </div>
        </div>
        <script>
        function meetingAi(cfg) {
            return {
                analyzeUrl: cfg.analyzeUrl,
                briefingUrl: cfg.briefingUrl,
                followupUrl: cfg.followupUrl,
                csrf: cfg.csrf,
                briefing: cfg.briefing || '',
                followup: cfg.followup || '',
                hostNotes: '',
                showFollowupForm: false,
                loading: { analyze: false, briefing: false, followup: false },
                error: '',
                copied: false,
                async post(url, body) {
                    body = body || {};
                    body._csrf = this.csrf;
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(body),
                    });
                    return r.json();
                },
                async reanalyze() {
                    this.error = ''; this.loading.analyze = true;
                    try {
                        const j = await this.post(this.analyzeUrl);
                        if (j.ok) location.reload();
                        else this.error = j.error || 'Error al analizar';
                    } catch (e) { this.error = 'Error de red'; }
                    this.loading.analyze = false;
                },
                async generateBriefing() {
                    this.error = ''; this.loading.briefing = true;
                    try {
                        const j = await this.post(this.briefingUrl);
                        if (j.ok) this.briefing = j.briefing;
                        else this.error = j.error || 'Error generando briefing';
                    } catch (e) { this.error = 'Error de red'; }
                    this.loading.briefing = false;
                },
                async generateFollowup() {
                    this.error = ''; this.loading.followup = true; this.showFollowupForm = true;
                    try {
                        const j = await this.post(this.followupUrl, { host_notes: this.hostNotes });
                        if (j.ok) this.followup = j.email;
                        else this.error = j.error || 'Error generando follow-up';
                    } catch (e) { this.error = 'Error de red'; }
                    this.loading.followup = false;
                },
                copyFollowup() {
                    navigator.clipboard.writeText(this.followup);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 1500);
                },
            };
        }
        </script>
        <?php endif; ?>

        <!-- Editar -->
        <?php if ($auth->can('meetings.edit') && !in_array($m['status'], ['cancelled','no_show'], true)): ?>
        <details class="card card-pad">
            <summary class="cursor-pointer font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-pencil text-[14px]"></i> Editar reunión</summary>
            <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Host</label>
                    <select name="host_user_id" class="input">
                        <option value="0">— Sin host —</option>
                        <?php foreach ($hosts as $h): ?>
                            <option value="<?= (int)$h['id'] ?>" <?= (int)$m['host_user_id']===(int)$h['id']?'selected':'' ?>><?= $e($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Empresa vinculada</label>
                    <select name="company_id" class="input">
                        <option value="0">—</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$m['company_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Cliente</label>
                    <input name="customer_name" class="input" value="<?= $e($m['customer_name']) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Email</label>
                    <input type="email" name="customer_email" class="input" value="<?= $e($m['customer_email']) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Teléfono</label>
                    <input name="customer_phone" class="input" value="<?= $e($m['customer_phone'] ?? '') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Empresa (texto)</label>
                    <input name="customer_company" class="input" value="<?= $e($m['customer_company'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">URL de la reunión</label>
                    <input type="url" name="meeting_url" class="input" value="<?= $e($m['meeting_url'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Detalle de ubicación</label>
                    <input name="location_value" class="input" value="<?= $e($m['location_value'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Asunto</label>
                    <input name="subject" class="input" value="<?= $e($m['subject'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Notas internas</label>
                    <textarea name="notes" rows="2" class="input" style="height:auto;padding:12px 16px"><?= $e($m['notes'] ?? '') ?></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-check"></i> Guardar cambios</button>
                </div>
            </form>
        </details>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Acciones</h3>

            <?php if ($auth->can('meetings.edit') && $m['status'] === 'scheduled'): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/confirm') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-primary btn-sm w-full"><i class="lucide lucide-check-circle"></i> Confirmar</button>
                </form>
            <?php endif; ?>

            <?php if ($auth->can('meetings.edit') && in_array($m['status'], ['scheduled','confirmed'], true)): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/complete') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-soft btn-sm w-full"><i class="lucide lucide-check-check"></i> Marcar completada</button>
                </form>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/no-show') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-outline btn-sm w-full"><i class="lucide lucide-user-x"></i> No-show</button>
                </form>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/cancel') ?>" onsubmit="return confirm('¿Cancelar la reunión y notificar al cliente?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input name="cancel_reason" class="input mb-2" placeholder="Motivo (opcional)">
                    <button class="btn btn-danger btn-sm w-full"><i class="lucide lucide-x-circle"></i> Cancelar reunión</button>
                </form>
            <?php endif; ?>

            <?php if (!empty($m['cancel_reason'])): ?>
                <div class="rounded-xl p-3" style="background:#fef2f2;border:1px solid #fecaca">
                    <div class="text-[11px] font-bold uppercase tracking-[0.14em]" style="color:#991b1b">Motivo de cancelación</div>
                    <div class="text-[12.5px] mt-1" style="color:#7f1d1d"><?= $e($m['cancel_reason']) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($auth->can('meetings.delete')): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/' . $m['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar permanentemente esta reunión?')">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-ghost btn-sm w-full text-[#ef4444]"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card card-pad space-y-2 text-[12.5px]">
            <h3 class="font-display font-bold text-[15px] mb-1">Detalles</h3>
            <div class="flex justify-between"><span class="text-ink-400">Código</span><span class="font-mono"><?= $e($m['code']) ?></span></div>
            <div class="flex justify-between"><span class="text-ink-400">Host</span><span><?= $e($m['host_name'] ?? '—') ?></span></div>
            <div class="flex justify-between"><span class="text-ink-400">Origen</span><span><?= ['public'=>'Página pública','manual'=>'Manual','import'=>'Importado'][$m['source']] ?? $m['source'] ?></span></div>
            <div class="flex justify-between"><span class="text-ink-400">Creada</span><span><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></span></div>
            <?php if (!empty($m['confirmation_sent_at'])): ?>
                <div class="flex justify-between"><span class="text-ink-400">Confirmación enviada</span><span><?= date('d/m H:i', strtotime($m['confirmation_sent_at'])) ?></span></div>
            <?php endif; ?>
            <div class="pt-2 mt-2" style="border-top:1px solid var(--border)">
                <a href="<?= $e($manageUrl) ?>" target="_blank" class="text-[12px] text-brand-700"><i class="lucide lucide-external-link text-[11px]"></i> Enlace público del cliente</a>
            </div>
        </div>
    </div>
</div>
