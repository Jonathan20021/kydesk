<div class="max-w-[640px] mx-auto px-6 py-16">
    <div class="text-center mb-9">
        <div class="w-14 h-14 rounded-2xl bg-brand-500 text-white grid place-items-center mx-auto mb-5" style="box-shadow:var(--shadow-purple)"><i class="lucide lucide-rocket text-[22px]"></i></div>
        <h1 class="font-display font-extrabold text-[34px] tracking-[-0.025em]">Instalar Kydesk</h1>
        <p class="mt-3 text-[14px] text-ink-400">Crearemos la base de datos, tablas y una organización demo.</p>
    </div>

    <?php if (!empty($status['connected']) && !empty($status['seeded'])): ?>
        <div class="card card-pad mb-4" style="background:#fef3c7;border-color:#fde68a">
            <div class="flex items-start gap-3">
                <i class="lucide lucide-alert-triangle text-amber-700 text-lg"></i>
                <div>
                    <div class="font-display font-bold text-[14px]">Ya existe instalación</div>
                    <p class="mt-1 text-[12.5px] text-ink-500">Continuar reinstalará desde cero.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($status['connected'])): ?>
        <div class="card card-pad mb-4" style="background:#fee2e2;border-color:#fecaca">
            <div class="font-display font-bold text-[13.5px]">MySQL no conectado</div>
            <p class="mt-1 text-[12px] text-ink-500">Es normal antes de instalar. Asegúrate de que MySQL esté corriendo.</p>
            <?php if (isset($status['error'])): ?><pre class="mt-2 p-2 rounded-lg bg-white text-[11px] font-mono overflow-auto"><?= $e($status['error']) ?></pre><?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $url('/install') ?>" class="card card-pad">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-4 text-ink-400">Se creará</div>
        <ul class="space-y-2 text-[13.5px] mb-6">
            <?php foreach ([['database','Base de datos'],['layers','22 tablas con relaciones'],['key','~50 permisos y 5 roles'],['building-2','Tenant demo /t/demo'],['users','5 usuarios'],['ticket','12 tickets con comentarios'],['book-open','5 artículos KB'],['workflow','4 automatizaciones'],['gauge','4 SLAs']] as [$ic,$it]): ?>
                <li class="flex items-start gap-2.5"><i class="lucide lucide-<?= $ic ?> text-[14px] text-ink-400 mt-0.5"></i> <?= $it ?></li>
            <?php endforeach; ?>
        </ul>
        <button class="btn btn-primary w-full" style="height:48px"><i class="lucide lucide-zap"></i> Instalar ahora</button>
    </form>

    <p class="text-center text-[11.5px] mt-5 text-ink-400">Asegúrate de tener MySQL corriendo en XAMPP.</p>
</div>
