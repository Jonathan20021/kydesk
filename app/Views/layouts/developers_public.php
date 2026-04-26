<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e($title ?? 'Developers · Kydesk API') ?></title>
<meta name="description" content="<?= $e($meta_desc ?? 'Construye con la API de Kydesk Helpdesk. Tickets, KB, automatizaciones — listos para tu app.') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='10' fill='%230ea5e9'/><path d='M11 8h3v6.5L19.5 8H23l-5.5 6.5L23 23h-3.5l-4-6.7L14 19V23h-3V8z' fill='white'/></svg>">

<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                display: ['Plus Jakarta Sans', 'sans-serif'],
                mono: ['Geist Mono','JetBrains Mono','monospace'],
            },
            colors: {
                dev: { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e' },
                ink: { 950:'#0a0a12',900:'#0f1018',800:'#181a25',700:'#2a2a33',500:'#6b6b78',400:'#8e8e9a',300:'#b8b8c4' }
            }
        }
    }
};
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
:root { --dev-bg: #050614; }
body { background: var(--dev-bg); }
.dev-grid-bg { background-image: radial-gradient(rgba(56,189,248,.12) 1px, transparent 1px); background-size: 28px 28px; }
.dev-glow { box-shadow: 0 30px 80px -20px rgba(14,165,233,.25), 0 0 0 1px rgba(56,189,248,.15) inset; }
.dev-card { background: linear-gradient(180deg, rgba(15,16,24,.7) 0%, rgba(10,10,18,.9) 100%); border: 1px solid rgba(56,189,248,.12); border-radius: 22px; backdrop-filter: blur(8px); }
.dev-pill { display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.14em;border:1px solid rgba(56,189,248,.25);background:rgba(14,165,233,.08);color:#7dd3fc; }
.dev-btn { display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 22px;height:44px;border-radius:12px;font-size:14px;font-weight:600;transition:all .15s;cursor:pointer;border:1px solid transparent;text-decoration:none;line-height:1;white-space:nowrap; }
.dev-btn-primary { background: linear-gradient(135deg,#0ea5e9 0%, #6366f1 100%); color:white; box-shadow: 0 12px 28px -10px rgba(14,165,233,.55); }
.dev-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 16px 36px -10px rgba(14,165,233,.65); }
.dev-btn-ghost { background: rgba(56,189,248,.06); color:#bae6fd; border-color: rgba(56,189,248,.18); }
.dev-btn-ghost:hover { background: rgba(56,189,248,.12); border-color: rgba(56,189,248,.4); }
.dev-input { width:100%;height:44px;padding:0 14px;border:1px solid rgba(56,189,248,.2);border-radius:12px;font-size:14px;background:rgba(10,10,18,.6);color:#e2e8f0;transition:all .15s;outline:none; }
.dev-input:focus { border-color:#0ea5e9; box-shadow:0 0 0 4px rgba(14,165,233,.18); background:rgba(10,10,18,.85); }
.dev-label { display:block;font-size:11.5px;font-weight:600;color:#94a3b8;margin-bottom:6px;letter-spacing:.04em;text-transform:uppercase; }
.dev-h1 { font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:54px;letter-spacing:-.035em;color:white;line-height:1.05; }
@media (max-width:768px){ .dev-h1{font-size:36px;} }
.dev-muted { color:#94a3b8; }
.code-block { background: #050614; border: 1px solid rgba(56,189,248,.12); border-radius: 14px; padding: 18px 20px; font-family:'Geist Mono','JetBrains Mono',monospace; font-size:12.5px; color:#bae6fd; overflow-x:auto; line-height:1.7; }
.code-block .k { color:#f472b6; } .code-block .s { color:#86efac; } .code-block .c { color:#64748b; } .code-block .n { color:#fbbf24; }
.dev-feature { padding:22px;border:1px solid rgba(56,189,248,.12);border-radius:18px;background:rgba(15,16,24,.6); transition:all .2s; }
.dev-feature:hover { border-color: rgba(56,189,248,.35); background: rgba(15,16,24,.85); transform: translateY(-2px); }
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
<body class="text-slate-100" x-data="{ menuOpen:false }">

<?php if (!empty($flash['success'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-emerald-500/15 border border-emerald-400/30 text-emerald-200 text-sm shadow-lg flex items-center gap-2">
    <i class="lucide lucide-check"></i> <?= $e($flash['success']) ?>
</div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
<div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl bg-red-500/15 border border-red-400/30 text-red-200 text-sm shadow-lg flex items-center gap-2">
    <i class="lucide lucide-alert-circle"></i> <?= $e($flash['error']) ?>
</div>
<?php endif; ?>

<header class="sticky top-0 z-40 backdrop-blur-md border-b border-sky-500/10 bg-[#050614]/80">
    <div class="max-w-[1180px] mx-auto px-6 h-16 flex items-center justify-between">
        <a href="<?= $url('/developers') ?>" class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">K</span>
            <div class="leading-none">
                <div class="font-display font-bold text-[15px] text-white">Kydesk</div>
                <div class="text-[9.5px] font-bold uppercase tracking-[0.18em] text-sky-400">Developers</div>
            </div>
        </a>
        <nav class="hidden md:flex items-center gap-7 text-[13.5px]">
            <a href="<?= $url('/developers') ?>" class="text-slate-300 hover:text-white">Inicio</a>
            <a href="<?= $url('/developers/pricing') ?>" class="text-slate-300 hover:text-white">Planes</a>
            <a href="<?= $url('/developers/docs') ?>" class="text-slate-300 hover:text-white">Documentación</a>
            <a href="<?= $url('/') ?>" class="text-slate-300 hover:text-white">Helpdesk</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="<?= $url('/developers/login') ?>" class="dev-btn dev-btn-ghost h-9 text-[13px] px-4">Login</a>
            <a href="<?= $url('/developers/register') ?>" class="dev-btn dev-btn-primary h-9 text-[13px] px-4">Crear cuenta</a>
        </div>
    </div>
</header>

<?= $content ?>

<footer class="border-t border-sky-500/10 mt-24">
    <div class="max-w-[1180px] mx-auto px-6 py-10 flex flex-col md:flex-row items-center justify-between gap-4 text-[12.5px] text-slate-400">
        <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-md grid place-items-center text-white font-bold text-[10px]" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">K</span>
            <span>Kydesk Developers · <?= date('Y') ?></span>
        </div>
        <div class="flex items-center gap-5">
            <a href="<?= $url('/developers/docs') ?>" class="hover:text-white">Docs</a>
            <a href="<?= $url('/developers/pricing') ?>" class="hover:text-white">Planes</a>
            <a href="<?= $url('/privacy') ?>" class="hover:text-white">Privacidad</a>
            <a href="<?= $url('/terms') ?>" class="hover:text-white">Términos</a>
        </div>
    </div>
</footer>

<script src="<?= $asset('js/app.js') ?>"></script>
</body>
</html>
