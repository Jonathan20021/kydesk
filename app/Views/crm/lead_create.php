<?php $slug = $tenant->slug; ?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver a leads</a>
    <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Nuevo lead</h1>
    <p class="text-[12.5px] text-ink-400">Capturá los datos esenciales · podés agregar oportunidades, actividades y notas más adelante.</p>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <div class="lg:col-span-2 space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-user text-brand-600"></i> Datos del contacto</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre *</label><input class="input" name="first_name" required></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Apellido</label><input class="input" name="last_name"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email</label><input class="input" type="email" name="email"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Teléfono</label><input class="input" name="phone"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">WhatsApp</label><input class="input" name="whatsapp"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Cargo</label><input class="input" name="job_title" placeholder="CEO, CTO, Compras…"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-building-2 text-amber-600"></i> Empresa</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Empresa registrada</label>
                    <select class="input" name="company_id">
                        <option value="0">— Sin vincular —</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre de empresa (libre)</label><input class="input" name="company_name" placeholder="Ej: Acme SA"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Sitio web</label><input class="input" name="website" placeholder="https://"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Industria</label><input class="input" name="industry"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">País</label><input class="input" name="country"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Ciudad</label><input class="input" name="city"></div>
                <div class="sm:col-span-2"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Dirección</label><input class="input" name="address"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-message-square-text text-emerald-600"></i> Notas</div>
            <textarea class="input" name="notes" rows="4" placeholder="Contexto, preguntas pendientes, productos de interés…"></textarea>
            <label class="inline-flex items-center gap-2 mt-3 text-[12px] cursor-pointer"><input type="checkbox" name="consent_marketing" value="1" class="rounded border-ink-300"> El lead consintió recibir comunicaciones comerciales</label>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-target text-rose-600"></i> Calificación</div>
            <div class="space-y-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Estado</label>
                    <select class="input" name="status">
                        <?php foreach (['new'=>'Nuevo','contacted'=>'Contactado','qualified'=>'Calificado','proposal'=>'Propuesta','negotiation'=>'Negociación','customer'=>'Cliente'] as $k=>$v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Rating</label>
                    <select class="input" name="rating">
                        <option value="cold">Frío</option>
                        <option value="warm" selected>Tibio</option>
                        <option value="hot">Caliente</option>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Score (0-100)</label><input type="number" class="input" name="score" value="0" min="0" max="100"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Valor estimado</label><input type="number" step="0.01" class="input" name="estimated_value" value="0"></div>
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Moneda</label><input class="input" name="currency" value="USD"></div>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Cierre estimado</label><input type="date" class="input" name="expected_close_on"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Próximo follow-up</label><input type="datetime-local" class="input" name="next_followup_at"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-radar text-sky-600"></i> Origen y propietario</div>
            <div class="space-y-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Origen</label>
                    <select class="input" name="source_id">
                        <option value="0">— Sin definir —</option>
                        <?php foreach ($sources as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= $e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Detalle del origen</label><input class="input" name="source_detail" placeholder="Campaña, evento, partner…"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Owner</label>
                    <select class="input" name="owner_id">
                        <option value="0">— Asignarme yo —</option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= (int)$o['id'] ?>"><?= $e($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <?php if (!empty($tags)): ?>
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-tags text-violet-600"></i> Tags</div>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($tags as $t): ?>
                    <label class="inline-flex items-center gap-1.5 text-[12px] font-semibold px-2.5 py-1 rounded-full border cursor-pointer" style="background:<?= $e($t['color']) ?>10;border-color:<?= $e($t['color']) ?>33;color:<?= $e($t['color']) ?>">
                        <input type="checkbox" name="tag_ids[]" value="<?= (int)$t['id'] ?>" class="rounded">
                        <?= $e($t['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <button class="btn btn-primary w-full"><i class="lucide lucide-save"></i> Crear lead</button>
    </div>
</form>
