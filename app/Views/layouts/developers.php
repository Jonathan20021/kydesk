<?php
use App\Core\Helpers;
$d = $devAuth->developer();
$current = $_SERVER['REQUEST_URI'] ?? '';
$basePath = $app->config['app']['base'] ?? '';
$isActive = function (string $path) use ($current, $basePath) {
    $full = $basePath . '/developers' . $path;
    if ($path === '' || $path === '/') return rtrim($current, '/') === rtrim($basePath . '/developers', '/');
    return strpos($current, $full) === 0;
};

$nav = [
    'Construir' => [
        ['Dashboard','layout-dashboard','/dashboard'],
        ['Mis Apps','boxes','/apps'],
        ['Webhooks','webhook','/webhooks'],
        ['Uso de API','activity','/usage'],
    ],
    'Developer Tools' => [
        ['API Console','terminal','/console'],
        ['AI Studio','bot','/ai'],
        ['AI Chat','message-square','/ai/chat'],
        ['Documentación','book-open','/developers/docs', true],
    ],
    'Cuenta' => [
        ['Facturación','wallet','/billing'],
        ['Planes','tag','/billing/plans'],
        ['Cómo pagar','landmark','/billing/payment-info'],
        ['Perfil','user','/profile'],
    ],
];

$plan = $devPlan ?? null;
$sub = $devSubscription ?? null;
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e(($title ?? 'Developer') . ' · Kydesk Developers') ?></title>
<meta name="csrf-token" content="<?= $e($csrf) ?>">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] },
    colors: {
        dev: { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e' },
        ink: { 950:'#0a0a12',900:'#0f1018',800:'#181a25',700:'#2a2a33',500:'#6b6b78',400:'#8e8e9a',300:'#b8b8c4' }
    }
} } };
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
body { background:#080a14; color:#e2e8f0; }
.dev-shell { min-height:100vh; display:flex; }
.dev-sidebar { width:260px; flex-shrink:0; background:#0a0c18; border-right:1px solid rgba(56,189,248,.10); padding:18px; display:flex; flex-direction:column; gap:6px; min-height:100vh; }
.dev-brand { display:flex; align-items:center; gap:10px; padding:8px 6px 16px; border-bottom:1px solid rgba(56,189,248,.10); margin-bottom:10px; }
.dev-brand-logo { width:38px; height:38px; border-radius:12px; display:grid; place-items:center; color:white; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:15px; background:linear-gradient(135deg,#0ea5e9,#6366f1); flex-shrink:0; }
.dev-brand-meta { font-size:9.5px; font-weight:800; letter-spacing:.18em; text-transform:uppercase; color:#7dd3fc; }
.dev-nav-heading { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.16em; color:#475569; padding:14px 6px 6px; }
.dev-nav-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:11px; font-size:13.5px; font-weight:500; color:#cbd5e1; transition:all .12s; cursor:pointer; }
.dev-nav-item:hover { background:rgba(56,189,248,.08); color:#fff; }
.dev-nav-item.active { background:linear-gradient(90deg, rgba(14,165,233,.18), rgba(99,102,241,.05)); color:#fff; box-shadow: inset 2px 0 0 #0ea5e9; }
.dev-main { flex:1; min-width:0; padding:28px 32px; display:flex; flex-direction:column; gap:22px; }
.dev-topbar { display:flex; align-items:center; gap:14px; padding-bottom:14px; border-bottom:1px solid rgba(56,189,248,.10); }
.dev-topbar h1 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px; color:white; letter-spacing:-.02em; }
.dev-pill { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.12em; }
.dev-pill-sky { background:rgba(14,165,233,.12); color:#7dd3fc; border:1px solid rgba(56,189,248,.2); }
.dev-pill-amber { background:rgba(245,158,11,.12); color:#fcd34d; border:1px solid rgba(245,158,11,.2); }
.dev-pill-emerald { background:rgba(16,185,129,.12); color:#86efac; border:1px solid rgba(16,185,129,.2); }
.dev-pill-red { background:rgba(239,68,68,.12); color:#fca5a5; border:1px solid rgba(239,68,68,.25); }
.dev-pill-gray { background:rgba(148,163,184,.10); color:#cbd5e1; border:1px solid rgba(148,163,184,.2); }
.dev-card { background:#0f1018; border:1px solid rgba(56,189,248,.10); border-radius:18px; }
.dev-card-pad { padding:20px 22px; }
.dev-card-head { display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid rgba(56,189,248,.10); }
.dev-stat { background:#0f1018; border:1px solid rgba(56,189,248,.10); border-radius:16px; padding:18px 20px; position:relative; transition:all .15s; }
.dev-stat:hover { border-color: rgba(56,189,248,.3); transform: translateY(-1px); }
.dev-stat-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:#94a3b8; }
.dev-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; color:white; margin-top:6px; line-height:1.1; letter-spacing:-.02em; }
.dev-stat-icon { position:absolute; top:18px; right:18px; width:32px; height:32px; border-radius:10px; display:grid; place-items:center; background:rgba(14,165,233,.10); color:#7dd3fc; }
.dev-btn { display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 14px;height:36px;border-radius:10px;font-size:12.5px;font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;line-height:1;transition:all .12s;white-space:nowrap; }
.dev-btn-primary { background:linear-gradient(135deg,#0ea5e9,#6366f1); color:white; box-shadow:0 8px 18px -6px rgba(14,165,233,.45); }
.dev-btn-primary:hover { transform:translateY(-1px); }
.dev-btn-soft { background:rgba(56,189,248,.08); color:#bae6fd; border-color:rgba(56,189,248,.2); }
.dev-btn-soft:hover { background:rgba(56,189,248,.18); border-color:rgba(56,189,248,.4); }
.dev-btn-danger { background:rgba(239,68,68,.10); color:#fca5a5; border-color:rgba(239,68,68,.25); }
.dev-btn-danger:hover { background:rgba(239,68,68,.18); }
.dev-btn-icon { padding:0; width:34px; height:34px; }
.dev-input, .dev-select, .dev-textarea { width:100%;height:42px;padding:0 14px;border:1px solid rgba(56,189,248,.18);border-radius:11px;font-size:13.5px;background:rgba(10,10,18,.4);color:#e2e8f0;outline:none;font-family:inherit; }
.dev-textarea { height:auto;min-height:96px;padding:12px 14px;line-height:1.5;resize:vertical; }
.dev-input:focus,.dev-select:focus,.dev-textarea:focus { border-color:#0ea5e9; box-shadow:0 0 0 4px rgba(14,165,233,.15); background:rgba(10,10,18,.7); }
.dev-label { display:block;font-size:12px;font-weight:600;color:#94a3b8;margin-bottom:6px; }
.dev-table { width:100%;border-collapse:collapse;font-size:13px; }
.dev-table th { text-align:left;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#64748b;padding:14px 18px;border-bottom:1px solid rgba(56,189,248,.10); }
.dev-table td { padding:14px 18px;border-bottom:1px solid rgba(56,189,248,.06);color:#cbd5e1; }
.dev-table tr:last-child td { border-bottom:none; }
.dev-table tbody tr:hover td { background: rgba(56,189,248,.04); }
.dev-flash-success { background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#86efac;padding:12px 16px;border-radius:14px;display:flex;align-items:center;gap:10px;font-size:13px; }
.dev-flash-error { background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.30);color:#fca5a5;padding:12px 16px;border-radius:14px;display:flex;align-items:center;gap:10px;font-size:13px; }
.dev-code { background:#040510; border:1px solid rgba(56,189,248,.15); border-radius:12px; padding:14px 16px; font-family:'Geist Mono',monospace; font-size:12.5px; color:#bae6fd; overflow-x:auto; line-height:1.7; }
.dev-link { color:#7dd3fc; }
.dev-link:hover { color:#bae6fd; text-decoration:underline; }
@media (max-width:1024px) {
    .dev-shell { flex-direction:column; }
    .dev-sidebar { width:100%; min-height:auto; flex-direction:row; flex-wrap:wrap; padding:12px; }
    .dev-main { padding:18px; }
}
</style>
<script>
function devRenderIcons(){
    document.querySelectorAll('i.lucide, span.lucide').forEach(el => {
        if (el.dataset.lucide) return;
        const cls = [...el.classList].find(c => c.startsWith('lucide-') && c !== 'lucide');
        if (cls) el.setAttribute('data-lucide', cls.replace('lucide-',''));
    });
    if (window.lucide) window.lucide.createIcons({ attrs: { width: '1em', height: '1em', 'stroke-width': 2 } });
}
document.addEventListener('DOMContentLoaded', devRenderIcons);
window.devRenderIcons = devRenderIcons;
window.renderIcons = devRenderIcons;
</script>
</head>
<body x-data="{ userMenu:false, sidebarOpen:false }">
<div class="dev-shell">
    <aside class="dev-sidebar">
        <div class="dev-brand">
            <div class="dev-brand-logo">K</div>
            <div style="min-width:0">
                <div class="font-display font-bold text-[14.5px] text-white">Kydesk</div>
                <div class="dev-brand-meta">Developers</div>
            </div>
        </div>

        <?php foreach ($nav as $section => $items): ?>
            <div class="dev-nav-heading"><?= $e($section) ?></div>
            <?php foreach ($items as $item):
                [$l,$ic,$p] = [$item[0], $item[1], $item[2]];
                $external = $item[3] ?? false;
                $href = $external ? $url($p) : $url('/developers' . $p);
                $active = !$external && $isActive($p);
            ?>
                <a href="<?= $e($href) ?>" class="dev-nav-item <?= $active?'active':'' ?>" <?= $external?'target="_blank"':'' ?>>
                    <i class="lucide lucide-<?= $ic ?> text-[16px]"></i><span><?= $e($l) ?></span>
                    <?php if ($external): ?><i class="lucide lucide-external-link text-[11px] ml-auto opacity-50"></i><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <div class="mt-auto pt-4 border-t" style="border-color:rgba(56,189,248,.10)">
            <?php
            $usage = $devAuth->usageStats();
            $monthReq = (int)($usage['month_requests'] ?? 0);
            $minReq = (int)($usage['last_minute_requests'] ?? 0);
            $quota = $plan ? (int)$plan['max_requests_month'] : 0;
            $rate = $plan ? (int)$plan['rate_limit_per_min'] : 0;
            $pctMonth = $quota > 0 ? min(100, round(($monthReq / $quota) * 100)) : 0;
            $pctMin = $rate > 0 ? min(100, round(($minReq / $rate) * 100)) : 0;
            $pctColor = $pctMonth >= 95 ? '#f87171' : ($pctMonth >= 80 ? '#fbbf24' : '#0ea5e9');
            ?>
            <?php if ($plan): ?>
                <a href="<?= $url('/developers/billing/plans') ?>" class="block p-3 rounded-xl border hover:border-sky-400/40 transition mb-2 group" style="border-color:rgba(56,189,248,.20); background:linear-gradient(180deg, rgba(14,165,233,.07), rgba(14,165,233,.02))">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <div class="text-[9.5px] font-bold uppercase tracking-[0.16em] text-sky-300">Plan actual</div>
                        <i class="lucide lucide-arrow-up-right text-sky-300/60 text-[12px] group-hover:text-sky-300 transition"></i>
                    </div>
                    <div class="font-display font-bold text-[14px] text-white"><?= $e($plan['name']) ?> <?php if (!empty($plan['has_custom_overrides'])): ?><span class="dev-pill dev-pill-amber !text-[9px] !py-0.5 ml-1">custom</span><?php endif; ?></div>
                    <?php if ($quota > 0): ?>
                    <div class="mt-2">
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="text-slate-400">Cuota mensual</span>
                            <span class="text-white font-mono"><?= $pctMonth ?>%</span>
                        </div>
                        <div class="h-1.5 rounded-full overflow-hidden" style="background:rgba(15,23,42,.5)">
                            <div class="h-full transition-all" style="width:<?= $pctMonth ?>%; background:<?= $pctColor ?>"></div>
                        </div>
                        <div class="text-[10px] text-slate-500 mt-1"><?= number_format($monthReq) ?> / <?= number_format($quota) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($rate > 0): ?>
                    <div class="mt-2">
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="text-slate-400">Rate limit (1m)</span>
                            <span class="text-white font-mono"><?= $minReq ?>/<?= $rate ?></span>
                        </div>
                        <div class="h-1 rounded-full overflow-hidden" style="background:rgba(15,23,42,.5)">
                            <div class="h-full transition-all" style="width:<?= $pctMin ?>%; background:<?= $pctMin >= 80 ? '#fbbf24' : '#6366f1' ?>"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="<?= $url('/developers/billing/plans') ?>" class="block p-3 rounded-xl border hover:border-amber-400/40 transition mb-2" style="border-color:rgba(245,158,11,.20); background:rgba(245,158,11,.05)">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="lucide lucide-alert-circle text-amber-300 text-[14px]"></i>
                        <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-amber-300">Sin plan</div>
                    </div>
                    <div class="text-[12.5px] text-slate-200">Suscríbete para usar la API</div>
                </a>
            <?php endif; ?>

            <div class="flex items-center gap-2 px-2 py-2 rounded-lg" style="background:rgba(56,189,248,.06)">
                <div style="width:32px;height:32px;border-radius:10px;background:<?= Helpers::colorFor($d['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:12px"><?= Helpers::initials($d['name']) ?></div>
                <div style="min-width:0;flex:1">
                    <div class="font-display font-bold text-[12.5px] text-white truncate"><?= $e($d['name']) ?></div>
                    <div class="text-[10.5px] text-slate-400 truncate"><?= $e($d['email']) ?></div>
                </div>
                <a href="<?= $url('/developers/profile') ?>" class="text-slate-400 hover:text-sky-300 p-1.5" title="Perfil"><i class="lucide lucide-user text-[13px]"></i></a>
                <form method="POST" action="<?= $url('/developers/logout') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button type="submit" class="text-slate-400 hover:text-red-300 p-1.5" title="Salir"><i class="lucide lucide-log-out text-[14px]"></i></button>
                </form>
            </div>
        </div>
    </aside>

    <main class="dev-main">
        <div class="dev-topbar">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="dev-pill dev-pill-sky"><i class="lucide lucide-code text-[10px]"></i> Developer</span>
                    <?php if ($d && empty($d['is_verified'])): ?>
                        <span class="dev-pill dev-pill-amber"><i class="lucide lucide-mail-warning text-[10px]"></i> Cuenta no verificada</span>
                    <?php endif; ?>
                    <?php if ($sub && $sub['status'] === 'trial'): ?>
                        <span class="dev-pill dev-pill-amber"><i class="lucide lucide-hourglass text-[10px]"></i> Trial<?php if (!empty($sub['trial_ends_at'])): ?> · termina <?= date('d M', strtotime($sub['trial_ends_at'])) ?><?php endif; ?></span>
                    <?php endif; ?>
                    <?php if ($sub && $sub['status'] === 'past_due'): ?>
                        <span class="dev-pill dev-pill-red"><i class="lucide lucide-alert-circle text-[10px]"></i> Pago pendiente</span>
                    <?php endif; ?>
                </div>
                <h1><?= $e($pageHeading ?? $title ?? 'Panel') ?></h1>
            </div>
            <a href="<?= $url('/developers/docs') ?>" class="dev-btn dev-btn-soft">
                <i class="lucide lucide-book-open text-[13px]"></i> Docs
            </a>
            <a href="<?= $url('/developers/apps/create') ?>" class="dev-btn dev-btn-primary">
                <i class="lucide lucide-plus text-[13px]"></i> Nueva app
            </a>
        </div>

        <?php if (!empty($flash['success'])): ?>
            <div class="dev-flash-success"><i class="lucide lucide-check-circle"></i><?= $e($flash['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
            <div class="dev-flash-error"><i class="lucide lucide-alert-circle"></i><?= $e($flash['error']) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>

<script src="<?= $asset('js/app.js') ?>"></script>
</body>
</html>
