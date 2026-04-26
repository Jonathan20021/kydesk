<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Tus apps</h2>
            <p class="text-[12px] text-slate-400"><?= $usedApps ?> de <?= $maxApps ?> usadas en tu plan</p>
        </div>
        <a href="<?= $url('/developers/apps/create') ?>" class="dev-btn dev-btn-primary"><i class="lucide lucide-plus text-[13px]"></i> Nueva app</a>
    </div>
    <?php if (empty($apps)): ?>
        <div class="p-10 text-center">
            <div class="w-14 h-14 rounded-2xl mx-auto grid place-items-center bg-sky-500/10 text-sky-300 mb-3"><i class="lucide lucide-boxes text-[22px]"></i></div>
            <p class="font-display font-bold text-white text-[16px] mb-1">Aún no tienes apps</p>
            <p class="text-[13px] text-slate-400 mb-4">Crea una app para obtener tokens API y empezar a llamar a Kydesk.</p>
            <a href="<?= $url('/developers/apps/create') ?>" class="dev-btn dev-btn-primary inline-flex"><i class="lucide lucide-plus text-[13px]"></i> Crear primera app</a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="dev-table">
                <thead><tr><th>App</th><th>Slug</th><th>Entorno</th><th>Tokens</th><th>Requests/mes</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td>
                                <a href="<?= $url('/developers/apps/' . $a['id']) ?>" class="font-display font-bold text-white hover:text-sky-300"><?= $e($a['name']) ?></a>
                                <?php if (!empty($a['description'])): ?><div class="text-[11.5px] text-slate-400 mt-0.5"><?= $e(mb_strimwidth($a['description'], 0, 60, '…')) ?></div><?php endif; ?>
                            </td>
                            <td class="font-mono text-[12px]"><?= $e($a['slug']) ?></td>
                            <td><span class="dev-pill dev-pill-gray"><?= $e($a['environment']) ?></span></td>
                            <td class="text-white"><?= (int)$a['active_tokens'] ?></td>
                            <td class="text-white"><?= number_format((int)$a['month_requests']) ?></td>
                            <td><span class="dev-pill <?= $a['status']==='active'?'dev-pill-emerald':($a['status']==='suspended'?'dev-pill-red':'dev-pill-gray') ?>"><?= $e($a['status']) ?></span></td>
                            <td><a href="<?= $url('/developers/apps/' . $a['id']) ?>" class="dev-btn dev-btn-soft dev-btn-icon" title="Abrir"><i class="lucide lucide-arrow-right text-[14px]"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
