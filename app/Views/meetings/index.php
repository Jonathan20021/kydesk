<?php
use App\Core\Helpers;
use App\Controllers\MeetingController;
$slug = $tenant->slug;
$publicSlug = $settings['public_slug'] ?: $slug;
$publicUrl = rtrim($app->config['app']['url'], '/') . '/book/' . rawurlencode($publicSlug);
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.14em] px-2.5 py-0.5 rounded-full" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0">
                <i class="lucide lucide-crown text-[11px]"></i> BUSINESS
            </span>
            <span class="text-[11px] text-ink-400">Función incluida en tu plan</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Agenda de reuniones</h1>
        <p class="text-[13px] text-ink-400">Página pública para que clientes reserven citas con tu equipo · control total de horarios, días bloqueados, tipos y notificaciones.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $e($publicUrl) ?>" target="_blank" class="btn btn-outline btn-sm" data-tooltip="Abrir página pública">
            <i class="lucide lucide-external-link"></i> Página pública
        </a>
        <?php if ($auth->can('meetings.config')): ?>
            <a href="<?= $url('/t/' . $slug . '/meetings/types') ?>" class="btn btn-soft btn-sm">
                <i class="lucide lucide-list"></i> Tipos
            </a>
            <a href="<?= $url('/t/' . $slug . '/meetings/availability') ?>" class="btn btn-soft btn-sm">
                <i class="lucide lucide-clock"></i> Disponibilidad
            </a>
            <a href="<?= $url('/t/' . $slug . '/meetings/settings') ?>" class="btn btn-soft btn-sm">
                <i class="lucide lucide-settings-2"></i> Ajustes
            </a>
        <?php endif; ?>
        <?php if ($auth->can('meetings.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/meetings/manual') ?>" class="btn btn-primary btn-sm">
                <i class="lucide lucide-plus"></i> Agendar
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Public link card -->
<div class="card card-pad mb-5 flex flex-col md:flex-row md:items-center gap-4" style="background:linear-gradient(135deg,#fdf4ff 0%,#f3f0ff 60%,#eef2ff 100%);border-color:#e9d5ff">
    <div class="w-12 h-12 rounded-2xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 20px -8px rgba(124,92,255,.5)">
        <i class="lucide lucide-link-2 text-[22px]"></i>
    </div>
    <div class="flex-1 min-w-0">
        <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-ink-400 mb-1">Tu enlace público de reservas</div>
        <div class="font-display font-bold text-[15px] truncate"><?= $e($publicUrl) ?></div>
        <div class="text-[12px] text-ink-500 mt-0.5">Compartilo en email firmas, sitio web, redes o WhatsApp.</div>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="navigator.clipboard.writeText('<?= $e($publicUrl) ?>'); this.querySelector('span').textContent='¡Copiado!'; setTimeout(()=>this.querySelector('span').textContent='Copiar enlace', 2000)" class="btn btn-outline btn-sm">
            <i class="lucide lucide-copy"></i> <span>Copiar enlace</span>
        </button>
        <a href="<?= $e($publicUrl) ?>" target="_blank" class="btn btn-primary btn-sm">
            <i class="lucide lucide-external-link"></i> Abrir
        </a>
    </div>
</div>

<!-- Stats grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php foreach ([
        ['Hoy',          $stats['today'],     'calendar-check',    '#0ea5e9', '#e0f2fe'],
        ['Próximas',     $stats['upcoming'],  'calendar-clock',    '#7c5cff', '#f3f0ff'],
        ['Este mes',     $stats['this_month'],'calendar-days',     '#10b981', '#ecfdf5'],
        ['Total',        $stats['total'],     'database',          '#f59e0b', '#fffbeb'],
    ] as [$lbl,$val,$ic,$col,$bg]): ?>
        <div class="card card-pad flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl grid place-items-center" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400"><?= $e($lbl) ?></div>
                <div class="font-display font-extrabold text-[22px] tracking-[-0.02em]"><?= number_format((int)$val) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Próximas reuniones -->
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="px-5 py-3.5 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
            <div class="flex items-center gap-2">
                <i class="lucide lucide-calendar-clock text-ink-400"></i>
                <h3 class="font-display font-bold text-[15px]">Próximas reuniones</h3>
            </div>
            <a href="<?= $url('/t/' . $slug . '/meetings/list?status=confirmed') ?>" class="text-[12px] font-semibold text-brand-700">Ver todas →</a>
        </div>
        <?php if (empty($upcoming)): ?>
            <div class="text-center py-12 px-6">
                <div class="w-14 h-14 rounded-2xl bg-ink-100 grid place-items-center mx-auto mb-3" style="background:var(--bg)"><i class="lucide lucide-calendar-x text-[24px] text-ink-400"></i></div>
                <p class="text-[14px] font-medium text-ink-700">No hay reuniones agendadas</p>
                <p class="text-[12px] text-ink-400 mt-1">Compartí tu enlace público para empezar a recibir reservas.</p>
            </div>
        <?php else: ?>
            <div class="divide-y" style="border-color:var(--border)">
                <?php foreach ($upcoming as $m):
                    $when = strtotime($m['scheduled_at']);
                    $isToday = date('Y-m-d', $when) === date('Y-m-d');
                    $isTomorrow = date('Y-m-d', $when) === date('Y-m-d', strtotime('+1 day'));
                ?>
                    <a href="<?= $url('/t/' . $slug . '/meetings/' . $m['id']) ?>" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-bg" style="--bg:var(--bg)">
                        <div class="text-center flex-shrink-0" style="width:54px">
                            <div class="text-[10px] font-bold uppercase tracking-[0.14em]" style="color:<?= $e($m['type_color'] ?? '#7c5cff') ?>"><?= date('M', $when) ?></div>
                            <div class="font-display font-extrabold text-[24px] leading-none mt-0.5"><?= date('d', $when) ?></div>
                            <div class="text-[10px] text-ink-400 mt-0.5"><?= date('H:i', $when) ?></div>
                        </div>
                        <div class="w-1 self-stretch rounded-full" style="background:<?= $e($m['type_color'] ?? '#7c5cff') ?>"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                <span class="font-semibold text-[14px] text-ink-900 truncate"><?= $e($m['customer_name']) ?></span>
                                <?php if ($isToday): ?><span class="badge badge-emerald text-[10px]">HOY</span>
                                <?php elseif ($isTomorrow): ?><span class="badge badge-blue text-[10px]">MAÑANA</span><?php endif; ?>
                            </div>
                            <div class="text-[12px] text-ink-500 truncate">
                                <i class="lucide lucide-<?= $e($m['type_icon'] ?? 'video') ?> text-[12px]"></i>
                                <?= $e($m['type_name'] ?? 'Reunión') ?>
                                <?php if (!empty($m['host_name'])): ?> · <?= $e($m['host_name']) ?><?php endif; ?>
                                <?php if (!empty($m['company_name'])): ?> · <?= $e($m['company_name']) ?><?php endif; ?>
                            </div>
                        </div>
                        <?php [$lbl, $cls] = MeetingController::STATUS_LABELS[$m['status']] ?? [ucfirst($m['status']), 'badge-gray']; ?>
                        <span class="badge <?= $cls ?>"><?= $lbl ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tipos de reunión activos -->
    <div class="card overflow-hidden">
        <div class="px-5 py-3.5 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
            <div class="flex items-center gap-2">
                <i class="lucide lucide-list text-ink-400"></i>
                <h3 class="font-display font-bold text-[15px]">Tipos activos</h3>
            </div>
            <?php if ($auth->can('meetings.config')): ?>
                <a href="<?= $url('/t/' . $slug . '/meetings/types') ?>" class="text-[12px] font-semibold text-brand-700">Gestionar →</a>
            <?php endif; ?>
        </div>
        <?php if (empty($types)): ?>
            <div class="text-center py-10 px-4">
                <p class="text-[13px] text-ink-400">Sin tipos activos.</p>
            </div>
        <?php else: ?>
            <div class="divide-y" style="border-color:var(--border)">
                <?php foreach ($types as $t): ?>
                    <a href="<?= $e($publicUrl . '/' . rawurlencode((string)$t['slug'])) ?>" target="_blank" class="flex items-center gap-3 px-5 py-3 transition hover:bg-bg">
                        <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:<?= $e($t['color']) ?>22;color:<?= $e($t['color']) ?>">
                            <i class="lucide lucide-<?= $e($t['icon']) ?> text-[16px]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-[13.5px] truncate"><?= $e($t['name']) ?></div>
                            <div class="text-[11px] text-ink-400"><?= (int)$t['duration_minutes'] ?> min · <?= (int)$t['upcoming_count'] ?> próximas</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recientes -->
<?php if (!empty($recent)): ?>
<div class="mt-4 card overflow-hidden">
    <div class="px-5 py-3.5 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
        <div class="flex items-center gap-2">
            <i class="lucide lucide-history text-ink-400"></i>
            <h3 class="font-display font-bold text-[15px]">Reuniones recientes</h3>
        </div>
        <a href="<?= $url('/t/' . $slug . '/meetings/list') ?>" class="text-[12px] font-semibold text-brand-700">Ver historial →</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Host</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $m):
                    [$lbl, $cls] = MeetingController::STATUS_LABELS[$m['status']] ?? [ucfirst($m['status']), 'badge-gray'];
                ?>
                    <tr style="cursor:pointer" onclick="window.location='<?= $url('/t/' . $slug . '/meetings/' . $m['id']) ?>'">
                        <td><?= date('d M · H:i', strtotime($m['scheduled_at'])) ?></td>
                        <td><span class="font-semibold"><?= $e($m['customer_name']) ?></span><br><span class="text-[11px] text-ink-400"><?= $e($m['customer_email']) ?></span></td>
                        <td>
                            <span class="inline-flex items-center gap-1.5 text-[12px]">
                                <span class="w-2 h-2 rounded-full" style="background:<?= $e($m['type_color'] ?? '#7c5cff') ?>"></span>
                                <?= $e($m['type_name'] ?? 'Reunión') ?>
                            </span>
                        </td>
                        <td><?= $e($m['host_name'] ?? '—') ?></td>
                        <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
