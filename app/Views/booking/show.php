<?php
$brandColor = $settings['primary_color'] ?: '#7c5cff';
$publicSlug = $settings['public_slug'] ?: $tenant->slug;
$businessName = $settings['business_name'] ?: $tenant->name;
$showPowered = !empty($settings['show_powered_by']);
$slotsUrl = $url('/book/' . rawurlencode($publicSlug) . '/' . rawurlencode($type['slug']) . '/slots.json');
$confirmUrl = $url('/book/' . rawurlencode($publicSlug) . '/' . rawurlencode($type['slug']) . '/confirm');
?>
<style>
:root { --book-brand: <?= htmlspecialchars($brandColor) ?>; --book-brand-soft: <?= htmlspecialchars($brandColor) ?>15; --book-brand-mid: <?= htmlspecialchars($brandColor) ?>33; }
.book-shell { min-height: 100vh; background: linear-gradient(180deg, #fafafb 0%, #f3f4f6 100%); }
.book-card { background: white; border: 1px solid #ececef; border-radius: 24px; box-shadow: 0 4px 24px -8px rgba(22,21,27,.06); }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.cal-day-name { text-align: center; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: #8e8e9a; padding: 8px 0; }
.cal-day { aspect-ratio: 1; display: grid; place-items: center; font-size: 14px; font-weight: 600; border-radius: 12px; cursor: pointer; transition: all .12s; color: #16151b; background: white; border: 1px solid transparent; }
.cal-day:hover { border-color: var(--book-brand-mid); background: var(--book-brand-soft); }
.cal-day.disabled { color: #c0c0cc; cursor: not-allowed; opacity: .55; }
.cal-day.disabled:hover { background: white; border-color: transparent; }
.cal-day.selected { background: var(--book-brand); color: white; border-color: var(--book-brand); }
.cal-day.today { font-weight: 800; box-shadow: inset 0 0 0 1px var(--book-brand-mid); }
.cal-day.empty { background: transparent; cursor: default; }
.cal-day.empty:hover { background: transparent; border-color: transparent; }
.slot-btn { padding: 11px 14px; border: 1.5px solid #ececef; border-radius: 12px; font-size: 13px; font-weight: 600; transition: all .12s; cursor: pointer; background: white; text-align: center; color: #2a2a33; }
.slot-btn:hover { border-color: var(--book-brand); color: var(--book-brand); }
.slot-btn.selected { background: var(--book-brand); color: white; border-color: var(--book-brand); }
.input { display: block; width: 100%; height: 44px; border-radius: 14px; background: white; border: 1px solid #ececef; padding: 0 16px; font-size: 13.5px; color: #16151b; outline: none; transition: all .15s; font-family: inherit; }
.input:focus { border-color: var(--book-brand-mid); box-shadow: 0 0 0 4px var(--book-brand-soft); }
textarea.input { padding: 12px 16px; height: auto; }
</style>

<div class="book-shell">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <!-- Header con back link -->
        <div class="mb-6">
            <a href="<?= $url('/book/' . rawurlencode($publicSlug)) ?>" class="inline-flex items-center gap-1 text-[12.5px] text-ink-500 hover:text-ink-900 transition">
                <i class="lucide lucide-arrow-left text-[13px]"></i> Otros tipos de reunión
            </a>
        </div>

        <div class="book-card overflow-hidden grid grid-cols-1 lg:grid-cols-3"
            x-data='bookingFlow(<?= json_encode([
                "slotsUrl" => $slotsUrl,
                "earliest" => $earliest,
                "latest"   => $latest,
                "duration" => (int)$type["duration_minutes"],
            ]) ?>)' x-init="init()">

            <!-- Columna izquierda: info del tipo -->
            <div class="lg:col-span-1 p-6 sm:p-8" style="background:linear-gradient(180deg,#fafafb,white);border-right:1px solid #ececef">
                <div class="w-12 h-12 rounded-2xl grid place-items-center mb-4" style="background:<?= $e($type['color']) ?>22;color:<?= $e($type['color']) ?>">
                    <i class="lucide lucide-<?= $e($type['icon']) ?> text-[20px]"></i>
                </div>
                <div class="text-[12px] font-semibold uppercase tracking-[0.14em] text-ink-400 mb-1"><?= $e($businessName) ?></div>
                <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em] text-ink-900 mb-2"><?= $e($type['name']) ?></h1>
                <?php if (!empty($type['description'])): ?>
                    <p class="text-[13.5px] text-ink-500 mb-4"><?= nl2br($e($type['description'])) ?></p>
                <?php endif; ?>
                <div class="space-y-2.5 text-[13px]">
                    <div class="flex items-center gap-2 text-ink-700"><i class="lucide lucide-clock text-[14px] text-ink-400"></i> <?= (int)$type['duration_minutes'] ?> minutos</div>
                    <div class="flex items-center gap-2 text-ink-700">
                        <i class="lucide lucide-<?= $type['location_type']==='virtual'?'video':($type['location_type']==='phone'?'phone':($type['location_type']==='in_person'?'map-pin':'map')) ?> text-[14px] text-ink-400"></i>
                        <?= ['virtual'=>'Videollamada','phone'=>'Llamada telefónica','in_person'=>'Presencial','custom'=>'A coordinar'][$type['location_type']] ?? '—' ?>
                    </div>
                    <div class="flex items-start gap-2 text-ink-700"><i class="lucide lucide-globe text-[14px] text-ink-400 mt-0.5"></i> Zona horaria: <?= $e($settings['timezone']) ?></div>
                </div>
                <div class="mt-4 pt-4 text-[11.5px] text-ink-400" style="border-top:1px solid #ececef">
                    <i class="lucide lucide-info text-[11px]"></i>
                    Aviso mínimo: <?= (int)$type['min_notice_hours'] ?>h · Anticipación máx.: <?= (int)$type['max_advance_days'] ?>d
                </div>
            </div>

            <!-- Columna central / derecha: calendario + slots + form -->
            <div class="lg:col-span-2 p-6 sm:p-8">
                <!-- Step indicator -->
                <div class="flex items-center gap-2 mb-5 text-[11.5px] font-bold uppercase tracking-[0.14em]">
                    <span :class="step >= 1 ? 'text-ink-900' : 'text-ink-400'">1. Fecha</span>
                    <span class="text-ink-400">/</span>
                    <span :class="step >= 2 ? 'text-ink-900' : 'text-ink-400'">2. Hora</span>
                    <span class="text-ink-400">/</span>
                    <span :class="step >= 3 ? 'text-ink-900' : 'text-ink-400'">3. Datos</span>
                </div>

                <!-- STEP 1 + 2: calendario y slots -->
                <div x-show="step <= 2" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Calendario -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <button type="button" @click="prevMonth()" :disabled="!canPrevMonth()" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-bg disabled:opacity-30 disabled:cursor-not-allowed" style="--bg:#f3f4f6">
                                <i class="lucide lucide-chevron-left"></i>
                            </button>
                            <div class="font-display font-bold text-[15px]" x-text="monthLabel"></div>
                            <button type="button" @click="nextMonth()" :disabled="!canNextMonth()" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-bg disabled:opacity-30 disabled:cursor-not-allowed" style="--bg:#f3f4f6">
                                <i class="lucide lucide-chevron-right"></i>
                            </button>
                        </div>
                        <div class="cal-grid">
                            <template x-for="d in dayLabels" :key="d">
                                <div class="cal-day-name" x-text="d"></div>
                            </template>
                            <template x-for="day in calendarDays" :key="day.key">
                                <div :class="['cal-day', day.empty && 'empty', day.disabled && 'disabled', day.selected && 'selected', day.today && 'today']"
                                     @click="!day.disabled && !day.empty && selectDate(day.iso)"
                                     x-text="day.label"></div>
                            </template>
                        </div>
                    </div>

                    <!-- Slots -->
                    <div>
                        <div class="font-display font-bold text-[15px] mb-3" x-text="selectedDate ? selectedDateLabel : 'Elegí una fecha'"></div>
                        <div x-show="!selectedDate" class="text-[13px] text-ink-400 py-8 text-center">
                            <i class="lucide lucide-mouse-pointer-2 text-[20px] block mb-2"></i>
                            Tocá un día disponible para ver los horarios.
                        </div>
                        <div x-show="selectedDate && loadingSlots" class="text-[13px] text-ink-400 py-8 text-center">
                            <i class="lucide lucide-loader-2 text-[20px] block mb-2 animate-spin"></i>
                            Buscando horarios...
                        </div>
                        <div x-show="selectedDate && !loadingSlots && slots.length === 0" class="text-[13px] text-ink-400 py-8 text-center">
                            <i class="lucide lucide-calendar-x text-[20px] block mb-2"></i>
                            Sin horarios para esta fecha. Probá otro día.
                        </div>
                        <div x-show="selectedDate && !loadingSlots && slots.length > 0" class="grid grid-cols-2 gap-2 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="s in slots" :key="s.start">
                                <button type="button" :class="['slot-btn', selectedTime === s.start && 'selected']" @click="selectTime(s.start)" x-text="s.label"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: form -->
                <form x-show="step === 3" method="POST" action="<?= $e($confirmUrl) ?>" class="space-y-3" x-cloak>
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="date" :value="selectedDate">
                    <input type="hidden" name="time" :value="selectedTime">

                    <div class="rounded-xl p-3 mb-2 flex items-start gap-3" style="background:var(--book-brand-soft);border:1px solid var(--book-brand-mid)">
                        <i class="lucide lucide-calendar-check text-[18px] mt-0.5" style="color:var(--book-brand)"></i>
                        <div class="text-[13px]">
                            <div class="font-bold text-ink-900" x-text="selectedDateLabel + ' · ' + selectedTime"></div>
                            <div class="text-ink-500" x-text="duration + ' min · <?= $e($type['name']) ?>'"></div>
                        </div>
                        <button type="button" @click="step = 2" class="ml-auto text-[12px] font-semibold" style="color:var(--book-brand)">Cambiar</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Nombre completo *</label>
                            <input name="name" required class="input" placeholder="Tu nombre">
                        </div>
                        <div>
                            <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Email *</label>
                            <input type="email" name="email" required class="input" placeholder="tu@email.com">
                        </div>
                        <div>
                            <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Teléfono <?= !empty($settings['require_phone']) ? '*' : '(opcional)' ?></label>
                            <input name="phone" <?= !empty($settings['require_phone']) ? 'required' : '' ?> class="input" placeholder="+1 809...">
                        </div>
                        <div>
                            <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Empresa <?= !empty($settings['require_company']) ? '*' : '(opcional)' ?></label>
                            <input name="company" <?= !empty($settings['require_company']) ? 'required' : '' ?> class="input" placeholder="Tu empresa">
                        </div>
                    </div>

                    <div>
                        <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Mensaje (opcional)</label>
                        <textarea name="notes" rows="3" class="input" placeholder="Cualquier detalle que quieras compartir..."></textarea>
                    </div>

                    <?php foreach ($questions as $i => $q): ?>
                        <div>
                            <label class="text-[12px] font-semibold text-ink-700 mb-1 block">
                                <?= $e($q['label']) ?><?= !empty($q['required']) ? ' *' : '' ?>
                            </label>
                            <?php if ($q['type'] === 'textarea'): ?>
                                <textarea name="q_<?= $i ?>" rows="2" <?= !empty($q['required']) ? 'required' : '' ?> class="input"></textarea>
                            <?php elseif ($q['type'] === 'select'): ?>
                                <select name="q_<?= $i ?>" <?= !empty($q['required']) ? 'required' : '' ?> class="input">
                                    <option value="">— Selecciona —</option>
                                    <?php foreach (array_filter(array_map('trim', explode('|', (string)($q['options'] ?? '')))) as $opt): ?>
                                        <option value="<?= $e($opt) ?>"><?= $e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($q['type'] === 'number'): ?>
                                <input type="number" name="q_<?= $i ?>" <?= !empty($q['required']) ? 'required' : '' ?> class="input">
                            <?php elseif ($q['type'] === 'phone'): ?>
                                <input type="tel" name="q_<?= $i ?>" <?= !empty($q['required']) ? 'required' : '' ?> class="input">
                            <?php else: ?>
                                <input type="text" name="q_<?= $i ?>" <?= !empty($q['required']) ? 'required' : '' ?> class="input">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="flex flex-col sm:flex-row gap-2 pt-2">
                        <button type="button" @click="step = 2" class="flex-1 inline-flex items-center justify-center gap-2 h-[44px] rounded-2xl border border-[#ececef] bg-white text-[13.5px] font-semibold hover:border-ink-300"><i class="lucide lucide-arrow-left text-[14px]"></i> Atrás</button>
                        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 h-[44px] rounded-2xl text-white text-[13.5px] font-semibold transition" style="background:var(--book-brand);box-shadow:0 4px 14px -4px <?= $e($brandColor) ?>aa">
                            <i class="lucide lucide-check"></i> Confirmar reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($showPowered): ?>
            <p class="text-center text-[11px] text-ink-400 mt-6">Powered by <a href="https://kydesk.kyrosrd.com" target="_blank" class="font-semibold text-brand-700">Kydesk</a></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($flash['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => alert(<?= json_encode($flash['error']) ?>));</script>
<?php endif; ?>

<script>
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS_CORTOS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
const DIAS_LARGOS = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

function bookingFlow(cfg) {
    return {
        step: 1,
        slotsUrl: cfg.slotsUrl,
        earliestDate: cfg.earliest,
        latestDate: cfg.latest,
        duration: cfg.duration,
        viewYear: 0,
        viewMonth: 0,
        selectedDate: null,
        selectedTime: null,
        slots: [],
        loadingSlots: false,
        dayLabels: DIAS_CORTOS,

        init() {
            const e = new Date(this.earliestDate + 'T00:00:00');
            this.viewYear = e.getFullYear();
            this.viewMonth = e.getMonth();
        },
        get monthLabel() {
            return MESES[this.viewMonth] + ' ' + this.viewYear;
        },
        get selectedDateLabel() {
            if (!this.selectedDate) return '';
            const d = new Date(this.selectedDate + 'T00:00:00');
            return DIAS_LARGOS[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth()];
        },
        get calendarDays() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const last = new Date(this.viewYear, this.viewMonth + 1, 0);
            const offset = first.getDay();
            const days = [];
            const today = new Date(); today.setHours(0,0,0,0);
            const earliestD = new Date(this.earliestDate + 'T00:00:00');
            const latestD = new Date(this.latestDate + 'T00:00:00');
            for (let i = 0; i < offset; i++) days.push({ key: 'e' + i, empty: true });
            for (let d = 1; d <= last.getDate(); d++) {
                const cur = new Date(this.viewYear, this.viewMonth, d);
                const iso = cur.getFullYear() + '-' + String(cur.getMonth()+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                const disabled = cur < earliestD || cur > latestD;
                const selected = iso === this.selectedDate;
                const isToday = cur.getTime() === today.getTime();
                days.push({ key: iso, label: d, iso, disabled, selected, today: isToday, empty: false });
            }
            return days;
        },
        canPrevMonth() {
            const earliestD = new Date(this.earliestDate + 'T00:00:00');
            return new Date(this.viewYear, this.viewMonth, 1) > new Date(earliestD.getFullYear(), earliestD.getMonth(), 1);
        },
        canNextMonth() {
            const latestD = new Date(this.latestDate + 'T00:00:00');
            return new Date(this.viewYear, this.viewMonth + 1, 1) <= new Date(latestD.getFullYear(), latestD.getMonth(), 1);
        },
        prevMonth() { this.viewMonth -= 1; if (this.viewMonth < 0) { this.viewMonth = 11; this.viewYear -= 1; } },
        nextMonth() { this.viewMonth += 1; if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear += 1; } },
        async selectDate(iso) {
            this.selectedDate = iso;
            this.selectedTime = null;
            this.loadingSlots = true;
            this.slots = [];
            this.step = 2;
            try {
                const r = await fetch(this.slotsUrl + '?date=' + encodeURIComponent(iso));
                const j = await r.json();
                if (j.ok) this.slots = j.slots || [];
            } catch (e) { /* keep empty */ }
            this.loadingSlots = false;
        },
        selectTime(t) {
            this.selectedTime = t;
            this.step = 3;
        },
    };
}
</script>
