<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e($title ?? 'Developers · Kydesk') ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='10' fill='%230ea5e9'/><path d='M11 8h3v6.5L19.5 8H23l-5.5 6.5L23 23h-3.5l-4-6.7L14 19V23h-3V8z' fill='white'/></svg>">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] },
    colors: { dev: { 50:'#f0f9ff',500:'#0ea5e9',600:'#0284c7',700:'#0369a1' } }
} } };
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<style>
body { background:#050614; }
.dev-bg { background-image: radial-gradient(rgba(56,189,248,.10) 1px, transparent 1px); background-size: 24px 24px; }
.dev-card { background: linear-gradient(180deg, rgba(15,16,24,.85), rgba(8,10,18,.95)); border:1px solid rgba(56,189,248,.18); border-radius: 24px; box-shadow: 0 30px 80px -20px rgba(14,165,233,.25); }
.dev-input { width:100%;height:46px;padding:0 14px;border:1px solid rgba(56,189,248,.2);border-radius:12px;font-size:14px;background:rgba(10,10,18,.6);color:#e2e8f0;outline:none; }
.dev-input:focus { border-color:#0ea5e9; box-shadow:0 0 0 4px rgba(14,165,233,.18); background:rgba(10,10,18,.85); }
.dev-label { display:block;font-size:11.5px;font-weight:700;color:#94a3b8;margin-bottom:7px;letter-spacing:.06em;text-transform:uppercase; }
.dev-btn-primary { display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;height:46px;border-radius:12px;font-size:14px;font-weight:600;background:linear-gradient(135deg,#0ea5e9,#6366f1);color:white;box-shadow:0 12px 28px -10px rgba(14,165,233,.55);border:none;cursor:pointer;transition:transform .15s; }
.dev-btn-primary:hover { transform: translateY(-1px); }
</style>
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
<body class="text-slate-100">
<div class="min-h-screen dev-bg flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-[440px]">
        <a href="<?= $url('/developers') ?>" class="flex items-center justify-center gap-2 mb-8">
            <span class="w-9 h-9 rounded-xl text-white grid place-items-center font-display font-bold" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">K</span>
            <div class="leading-none">
                <div class="font-display font-bold text-[16px] text-white">Kydesk</div>
                <div class="text-[10px] font-bold uppercase tracking-[0.18em] text-sky-400">Developers</div>
            </div>
        </a>

        <?php if (!empty($flash['success'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/15 border border-emerald-400/30 text-emerald-200 text-[13px] flex items-center gap-2">
                <i class="lucide lucide-check"></i> <?= $e($flash['success']) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-500/15 border border-red-400/30 text-red-200 text-[13px] flex items-center gap-2">
                <i class="lucide lucide-alert-circle"></i> <?= $e($flash['error']) ?>
            </div>
        <?php endif; ?>

        <div class="dev-card p-8">
            <?= $content ?>
        </div>

        <div class="text-center mt-6 text-[12.5px] text-slate-500">
            <a href="<?= $url('/') ?>" class="hover:text-slate-300">¿Buscas el helpdesk?</a> · <a href="<?= $url('/admin') ?>" class="hover:text-slate-300">Admin</a>
        </div>
    </div>
</div>
</body>
</html>
