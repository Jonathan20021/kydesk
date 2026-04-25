<div class="min-h-screen grid place-items-center p-8">
    <div class="text-center max-w-md">
        <div class="text-gradient-purple font-display font-extrabold text-[120px] leading-none tracking-[-0.05em]">500</div>
        <div class="mt-4 text-[15px] text-ink-500"><?= $e($message ?? 'Algo salió mal.') ?></div>
        <a href="<?= $url('/') ?>" class="btn btn-primary mt-7">Inicio</a>
    </div>
</div>
