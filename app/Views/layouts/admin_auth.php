<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e($title ?? 'Super Admin · Kydesk') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] },
    colors: { admin: {500:'#d946ef',600:'#c026d3',700:'#a21caf'}, ink: {900:'#16151b',500:'#6b6b78',400:'#8e8e9a'} }
} } };
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('i.lucide').forEach(el => {
        if (el.dataset.lucide) return;
        const cls = [...el.classList].find(c => c.startsWith('lucide-') && c !== 'lucide');
        if (cls) el.setAttribute('data-lucide', cls.replace('lucide-',''));
    });
    if (window.lucide) window.lucide.createIcons({ attrs: { width: '1em', height: '1em', 'stroke-width': 2 } });
});
</script>
<style>
  body { background:#0a0814; color:white; font-family:Inter,sans-serif; min-height:100vh; }
</style>
</head>
<body>

<?php if (!empty($flash['error'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-white text-ink-900 text-sm shadow-2xl flex items-center gap-2">
    <i class="lucide lucide-alert-circle text-red-500"></i> <?= $e($flash['error']) ?>
</div>
<?php endif; ?>
<?php if (!empty($flash['success'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-white text-ink-900 text-sm shadow-2xl flex items-center gap-2">
    <i class="lucide lucide-check-circle text-green-500"></i> <?= $e($flash['success']) ?>
</div>
<?php endif; ?>

<?= $content ?>

</body>
</html>
