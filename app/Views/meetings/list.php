<?php
use App\Controllers\MeetingController;
$slug = $tenant->slug;
$f = $filters;
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
            <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
            <span>Listado</span>
        </div>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Todas las reuniones</h1>
        <p class="text-[13px] text-ink-400">Filtrá por estado, tipo, host, rango de fechas o búsqueda libre.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $url('/t/' . $slug . '/meetings/calendar') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-calendar"></i> Calendario</a>
        <?php if ($auth->can('meetings.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/meetings/manual') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Agendar</a>
        <?php endif; ?>
    </div>
</div>

<form method="GET" class="card card-pad mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-2">
    <div class="lg:col-span-2"><div class="search-pill"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($f['q']) ?>" placeholder="Buscar por código, nombre, email..."></div></div>
    <select name="status" class="input">
        <option value="">Todos los estados</option>
        <?php foreach (MeetingController::STATUS_LABELS as $sk => [$sl,,]): ?>
            <option value="<?= $sk ?>" <?= $f['status']===$sk?'selected':'' ?>><?= $sl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type_id" class="input">
        <option value="0">Todos los tipos</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $f['typeId']===(int)$t['id']?'selected':'' ?>><?= $e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="host_id" class="input">
        <option value="0">Todos los hosts</option>
        <?php foreach ($hosts as $h): ?>
            <option value="<?= (int)$h['id'] ?>" <?= $f['hostId']===(int)$h['id']?'selected':'' ?>><?= $e($h['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="from" value="<?= $e($f['from']) ?>" class="input" placeholder="Desde">
    <div class="flex gap-2">
        <input type="date" name="to" value="<?= $e($f['to']) ?>" class="input" placeholder="Hasta">
        <button class="btn btn-dark btn-sm flex-shrink-0"><i class="lucide lucide-filter"></i></button>
    </div>
</form>

<?php if (empty($meetings)): ?>
    <div class="card card-pad text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-[#f3f0ff] grid place-items-center mx-auto mb-4"><i class="lucide lucide-calendar-x text-[26px] text-brand-500"></i></div>
        <h3 class="font-display font-bold text-[18px]">Sin resultados</h3>
        <p class="text-[13px] text-ink-400 mt-1">Ajustá los filtros o probá compartir tu enlace público para recibir reservas.</p>
    </div>
<?php else: ?>
    <div class="card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cuándo</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Host</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m):
                        [$lbl, $cls] = MeetingController::STATUS_LABELS[$m['status']] ?? [ucfirst($m['status']), 'badge-gray'];
                        $when = strtotime($m['scheduled_at']);
                    ?>
                        <tr>
                            <td><span class="font-mono text-[12px]"><?= $e($m['code']) ?></span></td>
                            <td>
                                <div class="font-semibold text-[13px]"><?= date('d M Y', $when) ?></div>
                                <div class="text-[11px] text-ink-400"><?= date('H:i', $when) ?> · <?= (int)$m['duration_minutes'] ?> min</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-semibold"><?= $e($m['customer_name']) ?></span>
                                    <?php if (!empty($m['ai_urgency']) && $m['ai_urgency'] === 'high'): ?>
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-[0.1em]" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca" title="Urgencia alta detectada por IA"><i class="lucide lucide-zap text-[9px]"></i> Urgente</span>
                                    <?php endif; ?>
                                    <?php if (!empty($m['ai_sentiment']) && $m['ai_sentiment'] === 'negative'): ?>
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-[0.1em]" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca" title="Sentiment negativo detectado por IA"><i class="lucide lucide-frown text-[9px]"></i> Negativo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[11px] text-ink-400"><?= $e($m['customer_email']) ?></div>
                                <?php if (!empty($m['company_name'])): ?>
                                    <div class="text-[11px] text-ink-500 mt-0.5"><i class="lucide lucide-building-2 text-[10px]"></i> <?= $e($m['company_name']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($m['ai_intent'])): ?>
                                    <div class="text-[10px] text-ink-400 mt-0.5"><i class="lucide lucide-sparkles text-[9px]"></i> <?= $e(ucfirst($m['ai_intent'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 text-[12px]">
                                    <span class="w-2 h-2 rounded-full" style="background:<?= $e($m['type_color'] ?? '#7c5cff') ?>"></span>
                                    <?= $e($m['type_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td><?= $e($m['host_name'] ?? '—') ?></td>
                            <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                            <td>
                                <a href="<?= $url('/t/' . $slug . '/meetings/' . $m['id']) ?>" class="admin-btn admin-btn-soft" style="height:32px;padding:0 12px;font-size:12px">
                                    <i class="lucide lucide-eye text-[12px]"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
