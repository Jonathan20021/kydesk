<?php $slug = $tenant->slug; ?>

<div class="flex items-center gap-2 text-[12px] text-ink-400 mb-1">
    <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="hover:text-ink-700">Reuniones</a> /
    <span>Agendar manualmente</span>
</div>
<h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em] mb-5">Agendar reunión manualmente</h1>

<form method="POST" action="<?= $url('/t/' . $slug . '/meetings/manual') ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad space-y-4">
            <h3 class="font-display font-bold text-[15px]">Cliente</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Nombre completo *</label>
                    <input name="customer_name" required class="input" placeholder="Juan Pérez">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Email *</label>
                    <input type="email" name="customer_email" required class="input" placeholder="juan@empresa.com">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Teléfono</label>
                    <input name="customer_phone" class="input" placeholder="+1 809 555-0000">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Empresa (texto libre)</label>
                    <input name="customer_company" class="input" placeholder="Acme S.A.">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Vincular a empresa existente</label>
                    <select name="company_id" class="input">
                        <option value="0">— Sin vincular —</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card card-pad space-y-4">
            <h3 class="font-display font-bold text-[15px]">Detalles de la reunión</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Tipo</label>
                    <select name="meeting_type_id" class="input">
                        <option value="0">Personalizado</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" data-duration="<?= (int)$t['duration_minutes'] ?>"><?= $e($t['name']) ?> (<?= (int)$t['duration_minutes'] ?> min)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Host</label>
                    <select name="host_user_id" class="input">
                        <option value="0">— Sin host —</option>
                        <?php foreach ($hosts as $h): ?>
                            <option value="<?= (int)$h['id'] ?>"><?= $e($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Fecha *</label>
                    <input type="date" name="date" required class="input" min="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Hora *</label>
                    <input type="time" name="time" required class="input">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Duración (min)</label>
                    <input type="number" name="duration_minutes" min="5" max="480" value="30" class="input">
                </div>
                <div>
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Asunto</label>
                    <input name="subject" class="input" placeholder="Demo de producto, Reunión técnica...">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">URL de la reunión (Zoom, Meet...)</label>
                    <input type="url" name="meeting_url" class="input" placeholder="https://meet.google.com/...">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[12px] font-semibold text-ink-700 mb-1 block">Notas / Mensaje del cliente</label>
                    <textarea name="notes" rows="3" class="input" style="height:auto;padding:12px 16px"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-2">Confirmación</h3>
            <p class="text-[12.5px] text-ink-500 mb-3">Al guardar, la reunión queda como <strong>confirmada</strong> y se envía al cliente un email con los detalles y un enlace para gestionarla.</p>
            <div class="flex gap-2">
                <a href="<?= $url('/t/' . $slug . '/meetings') ?>" class="btn btn-outline btn-sm flex-1">Cancelar</a>
                <button class="btn btn-primary btn-sm flex-1"><i class="lucide lucide-check"></i> Crear</button>
            </div>
        </div>
    </div>
</form>
