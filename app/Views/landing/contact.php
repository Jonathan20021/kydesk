<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<section class="pt-32 pb-20">
    <div class="max-w-[1100px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-5 gap-10">
        <div class="lg:col-span-2">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.14em] text-brand-600 mb-3">CONTACTO</div>
            <h1 class="heading-lg">Hablemos.</h1>
            <p class="mt-5 text-[15px] leading-relaxed text-ink-500">Te respondemos en menos de 24h.</p>
            <div class="mt-10 space-y-5 text-[13.5px]">
                <div class="flex items-center gap-3"><div class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-mail text-base"></i></div>hola@kydesk.io</div>
                <div class="flex items-center gap-3"><div class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-phone text-base"></i></div>+502 0000 0000</div>
                <div class="flex items-center gap-3"><div class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-map-pin text-base"></i></div>Ciudad de Guatemala</div>
            </div>
        </div>
        <form class="lg:col-span-3 card card-pad space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="label">Nombre</label><input class="input"></div>
                <div><label class="label">Empresa</label><input class="input"></div>
            </div>
            <div><label class="label">Email</label><input type="email" class="input"></div>
            <div><label class="label">Mensaje</label><textarea rows="5" class="input"></textarea></div>
            <button type="button" class="btn btn-primary w-full">Enviar <i class="lucide lucide-send"></i></button>
        </form>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
