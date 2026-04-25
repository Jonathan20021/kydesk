<div class="max-w-[640px] mx-auto px-6 py-16">
    <div class="text-center mb-7">
        <?php if (empty($error)): ?>
            <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white grid place-items-center mx-auto mb-5"><i class="lucide lucide-check text-[24px]"></i></div>
            <h1 class="font-display font-extrabold text-[34px] tracking-[-0.025em]">Instalación completa</h1>
        <?php else: ?>
            <div class="w-14 h-14 rounded-2xl bg-red-500 text-white grid place-items-center mx-auto mb-5"><i class="lucide lucide-x text-[24px]"></i></div>
            <h1 class="font-display font-extrabold text-[34px] tracking-[-0.025em]">Error en instalación</h1>
        <?php endif; ?>
    </div>
    <div class="card card-pad mb-5" style="background:#16151b;color:white;border:none;font-family:'Geist Mono',monospace;font-size:12px">
        <div class="space-y-1">
            <?php foreach ($out as $line): ?>
                <div class="<?= str_starts_with($line,'✓')?'text-emerald-400':(str_starts_with($line,'✗')?'text-red-400':'opacity-80') ?>"><?= $e($line) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (empty($error)): ?>
        <div class="card card-pad mb-4">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] mb-4 text-ink-400">Credenciales demo</div>
            <div class="space-y-2.5 text-[13px]">
                <div class="flex items-center gap-2"><i class="lucide lucide-crown text-[14px] text-amber-500"></i> <b>Owner:</b> <span class="kbd">admin@demo.com</span> <span class="kbd">admin123</span></div>
                <div class="flex items-center gap-2"><i class="lucide lucide-user-check text-[14px] text-emerald-600"></i> <b>Supervisor:</b> <span class="kbd">supervisor@demo.com</span> <span class="kbd">tech123</span></div>
                <div class="flex items-center gap-2"><i class="lucide lucide-wrench text-[14px] text-brand-500"></i> <b>Técnico:</b> <span class="kbd">tecnico@demo.com</span> <span class="kbd">tech123</span></div>
            </div>
        </div>
        <a href="<?= $url('/auth/login') ?>" class="btn btn-primary w-full" style="height:48px">Iniciar sesión <i class="lucide lucide-arrow-right"></i></a>
    <?php else: ?>
        <a href="<?= $url('/install') ?>" class="btn btn-primary w-full" style="height:48px">Reintentar</a>
    <?php endif; ?>
</div>
