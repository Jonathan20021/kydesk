<?php
use App\Controllers\MeetingController;
$slug = $tenant->slug;
$ts = strtotime($start);
$year = (int)date('Y', $ts);
$mo   = (int)date('n', $ts);
$daysInMonth = (int)date('t', $ts);
$firstWd = (int)date('w', $ts); // 0=Dom

$prev = date('Y-m', strtotime($start . ' -1 month'));
$next = date('Y-m', strtotime($start . ' +1 month'));
$today = date('Y-m-d');

// agrupar reuniones por día
$byDay = [];
foreach ($meetings as $m) {
    $d = date('Y-m-d', strtotime($m['scheduled_at']));
    $byDay[$d][] = $m;
}
$mesesEs = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasEs = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
            <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
            <span>Calendario</span>
        </div>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]"><?= $mesesEs[$mo] ?> <?= $year ?></h1>
        <p class="text-[13px] text-ink-400"><?= count($meetings) ?> reunión<?= count($meetings) === 1 ? '' : 'es' ?> en este mes</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= $url('/t/' . $slug . '/meetings/calendar?month=' . $prev) ?>" class="btn btn-outline btn-sm"><i class="lucide lucide-chevron-left"></i></a>
        <a href="<?= $url('/t/' . $slug . '/meetings/calendar') ?>" class="btn btn-soft btn-sm">Hoy</a>
        <a href="<?= $url('/t/' . $slug . '/meetings/calendar?month=' . $next) ?>" class="btn btn-outline btn-sm"><i class="lucide lucide-chevron-right"></i></a>
        <a href="<?= $url('/t/' . $slug . '/meetings/list') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-list"></i> Lista</a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="grid grid-cols-7 text-center border-b" style="border-color:var(--border);background:var(--bg)">
        <?php foreach ($diasEs as $i => $d): ?>
            <div class="py-2.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $d ?></div>
        <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-7" style="background:var(--border);gap:1px">
        <?php for ($i = 0; $i < $firstWd; $i++): ?>
            <div style="background:#fafafb;min-height:120px"></div>
        <?php endfor; ?>
        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $date = sprintf('%04d-%02d-%02d', $year, $mo, $d);
            $items = $byDay[$date] ?? [];
            $isToday = $date === $today;
        ?>
            <div style="background:white;min-height:120px;padding:8px;<?= $isToday?'background:linear-gradient(180deg,#f3f0ff,#ffffff)':'' ?>">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[12.5px] font-bold <?= $isToday?'text-brand-700':'text-ink-700' ?>"><?= $d ?></span>
                    <?php if (count($items) > 0): ?>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:<?= $isToday?'#7c5cff':'var(--bg)' ?>;color:<?= $isToday?'white':'var(--ink-500)' ?>"><?= count($items) ?></span>
                    <?php endif; ?>
                </div>
                <?php foreach (array_slice($items, 0, 3) as $m): ?>
                    <a href="<?= $url('/t/' . $slug . '/meetings/' . $m['id']) ?>" class="block mb-1 px-1.5 py-0.5 rounded text-[11px] truncate transition" style="background:<?= $e($m['type_color'] ?? '#7c5cff') ?>15;color:<?= $e($m['type_color'] ?? '#7c5cff') ?>;border-left:2px solid <?= $e($m['type_color'] ?? '#7c5cff') ?>">
                        <span class="font-mono text-[9px] opacity-70"><?= date('H:i', strtotime($m['scheduled_at'])) ?></span>
                        <span class="font-semibold"><?= $e($m['customer_name']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (count($items) > 3): ?>
                    <div class="text-[10px] text-ink-400 px-1.5">+<?= count($items) - 3 ?> más</div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
        <?php
            $totalCells = $firstWd + $daysInMonth;
            $remainder = (7 - ($totalCells % 7)) % 7;
            for ($i = 0; $i < $remainder; $i++): ?>
            <div style="background:#fafafb;min-height:120px"></div>
        <?php endfor; ?>
    </div>
</div>
