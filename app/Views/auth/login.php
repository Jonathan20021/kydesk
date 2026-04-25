<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
    <div class="flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-sm">
            <a href="<?= $url('/') ?>" class="flex items-center gap-2.5 mb-12">
                <div class="w-9 h-9 rounded-xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[15px]" style="box-shadow:0 4px 12px -2px rgba(124,92,255,.4)">K</div>
                <span class="font-display font-bold text-[18px]">Kydesk</span>
            </a>
            <h1 class="font-display font-extrabold text-[32px] tracking-[-0.025em] leading-tight">Bienvenido<br>de vuelta</h1>
            <p class="mt-3 text-sm text-ink-400">Ingresa a tu cuenta de Kydesk.</p>

            <form method="POST" action="<?= $url('/auth/login') ?>" class="mt-8 space-y-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Email</label><input name="email" type="email" required value="admin@demo.com" class="input"></div>
                <div x-data="{show:false}">
                    <div class="flex items-center justify-between"><label class="label" style="margin-bottom:0">Contraseña</label><a href="#" class="text-[12px] text-ink-400">¿Olvidaste?</a></div>
                    <div class="relative mt-2">
                        <input name="password" :type="show?'text':'password'" required value="admin123" class="input pr-11">
                        <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center text-ink-400"><i :class="show?'lucide-eye-off':'lucide-eye'" class="lucide text-[15px]"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary w-full mt-2" style="height:48px">Entrar <i class="lucide lucide-arrow-right"></i></button>
            </form>

            <div class="flex items-center gap-3 mt-7 mb-4">
                <div class="flex-1 h-px bg-[#ececef]"></div>
                <span class="text-[10.5px] font-bold uppercase tracking-[0.2em] text-ink-400">o probar demo</span>
                <div class="flex-1 h-px bg-[#ececef]"></div>
            </div>

            <p class="text-center text-[12px] text-ink-400 mb-3.5 flex items-center justify-center gap-1.5"><i class="lucide lucide-zap text-[12px] text-brand-500"></i> Workspace al instante · Se borra en 24h</p>

            <div class="grid grid-cols-3 gap-2">
                <?php
                $loginPlans = [
                    ['starter','Starter','$29','25 emp.', false, '#dbeafe', '#1d4ed8'],
                    ['pro','Pro','$79','100 emp.', true, '', ''],
                    ['enterprise','Enterprise','$199','∞', false, '#fef3c7', '#b45309'],
                ];
                foreach ($loginPlans as [$key,$label,$price,$meta,$featured,$bg,$col]):
                ?>
                    <form method="POST" action="<?= $url('/demo/start/' . $key) ?>" class="<?= $featured?'-mt-2':'' ?>">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button type="submit" class="group relative w-full text-center rounded-2xl py-4 px-3 transition-all duration-200 hover:-translate-y-1 <?= $featured ? 'text-white' : 'bg-white border border-[#ececef] hover:border-brand-300 hover:shadow-[0_14px_28px_-10px_rgba(124,92,255,0.22)]' ?>" <?= $featured ? 'style="background:linear-gradient(180deg,#1a1825,#16151b);box-shadow:0 12px 28px -8px rgba(124,92,255,.45)"' : '' ?>>
                            <?php if ($featured): ?>
                                <span class="absolute inset-0 rounded-2xl pointer-events-none" style="padding:1.5px;background:linear-gradient(135deg,#7c5cff,#d946ef);-webkit-mask:linear-gradient(white,white) content-box,linear-gradient(white,white);-webkit-mask-composite:xor;mask-composite:exclude"></span>
                                <span class="absolute -top-2.5 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold tracking-[0.16em] text-white whitespace-nowrap" style="background:linear-gradient(135deg,#7c5cff,#d946ef);box-shadow:0 4px 10px -2px rgba(124,92,255,.5)">POPULAR</span>
                            <?php else: ?>
                                <span class="inline-flex w-7 h-7 rounded-lg items-center justify-center mb-1.5" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $key==='starter'?'rocket':'crown' ?> text-[13px]"></i></span>
                            <?php endif; ?>
                            <?php if ($featured): ?>
                                <span class="inline-flex w-7 h-7 rounded-lg items-center justify-center mb-1.5" style="background:rgba(124,92,255,.25);color:#c4b5fd"><i class="lucide lucide-zap text-[13px]"></i></span>
                            <?php endif; ?>
                            <div class="font-display font-bold text-[12.5px] tracking-[-0.015em] <?= $featured?'text-white':'text-ink-900' ?>"><?= $e($label) ?></div>
                            <div class="font-display font-extrabold text-[16px] tracking-[-0.02em] mt-0.5 <?= $featured?'text-white':'text-ink-900' ?>"><?= $e($price) ?><span class="text-[9.5px] font-semibold opacity-50">/m</span></div>
                            <div class="text-[10px] font-semibold mt-0.5 <?= $featured?'text-white/60':'text-ink-400' ?>"><?= $e($meta) ?></div>
                            <div class="mt-2.5 inline-flex items-center justify-center gap-1 text-[9.5px] font-bold uppercase tracking-[0.08em] <?= $featured?'text-brand-300':'text-brand-700' ?> opacity-0 group-hover:opacity-100 transition"><i class="lucide lucide-play text-[9px]"></i> Probar</div>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <details class="mt-5 text-[12px] text-ink-500">
                <summary class="cursor-pointer hover:text-ink-900 transition inline-flex items-center gap-1.5"><i class="lucide lucide-key-round text-[12px]"></i> Credenciales del demo principal</summary>
                <div class="mt-2 p-3 rounded-xl bg-[#f3f4f6] font-mono text-[11.5px]">admin@demo.com · admin123</div>
            </details>

            <p class="mt-7 text-center text-[13px] text-ink-400">¿Nuevo? <a href="<?= $url('/auth/register') ?>" class="font-semibold text-ink-900">Crear organización</a></p>
        </div>
    </div>
    <div class="hidden lg:flex relative items-center justify-center p-10 overflow-hidden" style="background:linear-gradient(135deg,#6c47ff,#8b5cf6 50%,#a78bfa)">
        <div class="absolute inset-0" style="background-image:radial-gradient(circle at 80% 20%,rgba(255,255,255,.18) 0%,transparent 50%),radial-gradient(circle at 20% 80%,rgba(255,255,255,.12) 0%,transparent 50%)"></div>
        <svg class="absolute right-10 top-10 w-60 opacity-35 pointer-events-none" viewBox="0 0 280 200" fill="none">
            <path d="M150 20 L155 50 L185 55 L155 60 L150 90 L145 60 L115 55 L145 50 Z" fill="white"/>
            <path d="M70 80 L73 95 L88 98 L73 101 L70 116 L67 101 L52 98 L67 95 Z" fill="white"/>
            <path d="M220 130 L223 145 L238 148 L223 151 L220 166 L217 151 L202 148 L217 145 Z" fill="white"/>
            <path d="M40 30 L42 38 L50 40 L42 42 L40 50 L38 42 L30 40 L38 38 Z" fill="white"/>
        </svg>
        <div class="relative max-w-md text-white">
            <div class="inline-flex items-center gap-2 h-8 px-3.5 rounded-full bg-white/15 border border-white/20 text-[11.5px]"><span class="pulse"></span> Sistemas operativos</div>
            <h2 class="mt-6 font-display font-extrabold text-[42px] leading-[1.05] tracking-[-0.03em]">Una plataforma.<br>Todo tu soporte.</h2>
            <p class="mt-5 text-[14.5px] leading-relaxed text-white/85">Tickets, SLAs, escalamientos, automatizaciones y conocimiento.</p>
            <div class="mt-9 space-y-3">
                <?php foreach ([['zap','Atajos de teclado en todas partes'],['shield','Seguro y auditado'],['workflow','Automatizaciones nativas']] as [$ic,$t]): ?>
                    <div class="flex items-center gap-3 text-[13.5px] text-white/85">
                        <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 grid place-items-center"><i class="lucide lucide-<?= $ic ?> text-base"></i></div>
                        <?= $t ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
