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
                    <span class="aura-pill-tag"><i class="lucide lucide-scroll-text"></i> TÉRMINOS</span>
                    <span class="text-ink-700 font-medium">Última actualización: <?= date('d/m/Y') ?></span>
                </div>
            </div>
            <h1 class="display-xl mt-8" style="text-wrap:balance;font-size:clamp(2.4rem,5vw + 1rem,4rem)">Términos y <span class="gradient-shift">condiciones</span>.</h1>
        </div>
    </div>
</section>

<section class="pb-24">
    <div class="max-w-[820px] mx-auto px-6">
        <div class="rounded-2xl p-10 md:p-14 bg-white border border-[#ececef]">
            <div class="prose-kydesk">
                <p class="lead">Bienvenido a Kydesk. Al usar nuestros servicios aceptás estos Términos y Condiciones. Te recomendamos leerlos con atención.</p>

                <h2>1. Aceptación</h2>
                <p>Al crear una cuenta o usar Kydesk aceptás estar vinculado por estos términos. Si no estás de acuerdo, no uses el servicio.</p>

                <h2>2. Descripción del servicio</h2>
                <p>Kydesk es una plataforma SaaS de helpdesk multi-tenant que provee gestión de tickets, base de conocimiento, automatizaciones, SLAs, reportes y herramientas relacionadas. Los servicios pueden cambiar o evolucionar con el tiempo.</p>

                <h2>3. Cuenta y elegibilidad</h2>
                <ul>
                    <li>Debés ser mayor de 18 años o tener autorización legal de tu organización.</li>
                    <li>Sos responsable de mantener la confidencialidad de tus credenciales.</li>
                    <li>Notificá inmediatamente cualquier acceso no autorizado.</li>
                    <li>Una persona o entidad puede crear múltiples workspaces.</li>
                </ul>

                <h2>4. Uso aceptable</h2>
                <p>No podés usar Kydesk para:</p>
                <ul>
                    <li>Actividades ilegales o que violen derechos de terceros.</li>
                    <li>Enviar spam, malware o contenido malicioso.</li>
                    <li>Realizar ingeniería inversa, scraping masivo o abusar de la API.</li>
                    <li>Suplantar identidad o engañar sobre tu afiliación.</li>
                    <li>Sobrecargar la infraestructura intencionalmente.</li>
                </ul>

                <h2>5. Suscripciones y pagos</h2>
                <ul>
                    <li>Los planes pagos se facturan por adelantado mensual o anualmente.</li>
                    <li>Los precios están publicados en <a href="<?= $url('/pricing') ?>">/pricing</a> y pueden cambiar con 30 días de aviso.</li>
                    <li>Los pagos son automáticos al final de cada ciclo si tenés auto-renovación activada.</li>
                    <li>Podés cancelar cuando quieras desde el panel; el servicio continúa hasta el fin del período pagado.</li>
                </ul>

                <h2>6. Trial y reembolsos</h2>
                <p>Ofrecemos 14 días de prueba gratuita sin tarjeta. Si pagaste y no estás satisfecho, podés solicitar reembolso completo dentro de los primeros 30 días contactando <a href="mailto:billing@kydesk.com">billing@kydesk.com</a>. Después de ese plazo no se otorgan reembolsos por períodos parciales.</p>

                <h2>7. Tus datos</h2>
                <p>Vos sos dueño del contenido que subís a Kydesk (tickets, archivos, contactos, etc.). Nos otorgás una licencia limitada para almacenar, procesar y mostrarte ese contenido en el contexto del servicio. No usamos tus datos para entrenar modelos de IA externos ni los compartimos con terceros para marketing.</p>

                <h2>8. SLA</h2>
                <ul>
                    <li><strong>Plan Pro:</strong> 99.9% uptime mensual.</li>
                    <li><strong>Plan Enterprise:</strong> 99.99% uptime mensual con créditos de servicio si fallamos.</li>
                </ul>
                <p>Los créditos por incumplimiento de SLA están detallados en el <a href="<?= $url('/status') ?>">acuerdo de nivel de servicio</a>.</p>

                <h2>9. Propiedad intelectual</h2>
                <p>Kydesk, su logo, código y diseño son propiedad de Kydesk SaaS. No podés copiar, modificar ni distribuir el software sin autorización escrita.</p>

                <h2>10. Limitación de responsabilidad</h2>
                <p>El servicio se provee "tal cual". En la máxima medida permitida por ley, Kydesk no será responsable por daños indirectos, lucro cesante o pérdida de datos. La responsabilidad total acumulada está limitada al monto pagado por el cliente en los últimos 12 meses.</p>

                <h2>11. Suspensión y terminación</h2>
                <p>Podemos suspender o terminar tu cuenta si:</p>
                <ul>
                    <li>Hay falta de pago después de 15 días del vencimiento.</li>
                    <li>Detectamos violación de uso aceptable.</li>
                    <li>Es requerido por orden legal.</li>
                </ul>
                <p>Vos podés terminar tu cuenta cuando quieras desde el panel o contactando soporte.</p>

                <h2>12. Modificaciones</h2>
                <p>Podemos modificar estos términos. Cambios materiales se notifican con 30 días de antelación. El uso continuado del servicio después de la notificación implica aceptación.</p>

                <h2>13. Ley aplicable</h2>
                <p>Estos términos se rigen por la legislación de la República Dominicana. Cualquier disputa se resolverá en los tribunales competentes de Santo Domingo, salvo que la ley aplicable disponga lo contrario.</p>

                <h2>14. Contacto</h2>
                <p>Preguntas sobre estos términos: <a href="mailto:legal@kydesk.com">legal@kydesk.com</a>.</p>
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
