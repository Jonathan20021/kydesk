<?php
use App\Core\Helpers;
use App\Core\Plan;
use App\Core\Prefs;
use App\Core\License;
try { Prefs::ensureSchema($app->db); } catch (\Throwable $_layoutErr) {}
$user = $auth->user();
$prefs = Prefs::get($user);
$tenant = $app->tenant;
$slug = $tenant?->slug ?? '';
$current = $_SERVER['REQUEST_URI'] ?? '';
$basePath = $app->config['app']['base'] ?? '';
$isActive = function (string $path) use ($current, $slug, $basePath) {
    $full = $basePath . '/t/' . $slug . $path;
    return strpos($current, $full) === 0;
};
$can = fn(string $p) => $auth->can($p);
$planHas = fn(string $f) => Plan::has($tenant, $f);
$planName = Plan::tenantPlan($tenant);
$planLabel = Plan::label($tenant);

$navOps = [
    [__('nav.dashboard'),  'layout-dashboard','/dashboard',null,'tickets'],
    [__('nav.tickets'),    'inbox',           '/tickets','tickets.view','tickets'],
    [__('nav.board'),      'kanban-square',   '/tickets/board','tickets.view','tickets'],
    [__('nav.categories'), 'tags',            '/categories','tickets.view','tickets'],
    [__('nav.macros'),     'zap',             '/macros','tickets.comment','tickets'],
    [__('nav.todos'),      'check-square',    '/todos','todos.view','todos'],
    [__('nav.notes'),      'notebook-pen',    '/notes','notes.view','notes'],
];
$navManagement = [
    [__('nav.departments'), 'layers',         '/departments','departments.view','departments'],
    [__('nav.crm'),         'contact-round',  '/crm','crm.view','crm'],
    [__('nav.companies'),   'building-2',     '/companies','companies.view','companies'],
    [__('nav.quotes'),      'file-text',      '/quotes','quotes.view','quotes'],
    [__('nav.retainers'),   'handshake',      '/retainers','retainers.view','retainers'],
    [__('nav.meetings'),    'calendar-clock', '/meetings','meetings.view','meetings'],
    [__('nav.time'),        'timer',          '/time','time.view','time_tracking'],
    [__('nav.assets'),      'server',         '/assets','assets.view','assets'],
    [__('nav.kb'),          'book-open',      '/kb','kb.view','kb'],
    [__('nav.live_chat'),   'message-square', '/chat','chat.view','live_chat'],
    [__('nav.reports'),     'line-chart',     '/reports','reports.view','reports'],
    [__('nav.reports_builder'),'bar-chart-3', '/reports-builder','reports.builder','reports_builder'],
];
$navAdmin = [
    [__('nav.automations'),  'workflow',     '/automations','automations.view','automations'],
    [__('nav.integrations'), 'plug',         '/integrations','integrations.view','integrations'],
    [__('nav.email_inbound'),'mail-open',    '/email-inbound','email.view','email_inbound'],
    [__('nav.ai'),           'sparkles',     '/ai','ai.config','ai_assist'],
    [__('nav.itsm'),         'workflow',     '/itsm','itsm.view','itsm'],
    [__('nav.sla'),          'gauge',        '/sla','sla.view','sla'],
    [__('nav.audit'),        'history',      '/audit','audit.view','audit'],
    [__('nav.csat'),         'smile',        '/csat','csat.view','csat'],
    [__('nav.status_page'),  'activity',     '/status','status.view','status_page'],
    [__('nav.custom_fields'),'list-plus',    '/custom-fields','custom_fields.view','custom_fields'],
    [__('nav.portal_users'), 'lock-keyhole', '/portal-users','portal.manage','customer_portal'],
    [__('nav.users'),        'users',        '/users','users.view','users'],
    [__('nav.roles'),        'shield',       '/roles','roles.view','roles'],
    [__('nav.billing'),      'wallet',       '/billing',null,'tickets'],
];

$openTickets = (int)$app->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status IN ('open','in_progress')", [$tenant->id ?? 0]);
$friends = $app->db->all("SELECT id,name,email,title FROM users WHERE tenant_id=? AND id<>? ORDER BY id LIMIT 3", [$tenant->id ?? 0, $user['id']]);

$isDemo = (int)($tenant->data['is_demo'] ?? 0) === 1;
$demoExpiresAt = $tenant->data['demo_expires_at'] ?? null;
$demoPlan = $tenant->data['demo_plan'] ?? null;
$demoCreds = $app->session->get('demo_credentials');

$license = null;
if (!$isDemo) {
    try { $license = License::status($tenant); } catch (\Throwable $_licErr) { $license = null; }
}
$showLicenseBanner = $license && $license['is_usable'] && in_array($license['state'], ['trial','past_due'], true);
?><!DOCTYPE html>
<html lang="<?= $e($locale ?? 'es') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e(($title ?? 'Panel') . ' · ' . ($tenant->name ?? 'Kydesk')) ?></title>
<meta name="csrf-token" content="<?= $e($csrf) ?>">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { sans:['Inter','sans-serif'], display:['Plus Jakarta Sans','sans-serif'], mono:['Geist Mono','monospace'] },
    colors: {
        brand: { 50:'#f3f0ff',100:'#e7e0ff',200:'#cdbfff',300:'#a78bfa',400:'#8b6dff',500:'#7c5cff',600:'#6c47ff',700:'#5a3aff' },
        ink: { 900:'#16151b',700:'#2a2a33',500:'#6b6b78',400:'#8e8e9a',300:'#b8b8c4' }
    }
} } };
</script>
<link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script>
function kydeskRenderIcons(){
    document.querySelectorAll('i.lucide, span.lucide').forEach(el => {
        if (el.dataset.lucide) return;
        const cls = [...el.classList].find(c => c.startsWith('lucide-') && c !== 'lucide');
        if (cls) el.setAttribute('data-lucide', cls.replace('lucide-',''));
    });
    if (window.lucide) window.lucide.createIcons({ attrs: { width: '1em', height: '1em', 'stroke-width': 2 } });
}
document.addEventListener('DOMContentLoaded', kydeskRenderIcons);
window.renderIcons = kydeskRenderIcons;
</script>
</head>
<body
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: false,
        userMenu: false, cmd: false, notifMenu: false, shortcuts: false,
        toggleSidebar(){
            this.sidebarCollapsed = !this.sidebarCollapsed;
            try { localStorage.setItem('kydesk_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0'); } catch(e){}
            document.body.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
        }
    }"
    x-init="sidebarCollapsed = (localStorage.getItem('kydesk_sidebar_collapsed')==='1'); document.body.classList.toggle('sidebar-collapsed', sidebarCollapsed)"
    @keydown.window.shift.question="shortcuts=true"
    @keydown.window.meta.k.prevent="cmd=true"
    @keydown.window.ctrl.k.prevent="cmd=true"
    @keydown.window.meta.b.prevent="toggleSidebar()"
    @keydown.window.ctrl.b.prevent="toggleSidebar()"
    data-theme="<?= $e($prefs['theme']) ?>" data-density="<?= $e($prefs['density']) ?>" data-sidebar="<?= $e($prefs['sidebar_mode']) ?>" data-wallpaper="<?= $e($prefs['wallpaper']) ?>" style="<?= Prefs::styleVars($prefs) ?>">

<div class="app-shell">
    <div class="app-frame">

        <aside class="sidebar" :class="sidebarOpen && 'open'">
            <div class="brand">
                <div class="brand-logo"><i class="lucide lucide-zap text-base"></i></div>
                <div class="brand-name">Kydesk</div>
                <button type="button" @click.stop="toggleSidebar()" class="sidebar-toggle" :data-tooltip="(sidebarCollapsed ? <?= htmlspecialchars(json_encode(__('app.expand_menu')), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(__('app.collapse_menu')), ENT_QUOTES) ?>) + ' (' + ((window.KYDESK_OS||'win')==='mac' ? '⌘B' : 'Ctrl+B') + ')'" aria-label="<?= __e('app.toggle_menu') ?>">
                    <i class="lucide lucide-chevrons-left" x-show="!sidebarCollapsed"></i>
                    <i class="lucide lucide-chevrons-right" x-show="sidebarCollapsed" x-cloak></i>
                </button>
            </div>

            <nav class="nav-section">
                <div class="nav-heading"><?= __e('app.section.general') ?></div>
                <?php foreach ($navOps as [$l,$ic,$p,$perm,$feat]):
                    if ($perm && !$can($perm)) continue;
                    if ($feat && !$planHas($feat)) continue;
                    $active = $isActive($p); ?>
                    <a href="<?= $url('/t/' . $slug . $p) ?>" class="nav-item <?= $active?'active':'' ?>" data-tooltip="<?= $e($l) ?>">
                        <i class="lucide lucide-<?= $ic ?>"></i><span class="nav-label"><?= $l ?></span>
                        <?php if ($p === '/tickets' && $openTickets > 0): ?><span class="badge-mini"><?= $openTickets ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php $visMgmt = array_filter($navManagement, fn($i) => (!$i[3] || $can($i[3])) && $planHas($i[4])); if ($visMgmt): ?>
            <nav class="nav-section">
                <div class="nav-heading"><?= __e('app.section.management') ?></div>
                <?php foreach ($visMgmt as [$l,$ic,$p,$perm,$feat]): $active = $isActive($p); ?>
                    <a href="<?= $url('/t/' . $slug . $p) ?>" class="nav-item <?= $active?'active':'' ?>" data-tooltip="<?= $e($l) ?>">
                        <i class="lucide lucide-<?= $ic ?>"></i><span class="nav-label"><?= $l ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>

            <?php
            $visAdmin = [];
            $lockedAdmin = [];
            foreach ($navAdmin as $item) {
                [$l,$ic,$p,$perm,$feat] = $item;
                if ($perm && !$can($perm)) continue;
                if ($planHas($feat)) $visAdmin[] = $item;
                else $lockedAdmin[] = $item;
            }
            if ($visAdmin || $lockedAdmin): ?>
            <nav class="nav-section">
                <div class="nav-heading"><?= __e('app.section.admin') ?></div>
                <?php foreach ($visAdmin as [$l,$ic,$p,$perm,$feat]): $active = $isActive($p); ?>
                    <a href="<?= $url('/t/' . $slug . $p) ?>" class="nav-item <?= $active?'active':'' ?>" data-tooltip="<?= $e($l) ?>">
                        <i class="lucide lucide-<?= $ic ?>"></i><span class="nav-label"><?= $l ?></span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($lockedAdmin as [$l,$ic,$p,$perm,$feat]): ?>
                    <a href="<?= $url('/t/' . $slug . $p) ?>" class="nav-item" style="opacity:.5" data-tooltip="<?= $e($l) ?> · <?= __e('app.locked.available_pro') ?>">
                        <i class="lucide lucide-<?= $ic ?>"></i><span class="nav-label flex-1"><?= $l ?></span>
                        <i class="lucide lucide-lock text-[11px] text-ink-400 nav-lock"></i>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>

            <?php if ($tenant && !empty($lockedAdmin)):
                $lockedNames = array_map(fn($i) => $i[0], $lockedAdmin);
                $lockedSummary = count($lockedNames) > 2
                    ? implode(', ', array_slice($lockedNames, 0, 2)) . ' y ' . (count($lockedNames) - 2) . ' más'
                    : implode(' y ', $lockedNames);
            ?>
            <div class="mt-auto pt-3 sidebar-bottom-card">
                <div class="rounded-2xl p-4 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#1a1825,#2a1f3d);box-shadow:0 8px 20px -8px rgba(124,92,255,.4)">
                    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 0% 100%,rgba(124,92,255,.4),transparent 60%)"></div>
                    <div class="relative">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="lucide lucide-sparkles text-[14px]" style="color:#c4b5fd"></i>
                            <span class="text-[10.5px] font-bold uppercase tracking-[0.14em]" style="color:#c4b5fd">Plan <?= $e($planLabel) ?></span>
                        </div>
                        <div class="font-display font-bold text-[12.5px] leading-tight"><?= $e(__('app.locked.unlock', ['items' => strtolower($lockedSummary)])) ?></div>
                        <a href="<?= $url('/pricing') ?>" class="mt-3 inline-flex items-center gap-1 text-[11.5px] font-semibold" style="color:#a78bfa"><?= __e('app.locked.see_plans') ?> <i class="lucide lucide-arrow-right text-[10px]"></i></a>
                    </div>
                </div>
            </div>
            <?php elseif ($tenant && (int)($tenant->data['is_demo'] ?? 0) === 1): ?>
            <div class="mt-auto pt-3 sidebar-bottom-card">
                <div class="rounded-2xl p-4 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#1a1825,#2a1f3d);box-shadow:0 8px 20px -8px rgba(124,92,255,.4)">
                    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 100% 0%,rgba(34,197,94,.35),transparent 60%)"></div>
                    <div class="relative">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="lucide lucide-check-circle-2 text-[14px]" style="color:#86efac"></i>
                            <span class="text-[10.5px] font-bold uppercase tracking-[0.14em]" style="color:#86efac">Plan <?= $e($planLabel) ?></span>
                        </div>
                        <div class="font-display font-bold text-[12.5px] leading-tight"><?= __e('app.demo.unlocked_full') ?></div>
                        <a href="<?= $url('/auth/register') ?>" class="mt-3 inline-flex items-center gap-1 text-[11.5px] font-semibold" style="color:#a78bfa"><?= __e('app.demo.keep_plan') ?> <i class="lucide lucide-arrow-right text-[10px]"></i></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </aside>

        <div x-show="sidebarOpen" @click="sidebarOpen=false" class="fixed inset-0 bg-black/30 z-40 lg:hidden" x-cloak></div>

        <div class="main">
            <div class="topbar">
                <button @click="window.innerWidth >= 1024 ? toggleSidebar() : (sidebarOpen=true)" class="icon-btn" :data-tooltip="<?= htmlspecialchars(json_encode(__('app.menu')), ENT_QUOTES) ?> + ' (' + ((window.KYDESK_OS||'win')==='mac' ? '⌘B' : 'Ctrl+B') + ')'">
                    <i class="lucide lucide-menu"></i>
                </button>
                <div class="search-pill">
                    <i class="lucide lucide-search"></i>
                    <input @click="cmd=true" placeholder="<?= __e('app.search_placeholder') ?>" readonly>
                </div>
                <button @click="cmd=true" class="icon-btn" :data-tooltip="<?= htmlspecialchars(json_encode(__('app.search_tooltip')), ENT_QUOTES) ?> + ' (' + ((window.KYDESK_OS||'win')==='mac' ? '⌘K' : 'Ctrl+K') + ')'"><i class="lucide lucide-command"></i></button>
                <?php $variant = 'light'; $align = 'right'; $compact = false; include APP_PATH . '/Views/partials/lang_switcher.php'; ?>
                <div class="relative">
                    <button @click="notifMenu=!notifMenu" class="icon-btn" data-tooltip="<?= __e('app.notifications') ?>"><i class="lucide lucide-bell"></i><span class="dot-notif"></span></button>
                    <div x-show="notifMenu" @click.away="notifMenu=false" x-cloak class="popover absolute right-0 mt-2 z-30 notif-dropdown" x-transition>
                        <div class="px-3 py-2.5 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
                            <div class="font-display font-bold text-[14px]"><?= __e('app.notifications') ?></div>
                            <button class="text-[11.5px] text-brand-600 font-semibold"><?= __e('app.mark_all') ?></button>
                        </div>
                        <div class="p-2 max-h-80 overflow-y-auto">
                            <?php
                            $latestTickets = $app->db->all("SELECT id, code, subject, created_at FROM tickets WHERE tenant_id=? ORDER BY created_at DESC LIMIT 4", [$tenant->id ?? 0]);
                            foreach ($latestTickets as $lt): ?>
                                <a href="<?= $url('/t/' . $slug . '/tickets/' . $lt['id']) ?>" class="notif-item">
                                    <span class="notif-dot"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[12.5px] font-medium leading-snug truncate"><?= $e($lt['subject']) ?></div>
                                        <div class="text-[10.5px] text-ink-400 mt-0.5"><span class="font-mono"><?= $e($lt['code']) ?></span> · <?= Helpers::ago($lt['created_at']) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($latestTickets)): ?>
                                <div class="text-center py-6 text-[12px] text-ink-400"><?= __e('app.no_notifications') ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $url('/t/' . $slug . '/tickets') ?>" class="block text-center py-2.5 text-[12.5px] font-semibold text-brand-700 border-t" style="border-color:var(--border)"><?= __e('app.see_all_activity') ?></a>
                    </div>
                </div>
                <div class="relative">
                    <button @click="userMenu=!userMenu" class="user-pill">
                        <div class="avatar avatar-sm" style="background: <?= Helpers::colorFor($user['email']) ?>; color: white;"><?= Helpers::initials($user['name']) ?></div>
                        <span class="font-display font-bold text-[13.5px]"><?= $e(explode(' ', $user['name'])[0]) ?></span>
                        <i class="lucide lucide-chevron-down text-sm text-ink-400"></i>
                    </button>
                    <div x-show="userMenu" @click.away="userMenu=false" x-cloak class="popover absolute right-0 mt-2 w-60 z-30">
                        <div class="px-3 py-2 border-b border-ink-100" style="border-color:var(--border); margin-bottom:4px">
                            <div class="font-display font-bold text-[13.5px]"><?= $e($user['name']) ?></div>
                            <div class="text-[11.5px] text-ink-400"><?= $e($user['email']) ?></div>
                        </div>
                        <a href="<?= $url('/t/' . $slug . '/profile') ?>" class="popover-item"><i class="lucide lucide-user text-sm"></i><span><?= __e('app.user.my_profile') ?></span></a>
                        <a href="<?= $url('/t/' . $slug . '/preferences') ?>" class="popover-item"><i class="lucide lucide-palette text-sm"></i><span><?= __e('app.user.customize') ?></span></a>
                        <?php if ($can('settings.view')): ?>
                        <a href="<?= $url('/t/' . $slug . '/settings') ?>" class="popover-item"><i class="lucide lucide-settings text-sm"></i><span><?= __e('app.user.settings') ?></span></a>
                        <?php endif; ?>
                        <button @click="cmd=true" class="popover-item"><i class="lucide lucide-search text-sm"></i><span><?= __e('app.user.search') ?></span><span class="shortcut">⌘K</span></button>
                        <div class="my-1" style="height:1px;background:var(--border)"></div>
                        <form method="POST" action="<?= $url('/auth/logout') ?>">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="popover-item" style="color:#ef4444"><i class="lucide lucide-log-out text-sm"></i><span><?= __e('app.user.logout') ?></span></button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($isDemo && $demoExpiresAt): ?>
                <div class="relative overflow-hidden rounded-2xl text-white" style="background:linear-gradient(120deg,#0f0d18 0%,#1a1530 50%,#2a1f3d 100%);box-shadow:0 16px 40px -12px rgba(124,92,255,.35),inset 0 1px 0 rgba(255,255,255,.08)" x-data="{secs: Math.max(0, Math.floor((new Date('<?= str_replace(' ', 'T', $demoExpiresAt) ?>') - new Date()) / 1000)), label:'--:--:--'}" x-init="(()=>{ const tick=()=>{ if(secs>0)secs--; const h=Math.floor(secs/3600).toString().padStart(2,'0'); const m=Math.floor((secs%3600)/60).toString().padStart(2,'0'); const s=(secs%60).toString().padStart(2,'0'); label = h+':'+m+':'+s; }; tick(); setInterval(tick,1000); })()">
                    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 0% 50%,rgba(124,92,255,.4),transparent 55%),radial-gradient(circle at 100% 50%,rgba(217,70,239,.18),transparent 60%)"></div>
                    <div class="absolute inset-y-0 left-0 w-[1px]" style="background:linear-gradient(180deg,transparent,rgba(124,92,255,.6),transparent)"></div>

                    <div class="relative flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-4 px-5 py-3.5">

                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0 relative" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);box-shadow:0 6px 16px -4px rgba(124,92,255,.6)">
                                <i class="lucide lucide-rocket text-[16px]"></i>
                                <span class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full" style="background:#22c55e;box-shadow:0 0 0 2px #1a1530,0 0 8px #22c55e"></span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-extrabold text-[14px] tracking-[-0.015em]">Demo <?= $e(ucfirst((string)$demoPlan)) ?></span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-[0.12em]" style="background:rgba(34,197,94,.18);color:#86efac;border:1px solid rgba(34,197,94,.3)"><?= __e('app.demo.live') ?></span>
                                </div>
                                <div class="text-[11.5px] mt-0.5" style="color:rgba(255,255,255,.55)"><?= __e('app.demo.subtitle') ?></div>
                            </div>
                        </div>

                        <?php if ($demoCreds): ?>
                            <div class="hidden xl:flex items-center gap-2 px-3 py-2 rounded-xl" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
                                <i class="lucide lucide-key-round text-[13px]" style="color:rgba(255,255,255,.4)"></i>
                                <div class="font-mono text-[11px] leading-tight">
                                    <div style="color:rgba(255,255,255,.85)"><?= $e($demoCreds['email']) ?></div>
                                    <div style="color:rgba(255,255,255,.5)"><?= __e('app.demo.password') ?>: <span style="color:#c4b5fd"><?= $e($demoCreds['password']) ?></span></div>
                                </div>
                                <button type="button" onclick="navigator.clipboard.writeText('<?= $e($demoCreds['email']) ?> / <?= $e($demoCreds['password']) ?>'); this.querySelector('i').setAttribute('data-lucide','check'); window.renderIcons && window.renderIcons();" class="ml-1 w-7 h-7 rounded-lg grid place-items-center transition" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.6)" onmouseover="this.style.background='rgba(255,255,255,.12)'" onmouseout="this.style.background='rgba(255,255,255,.06)'"><i class="lucide lucide-copy text-[12px]"></i></button>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-2 lg:ml-auto">
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">
                                <span class="relative inline-flex w-2 h-2"><span class="absolute inset-0 rounded-full" style="background:#fbbf24;animation:pulse-ring 2s ease-out infinite"></span><span class="relative inline-block w-2 h-2 rounded-full" style="background:#fbbf24"></span></span>
                                <span class="text-[10px] font-bold uppercase tracking-[0.14em]" style="color:rgba(255,255,255,.5)">Expira en</span>
                                <span class="font-mono font-bold text-[14px] tabular-nums" style="color:#fde68a;text-shadow:0 0 12px rgba(253,224,71,.4)" x-text="label"></span>
                            </div>
                            <a href="<?= $url('/auth/register') ?>" class="inline-flex items-center gap-1.5 h-[38px] px-4 rounded-xl font-semibold text-[12.5px] transition" style="background:white;color:#0f0d18;box-shadow:0 4px 12px -2px rgba(0,0,0,.3)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">Quedármelo <i class="lucide lucide-arrow-right text-[13px]"></i></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($showLicenseBanner):
                $isTrial = $license['state'] === 'trial';
                $daysLeft = $license['days_left'];
                $hoursLeft = $license['hours_left'];
                $endsAt = $license['trial_ends_at'] ?? $license['period_end'];
                $accent = $isTrial ? ['#7c5cff', '#a78bfa', '#c4b5fd', 'rocket'] : ['#f59e0b', '#fbbf24', '#fde68a', 'alert-triangle'];
            ?>
                <div class="relative overflow-hidden rounded-2xl text-white" style="background:linear-gradient(120deg,#0f0d18 0%,#1a1530 50%,#2a1f3d 100%);box-shadow:0 16px 40px -12px rgba(124,92,255,.3),inset 0 1px 0 rgba(255,255,255,.08)">
                    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 0% 50%,rgba(124,92,255,.35),transparent 55%),radial-gradient(circle at 100% 50%,rgba(217,70,239,.14),transparent 60%)"></div>
                    <div class="relative flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-4 px-5 py-3.5">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,<?= $accent[0] ?>,<?= $accent[1] ?>);box-shadow:0 6px 16px -4px <?= $accent[0] ?>aa">
                                <i class="lucide lucide-<?= $accent[3] ?> text-[16px]"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-display font-extrabold text-[14px] tracking-[-0.015em]"><?= $isTrial ? 'Período de prueba activo' : 'Pago vencido' ?></span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-[0.12em]" style="background:rgba(124,92,255,.18);color:<?= $accent[2] ?>;border:1px solid rgba(124,92,255,.3)">Plan <?= $e($license['plan_name']) ?></span>
                                </div>
                                <div class="text-[11.5px] mt-0.5" style="color:rgba(255,255,255,.55)">
                                    <?php if ($isTrial && $endsAt): ?>
                                        Tu licencia será gestionada por el equipo de Kydesk · expira <?= $e($endsAt) ?>
                                    <?php elseif (!$isTrial): ?>
                                        Regulariza el pago para mantener acceso completo
                                    <?php else: ?>
                                        Tu organización está en evaluación
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 lg:ml-auto">
                            <?php if ($daysLeft !== null && $daysLeft >= 0): ?>
                                <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">
                                    <span class="relative inline-flex w-2 h-2"><span class="absolute inset-0 rounded-full" style="background:<?= $accent[1] ?>;animation:pulse-ring 2s ease-out infinite"></span><span class="relative inline-block w-2 h-2 rounded-full" style="background:<?= $accent[1] ?>"></span></span>
                                    <span class="text-[10px] font-bold uppercase tracking-[0.14em]" style="color:rgba(255,255,255,.5)"><?= $isTrial ? 'Quedan' : 'Vence en' ?></span>
                                    <span class="font-mono font-bold text-[14px] tabular-nums" style="color:<?= $accent[2] ?>;text-shadow:0 0 12px <?= $accent[1] ?>66">
                                        <?php if ($daysLeft >= 1): ?>
                                            <?= $daysLeft ?> día<?= $daysLeft === 1 ? '' : 's' ?>
                                        <?php else: ?>
                                            <?= max(0, (int)$hoursLeft) ?> h
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <a href="mailto:<?= $e(\App\Core\License::settingStr('saas_billing_email', 'soporte@kydesk.com')) ?>?subject=<?= rawurlencode('Activación de licencia · ' . $tenant->name) ?>" class="inline-flex items-center gap-1.5 h-[38px] px-4 rounded-xl font-semibold text-[12.5px] transition" style="background:white;color:#0f0d18;box-shadow:0 4px 12px -2px rgba(0,0,0,.3)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'"><i class="lucide lucide-shield-check text-[13px]"></i> Activar licencia</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($flash['success'])): ?>
                <div class="welcome-strip" x-data="{show:true}" x-show="show">
                    <div class="welcome-strip-icon"><i class="lucide lucide-check"></i></div>
                    <div class="flex-1"><?= $e($flash['success']) ?></div>
                    <button @click="show=false" class="welcome-strip-close"><i class="lucide lucide-x text-[14px]"></i></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="welcome-strip" x-data="{show:true}" x-show="show" style="background:linear-gradient(90deg,#fef2f2,#fff1f2);border-color:#fecaca;color:#991b1b">
                    <div class="welcome-strip-icon" style="color:#b91c1c"><i class="lucide lucide-alert-circle"></i></div>
                    <div class="flex-1"><?= $e($flash['error']) ?></div>
                    <button @click="show=false" class="welcome-strip-close"><i class="lucide lucide-x text-[14px]"></i></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>
</div>

<div x-show="cmd" x-cloak class="fixed inset-0 z-50 grid place-items-start pt-24 px-4 bg-ink-900/40" @click.self="cmd=false" @keydown.escape.window="cmd=false">
    <div class="popover w-full max-w-xl" style="padding:0">
        <div class="flex items-center gap-2 px-4" style="border-bottom:1px solid var(--border)">
            <i class="lucide lucide-search text-ink-400"></i>
            <input autofocus type="text" placeholder="Buscar o ejecutar comando..." class="flex-1 py-3.5 text-sm border-0 outline-none bg-transparent">
            <span class="kbd">ESC</span>
        </div>
        <div class="p-2 max-h-96 overflow-y-auto">
            <div class="px-3 py-2 nav-heading" style="margin-bottom:0">Ir a</div>
            <?php foreach ([
                ['/dashboard','Dashboard','layout-dashboard'],
                ['/tickets','Tickets','inbox'],
                ['/tickets/board','Tablero','kanban-square'],
                ['/companies','Empresas','building-2'],
                ['/assets','Activos','server'],
                ['/kb','Conocimiento','book-open'],
                ['/notes','Notas','notebook-pen'],
                ['/todos','Tareas','check-square'],
                ['/automations','Automatizaciones','workflow'],
                ['/sla','SLA','gauge'],
                ['/users','Usuarios','users'],
                ['/reports','Reportes','line-chart'],
            ] as [$p,$l,$ic]): ?>
                <a href="<?= $url('/t/' . $slug . $p) ?>" class="popover-item">
                    <i class="lucide lucide-<?= $ic ?> text-sm text-ink-400"></i><span><?= $l ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Keyboard shortcuts modal -->
<?php
// Tabla de atajos. Cada combo usa placeholders MOD/SHIFT/ALT/RETURN/ESC que se renderizan
// según el SO desde JS (window.KYDESK_RENDER_COMBO).
$shortcutGroups = [
    'Navegación global' => [
        ['Abrir paleta de comandos',       ['MOD+K']],
        ['Paleta (alt · estilo VSCode)',   ['MOD+SHIFT+P']],
        ['Mostrar / ocultar atajos',       ['?'], ['MOD+/']],
        ['Colapsar / expandir sidebar',    ['MOD+B']],
        ['Abrir ajustes',                  ['MOD+,']],
        ['Foco en buscador',               ['/']],
        ['Cerrar modal o popup',           ['ESC']],
    ],
    'Ir a (G + letra)' => [
        ['Dashboard',                      ['G+D']],
        ['Tickets',                        ['G+T']],
        ['Tablero Kanban',                 ['G+B']],
        ['Categorías',                     ['G+C']],
        ['Notas',                          ['G+N']],
        ['Tareas',                         ['G+O']],
        ['Conocimiento (KB)',              ['G+K']],
        ['Empresas',                       ['G+E']],
        ['Activos',                        ['G+A']],
        ['Reuniones',                      ['G+M']],
        ['Igualas',                        ['G+I']],
        ['Reportes',                       ['G+R']],
        ['Usuarios',                       ['G+U']],
        ['Automatizaciones',               ['G+Y']],
        ['Integraciones',                  ['G+G']],
        ['SLA',                            ['G+Q']],
        ['Custom Fields',                  ['G+F']],
        ['ITSM',                           ['G+X']],
        ['Auditoría',                      ['G+L']],
        ['Ajustes',                        ['G+S']],
        ['Mi perfil',                      ['G+P']],
        ['Centro de ayuda',                ['G+H']],
    ],
    'Acciones rápidas' => [
        ['Crear nuevo ticket',             ['C']],
        ['Asignarme el ticket',            ['A']],
        ['Resolver ticket',                ['R']],
        ['Escalar ticket',                 ['E']],
        ['Abrir menú de macros',           ['M']],
        ['Enviar respuesta / comentario',  ['MOD+RETURN']],
    ],
    'En modales y formularios' => [
        ['Cerrar modal',                   ['ESC']],
        ['Submit del formulario activo',   ['MOD+RETURN']],
        ['Foco en buscador del modal',     ['MOD+K']],
    ],
];
?>
<div x-show="shortcuts" x-cloak class="fixed inset-0 z-[60] grid place-items-center p-4" style="background:rgba(15,13,24,.6);backdrop-filter:blur(8px)" @click.self="shortcuts=false" @keydown.escape.window="shortcuts=false" x-transition>
    <div class="w-full max-w-3xl rounded-3xl overflow-hidden flex flex-col" style="background:white;box-shadow:0 40px 80px -20px rgba(15,13,24,.5);max-height:85vh"
         x-data="kydeskShortcutsModal()">
        <!-- Header -->
        <div class="px-6 py-5 flex items-center justify-between border-b border-[#ececef]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white"><i class="lucide lucide-keyboard text-[18px]"></i></div>
                <div>
                    <div class="font-display font-extrabold text-[18px] tracking-[-0.02em]">Atajos de teclado</div>
                    <div class="text-[11.5px] text-ink-400">Pulsá <kbd class="kbd">?</kbd> o <kbd class="kbd" x-text="renderKey('MOD')"></kbd><kbd class="kbd">/</kbd> para abrir esto</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- OS tabs -->
                <div class="inline-flex items-center rounded-xl p-0.5" style="background:#f3f4f6;border:1px solid #ececef">
                    <?php foreach (['mac' => 'macOS', 'win' => 'Windows', 'linux' => 'Linux'] as $osKey => $osLabel): ?>
                        <button type="button" @click="os = '<?= $osKey ?>'"
                                :class="os === '<?= $osKey ?>' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-400 hover:text-ink-700'"
                                class="text-[11.5px] font-semibold px-3 py-1.5 rounded-lg transition">
                            <?= $osLabel ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button @click="shortcuts=false" class="w-9 h-9 rounded-lg grid place-items-center text-ink-400 hover:bg-bg hover:text-ink-900 transition"><i class="lucide lucide-x text-[16px]"></i></button>
            </div>
        </div>

        <!-- Search input -->
        <div class="px-6 py-3 border-b border-[#ececef] flex items-center gap-2" style="background:#fafafb">
            <i class="lucide lucide-search text-ink-400 text-[14px]"></i>
            <input type="text" x-model="filter" placeholder="Filtrar atajos..." class="flex-1 bg-transparent text-[13px] outline-none border-0 py-1 placeholder-ink-400" @keydown.escape.stop="filter=''">
            <span x-show="filter" @click="filter=''" class="text-[11px] text-ink-400 cursor-pointer hover:text-ink-700" x-cloak>limpiar</span>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            <?php foreach ($shortcutGroups as $group => $items): ?>
                <?php
                    $groupLabels = array_map(fn($item) => $item[0], $items);
                ?>
                <div x-show="groupHasMatches(<?= $e(json_encode($groupLabels)) ?>)">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-ink-400 mb-3"><?= $e($group) ?></div>
                    <div class="space-y-2.5">
                        <?php foreach ($items as $item):
                            $lbl = $item[0];
                            $combos = array_slice($item, 1);
                        ?>
                            <div class="flex items-center justify-between text-[13px]" x-show="matches(<?= $e(json_encode($lbl)) ?>)">
                                <span class="text-ink-700"><?= $e($lbl) ?></span>
                                <span class="flex items-center gap-1.5">
                                    <?php foreach ($combos as $j => $combo): ?>
                                        <?php if ($j > 0): ?><span class="text-[10px] text-ink-400 mx-0.5">o</span><?php endif; ?>
                                        <span class="inline-flex items-center gap-0.5">
                                            <template x-for="k in renderCombo(<?= $e(json_encode($combo)) ?>)">
                                                <kbd class="kbd" x-text="k"></kbd>
                                            </template>
                                        </span>
                                    <?php endforeach; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-2" style="background:#fafafb;border-top:1px solid #ececef">
            <div class="text-[11.5px] text-ink-400 inline-flex items-center gap-1.5">
                <i class="lucide lucide-info text-[12px]"></i>
                Tip: <kbd class="kbd mx-1">G</kbd> seguido de una letra te lleva a cualquier sección
            </div>
            <div class="text-[11px] text-ink-400 inline-flex items-center gap-1.5" x-show="autoOs">
                <i class="lucide lucide-cpu text-[11px]"></i>
                Detectado: <strong class="text-ink-700" x-text="osLabel()"></strong>
            </div>
        </div>
    </div>
</div>

<script>
function kydeskShortcutsModal() {
    return {
        os: (window.KYDESK_OS || 'win'),
        autoOs: true,
        filter: '',
        renderCombo(combo) {
            return (window.KYDESK_RENDER_COMBO || ((c) => c.split('+')))(combo, this.os);
        },
        renderKey(key) {
            const dict = (window.KYDESK_KEYS || {})[this.os] || {};
            return dict[key.toUpperCase()] || key;
        },
        osLabel() {
            return ({mac: 'macOS', win: 'Windows', linux: 'Linux'})[this.os] || this.os;
        },
        matches(label) {
            const f = (this.filter || '').trim().toLowerCase();
            if (!f) return true;
            return label.toLowerCase().includes(f);
        },
        groupHasMatches(labels) {
            const f = (this.filter || '').trim().toLowerCase();
            if (!f) return true;
            return labels.some(l => l.toLowerCase().includes(f));
        },
    };
}
</script>

<!-- Floating help button (bottom-right) -->
<button @click="shortcuts=true" class="fixed bottom-5 right-5 w-11 h-11 rounded-full grid place-items-center transition z-30 hidden lg:grid" style="background:white;border:1px solid #ececef;box-shadow:0 8px 20px -8px rgba(22,21,27,.15);color:#6b6b78" data-tooltip="Atajos de teclado (?)" onmouseover="this.style.color='#7c5cff';this.style.borderColor='#cdbfff'" onmouseout="this.style.color='#6b6b78';this.style.borderColor='#ececef'">
    <i class="lucide lucide-keyboard text-[16px]"></i>
</button>

<script src="<?= $asset('js/app.js') ?>"></script>
</body>
</html>
