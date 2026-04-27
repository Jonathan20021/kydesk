<?php
$slug = $tenant->slug;
$installedByProvider = [];
foreach ($installed as $i) {
    $installedByProvider[$i['provider']][] = $i;
}
$catLabels = $categories;
?>
<style>
.iq-hero { padding:24px 26px; border-radius:24px; background:linear-gradient(135deg,#fff,#f3f0ff); border:1px solid #ececef; position:relative; overflow:hidden; }
.iq-hero::before { content:''; position:absolute; width:240px; height:240px; border-radius:50%; background:radial-gradient(circle,rgba(124,92,255,.12),transparent 70%); top:-100px; right:-60px; }
.iq-stat { padding:16px; border-radius:16px; background:#fff; border:1px solid #ececef; transition:transform .15s, box-shadow .15s; }
.iq-stat:hover { transform:translateY(-1px); box-shadow:0 8px 18px -10px rgba(22,21,27,.08); }
.iq-stat-label { font-size:10.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#8e8e9a; }
.iq-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; letter-spacing:-.025em; margin-top:4px; }

.iq-card { background:#fff; border:1px solid #ececef; border-radius:18px; padding:18px; transition:transform .15s, box-shadow .15s, border-color .15s; height:100%; display:flex; flex-direction:column; }
.iq-card:hover { transform:translateY(-2px); box-shadow:0 14px 32px -16px rgba(22,21,27,.16); border-color:#dcdce0; }
.iq-card-icon { width:48px; height:48px; border-radius:14px; display:grid; place-items:center; flex-shrink:0; margin-bottom:12px; }
.iq-card-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15px; letter-spacing:-.01em; color:#16151b; }
.iq-card-cat { font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#8e8e9a; }
.iq-card-desc { font-size:12.5px; color:#6b6b78; margin:8px 0 14px; line-height:1.5; flex:1; }

.iq-pill-status { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
.iq-pill-active { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.iq-pill-paused { background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb; }
.iq-pill-error  { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

.iq-installed-row { display:flex; align-items:center; gap:14px; padding:14px 18px; border-bottom:1px solid #f3f4f6; transition:background .12s; }
.iq-installed-row:last-child { border-bottom:none; }
.iq-installed-row:hover { background:#fafbff; }
.iq-installed-icon { width:42px; height:42px; border-radius:12px; display:grid; place-items:center; flex-shrink:0; }

.iq-cat-tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; }
.iq-cat-tab { padding:6px 14px; border-radius:999px; font-size:12.5px; font-weight:600; background:#fff; border:1px solid #ececef; color:#6b6b78; cursor:pointer; transition:all .12s; }
.iq-cat-tab:hover { border-color:#7c5cff; color:#7c5cff; }
.iq-cat-tab.active { background:#16151b; color:#fff; border-color:#16151b; }
</style>

<div x-data="{ filter:'all' }" class="space-y-5">

    <div class="iq-hero relative">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 relative">
            <div>
                <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-ink-400 mb-2">
                    <i class="lucide lucide-plug text-[12px]"></i> MARKETPLACE
                    <span class="text-ink-300">·</span>
                    <span class="px-2 py-0.5 rounded-full" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe">Plan <?= $e($planLabel) ?></span>
                </div>
                <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Integraciones</h1>
                <p class="text-[13px] text-ink-500 mt-1 max-w-2xl">Conecta Kydesk con tus herramientas favoritas. Las notificaciones de eventos se enviarán automáticamente cuando ocurran.</p>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="text-right">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Uso del plan</div>
                    <div class="font-display font-extrabold text-[22px]" style="letter-spacing:-.02em">
                        <?= number_format($stats['installed']) ?>
                        <span class="text-ink-400 font-normal text-[14px]">/ <?= $maxAllowed > 0 ? number_format($maxAllowed) : '∞' ?></span>
                    </div>
                </div>
                <?php if ($maxAllowed > 0): ?>
                    <?php $pct = min(100, ($stats['installed'] * 100) / max($maxAllowed, 1)); ?>
                    <div class="hidden md:block w-32">
                        <div class="h-2 rounded-full bg-[#f3f4f6] overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#7c5cff,#a78bfa)"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="iq-stat" style="border-top:3px solid #7c5cff">
            <div class="iq-stat-label">Instaladas</div>
            <div class="iq-stat-value"><?= number_format($stats['installed']) ?></div>
            <div class="text-[11.5px] text-ink-400 mt-1">en este workspace</div>
        </div>
        <div class="iq-stat" style="border-top:3px solid #16a34a">
            <div class="iq-stat-label">Activas</div>
            <div class="iq-stat-value"><?= number_format($stats['active']) ?></div>
            <div class="text-[11.5px] text-ink-400 mt-1">enviando eventos</div>
        </div>
        <div class="iq-stat" style="border-top:3px solid #0ea5e9">
            <div class="iq-stat-label">Eventos enviados</div>
            <div class="iq-stat-value"><?= number_format($stats['success']) ?></div>
            <div class="text-[11.5px] text-ink-400 mt-1">total exitosos</div>
        </div>
        <div class="iq-stat" style="border-top:3px solid #ef4444">
            <div class="iq-stat-label">Errores</div>
            <div class="iq-stat-value" style="color:<?= $stats['errors']>0?'#dc2626':'#16151b' ?>"><?= number_format($stats['errors']) ?></div>
            <div class="text-[11.5px] text-ink-400 mt-1"><?= $stats['errors']>0 ? 'Revisa la configuración' : 'Sin fallos' ?></div>
        </div>
    </div>

    <?php if (!empty($installed)): ?>
        <div class="card overflow-hidden" style="border-radius:18px">
            <div class="card-pad" style="border-bottom:1px solid #ececef">
                <h3 class="font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-check-circle-2" style="color:#16a34a"></i> Integraciones activas</h3>
                <p class="text-[12px] text-ink-400 mt-0.5"><?= count($installed) ?> integración(es) instaladas en tu workspace</p>
            </div>
            <?php foreach ($installed as $i):
                $def = $allProviders[$i['provider']] ?? ['name'=>$i['provider'],'icon'=>'plug','color'=>'#6b7280'];
                $statusCls = (int)$i['is_active']
                    ? ((string)$i['last_status']==='failed' ? 'iq-pill-error' : 'iq-pill-active')
                    : 'iq-pill-paused';
                $statusLbl = (int)$i['is_active']
                    ? ((string)$i['last_status']==='failed' ? 'Con errores' : 'Activa')
                    : 'Pausada';
            ?>
                <a href="<?= $url('/t/' . $slug . '/integrations/' . $e($i['provider']) . '/' . (int)$i['id']) ?>" class="iq-installed-row" style="text-decoration:none;color:inherit">
                    <div class="iq-installed-icon" style="background:<?= $e($def['color']) ?>15;color:<?= $e($def['color']) ?>;border:1px solid <?= $e($def['color']) ?>30">
                        <i class="lucide lucide-<?= $e($def['icon']) ?> text-[16px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-display font-bold text-[14px]"><?= $e($i['name']) ?></span>
                            <span class="iq-pill-status <?= $statusCls ?>"><?= $e($statusLbl) ?></span>
                            <span class="text-[11px] text-ink-400"><?= $e($def['name']) ?></span>
                        </div>
                        <div class="flex items-center gap-3 mt-1 text-[11.5px] text-ink-400">
                            <span><i class="lucide lucide-check text-[11px]" style="color:#16a34a"></i> <?= number_format((int)$i['success_count']) ?> envíos OK</span>
                            <?php if ((int)$i['error_count'] > 0): ?>
                                <span style="color:#dc2626"><i class="lucide lucide-x text-[11px]"></i> <?= number_format((int)$i['error_count']) ?> errores</span>
                            <?php endif; ?>
                            <?php if ($i['last_event_at']): ?>
                                <span><i class="lucide lucide-clock text-[11px]"></i> Último evento: <?= $e(date('d M H:i', strtotime((string)$i['last_event_at']))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="lucide lucide-chevron-right text-ink-300 text-[16px]"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div>
        <div class="iq-cat-tabs">
            <button type="button" @click="filter='all'" :class="filter==='all'?'active':''" class="iq-cat-tab">Todas</button>
            <?php foreach ($catLabels as $key => $cat): ?>
                <button type="button" @click="filter='<?= $key ?>'" :class="filter==='<?= $key ?>'?'active':''" class="iq-cat-tab"><i class="lucide lucide-<?= $cat['icon'] ?> text-[12px]"></i> <?= $e($cat['label']) ?></button>
            <?php endforeach; ?>
        </div>

        <?php if (empty($marketplace)): ?>
            <div class="card card-pad text-center py-16">
                <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-lock text-[22px] text-ink-400"></i></div>
                <div class="font-display font-bold">No hay integraciones disponibles en tu plan</div>
                <p class="text-[13px] text-ink-400 mt-1">Haz upgrade para acceder a más proveedores</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($marketplace as $slugProv => $def):
                    $installedCount = isset($installedByProvider[$slugProv]) ? count($installedByProvider[$slugProv]) : 0;
                ?>
                    <div x-show="filter==='all' || filter==='<?= $e($def['category']) ?>'" x-transition>
                        <div class="iq-card">
                            <div class="iq-card-icon" style="background:<?= $e($def['color']) ?>15;color:<?= $e($def['color']) ?>;border:1px solid <?= $e($def['color']) ?>30">
                                <i class="lucide lucide-<?= $e($def['icon']) ?> text-[20px]"></i>
                            </div>
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <div class="iq-card-cat"><?= $e($catLabels[$def['category']]['label'] ?? $def['category']) ?></div>
                                <?php if ($installedCount > 0): ?>
                                    <span class="iq-pill-status iq-pill-active"><?= $installedCount ?> activa<?= $installedCount>1?'s':'' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="iq-card-title"><?= $e($def['name']) ?></div>
                            <p class="iq-card-desc"><?= $e($def['short']) ?></p>
                            <a href="<?= $url('/t/' . $slug . '/integrations/' . $e($slugProv)) ?>" class="btn btn-dark btn-sm w-full">
                                <i class="lucide lucide-<?= $installedCount>0?'plus':'plug' ?> text-[13px]"></i>
                                <?= $installedCount>0 ? 'Añadir otra' : 'Conectar' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($maxAllowed > 0 && $stats['installed'] >= $maxAllowed): ?>
        <div class="card card-pad" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fde68a;border-radius:16px">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#fef3c7;color:#b45309"><i class="lucide lucide-alert-circle"></i></div>
                <div class="flex-1">
                    <div class="font-display font-bold text-[14.5px]" style="color:#92400e">Has alcanzado el límite de tu plan</div>
                    <div class="text-[12.5px]" style="color:#a16207">Tu plan <?= $e($planLabel) ?> permite hasta <?= number_format($maxAllowed) ?> integraciones. Haz upgrade para añadir más, o desactiva alguna existente.</div>
                </div>
                <a href="<?= $url('/pricing') ?>" class="btn btn-sm" style="background:#92400e;color:white">Hacer upgrade</a>
            </div>
        </div>
    <?php endif; ?>
</div>
