<?php
$t = $tenant;
$brand = $t->data['primary_color'] ?? '#7c5cff';
$brandRgb = sscanf($brand, "#%02x%02x%02x");
$rgbStr = $brandRgb ? implode(',', $brandRgb) : '124,92,255';
?>

<!-- Top nav (estilo landing pill) -->
<nav class="fixed top-4 inset-x-0 z-50 px-4">
    <div class="nav-land">
        <div class="nav-land-inner">
            <a href="<?= $url('/portal/' . $t->slug) ?>" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px]" style="background:<?= $e($brand) ?>;box-shadow:0 6px 14px -4px rgba(<?= $rgbStr ?>,.45)"><?= strtoupper(substr($t->name,0,1)) ?></div>
                <div class="leading-tight">
                    <div class="font-display font-extrabold text-[15px] tracking-[-0.015em]"><?= $e($t->name) ?></div>
                    <div class="text-[10px] text-ink-400 uppercase tracking-[0.12em]">Centro de soporte</div>
                </div>
            </a>
            <div class="hidden lg:flex items-center gap-0.5 text-[13px] font-medium text-ink-500 ml-4">
                <a href="<?= $url('/portal/' . $t->slug) ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Inicio</a>
                <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Base de conocimiento</a>
                <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="px-3 py-1.5 rounded-full hover:bg-[#f3f4f6] hover:text-ink-900 transition">Crear ticket</a>
            </div>
            <div class="flex items-center gap-1.5 ml-auto">
                <a href="https://kydesk.kyrosrd.com" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-ink-400 hover:text-ink-900 transition">
                    Powered by
                    <span class="font-display font-bold text-ink-900">Kydesk</span>
                </a>
                <a href="<?= $url('/auth/login') ?>" class="btn btn-ghost btn-sm">Acceso equipo</a>
            </div>
        </div>
    </div>
</nav>
<div class="h-[88px]"></div>

<section class="py-12 relative overflow-hidden">
    <!-- Aurora background -->
    <div class="absolute inset-x-0 top-0 h-[500px] pointer-events-none -z-10 overflow-hidden">
        <div class="absolute" style="width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(<?= $rgbStr ?>,.18),transparent 70%);top:-200px;left:-100px;filter:blur(60px)"></div>
        <div class="absolute" style="width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(<?= $rgbStr ?>,.12),transparent 70%);top:-100px;right:-100px;filter:blur(60px)"></div>
        <div class="absolute inset-0" style="background-image:linear-gradient(rgba(<?= $rgbStr ?>,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(<?= $rgbStr ?>,.06) 1px, transparent 1px); background-size: 64px 64px; mask-image: radial-gradient(ellipse 60% 50% at 50% 0%, black 30%, transparent 80%); -webkit-mask-image: radial-gradient(ellipse 60% 50% at 50% 0%, black 30%, transparent 80%);"></div>
    </div>

    <div class="max-w-[860px] mx-auto px-6">
        <a href="<?= $url('/portal/' . $t->slug) ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition mb-5"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver al portal</a>

        <!-- HERO -->
        <div class="text-center max-w-2xl mx-auto mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10.5px] font-bold uppercase tracking-[0.16em] mb-5" style="background:rgba(<?= $rgbStr ?>,.12);color:<?= $e($brand) ?>;border:1px solid rgba(<?= $rgbStr ?>,.25)"><i class="lucide lucide-life-buoy text-[12px]"></i> Crear ticket de soporte</div>
            <h1 class="font-display font-extrabold tracking-[-0.025em] leading-[1.05]" style="font-size:clamp(2rem,4vw + 1rem,3.2rem)">Cuéntanos qué pasó.</h1>
            <p class="mt-4 text-[15px] text-ink-500 max-w-lg mx-auto leading-relaxed">Te respondemos en menos de 24 horas por email. Mientras tanto recibirás un link único para seguir tu caso.</p>
        </div>

        <!-- STEPPER -->
        <div x-data="ticketForm()" x-init="init()" class="space-y-5">

            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-2 mb-7">
                <template x-for="(label, idx) in steps" :key="idx">
                    <div class="flex items-center">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full grid place-items-center text-[12px] font-bold transition-all" :class="step > idx ? 'bg-emerald-500 text-white' : (step === idx ? 'text-white' : 'bg-[#ececef] text-ink-400')" :style="step === idx ? 'background:<?= $e($brand) ?>;box-shadow:0 6px 14px -4px rgba(<?= $rgbStr ?>,.5)' : ''">
                                <i x-show="step > idx" class="lucide lucide-check text-[14px]"></i>
                                <span x-show="step <= idx" x-text="idx + 1"></span>
                            </div>
                            <span class="hidden sm:inline text-[12px] font-semibold" :class="step >= idx ? 'text-ink-900' : 'text-ink-400'" x-text="label"></span>
                        </div>
                        <div x-show="idx < steps.length - 1" class="mx-3 w-10 h-px" :class="step > idx ? 'bg-emerald-500' : 'bg-[#ececef]'"></div>
                    </div>
                </template>
            </div>

            <?php if (!empty($company)): ?>
                <div class="flex items-center gap-3.5 p-4 rounded-2xl" style="background:<?= $e($brand) ?>0d;border:1px solid <?= $e($brand) ?>30">
                    <div class="w-11 h-11 rounded-xl grid place-items-center" style="background:<?= $e($brand) ?>;color:white;box-shadow:0 6px 14px -4px rgba(<?= $rgbStr ?>,.4)"><i class="lucide lucide-building-2 text-[16px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em]" style="color:<?= $e($brand) ?>">Reportando como</div>
                        <div class="font-display font-bold text-[15px] tracking-[-0.015em] mt-0.5"><?= $e($company['name']) ?></div>
                        <?php if (!empty($company['industry'])): ?><div class="text-[11.5px] text-ink-500"><?= $e($company['industry']) ?></div><?php endif; ?>
                    </div>
                    <a href="<?= $url('/portal/' . $t->slug . '/new') ?>" class="text-[11.5px] text-ink-500 hover:text-ink-900 inline-flex items-center gap-1"><i class="lucide lucide-x text-[12px]"></i> Cambiar</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= $url('/portal/' . $t->slug . '/new') ?>" @submit="submitting=true" enctype="multipart/form-data" class="rounded-3xl bg-white border border-[#ececef] overflow-hidden" style="box-shadow:0 30px 60px -20px rgba(<?= $rgbStr ?>,.18)">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <?php if (!empty($company)): ?>
                    <input type="hidden" name="company_id" value="<?= (int)$company['id'] ?>">
                <?php endif; ?>

                <!-- STEP 1: Tus datos -->
                <div x-show="step === 0" class="p-7 sm:p-9 space-y-5" x-transition>
                    <div class="flex items-center gap-3 pb-2">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($brand) ?>15;color:<?= $e($brand) ?>"><i class="lucide lucide-user-round text-[18px]"></i></div>
                        <div>
                            <h2 class="font-display font-bold text-[18px] tracking-[-0.02em]">¿Quién nos contacta?</h2>
                            <p class="text-[12.5px] text-ink-500">Solo necesitamos lo básico para responderte</p>
                        </div>
                    </div>

                    <?php if (!empty($contacts)): ?>
                        <!-- Contact picker (existing contacts of the company) -->
                        <div class="rounded-2xl p-4" style="background:<?= $e($brand) ?>0a;border:1px dashed <?= $e($brand) ?>40">
                            <div class="flex items-center gap-2 mb-2.5">
                                <i class="lucide lucide-users text-[14px]" style="color:<?= $e($brand) ?>"></i>
                                <div class="text-[11.5px] font-bold uppercase tracking-[0.12em]" style="color:<?= $e($brand) ?>">¿Ya nos contactaste antes?</div>
                            </div>
                            <p class="text-[12px] text-ink-500 mb-3">Selecciona tu nombre para autocompletar el formulario, o escribe tus datos manualmente.</p>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($contacts as $ct): ?>
                                    <button type="button"
                                        @click="pickContact(<?= htmlspecialchars(json_encode([
                                            'name' => $ct['name'],
                                            'email' => $ct['email'],
                                            'phone' => $ct['phone'] ?? '',
                                        ]), ENT_QUOTES) ?>)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[12px] font-semibold bg-white border transition hover:-translate-y-0.5"
                                        style="border-color:<?= $e($brand) ?>40;color:<?= $e($brand) ?>">
                                        <i class="lucide lucide-user-check text-[12px]"></i>
                                        <?= $e($ct['name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <p x-show="form.name && form.email" class="text-[11.5px] mt-3 inline-flex items-center gap-1" style="color:#16a34a">
                                <i class="lucide lucide-check-circle text-[12px]"></i> Datos cargados desde contacto existente
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nombre <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i class="lucide lucide-user text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="name" x-model="form.name" required placeholder="María García" class="input pl-10">
                            </div>
                        </div>
                        <div>
                            <label class="label">Email <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i class="lucide lucide-mail text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="email" type="email" x-model="form.email" required placeholder="maria@empresa.com" class="input pl-10">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Teléfono <span class="text-[10.5px] text-ink-400 font-normal">(opcional)</span></label>
                            <div class="relative">
                                <i class="lucide lucide-phone text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="phone" x-model="form.phone" placeholder="+1 809 000 0000" class="input pl-10">
                            </div>
                            <p class="text-[11px] text-ink-400 mt-1.5"><i class="lucide lucide-info text-[11px]"></i> Solo te llamaremos si el caso es urgente</p>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Detalle -->
                <div x-show="step === 1" class="p-7 sm:p-9 space-y-5" x-transition x-cloak>
                    <div class="flex items-center gap-3 pb-2">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($brand) ?>15;color:<?= $e($brand) ?>"><i class="lucide lucide-message-square-text text-[18px]"></i></div>
                        <div>
                            <h2 class="font-display font-bold text-[18px] tracking-[-0.02em]">¿Qué está pasando?</h2>
                            <p class="text-[12.5px] text-ink-500">Mientras más detalle nos des, más rápido podremos ayudarte</p>
                        </div>
                    </div>

                    <div>
                        <label class="label">Asunto <span class="text-rose-500">*</span></label>
                        <input name="subject" x-model="form.subject" required maxlength="200" placeholder="Ej: VPN se desconecta cada 10 minutos" class="input">
                        <div class="flex justify-between mt-1.5">
                            <p class="text-[11px] text-ink-400"><i class="lucide lucide-lightbulb text-[11px]"></i> Sé específico — ayuda a categorizar más rápido</p>
                            <span class="text-[11px] text-ink-400" x-text="(form.subject?.length || 0) + '/200'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="label">Descripción <span class="text-rose-500">*</span></label>
                        <textarea name="description" x-model="form.description" rows="6" required minlength="20" placeholder="Describe lo que está pasando, cuándo empezó y cualquier paso que ya hayas intentado…" class="input"></textarea>
                        <div class="flex justify-between mt-1.5">
                            <p class="text-[11px]" :class="(form.description?.length || 0) >= 20 ? 'text-emerald-600' : 'text-ink-400'">
                                <i class="lucide" :class="(form.description?.length || 0) >= 20 ? 'lucide-check-circle' : 'lucide-info'"></i>
                                <span x-show="(form.description?.length || 0) < 20">Mínimo 20 caracteres</span>
                                <span x-show="(form.description?.length || 0) >= 20">Excelente nivel de detalle</span>
                            </p>
                            <span class="text-[11px] text-ink-400" x-text="(form.description?.length || 0) + ' caracteres'"></span>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div x-data="{files: []}">
                        <label class="label flex items-center gap-1.5">
                            <i class="lucide lucide-paperclip text-[12px]"></i> Adjuntos <span class="text-[10.5px] text-ink-400 font-normal">(opcional · hasta 10 archivos · 25 MB c/u)</span>
                        </label>
                        <label class="block cursor-pointer rounded-2xl border-2 border-dashed transition" style="border-color:#e5e7eb"
                               :class="files.length > 0 ? 'border-solid' : ''"
                               onmouseover="this.style.borderColor='<?= $e($brand) ?>'" onmouseout="this.style.borderColor=files.length>0?'<?= $e($brand) ?>':'#e5e7eb'">
                            <input type="file" name="attachments[]" multiple class="sr-only"
                                @change="files = Array.from($event.target.files)">
                            <div x-show="files.length === 0" class="text-center py-6 px-4">
                                <i class="lucide lucide-upload-cloud text-[28px]" style="color:#9ca3af"></i>
                                <div class="text-[13px] mt-2 font-semibold text-ink-700">Click o arrastrá tus archivos aquí</div>
                                <div class="text-[11.5px] text-ink-400 mt-0.5">Imágenes, PDF, Word, Excel, ZIP — máx 25 MB cada uno</div>
                            </div>
                            <div x-show="files.length > 0" x-cloak class="p-3 space-y-1.5">
                                <template x-for="(f, i) in files" :key="i">
                                    <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-[#fafafb]">
                                        <i class="lucide text-[14px]" :class="f.type.startsWith('image/') ? 'lucide-image' : (f.type === 'application/pdf' ? 'lucide-file-text' : 'lucide-file')" style="color:#6b6b78"></i>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[12.5px] font-semibold truncate" x-text="f.name"></div>
                                            <div class="text-[10.5px] text-ink-400" x-text="(f.size < 1024*1024 ? Math.round(f.size/1024) + ' KB' : (f.size/(1024*1024)).toFixed(1) + ' MB')"></div>
                                        </div>
                                        <span x-show="f.size > 26214400" class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#b91c1c">Excede 25 MB</span>
                                    </div>
                                </template>
                                <button type="button" @click="files = []; $root.querySelector('input[type=file]').value = ''" class="text-[11px] text-ink-500 hover:text-ink-900 mt-1">Quitar todos</button>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Categoría</label>
                            <div class="relative">
                                <i class="lucide lucide-folder-tree text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <select name="category_id" x-model="form.category" class="input pl-10">
                                    <option value="0">Selecciona…</option>
                                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="label">Prioridad</label>
                            <div class="grid grid-cols-4 gap-1.5">
                                <?php foreach ([
                                    ['low','Baja','#94a3b8','arrow-down'],
                                    ['medium','Media','#3b82f6','minus'],
                                    ['high','Alta','#f59e0b','arrow-up'],
                                    ['urgent','Urgente','#ef4444','flame'],
                                ] as [$pVal,$pLbl,$pCol,$pIc]): ?>
                                    <label class="cursor-pointer text-center px-1 py-2 rounded-xl border transition"
                                           :class="form.priority === '<?= $pVal ?>' ? 'border-[<?= $pCol ?>] bg-[<?= $pCol ?>]/10' : 'border-[#ececef] hover:border-[<?= $pCol ?>]/50'"
                                           :style="form.priority === '<?= $pVal ?>' ? 'border-color:<?= $pCol ?>;background:<?= $pCol ?>15' : ''">
                                        <input type="radio" name="priority" value="<?= $pVal ?>" x-model="form.priority" class="hidden" <?= $pVal === 'medium' ? 'checked' : '' ?>>
                                        <i class="lucide lucide-<?= $pIc ?> text-[13px]" :style="form.priority === '<?= $pVal ?>' ? 'color:<?= $pCol ?>' : 'color:#8e8e9a'"></i>
                                        <div class="text-[10.5px] font-bold mt-0.5" :style="form.priority === '<?= $pVal ?>' ? 'color:<?= $pCol ?>' : 'color:#6b6b78'"><?= $pLbl ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Confirmar -->
                <div x-show="step === 2" class="p-7 sm:p-9 space-y-5" x-transition x-cloak>
                    <div class="flex items-center gap-3 pb-2">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:#dcfce7;color:#047857"><i class="lucide lucide-check-circle text-[18px]"></i></div>
                        <div>
                            <h2 class="font-display font-bold text-[18px] tracking-[-0.02em]">Revisá antes de enviar</h2>
                            <p class="text-[12.5px] text-ink-500">Confirmá que la información está correcta</p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-[#fafafb] border border-[#ececef] p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">De</div>
                                <div class="font-display font-bold text-[14px] mt-1" x-text="form.name || '—'"></div>
                                <div class="text-[12px] text-ink-500 font-mono" x-text="form.email || ''"></div>
                                <div class="text-[12px] text-ink-500 font-mono" x-text="form.phone || ''"></div>
                            </div>
                            <div>
                                <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Prioridad</div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-[0.1em]"
                                          :class="{'bg-slate-100 text-slate-600':form.priority==='low','bg-blue-100 text-blue-700':form.priority==='medium','bg-amber-100 text-amber-700':form.priority==='high','bg-rose-100 text-rose-700':form.priority==='urgent'}">
                                        <span x-text="{low:'Baja',medium:'Media',high:'Alta',urgent:'Urgente'}[form.priority] || 'Media'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Asunto</div>
                            <div class="font-display font-bold text-[14px] mt-1" x-text="form.subject || '—'"></div>
                        </div>
                        <div>
                            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-400">Descripción</div>
                            <div class="text-[13px] text-ink-700 mt-1 whitespace-pre-wrap" x-text="form.description || '—'"></div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 p-4 rounded-2xl" style="background:rgba(<?= $rgbStr ?>,.06);border:1px solid rgba(<?= $rgbStr ?>,.2)">
                        <i class="lucide lucide-shield-check text-[16px] mt-0.5 flex-shrink-0" style="color:<?= $e($brand) ?>"></i>
                        <div class="text-[12px] text-ink-700">
                            <strong>Cifrado y privado.</strong> Solo vos y nuestro equipo verán este ticket. Recibirás un link único en tu email para seguirlo.
                        </div>
                    </div>
                </div>

                <!-- FOOTER NAV -->
                <div class="px-7 sm:px-9 py-5 border-t border-[#ececef] bg-[#fafafb] flex items-center justify-between gap-3 flex-wrap">
                    <button type="button" @click="prev()" x-show="step > 0" class="inline-flex items-center gap-1.5 px-4 h-11 rounded-xl text-[13px] font-semibold text-ink-700 hover:bg-white border border-[#ececef] transition"><i class="lucide lucide-arrow-left text-[13px]"></i> Atrás</button>
                    <p x-show="step === 0" class="text-[11.5px] text-ink-400 inline-flex items-center gap-1.5"><i class="lucide lucide-shield-check text-[12px] text-emerald-600"></i> Tus datos viajan cifrados</p>

                    <div class="flex gap-2 ml-auto">
                        <button type="button" @click="next()" x-show="step < 2" :disabled="!canAdvance()" :class="!canAdvance() ? 'opacity-50 cursor-not-allowed' : ''" class="inline-flex items-center gap-1.5 px-5 h-11 rounded-xl text-[13px] font-semibold transition" :style="canAdvance() ? 'background:<?= $e($brand) ?>;color:white;box-shadow:0 8px 18px -6px rgba(<?= $rgbStr ?>,.5)' : 'background:#ececef;color:#8e8e9a'">Continuar <i class="lucide lucide-arrow-right text-[13px]"></i></button>
                        <button type="submit" x-show="step === 2" :disabled="submitting" class="inline-flex items-center gap-1.5 px-5 h-11 rounded-xl text-[13px] font-semibold transition" :style="'background:<?= $e($brand) ?>;color:white;box-shadow:0 10px 22px -8px rgba(<?= $rgbStr ?>,.6)' + (submitting ? ';opacity:.7' : '')">
                            <i class="lucide" :class="submitting ? 'lucide-loader-2 animate-spin' : 'lucide-send'"></i>
                            <span x-text="submitting ? 'Enviando…' : 'Enviar ticket'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TRUST BADGES -->
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?php foreach ([
                ['zap','#7c5cff','Respuesta rápida','Menos de 24h en la mayoría de casos'],
                ['shield-check','#22c55e','100% privado','Solo vos y el equipo de soporte ven el caso'],
                ['link','#0ea5e9','Sin registro','Recibirás un link único en tu email'],
            ] as [$ic,$col,$ti,$de]): ?>
                <div class="p-5 rounded-2xl bg-white border border-[#ececef] hover:border-brand-300 transition">
                    <div class="w-10 h-10 rounded-xl grid place-items-center mb-3" style="background:<?= $col ?>1a;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                    <div class="font-display font-bold text-[13.5px] tracking-[-0.015em]"><?= $ti ?></div>
                    <div class="text-[11.5px] mt-1 text-ink-500 leading-relaxed"><?= $de ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- KB SUGGESTION -->
        <div class="mt-6 p-5 rounded-2xl text-center" style="background:linear-gradient(135deg,#fafafb,#f3f0ff);border:1px solid #ececef">
            <i class="lucide lucide-search text-[20px]" style="color:<?= $e($brand) ?>"></i>
            <div class="font-display font-bold text-[14px] mt-1.5">¿Y si tu respuesta ya existe?</div>
            <p class="text-[12px] text-ink-500 mt-1">Revisá la base de conocimiento — quizás encuentres la solución sin esperar respuesta.</p>
            <a href="<?= $url('/portal/' . $t->slug . '/kb') ?>" class="inline-flex items-center gap-1.5 mt-3 text-[12.5px] font-semibold transition" style="color:<?= $e($brand) ?>">Buscar en KB <i class="lucide lucide-arrow-right text-[12px]"></i></a>
        </div>
    </div>
</section>

<script>
function ticketForm() {
    return {
        step: 0,
        submitting: false,
        steps: ['Datos','Detalle','Confirmar'],
        form: { name:'', email:'', phone:'', subject:'', description:'', category:'0', priority:'medium' },
        init() {
            // Restore from localStorage if present
            try {
                const saved = localStorage.getItem('kydesk_portal_draft_<?= $e($t->slug) ?>');
                if (saved) Object.assign(this.form, JSON.parse(saved));
            } catch(e) {}
            this.$watch('form', v => { try { localStorage.setItem('kydesk_portal_draft_<?= $e($t->slug) ?>', JSON.stringify(v)); } catch(e){} }, {deep:true});
        },
        canAdvance() {
            if (this.step === 0) {
                return this.form.name && this.form.email && /^[^@]+@[^@]+\.[^@]+$/.test(this.form.email);
            }
            if (this.step === 1) {
                return this.form.subject && (this.form.description?.length || 0) >= 20;
            }
            return true;
        },
        next() { if (this.canAdvance() && this.step < 2) { this.step++; window.scrollTo({top:0,behavior:'smooth'}); } },
        prev() { if (this.step > 0) { this.step--; window.scrollTo({top:0,behavior:'smooth'}); } },
        pickContact(c) {
            if (c.name) this.form.name = c.name;
            if (c.email) this.form.email = c.email;
            if (c.phone) this.form.phone = c.phone;
        },
    };
}
</script>
