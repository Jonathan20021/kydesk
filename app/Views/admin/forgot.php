<div class="min-h-screen relative overflow-hidden grid place-items-center px-4 py-10">
    <!-- Aurora background -->
    <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute" style="width:720px;height:720px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.42),transparent 70%);top:-220px;left:-160px;filter:blur(90px);"></div>
        <div class="absolute" style="width:620px;height:620px;border-radius:50%;background:radial-gradient(circle,rgba(217,70,239,.28),transparent 70%);bottom:-220px;right:-120px;filter:blur(90px);"></div>
        <div class="absolute" style="width:480px;height:480px;border-radius:50%;background:radial-gradient(circle,rgba(167,139,250,.18),transparent 70%);top:50%;left:50%;transform:translate(-50%,-50%);filter:blur(90px);"></div>
        <div class="absolute inset-0 opacity-50" style="background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2.5 mb-5">
                <div class="w-14 h-14 rounded-2xl text-white grid place-items-center font-display font-extrabold text-[22px] relative" style="background:linear-gradient(135deg,#7c5cff 0%,#a78bfa 60%,#d946ef 100%);box-shadow:0 18px 40px -10px rgba(124,92,255,.55)">
                    K
                    <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full grid place-items-center" style="background:white"><i class="lucide lucide-key-round text-[11px]" style="color:#5a3aff"></i></span>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-[0.2em]" style="background:rgba(124,92,255,.18);border:1px solid rgba(167,139,250,.35);color:#c4b5fd">
                <i class="lucide lucide-key-round text-[10px]"></i> Recuperar acceso
            </div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] leading-tight mt-4">¿Olvidaste tu contraseña?</h1>
            <p class="text-[13.5px] mt-2 max-w-sm mx-auto" style="color:rgba(255,255,255,.55)">Ingresá el email de tu cuenta de super admin y te enviamos un enlace para crear una nueva contraseña.</p>
        </div>

        <div class="rounded-3xl p-7 relative" style="background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);box-shadow:0 30px 60px -20px rgba(0,0,0,.5)">
            <form method="POST" action="<?= $url('/admin/forgot') ?>" class="space-y-4">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div>
                    <label class="block text-[12px] font-semibold mb-1.5" style="color:rgba(255,255,255,.7)">Email</label>
                    <div class="relative">
                        <i class="lucide lucide-mail text-[15px] absolute left-4 top-1/2 -translate-y-1/2" style="color:rgba(255,255,255,.4)"></i>
                        <input name="email" type="email" required autofocus placeholder="tu@email.com" class="w-full h-12 pl-11 pr-4 rounded-xl text-[14px] outline-none transition" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:white" onfocus="this.style.borderColor='#a78bfa';this.style.boxShadow='0 0 0 3px rgba(124,92,255,.22)'" onblur="this.style.borderColor='rgba(255,255,255,.1)';this.style.boxShadow='none'">
                    </div>
                </div>
                <button class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-xl font-semibold text-[14px] transition mt-2" style="background:linear-gradient(135deg,#7c5cff,#6c47ff);color:white;box-shadow:0 12px 28px -8px rgba(124,92,255,.55)" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 16px 36px -8px rgba(124,92,255,.65)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 12px 28px -8px rgba(124,92,255,.55)'"><i class="lucide lucide-send"></i> Enviar enlace</button>
            </form>

            <div class="mt-6 pt-6 border-t" style="border-color:rgba(255,255,255,.08)">
                <div class="text-[11.5px] flex items-start gap-2" style="color:rgba(255,255,255,.5)">
                    <i class="lucide lucide-info text-[13px] flex-shrink-0 mt-0.5"></i>
                    <div>El enlace expira en <strong style="color:rgba(255,255,255,.7)">1 hora</strong>. Por seguridad, no revelamos si el email existe en el sistema.</div>
                </div>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="<?= $url('/admin/login') ?>" class="text-[12.5px] inline-flex items-center gap-1.5 transition" style="color:rgba(255,255,255,.5)" onmouseover="this.style.color='#c4b5fd'" onmouseout="this.style.color='rgba(255,255,255,.5)'">
                <i class="lucide lucide-arrow-left text-[12px]"></i> Volver al login
            </a>
        </div>
    </div>
</div>
