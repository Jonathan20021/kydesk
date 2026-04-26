<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;

class SupportController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $u = $this->auth->user();
        $tickets = $this->db->all(
            "SELECT id, code, subject, status, priority, created_at, updated_at FROM saas_support_tickets
             WHERE tenant_id=? AND (user_id=? OR user_id IS NULL)
             ORDER BY id DESC LIMIT 50",
            [$tenant->id, $u['id']]
        );
        $this->render('support/index', [
            'title' => 'Soporte directo · Kydesk',
            'tickets' => $tickets,
        ]);
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $u = $this->auth->user();
        $id = (int)$params['id'];
        $ticket = $this->db->one('SELECT * FROM saas_support_tickets WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$ticket) {
            $this->session->flash('error', 'Ticket no encontrado.');
            $this->redirect('/t/' . $tenant->slug . '/support');
        }
        $replies = $this->db->all('SELECT * FROM saas_support_replies WHERE ticket_id=? ORDER BY created_at ASC', [$id]);
        $this->render('support/show', [
            'title' => $ticket['code'] . ' · Soporte',
            'ticket' => $ticket,
            'replies' => $replies,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $this->validateCsrf();
        $u = $this->auth->user();

        $subject  = trim((string)$this->input('subject', ''));
        $body     = trim((string)$this->input('body', ''));
        $priority = (string)$this->input('priority', 'medium');
        if (!in_array($priority, ['low','medium','high','urgent'], true)) $priority = 'medium';
        if ($subject === '' || $body === '') {
            $this->session->flash('error', 'Asunto y mensaje son obligatorios.');
            $this->redirect('/t/' . $tenant->slug . '/support');
        }

        $code = 'SUP-' . strtoupper(bin2hex(random_bytes(3)));
        $id = $this->db->insert('saas_support_tickets', [
            'code' => $code,
            'tenant_id' => $tenant->id,
            'user_id' => $u['id'],
            'subject' => $subject,
            'body' => $body,
            'priority' => $priority,
            'status' => 'open',
        ]);

        // Email al super admin
        try {
            $sa = $this->db->val("SELECT `value` FROM saas_settings WHERE `key`='saas_support_email'");
            $sa = $sa ?: 'jonathansandoval@kyrosrd.com';
            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $inner = '<p>Nuevo ticket de soporte de <strong>' . htmlspecialchars($tenant->name) . '</strong> (' . htmlspecialchars($tenant->slug) . ')</p>'
                . '<p><strong>Solicitante:</strong> ' . htmlspecialchars($u['name']) . ' &lt;' . htmlspecialchars($u['email']) . '&gt;</p>'
                . '<p><strong>Prioridad:</strong> ' . htmlspecialchars($priority) . '</p>'
                . '<p><strong>Asunto:</strong> ' . htmlspecialchars($subject) . '</p>'
                . '<hr><p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</p>';
            (new Mailer())->send(
                $sa,
                '[' . $code . '] ' . $subject,
                Mailer::template('Nuevo ticket de soporte SaaS', $inner, 'Abrir en panel', $appUrl . '/admin/support'),
                null,
                ['reply_to' => $u['email']]
            );
        } catch (\Throwable $e) { /* ignore */ }

        $this->session->flash('success', 'Ticket de soporte enviado. Te responderemos pronto.');
        $this->redirect('/t/' . $tenant->slug . '/support/' . $id);
    }

    public function reply(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $this->validateCsrf();
        $u = $this->auth->user();
        $id = (int)$params['id'];
        $ticket = $this->db->one('SELECT * FROM saas_support_tickets WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$ticket) $this->redirect('/t/' . $tenant->slug . '/support');
        $body = trim((string)$this->input('body', ''));
        if ($body === '') $this->redirect('/t/' . $tenant->slug . '/support/' . $id);

        $this->db->insert('saas_support_replies', [
            'ticket_id' => $id,
            'author_type' => 'tenant',
            'author_id' => $u['id'],
            'author_name' => $u['name'],
            'body' => $body,
        ]);
        $this->db->update('saas_support_tickets', ['status' => 'open', 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);

        try {
            $sa = $this->db->val("SELECT `value` FROM saas_settings WHERE `key`='saas_support_email'") ?: 'jonathansandoval@kyrosrd.com';
            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $inner = '<p><strong>' . htmlspecialchars($u['name']) . '</strong> respondió en el ticket <strong>' . htmlspecialchars($ticket['code']) . '</strong>.</p>'
                . '<hr><p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</p>';
            (new Mailer())->send(
                $sa,
                '[' . $ticket['code'] . '] Nueva respuesta del cliente',
                Mailer::template('Nueva respuesta del cliente', $inner, 'Abrir', $appUrl . '/admin/support'),
                null, ['reply_to' => $u['email']]
            );
        } catch (\Throwable $e) {}

        $this->session->flash('success', 'Respuesta enviada.');
        $this->redirect('/t/' . $tenant->slug . '/support/' . $id);
    }
}
