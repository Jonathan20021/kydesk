<?php
use App\Core\Helpers;
$admin = $superAuth->admin();
$current = $_SERVER['REQUEST_URI'] ?? '';
$basePath = $app->config['app']['base'] ?? '';
$isActive = function (string $path) use ($current, $basePath) {
    $full = $basePath . '/admin' . $path;
    if ($path === '' || $path === '/') return rtrim($current, '/') === rtrim($basePath . '/admin', '/');
    return strpos($current, $full) === 0;
};
$can = fn(string $a) => $superAuth->can($a);

$nav = [
    'Operación' => [
        ['Dashboard','layout-dashboard','/dashboard',null],
        ['Empresas (Tenants)','building-2','/tenants','tenants.view'],
        ['Usuarios (Global)','users','/users','users.view'],
        ['Soporte','life-buoy','/support','support.view'],
    ],
    'Facturación' => [
        ['Planes','tag','/plans','plans.view'],
        ['Suscripciones','repeat','/subscriptions','subscriptions.view'],
        ['Facturas','file-text','/invoices','invoices.view'],
        ['Pagos','wallet','/payments','payments.view'],
    ],
    'Sistema' => [
        ['Reportes SaaS','bar-chart-3','/reports','reports.view'],
        ['Auditoría','history','/audit','reports.view'],
        ['Super Admins','shield','/super-admins','super_admins.view'],
        ['Ajustes','settings','/settings','settings.view'],
    ],
];

// Quick metrics
$stats = [
    'tenants' => (int)$app->db->val('SELECT COUNT(*) FROM tenants WHERE is_active=1') ?: 0,
    'users'   => (int)$app->db->val('SELECT COUNT(*) FROM users') ?: 0,
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e(($title ?? 'Super Admin') . ' · Kydesk SaaS') ?></title>
<meta name="csrf-token" content="<?= $e($csrf) ?>">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] },
    colors: {
        admin: { 50:'#fdf4ff',100:'#fae8ff',200:'#f5d0fe',300:'#f0abfc',400:'#e879f9',500:'#d946ef',600:'#c026d3',700:'#a21caf',800:'#86198f',900:'#701a75' },
        ink: { 900:'#16151b',700:'#2a2a33',500:'#6b6b78',400:'#8e8e9a',300:'#b8b8c4' }
    }
} } };
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
  :root { --bg:#fafafb; --border:#ececef; --bg-card:#fff; }
  *, *::before, *::after { box-sizing:border-box; }
  html, body { margin:0; padding:0; }
  body { background:#fafafb; color:#16151b; font-family:Inter,sans-serif; overflow-x:hidden; }
  .admin-shell { min-height:100vh; display:grid; grid-template-columns:264px minmax(0, 1fr); width:100%; max-width:100vw; }
  .admin-sidebar { background:linear-gradient(180deg,#0f0d18 0%,#1a1530 100%); color:#e9e8ef; padding:18px 14px; position:sticky; top:0; height:100vh; overflow-y:auto; display:flex; flex-direction:column; }
  .admin-brand { display:flex; align-items:center; gap:10px; padding:6px 6px 18px; border-bottom:1px solid rgba(255,255,255,.08); margin-bottom:14px; }
  .admin-brand-logo { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#d946ef,#7c5cff); display:grid; place-items:center; color:white; font-weight:800; box-shadow:0 6px 14px -4px rgba(217,70,239,.5); }
  .admin-nav-section { margin-bottom:16px; }
  .admin-nav-heading { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.16em; color:rgba(255,255,255,.4); padding:0 8px 6px; }
  .admin-nav-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; color:rgba(255,255,255,.7); font-size:13px; font-weight:500; transition:all .15s; margin-bottom:2px; }
  .admin-nav-item:hover { background:rgba(255,255,255,.06); color:white; }
  .admin-nav-item.active { background:linear-gradient(135deg,rgba(217,70,239,.25),rgba(124,92,255,.25)); color:white; box-shadow:inset 0 0 0 1px rgba(217,70,239,.3); }
  .admin-nav-item i { font-size:16px; opacity:.85; }
  .admin-main { padding:24px 28px; min-width:0; max-width:100%; overflow-x:hidden; }
  .admin-main > * { min-width:0; max-width:100%; }
  .admin-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; gap:14px; flex-wrap:wrap; }
  .admin-card { background:white; border:1px solid #ececef; border-radius:16px; box-shadow:0 1px 2px rgba(22,21,27,.04); min-width:0; max-width:100%; overflow-x:auto; overflow-y:hidden; }
  .admin-card-pad { padding:20px; overflow-x:visible; }
  .admin-table-wrap { overflow-x:auto; max-width:100%; }
  .admin-chart-wrap { position:relative; height:240px; max-width:100%; width:100%; }
  .admin-stat { background:white; border:1px solid #ececef; border-radius:16px; padding:18px 18px 16px; min-width:0; max-width:100%; overflow:hidden; }
  .admin-stat-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.14em; color:#8e8e9a; }
  .admin-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:28px; letter-spacing:-.02em; margin-top:6px; word-break:break-word; line-height:1.1; }
  .admin-table { width:100%; border-collapse:collapse; min-width:560px; }
  .admin-table th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:#6b6b78; padding:12px 16px; border-bottom:1px solid #ececef; background:#fafafb; }
  .admin-table td { padding:14px 16px; border-bottom:1px solid #f3f3f5; font-size:13px; }
  .admin-table tr:hover td { background:#fafafb; }
  .admin-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:10px; font-size:13px; font-weight:600; transition:all .15s; cursor:pointer; }
  .admin-btn-primary { background:linear-gradient(135deg,#d946ef,#7c5cff); color:white; box-shadow:0 6px 14px -4px rgba(217,70,239,.4); }
  .admin-btn-primary:hover { transform:translateY(-1px); box-shadow:0 10px 22px -6px rgba(217,70,239,.5); }
  .admin-btn-soft { background:white; border:1px solid #ececef; color:#2a2a33; }
  .admin-btn-soft:hover { border-color:#d946ef; color:#a21caf; }
  .admin-btn-danger { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
  .admin-btn-danger:hover { background:#fee2e2; }
  .admin-input, .admin-select, .admin-textarea { width:100%; padding:10px 14px; border:1px solid #ececef; border-radius:10px; font-size:13.5px; background:white; transition:border-color .15s; }
  .admin-input:focus, .admin-select:focus, .admin-textarea:focus { outline:none; border-color:#d946ef; box-shadow:0 0 0 3px rgba(217,70,239,.15); }
  .admin-label { display:block; font-size:12px; font-weight:600; color:#2a2a33; margin-bottom:6px; }
  .admin-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }
  .admin-pill-green { background:#dcfce7; color:#166534; }
  .admin-pill-red { background:#fee2e2; color:#991b1b; }
  .admin-pill-amber { background:#fef3c7; color:#92400e; }
  .admin-pill-blue { background:#dbeafe; color:#1e40af; }
  .admin-pill-purple { background:#fae8ff; color:#86198f; }
  .admin-pill-gray { background:#f3f4f6; color:#374151; }
  .admin-h1 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:28px; letter-spacing:-.025em; }
  .admin-h2 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:18px; letter-spacing:-.015em; }
  .admin-flash-success { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:12px; margin-bottom:16px; display:flex; align-items:center; gap:10px; }
  .admin-flash-error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:12px; margin-bottom:16px; display:flex; align-items:center; gap:10px; }
  @media (max-width:900px) { .admin-shell { grid-template-columns:minmax(0,1fr); } .admin-sidebar { position:fixed; left:-280px; transition:left .25s; z-index:50; width:264px; } .admin-sidebar.open { left:0; } .admin-main { padding:16px; } }
</style>
<script>
function adminRenderIcons(){
    document.querySelectorAll('i.lucide, span.lucide').forEach(el => {
        if (el.dataset.lucide) return;
        const cls = [...el.classList].find(c => c.startsWith('lucide-') && c !== 'lucide');
        if (cls) el.setAttribute('data-lucide', cls.replace('lucide-',''));
    });
    if (window.lucide) window.lucide.createIcons({ attrs: { width: '1em', height: '1em', 'stroke-width': 2 } });
}
document.addEventListener('DOMContentLoaded', adminRenderIcons);
window.adminRenderIcons = adminRenderIcons;
</script>
</head>
<body x-data="{ sidebarOpen:false, userMenu:false }">

<div class="admin-shell">
    <aside class="admin-sidebar" :class="sidebarOpen && 'open'">
        <div class="admin-brand">
            <div class="admin-brand-logo">K</div>
            <div>
                <div style="font-family:'Plus Jakarta Sans';font-weight:800;font-size:15px;color:white">Kydesk</div>
                <div style="font-size:10px;color:rgba(255,255,255,.5);font-weight:600;text-transform:uppercase;letter-spacing:.14em">Super Admin</div>
            </div>
        </div>

        <?php foreach ($nav as $section => $items):
            $visible = array_filter($items, fn($i) => !$i[3] || $can($i[3]));
            if (!$visible) continue;
        ?>
            <div class="admin-nav-section">
                <div class="admin-nav-heading"><?= $e($section) ?></div>
                <?php foreach ($visible as [$l,$ic,$p,$perm]):
                    $active = $isActive($p);
                ?>
                    <a href="<?= $url('/admin' . $p) ?>" class="admin-nav-item <?= $active?'active':'' ?>">
                        <i class="lucide lucide-<?= $ic ?>"></i><span><?= $e($l) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:auto; padding-top:14px; border-top:1px solid rgba(255,255,255,.08)">
            <div style="display:flex; align-items:center; gap:10px; padding:8px">
                <div style="width:36px;height:36px;border-radius:10px;background:<?= Helpers::colorFor($admin['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:13px"><?= Helpers::initials($admin['name']) ?></div>
                <div style="min-width:0; flex:1">
                    <div style="font-weight:700; font-size:13px; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"><?= $e($admin['name']) ?></div>
                    <div style="font-size:10.5px; color:rgba(255,255,255,.5); text-transform:uppercase; letter-spacing:.12em; font-weight:700"><?= $e($admin['role']) ?></div>
                </div>
            </div>
            <div style="display:flex; gap:6px; margin-top:8px">
                <a href="<?= $url('/admin/profile') ?>" class="admin-nav-item" style="flex:1; justify-content:center; padding:7px"><i class="lucide lucide-user"></i></a>
                <form method="POST" action="<?= $url('/admin/logout') ?>" style="flex:1">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="admin-nav-item" style="width:100%; justify-content:center; padding:7px; color:#fca5a5"><i class="lucide lucide-log-out"></i></button>
                </form>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <button @click="sidebarOpen=true" class="admin-btn admin-btn-soft" style="display:none" :class="{'!hidden':false}"><i class="lucide lucide-menu"></i></button>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.16em; color:#a21caf"><?= $e($title ?? 'Panel') ?></div>
                <div class="admin-h1"><?= $e($pageHeading ?? $title ?? 'Super Admin') ?></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px">
                <a href="<?= $url('/') ?>" target="_blank" class="admin-btn admin-btn-soft"><i class="lucide lucide-external-link"></i> Sitio público</a>
            </div>
        </div>

        <?php if (!empty($flash['success'])): ?>
            <div class="admin-flash-success"><i class="lucide lucide-check-circle"></i><?= $e($flash['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
            <div class="admin-flash-error"><i class="lucide lucide-alert-circle"></i><?= $e($flash['error']) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>

<script src="<?= $asset('js/app.js') ?>"></script>
</body>
</html>
