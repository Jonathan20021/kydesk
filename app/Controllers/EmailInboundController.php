<?php
namespace App\Controllers;

use App\Core\Controller;

class EmailInboundController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('email_inbound');
        $this->requireCan('email.view');

        $accounts = $this->db->all('SELECT * FROM email_accounts WHERE tenant_id = ? ORDER BY created_at DESC', [$tenant->id]);
        $messages = $this->db->all(
            'SELECT m.*, a.name AS account_name, a.email AS account_email, t.code AS ticket_code
             FROM email_messages m
             LEFT JOIN email_accounts a ON a.id = m.account_id
             LEFT JOIN tickets t ON t.id = m.ticket_id
             WHERE m.tenant_id = ? ORDER BY m.received_at DESC LIMIT 100',
            [$tenant->id]
        );
        $stats = [
            'accounts' => count($accounts),
            'inbound_today' => (int)$this->db->val("SELECT COUNT(*) FROM email_messages WHERE tenant_id = ? AND DATE(received_at) = CURDATE() AND direction = 'inbound'", [$tenant->id]),
            'tickets_via_email' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id = ? AND channel = 'email'", [$tenant->id]),
        ];
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');

        $categories = $this->db->all('SELECT id, name FROM ticket_categories WHERE tenant_id = ? ORDER BY name', [$tenant->id]);
        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tenant->id]);

        $this->render('email_inbound/index', [
            'title' => 'Email-to-Ticket',
            'accounts' => $accounts,
            'messages' => $messages,
            'stats' => $stats,
            'appUrl' => $appUrl,
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('email_inbound');
        $this->requireCan('email.config');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error','Nombre y email son obligatorios.');
            $this->redirect('/t/' . $tenant->slug . '/email-inbound');
        }
        $method = (string)$this->input('fetch_method', 'imap');
        if (!in_array($method, ['imap','forward'], true)) $method = 'imap';
        $existing = $this->db->val('SELECT id FROM email_accounts WHERE tenant_id=? AND email=?', [$tenant->id, $email]);
        if ($existing) {
            $this->session->flash('error','Ya existe un buzón con ese email.');
            $this->redirect('/t/' . $tenant->slug . '/email-inbound');
        }

        $forwardToken = bin2hex(random_bytes(16));
        $this->db->insert('email_accounts', [
            'tenant_id'           => $tenant->id,
            'name'                => $name,
            'email'               => $email,
            'fetch_method'        => $method,
            'imap_host'           => (string)$this->input('imap_host','') ?: null,
            'imap_port'           => ((int)$this->input('imap_port', 993)) ?: 993,
            'imap_user'           => (string)$this->input('imap_user','') ?: null,
            'imap_pass'           => (string)$this->input('imap_pass','') ?: null,
            'imap_encryption'     => in_array($this->input('imap_encryption'), ['ssl','tls','none'], true) ? (string)$this->input('imap_encryption') : 'ssl',
            'imap_folder'         => (string)$this->input('imap_folder','INBOX') ?: 'INBOX',
            'forward_token'       => $forwardToken,
            'default_category_id' => ((int)$this->input('default_category_id', 0)) ?: null,
            'default_priority'    => in_array($this->input('default_priority'), ['low','medium','high','urgent'], true) ? (string)$this->input('default_priority') : 'medium',
            'auto_assign_to'      => ((int)$this->input('auto_assign_to', 0)) ?: null,
            'is_active'           => (int)($this->input('is_active', 1) ? 1 : 0),
        ]);
        $this->session->flash('success','Buzón configurado.');
        $this->redirect('/t/' . $tenant->slug . '/email-inbound');
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('email_inbound');
        $this->requireCan('email.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $a = $this->db->one('SELECT * FROM email_accounts WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$a) { $this->redirect('/t/' . $tenant->slug . '/email-inbound'); }

        $data = [
            'name'                => trim((string)$this->input('name', $a['name'])),
            'imap_host'           => (string)$this->input('imap_host','') ?: null,
            'imap_port'           => ((int)$this->input('imap_port', $a['imap_port'])) ?: 993,
            'imap_user'           => (string)$this->input('imap_user','') ?: null,
            'imap_encryption'     => in_array($this->input('imap_encryption'), ['ssl','tls','none'], true) ? (string)$this->input('imap_encryption') : $a['imap_encryption'],
            'imap_folder'         => (string)$this->input('imap_folder', $a['imap_folder']) ?: 'INBOX',
            'default_category_id' => ((int)$this->input('default_category_id', 0)) ?: null,
            'default_priority'    => in_array($this->input('default_priority'), ['low','medium','high','urgent'], true) ? (string)$this->input('default_priority') : $a['default_priority'],
            'auto_assign_to'      => ((int)$this->input('auto_assign_to', 0)) ?: null,
            'is_active'           => (int)($this->input('is_active') ? 1 : 0),
        ];
        $newPwd = (string)$this->input('imap_pass','');
        if ($newPwd !== '') $data['imap_pass'] = $newPwd;

        $this->db->update('email_accounts', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Buzón actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/email-inbound');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('email_inbound');
        $this->requireCan('email.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('email_accounts', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Buzón eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/email-inbound');
    }

    /** Trigger manual fetch para una cuenta. */
    public function fetch(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('email_inbound');
        $this->requireCan('email.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $a = $this->db->one('SELECT * FROM email_accounts WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$a) { $this->redirect('/t/' . $tenant->slug . '/email-inbound'); }

        $count = self::fetchAccount($a, $this->db);
        $this->session->flash('success', "Procesados $count emails.");
        $this->redirect('/t/' . $tenant->slug . '/email-inbound');
    }

    /**
     * Endpoint público para forward de emails (Mailgun-style payload o JSON con
     * keys: from, to, subject, text, html, message_id, in_reply_to, references).
     * Headers: x-forward-token: <forward_token>
     */
    public function forwardWebhook(array $params): void
    {
        $token = $_SERVER['HTTP_X_FORWARD_TOKEN'] ?? ($_GET['token'] ?? '');
        if (!$token) { http_response_code(401); echo 'token required'; return; }
        $account = $this->db->one('SELECT * FROM email_accounts WHERE forward_token = ? AND is_active = 1', [$token]);
        if (!$account) { http_response_code(404); echo 'invalid token'; return; }

        $raw = file_get_contents('php://input');
        $payload = [];
        if ($raw && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'json') !== false) {
            $payload = json_decode($raw, true) ?: [];
        } else {
            $payload = $_POST;
        }
        $payload = $this->normalizePayload($payload);
        $msgId = self::ingestEmail((int)$account['id'], $payload, $this->db);
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'message_id' => $msgId]);
    }

    /* ─────── Helpers IMAP / ingestion ─────── */

    /** Devuelve número de mensajes procesados. */
    public static function fetchAccount(array $account, $db): int
    {
        if (($account['fetch_method'] ?? 'imap') !== 'imap') return 0;
        if (!function_exists('imap_open')) {
            $db->update('email_accounts', ['last_error' => 'PHP IMAP extension no disponible'], 'id = :id', ['id' => (int)$account['id']]);
            return 0;
        }
        $enc = $account['imap_encryption'] === 'ssl' ? '/ssl/novalidate-cert' : ($account['imap_encryption'] === 'tls' ? '/tls' : '');
        $folder = $account['imap_folder'] ?: 'INBOX';
        $mbox = '{' . $account['imap_host'] . ':' . (int)$account['imap_port'] . '/imap' . $enc . '}' . $folder;

        try {
            $stream = @imap_open($mbox, $account['imap_user'], $account['imap_pass']);
            if (!$stream) {
                $err = imap_last_error() ?: 'No se pudo conectar';
                $db->update('email_accounts', ['last_error' => substr($err,0,500)], 'id = :id', ['id' => (int)$account['id']]);
                return 0;
            }
            $emails = imap_search($stream, 'UNSEEN');
            $count = 0;
            if ($emails) {
                foreach ($emails as $num) {
                    $headerInfo = imap_headerinfo($stream, $num);
                    $structure = imap_fetchstructure($stream, $num);
                    $body = self::extractIMAPBody($stream, $num, $structure);
                    $payload = [
                        'from_email' => isset($headerInfo->from[0]) ? $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host : null,
                        'from_name'  => isset($headerInfo->from[0]->personal) ? imap_utf8($headerInfo->from[0]->personal) : null,
                        'to_email'   => $account['email'],
                        'subject'    => imap_utf8($headerInfo->subject ?? ''),
                        'message_id' => trim($headerInfo->message_id ?? '', '<>'),
                        'in_reply_to'=> trim($headerInfo->in_reply_to ?? '', '<>'),
                        'references' => $headerInfo->references ?? null,
                        'body_text'  => $body['text'],
                        'body_html'  => $body['html'],
                        'received_at'=> date('Y-m-d H:i:s', strtotime($headerInfo->date ?? 'now')),
                    ];
                    self::ingestEmail((int)$account['id'], $payload, $db);
                    imap_setflag_full($stream, $num, '\\Seen');
                    $count++;
                }
            }
            imap_close($stream);
            $db->update('email_accounts', [
                'last_fetched_at' => date('Y-m-d H:i:s'),
                'last_error' => null,
                'fetch_count' => ((int)$account['fetch_count']) + $count,
            ], 'id = :id', ['id' => (int)$account['id']]);
            return $count;
        } catch (\Throwable $e) {
            $db->update('email_accounts', ['last_error' => substr($e->getMessage(),0,500)], 'id = :id', ['id' => (int)$account['id']]);
            return 0;
        }
    }

    protected static function extractIMAPBody($stream, int $num, $structure): array
    {
        $text = ''; $html = '';
        $walker = function ($struct, $partNum = '') use (&$walker, $stream, $num, &$text, &$html) {
            if (isset($struct->parts) && is_array($struct->parts)) {
                foreach ($struct->parts as $i => $part) {
                    $newPart = $partNum === '' ? (string)($i + 1) : $partNum . '.' . ($i + 1);
                    $walker($part, $newPart);
                }
                return;
            }
            $section = $partNum === '' ? '1' : $partNum;
            $data = imap_fetchbody($stream, $num, $section);
            if ($struct->encoding === 3) $data = base64_decode($data);
            elseif ($struct->encoding === 4) $data = quoted_printable_decode($data);
            $subtype = strtolower($struct->subtype ?? '');
            if ($subtype === 'plain') $text .= $data;
            elseif ($subtype === 'html') $html .= $data;
        };
        $walker($structure);
        return ['text' => $text, 'html' => $html];
    }

    /** Normaliza payloads diversos (Mailgun, Postmark, JSON simple). */
    protected function normalizePayload(array $p): array
    {
        return [
            'from_email' => $p['from_email'] ?? $p['from'] ?? $p['sender'] ?? null,
            'from_name'  => $p['from_name'] ?? null,
            'to_email'   => $p['to_email'] ?? $p['to'] ?? $p['recipient'] ?? null,
            'subject'    => $p['subject'] ?? '',
            'message_id' => $p['message_id'] ?? $p['Message-Id'] ?? null,
            'in_reply_to'=> $p['in_reply_to'] ?? $p['In-Reply-To'] ?? null,
            'references' => $p['references'] ?? $p['References'] ?? null,
            'body_text'  => $p['body_text'] ?? $p['text'] ?? $p['stripped-text'] ?? '',
            'body_html'  => $p['body_html'] ?? $p['html'] ?? $p['stripped-html'] ?? '',
            'received_at'=> $p['received_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    /** Ingesta + threading. Devuelve id de email_messages. */
    public static function ingestEmail(int $accountId, array $payload, $db): int
    {
        $account = $db->one('SELECT * FROM email_accounts WHERE id = ?', [$accountId]);
        if (!$account) return 0;

        $msgId = $payload['message_id'] ?: null;
        // Avoid duplicates by message_id
        if ($msgId) {
            $dup = $db->one('SELECT id FROM email_messages WHERE tenant_id=? AND message_id=?', [(int)$account['tenant_id'], $msgId]);
            if ($dup) return (int)$dup['id'];
        }

        // Parse from name from "Name <email>" if needed
        $fromEmail = trim((string)($payload['from_email'] ?? ''));
        $fromName = trim((string)($payload['from_name'] ?? ''));
        if ($fromEmail && preg_match('/(.*)<(.+)>/', $fromEmail, $m)) {
            if (!$fromName) $fromName = trim($m[1], '" ');
            $fromEmail = trim($m[2]);
        }

        $bodyText = (string)($payload['body_text'] ?? '');
        if (empty($bodyText) && !empty($payload['body_html'])) {
            $bodyText = trim(strip_tags($payload['body_html']));
        }

        $emailRowId = $db->insert('email_messages', [
            'tenant_id'   => (int)$account['tenant_id'],
            'account_id'  => $accountId,
            'message_id'  => $msgId,
            'in_reply_to' => $payload['in_reply_to'] ?? null,
            'references'  => $payload['references'] ?? null,
            'direction'   => 'inbound',
            'from_email'  => $fromEmail ?: null,
            'from_name'   => $fromName ?: null,
            'to_email'    => $payload['to_email'] ?? $account['email'],
            'subject'     => mb_substr((string)($payload['subject'] ?? ''), 0, 255),
            'body_text'   => $bodyText,
            'body_html'   => $payload['body_html'] ?? null,
            'received_at' => $payload['received_at'] ?? date('Y-m-d H:i:s'),
            'raw_size'    => strlen($bodyText) + strlen((string)($payload['body_html'] ?? '')),
            'status'      => 'new',
        ]);

        // Threading: si in_reply_to apunta a un email anterior con ticket_id, agregar como comentario
        $ticketId = null; $commentId = null;
        if (!empty($payload['in_reply_to'])) {
            $parent = $db->one('SELECT * FROM email_messages WHERE tenant_id=? AND message_id=?', [(int)$account['tenant_id'], $payload['in_reply_to']]);
            if ($parent && $parent['ticket_id']) {
                $ticketId = (int)$parent['ticket_id'];
                $commentId = $db->insert('ticket_comments', [
                    'tenant_id'    => (int)$account['tenant_id'],
                    'ticket_id'    => $ticketId,
                    'user_id'      => null,
                    'author_name'  => $fromName ?: $fromEmail,
                    'author_email' => $fromEmail,
                    'body'         => $bodyText,
                    'is_internal'  => 0,
                ]);
                $db->update('tickets', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $ticketId]);
            }
        }

        // Si no se ligó a un ticket existente, crear uno nuevo
        if (!$ticketId) {
            $code = self::generateTicketCode((int)$account['tenant_id'], $db);
            $companyId = null;
            if ($fromEmail) {
                $companyId = (int)$db->val(
                    "SELECT c.id FROM companies c LEFT JOIN contacts ct ON ct.company_id = c.id
                     WHERE c.tenant_id = ? AND (ct.email = ? OR c.name LIKE CONCAT('%@', SUBSTRING_INDEX(?, '@', -1), '%'))
                     LIMIT 1",
                    [(int)$account['tenant_id'], $fromEmail, $fromEmail]
                ) ?: 0 ?: null;
            }
            $token = bin2hex(random_bytes(16));
            $ticketId = $db->insert('tickets', [
                'tenant_id'        => (int)$account['tenant_id'],
                'code'             => $code,
                'subject'          => mb_substr((string)($payload['subject'] ?? '(sin asunto)'), 0, 200),
                'description'      => $bodyText,
                'category_id'      => $account['default_category_id'] ?? null,
                'company_id'       => $companyId,
                'priority'         => $account['default_priority'] ?? 'medium',
                'status'           => 'open',
                'channel'          => 'email',
                'requester_name'   => $fromName ?: ($fromEmail ?: 'Cliente'),
                'requester_email'  => $fromEmail,
                'assigned_to'      => $account['auto_assign_to'] ?? null,
                'public_token'     => $token,
            ]);
        }

        $db->update('email_messages', [
            'ticket_id' => $ticketId,
            'comment_id' => $commentId,
            'status' => 'threaded',
        ], 'id = :id', ['id' => $emailRowId]);

        return $emailRowId;
    }

    protected static function generateTicketCode(int $tenantId, $db): string
    {
        for ($i = 0; $i < 8; $i++) {
            $code = 'TKT-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            if (!$db->val('SELECT id FROM tickets WHERE tenant_id=? AND code=?', [$tenantId, $code])) return $code;
        }
        return 'TKT-' . substr((string)time(), -6);
    }
}
