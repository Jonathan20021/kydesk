<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$statusLabels = [
    'new'         => ['Nuevo',       '#94a3b8'],
    'contacted'   => ['Contactado',  '#3b82f6'],
    'qualified'   => ['Calificado',  '#0ea5e9'],
    'proposal'    => ['Propuesta',   '#a78bfa'],
    'negotiation' => ['Negociación', '#f59e0b'],
    'customer'    => ['Cliente',     '#16a34a'],
    'lost'        => ['Perdido',     '#ef4444'],
    'archived'    => ['Archivado',   '#6b7280'],
];
$ratingLabels = [
    'cold' => ['Frío', '#3b82f6', 'snowflake'],
    'warm' => ['Tibio', '#f59e0b', 'flame'],
    'hot'  => ['Caliente', '#ef4444', 'flame-kindling'],
];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] px-2.5 py-0.5 rounded-full" style="background:#f3f0ff;color:#5a3aff;border:1px solid #cdbfff">
                <i class="lucide lucide-crown text-[11px]"></i> BUSINESS
            </span>
            <span class="text-[11px] text-ink-400">Función incluida en tu plan</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">CRM · Leads y Clientes</h1>
        <p class="text-[13px] text-ink-400">Pipeline comercial, oportunidades, actividades y conversión de clientes en una sola plataforma.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $url('/t/' . $slug . '/crm/pipeline') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-kanban-square"></i> Pipeline</a>
        <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-users"></i> Leads</a>
        <?php if ($auth->can('crm.config')): ?>
            <a href="<?= $url('/t/' . $slug . '/crm/settings') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-settings-2"></i> Configurar</a>
        <?php endif; ?>
        <?php if ($auth->can('crm.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/crm/leads/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo lead</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php foreach ([
        ['Leads totales',  $stats['total_leads'],     'users',           '#7c5cff', '#f3f0ff', false],
        ['Abiertos',       $stats['open_leads'],      'inbox',           '#0ea5e9', '#e0f2fe', false],
        ['Clientes',       $stats['customers'],       'check-circle-2',  '#16a34a', '#ecfdf5', false],
        ['Pipeline ($)',   $stats['pipeline_value'],  'trending-up',     '#f59e0b', '#fffbeb', true],
    ] as [$lbl,$val,$ic,$col,$bg,$money]): ?>
        <div class="card card-pad flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $e($lbl) ?></div>
                <div class="font-display font-extrabold text-[22px] tracking-[-0.02em]">
                    <?= $money ? '$' . number_format((float)$val, 0) : number_format((int)$val) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
    <div class="card card-pad lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-bar-chart-3 text-brand-600"></i> Distribución por estado</div>
            <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="text-[11.5px] font-semibold text-brand-700">Ver todos →</a>
        </div>
        <?php
        $maxCount = max(1, ...array_values($statusCounts));
        ?>
        <div class="space-y-2">
            <?php foreach ($statusLabels as $k => [$lbl, $col]):
                $val = (int)($statusCounts[$k] ?? 0);
                $pct = (int)round(($val / $maxCount) * 100);
            ?>
                <a href="<?= $url('/t/' . $slug . '/crm/leads?status=' . $k) ?>" class="block hover:bg-bg rounded-lg px-2 py-1.5 transition">
                    <div class="flex items-center justify-between text-[12px] mb-1">
                        <span class="flex items-center gap-1.5 font-semibold"><span class="w-2 h-2 rounded-full" style="background:<?= $col ?>"></span><?= $e($lbl) ?></span>
                        <span class="font-mono text-ink-500"><?= number_format($val) ?></span>
                    </div>
                    <div class="progress" style="height:6px"><div class="progress-bar" style="width:<?= $pct ?>%; background:<?= $col ?>"></div></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card card-pad">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-radar text-amber-600"></i> Por origen</div>
        </div>
        <div class="space-y-1.5">
            <?php if (empty($bySource)): ?>
                <div class="text-[12px] text-ink-400 py-4 text-center">Sin orígenes aún.</div>
            <?php else: foreach (array_slice($bySource, 0, 8) as $s): ?>
                <div class="flex items-center gap-2 text-[12.5px]">
                    <div class="w-7 h-7 rounded-lg grid place-items-center flex-shrink-0" style="background:<?= $e($s['color']) ?>1a;color:<?= $e($s['color']) ?>"><i class="lucide lucide-<?= $e($s['icon']) ?> text-[12px]"></i></div>
                    <span class="flex-1 truncate"><?= $e($s['name']) ?></span>
                    <span class="font-mono font-bold text-ink-700"><?= number_format((int)$s['total']) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
    <div class="card card-pad lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-clock text-rose-600"></i> Próximas actividades</div>
            <?php if ((int)$stats['overdue_followups'] > 0): ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#b91c1c"><i class="lucide lucide-alert-triangle text-[11px]"></i> <?= (int)$stats['overdue_followups'] ?> vencidas</span>
            <?php endif; ?>
        </div>
        <?php if (empty($upcomingActivities)): ?>
            <div class="text-center py-10 text-[12.5px] text-ink-400">Nada agendado por ahora.</div>
        <?php else: ?>
            <div class="space-y-1">
                <?php foreach ($upcomingActivities as $a):
                    $when = $a['scheduled_at'] ? date('d M, H:i', strtotime($a['scheduled_at'])) : '—';
                    $isPast = $a['scheduled_at'] && strtotime($a['scheduled_at']) < time();
                ?>
                    <a href="<?= $url('/t/' . $slug . '/crm/leads/' . (int)$a['lead_id']) ?>" class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-bg transition">
                        <div class="w-8 h-8 rounded-lg grid place-items-center" style="background:#f3f0ff;color:#7c5cff"><i class="lucide lucide-<?= $a['type']==='call'?'phone':($a['type']==='meeting'?'calendar':($a['type']==='email'?'mail':'check-square')) ?> text-[13px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-semibold truncate"><?= $e($a['subject']) ?></div>
                            <div class="text-[11px] text-ink-400">con <?= $e(trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''))) ?> · <span class="font-mono"><?= $e($a['lead_code'] ?? '') ?></span></div>
                        </div>
                        <span class="text-[11px] font-mono <?= $isPast ? 'text-rose-600 font-bold' : 'text-ink-500' ?>"><?= $e($when) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card card-pad" style="background:linear-gradient(135deg,#fff5f5,#ffe4e6);border-color:#fecaca">
        <div class="flex items-center justify-between mb-3">
            <div class="font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-flame-kindling text-rose-600"></i> Hot leads</div>
        </div>
        <?php if (empty($hotLeads)): ?>
            <div class="text-center py-8 text-[12.5px] text-ink-400">Aún no hay leads marcados como "caliente".</div>
        <?php else: ?>
            <div class="space-y-1.5">
                <?php foreach ($hotLeads as $hl): ?>
                    <a href="<?= $url('/t/' . $slug . '/crm/leads/' . (int)$hl['id']) ?>" class="flex items-center gap-2 p-2 rounded-lg bg-white border border-rose-100 hover:border-rose-300 transition">
                        <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($hl['email'] ?? $hl['code']) ?>;color:white"><?= Helpers::initials(trim(($hl['first_name']??'') . ' ' . ($hl['last_name']??''))) ?></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-semibold truncate"><?= $e(trim(($hl['first_name']??'') . ' ' . ($hl['last_name']??''))) ?></div>
                            <div class="text-[10.5px] text-ink-400 truncate"><?= $e($hl['company_name'] ?? '—') ?></div>
                        </div>
                        <span class="text-[11px] font-mono font-bold text-rose-600"><?= (int)$hl['score'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="px-4 py-3 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
        <div class="font-display font-bold text-[15px] flex items-center gap-2"><i class="lucide lucide-sparkles text-brand-600"></i> Leads recientes</div>
        <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="text-[11.5px] font-semibold text-brand-700">Ver todos →</a>
    </div>
    <?php if (empty($recentLeads)): ?>
        <div class="text-center py-12">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 grid place-items-center mx-auto mb-3"><i class="lucide lucide-contact-round text-[24px] text-brand-600"></i></div>
            <h3 class="font-display font-bold text-[16px]">Aún no hay leads</h3>
            <p class="text-[12.5px] text-ink-400 mt-1">Empezá agregando tu primer lead manualmente o conectalo a un formulario web.</p>
            <?php if ($auth->can('crm.create')): ?>
                <a href="<?= $url('/t/' . $slug . '/crm/leads/create') ?>" class="btn btn-primary btn-sm mt-3 inline-flex"><i class="lucide lucide-plus"></i> Crear lead</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="admin-table" style="width:100%">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Lead</th>
                    <th>Empresa</th>
                    <th>Origen</th>
                    <th>Estado</th>
                    <th>Owner</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLeads as $l):
                    [$sl, $sCol] = $statusLabels[$l['status']] ?? ['—', '#6b7280'];
                ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/t/' . $slug . '/crm/leads/' . (int)$l['id']) ?>'">
                        <td class="font-mono text-[11.5px] text-ink-500"><?= $e($l['code']) ?></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($l['email'] ?? $l['code']) ?>;color:white"><?= Helpers::initials(trim(($l['first_name']??'').' '.($l['last_name']??''))) ?></div>
                                <div>
                                    <div class="font-display font-bold text-[13px]"><?= $e(trim(($l['first_name']??'').' '.($l['last_name']??''))) ?></div>
                                    <div class="text-[11px] text-ink-400"><?= $e($l['email'] ?? $l['phone'] ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-[12.5px]"><?= $e($l['company_name'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($l['source_name'])): ?>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:<?= $e($l['source_color']) ?>1a;color:<?= $e($l['source_color']) ?>"><i class="lucide lucide-<?= $e($l['source_icon']) ?> text-[10px]"></i> <?= $e($l['source_name']) ?></span>
                            <?php else: ?>
                                <span class="text-[11px] text-ink-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $sCol ?>1a;color:<?= $sCol ?>"><?= $e($sl) ?></span></td>
                        <td class="text-[12px]"><?= $e($l['owner_name'] ?? '—') ?></td>
                        <td class="text-right font-mono font-bold text-[12.5px]"><?= $l['estimated_value'] > 0 ? '$' . number_format((float)$l['estimated_value'], 0) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
