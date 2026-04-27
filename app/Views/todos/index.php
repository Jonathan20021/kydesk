<?php
use App\Core\Helpers;

$slug = $tenant->slug;
$viewMode = $viewMode ?? 'inbox';
$currentListId = (int)($currentListId ?? 0);
$counts = $counts ?? ['pending'=>0,'done'=>0,'today'=>0,'overdue'=>0,'important'=>0,'delegated'=>0,'alerts'=>0,'progress'=>0];
$listCounts = $listCounts ?? [];
$users = $users ?? [];
$lists = $lists ?? [];
$todos = $todos ?? [];
$upcoming = $upcoming ?? [];
$teamLoad = $teamLoad ?? [];
$priorityFilter = $priorityFilter ?? '';
$assigneeFilter = $assigneeFilter ?? '';
$search = $search ?? '';

$priColor = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
$priLabel = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baja'];
$priBg    = ['urgent'=>'#fef2f2','high'=>'#fffbeb','medium'=>'#f5f3ff','low'=>'#f9fafb'];

$dt = function ($value) {
    if (!$value) return 'Sin fecha';
    $ts = strtotime((string)$value);
    $today = strtotime(date('Y-m-d'));
    $tomorrow = $today + 86400;
    if ($ts >= $today && $ts < $today + 86400) return 'Hoy ' . date('H:i', $ts);
    if ($ts >= $tomorrow && $ts < $tomorrow + 86400) return 'Mañana ' . date('H:i', $ts);
    return date('d M · H:i', $ts);
};
$inputDt = function ($value) {
    if (!$value) return '';
    return date('Y-m-d\TH:i', strtotime((string)$value));
};
$initials = function ($name) {
    $parts = preg_split('/\s+/', trim((string)$name));
    $out = '';
    foreach (array_slice(array_filter($parts), 0, 2) as $p) $out .= strtoupper(substr($p, 0, 1));
    return $out ?: 'U';
};
$avatarColor = function ($name) {
    $palette = ['#7c5cff','#0ea5e9','#10b981','#f59e0b','#ef4444','#ec4899','#8b5cf6','#14b8a6'];
    return $palette[abs(crc32((string)$name)) % count($palette)];
};
$labelsOf = function ($labels) {
    return array_values(array_filter(array_map('trim', explode(',', (string)$labels))));
};
$build = function (array $overrides = []) use ($url, $slug, $viewMode, $currentListId, $search, $priorityFilter, $assigneeFilter) {
    $params = [
        'view' => $viewMode,
        'list' => $currentListId ?: null,
        'q' => $search !== '' ? $search : null,
        'priority' => $priorityFilter !== '' ? $priorityFilter : null,
        'assignee' => $assigneeFilter !== '' ? $assigneeFilter : null,
    ];
    foreach ($overrides as $k => $v) $params[$k] = $v;
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    $qs = http_build_query($params);
    return $url('/t/' . $slug . '/todos' . ($qs ? '?' . $qs : ''));
};

$navItems = [
    ['inbox',     'Inbox',       'inbox',          $counts['pending'],  '#7c5cff'],
    ['today',     'Hoy',         'calendar-days',  $counts['today'],    '#0ea5e9'],
    ['upcoming',  'Próximas',    'calendar-clock', null,                '#10b981'],
    ['overdue',   'Vencidas',    'alert-triangle', $counts['overdue'],  '#ef4444'],
    ['important', 'Prioridad',   'flag',           $counts['important'],'#f59e0b'],
    ['delegated', 'Delegadas',   'send',           $counts['delegated'],'#8b5cf6'],
    ['completed', 'Completadas', 'check-circle-2', $counts['done'],     '#16a34a'],
    ['all',       'Todas',       'list-checks',    null,                '#6b7280'],
];
$viewMeta = [
    'inbox'     => ['Inbox',              'Tareas pendientes en tu bandeja',                  'inbox',           '#7c5cff'],
    'today'     => ['Hoy',                'Tareas que vencen hoy',                             'calendar-days',   '#0ea5e9'],
    'upcoming'  => ['Próximas',           'Tareas próximas con vencimiento programado',        'calendar-clock',  '#10b981'],
    'overdue'   => ['Vencidas',           'Tareas que pasaron su fecha límite',                'alert-triangle',  '#ef4444'],
    'important' => ['Prioridad alta',     'Tareas marcadas como urgentes o de alta prioridad', 'flag',            '#f59e0b'],
    'delegated' => ['Delegadas',          'Tareas que asignaste a otras personas',             'send',            '#8b5cf6'],
    'completed' => ['Completadas',        'Tareas que ya fueron resueltas',                    'check-circle-2',  '#16a34a'],
    'all'       => ['Todas las tareas',   'Vista global de tareas del workspace',              'list-checks',     '#6b7280'],
];
[$viewTitle, $viewSubtitle, $viewIcon, $viewColor] = $viewMeta[$viewMode] ?? $viewMeta['inbox'];
if ($currentListId) {
    $currentList = null;
    foreach ($lists as $l) if ((int)$l['id'] === $currentListId) { $currentList = $l; break; }
    if ($currentList) {
        $viewTitle = $currentList['name'];
        $viewSubtitle = 'Lista personalizada · ' . ($listCounts[$currentListId] ?? 0) . ' tareas';
        $viewColor = $currentList['color'] ?: '#7c5cff';
        $viewIcon = 'list';
    }
}
$maxLoad = 1;
foreach ($teamLoad as $member) $maxLoad = max($maxLoad, (int)$member['open_tasks']);
?>

<style>
.td-shell { display:grid; grid-template-columns: 260px minmax(0,1fr) 300px; gap:16px; align-items:flex-start; }
@media (max-width: 1280px) { .td-shell { grid-template-columns: 240px minmax(0,1fr); } .td-right { display:none; } }
@media (max-width: 900px) { .td-shell { grid-template-columns: 1fr; } .td-left { order:2; } .td-right { display:block; order:3; } }

.td-card { background:#fff; border:1px solid #ececef; border-radius:18px; }
.td-stat { padding:18px; border-radius:18px; background:#fff; border:1px solid #ececef; transition:transform .15s, box-shadow .15s; position:relative; overflow:hidden; }
.td-stat:hover { transform:translateY(-1px); box-shadow:0 8px 20px -10px rgba(22,21,27,.10); }
.td-stat-label { font-size:10.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#8e8e9a; }
.td-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:30px; color:#16151b; line-height:1.05; margin-top:6px; letter-spacing:-.025em; }
.td-stat-meta { font-size:11.5px; color:#8e8e9a; margin-top:4px; }
.td-stat-icon { position:absolute; top:14px; right:14px; width:34px; height:34px; border-radius:11px; display:grid; place-items:center; }

.td-nav { padding:6px; border-radius:18px; background:#fff; border:1px solid #ececef; }
.td-nav a { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:11px; font-size:13px; font-weight:500; color:#3d3d49; text-decoration:none; transition:background .12s; position:relative; }
.td-nav a:hover { background:#f6f7f9; }
.td-nav a.active { background:#f3f0ff; color:#16151b; font-weight:600; }
.td-nav a.active::before { content:''; position:absolute; left:0; top:50%; transform:translateY(-50%); width:3px; height:18px; border-radius:0 3px 3px 0; background:#7c5cff; }
.td-nav-icon { width:18px; display:grid; place-items:center; flex-shrink:0; }
.td-nav-count { margin-left:auto; font-size:11px; font-weight:600; color:#8e8e9a; padding:2px 7px; border-radius:999px; background:#f3f4f6; }
.td-nav a.active .td-nav-count { background:rgba(124,92,255,.12); color:#5a3aff; }

.td-section-title { font-size:10.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#8e8e9a; padding:8px 10px 6px; }

.td-hero { display:flex; align-items:flex-start; gap:14px; padding:22px 24px; border-radius:22px; background:linear-gradient(135deg,#fff,#fafbff); border:1px solid #ececef; position:relative; overflow:hidden; }
.td-hero-icon { width:54px; height:54px; border-radius:16px; display:grid; place-items:center; flex-shrink:0; }
.td-hero h1 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; letter-spacing:-.025em; color:#16151b; line-height:1.15; }
.td-hero p { font-size:13px; color:#6b6b78; margin-top:4px; }

.td-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:10px; border-radius:14px; background:#fff; border:1px solid #ececef; }
.td-toolbar .input { height:36px; border-radius:10px; }
.td-search { position:relative; flex:1; min-width:200px; }
.td-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8e8e9a; font-size:14px; }
.td-search input { padding-left:36px !important; }

.td-create { padding:18px; border-radius:18px; background:linear-gradient(180deg,#fff,#fafbff); border:1px solid #ececef; }
.td-create-input { width:100%; font-size:15px; font-weight:600; outline:none; border:0; background:transparent; padding:6px 0; color:#16151b; }
.td-create-input::placeholder { color:#b8b8c4; font-weight:500; }
.td-create-meta { display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:8px; margin-top:12px; }

.td-list { background:#fff; border:1px solid #ececef; border-radius:18px; overflow:hidden; }
.td-list-empty { padding:60px 20px; text-align:center; }
.td-list-empty-icon { width:60px; height:60px; border-radius:18px; background:#f3f4f6; display:grid; place-items:center; margin:0 auto 14px; }
.td-row { display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-bottom:1px solid #f3f4f6; transition:background .1s; }
.td-row:last-child { border-bottom:none; }
.td-row:hover { background:#fafbff; }
.td-row-checkbox { margin-top:2px; }
.td-row-check { width:20px; height:20px; border:2px solid; border-radius:50%; display:grid; place-items:center; transition:transform .15s, background .15s; cursor:pointer; }
.td-row-check:hover { transform:scale(1.1); }
.td-row-content { flex:1; min-width:0; }
.td-row-title { font-size:14px; font-weight:600; color:#16151b; line-height:1.4; word-break:break-word; }
.td-row-title.completed { text-decoration:line-through; color:#8e8e9a; }
.td-row-desc { font-size:12.5px; color:#6b6b78; margin-top:4px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.td-row-meta { display:flex; flex-wrap:wrap; align-items:center; gap:8px 14px; margin-top:8px; font-size:11.5px; color:#8e8e9a; }
.td-row-meta-item { display:inline-flex; align-items:center; gap:5px; }
.td-row-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; opacity:0; transition:opacity .15s; }
.td-row:hover .td-row-actions { opacity:1; }
@media (max-width: 900px) { .td-row-actions { opacity:1; } }
.td-action-btn { width:32px; height:32px; border-radius:9px; display:grid; place-items:center; color:#8e8e9a; background:transparent; border:none; cursor:pointer; transition:background .12s, color .12s; }
.td-action-btn:hover { background:#f3f4f6; color:#16151b; }
.td-action-btn.danger:hover { background:#fef2f2; color:#dc2626; }
.td-action-btn.success:hover { background:#eff6ff; color:#1d4ed8; }

.td-pri-pill { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
.td-pri-dot { width:6px; height:6px; border-radius:50%; }

.td-assignee { display:inline-flex; align-items:center; gap:7px; padding:3px 10px 3px 3px; border-radius:999px; background:#f6f7f9; border:1px solid #ececef; font-size:11.5px; font-weight:600; color:#3d3d49; flex-shrink:0; max-width:160px; }
.td-avatar { width:24px; height:24px; border-radius:50%; color:white; display:grid; place-items:center; font-size:10px; font-weight:700; flex-shrink:0; }

.td-tag { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:600; background:#f3f4f6; color:#6b6b78; }

.td-alerts-card { padding:18px; border-radius:20px; background:linear-gradient(135deg,#16151b,#2a2a33); color:white; position:relative; overflow:hidden; }
.td-alerts-card::before { content:''; position:absolute; width:160px; height:160px; border-radius:50%; background:radial-gradient(circle,rgba(124,92,255,.3),transparent 70%); top:-60px; right:-50px; }

.td-side-card { padding:16px; border-radius:18px; background:#fff; border:1px solid #ececef; }
.td-side-title { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.td-side-title h3 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:13.5px; color:#16151b; letter-spacing:-.01em; }

.td-mini-item { display:block; padding:10px 12px; border-radius:12px; border:1px solid #f3f4f6; background:#fafbfc; transition:background .12s, border-color .12s; text-decoration:none; color:inherit; margin-bottom:6px; }
.td-mini-item:hover { background:#fff; border-color:#e7e7eb; }
.td-mini-item:last-child { margin-bottom:0; }

.td-team-row { padding:10px 0; border-top:1px solid #f3f4f6; }
.td-team-row:first-child { border-top:none; padding-top:0; }
.td-team-bar { height:5px; border-radius:999px; background:#f3f4f6; overflow:hidden; margin-top:6px; }
.td-team-bar-fill { height:100%; border-radius:999px; transition:width .3s; }

.td-edit-form { background:#fafbff; border-top:1px solid #ececef; padding:18px; }
</style>

<div x-data="{ showListForm:false, createOpen:false, mobileNav:false }" class="space-y-4">

    <!-- HERO HEADER -->
    <div class="td-hero">
        <div class="td-hero-icon" style="background:<?= $e($viewColor) ?>15;color:<?= $e($viewColor) ?>;border:1px solid <?= $e($viewColor) ?>30">
            <i class="lucide lucide-<?= $e($viewIcon) ?> text-[22px]"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="inline-flex items-center gap-2 text-[10.5px] font-bold uppercase tracking-[0.16em] text-ink-400 mb-1">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $e($viewColor) ?>"></span>
                Productividad
            </div>
            <h1><?= $e($viewTitle) ?></h1>
            <p><?= $e($viewSubtitle) ?></p>
        </div>
        <div class="hidden sm:flex items-center gap-2 flex-shrink-0">
            <a href="<?= $build(['view'=>'today','list'=>null]) ?>" class="btn btn-outline btn-sm <?= $viewMode==='today'?'btn-dark':'' ?>"><i class="lucide lucide-calendar-days text-[13px]"></i> Hoy</a>
            <a href="<?= $build(['view'=>'overdue','list'=>null]) ?>" class="btn btn-outline btn-sm <?= $viewMode==='overdue'?'btn-dark':'' ?>"><i class="lucide lucide-alert-triangle text-[13px]"></i> Vencidas</a>
            <button type="button" @click="createOpen=!createOpen" class="btn btn-primary btn-sm"><i class="lucide lucide-plus text-[13px]"></i> Nueva tarea</button>
        </div>
    </div>

    <!-- MOBILE: action bar -->
    <div class="sm:hidden flex gap-2">
        <button @click="mobileNav=true" class="btn btn-outline btn-sm flex-1"><i class="lucide lucide-menu text-[13px]"></i> Vistas</button>
        <button type="button" @click="createOpen=!createOpen" class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-plus text-[13px]"></i> Nueva tarea</button>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="td-stat" style="border-top:3px solid #7c5cff">
            <div class="td-stat-label">Pendientes</div>
            <div class="td-stat-icon" style="background:#f3f0ff;color:#5a3aff"><i class="lucide lucide-circle-dot text-[16px]"></i></div>
            <div class="td-stat-value"><?= number_format((int)$counts['pending']) ?></div>
            <div class="td-stat-meta">Tareas activas en tu bandeja</div>
        </div>
        <div class="td-stat" style="border-top:3px solid #0ea5e9">
            <div class="td-stat-label">Hoy</div>
            <div class="td-stat-icon" style="background:#eff6ff;color:#0ea5e9"><i class="lucide lucide-calendar-days text-[16px]"></i></div>
            <div class="td-stat-value"><?= number_format((int)$counts['today']) ?></div>
            <div class="td-stat-meta">Vencen el día de hoy</div>
        </div>
        <div class="td-stat" style="border-top:3px solid #ef4444">
            <div class="td-stat-label">Vencidas</div>
            <div class="td-stat-icon" style="background:#fef2f2;color:#dc2626"><i class="lucide lucide-alert-triangle text-[16px]"></i></div>
            <div class="td-stat-value" style="color:<?= (int)$counts['overdue']>0?'#dc2626':'#16151b' ?>"><?= number_format((int)$counts['overdue']) ?></div>
            <div class="td-stat-meta"><?= (int)$counts['overdue']>0 ? 'Requieren atención' : 'Todo al día ✓' ?></div>
        </div>
        <div class="td-stat" style="border-top:3px solid #16a34a">
            <div class="td-stat-label">Progreso</div>
            <div class="td-stat-icon" style="background:#f0fdf4;color:#16a34a"><i class="lucide lucide-trending-up text-[16px]"></i></div>
            <div class="flex items-baseline gap-1.5 mt-1.5">
                <div class="td-stat-value" style="font-size:30px"><?= (int)$counts['progress'] ?></div>
                <div class="text-[14px] font-bold text-ink-400">%</div>
            </div>
            <div class="h-1.5 rounded-full bg-[#f3f4f6] overflow-hidden mt-2">
                <div class="h-full rounded-full transition-all" style="width:<?= (int)$counts['progress'] ?>%;background:linear-gradient(90deg,#16a34a,#22c55e)"></div>
            </div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="td-shell">

        <!-- LEFT NAV -->
        <aside class="td-left space-y-3" :class="{ 'fixed inset-0 z-50 p-4 bg-black/40 sm:relative sm:inset-auto sm:p-0 sm:bg-transparent': mobileNav }" x-cloak @click.self="mobileNav=false">
            <div class="space-y-3" :class="{ 'bg-white p-4 rounded-2xl max-w-xs ml-auto h-full overflow-y-auto sm:bg-transparent sm:p-0 sm:max-w-none sm:h-auto': mobileNav, '': !mobileNav }">
                <button @click="mobileNav=false" class="sm:hidden btn btn-outline btn-sm w-full mb-2"><i class="lucide lucide-x text-[13px]"></i> Cerrar</button>

                <div class="td-nav">
                    <div class="td-section-title">Vistas</div>
                    <?php foreach ($navItems as [$key, $label, $icon, $count, $color]): ?>
                        <a href="<?= $build(['view'=>$key,'list'=>null]) ?>" class="<?= $viewMode===$key && $currentListId===0 ? 'active' : '' ?>">
                            <span class="td-nav-icon" style="color:<?= $e($color) ?>"><i class="lucide lucide-<?= $icon ?> text-[15px]"></i></span>
                            <span class="truncate"><?= $label ?></span>
                            <?php if ($count !== null && (int)$count > 0): ?><span class="td-nav-count"><?= (int)$count ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="td-nav">
                    <div class="td-section-title flex items-center justify-between">
                        <span>Listas</span>
                        <button type="button" @click="showListForm=!showListForm" class="w-6 h-6 rounded-md hover:bg-[#f3f4f6] grid place-items-center text-ink-400 transition" title="Nueva lista">
                            <i class="lucide lucide-plus text-[12px]"></i>
                        </button>
                    </div>
                    <form x-show="showListForm" x-cloak method="POST" action="<?= $url('/t/' . $slug . '/todos/lists') ?>" class="m-2 p-3 rounded-xl space-y-2 bg-[#fafbfc] border border-[#ececef]">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input name="name" required placeholder="Nombre de la lista" class="input" style="height:34px;border-radius:8px;font-size:12.5px">
                        <div class="flex gap-1.5 flex-wrap">
                            <?php foreach (['#7c5cff','#0ea5e9','#10b981','#f59e0b','#ef4444','#ec4899','#16151b'] as $c): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="color" value="<?= $c ?>" class="sr-only peer" <?= $c==='#7c5cff'?'checked':'' ?>>
                                    <span class="block w-6 h-6 rounded-full border-2 peer-checked:border-ink-900 border-transparent transition" style="background:<?= $c ?>"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-primary btn-xs w-full"><i class="lucide lucide-check text-[11px]"></i> Crear lista</button>
                    </form>
                    <?php if (empty($lists)): ?>
                        <div class="text-[12px] text-ink-400 px-3 py-4 text-center">Sin listas todavía</div>
                    <?php else: ?>
                        <?php foreach ($lists as $l): ?>
                            <div class="group flex items-center gap-1">
                                <a href="<?= $build(['view'=>'inbox','list'=>(int)$l['id']]) ?>" class="flex-1 <?= $currentListId===(int)$l['id']?'active':'' ?>">
                                    <span class="td-nav-icon"><span class="w-2.5 h-2.5 rounded-full" style="background:<?= $e($l['color']) ?>"></span></span>
                                    <span class="truncate"><?= $e($l['name']) ?></span>
                                    <?php if (!empty($listCounts[(int)$l['id']])): ?><span class="td-nav-count"><?= (int)$listCounts[(int)$l['id']] ?></span><?php endif; ?>
                                </a>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/todos/lists/' . $l['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar la lista?')" class="opacity-0 group-hover:opacity-100 transition">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="w-7 h-7 grid place-items-center rounded-lg text-ink-400 hover:bg-rose-50 hover:text-rose-600 transition"><i class="lucide lucide-x text-[12px]"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="td-alerts-card">
                    <div class="relative">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em]" style="color:rgba(255,255,255,.55)">Recordatorios</div>
                        <div class="font-display text-[28px] font-extrabold mt-1" style="letter-spacing:-.025em"><?= (int)$counts['alerts'] ?></div>
                        <div class="text-[11.5px] mt-0.5" style="color:rgba(255,255,255,.55)">Tareas con alerta activa</div>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/todos/reminders') ?>" class="mt-3.5">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="btn w-full" style="background:rgba(255,255,255,.95);color:#16151b;height:36px"><i class="lucide lucide-send text-[12px]"></i> Enviar por Resend</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="space-y-3 min-w-0">

            <!-- CREATE TASK -->
            <form x-show="createOpen" x-cloak method="POST" action="<?= $url('/t/' . $slug . '/todos') ?>" class="td-create" x-transition.opacity>
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div class="flex items-start gap-3">
                    <div class="w-5 h-5 rounded-full border-2 border-ink-300 shrink-0 mt-1.5"></div>
                    <div class="flex-1 min-w-0">
                        <input name="title" required placeholder="¿Qué tarea quieres añadir?" class="td-create-input" autocomplete="off">
                        <textarea name="description" rows="2" placeholder="Notas, contexto o checklist (opcional)" class="input mt-2" style="min-height:60px;border-radius:10px;font-size:13px"></textarea>
                        <div class="td-create-meta">
                            <select name="assigned_to_id" class="input" title="Responsable">
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id']===(int)$auth->userId()?'selected':'' ?>><?= $e($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="list_id" class="input" title="Lista">
                                <option value="">Inbox</option>
                                <?php foreach ($lists as $l): ?>
                                    <option value="<?= (int)$l['id'] ?>" <?= $currentListId===(int)$l['id']?'selected':'' ?>><?= $e($l['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="priority" class="input" title="Prioridad">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                            <input name="estimate_minutes" type="number" min="0" step="5" placeholder="Min" class="input" title="Minutos estimados">
                        </div>
                        <div class="td-create-meta">
                            <input name="due_at" type="datetime-local" class="input" title="Vencimiento">
                            <input name="reminder_at" type="datetime-local" class="input" title="Recordatorio">
                            <input name="labels" placeholder="Etiquetas (coma)" class="input" title="Etiquetas">
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-[#ececef] mt-3">
                            <label class="inline-flex items-center gap-2 text-[12px] text-ink-500 font-medium cursor-pointer">
                                <input type="checkbox" name="email_notifications" value="1" checked class="rounded border-[#d7d7df] w-3.5 h-3.5">
                                Notificar por email al responsable
                            </label>
                            <div class="flex gap-2">
                                <button type="button" @click="createOpen=false" class="btn btn-outline btn-sm">Cancelar</button>
                                <button class="btn btn-primary btn-sm"><i class="lucide lucide-plus text-[13px]"></i> Crear tarea</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- TOOLBAR / FILTERS -->
            <form method="GET" action="<?= $url('/t/' . $slug . '/todos') ?>" class="td-toolbar">
                <input type="hidden" name="view" value="<?= $e($viewMode) ?>">
                <?php if ($currentListId): ?><input type="hidden" name="list" value="<?= $currentListId ?>"><?php endif; ?>
                <div class="td-search">
                    <i class="lucide lucide-search"></i>
                    <input name="q" value="<?= $e($search) ?>" placeholder="Buscar tareas..." class="input">
                </div>
                <select name="priority" class="input" style="width:140px">
                    <option value="">Prioridad</option>
                    <?php foreach ($priLabel as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $priorityFilter===$key?'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="assignee" class="input" style="width:160px">
                    <option value="">Responsable</option>
                    <option value="me" <?= $assigneeFilter==='me'?'selected':'' ?>>Mis tareas</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= $assigneeFilter===(string)$u['id']?'selected':'' ?>><?= $e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-dark btn-sm"><i class="lucide lucide-sliders-horizontal text-[12px]"></i> Filtrar</button>
                <a href="<?= $build(['q'=>null,'priority'=>null,'assignee'=>null]) ?>" class="btn btn-outline btn-sm" title="Limpiar"><i class="lucide lucide-rotate-ccw text-[12px]"></i></a>
            </form>

            <!-- LIST -->
            <div class="td-list">
                <?php if (empty($todos)): ?>
                    <div class="td-list-empty">
                        <div class="td-list-empty-icon"><i class="lucide lucide-<?= $viewMode === 'completed' ? 'check-check' : 'inbox' ?> text-[24px] text-ink-400"></i></div>
                        <div class="font-display font-bold text-[15px] text-ink-700"><?= $viewMode === 'completed' ? 'Aún no hay tareas completadas' : 'Sin tareas en esta vista' ?></div>
                        <p class="text-[13px] text-ink-400 mt-1 max-w-sm mx-auto"><?= $viewMode === 'completed' ? 'Cuando completes tareas aparecerán aquí.' : 'Crea una nueva tarea o revisa los filtros activos.' ?></p>
                        <?php if ($viewMode !== 'completed'): ?>
                            <button type="button" @click="createOpen=true" class="btn btn-primary btn-sm mt-4"><i class="lucide lucide-plus text-[13px]"></i> Nueva tarea</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($todos as $td):
                    $color = $priColor[$td['priority']] ?? '#7c5cff';
                    $priBgC = $priBg[$td['priority']] ?? '#f5f3ff';
                    $dueTs = !empty($td['due_at']) ? strtotime((string)$td['due_at']) : null;
                    $overdue = $dueTs && $dueTs < time() && !(int)$td['completed'];
                    $today = $dueTs && date('Y-m-d', $dueTs) === date('Y-m-d');
                    $labels = $labelsOf($td['labels'] ?? '');
                    $assigneeName = $td['assignee_name'] ?: 'Sin responsable';
                    $avColor = $avatarColor($assigneeName);
                ?>
                    <div x-data="{ edit:false }">
                        <div x-show="!edit" class="td-row">
                            <form method="POST" action="<?= $url('/t/' . $slug . '/todos/' . $td['id'] . '/toggle') ?>" class="td-row-checkbox">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="td-row-check" style="border-color:<?= $color ?>;<?= (int)$td['completed'] ? "background:$color" : '' ?>" title="Completar">
                                    <?php if ((int)$td['completed']): ?><i class="lucide lucide-check text-white text-[11px]"></i><?php endif; ?>
                                </button>
                            </form>

                            <div class="td-row-content">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="td-row-title <?= (int)$td['completed']?'completed':'' ?>"><?= $e($td['title']) ?></div>
                                    <?php if ($overdue): ?>
                                        <span class="td-pri-pill" style="background:#fef2f2;color:#dc2626"><span class="td-pri-dot" style="background:#dc2626"></span>Vencida</span>
                                    <?php elseif ($today): ?>
                                        <span class="td-pri-pill" style="background:#fffbeb;color:#b45309"><span class="td-pri-dot" style="background:#f59e0b"></span>Hoy</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($td['description'])): ?>
                                    <div class="td-row-desc"><?= $e($td['description']) ?></div>
                                <?php endif; ?>
                                <div class="td-row-meta">
                                    <span class="td-pri-pill" style="background:<?= $priBgC ?>;color:<?= $color ?>">
                                        <span class="td-pri-dot" style="background:<?= $color ?>"></span><?= $priLabel[$td['priority']] ?? 'Media' ?>
                                    </span>
                                    <?php if (!empty($td['list_name'])): ?>
                                        <span class="td-row-meta-item"><span class="w-2 h-2 rounded-full" style="background:<?= $e($td['list_color'] ?: '#7c5cff') ?>"></span><?= $e($td['list_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($td['due_at'])): ?>
                                        <span class="td-row-meta-item <?= $overdue?'text-rose-600 font-semibold':'' ?>"><i class="lucide lucide-calendar text-[11px]"></i><?= $dt($td['due_at']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($td['reminder_at'])): ?>
                                        <span class="td-row-meta-item"><i class="lucide lucide-bell text-[11px]"></i><?= $dt($td['reminder_at']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($td['estimate_minutes'])): ?>
                                        <span class="td-row-meta-item"><i class="lucide lucide-timer text-[11px]"></i><?= (int)$td['estimate_minutes'] ?>m</span>
                                    <?php endif; ?>
                                    <?php foreach ($labels as $label): ?>
                                        <span class="td-tag">#<?= $e($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="td-assignee" title="<?= $e($assigneeName) ?>">
                                <div class="td-avatar" style="background:<?= $avColor ?>"><?= $e($initials($assigneeName)) ?></div>
                                <span class="truncate"><?= $e(explode(' ', $assigneeName)[0]) ?></span>
                            </div>

                            <div class="td-row-actions">
                                <button type="button" @click="edit=true; $nextTick(() => window.renderIcons && window.renderIcons())" class="td-action-btn" title="Editar"><i class="lucide lucide-pencil text-[13px]"></i></button>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/todos/' . $td['id'] . '/remind') ?>">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="td-action-btn success" title="Enviar recordatorio"><i class="lucide lucide-mail text-[13px]"></i></button>
                                </form>
                                <form method="POST" action="<?= $url('/t/' . $slug . '/todos/' . $td['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar tarea?')">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button class="td-action-btn danger" title="Eliminar"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                                </form>
                            </div>
                        </div>

                        <form x-show="edit" x-cloak method="POST" action="<?= $url('/t/' . $slug . '/todos/' . $td['id']) ?>" class="td-edit-form">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <div class="space-y-3">
                                <input name="title" required value="<?= $e($td['title']) ?>" class="input font-semibold" style="height:38px;border-radius:10px;font-size:14px">
                                <textarea name="description" rows="3" class="input" style="border-radius:10px;font-size:13px"><?= $e($td['description']) ?></textarea>
                                <div class="td-create-meta">
                                    <select name="assigned_to_id" class="input">
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= (int)$u['id'] ?>" <?= (int)($td['assigned_to_id'] ?: $td['user_id'])===(int)$u['id']?'selected':'' ?>><?= $e($u['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="list_id" class="input">
                                        <option value="">Inbox</option>
                                        <?php foreach ($lists as $l): ?>
                                            <option value="<?= (int)$l['id'] ?>" <?= (int)($td['list_id'] ?? 0)===(int)$l['id']?'selected':'' ?>><?= $e($l['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="priority" class="input">
                                        <?php foreach ($priLabel as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= $td['priority']===$key?'selected':'' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input name="estimate_minutes" type="number" min="0" step="5" value="<?= $e($td['estimate_minutes']) ?>" placeholder="Min" class="input">
                                </div>
                                <div class="td-create-meta">
                                    <input name="due_at" type="datetime-local" value="<?= $e($inputDt($td['due_at'])) ?>" class="input">
                                    <input name="reminder_at" type="datetime-local" value="<?= $e($inputDt($td['reminder_at'])) ?>" class="input">
                                    <input name="labels" value="<?= $e($td['labels']) ?>" placeholder="Etiquetas" class="input">
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-[#ececef]">
                                    <label class="inline-flex items-center gap-2 text-[12px] text-ink-500 font-medium cursor-pointer">
                                        <input type="checkbox" name="email_notifications" value="1" <?= (int)($td['email_notifications'] ?? 1) ? 'checked' : '' ?> class="rounded border-[#d7d7df] w-3.5 h-3.5">
                                        Notificar cambios
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="edit=false" class="btn btn-outline btn-sm">Cancelar</button>
                                        <button class="btn btn-primary btn-sm"><i class="lucide lucide-save text-[12px]"></i> Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- RIGHT SIDEBAR -->
        <aside class="td-right space-y-3">
            <div class="td-side-card">
                <div class="td-side-title">
                    <h3>Próximos vencimientos</h3>
                    <i class="lucide lucide-calendar-clock text-[14px] text-ink-400"></i>
                </div>
                <?php if (empty($upcoming)): ?>
                    <div class="text-[12px] text-ink-400 py-3 text-center">Sin vencimientos cercanos</div>
                <?php else: ?>
                    <?php foreach ($upcoming as $item):
                        $dueTs = !empty($item['due_at']) ? strtotime((string)$item['due_at']) : null;
                        $isLate = $dueTs && $dueTs < time();
                    ?>
                        <a href="<?= $build(['view'=>$isLate?'overdue':'upcoming']) ?>" class="td-mini-item">
                            <div class="text-[12.5px] font-semibold text-ink-700 line-clamp-1"><?= $e($item['title']) ?></div>
                            <div class="mt-1 inline-flex items-center gap-1.5 text-[11px] <?= $isLate?'text-rose-600 font-semibold':'text-ink-400' ?>">
                                <i class="lucide lucide-calendar text-[11px]"></i><?= $dt($item['due_at']) ?>
                                <?php if ($isLate): ?><span class="td-pri-pill ml-1" style="background:#fef2f2;color:#dc2626;font-size:9px">Vencida</span><?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="td-side-card">
                <div class="td-side-title">
                    <h3>Carga del equipo</h3>
                    <i class="lucide lucide-users text-[14px] text-ink-400"></i>
                </div>
                <?php if (empty($teamLoad)): ?>
                    <div class="text-[12px] text-ink-400 py-3 text-center">Sin usuarios activos</div>
                <?php else: ?>
                    <?php foreach ($teamLoad as $member):
                        $tasks = (int)$member['open_tasks'];
                        $pct = (int)round(($tasks / $maxLoad) * 100);
                        $col = $avatarColor($member['name']);
                        $heat = $tasks >= $maxLoad ? '#ef4444' : ($tasks >= $maxLoad * 0.6 ? '#f59e0b' : '#10b981');
                    ?>
                        <div class="td-team-row">
                            <div class="flex items-center gap-2.5">
                                <div class="td-avatar" style="background:<?= $col ?>;width:28px;height:28px;font-size:11px"><?= $e($initials($member['name'])) ?></div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-[12.5px] font-semibold text-ink-700 truncate"><?= $e($member['name']) ?></div>
                                    <div class="text-[10.5px] text-ink-400"><?= $tasks ?> tarea<?= $tasks!==1?'s':'' ?> pendiente<?= $tasks!==1?'s':'' ?></div>
                                </div>
                                <a href="<?= $build(['assignee'=>(int)$member['id'],'view'=>'inbox','list'=>null]) ?>" class="td-action-btn" style="width:28px;height:28px"><i class="lucide lucide-arrow-right text-[12px]"></i></a>
                            </div>
                            <div class="td-team-bar">
                                <div class="td-team-bar-fill" style="width:<?= max(8, $pct) ?>%;background:<?= $heat ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
