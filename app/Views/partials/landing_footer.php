<footer class="border-t border-[#ececef] bg-white mt-20">
    <div class="max-w-[1240px] mx-auto px-4 sm:px-6 py-10 sm:py-14 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-8 sm:gap-10">
        <div class="col-span-2 sm:col-span-3 lg:col-span-2">
            <a href="<?= $url('/') ?>" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[15px]">K</div>
                <span class="font-display font-bold text-[18px]">Kydesk</span>
            </a>
            <p class="mt-4 text-[13.5px] text-ink-500 max-w-sm leading-relaxed">El helpdesk que tu equipo se merece. Tickets, SLAs, KB, automatizaciones y más.</p>
            <div class="flex items-center gap-2 mt-5">
                <span class="pulse"></span>
                <span class="text-[12px] text-ink-500">Todos los sistemas operativos</span>
            </div>
            <div class="mt-5 space-y-1.5 text-[12.5px] text-ink-500">
                <a href="mailto:jonathansandoval@kyrosrd.com" class="flex items-center gap-2 hover:text-ink-900 transition"><i class="lucide lucide-mail text-[13px] text-brand-600"></i> jonathansandoval@kyrosrd.com</a>
                <a href="https://wa.me/18495024061" target="_blank" rel="noopener" class="flex items-center gap-2 hover:text-ink-900 transition"><i class="lucide lucide-message-circle text-[13px] text-emerald-600"></i> +1 849 502 4061 · WhatsApp</a>
                <div class="flex items-center gap-2"><i class="lucide lucide-globe text-[13px] text-brand-600"></i> República Dominicana · 100% remoto</div>
            </div>
        </div>
        <?php
        $cols = [
            'Producto' => [['Funcionalidades','/features'],['Precios','/pricing'],['Portal demo','/portal/demo']],
            'Empresa'  => [['Contacto','/contact'],['Clientes','/clients'],['Carreras','/careers']],
            'Recursos' => [['Documentación','/docs'],['Estado','/status'],['Changelog','/changelog']],
        ];
        foreach ($cols as $heading => $items): ?>
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 mb-4"><?= $heading ?></h4>
                <ul class="space-y-2.5 text-[13px]">
                    <?php foreach ($items as [$l,$h]): ?>
                        <li><a href="<?= $h[0] === '/' ? $url($h) : $h ?>" class="text-ink-500 hover:text-ink-900 transition"><?= $l ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="border-t border-[#ececef]">
        <div class="max-w-[1240px] mx-auto px-4 sm:px-6 py-3 sm:py-0 sm:h-12 flex flex-col sm:flex-row items-center sm:justify-between gap-2 text-[11.5px] text-ink-400">
            <div>© <?= date('Y') ?> Kydesk Helpdesk</div>
            <div class="flex items-center gap-4">
                <a href="<?= $url('/privacy') ?>" class="hover:text-ink-900">Privacidad</a>
                <a href="<?= $url('/terms') ?>" class="hover:text-ink-900">Términos</a>
            </div>
        </div>
    </div>
</footer>
