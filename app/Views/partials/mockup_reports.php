<div class="p-5 bg-muted/30 h-full">
    <div class="flex items-end justify-between mb-3">
        <div>
            <div class="text-[15px] font-semibold font-display">Reportes</div>
            <div class="text-[10px] text-muted-foreground">Últimos 30 días</div>
        </div>
        <span class="badge badge-outline">CSV <i class="lucide lucide-download text-[11px]"></i></span>
    </div>
    <div class="grid grid-cols-3 gap-2">
        <div class="card p-3 col-span-2">
            <div class="text-[10px] text-muted-foreground">Creados vs resueltos</div>
            <svg viewBox="0 0 320 70" class="mt-1 w-full" preserveAspectRatio="none">
                <path d="M0,55 C30,45 60,40 90,35 C120,30 150,23 180,26 C210,29 240,18 280,15 C300,14 320,10 320,10" stroke="hsl(var(--foreground))" stroke-width="1.6" fill="none"/>
                <path d="M0,65 C30,60 60,52 90,50 C120,47 150,42 180,38 C210,34 240,28 280,26 C300,25 320,22 320,22" stroke="hsl(var(--chart-2))" stroke-width="1.6" fill="none"/>
            </svg>
        </div>
        <div class="card p-3">
            <div class="text-[10px] text-muted-foreground">Prioridad</div>
            <svg viewBox="0 0 100 100" class="w-full mt-1">
                <circle cx="50" cy="50" r="34" stroke="hsl(var(--muted))" stroke-width="12" fill="none"/>
                <circle cx="50" cy="50" r="34" stroke="hsl(var(--destructive))" stroke-width="12" stroke-dasharray="55 215" stroke-linecap="round" transform="rotate(-90 50 50)" fill="none"/>
                <circle cx="50" cy="50" r="34" stroke="hsl(var(--prio-high))" stroke-width="12" stroke-dasharray="65 215" stroke-dashoffset="-55" transform="rotate(-90 50 50)" fill="none"/>
                <circle cx="50" cy="50" r="34" stroke="hsl(var(--prio-medium))" stroke-width="12" stroke-dasharray="48 215" stroke-dashoffset="-120" transform="rotate(-90 50 50)" fill="none"/>
            </svg>
        </div>
    </div>
    <div class="mt-2.5 card p-3">
        <div class="text-[10px] text-muted-foreground mb-2">Desempeño de técnicos</div>
        <?php foreach ([['María T.',92],['Juan S.',78],['Ana G.',64]] as [$n,$p]): ?>
            <div class="flex items-center gap-2 py-1">
                <div class="avatar avatar-sm bg-muted" style="width:18px;height:18px;font-size:8.5px;"><?= $n[0] ?></div>
                <span class="text-[11px] w-20"><?= $n ?></span>
                <div class="flex-1 progress" style="height:5px"><div class="progress-bar" style="width: <?= $p ?>%; background: hsl(var(--chart-2))"></div></div>
                <span class="text-[10px] font-mono text-muted-foreground"><?= $p ?>%</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
