<?php
$brandColor = $settings['primary_color'] ?? '#7c5cff';
$businessName = $settings['business_name'] ?? ($tenant->name ?? 'Kydesk');
?>
<style>
.book-shell { min-height: 100vh; background: linear-gradient(180deg, #fafafb 0%, #f3f4f6 100%); display: grid; place-items: center; }
.book-card { background: white; border: 1px solid #ececef; border-radius: 24px; box-shadow: 0 4px 24px -8px rgba(22,21,27,.06); }
</style>

<div class="book-shell">
    <div class="max-w-md mx-auto px-4">
        <div class="book-card p-10 text-center">
            <div class="w-16 h-16 rounded-full mx-auto mb-4 grid place-items-center" style="background:#f3f4f6;color:#6b6b78">
                <i class="lucide lucide-calendar-x text-[26px]"></i>
            </div>
            <h1 class="font-display font-extrabold text-[22px] tracking-[-0.025em] text-ink-900 mb-2">No encontramos la página</h1>
            <p class="text-[13.5px] text-ink-500">La agenda no existe, no está activa o el enlace expiró. Si esperabas confirmar una reserva, contactá al organizador.</p>
        </div>
    </div>
</div>
