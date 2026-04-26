<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e($title ?? 'Kydesk — Helpdesk profesional') ?></title>
<meta name="description" content="<?= $e($meta_desc ?? 'SaaS de helpdesk multi-tenant.') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='10' fill='%237c5cff'/><path d='M11 8h3v6.5L19.5 8H23l-5.5 6.5L23 23h-3.5l-4-6.7L14 19V23h-3V8z' fill='white'/></svg>">

<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                display: ['Plus Jakarta Sans', 'sans-serif'],
                mono: ['Geist Mono', 'monospace'],
            },
            colors: {
                brand: { 50:'#f3f0ff',100:'#e7e0ff',200:'#cdbfff',300:'#a78bfa',400:'#8b6dff',500:'#7c5cff',600:'#6c47ff',700:'#5a3aff' },
                ink: { 900:'#16151b',700:'#2a2a33',500:'#6b6b78',400:'#8e8e9a',300:'#b8b8c4' }
            }
        }
    }
};
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('i.lucide, span.lucide').forEach(el => {
        if (el.dataset.lucide) return;
        const cls = [...el.classList].find(c => c.startsWith('lucide-') && c !== 'lucide');
        if (cls) el.setAttribute('data-lucide', cls.replace('lucide-',''));
    });
    if (window.lucide) window.lucide.createIcons({ attrs: { width: '1em', height: '1em', 'stroke-width': 2 } });
});
</script>
</head>
<body class="bg-[#f3f4f6]">

<?php if (!empty($flash['success'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-ink-900 text-white text-sm shadow-lg flex items-center gap-2">
    <i class="lucide lucide-check text-emerald-400"></i> <?= $e($flash['success']) ?>
</div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-ink-900 text-white text-sm shadow-lg flex items-center gap-2">
    <i class="lucide lucide-alert-circle text-red-400"></i> <?= $e($flash['error']) ?>
</div>
<?php endif; ?>

<?= $content ?>

<?php if (!empty($showPoweredFooter)): ?>
<footer class="border-t border-[#ececef] bg-white mt-16">
    <div class="max-w-[1100px] mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[12.5px]">
        <a href="https://kydesk.kyrosrd.com" target="_blank" rel="noopener" class="flex items-center gap-2 text-ink-500 hover:text-ink-900 transition">
            <span>Powered by</span>
            <span class="flex items-center gap-1.5">
                <span class="w-5 h-5 rounded-md text-white grid place-items-center font-display font-bold text-[10px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">K</span>
                <span class="font-display font-bold text-ink-900">Kydesk</span>
            </span>
        </a>
        <div class="flex items-center gap-5 text-ink-400">
            <a href="https://kydesk.kyrosrd.com/privacy" target="_blank" rel="noopener" class="hover:text-ink-900 transition">Privacidad</a>
            <a href="https://kydesk.kyrosrd.com/terms"   target="_blank" rel="noopener" class="hover:text-ink-900 transition">Términos</a>
            <a href="https://kydesk.kyrosrd.com"          target="_blank" rel="noopener" class="hover:text-ink-900 transition">¿Qué es Kydesk?</a>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="<?= $asset('js/app.js') ?>"></script>
</body>
</html>
