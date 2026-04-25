<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
    <div class="flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-sm">
            <a href="<?= $url('/') ?>" class="flex items-center gap-2.5 mb-10">
                <div class="w-9 h-9 rounded-xl bg-brand-500 text-white grid place-items-center font-display font-bold text-[15px]" style="box-shadow:0 4px 12px -2px rgba(124,92,255,.4)">K</div>
                <span class="font-display font-bold text-[18px]">Kydesk</span>
            </a>
            <h1 class="font-display font-extrabold text-[32px] tracking-[-0.025em] leading-tight">Crea tu<br>organización</h1>
            <p class="mt-3 text-sm text-ink-400">Empieza gratis. Sin tarjeta.</p>

            <form method="POST" action="<?= $url('/auth/register') ?>" class="mt-7 space-y-3.5"
                  x-data="{org:'',slug:''}"
                  x-init="$watch('org', v => { if (!slug || slug === $refs.s.dataset.auto) { const a = v.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-'); $refs.s.value = a; $refs.s.dataset.auto = a; slug = a; } })">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Organización</label><input name="org_name" x-model="org" required class="input" placeholder="Acme Inc."></div>
                <div>
                    <label class="label">URL del panel</label>
                    <div class="flex h-11 rounded-2xl overflow-hidden border border-[#ececef] bg-white">
                        <span class="px-3.5 grid place-items-center text-[12.5px] font-mono bg-[#f3f4f6] text-ink-400 border-r border-[#ececef]">/t/</span>
                        <input x-ref="s" name="org_slug" data-auto="" @input="slug=$event.target.value" placeholder="acme" class="flex-1 px-3.5 text-[13.5px] outline-none">
                    </div>
                </div>
                <div class="h-px bg-[#ececef] my-2"></div>
                <div><label class="label">Tu nombre</label><input name="name" required class="input" placeholder="Ana García"></div>
                <div><label class="label">Email</label><input name="email" type="email" required class="input"></div>
                <div x-data="{show:false}">
                    <label class="label">Contraseña</label>
                    <div class="relative">
                        <input name="password" :type="show?'text':'password'" required minlength="6" class="input pr-11" placeholder="Mínimo 6 caracteres">
                        <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center text-ink-400"><i :class="show?'lucide-eye-off':'lucide-eye'" class="lucide text-[15px]"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary w-full mt-2" style="height:48px">Crear organización <i class="lucide lucide-arrow-right"></i></button>
            </form>
            <p class="mt-6 text-center text-[13px] text-ink-400">¿Ya tienes cuenta? <a href="<?= $url('/auth/login') ?>" class="font-semibold text-ink-900">Iniciar sesión</a></p>
        </div>
    </div>
    <div class="hidden lg:flex relative items-center justify-center p-10 overflow-hidden" style="background:linear-gradient(135deg,#6c47ff,#8b5cf6 50%,#a78bfa)">
        <div class="absolute inset-0" style="background-image:radial-gradient(circle at 80% 20%,rgba(255,255,255,.18) 0%,transparent 50%)"></div>
        <div class="relative max-w-md text-white">
            <h2 class="font-display font-extrabold text-[42px] leading-[1.05] tracking-[-0.03em]">Listo en<br>60 segundos.</h2>
            <p class="mt-5 text-[14.5px] leading-relaxed text-white/85">Creamos roles, categorías, SLAs y portal automáticamente.</p>
            <ol class="mt-9 space-y-3.5">
                <?php foreach (['Crea tu espacio','Invita a tu equipo','Conecta tu portal','Configura SLAs','Empieza a atender'] as $i => $t): ?>
                    <li class="flex items-center gap-3 text-[14px] text-white/85">
                        <span class="w-8 h-8 rounded-full bg-white/15 border border-white/20 grid place-items-center text-[12px] font-mono"><?= $i+1 ?></span>
                        <?= $t ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</div>
