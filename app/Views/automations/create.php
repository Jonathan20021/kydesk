<?php $slug = $tenant->slug; ?>

<div class="flex items-center gap-3">
    <a href="<?= $url('/t/' . $slug . '/automations') ?>" class="btn btn-ghost btn-sm"><i class="lucide lucide-arrow-left text-[13px]"></i></a>
    <div>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Nueva automatización</h1>
        <p class="text-[13px] text-ink-400">Define un disparador, condiciones opcionales y acciones a ejecutar</p>
    </div>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/automations') ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <!-- 1. Nombre + disparador -->
    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-[11px] font-bold mr-2">1</span>Información</h3>
            <div>
                <label class="text-[11.5px] font-semibold text-ink-700">Nombre</label>
                <input name="name" required maxlength="120" class="input mt-1" placeholder="Ej: Auto-asignar tickets urgentes a Carlos">
            </div>
            <div>
                <label class="text-[11.5px] font-semibold text-ink-700">Descripción (opcional)</label>
                <input name="description" maxlength="255" class="input mt-1" placeholder="¿Qué hace esta automatización?">
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-[11px] font-bold mr-2">2</span>Disparador</h3>
            <div>
                <label class="text-[11.5px] font-semibold text-ink-700">¿Cuándo se ejecuta?</label>
                <select name="trigger_event" class="input mt-1">
                    <option value="ticket.created">Cuando se crea un ticket</option>
                    <option value="ticket.updated">Cuando se actualiza un ticket</option>
                    <option value="ticket.sla_breach">Cuando un SLA está en riesgo / brechado</option>
                    <option value="ticket.escalated">Cuando un ticket es escalado</option>
                    <option value="ticket.resolved">Cuando un ticket se resuelve</option>
                </select>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-[11px] font-bold mr-2">3</span>Condiciones <span class="text-[11.5px] text-ink-400 font-normal">(opcional · todas deben cumplirse)</span></h3>
            <div class="grid grid-cols-1 md:grid-cols-<?= !empty($departments) ? '4' : '3' ?> gap-3">
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Prioridad</label>
                    <select name="cond_priority" class="input mt-1">
                        <option value="">Cualquiera</option>
                        <option value="urgent">Urgente</option>
                        <option value="high">Alta</option>
                        <option value="medium">Media</option>
                        <option value="low">Baja</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Categoría</label>
                    <select name="cond_category_id" class="input mt-1">
                        <option value="0">Cualquiera</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($departments)): ?>
                    <div>
                        <label class="text-[11.5px] font-semibold text-ink-700">Departamento</label>
                        <select name="cond_department_id" class="input mt-1">
                            <option value="0">Cualquiera</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['id'] ?>"><?= $e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Palabra clave en asunto</label>
                    <input name="cond_keyword" maxlength="80" class="input mt-1" placeholder="Ej: VPN">
                </div>
            </div>
        </div>

        <div class="card card-pad space-y-3">
            <h3 class="font-display font-bold text-[15px]"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500 text-white text-[11px] font-bold mr-2">4</span>Acciones <span class="text-[11.5px] text-ink-400 font-normal">(qué ejecutar)</span></h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Cambiar prioridad a</label>
                    <select name="act_set_priority" class="input mt-1">
                        <option value="">— sin cambio —</option>
                        <option value="urgent">Urgente</option>
                        <option value="high">Alta</option>
                        <option value="medium">Media</option>
                        <option value="low">Baja</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Cambiar estado a</label>
                    <select name="act_set_status" class="input mt-1">
                        <option value="">— sin cambio —</option>
                        <option value="open">Abierto</option>
                        <option value="in_progress">En progreso</option>
                        <option value="on_hold">En espera</option>
                        <option value="resolved">Resuelto</option>
                        <option value="closed">Cerrado</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Asignar a</label>
                    <select name="act_assign_to" class="input mt-1">
                        <option value="0">— sin cambio —</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"><?= $e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($departments)): ?>
                    <div>
                        <label class="text-[11.5px] font-semibold text-ink-700 flex items-center gap-1.5">Enrutar a departamento <span class="text-[9.5px] uppercase tracking-[0.14em] px-1 py-0.5 rounded-full" style="background:#eff6ff;color:#1d4ed8">PRO</span></label>
                        <select name="act_assign_to_department" class="input mt-1">
                            <option value="0">— sin cambio —</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['id'] ?>"><?= $e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="text-[11.5px] font-semibold text-ink-700">Notificar por email a</label>
                    <input name="act_notify_email" type="email" class="input mt-1" placeholder="alerts@empresa.com">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[11.5px] font-semibold text-ink-700">Agregar comentario interno</label>
                    <textarea name="act_add_comment" rows="2" class="input mt-1" placeholder="Ej: Auto-clasificado por la regla X"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[15px] mb-3">Estado</h3>
            <label class="flex items-center gap-2 text-[13px]">
                <input type="checkbox" name="active" value="1" checked>
                Activar esta automatización al crearla
            </label>
            <div class="mt-4">
                <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear automatización</button>
                <a href="<?= $url('/t/' . $slug . '/automations') ?>" class="btn btn-ghost w-full mt-2">Cancelar</a>
            </div>
        </div>
        <div class="card card-pad bg-brand-50 border-brand-200">
            <div class="flex items-start gap-2">
                <i class="lucide lucide-lightbulb text-brand-700 text-[16px] mt-0.5"></i>
                <div class="text-[12.5px] text-ink-700 leading-relaxed">
                    <strong>Tip:</strong> Las automatizaciones se evalúan en orden. Si dejas todas las condiciones vacías, la regla se aplica a TODOS los tickets que coincidan con el disparador.
                </div>
            </div>
        </div>
    </div>
</form>
