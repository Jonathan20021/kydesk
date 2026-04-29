<!DOCTYPE html>
<html lang="<?= $e($locale ?? 'es') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e($title ?? 'Kydesk') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] },
    colors: { brand: {50:'#f3f0ff',500:'#7c5cff',600:'#6c47ff'}, ink: {900:'#16151b',500:'#6b6b78',400:'#8e8e9a'} }
} } };
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

<?php if (!empty($flash['error'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-ink-900 text-white text-sm shadow-lg flex items-center gap-2">
    <i class="lucide lucide-alert-circle text-red-400"></i> <?= $e($flash['error']) ?>
</div>
<?php endif; ?>

<?= $content ?>

</body>
</html>
