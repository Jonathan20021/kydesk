<div class="min-h-screen grid place-items-center p-8">
    <div class="text-center max-w-md">
        <div class="text-gradient-purple font-display font-extrabold text-[120px] leading-none tracking-[-0.05em]">404</div>
        <div class="mt-4 text-[15px] text-ink-500"><?= $e($message ?? 'Página no encontrada.') ?></div>
        <a href="<?= $url('/') ?>" class="btn btn-primary mt-7"><i class="lucide lucide-arrow-left"></i> Inicio</a>
    </div>
</div>
