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
