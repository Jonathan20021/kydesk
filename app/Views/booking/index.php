<?php
$brandColor = $settings['primary_color'] ?: '#7c5cff';
$publicSlug = $settings['public_slug'] ?: $tenant->slug;
$businessName = $settings['business_name'] ?: $tenant->name;
$showPowered = !empty($settings['show_powered_by']);
?>
<style>
:root { --book-brand: <?= htmlspecialchars($brandColor) ?>; --book-brand-soft: <?= htmlspecialchars($brandColor) ?>15; --book-brand-mid: <?= htmlspecialchars($brandColor) ?>33; }
.book-shell { min-height: 100vh; background: linear-gradient(180deg, #fafafb 0%, #f3f4f6 100%); }
.book-card { background: white; border: 1px solid #ececef; border-radius: 24px; box-shadow: 0 4px 24px -8px rgba(22,21,27,.06); }
.book-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; background: var(--book-brand-soft); color: var(--book-brand); border: 1px solid var(--book-brand-mid); }
.book-type-card { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border: 1px solid #ececef; border-radius: 20px; background: white; transition: all .18s; cursor: pointer; }
.book-type-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -16px rgba(22,21,27,.18); border-color: var(--book-brand-mid); }
.book-type-icon { width: 48px; height: 48px; border-radius: 16px; display: grid; place-items: center; flex-shrink: 0; }
.book-meta { font-size: 12px; color: #6b6b78; display: inline-flex; align-items: center; gap: 5px; }
</style>

<div class="book-shell">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
        <!-- Header -->
        <div class="text-center mb-10">
            <?php if (!empty($settings['logo_url'])): ?>
                <img src="<?= $e($settings['logo_url']) ?>" alt="<?= $e($businessName) ?>" class="h-14 mx-auto mb-4 rounded-xl">
            <?php else: ?>
                <div class="w-14 h-14 rounded-2xl mx-auto mb-4 grid place-items-center" style="background:var(--book-brand);color:white">
                    <i class="lucide lucide-calendar-clock text-[22px]"></i>
                </div>
            <?php endif; ?>
            <span class="book-pill mb-3"><i class="lucide lucide-calendar text-[12px]"></i> <?= $e($businessName) ?></span>
            <h1 class="font-display font-extrabold text-[34px] sm:text-[40px] tracking-[-0.025em] text-ink-900 mb-2"><?= $e($settings['page_title'] ?: ('Agenda una reunión con ' . $businessName)) ?></h1>
            <?php if (!empty($settings['page_description'])): ?>
                <p class="text-[15px] text-ink-500 max-w-xl mx-auto"><?= $e($settings['page_description']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($settings['welcome_message'])): ?>
            <div class="book-card p-5 mb-6 flex items-start gap-3" style="background:var(--book-brand-soft);border-color:var(--book-brand-mid)">
                <i class="lucide lucide-message-square-quote text-[18px]" style="color:var(--book-brand)"></i>
                <p class="text-[14px] text-ink-700"><?= nl2br($e($settings['welcome_message'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($aiSuggester)):
            $suggestUrl = $url('/book/' . rawurlencode($publicSlug) . '/ai/suggest');
        ?>
            <!-- AI Smart Suggester -->
            <div class="book-card p-5 mb-6"
                 x-data="aiSuggester(<?= htmlspecialchars(json_encode(['url' => $suggestUrl, 'csrf' => $csrf]), ENT_QUOTES) ?>)"
                 style="background:linear-gradient(135deg,#0f0d18 0%,#1a1530 60%,#2a1f3d 100%);border:1px solid rgba(167,139,250,.25);color:white;position:relative;overflow:hidden">
                <div style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at 0% 100%,rgba(167,139,250,.18),transparent 60%)"></div>
                <div style="position:relative">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-[0.14em]" style="background:rgba(167,139,250,.18);color:#c4b5fd;border:1px solid rgba(167,139,250,.3)">
                            <i class="lucide lucide-sparkles text-[11px]"></i> Asistente IA
                        </span>
                        <span class="text-[11px]" style="color:rgba(255,255,255,.55)">Powered by Kyros IA</span>
                    </div>
                    <h3 class="font-display font-extrabold text-[18px] tracking-[-0.015em] mb-1">¿No sabés cuál elegir?</h3>
                    <p class="text-[13px] mb-3" style="color:rgba(255,255,255,.7)">Contanos en una línea qué necesitás y nuestra IA te recomienda el tipo de reunión que mejor encaja.</p>
                    <form @submit.prevent="suggest()" class="flex flex-col sm:flex-row gap-2">
                        <input x-model="description" type="text" maxlength="500" required
                               placeholder="Ej. Necesito ayuda con un problema de facturación..."
                               class="flex-1 h-11 px-4 rounded-xl text-[13.5px]"
                               style="background:rgba(255,255,255,.06);color:white;border:1px solid rgba(255,255,255,.12);outline:none">
                        <button type="submit" :disabled="loading" class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl font-semibold text-[13px] transition disabled:opacity-50" style="background:white;color:#0f0d18">
                            <i class="lucide lucide-sparkles text-[13px]" x-show="!loading"></i>
                            <i class="lucide lucide-loader-2 text-[13px] animate-spin" x-show="loading" x-cloak></i>
                            <span x-text="loading ? 'Pensando...' : 'Sugerir tipo'"></span>
                        </button>
                    </form>

                    <!-- Resultado -->
                    <div x-show="result" x-cloak x-transition class="mt-4 rounded-xl p-4" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl grid place-items-center flex-shrink-0" style="background:rgba(34,197,94,.18);color:#86efac">
                                <i class="lucide lucide-check-circle text-[15px]"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="font-display font-bold text-[15px]" x-text="result?.name"></span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)" x-text="result ? result.duration + ' min' : ''"></span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded uppercase tracking-[0.12em] font-bold"
                                          :style="result?.confidence === 'high' ? 'background:rgba(34,197,94,.18);color:#86efac' : (result?.confidence === 'medium' ? 'background:rgba(251,191,36,.18);color:#fde68a' : 'background:rgba(255,255,255,.08);color:rgba(255,255,255,.5)')"
                                          x-text="result?.confidence"></span>
                                </div>
                                <p class="text-[12.5px] mb-3" style="color:rgba(255,255,255,.75)" x-text="result?.reason"></p>
                                <a :href="result?.url" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-lg font-semibold text-[12.5px]" style="background:#7c5cff;color:white">
                                    Reservar este tipo <i class="lucide lucide-arrow-right text-[12px]"></i>
                                </a>
                            </div>
                            <button type="button" @click="result=null" class="w-7 h-7 rounded-lg grid place-items-center flex-shrink-0" style="color:rgba(255,255,255,.4)" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                                <i class="lucide lucide-x text-[13px]"></i>
                            </button>
                        </div>
                    </div>

                    <div x-show="error" x-cloak class="mt-3 text-[12px] px-3 py-2 rounded-lg" style="background:rgba(239,68,68,.12);color:#fca5a5;border:1px solid rgba(239,68,68,.25)" x-text="error"></div>
                </div>
            </div>
            <script>
            function aiSuggester(cfg) {
                return {
                    url: cfg.url,
                    csrf: cfg.csrf,
                    description: '',
                    loading: false,
                    result: null,
                    error: '',
                    async suggest() {
                        if (!this.description.trim()) return;
                        this.loading = true; this.result = null; this.error = '';
                        try {
                            const r = await fetch(this.url, {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: new URLSearchParams({_csrf: this.csrf, description: this.description}),
                            });
                            const j = await r.json();
                            if (j.ok) this.result = j;
                            else {
                                const errs = {
                                    'ai_unavailable':'IA no disponible ahora.',
                                    'suggester_disabled':'El asistente está deshabilitado.',
                                    'no_types':'No hay tipos disponibles.',
                                    'empty':'Escribí algo primero.',
                                    'ai_error':'No pudimos procesar la consulta.',
                                    'parse_error':'Error procesando la respuesta.',
                                    'unknown_type':'Tipo recomendado inválido.',
                                };
                                this.error = errs[j.error] || 'Algo salió mal. Probá manualmente abajo.';
                            }
                        } catch (e) { this.error = 'Error de red. Probá de nuevo.'; }
                        this.loading = false;
                    },
                };
            }
            </script>
        <?php endif; ?>

        <!-- Lista de tipos -->
        <?php if (empty($types)): ?>
            <div class="book-card p-10 text-center">
                <div class="w-14 h-14 rounded-2xl bg-ink-100 grid place-items-center mx-auto mb-3" style="background:#f3f4f6"><i class="lucide lucide-calendar-x text-[24px] text-ink-400"></i></div>
                <p class="text-[14px] font-semibold text-ink-700">No hay tipos de reunión disponibles ahora mismo.</p>
                <p class="text-[12.5px] text-ink-400 mt-1">Por favor, intentá más tarde o contactá al equipo directamente.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($types as $t): ?>
                    <a href="<?= $url('/book/' . rawurlencode($publicSlug) . '/' . rawurlencode($t['slug'])) ?>" class="book-type-card">
                        <div class="book-type-icon" style="background:<?= $e($t['color']) ?>22;color:<?= $e($t['color']) ?>">
                            <i class="lucide lucide-<?= $e($t['icon']) ?> text-[20px]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-display font-bold text-[16px] text-ink-900 mb-0.5"><?= $e($t['name']) ?></h3>
                            <?php if (!empty($t['description'])): ?>
                                <p class="text-[13px] text-ink-500 mb-1.5 line-clamp-2"><?= $e($t['description']) ?></p>
                            <?php endif; ?>
                            <div class="flex flex-wrap gap-3">
                                <span class="book-meta"><i class="lucide lucide-clock"></i> <?= (int)$t['duration_minutes'] ?> min</span>
                                <span class="book-meta"><i class="lucide lucide-<?= $t['location_type']==='virtual'?'video':($t['location_type']==='phone'?'phone':($t['location_type']==='in_person'?'map-pin':'map')) ?>"></i> <?= ['virtual'=>'Videollamada','phone'=>'Llamada','in_person'=>'Presencial','custom'=>'Custom'][$t['location_type']] ?? $t['location_type'] ?></span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 w-9 h-9 rounded-full grid place-items-center" style="background:var(--book-brand-soft);color:var(--book-brand)">
                            <i class="lucide lucide-arrow-right text-[16px]"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-10 text-center text-[12px] text-ink-400">
            <?php if (!empty($settings['business_email']) || !empty($settings['business_phone'])): ?>
                <p class="mb-2">¿Necesitás ayuda?
                    <?php if (!empty($settings['business_email'])): ?>
                        <a href="mailto:<?= $e($settings['business_email']) ?>" class="text-ink-700 hover:text-ink-900"><?= $e($settings['business_email']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['business_email']) && !empty($settings['business_phone'])): ?> · <?php endif; ?>
                    <?php if (!empty($settings['business_phone'])): ?>
                        <a href="tel:<?= $e($settings['business_phone']) ?>" class="text-ink-700 hover:text-ink-900"><?= $e($settings['business_phone']) ?></a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if ($showPowered): ?>
                <p class="text-[11px]">Powered by <a href="https://kydesk.kyrosrd.com" target="_blank" class="font-semibold text-brand-700">Kydesk</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
