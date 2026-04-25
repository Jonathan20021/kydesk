<?php $t = $tenant; $brand = $t->data['primary_color'] ?? '#7c5cff'; ?>
<nav class="bg-white border-b border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6 h-[68px] flex items-center justify-between">
        <a href="<?= $url('/portal/' . $t->slug) ?>" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold" style="background:<?= $e($brand) ?>;box-shadow:0 4px 12px -2px <?= $e($brand) ?>66"><?= strtoupper(substr($t->name,0,1)) ?></div>
            <div>
                <div class="font-display font-bold text-[14px]"><?= $e($t->name) ?></div>
                <div class="text-[11px] text-ink-400">Centro de soporte</div>
            </div>
        </a>
        <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="text-[13px] text-ink-500 hover:text-ink-900 inline-flex items-center gap-1.5"><i class="lucide lucide-book-open text-[14px]"></i> Base de conocimiento</a>
    </div>
</nav>

<section class="py-14 relative overflow-hidden">
    <div class="absolute inset-x-0 top-0 h-[400px] pointer-events-none -z-10" style="background:radial-gradient(ellipse 60% 70% at 50% 0%, <?= $e($brand) ?>14, transparent 70%)"></div>

    <div class="max-w-[760px] mx-auto px-6">
        <a href="<?= $url('/portal/' . $t->slug) ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition mb-5"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver al portal</a>

        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em] mb-4" style="background:<?= $e($brand) ?>15;color:<?= $e($brand) ?>;border:1px solid <?= $e($brand) ?>30"><i class="lucide lucide-life-buoy text-[12px]"></i> NUEVO TICKET</div>

        <h1 class="font-display font-extrabold text-[40px] tracking-[-0.025em] leading-[1.05]">Cuéntanos qué pasó.</h1>
        <p class="mt-4 text-[15px] text-ink-500 max-w-lg">Te respondemos a la brevedad por email. Mientras tanto guardarás un link único para seguir tu caso.</p>

        <?php if (!empty($company)): ?>
            <div class="mt-7 flex items-center gap-3.5 p-4 rounded-2xl" style="background:<?= $e($brand) ?>0d;border:1px solid <?= $e($brand) ?>30">
                <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($brand) ?>;color:white"><i class="lucide lucide-building-2 text-[16px]"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Reportando como</div>
                    <div class="font-display font-bold text-[14.5px] tracking-[-0.015em]"><?= $e($company['name']) ?></div>
                    <?php if (!empty($company['industry'])): ?><div class="text-[11.5px] text-ink-500"><?= $e($company['industry']) ?></div><?php endif; ?>
                </div>
                <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="text-[11.5px] text-ink-500 hover:text-ink-900">Cambiar</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $url('/portal/' . $t->slug . '/new') ?>" class="card card-pad mt-6 space-y-5">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <?php if (!empty($company)): ?>
                <input type="hidden" name="company_id" value="<?= (int)$company['id'] ?>">
            <?php endif; ?>

            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-3">Tus datos</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="label">Nombre <span class="text-rose-500">*</span></label><input name="name" required placeholder="María García" class="input"></div>
                    <div><label class="label">Email <span class="text-rose-500">*</span></label><input name="email" type="email" required placeholder="maria@empresa.com" class="input"></div>
                    <div class="md:col-span-2"><label class="label">Teléfono</label><input name="phone" placeholder="+1 809 000 0000" class="input"></div>
                </div>
            </div>

            <div class="pt-5 border-t border-[#ececef]">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-3">Detalle del ticket</div>
                <div class="space-y-4">
                    <div><label class="label">Asunto <span class="text-rose-500">*</span></label><input name="subject" required placeholder="Ej: VPN se desconecta cada 10 minutos" class="input"></div>
                    <div><label class="label">Descripción <span class="text-rose-500">*</span></label><textarea name="description" rows="6" required placeholder="Describe lo que está pasando, cuándo empezó y cualquier paso que ya hayas intentado…" class="input"></textarea></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Categoría</label>
                            <select name="category_id" class="input">
                                <option value="0">Selecciona…</option>
                                <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="label">Prioridad</label>
                            <select name="priority" class="input">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5 border-t border-[#ececef] flex items-center justify-between gap-3 flex-wrap">
                <p class="text-[11.5px] text-ink-400 inline-flex items-center gap-1.5"><i class="lucide lucide-shield-check text-[13px] text-emerald-600"></i> Tus datos se transmiten cifrados</p>
                <button class="btn btn-lg" style="background:<?= $e($brand) ?>;color:white;box-shadow:0 12px 28px -8px <?= $e($brand) ?>80"><i class="lucide lucide-send"></i> Enviar ticket</button>
            </div>
        </form>

        <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
            <?php foreach ([['zap','Respuesta 24h','En la mayoría de casos'],['shield-check','Privado','Solo tú accedes con tu link'],['message-circle','Sin registro','No necesitás cuenta']] as [$ic,$ti,$de]): ?>
                <div class="p-4 rounded-2xl bg-white border border-[#ececef]">
                    <div class="w-9 h-9 rounded-xl mx-auto grid place-items-center mb-2" style="background:<?= $e($brand) ?>1a;color:<?= $e($brand) ?>"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                    <div class="font-display font-bold text-[13px]"><?= $ti ?></div>
                    <div class="text-[11.5px] mt-0.5 text-ink-400"><?= $de ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
