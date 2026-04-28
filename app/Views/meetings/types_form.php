<?php
$slug = $tenant->slug;
$t = $type;
$isEdit = $t !== null;
$action = $isEdit ? $url('/t/' . $slug . '/meetings/types/' . $t['id']) : $url('/t/' . $slug . '/meetings/types');
$questions = [];
if ($isEdit && !empty($t['custom_questions'])) {
    $decoded = json_decode($t['custom_questions'], true);
    if (is_array($decoded)) $questions = $decoded;
}
$colors = ['#7c5cff','#22c55e','#0ea5e9','#f59e0b','#ec4899','#ef4444','#14b8a6','#a855f7','#0891b2','#65a30d'];
$icons = ['video','phone-call','users','briefcase','map-pin','message-square','calendar-clock','book-open','presentation','headphones','rocket','heart-handshake'];
?>

<div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
    <a href="<?= $url('/t/' . $slug . '/meetings/types') ?>" class="hover:text-ink-700">Tipos</a> /
    <span><?= $isEdit ? 'Editar' : 'Nuevo' ?></span>
</div>
<h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em] mb-5"><?= $isEdit ? $e($t['name']) : 'Nuevo tipo de reunión' ?></h1>

<form method="POST" action="<?= $e($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4"
      x-data='{
        questions: <?= json_encode(array_map(fn($q) => ["label"=>$q["label"]??"","type"=>$q["type"]??"text","required"=>!empty($q["required"]),"options"=>$q["options"]??""], $questions), JSON_HEX_APOS) ?>,
        addQuestion(){ this.questions.push({label:"",type:"text",required:false,options:""}); },
        removeQuestion(i){ this.questions.splice(i,1); }
      }'>
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <!-- Columna izquierda — datos básicos -->
    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad space-y-4">
            <h3 class="font-display font-bold text-[15px]">Información básica</h3>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Nombre *</label>
                <input name="name" required class="input" placeholder="Ej. Demo de producto · 30 min" value="<?= $e($t['name'] ?? '') ?>">
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Descripción</label>
                <textarea name="description" rows="3" class="input" style="height:auto;padding:12px 16px" placeholder="Qué incluye esta reunión, qué esperar, quién participa..."><?= $e($t['description'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Duración (min) *</label>
                    <input type="number" min="5" max="480" name="duration_minutes" required class="input" value="<?= (int)($t['duration_minutes'] ?? 30) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Slot cada (min)</label>
                    <input type="number" min="5" max="120" name="slot_step_minutes" class="input" value="<?= (int)($t['slot_step_minutes'] ?? 30) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Orden</label>
                    <input type="number" name="sort_order" class="input" value="<?= (int)($t['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Color</label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="color" name="color" value="<?= $e($t['color'] ?? '#7c5cff') ?>" class="w-10 h-10 rounded-lg border" style="border-color:var(--border)">
                        <?php foreach ($colors as $c): ?>
                            <button type="button" onclick="this.parentElement.querySelector('input[type=color]').value='<?= $c ?>'" class="w-7 h-7 rounded-lg" style="background:<?= $c ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Icono</label>
                    <select name="icon" class="input">
                        <?php foreach ($icons as $i): ?>
                            <option value="<?= $i ?>" <?= ($t['icon'] ?? 'video')===$i?'selected':'' ?>><?= $i ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card card-pad space-y-4">
            <h3 class="font-display font-bold text-[15px]">Ubicación</h3>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Tipo de ubicación</label>
                <select name="location_type" class="input">
                    <?php foreach (['virtual'=>'Virtual (Zoom / Meet / Teams)','phone'=>'Llamada telefónica','in_person'=>'Presencial','custom'=>'Custom'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= ($t['location_type'] ?? 'virtual')===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Detalle de ubicación</label>
                <input name="location_value" class="input" placeholder="Ej. Google Meet · enlace por email · Avenida X 123, oficina 4" value="<?= $e($t['location_value'] ?? '') ?>">
                <p class="text-[11px] text-ink-400 mt-1">Visible para el cliente en el email de confirmación.</p>
            </div>
        </div>

        <!-- Custom questions -->
        <div class="card card-pad space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-[15px]">Preguntas personalizadas</h3>
                <button type="button" @click="addQuestion()" class="btn btn-soft btn-sm"><i class="lucide lucide-plus"></i> Agregar</button>
            </div>
            <p class="text-[12px] text-ink-400">Información adicional que solicitarás al cliente al reservar.</p>
            <template x-for="(q, i) in questions" :key="i">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 p-3 rounded-xl" style="background:var(--bg);border:1px solid var(--border)">
                    <input type="text" :name="'questions[' + i + '][label]'" x-model="q.label" placeholder="Pregunta (ej. ¿Cuál es el tamaño de tu equipo?)" class="input md:col-span-5">
                    <select :name="'questions[' + i + '][type]'" x-model="q.type" class="input md:col-span-2">
                        <option value="text">Texto</option>
                        <option value="textarea">Párrafo</option>
                        <option value="number">Número</option>
                        <option value="phone">Teléfono</option>
                        <option value="select">Selección</option>
                    </select>
                    <input type="text" :name="'questions[' + i + '][options]'" x-model="q.options" x-show="q.type==='select'" placeholder="Opciones | separadas | por | pipes" class="input md:col-span-3">
                    <label class="md:col-span-1 inline-flex items-center gap-1 text-[12px]">
                        <input type="checkbox" :name="'questions[' + i + '][required]'" x-model="q.required" value="1"> Obl.
                    </label>
                    <button type="button" @click="removeQuestion(i)" class="md:col-span-1 admin-btn admin-btn-danger" style="height:38px"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                </div>
            </template>
            <template x-if="questions.length === 0">
                <p class="text-[12px] text-ink-400 italic">Sin preguntas — solo se piden los datos básicos (nombre, email, teléfono opcional).</p>
            </template>
        </div>
    </div>

    <!-- Columna derecha — programación y políticas -->
    <div class="space-y-4">
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Estado y host</h3>
            <label class="inline-flex items-center gap-2 text-[13px]">
                <input type="checkbox" name="is_active" value="1" <?= ($isEdit ? (int)$t['is_active'] : 1) ? 'checked' : '' ?>>
                <span>Activo (visible en página pública)</span>
            </label>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Host por defecto</label>
                <select name="default_host_id" class="input">
                    <option value="0">Sin host asignado</option>
                    <?php foreach ($hosts as $h): ?>
                        <option value="<?= (int)$h['id'] ?>" <?= ($t['default_host_id'] ?? 0)===(int)$h['id']?'selected':'' ?>><?= $e($h['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-ink-400 mt-1">Las reservas se asignan automáticamente a este usuario.</p>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Política de horarios</h3>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Aviso mínimo (horas)</label>
                <input type="number" min="0" max="720" name="min_notice_hours" class="input" value="<?= (int)($t['min_notice_hours'] ?? 4) ?>">
                <p class="text-[11px] text-ink-400 mt-1">No se puede reservar con menos de N horas de anticipación.</p>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Anticipación máxima (días)</label>
                <input type="number" min="1" max="365" name="max_advance_days" class="input" value="<?= (int)($t['max_advance_days'] ?? 60) ?>">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Buffer antes (min)</label>
                    <input type="number" min="0" max="240" name="buffer_before_minutes" class="input" value="<?= (int)($t['buffer_before_minutes'] ?? 0) ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Buffer después (min)</label>
                    <input type="number" min="0" max="240" name="buffer_after_minutes" class="input" value="<?= (int)($t['buffer_after_minutes'] ?? 15) ?>">
                </div>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]">Políticas y notificaciones</h3>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Requiere mi confirmación manual</span>
                <input type="checkbox" name="requires_confirmation" value="1" <?= !empty($t['requires_confirmation']) ? 'checked' : '' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Permitir cancelar al cliente</span>
                <input type="checkbox" name="allow_cancel" value="1" <?= ($isEdit ? (int)$t['allow_cancel'] : 1) ? 'checked' : '' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Permitir reprogramar al cliente</span>
                <input type="checkbox" name="allow_reschedule" value="1" <?= ($isEdit ? (int)$t['allow_reschedule'] : 1) ? 'checked' : '' ?>>
            </label>
            <label class="flex items-center justify-between gap-2 text-[13px]">
                <span>Enviar recordatorios</span>
                <input type="checkbox" name="send_reminders" value="1" <?= ($isEdit ? (int)$t['send_reminders'] : 1) ? 'checked' : '' ?>>
            </label>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Recordar a los (min) antes</label>
                <input type="number" min="0" max="2880" name="reminder_minutes" class="input" value="<?= (int)($t['reminder_minutes'] ?? 60) ?>">
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Redirigir tras reservar</label>
                <input type="url" name="redirect_url" class="input" placeholder="https://..." value="<?= $e($t['redirect_url'] ?? '') ?>">
            </div>
        </div>

        <div class="flex gap-2 sticky bottom-4">
            <a href="<?= $url('/t/' . $slug . '/meetings/types') ?>" class="btn btn-outline btn-sm flex-1">Cancelar</a>
            <button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-check"></i> <?= $isEdit ? 'Guardar' : 'Crear tipo' ?></button>
        </div>
    </div>
</form>
