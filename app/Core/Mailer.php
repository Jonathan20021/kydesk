<?php
namespace App\Core;

class Mailer
{
    protected array $cfg;
    protected ?Database $db = null;

    public function __construct(?array $cfg = null, ?Database $db = null)
    {
        $app = Application::get();
        $this->cfg = $cfg ?? ($app->config['mail'] ?? []);
        $this->db = $db ?? ($app ? $app->db : null);
        $this->mergeFromSettings();
    }

    protected function mergeFromSettings(): void
    {
        if (!$this->db) return;
        try {
            $rows = $this->db->all("SELECT `key`, `value` FROM saas_settings WHERE `key` LIKE 'mail_%' OR `key` LIKE 'smtp_%' OR `key` IN ('resend_api_key','mail_from_email','mail_from_name','mail_driver','mail_reply_to')");
        } catch (\Throwable $e) { return; }
        foreach ($rows as $r) {
            $k = $r['key']; $v = (string)$r['value'];
            if ($v === '') continue;
            switch ($k) {
                case 'resend_api_key':   $this->cfg['resend']['api_key'] = $v; break;
                case 'mail_from_email':  $this->cfg['from']['email'] = $v; break;
                case 'mail_from_name':   $this->cfg['from']['name'] = $v; break;
                case 'mail_reply_to':    $this->cfg['reply_to'] = $v; break;
                case 'mail_driver':      $this->cfg['driver'] = $v; break;
                case 'smtp_host':        $this->cfg['smtp']['host'] = $v; break;
                case 'smtp_port':        $this->cfg['smtp']['port'] = (int)$v; break;
                case 'smtp_user':        $this->cfg['smtp']['user'] = $v; break;
                case 'smtp_pass':        $this->cfg['smtp']['pass'] = $v; break;
                case 'smtp_secure':      $this->cfg['smtp']['secure'] = $v; break;
            }
        }
    }

    /**
     * Send an email. Tries Resend first; falls back to SMTP if it fails.
     * @param string|array $to  email or [['email'=>..., 'name'=>...], ...]
     * @return array ['ok'=>bool, 'driver'=>string, 'id'=>?string, 'error'=>?string]
     */
    public function send($to, string $subject, string $html, ?string $text = null, array $opts = []): array
    {
        $recipients = $this->normalizeRecipients($to);
        if (!$recipients) return $this->finish(false, 'none', null, 'Sin destinatarios', $to, $subject);

        $fromEmail = $opts['from_email'] ?? ($this->cfg['from']['email'] ?? 'no-reply@kyrosrd.com');
        $fromName  = $opts['from_name']  ?? ($this->cfg['from']['name']  ?? 'Kydesk Helpdesk');
        $replyTo   = $opts['reply_to']   ?? ($this->cfg['reply_to'] ?? null);
        $text      = $text ?? trim(strip_tags(preg_replace('/<br\s*\/?\s*>/i', "\n", $html)));

        $driver = strtolower($this->cfg['driver'] ?? 'resend');
        $tryOrder = $driver === 'smtp' ? ['smtp','resend'] : ['resend','smtp'];

        $lastError = null;
        foreach ($tryOrder as $d) {
            try {
                if ($d === 'resend') {
                    $apiKey = $this->cfg['resend']['api_key'] ?? '';
                    if ($apiKey === '') { $lastError = 'Resend API key no configurada'; continue; }
                    $res = $this->sendResend($apiKey, $recipients, $fromEmail, $fromName, $subject, $html, $text, $replyTo, $opts);
                    if ($res['ok']) return $this->finish(true, 'resend', $res['id'] ?? null, null, $to, $subject);
                    $lastError = $res['error'] ?? 'Resend falló';
                } else {
                    $smtp = $this->cfg['smtp'] ?? [];
                    if (empty($smtp['host'])) { $lastError = $lastError ?: 'SMTP no configurado'; continue; }
                    $res = $this->sendSmtp($smtp, $recipients, $fromEmail, $fromName, $subject, $html, $text, $replyTo, $opts);
                    if ($res['ok']) return $this->finish(true, 'smtp', null, null, $to, $subject);
                    $lastError = $res['error'] ?? 'SMTP falló';
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }
        return $this->finish(false, 'none', null, $lastError ?: 'Sin transportes disponibles', $to, $subject);
    }

    protected function normalizeRecipients($to): array
    {
        $out = [];
        if (is_string($to)) {
            $email = trim($to);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $out[] = ['email' => $email];
        } elseif (is_array($to)) {
            // ['a@b.com','c@d.com']
            if (array_keys($to) === range(0, count($to)-1) && (!isset($to[0]) || is_string($to[0]))) {
                foreach ($to as $e) {
                    $e = trim((string)$e);
                    if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $out[] = ['email' => $e];
                }
            } else {
                // single ['email'=>..., 'name'=>...]
                if (isset($to['email'])) $to = [$to];
                foreach ($to as $r) {
                    $email = trim((string)($r['email'] ?? ''));
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $out[] = ['email' => $email, 'name' => $r['name'] ?? null];
                    }
                }
            }
        }
        return $out;
    }

    protected function sendResend(string $apiKey, array $recipients, string $fromEmail, string $fromName, string $subject, string $html, string $text, ?string $replyTo, array $opts): array
    {
        $payload = [
            'from'    => sprintf('%s <%s>', $this->encodeName($fromName), $fromEmail),
            'to'      => array_map(fn($r) => $r['email'], $recipients),
            'subject' => $subject,
            'html'    => $html,
            'text'    => $text,
        ];
        if ($replyTo) $payload['reply_to'] = $replyTo;
        if (!empty($opts['cc']))  $payload['cc']  = (array)$opts['cc'];
        if (!empty($opts['bcc'])) $payload['bcc'] = (array)$opts['bcc'];
        if (!empty($opts['tags']) && is_array($opts['tags'])) $payload['tags'] = $opts['tags'];
        // Attachments (path-based, encoded inline as base64 for Resend)
        if (!empty($opts['attachments']) && is_array($opts['attachments'])) {
            $atts = [];
            foreach ($opts['attachments'] as $a) {
                $path = $a['path'] ?? null;
                if (!$path || !is_file($path)) continue;
                $data = @file_get_contents($path);
                if ($data === false) continue;
                $atts[] = [
                    'filename' => $a['name'] ?? basename($path),
                    'content' => base64_encode($data),
                ];
            }
            if ($atts) $payload['attachments'] = $atts;
        }

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];
        if (!empty($opts['idempotency_key'])) {
            $key = substr(preg_replace('/[^a-zA-Z0-9._:-]/', '-', (string)$opts['idempotency_key']), 0, 256);
            if ($key !== '') $headers[] = 'Idempotency-Key: ' . $key;
        }

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) return ['ok' => false, 'error' => 'cURL: ' . $err];
        $json = json_decode($body, true);
        if ($code >= 200 && $code < 300 && isset($json['id'])) {
            return ['ok' => true, 'id' => $json['id']];
        }
        $msg = is_array($json) ? ($json['message'] ?? ($json['error']['message'] ?? json_encode($json))) : $body;
        return ['ok' => false, 'error' => 'Resend HTTP ' . $code . ': ' . $msg];
    }

    protected function sendSmtp(array $smtp, array $recipients, string $fromEmail, string $fromName, string $subject, string $html, string $text, ?string $replyTo, array $opts): array
    {
        $host   = (string)($smtp['host'] ?? '');
        $port   = (int)($smtp['port'] ?? 587);
        $user   = (string)($smtp['user'] ?? '');
        $pass   = (string)($smtp['pass'] ?? '');
        $secure = strtolower((string)($smtp['secure'] ?? 'tls')); // tls | ssl | ''

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $errno = 0; $errstr = '';
        $sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$sock) return ['ok' => false, 'error' => "SMTP connect $remote: $errstr"];
        stream_set_timeout($sock, 15);

        $read = function() use ($sock) {
            $data = '';
            while (($line = fgets($sock, 1024)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };
        $write = function(string $cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };
        $expect = function(string $resp, string $code) {
            return strncmp(ltrim($resp), $code, 3) === 0;
        };

        $banner = $read();
        if (!$expect($banner, '220')) { fclose($sock); return ['ok' => false, 'error' => 'SMTP banner: ' . trim($banner)]; }

        $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $write('EHLO ' . $hostname);
        $ehlo = $read();
        if (!$expect($ehlo, '250')) { fclose($sock); return ['ok' => false, 'error' => 'EHLO failed: ' . trim($ehlo)]; }

        if ($secure === 'tls') {
            $write('STARTTLS');
            $r = $read();
            if (!$expect($r, '220')) { fclose($sock); return ['ok' => false, 'error' => 'STARTTLS failed: ' . trim($r)]; }
            if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($sock); return ['ok' => false, 'error' => 'TLS handshake failed'];
            }
            $write('EHLO ' . $hostname);
            $ehlo2 = $read();
            if (!$expect($ehlo2, '250')) { fclose($sock); return ['ok' => false, 'error' => 'EHLO(2) failed: ' . trim($ehlo2)]; }
        }

        if ($user !== '') {
            $write('AUTH LOGIN');
            $r = $read();
            if (!$expect($r, '334')) { fclose($sock); return ['ok' => false, 'error' => 'AUTH LOGIN: ' . trim($r)]; }
            $write(base64_encode($user));
            $r = $read();
            if (!$expect($r, '334')) { fclose($sock); return ['ok' => false, 'error' => 'AUTH user: ' . trim($r)]; }
            $write(base64_encode($pass));
            $r = $read();
            if (!$expect($r, '235')) { fclose($sock); return ['ok' => false, 'error' => 'AUTH pass: ' . trim($r)]; }
        }

        $write('MAIL FROM:<' . $fromEmail . '>');
        $r = $read();
        if (!$expect($r, '250')) { fclose($sock); return ['ok' => false, 'error' => 'MAIL FROM: ' . trim($r)]; }

        foreach ($recipients as $rcpt) {
            $write('RCPT TO:<' . $rcpt['email'] . '>');
            $r = $read();
            if (!$expect($r, '250') && !$expect($r, '251')) { fclose($sock); return ['ok' => false, 'error' => 'RCPT TO: ' . trim($r)]; }
        }

        $write('DATA');
        $r = $read();
        if (!$expect($r, '354')) { fclose($sock); return ['ok' => false, 'error' => 'DATA: ' . trim($r)]; }

        $boundary = 'kydesk-' . bin2hex(random_bytes(8));
        $headers  = [];
        $headers[] = 'From: ' . $this->encodeName($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'To: ' . implode(', ', array_map(fn($x) => '<' . $x['email'] . '>', $recipients));
        if ($replyTo) $headers[] = 'Reply-To: <' . $replyTo . '>';
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $hostname . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $text . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= "--$boundary--\r\n";

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        // Dot-stuffing
        $message = preg_replace("/^\./m", "..", $message);

        $write($message);
        $write('.');
        $r = $read();
        if (!$expect($r, '250')) { fclose($sock); return ['ok' => false, 'error' => 'After DATA: ' . trim($r)]; }

        $write('QUIT');
        @fclose($sock);
        return ['ok' => true];
    }

    protected function encodeName(string $name): string
    {
        return preg_match('/[\x80-\xff]/', $name)
            ? '=?UTF-8?B?' . base64_encode($name) . '?='
            : '"' . str_replace('"', '\\"', $name) . '"';
    }
    protected function encodeHeader(string $value): string
    {
        return preg_match('/[\x80-\xff]/', $value)
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }

    protected function finish(bool $ok, string $driver, ?string $id, ?string $error, $to, string $subject): array
    {
        if ($this->db) {
            try {
                $this->db->insert('email_log', [
                    'driver'  => $driver,
                    'to_addr' => is_string($to) ? $to : json_encode($to),
                    'subject' => $subject,
                    'status'  => $ok ? 'sent' : 'failed',
                    'message_id' => $id,
                    'error'   => $error,
                ]);
            } catch (\Throwable $e) { /* table may not exist yet */ }
        }
        return ['ok' => $ok, 'driver' => $driver, 'id' => $id, 'error' => $error];
    }

    /** Render a simple branded HTML wrapper around inner content. */
    public static function template(string $title, string $innerHtml, ?string $ctaText = null, ?string $ctaUrl = null): string
    {
        $brand = htmlspecialchars(Application::get()->config['app']['name'] ?? 'Kydesk Helpdesk');
        $appUrl = htmlspecialchars(Application::get()->config['app']['url'] ?? '');
        $cta = '';
        if ($ctaText && $ctaUrl) {
            $cta = '<p style="margin:24px 0;"><a href="' . htmlspecialchars($ctaUrl) . '" style="background:#6366f1;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;display:inline-block;">' . htmlspecialchars($ctaText) . '</a></p>';
        }
        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title></head>'
            . '<body style="margin:0;background:#f4f5f8;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#16151b;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f5f8;padding:32px 16px;"><tr><td align="center">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px -8px rgba(22,21,27,.08);max-width:600px;width:100%;">'
            . '<tr><td style="padding:24px 28px;border-bottom:1px solid #ececf0;"><strong style="font-size:18px;color:#6366f1;">' . $brand . '</strong></td></tr>'
            . '<tr><td style="padding:28px;">'
            . '<h1 style="font-size:20px;margin:0 0 16px;color:#16151b;">' . htmlspecialchars($title) . '</h1>'
            . '<div style="font-size:15px;line-height:1.6;color:#3a3946;">' . $innerHtml . '</div>'
            . $cta
            . '</td></tr>'
            . '<tr><td style="padding:18px 28px;background:#fafafb;border-top:1px solid #ececf0;font-size:12px;color:#6b6a78;">'
            . 'Enviado por ' . $brand . ' · <a href="' . $appUrl . '" style="color:#6366f1;text-decoration:none;">' . $appUrl . '</a>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
