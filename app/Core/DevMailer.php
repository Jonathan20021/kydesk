<?php
namespace App\Core;

/**
 * Email helper para el Developer Portal.
 * Centraliza todos los envíos para que siempre vayan por Resend (vía Mailer)
 * con branding consistente y logging adecuado.
 */
class DevMailer
{
    public static function portalName(): string
    {
        $app = Application::get();
        try {
            $row = $app->db->one("SELECT `value` FROM saas_settings WHERE `key`='dev_portal_name'");
            return (string)($row['value'] ?? 'Kydesk Developers');
        } catch (\Throwable $e) { return 'Kydesk Developers'; }
    }

    public static function url(string $path = ''): string
    {
        $app = Application::get();
        return rtrim($app->config['app']['url'], '/') . '/' . ltrim($path, '/');
    }

    /**
     * Wrapper consistente para todos los emails dev: pasa por Mailer (Resend default),
     * registra fallos en error_log y devuelve el resultado.
     */
    public static function send(string $to, string $subject, string $innerHtml, ?string $ctaText = null, ?string $ctaUrl = null, array $opts = []): array
    {
        $portal = self::portalName();
        $title = $subject;
        $html = Mailer::template($title, $innerHtml, $ctaText, $ctaUrl);

        try {
            $mailer = new Mailer(null, Application::get()->db);
            $res = $mailer->send($to, $subject, $html, null, $opts);
            if (!$res['ok']) {
                error_log("[DevMailer] Failed to send '$subject' to $to: " . ($res['error'] ?? 'unknown'));
            }
            return $res;
        } catch (\Throwable $e) {
            error_log("[DevMailer] Exception sending '$subject' to $to: " . $e->getMessage());
            return ['ok' => false, 'driver' => 'none', 'id' => null, 'error' => $e->getMessage()];
        }
    }

    // ─── Specific developer emails ─────────────────────────────────

    public static function emailVerification(string $to, string $name, string $verifyUrl, int $ttlMinutes): array
    {
        $portal = self::portalName();
        $hours = round($ttlMinutes / 60, 1);
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Confirma tu email para activar tu cuenta de developer en <strong>' . htmlspecialchars($portal) . '</strong>.</p>'
               . '<p style="font-size:13px;color:#6b6a78;">Este enlace expira en ' . $hours . ' horas. Si no creaste esta cuenta, ignora este email.</p>';
        return self::send($to, "Verifica tu email · $portal", $inner, 'Verificar email', $verifyUrl);
    }

    public static function passwordReset(string $to, string $name, string $resetUrl, int $ttlMinutes): array
    {
        $portal = self::portalName();
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Recibimos una solicitud para restablecer tu contraseña en <strong>' . htmlspecialchars($portal) . '</strong>.</p>'
               . '<p>Si fuiste tú, haz click en el botón. Si no, simplemente ignora este email — tu contraseña no cambiará.</p>'
               . '<p style="font-size:13px;color:#6b6a78;">Este enlace expira en ' . $ttlMinutes . ' minutos.</p>';
        return self::send($to, "Restablece tu contraseña · $portal", $inner, 'Crear nueva contraseña', $resetUrl);
    }

    public static function welcomeRegistered(string $to, string $name, string $planName): array
    {
        $portal = self::portalName();
        $dashUrl = self::url('/developers/dashboard');
        $docsUrl = self::url('/developers/docs');
        $inner = '<p>¡Bienvenido <strong>' . htmlspecialchars($name) . '</strong>!</p>'
               . '<p>Tu cuenta de developer está lista en <strong>' . htmlspecialchars($portal) . '</strong>. Estás suscrito al plan <strong>' . htmlspecialchars($planName) . '</strong>.</p>'
               . '<p>Próximos pasos:</p>'
               . '<ol style="padding-left:20px;line-height:1.8;">'
               . '<li>Crea tu primera <strong>app</strong> desde el panel — obtienes un workspace aislado.</li>'
               . '<li>Genera un <strong>token API</strong> (Bearer) y prueba <code>GET /api/v1/me</code>.</li>'
               . '<li>Lee la documentación o usa el <strong>AI Studio</strong> para integrar más rápido.</li>'
               . '</ol>'
               . '<p>¿Necesitas ayuda? Responde este email — te leemos.</p>';
        return self::send($to, "¡Bienvenido a $portal!", $inner, 'Abrir mi panel', $dashUrl);
    }

    public static function quotaWarning(string $to, string $name, int $used, int $limit, int $pct): array
    {
        $portal = self::portalName();
        $upgradeUrl = self::url('/developers/billing/plans');
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Has usado <strong>' . number_format($used) . '</strong> de tus <strong>' . number_format($limit) . '</strong> requests mensuales (<strong>' . $pct . '%</strong>) en ' . $portal . '.</p>'
               . '<p>Considera mejorar tu plan para evitar que la API empiece a rechazar requests cuando llegues al 100%.</p>';
        return self::send($to, "Cuota al $pct% · $portal", $inner, 'Ver planes', $upgradeUrl);
    }

    public static function quotaExceeded(string $to, string $name, int $limit): array
    {
        $portal = self::portalName();
        $upgradeUrl = self::url('/developers/billing/plans');
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p style="color:#b91c1c;"><strong>Has alcanzado tu cuota mensual</strong> de ' . number_format($limit) . ' requests en ' . $portal . '. La API está rechazando nuevas llamadas con HTTP 429.</p>'
               . '<p>Mejora tu plan ahora para reanudar el servicio inmediatamente, o espera al próximo ciclo.</p>';
        return self::send($to, "⚠ Cuota agotada · $portal", $inner, 'Mejorar plan', $upgradeUrl);
    }

    public static function invoiceCreated(string $to, string $name, string $invoiceNumber, float $total, string $currency, string $dueDate, int $invoiceId): array
    {
        $portal = self::portalName();
        $url = self::url('/developers/billing/invoices/' . $invoiceId);
        $totalStr = '$' . number_format($total, 2) . ' ' . $currency;
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Tienes una nueva factura disponible en ' . $portal . ':</p>'
               . '<ul style="line-height:1.8;">'
               . '<li>Número: <strong>' . htmlspecialchars($invoiceNumber) . '</strong></li>'
               . '<li>Monto: <strong>' . $totalStr . '</strong></li>'
               . '<li>Vence: <strong>' . htmlspecialchars($dueDate) . '</strong></li>'
               . '</ul>';
        return self::send($to, "Nueva factura $invoiceNumber · $portal", $inner, 'Ver factura', $url);
    }

    public static function paymentConfirmed(string $to, string $name, float $amount, string $currency, ?string $invoiceNumber = null): array
    {
        $portal = self::portalName();
        $url = self::url('/developers/billing');
        $amountStr = '$' . number_format($amount, 2) . ' ' . $currency;
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Confirmamos la recepción de tu pago de <strong>' . $amountStr . '</strong>'
               . ($invoiceNumber ? ' por la factura <strong>' . htmlspecialchars($invoiceNumber) . '</strong>' : '')
               . '. ¡Gracias!</p>';
        return self::send($to, "Pago recibido · $portal", $inner, 'Ver recibo', $url);
    }

    public static function webhookDisabled(string $to, string $name, string $webhookName, int $failures): array
    {
        $portal = self::portalName();
        $url = self::url('/developers/webhooks');
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Hemos <strong>desactivado automáticamente</strong> tu webhook <em>' . htmlspecialchars($webhookName) . '</em> después de <strong>' . $failures . ' entregas fallidas</strong> consecutivas.</p>'
               . '<p>Revisa tu endpoint y reactívalo desde el panel cuando esté listo.</p>';
        return self::send($to, "⚠ Webhook desactivado · $portal", $inner, 'Revisar webhooks', $url);
    }

    public static function subscriptionChanged(string $to, string $name, string $oldPlan, string $newPlan): array
    {
        $portal = self::portalName();
        $url = self::url('/developers/billing');
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Tu suscripción ha cambiado de <strong>' . htmlspecialchars($oldPlan) . '</strong> a <strong>' . htmlspecialchars($newPlan) . '</strong>.</p>'
               . '<p>Los nuevos límites están activos desde ahora.</p>';
        return self::send($to, "Suscripción actualizada · $portal", $inner, 'Ver detalles', $url);
    }

    public static function tokenCreated(string $to, string $name, string $tokenName, string $appName, string $preview): array
    {
        $portal = self::portalName();
        $url = self::url('/developers/apps');
        $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
               . '<p>Se acaba de crear un nuevo <strong>token API</strong> para tu app <em>' . htmlspecialchars($appName) . '</em>:</p>'
               . '<ul style="line-height:1.8;">'
               . '<li>Nombre: <strong>' . htmlspecialchars($tokenName) . '</strong></li>'
               . '<li>Preview: <code>' . htmlspecialchars($preview) . '</code></li>'
               . '</ul>'
               . '<p>Si no fuiste tú, <strong>revoca el token inmediatamente</strong> desde tu panel y cambia tu contraseña.</p>';
        return self::send($to, "Nuevo token API · $portal", $inner, 'Ver tokens', $url);
    }
}
