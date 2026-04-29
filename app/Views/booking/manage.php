<?php
$brandColor = $settings['primary_color'] ?: '#7c5cff';
$publicSlug = $settings['public_slug'] ?: $tenant->slug;
$businessName = $settings['business_name'] ?: $tenant->name;
$showPowered = !empty($settings['show_powered_by']);
$when = strtotime($meeting['scheduled_at']);
$ends = strtotime($meeting['ends_at']);
$mesesEs = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$diasEs = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$dateLabel = $diasEs[(int)date('w', $when)] . ' ' . date('j', $when) . ' de ' . $mesesEs[(int)date('n', $when)] . ' de ' . date('Y', $when);

$canCancel = (int)($meeting['allow_cancel'] ?? 1) === 1 && !in_array($meeting['status'], ['cancelled','completed','no_show'], true);
$canReschedule = (int)($meeting['allow_reschedule'] ?? 1) === 1 && !in_array($meeting['status'], ['cancelled','completed','no_show'], true);
$cancelUrl = $url('/book/' . rawurlencode($publicSlug) . '/manage/' . $meeting['public_token'] . '/cancel');
$rescheduleUrl = $url('/book/' . rawurlencode($publicSlug) . '/manage/' . $meeting['public_token'] . '/reschedule');
$slotsUrl = $url('/book/' . rawurlencode($publicSlug) . '/' . rawurlencode($meeting['type_slug'] ?? '') . '/slots.json');

$statusBadge = [
    'scheduled'   => ['Pendiente de confirmación','#fbbf24','#fffbeb'],
    'confirmed'   => ['Confirmada','#10b981','#ecfdf5'],
    'cancelled'   => ['Cancelada','#ef4444','#fef2f2'],
    'completed'   => ['Completada','#7c5cff','#f3f0ff'],
    'no_show'     => ['No-show','#f59e0b','#fffbeb'],
    'rescheduled' => ['Reprogramada','#6b6b78','#f3f4f6'],
];
[$sLbl, $sCol, $sBg] = $statusBadge[$meeting['status']] ?? ['—', '#6b6b78', '#f3f4f6'];
?>
<style>
:root { --book-brand: <?= htmlspecialchars($brandColor) ?>; --book-brand-soft: <?= htmlspecialchars($brandColor) ?>15; --book-brand-mid: <?= htmlspecialchars($brandColor) ?>33; }
.book-shell { min-height: 100vh; background: linear-gradient(180deg, #fafafb 0%, #f3f4f6 100%); }
.book-card { background: white; border: 1px solid #ececef; border-radius: 24px; box-shadow: 0 4px 24px -8px rgba(22,21,27,.06); }
.input { display: block; width: 100%; height: 44px; border-radius: 14px; background: white; border: 1px solid #ececef; padding: 0 16px; font-size: 13.5px; color: #16151b; outline: none; }
textarea.input { padding: 12px 16px; height: auto; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.cal-day-name { text-align: center; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: #8e8e9a; padding: 8px 0; }
.cal-day { aspect-ratio: 1; display: grid; place-items: center; font-size: 14px; font-weight: 600; border-radius: 12px; cursor: pointer; transition: all .12s; color: #16151b; background: white; border: 1px solid transparent; }
.cal-day:hover { border-color: var(--book-brand-mid); background: var(--book-brand-soft); }
.cal-day.disabled { color: #c0c0cc; cursor: not-allowed; opacity: .55; }
.cal-day.disabled:hover { background: white; border-color: transparent; }
.cal-day.selected { background: var(--book-brand); color: white; border-color: var(--book-brand); }
.cal-day.empty { background: transparent; cursor: default; }
.cal-day.empty:hover { background: transparent; border-color: transparent; }
.slot-btn { padding: 10px 12px; border: 1.5px solid #ececef; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; background: white; text-align: center; }
.slot-btn:hover { border-color: var(--book-brand); color: var(--book-brand); }
.slot-btn.selected { background: var(--book-brand); color: white; border-color: var(--book-brand); }
</style>

<div class="book-shell">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <div class="text-center mb-6">
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] text-ink-900">Tu reserva</h1>
            <p class="text-[13.5px] text-ink-500"><?= $e($businessName) ?></p>
        </div>

        <div class="book-card overflow-hidden">
            <!-- header con estado -->
            <div class="px-6 py-5 flex items-center justify-between" style="background:linear-gradient(180deg,#fafafb,white);border-bottom:1px solid #ececef">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($meeting['type_color'] ?? $brandColor) ?>22;color:<?= $e($meeting['type_color'] ?? $brandColor) ?>">
                        <i class="lucide lucide-<?= $e($meeting['type_icon'] ?? 'calendar') ?> text-[16px]"></i>
                    </div>
                    <div>
                        <div class="font-display font-bold text-[15px]"><?= $e($meeting['type_name'] ?? 'Reunión') ?></div>
                        <div class="text-[11.5px] text-ink-400 font-mono"><?= $e($meeting['code']) ?></div>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold" style="background:<?= $sBg ?>;color:<?= $sCol ?>"><?= $e($sLbl) ?></span>
            </div>

            <?php if (!empty($conferenceConfig) && !in_array($meeting['status'], ['cancelled','no_show'], true)):
                $isAudioOnly = !empty($conferenceConfig['audioOnly']);
                $whenTs = strtotime($meeting['scheduled_at']);
                $endTs  = strtotime($meeting['ends_at']);
                $minutesToStart = (int)(($whenTs - time()) / 60);
                $isLive = time() >= $whenTs - 900 && time() < $endTs + 1800; // 15 min antes a 30 min después
            ?>
                <div class="px-6 py-4" style="background:linear-gradient(135deg,#0f0d18 0%,#1a1530 60%,#2a1f3d 100%);border-bottom:1px solid #ececef;color:white"
                     x-data="customerConferencePanel(<?= htmlspecialchars(json_encode($conferenceConfig), ENT_QUOTES) ?>)">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:rgba(167,139,250,.18);color:#c4b5fd">
                                <i class="lucide lucide-<?= $isAudioOnly ? 'phone' : 'video' ?> text-[16px]"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-display font-bold text-[14px]"><?= $isAudioOnly ? 'Llamada de audio' : 'Video conferencia' ?></div>
                                <div class="text-[11.5px]" style="color:rgba(255,255,255,.6)">
                                    <?php if ($minutesToStart > 60): ?>
                                        Se habilita 15 min antes
                                    <?php elseif ($isLive): ?>
                                        <span style="color:#86efac">● Disponible ahora</span>
                                    <?php else: ?>
                                        Reunión finalizada
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php $newTabMode = ($conferenceConfig['embedMode'] ?? 'iframe') === 'new_tab'; ?>
                        <button @click="join()"
                                <?= !$isLive ? 'disabled' : '' ?>
                                class="inline-flex items-center gap-2 h-10 px-5 rounded-xl font-semibold text-[13px] transition disabled:opacity-50 disabled:cursor-not-allowed"
                                style="background:white;color:#0f0d18">
                            <i class="lucide lucide-<?= $newTabMode ? 'external-link' : 'video' ?> text-[14px]"></i>
                            <?= $isLive ? 'Entrar a la reunión' : 'Aún no disponible' ?>
                        </button>
                    </div>

                    <!-- Embed -->
                    <div x-show="open" x-cloak x-transition class="mt-4 rounded-2xl overflow-hidden" style="background:#000">
                        <div class="relative" style="height:520px">
                            <div x-show="!ready" class="absolute inset-0 grid place-items-center">
                                <div class="text-center">
                                    <i class="lucide lucide-loader-2 text-[24px] animate-spin block mb-2" style="color:#a78bfa"></i>
                                    <div class="text-[12px]" style="color:rgba(255,255,255,.6)">Conectando...</div>
                                </div>
                            </div>
                            <div x-ref="container" class="absolute inset-0"></div>
                        </div>
                        <div class="px-4 py-2.5 flex items-center justify-between text-[11.5px]" style="background:rgba(255,255,255,.04);color:rgba(255,255,255,.55)">
                            <span><i class="lucide lucide-shield-check text-[11px]"></i> Conexión cifrada P2P</span>
                            <button @click="leave()" class="font-semibold" style="color:rgba(255,255,255,.85)">Cerrar y volver</button>
                        </div>
                    </div>
                </div>
                <script>
                function customerConferencePanel(cfg) {
                    return {
                        cfg: cfg, api: null, open: false, ready: false,
                        async join() {
                            if (cfg.provider !== 'jitsi') { alert(cfg.message || 'Provider no soportado'); return; }
                            // meet.jit.si gratis: abrir en pestaña nueva (sin límite de 5 min)
                            if (cfg.embedMode === 'new_tab') {
                                window.open(cfg.joinUrl, '_blank', 'noopener');
                                return;
                            }
                            this.open = true; this.ready = false;
                            if (!window.JitsiMeetExternalAPI) {
                                await new Promise((resolve, reject) => {
                                    const s = document.createElement('script');
                                    s.src = 'https://' + cfg.domain + '/external_api.js';
                                    s.onload = resolve; s.onerror = () => reject();
                                    document.head.appendChild(s);
                                });
                            }
                            await this.$nextTick();
                            const opts = {
                                roomName: cfg.roomName,
                                parentNode: this.$refs.container,
                                width: '100%', height: '100%',
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
                            this.open = false; this.ready = false;
                        },
                    };
                }
                </script>
            <?php endif; ?>

            <!-- detalles actuales -->
            <div class="px-6 py-5 space-y-3">
                <div class="flex items-start gap-3">
                    <i class="lucide lucide-calendar text-[14px] text-ink-400 mt-1"></i>
                    <div>
                        <div class="font-display font-bold text-[14.5px] text-ink-900"><?= $e($dateLabel) ?></div>
                        <div class="text-[12px] text-ink-500"><?= date('H:i', $when) ?> - <?= date('H:i', $ends) ?> · <?= (int)$meeting['duration_minutes'] ?> min</div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="lucide lucide-user text-[14px] text-ink-400 mt-1"></i>
                    <div>
                        <div class="font-semibold text-[13.5px] text-ink-900"><?= $e($meeting['customer_name']) ?></div>
                        <div class="text-[12px] text-ink-500"><?= $e($meeting['customer_email']) ?></div>
                    </div>
                </div>
                <?php if (!empty($meeting['meeting_url'])): ?>
                    <div class="flex items-start gap-3">
                        <i class="lucide lucide-link text-[14px] text-ink-400 mt-1"></i>
                        <a href="<?= $e($meeting['meeting_url']) ?>" target="_blank" class="text-[13px] truncate" style="color:var(--book-brand)"><?= $e($meeting['meeting_url']) ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- acciones -->
            <?php if ($canReschedule || $canCancel): ?>
            <div class="px-6 py-4 flex flex-wrap gap-2" style="background:#fafafb;border-top:1px solid #ececef" x-data="{ tab:'' }">
                <?php if ($canReschedule): ?>
                    <button @click="tab = tab === 'reschedule' ? '' : 'reschedule'" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-[13px] font-semibold transition" :style="tab === 'reschedule' ? 'background:var(--book-brand);color:white' : 'background:white;border:1px solid #ececef;color:#16151b'">
                        <i class="lucide lucide-rotate-cw text-[14px]"></i> Reprogramar
                    </button>
                <?php endif; ?>
                <?php if ($canCancel): ?>
                    <button @click="tab = tab === 'cancel' ? '' : 'cancel'" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-[13px] font-semibold transition" :style="tab === 'cancel' ? 'background:#ef4444;color:white' : 'background:white;border:1px solid #ececef;color:#16151b'">
                        <i class="lucide lucide-x-circle text-[14px]"></i> Cancelar
                    </button>
                <?php endif; ?>

                <!-- Cancel form -->
                <?php if ($canCancel): ?>
                    <form x-show="tab === 'cancel'" x-cloak method="POST" action="<?= $e($cancelUrl) ?>" class="w-full mt-3 p-4 rounded-2xl space-y-3" style="background:#fef2f2;border:1px solid #fecaca">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <h3 class="font-display font-bold text-[14px]" style="color:#991b1b">¿Cancelar tu reserva?</h3>
                        <textarea name="cancel_reason" rows="2" class="input" placeholder="Motivo (opcional)"></textarea>
                        <div class="flex gap-2">
                            <button type="button" @click="tab=''" class="flex-1 h-10 rounded-xl border border-[#ececef] bg-white text-[13px] font-semibold">Volver</button>
                            <button class="flex-1 h-10 rounded-xl bg-[#ef4444] text-white text-[13px] font-semibold">Sí, cancelar</button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Reschedule form -->
                <?php if ($canReschedule): ?>
                    <form x-show="tab === 'reschedule'" x-cloak method="POST" action="<?= $e($rescheduleUrl) ?>" class="w-full mt-3"
                        x-data='reschedule(<?= json_encode(["slotsUrl" => $slotsUrl]) ?>)' x-init="init()">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input type="hidden" name="date" :value="selectedDate" required>
                        <input type="hidden" name="time" :value="selectedTime" required>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-2xl" style="background:white;border:1px solid #ececef">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <button type="button" @click="prevMonth()" class="w-7 h-7 grid place-items-center rounded-lg"><i class="lucide lucide-chevron-left text-[14px]"></i></button>
                                    <div class="font-display font-bold text-[13.5px]" x-text="monthLabel"></div>
                                    <button type="button" @click="nextMonth()" class="w-7 h-7 grid place-items-center rounded-lg"><i class="lucide lucide-chevron-right text-[14px]"></i></button>
                                </div>
                                <div class="cal-grid">
                                    <template x-for="d in dayLabels" :key="d"><div class="cal-day-name" x-text="d"></div></template>
                                    <template x-for="day in calendarDays" :key="day.key">
                                        <div :class="['cal-day', day.empty && 'empty', day.disabled && 'disabled', day.selected && 'selected']" @click="!day.disabled && !day.empty && selectDate(day.iso)" x-text="day.label"></div>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <div class="font-display font-bold text-[13px] mb-2" x-text="selectedDate ? 'Horarios disponibles' : 'Elegí una fecha'"></div>
                                <div x-show="loadingSlots" class="text-[12.5px] text-ink-400">Cargando...</div>
                                <div x-show="selectedDate && !loadingSlots && slots.length === 0" class="text-[12.5px] text-ink-400">Sin horarios — probá otro día.</div>
                                <div x-show="selectedDate && !loadingSlots && slots.length > 0" class="grid grid-cols-2 gap-1.5 max-h-[260px] overflow-y-auto pr-1">
                                    <template x-for="s in slots" :key="s.start">
                                        <button type="button" :class="['slot-btn', selectedTime === s.start && 'selected']" @click="selectedTime = s.start" x-text="s.label"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button type="button" @click="tab=''" class="flex-1 h-10 rounded-xl border border-[#ececef] bg-white text-[13px] font-semibold">Volver</button>
                            <button :disabled="!selectedDate || !selectedTime" class="flex-1 h-10 rounded-xl text-white text-[13px] font-semibold disabled:opacity-50" style="background:var(--book-brand)">Confirmar reprogramación</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-6 text-center text-[11px] text-ink-400">
            <a href="<?= $url('/book/' . rawurlencode($publicSlug)) ?>" class="hover:text-ink-700">Volver al inicio</a>
            <?php if ($showPowered): ?> · Powered by <a href="https://kydesk.kyrosrd.com" target="_blank" class="font-semibold text-brand-700">Kydesk</a><?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canReschedule): ?>
<script>
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS_CORTOS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
function reschedule(cfg) {
    return {
        slotsUrl: cfg.slotsUrl,
        viewYear: 0, viewMonth: 0,
        selectedDate: null, selectedTime: null,
        slots: [], loadingSlots: false,
        dayLabels: DIAS_CORTOS,
        init(){ const n=new Date(); this.viewYear=n.getFullYear(); this.viewMonth=n.getMonth(); },
        get monthLabel(){ return MESES[this.viewMonth]+' '+this.viewYear; },
        get calendarDays(){
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const last  = new Date(this.viewYear, this.viewMonth + 1, 0);
            const offset = first.getDay();
            const days = [];
            const today = new Date(); today.setHours(0,0,0,0);
            for (let i = 0; i < offset; i++) days.push({ key:'e'+i, empty:true });
            for (let d = 1; d <= last.getDate(); d++) {
                const cur = new Date(this.viewYear, this.viewMonth, d);
                const iso = cur.getFullYear()+'-'+String(cur.getMonth()+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
                const disabled = cur < today;
                days.push({ key:iso, label:d, iso, disabled, selected: iso===this.selectedDate, empty:false });
            }
            return days;
        },
        prevMonth(){ this.viewMonth--; if(this.viewMonth<0){this.viewMonth=11;this.viewYear--;} },
        nextMonth(){ this.viewMonth++; if(this.viewMonth>11){this.viewMonth=0;this.viewYear++;} },
        async selectDate(iso){
            this.selectedDate = iso; this.selectedTime = null; this.loadingSlots = true; this.slots = [];
            try { const r=await fetch(this.slotsUrl+'?date='+encodeURIComponent(iso)); const j=await r.json(); if(j.ok) this.slots = j.slots||[]; } catch(e){}
            this.loadingSlots = false;
        },
    };
}
</script>
<?php endif; ?>
