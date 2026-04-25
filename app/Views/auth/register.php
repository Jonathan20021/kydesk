<div class="min-h-screen relative overflow-hidden" style="background:#fafafb">

    <!-- Aurora background -->
    <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute" style="width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.4),transparent 70%);top:-200px;left:-100px;filter:blur(60px);animation:aurora-1 22s ease-in-out infinite"></div>
        <div class="absolute" style="width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(217,70,239,.3),transparent 70%);top:20%;right:-100px;filter:blur(60px);animation:aurora-2 26s ease-in-out infinite"></div>
        <div class="absolute" style="width:550px;height:550px;border-radius:50%;background:radial-gradient(circle,rgba(34,197,94,.2),transparent 70%);bottom:-150px;left:30%;filter:blur(70px);animation:aurora-3 30s ease-in-out infinite"></div>
        <div class="absolute inset-0" style="background-image:linear-gradient(rgba(124,92,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(124,92,255,0.06) 1px, transparent 1px); background-size: 64px 64px; mask-image: radial-gradient(ellipse 70% 50% at 50% 0%, black 30%, transparent 80%); -webkit-mask-image: radial-gradient(ellipse 70% 50% at 50% 0%, black 30%, transparent 80%);"></div>
    </div>

    <!-- Top nav -->
    <nav class="fixed top-4 inset-x-0 z-50 px-4">
        <div class="max-w-[1100px] mx-auto">
            <div class="flex items-center justify-between gap-4 px-5 py-2.5 rounded-full" style="background:rgba(255,255,255,0.78);backdrop-filter:blur(20px) saturate(180%);border:1px solid rgba(124,92,255,0.12);box-shadow:0 4px 20px -4px rgba(22,21,27,0.06)">
                <a href="<?= $url('/') ?>" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);box-shadow:0 4px 12px -2px rgba(124,92,255,.4)">K</div>
                    <span class="font-display font-extrabold text-[16px] tracking-[-0.02em]">Kydesk</span>
                </a>
                <a href="<?= $url('/auth/login') ?>" class="text-[13px] font-semibold text-ink-700 hover:text-brand-700 transition">Iniciar sesión →</a>
            </div>
        </div>
    </nav>

    <div class="min-h-screen grid place-items-center px-4 py-24">
        <div class="w-full max-w-[1100px] grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">

            <!-- LEFT: branding + steps -->
            <div class="hidden lg:block">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em]" style="background:rgba(255,255,255,.85);border:1px solid rgba(124,92,255,0.2);color:#5a3aff">
                    <i class="lucide lucide-rocket text-[12px]"></i> EMPEZAR GRATIS
                </div>

                <h1 class="display-xl mt-7" style="font-size:clamp(2.4rem,4vw + 1rem,4rem);text-wrap:balance">Listo en<br><span class="gradient-shift">60 segundos</span>.</h1>

                <p class="mt-6 text-[16px] text-ink-500 max-w-md leading-relaxed">Creamos tus roles, categorías, SLAs y portal automáticamente. Sin tarjeta. Sin compromiso.</p>

                <ol class="mt-10 space-y-3.5">
                    <?php foreach ([
                        ['Crea tu workspace', 'Slug propio, colores y portal'],
                        ['Invita a tu equipo', 'Roles granulares por persona'],
                        ['Conecta canales', 'Email, portal, teléfono y chat'],
                        ['Configura SLAs', 'Por prioridad y por cliente'],
                        ['Empieza a atender', 'Todo listo desde el día 1'],
                    ] as $i => [$t,$d]): ?>
                        <li class="flex items-start gap-3.5">
                            <div class="w-9 h-9 rounded-xl grid place-items-center flex-shrink-0 font-display font-bold text-[13px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 6px 14px -4px rgba(124,92,255,.4)"><?= $i+1 ?></div>
                            <div>
                                <div class="font-display font-bold text-[14px]"><?= $t ?></div>
                                <div class="text-[12px] text-ink-500 mt-0.5"><?= $d ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <div class="mt-10 flex flex-wrap items-center gap-x-5 gap-y-2 text-[12.5px] text-ink-500">
                    <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> 14 días gratis</span>
                    <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> Sin tarjeta</span>
                    <span class="flex items-center gap-1.5"><i class="lucide lucide-check text-emerald-600"></i> SOC 2</span>
                </div>
            </div>

            <!-- RIGHT: form card -->
            <div class="relative">
                <div class="absolute -inset-1 rounded-[32px] opacity-50 blur-2xl pointer-events-none" style="background:linear-gradient(135deg,rgba(124,92,255,.4),rgba(34,197,94,.25))"></div>

                <div class="relative rounded-[28px] p-8 md:p-10" style="background:white;border:1px solid #ececef;box-shadow:0 30px 60px -20px rgba(124,92,255,0.18)">
                    <div class="lg:hidden flex items-center gap-2.5 mb-8">
                        <div class="w-9 h-9 rounded-xl text-white grid place-items-center font-display font-bold" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">K</div>
                        <span class="font-display font-bold text-[18px]">Kydesk</span>
                    </div>

                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-brand-600 mb-2">Crear cuenta</div>
                    <h2 class="font-display font-extrabold text-[28px] tracking-[-0.025em] leading-tight">Crea tu<br>organización</h2>
                    <p class="mt-2 text-[13.5px] text-ink-500">Empieza gratis · Sin tarjeta · 5 minutos</p>

                    <form method="POST" action="<?= $url('/auth/register') ?>" class="mt-7 space-y-3.5"
                          x-data="{org:'',slug:''}"
                          x-init="$watch('org', v => { if (!slug || slug === $refs.s.dataset.auto) { const a = v.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-'); $refs.s.value = a; $refs.s.dataset.auto = a; slug = a; } })">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Tu organización</div>
                        <div>
                            <label class="label">Nombre de la organización</label>
                            <div class="relative">
                                <i class="lucide lucide-building-2 text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="org_name" x-model="org" required class="input pl-11" placeholder="Acme Inc.">
                            </div>
                        </div>
                        <div>
                            <label class="label">URL del panel</label>
                            <div class="flex h-11 rounded-xl overflow-hidden border border-[#ececef] bg-white focus-within:border-brand-300 focus-within:shadow-[0_0_0_4px_#f3f0ff] transition">
                                <span class="px-3.5 grid place-items-center text-[12.5px] font-mono bg-[#f3f4f6] text-ink-400 border-r border-[#ececef]">kydesk.kyrosrd.com/t/</span>
                                <input x-ref="s" name="org_slug" data-auto="" @input="slug=$event.target.value" placeholder="acme" class="flex-1 px-3.5 text-[13.5px] outline-none font-mono">
                            </div>
                        </div>

                        <div class="h-px bg-[#ececef] my-3"></div>
                        <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Tus credenciales</div>

                        <div>
                            <label class="label">Tu nombre</label>
                            <div class="relative">
                                <i class="lucide lucide-user text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="name" required class="input pl-11" placeholder="Ana García">
                            </div>
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <div class="relative">
                                <i class="lucide lucide-mail text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="email" type="email" required class="input pl-11" placeholder="ana@acme.com">
                            </div>
                        </div>
                        <div x-data="{show:false}">
                            <label class="label">Contraseña</label>
                            <div class="relative">
                                <i class="lucide lucide-lock text-[15px] absolute left-4 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                                <input name="password" :type="show?'text':'password'" required minlength="6" class="input pl-11 pr-11" placeholder="Mínimo 6 caracteres">
                                <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center text-ink-400 hover:text-ink-700"><i :class="show?'lucide-eye-off':'lucide-eye'" class="lucide text-[15px]"></i></button>
                            </div>
                        </div>

                        <button class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-xl font-semibold text-[14px] transition mt-3" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.55)" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">Crear organización <i class="lucide lucide-arrow-right"></i></button>

                        <p class="text-[11.5px] text-ink-400 text-center pt-1">Al continuar aceptás los <a href="#" class="text-brand-700 hover:underline">términos</a> y la <a href="#" class="text-brand-700 hover:underline">privacidad</a></p>
                    </form>

                    <p class="mt-6 text-center text-[13px] text-ink-400">¿Ya tienes cuenta? <a href="<?= $url('/auth/login') ?>" class="font-semibold text-brand-700 hover:underline">Iniciar sesión</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
