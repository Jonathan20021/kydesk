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
        ['Empresas','building-2','/tenants','tenants.view'],
        ['Usuarios','users','/users','users.view'],
        ['Soporte','life-buoy','/support','support.view'],
    ],
    'Facturación' => [
        ['Planes','tag','/plans','plans.view'],
        ['Suscripciones','repeat','/subscriptions','subscriptions.view'],
        ['Facturas','file-text','/invoices','invoices.view'],
        ['Pagos','wallet','/payments','payments.view'],
    ],
    'Sistema' => [
        ['Reportes','bar-chart-3','/reports','reports.view'],
        ['Auditoría','history','/audit','reports.view'],
        ['Super Admins','shield','/super-admins','super_admins.view'],
        ['Ajustes','settings','/settings','settings.view'],
    ],
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
        brand: { 50:'#f3f0ff',100:'#e7e0ff',200:'#cdbfff',300:'#a78bfa',400:'#8b6dff',500:'#7c5cff',600:'#6c47ff',700:'#5a3aff' },
        admin: { 50:'#f3f0ff',100:'#e7e0ff',200:'#cdbfff',300:'#a78bfa',400:'#8b6dff',500:'#7c5cff',600:'#6c47ff',700:'#5a3aff',800:'#3f2bbb',900:'#2c1d99' },
        ink: { 900:'#16151b',700:'#2a2a33',500:'#6b6b78',400:'#8e8e9a',300:'#b8b8c4' }
    }
} } };
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
  /* Super-admin specific helpers — extends app.css */
  .super-tag { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; background:linear-gradient(135deg,rgba(124,92,255,.12),rgba(217,70,239,.10)); color:#5a3aff; font-size:10px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; border:1px solid rgba(124,92,255,.22); }
  .super-brand-logo { width:36px; height:36px; border-radius:12px; display:grid; place-items:center; color:white; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:15px; background:linear-gradient(135deg,#7c5cff 0%,#a78bfa 60%,#d946ef 100%); box-shadow:0 8px 18px -6px rgba(124,92,255,.45); flex-shrink:0; }
  .super-brand-meta { font-size:9.5px; font-weight:800; letter-spacing:.18em; text-transform:uppercase; color:var(--brand-700); margin-top:1px; }

  .admin-card { background:#fff; border:1px solid var(--border); border-radius:22px; min-width:0; max-width:100%; }
  .admin-card-pad { padding:22px 24px; }
  .admin-card-head { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid var(--border); gap:12px; flex-wrap:wrap; }
  .admin-table-wrap { overflow-x:auto; max-width:100%; }
  .admin-chart-wrap { position:relative; height:260px; max-width:100%; width:100%; }

  .admin-stat { background:#fff; border:1px solid var(--border); border-radius:18px; padding:18px 20px 16px; min-width:0; max-width:100%; overflow:hidden; transition:box-shadow .15s, transform .15s; position:relative; }
  .admin-stat:hover { box-shadow:0 6px 18px -8px rgba(22,21,27,.08); transform:translateY(-1px); }
  .admin-stat-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--ink-400); }
  .admin-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:28px; letter-spacing:-.025em; margin-top:4px; word-break:break-word; line-height:1.1; color:var(--ink-900); }
  .admin-stat-icon { position:absolute; top:16px; right:16px; width:36px; height:36px; border-radius:12px; display:grid; place-items:center; }

  .admin-table { width:100%; border-collapse:collapse; min-width:560px; font-size:13px; }
  .admin-table th { text-align:left; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.1em; color:var(--ink-400); padding:14px 18px; border-bottom:1px solid var(--border); background:transparent; }
  .admin-table td { padding:16px 18px; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--ink-700); }
  .admin-table tr:last-child td { border-bottom:none; }
  .admin-table tbody tr:hover td { background:var(--bg); }

  .admin-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:0 16px; height:38px; border-radius:999px; font-size:13px; font-weight:600; transition:all .15s; cursor:pointer; white-space:nowrap; border:1px solid transparent; text-decoration:none; line-height:1; }
  .admin-btn-primary { background:var(--brand-500); color:white; box-shadow:0 4px 12px -2px rgba(124,92,255,.35); }
  .admin-btn-primary:hover { background:var(--brand-600); transform:translateY(-1px); }
  .admin-btn-soft { background:#fff; border-color:var(--border); color:var(--ink-700); }
  .admin-btn-soft:hover { border-color:var(--brand-300); color:var(--brand-700); background:var(--brand-50); }
  .admin-btn-danger { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
  .admin-btn-danger:hover { background:#fee2e2; }
  .admin-btn-icon { padding:0; width:34px; height:34px; }

  .admin-input, .admin-select, .admin-textarea { width:100%; height:42px; padding:0 14px; border:1px solid var(--border); border-radius:12px; font-size:13.5px; background:#fff; transition:all .15s; outline:none; color:var(--ink-900); font-family:inherit; }
  .admin-textarea { height:auto; min-height:96px; padding:12px 14px; line-height:1.55; resize:vertical; }
  .admin-input:focus, .admin-select:focus, .admin-textarea:focus { border-color:var(--brand-300); box-shadow:0 0 0 4px var(--brand-50); }
  .admin-select { cursor:pointer; appearance:none; background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238e8e9a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>"); background-repeat:no-repeat; background-position:right 12px center; background-size:16px; padding-right:38px; }
  .admin-label { display:block; font-size:12.5px; font-weight:600; color:var(--ink-700); margin-bottom:6px; }

  .admin-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; border:1px solid transparent; white-space:nowrap; }
  .admin-pill-green { background:#d1fae5; color:#047857; border-color:#a7f3d0; }
  .admin-pill-red { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
  .admin-pill-amber { background:#fef3c7; color:#b45309; border-color:#fde68a; }
  .admin-pill-blue { background:#dbeafe; color:#1d4ed8; border-color:#bfdbfe; }
  .admin-pill-purple { background:var(--brand-50); color:var(--brand-700); border-color:var(--brand-100); }
  .admin-pill-gray { background:var(--bg); color:var(--ink-500); border-color:var(--border); }

  .admin-h1 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; letter-spacing:-.025em; color:var(--ink-900); line-height:1.15; }
  .admin-h2 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:17px; letter-spacing:-.015em; color:var(--ink-900); }

  .admin-flash-success { background:#d1fae5; border:1px solid #a7f3d0; color:#047857; padding:12px 16px; border-radius:14px; display:flex; align-items:center; gap:10px; font-size:13px; }
  .admin-flash-error { background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:14px; display:flex; align-items:center; gap:10px; font-size:13px; }

  .super-user-pill { display:flex; align-items:center; gap:10px; padding:10px; border-radius:14px; background:var(--bg); border:1px solid var(--border); }

  /* Tabs (used in show pages) */
  .admin-tabs { display:flex; gap:4px; padding:4px; background:var(--bg); border-radius:14px; overflow-x:auto; }
  .admin-tab { padding:8px 14px; border-radius:10px; font-size:12.5px; font-weight:600; color:var(--ink-500); white-space:nowrap; display:inline-flex; align-items:center; gap:6px; transition:all .15s; cursor:pointer; border:none; background:transparent; }
  .admin-tab:hover { color:var(--ink-900); }
  .admin-tab.active { background:#fff; color:var(--brand-700); box-shadow:0 1px 2px rgba(22,21,27,.05); }

  @media (max-width: 1024px) {
    .app-shell { padding:0; }
    .app-frame { border-radius:0; min-height:100vh; }
    .sidebar { position:fixed; left:-260px; top:0; transition:left .25s; z-index:50; height:100vh; max-height:100vh; box-shadow:0 8px 32px -8px rgba(0,0,0,.2); border-radius:0; }
    .sidebar.open { left:0; }
    .main { padding:18px 16px; }
  }

  /* Collapsed footers — only one is visible at a time */
  .sidebar-collapsed-foot { display: none; flex-direction: column; gap: 4px; }
  body.sidebar-collapsed .sidebar-collapsed-foot { display: flex; }
  body.sidebar-collapsed .sidebar .super-brand-meta { display: none; }
  body.sidebar-collapsed .sidebar .brand > div.nav-label { display: none; }
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
window.renderIcons = adminRenderIcons;
</script>
</head>
<body
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: false,
        userMenu: false,
        toggleSidebar(){
            this.sidebarCollapsed = !this.sidebarCollapsed;
            try { localStorage.setItem('kydesk_admin_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0'); } catch(e){}
            document.body.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
        }
    }"
    x-init="sidebarCollapsed = (localStorage.getItem('kydesk_admin_sidebar_collapsed')==='1'); document.body.classList.toggle('sidebar-collapsed', sidebarCollapsed)"
    @keydown.window.meta.b.prevent="toggleSidebar()"
    @keydown.window.ctrl.b.prevent="toggleSidebar()">

<div class="app-shell">
    <div class="app-frame">

        <aside class="sidebar" :class="sidebarOpen && 'open'">
            <div class="brand">
                <div class="super-brand-logo">K</div>
                <div style="min-width:0" class="nav-label">
                    <div class="brand-name">Kydesk</div>
                    <div class="super-brand-meta">Super Admin</div>
                </div>
                <button type="button" @click.stop="toggleSidebar()" class="sidebar-toggle" :data-tooltip="sidebarCollapsed ? 'Expandir menú (Ctrl+B)' : 'Colapsar menú (Ctrl+B)'" aria-label="Alternar menú">
                    <i class="lucide lucide-chevrons-left" x-show="!sidebarCollapsed"></i>
                    <i class="lucide lucide-chevrons-right" x-show="sidebarCollapsed" x-cloak></i>
                </button>
            </div>

            <?php foreach ($nav as $section => $items):
                $visible = array_filter($items, fn($i) => !$i[3] || $can($i[3]));
                if (!$visible) continue;
            ?>
                <nav class="nav-section">
                    <div class="nav-heading"><?= $e($section) ?></div>
                    <?php foreach ($visible as [$l,$ic,$p,$perm]):
                        $active = $isActive($p);
                    ?>
                        <a href="<?= $url('/admin' . $p) ?>" class="nav-item <?= $active?'active':'' ?>" data-tooltip="<?= $e($l) ?>">
                            <i class="lucide lucide-<?= $ic ?>"></i><span class="nav-label"><?= $e($l) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endforeach; ?>

            <div class="mt-auto pt-3 sidebar-bottom-card" style="border-top:1px solid var(--border);">
                <div class="super-user-pill">
                    <div style="width:34px;height:34px;border-radius:10px;background:<?= Helpers::colorFor($admin['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:12.5px"><?= Helpers::initials($admin['name']) ?></div>
                    <div style="min-width:0; flex:1">
                        <div class="font-display font-bold text-[13px]" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($admin['name']) ?></div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.14em]" style="color:var(--brand-700)"><?= $e($admin['role']) ?></div>
                    </div>
                </div>
                <div class="flex gap-2 mt-2">
                    <a href="<?= $url('/admin/profile') ?>" class="btn btn-outline btn-sm" style="flex:1; padding:0 10px"><i class="lucide lucide-user text-[13px]"></i> Perfil</a>
                    <form method="POST" action="<?= $url('/admin/logout') ?>" style="flex:1">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="width:100%; color:#ef4444; padding:0 10px" data-tooltip="Salir"><i class="lucide lucide-log-out text-[13px]"></i></button>
                    </form>
                </div>
            </div>

            <!-- Compact bottom row when collapsed -->
            <div class="sidebar-collapsed-foot mt-auto pt-3" style="border-top:1px solid var(--border);">
                <a href="<?= $url('/admin/profile') ?>" class="nav-item" data-tooltip="<?= $e($admin['name']) ?> · <?= $e($admin['role']) ?>" style="justify-content:center; padding:10px 0">
                    <div style="width:30px;height:30px;border-radius:9px;background:<?= Helpers::colorFor($admin['email']) ?>;color:white;display:grid;place-items:center;font-weight:700;font-size:11.5px"><?= Helpers::initials($admin['name']) ?></div>
                </a>
                <form method="POST" action="<?= $url('/admin/logout') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="nav-item" data-tooltip="Cerrar sesión" style="width:100%; justify-content:center; padding:10px 0; color:#ef4444"><i class="lucide lucide-log-out"></i></button>
                </form>
            </div>
        </aside>

        <div x-show="sidebarOpen" @click="sidebarOpen=false" class="fixed inset-0 bg-black/30 z-40 lg:hidden" x-cloak></div>

        <div class="main">
            <div class="topbar">
                <button @click="window.innerWidth >= 1024 ? toggleSidebar() : (sidebarOpen=true)" class="icon-btn" data-tooltip="Menú (⌘B)">
                    <i class="lucide lucide-menu"></i>
                </button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span class="super-tag"><i class="lucide lucide-shield text-[10px]"></i> Super Admin</span>
                        <?php if (!empty($title) && (!isset($pageHeading) || $title !== $pageHeading)): ?>
                            <span class="text-[11.5px] font-medium" style="color:var(--ink-400)"><?= $e($title) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-h1" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis"><?= $e($pageHeading ?? $title ?? 'Panel') ?></div>
                </div>
                <a href="<?= $url('/') ?>" target="_blank" class="btn btn-outline btn-sm" data-tooltip="Sitio público"><i class="lucide lucide-external-link text-[13px]"></i> <span class="hidden sm:inline">Sitio</span></a>
                <div class="relative">
                    <button @click="userMenu=!userMenu" class="user-pill">
                        <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($admin['email']) ?>; color:white"><?= Helpers::initials($admin['name']) ?></div>
                        <span class="font-display font-bold text-[13.5px] hidden sm:inline"><?= $e(explode(' ', $admin['name'])[0]) ?></span>
                        <i class="lucide lucide-chevron-down text-sm text-ink-400"></i>
                    </button>
                    <div x-show="userMenu" @click.away="userMenu=false" x-cloak class="popover absolute right-0 mt-2 w-60 z-30">
                        <div class="px-3 py-2 border-b" style="border-color:var(--border); margin-bottom:4px">
                            <div class="font-display font-bold text-[13.5px]"><?= $e($admin['name']) ?></div>
                            <div class="text-[11.5px] text-ink-400"><?= $e($admin['email']) ?></div>
                            <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-[0.12em]" style="background:var(--brand-50); color:var(--brand-700)"><i class="lucide lucide-shield text-[10px]"></i> <?= $e($admin['role']) ?></div>
                        </div>
                        <a href="<?= $url('/admin/profile') ?>" class="popover-item"><i class="lucide lucide-user text-sm"></i><span>Mi perfil</span></a>
                        <?php if ($can('settings.view')): ?>
                            <a href="<?= $url('/admin/settings') ?>" class="popover-item"><i class="lucide lucide-settings text-sm"></i><span>Ajustes</span></a>
                        <?php endif; ?>
                        <a href="<?= $url('/') ?>" target="_blank" class="popover-item"><i class="lucide lucide-external-link text-sm"></i><span>Sitio público</span></a>
                        <div class="my-1" style="height:1px;background:var(--border)"></div>
                        <form method="POST" action="<?= $url('/admin/logout') ?>">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="popover-item" style="color:#ef4444"><i class="lucide lucide-log-out text-sm"></i><span>Cerrar sesión</span></button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($flash['success'])): ?>
                <div class="admin-flash-success"><i class="lucide lucide-check-circle"></i><?= $e($flash['success']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="admin-flash-error"><i class="lucide lucide-alert-circle"></i><?= $e($flash['error']) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>
</div>

<script src="<?= $asset('js/app.js') ?>"></script>
</body>
</html>
