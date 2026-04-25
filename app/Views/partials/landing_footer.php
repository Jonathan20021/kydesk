<footer class="border-t border-[#ececef] bg-white mt-20">
    <div class="max-w-[1240px] mx-auto px-6 py-14 grid grid-cols-1 lg:grid-cols-5 gap-10">
        <div class="lg:col-span-2">
            <a href="<?= $url('/') ?>" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[15px]">K</div>
                <span class="font-display font-bold text-[18px]">Kydesk</span>
            </a>
            <p class="mt-4 text-[13.5px] text-ink-500 max-w-sm leading-relaxed">El helpdesk que tu equipo se merece. Tickets, SLAs, KB, automatizaciones y más.</p>
            <div class="flex items-center gap-2 mt-5">
                <span class="pulse"></span>
                <span class="text-[12px] text-ink-500">Todos los sistemas operativos</span>
            </div>
        </div>
        <?php
        $cols = [
            'Producto' => [['Funcionalidades','/features'],['Precios','/pricing'],['Portal demo','/portal/demo']],
            'Empresa'  => [['Contacto','/contact'],['Clientes','#'],['Carreras','#']],
            'Recursos' => [['Documentación','#'],['Estado','#'],['Changelog','#']],
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
        <div class="max-w-[1240px] mx-auto px-6 h-12 flex items-center justify-between text-[11.5px] text-ink-400">
            <div>© <?= date('Y') ?> Kydesk Helpdesk</div>
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-ink-900">Privacidad</a>
                <a href="#" class="hover:text-ink-900">Términos</a>
            </div>
        </div>
    </div>
</footer>
