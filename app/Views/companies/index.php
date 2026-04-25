<?php use App\Core\Helpers; $slug = $tenant->slug; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Empresas</h1>
        <p class="text-[13px] text-ink-400"><?= count($companies) ?> clientes registrados</p>
    </div>
    <?php if ($auth->can('companies.create')): ?>
        <a href="<?= $url('/t/' . $slug . '/companies/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nueva empresa</a>
    <?php endif; ?>
</div>

<form method="GET">
    <div class="search-pill"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar empresas…"></div>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php
    $tierConfig = ['enterprise'=>['Enterprise','badge-purple'],'premium'=>['Premium','badge-blue'],'standard'=>['Standard','badge-gray']];
    foreach ($companies as $c): [$tl,$tcl] = $tierConfig[$c['tier']] ?? ['—','badge-gray']; ?>
        <div class="card card-pad hover:shadow-md transition block group relative">
            <a href="<?= $url('/t/' . $slug . '/companies/' . $c['id']) ?>" class="block">
                <div class="flex items-start justify-between">
                    <div class="avatar avatar-lg" style="background:<?= Helpers::colorFor($c['name']) ?>;color:white;border-radius:14px;"><?= strtoupper(substr($c['name'],0,2)) ?></div>
                    <span class="badge <?= $tcl ?>"><?= $tl ?></span>
                </div>
                <div class="mt-4">
                    <div class="font-display font-bold text-[15.5px] truncate"><?= $e($c['name']) ?></div>
                    <div class="text-[12px] mt-0.5 text-ink-400"><?= $e($c['industry'] ?? '—') ?> · <?= $e($c['size'] ?? '') ?></div>
                </div>
                <div class="mt-5 pt-4 grid grid-cols-3 gap-2 text-[11.5px] border-t border-[#ececef]">
                    <div><div class="text-ink-400">Tickets</div><div class="font-display font-bold text-[16px] mt-0.5"><?= (int)$c['tickets'] ?></div></div>
                    <div><div class="text-ink-400">Contactos</div><div class="font-display font-bold text-[16px] mt-0.5"><?= (int)$c['contacts'] ?></div></div>
                    <div><div class="text-ink-400">Activos</div><div class="font-display font-bold text-[16px] mt-0.5"><?= (int)$c['assets'] ?></div></div>
                </div>
            </a>
            <div class="mt-4 pt-3 flex items-center gap-1.5 border-t border-[#ececef]">
                <a href="<?= $url('/t/' . $slug . '/tickets/create?company_id=' . (int)$c['id']) ?>" class="btn btn-soft btn-xs flex-1 justify-center"><i class="lucide lucide-plus text-[12px]"></i> Nuevo ticket</a>
                <a href="<?= $url('/portal/' . $slug . '/new?company=' . (int)$c['id']) ?>" target="_blank" class="btn btn-outline btn-xs" data-tooltip="Portal público"><i class="lucide lucide-external-link text-[12px]"></i></a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($companies)): ?>
        <div class="col-span-full card card-pad text-center py-16">
            <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-building-2 text-[22px] text-ink-400"></i></div>
            <h3 class="font-display font-bold">Sin empresas</h3>
        </div>
    <?php endif; ?>
</div>
