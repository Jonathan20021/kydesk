<?php
use App\Controllers\MeetingController;
$slug = $tenant->slug;
$weekdays = MeetingController::WEEKDAYS;
// Construir mapa: weekday → primer slot (mostraremos 1 franja por día por simplicidad)
$daySlots = [];
foreach ($weekdays as $wd => $name) {
    $daySlots[$wd] = $slots[$wd] ?? [];
}
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
            <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
            <span>Disponibilidad</span>
        </div>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Disponibilidad</h1>
        <p class="text-[13px] text-ink-400">Definí los horarios semanales en los que cada host puede recibir reservas.</p>
    </div>
    <form method="GET" class="flex gap-2 items-end">
        <div>
            <label class="text-[11px] font-semibold text-ink-500 uppercase tracking-[0.14em] mb-1 block">Host</label>
            <select name="host_id" class="input" onchange="this.form.submit()">
                <?php foreach ($hosts as $h): ?>
                    <option value="<?= (int)$h['id'] ?>" <?= $hostId===(int)$h['id']?'selected':'' ?>><?= $e($h['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="px-5 py-3.5 flex items-center gap-2" style="border-bottom:1px solid var(--border)">
            <i class="lucide lucide-clock text-ink-400"></i>
            <h3 class="font-display font-bold text-[15px]">Horario semanal</h3>
        </div>
        <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/availability') ?>" class="p-5">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="host_id" value="<?= (int)$hostId ?>">

            <div class="space-y-2">
                <?php foreach ($weekdays as $wd => $name):
                    $current = $daySlots[$wd] ?? [];
                    // soportamos hasta 3 franjas por día
                    $franjas = [];
                    foreach ($current as $c) {
                        $franjas[] = ['start' => substr($c['start_time'], 0, 5), 'end' => substr($c['end_time'], 0, 5), 'active' => true];
                    }
                    if (empty($franjas)) {
                        $franjas[] = ['start' => '09:00', 'end' => '17:00', 'active' => false];
                    }
                ?>
                    <div x-data='<?= htmlspecialchars(json_encode(["active" => !empty($current), "slots" => $franjas]), ENT_QUOTES) ?>' class="rounded-xl p-3" :class="active ? 'bg-white' : 'bg-bg'" style="border:1px solid var(--border);background:<?= !empty($current) ? 'white' : 'var(--bg)' ?>">
                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-[13px] font-semibold text-ink-900 min-w-[120px]">
                                <input type="checkbox" x-model="active"> <?= $e($name) ?>
                            </label>
                            <div class="flex-1 flex flex-col gap-2 ml-3">
                                <template x-for="(slot, i) in slots" :key="i">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" :name="'slots[<?= $wd ?>][' + i + '][active]'" :value="active ? '1' : '0'">
                                        <input type="time" :name="'slots[<?= $wd ?>][' + i + '][start]'" x-model="slot.start" class="input" style="height:36px;flex:1" :disabled="!active">
                                        <span class="text-ink-400">→</span>
                                        <input type="time" :name="'slots[<?= $wd ?>][' + i + '][end]'" x-model="slot.end" class="input" style="height:36px;flex:1" :disabled="!active">
                                        <button type="button" @click="slots.splice(i,1)" x-show="slots.length > 1" class="admin-btn admin-btn-soft" style="height:36px;width:36px;padding:0"><i class="lucide lucide-x text-[12px]"></i></button>
                                    </div>
                                </template>
                                <button type="button" @click="slots.push({start:'09:00',end:'17:00',active:true})" x-show="active" class="text-[11.5px] text-brand-700 font-semibold inline-flex items-center gap-1 self-start"><i class="lucide lucide-plus text-[11px]"></i> Otra franja</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="lucide lucide-check"></i> Guardar disponibilidad</button>
            </div>
        </form>
    </div>

    <div class="space-y-4">
        <div class="card overflow-hidden">
            <div class="px-5 py-3.5 flex items-center justify-between" style="border-bottom:1px solid var(--border)">
                <div class="flex items-center gap-2">
                    <i class="lucide lucide-calendar-x text-ink-400"></i>
                    <h3 class="font-display font-bold text-[15px]">Días bloqueados</h3>
                </div>
            </div>
            <div class="p-4 space-y-2">
                <?php if (empty($blocked)): ?>
                    <p class="text-[12px] text-ink-400 text-center py-4">Sin bloqueos · todos los días según el horario semanal.</p>
                <?php else: ?>
                    <?php foreach ($blocked as $b): ?>
                        <div class="flex items-center gap-2 p-2.5 rounded-lg" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="flex-1 min-w-0">
                                <div class="text-[12.5px] font-semibold">
                                    <?= date('d M Y', strtotime($b['date_start'])) ?>
                                    <?php if ($b['date_start'] !== $b['date_end']): ?>
                                        → <?= date('d M Y', strtotime($b['date_end'])) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[11px] text-ink-400">
                                    <?= (int)$b['is_full_day'] ? 'Día completo' : (substr($b['start_time'] ?? '', 0, 5) . ' - ' . substr($b['end_time'] ?? '', 0, 5)) ?>
                                    <?php if (!empty($b['reason'])): ?> · <?= $e($b['reason']) ?><?php endif; ?>
                                    <?php if (!$b['user_id']): ?> · <span class="text-amber-600">Global</span><?php endif; ?>
                                </div>
                            </div>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/blocked/' . $b['id'] . '/delete') ?>">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="admin-btn admin-btn-danger" style="height:30px;padding:0 10px;font-size:11.5px" onclick="return confirm('¿Eliminar bloqueo?')"><i class="lucide lucide-trash-2 text-[11px]"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="<?= $url('/t/' . $slug . '/meetings/blocked') ?>" class="card card-pad space-y-3" x-data="{ fullDay: true }">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <h3 class="font-display font-bold text-[15px]">Bloquear días</h3>
            <input type="hidden" name="user_id" value="<?= (int)$hostId ?>">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Desde</label>
                    <input type="date" name="date_start" required class="input">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Hasta</label>
                    <input type="date" name="date_end" required class="input">
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-[12.5px]">
                <input type="checkbox" name="is_full_day" value="1" x-model="fullDay" checked> Día completo
            </label>
            <div class="grid grid-cols-2 gap-2" x-show="!fullDay" x-cloak>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Hora inicio</label>
                    <input type="time" name="start_time" class="input">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Hora fin</label>
                    <input type="time" name="end_time" class="input">
                </div>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Motivo (opcional)</label>
                <input type="text" name="reason" class="input" placeholder="Vacaciones, feriado, fuera de oficina...">
            </div>
            <button class="btn btn-primary btn-sm w-full"><i class="lucide lucide-plus"></i> Agregar bloqueo</button>
        </form>
    </div>
</div>
