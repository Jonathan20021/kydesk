<?php include APP_PATH . '/Views/partials/landing_nav.php'; ?>

<section class="relative pt-36 pb-12 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
    </div>
    <div class="grid-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex justify-center">
                <div class="aura-pill">
                    <span class="aura-pill-tag"><i class="lucide lucide-shield-check"></i> PRIVACIDAD</span>
                    <span class="text-ink-700 font-medium">Última actualización: <?= date('d/m/Y') ?></span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.4rem,5vw + 1rem,4rem)">Política de <span class="gradient-shift">privacidad</span>.</h1>
        </div>
    </div>
</section>

<section class="pb-24">
    <div class="max-w-[820px] mx-auto px-6">
        <div class="rounded-2xl p-10 md:p-14 bg-white border border-[#ececef]">
            <div class="prose-kydesk">
                <p class="lead">En Kydesk respetamos tu privacidad y nos tomamos en serio la protección de tus datos. Esta política describe qué información recopilamos, cómo la usamos y los derechos que tenés sobre ella.</p>

                <h2>1. Información que recopilamos</h2>
                <p>Recopilamos información que vos nos proporcionás directamente al crear una cuenta, usar nuestros servicios, o contactarnos:</p>
                <ul>
                    <li><strong>Datos de cuenta:</strong> nombre, email, contraseña (hasheada), organización.</li>
                    <li><strong>Datos de facturación:</strong> dirección de pago, información fiscal, historial de pagos.</li>
                    <li><strong>Contenido del workspace:</strong> tickets, comentarios, archivos adjuntos, contactos, notas.</li>
                    <li><strong>Datos técnicos:</strong> dirección IP, navegador, sistema operativo, log de actividad.</li>
                </ul>

                <h2>2. Cómo usamos tu información</h2>
                <p>Usamos la información recopilada para:</p>
                <ul>
                    <li>Proveer, mantener y mejorar nuestros servicios.</li>
                    <li>Procesar pagos y administrar tu suscripción.</li>
                    <li>Responder consultas y proveer soporte técnico.</li>
                    <li>Enviar notificaciones operativas y, con tu consentimiento, comunicaciones de marketing.</li>
                    <li>Detectar y prevenir fraude, uso abusivo y violaciones de seguridad.</li>
                </ul>

                <h2>3. Compartir información con terceros</h2>
                <p>No vendemos tus datos. Compartimos información solo con:</p>
                <ul>
                    <li><strong>Procesadores de pago</strong> (Stripe, PayPal) para gestionar suscripciones.</li>
                    <li><strong>Infraestructura cloud</strong> (AWS, Cloudflare) bajo acuerdos de procesamiento de datos.</li>
                    <li><strong>Autoridades</strong> cuando sea requerido por ley.</li>
                </ul>

                <h2>4. Aislamiento multi-tenant</h2>
                <p>Cada workspace está aislado a nivel de base de datos. Los datos de un cliente nunca son accesibles por otro cliente. Aplicamos foreign keys con tenant_id en cada tabla y nuestros endpoints validan permisos por workspace en cada request.</p>

                <h2>5. Seguridad</h2>
                <p>Implementamos medidas técnicas y organizativas razonables para proteger tus datos:</p>
                <ul>
                    <li>Cifrado en tránsito (TLS 1.3) y en reposo (AES-256).</li>
                    <li>Hash bcrypt con cost 12 para contraseñas.</li>
                    <li>CSRF protection en todos los endpoints de modificación.</li>
                    <li>Auditoría completa de accesos administrativos.</li>
                    <li>Backups diarios cifrados con retención de 30 días.</li>
                </ul>

                <h2>6. Residencia de datos</h2>
                <p>Por defecto procesamos datos en regiones de Estados Unidos. Los planes Enterprise pueden elegir residencia en US, EU o LATAM con SLA garantizado por región.</p>

                <h2>7. Tus derechos</h2>
                <p>Tenés derecho a:</p>
                <ul>
                    <li><strong>Acceder</strong> a tus datos personales.</li>
                    <li><strong>Rectificar</strong> información incorrecta o incompleta.</li>
                    <li><strong>Eliminar</strong> tu cuenta y todos los datos asociados.</li>
                    <li><strong>Exportar</strong> tu información en formato estructurado (JSON / CSV).</li>
                    <li><strong>Oponerte</strong> al procesamiento con fines de marketing.</li>
                </ul>
                <p>Para ejercer cualquiera de estos derechos, contactanos a <a href="mailto:jonathansandoval@kyrosrd.com">jonathansandoval@kyrosrd.com</a>.</p>

                <h2>8. Retención</h2>
                <p>Mantenemos tus datos mientras tu cuenta esté activa. Al cancelar, retenemos los datos durante 30 días por si reactivás la cuenta, luego son eliminados permanentemente. Las copias de respaldo se purgan dentro de los 90 días siguientes.</p>

                <h2>9. Cookies</h2>
                <p>Usamos cookies estrictamente necesarias para autenticación y CSRF protection. No usamos cookies de tracking de terceros para publicidad.</p>

                <h2>10. Cambios a esta política</h2>
                <p>Podemos actualizar esta política ocasionalmente. Si hacemos cambios materiales te notificaremos con al menos 30 días de antelación a la dirección de email asociada a tu cuenta.</p>

                <h2>11. Contacto</h2>
                <p>Para cualquier pregunta sobre privacidad o tratamiento de datos personales, escribinos a <a href="mailto:jonathansandoval@kyrosrd.com">jonathansandoval@kyrosrd.com</a>.</p>
            </div>
        </div>
    </div>
</section>

<style>
.prose-kydesk { font-size:14.5px; line-height:1.75; color:#2a2a33; }
.prose-kydesk .lead { font-size:16px; color:#6b6b78; margin-bottom:32px; padding-bottom:24px; border-bottom:1px solid #ececef; }
.prose-kydesk h2 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:20px; letter-spacing:-0.02em; margin-top:32px; margin-bottom:14px; color:#16151b; }
.prose-kydesk p { margin-bottom:14px; }
.prose-kydesk ul { margin:14px 0 22px 0; padding-left:22px; }
.prose-kydesk ul li { margin-bottom:8px; }
.prose-kydesk strong { color:#16151b; font-weight:600; }
.prose-kydesk a { color:#5a3aff; font-weight:500; }
.prose-kydesk a:hover { text-decoration:underline; }
</style>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
