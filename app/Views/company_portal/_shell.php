<?php
/**
 * Shell del portal empresarial.
 * Variables esperadas:
 *   - $tenantPublic (Tenant)
 *   - $portalUser   (array)
 *   - $company      (array)
 *   - $nav          (string: dashboard|tickets|reports|team)
 *   - $bodyContent  (string ya renderizado a inyectar)
 */
$brand = $tenantPublic->data['primary_color'] ?? '#7c5cff';
$brandRgb = sscanf($brand, "#%02x%02x%02x");
$rgbStr = $brandRgb ? implode(',', $brandRgb) : '124,92,255';
$slug = $tenantPublic->slug;
$isManager = !empty($portalUser['is_company_manager']);
?>
<div class="min-h-screen" style="background:#fafafb">
    <nav class="bg-white border-b border-[#ececef] sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center gap-4">
            <a href="<?= $url('/portal/' . $slug . '/company') ?>" class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px] shrink-0" style="background:<?= $e($brand) ?>;box-shadow:0 6px 14px -4px rgba(<?= $rgbStr ?>,.45)"><?= strtoupper(substr($tenantPublic->name,0,1)) ?></div>
                <div class="leading-tight min-w-0">
                    <div class="font-display font-extrabold text-[14px] tracking-[-0.015em] truncate"><?= $e($company['name']) ?></div>
                    <div class="text-[10px] text-ink-400 uppercase tracking-[0.12em] truncate">Portal · <?= $e($tenantPublic->name) ?></div>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-1 ml-4">
                <a href="<?= $url('/portal/' . $slug . '/company') ?>"          class="px-3 py-1.5 rounded-lg text-[12.5px] <?= $nav==='dashboard'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500 hover:text-ink-900' ?>"><i class="lucide lucide-layout-dashboard text-[13px]"></i> Dashboard</a>
                <a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>"  class="px-3 py-1.5 rounded-lg text-[12.5px] <?= $nav==='tickets'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500 hover:text-ink-900' ?>"><i class="lucide lucide-ticket text-[13px]"></i> Tickets</a>
                <?php if ($isManager): ?>
                    <a href="<?= $url('/portal/' . $slug . '/company/reports') ?>" class="px-3 py-1.5 rounded-lg text-[12.5px] <?= $nav==='reports'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500 hover:text-ink-900' ?>"><i class="lucide lucide-bar-chart-3 text-[13px]"></i> Reportes</a>
                    <a href="<?= $url('/portal/' . $slug . '/company/team') ?>"    class="px-3 py-1.5 rounded-lg text-[12.5px] <?= $nav==='team'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500 hover:text-ink-900' ?>"><i class="lucide lucide-users text-[13px]"></i> Equipo</a>
                <?php endif; ?>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <a href="<?= $url('/portal/' . $slug . '/company/tickets/new') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i><span class="hidden sm:inline">Nuevo ticket</span></a>
                <div class="relative" x-data="{open:false}">
                    <button @click="open=!open" class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-xl hover:bg-[#fafafb] transition">
                        <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[12px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)"><?= strtoupper(substr($portalUser['name'],0,1)) ?></div>
                        <div class="hidden sm:block leading-tight text-left">
                            <div class="text-[12px] font-semibold text-ink-900 truncate max-w-[140px]"><?= $e(explode(' ', $portalUser['name'])[0]) ?></div>
                            <div class="text-[10px] text-ink-400"><?= $isManager ? 'Manager' : 'Miembro' ?></div>
                        </div>
                        <i class="lucide lucide-chevron-down text-[12px] text-ink-400"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open=false" class="absolute right-0 mt-2 w-56 bg-white border border-[#ececef] rounded-xl shadow-lg p-1.5 z-50">
                        <div class="px-3 py-2 border-b border-[#ececef] mb-1">
                            <div class="text-[12.5px] font-semibold truncate"><?= $e($portalUser['name']) ?></div>
                            <div class="text-[11px] text-ink-400 truncate"><?= $e($portalUser['email']) ?></div>
                        </div>
                        <a href="<?= $url('/portal/' . $slug . '/account') ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] hover:bg-[#fafafb]"><i class="lucide lucide-user text-[13px]"></i> Mi cuenta</a>
                        <a href="<?= $url('/portal/' . $slug) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] hover:bg-[#fafafb]"><i class="lucide lucide-life-buoy text-[13px]"></i> Centro de soporte</a>
                        <form method="POST" action="<?= $url('/portal/' . $slug . '/logout') ?>" class="border-t border-[#ececef] mt-1 pt-1">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] text-rose-600 hover:bg-rose-50"><i class="lucide lucide-log-out text-[13px]"></i> Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="md:hidden flex items-center gap-1 px-3 pb-2 overflow-x-auto">
            <a href="<?= $url('/portal/' . $slug . '/company') ?>"         class="px-3 py-1.5 rounded-lg text-[12px] whitespace-nowrap <?= $nav==='dashboard'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500' ?>">Dashboard</a>
            <a href="<?= $url('/portal/' . $slug . '/company/tickets') ?>" class="px-3 py-1.5 rounded-lg text-[12px] whitespace-nowrap <?= $nav==='tickets'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500' ?>">Tickets</a>
            <?php if ($isManager): ?>
                <a href="<?= $url('/portal/' . $slug . '/company/reports') ?>" class="px-3 py-1.5 rounded-lg text-[12px] whitespace-nowrap <?= $nav==='reports'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500' ?>">Reportes</a>
                <a href="<?= $url('/portal/' . $slug . '/company/team') ?>"    class="px-3 py-1.5 rounded-lg text-[12px] whitespace-nowrap <?= $nav==='team'?'bg-brand-50 text-brand-700 font-semibold':'text-ink-500' ?>">Equipo</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6"><?= $bodyContent ?></main>
</div>
