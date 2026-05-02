<?php $slug = $tenant->slug; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <a href="<?= $url('/t/' . $slug . '/quotes') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver a cotizaciones</a>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Configuración de cotizaciones</h1>
        <p class="text-[12.5px] text-ink-400">Personalizá branding, ITBIS, plantillas, prefijos y datos de pago. Estos valores se usan por defecto en cada nueva cotización y aparecen en el PDF.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- BRANDING -->
    <form method="POST" enctype="multipart/form-data" action="<?= $url('/t/' . $slug . '/quotes/settings') ?>" class="lg:col-span-2 space-y-4">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-palette text-brand-600"></i> Branding del PDF y portal público</div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                <div class="sm:col-span-1">
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Logo</label>
                    <?php if (!empty($settings['logo_url'])): ?>
                        <div class="border border-[#ececef] rounded-xl p-3 mb-2 bg-[#fafafb] grid place-items-center" style="min-height:90px">
                            <img src="<?= $e($settings['logo_url']) ?>" alt="logo" style="max-height:80px;max-width:100%">
                        </div>
                        <input type="hidden" name="logo_url" value="<?= $e($settings['logo_url']) ?>">
                    <?php endif; ?>
                    <input type="file" name="logo_file" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="text-[11px]">
                    <p class="text-[10.5px] text-ink-400 mt-1">JPG, PNG, WebP o SVG · max 2MB recomendado.</p>
                </div>
                <div class="sm:col-span-2 grid grid-cols-2 gap-3">
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Razón social</label><input class="input" name="business_name" value="<?= $e($settings['business_name']) ?>"></div>
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">RNC / ID fiscal</label><input class="input" name="business_doc" value="<?= $e($settings['business_doc']) ?>"></div>
                    <div class="col-span-2"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Dirección</label><input class="input" name="business_address" value="<?= $e($settings['business_address']) ?>"></div>
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Teléfono</label><input class="input" name="business_phone" value="<?= $e($settings['business_phone']) ?>"></div>
                    <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email</label><input type="email" class="input" name="business_email" value="<?= $e($settings['business_email']) ?>"></div>
                    <div class="col-span-2"><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Sitio web</label><input class="input" name="business_website" value="<?= $e($settings['business_website']) ?>"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-[#ececef]">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Color primario</label><input type="color" class="input" name="primary_color" value="<?= $e($settings['primary_color']) ?>" style="height:38px"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Color de acento</label><input type="color" class="input" name="accent_color" value="<?= $e($settings['accent_color']) ?>" style="height:38px"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Moneda</label><input class="input" name="currency" value="<?= $e($settings['currency']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Símbolo</label><input class="input" name="currency_symbol" value="<?= $e($settings['currency_symbol']) ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-hash text-amber-600"></i> Numeración y defaults</div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Prefijo</label><input class="input" name="prefix" value="<?= $e($settings['prefix']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Próximo número</label><input type="number" min="1" class="input" name="next_number" value="<?= (int)$settings['next_number'] ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Decimales</label><input type="number" min="0" max="4" class="input" name="decimals" value="<?= (int)$settings['decimals'] ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Validez (días)</label><input type="number" min="1" class="input" name="validity_days" value="<?= (int)$settings['validity_days'] ?>"></div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Impuesto por defecto</label>
                    <select class="input" name="default_tax_id">
                        <option value="0">Sin impuesto</option>
                        <?php foreach ($taxes as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= (int)$settings['default_tax_id']===(int)$t['id']?'selected':'' ?>><?= $e($t['name']) ?> · <?= $e($t['rate']) ?>%</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Descuento por defecto (%)</label><input type="number" min="0" max="100" step="0.01" class="input" name="default_discount_pct" value="<?= (float)$settings['default_discount_pct'] ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-text text-emerald-600"></i> Textos por defecto</div>
            <div class="space-y-3">
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Texto de introducción</label><textarea class="input" name="intro_text" rows="3"><?= $e($settings['intro_text']) ?></textarea></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Términos y condiciones</label><textarea class="input" name="terms_text" rows="6"><?= $e($settings['terms_text']) ?></textarea></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Datos de pago (aparece en el PDF y portal público)</label><textarea class="input" name="bank_info" rows="4" placeholder="Banco Popular · Cta. Corriente 123-456-789&#10;A nombre de Acme SRL&#10;RNC: 1-23-45678-9"><?= $e($settings['bank_info']) ?></textarea></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Footer del PDF</label><input class="input" name="footer_text" value="<?= $e($settings['footer_text']) ?>"></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-pen-tool text-violet-600"></i> Firma y notificaciones</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="inline-flex items-center gap-2 text-[12px] cursor-pointer"><input type="checkbox" name="show_signature" value="1" <?= (int)$settings['show_signature']===1?'checked':'' ?> class="rounded"> Mostrar línea de firma en el PDF</label>
                </div>
                <div></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Nombre que firma</label><input class="input" name="signature_name" value="<?= $e($settings['signature_name']) ?>"></div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Cargo</label><input class="input" name="signature_role" value="<?= $e($settings['signature_role']) ?>"></div>
                <div>
                    <label class="inline-flex items-center gap-2 text-[12px] cursor-pointer"><input type="checkbox" name="notify_on_accept" value="1" <?= (int)$settings['notify_on_accept']===1?'checked':'' ?> class="rounded"> Avisar por email cuando un cliente acepte</label>
                </div>
                <div><label class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400 block mb-1">Email para notificaciones</label><input type="email" class="input" name="notify_email" value="<?= $e($settings['notify_email']) ?>" placeholder="ventas@empresa.com"></div>
            </div>
        </div>

        <button class="btn btn-primary w-full"><i class="lucide lucide-save"></i> Guardar configuración</button>
    </form>

    <!-- TAXES + TEMPLATES -->
    <div class="space-y-4">
        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-percent text-rose-600"></i> Impuestos</div>
            <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/settings/taxes') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-2 mb-3">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input class="input sm:col-span-2" name="name" placeholder="Nombre (ej: ITBIS 18%)" required>
                <input type="number" step="0.001" min="0" max="100" class="input" name="rate" placeholder="%" required>
                <label class="text-[11px] inline-flex items-center gap-1"><input type="checkbox" name="is_default" value="1" class="rounded"> Default</label>
                <button class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i></button>
            </form>
            <div class="space-y-1.5">
                <?php foreach ($taxes as $t): ?>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/settings/taxes/' . (int)$t['id']) ?>" class="flex items-center gap-2 text-[12px]">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input class="input" name="name" value="<?= $e($t['name']) ?>" style="max-width:160px">
                        <input type="number" step="0.001" class="input" name="rate" value="<?= $e($t['rate']) ?>" style="width:80px">
                        <span class="text-ink-400">%</span>
                        <label class="inline-flex items-center gap-1 text-[10.5px]"><input type="checkbox" name="is_default" value="1" <?= (int)$t['is_default']===1?'checked':'' ?> class="rounded">Def</label>
                        <label class="inline-flex items-center gap-1 text-[10.5px]"><input type="checkbox" name="is_active" value="1" <?= (int)$t['is_active']===1?'checked':'' ?> class="rounded">On</label>
                        <button class="text-brand-700"><i class="lucide lucide-save text-[13px]"></i></button>
                        <button type="button" onclick="if(confirm('¿Eliminar?')){this.closest('form').nextElementSibling.submit()}" class="text-rose-600"><i class="lucide lucide-x text-[13px]"></i></button>
                    </form>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/settings/taxes/' . (int)$t['id'] . '/delete') ?>" class="hidden">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card card-pad">
            <div class="font-display font-bold text-[14.5px] mb-3 flex items-center gap-2"><i class="lucide lucide-bookmark text-violet-600"></i> Plantillas</div>
            <p class="text-[11.5px] text-ink-400 mb-3">Crea plantillas con items prearmados para acelerar nuevas cotizaciones. Cargalas desde el formulario de creación.</p>
            <div class="space-y-2">
                <?php if (empty($templates)): ?>
                    <div class="text-[12px] text-ink-400 py-3 text-center">Aún no hay plantillas.</div>
                <?php else: foreach ($templates as $tpl): ?>
                    <div class="border border-[#ececef] rounded-xl p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-display font-bold text-[13px]"><?= $e($tpl['name']) ?></div>
                                <div class="text-[11px] text-ink-400"><?= $e($tpl['description'] ?? '—') ?></div>
                            </div>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/settings/templates/' . (int)$tpl['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar plantilla?')">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                <button class="text-rose-600"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <details class="mt-3">
                <summary class="text-[12px] text-brand-700 font-semibold cursor-pointer">+ Crear plantilla nueva</summary>
                <form method="POST" action="<?= $url('/t/' . $slug . '/quotes/settings/templates') ?>" class="space-y-2 mt-2 pt-2 border-t border-[#ececef]">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input class="input" name="name" placeholder="Nombre de la plantilla *" required>
                    <input class="input" name="description" placeholder="Descripción corta">
                    <textarea class="input text-[11.5px]" name="intro" rows="2" placeholder="Texto de intro"></textarea>
                    <textarea class="input text-[11.5px]" name="terms" rows="3" placeholder="Términos y condiciones"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <input class="input" name="currency" placeholder="DOP" value="<?= $e($settings['currency']) ?>">
                        <input type="number" min="1" class="input" name="validity_days" value="<?= (int)$settings['validity_days'] ?>">
                    </div>
                    <p class="text-[10.5px] text-ink-400">Los items se pueden agregar editando la plantilla luego (próximamente UI inline). Por ahora se crea sin items.</p>
                    <button class="btn btn-primary btn-sm w-full"><i class="lucide lucide-plus"></i> Crear plantilla</button>
                </form>
            </details>
        </div>
    </div>
</div>
