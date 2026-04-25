<div class="min-h-[60vh] grid place-items-center p-8">
    <div class="text-center max-w-md">
        <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 grid place-items-center mx-auto mb-4"><i class="lucide lucide-shield-alert text-[24px]"></i></div>
        <h1 class="font-display font-bold text-[24px] tracking-[-0.025em]">Acceso denegado</h1>
        <p class="mt-2 text-sm text-ink-500"><?= $e($message ?? 'No tienes permisos.') ?></p>
        <a href="javascript:history.back()" class="btn btn-outline mt-5"><i class="lucide lucide-arrow-left"></i> Volver</a>
    </div>
</div>
