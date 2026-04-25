<div class="p-5 bg-muted/30 h-full">
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-[10.5px] text-muted-foreground">Hola, Ana</div>
            <div class="text-[15px] font-semibold font-display">Resumen de hoy</div>
        </div>
        <div class="badge badge-outline"><span class="pulse"></span> En vivo</div>
    </div>
    <div class="grid grid-cols-4 gap-2">
        <?php foreach ([['Abiertos','24','hsl(var(--chart-1))'],['En progreso','12','hsl(var(--chart-3))'],['Resueltos','189','hsl(var(--chart-2))'],['SLA','97%','hsl(var(--foreground))']] as [$l,$v,$c]): ?>
            <div class="card p-3">
                <div class="text-[10px] text-muted-foreground"><?= $l ?></div>
                <div class="mt-1 text-[17px] font-semibold font-display"><?= $v ?></div>
                <div class="mt-1.5 h-1 rounded-full bg-muted overflow-hidden"><div class="h-full" style="width: <?= rand(40,90) ?>%; background: <?= $c ?>"></div></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-3 card p-4">
        <div class="text-[10.5px] text-muted-foreground mb-2">Tickets últimos 14 días</div>
        <svg viewBox="0 0 400 90" class="w-full" preserveAspectRatio="none">
            <defs><linearGradient id="dg" x1="0" x2="0" y1="0" y2="1"><stop stop-color="hsl(var(--foreground))" stop-opacity=".2"/><stop offset="1" stop-color="hsl(var(--foreground))" stop-opacity="0"/></linearGradient></defs>
            <path d="M0,70 C40,60 60,50 90,45 C120,40 150,22 180,28 C210,34 240,15 280,22 C320,29 350,10 400,8 L400,90 L0,90 Z" fill="url(#dg)"/>
            <path d="M0,70 C40,60 60,50 90,45 C120,40 150,22 180,28 C210,34 240,15 280,22 C320,29 350,10 400,8" stroke="hsl(var(--foreground))" stroke-width="1.6" fill="none"/>
        </svg>
    </div>
</div>
