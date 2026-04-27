<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Events;
use App\Core\Helpers;
use App\Core\Tenant;
use App\Core\Mailer;

class PortalController extends Controller
{
    protected function resolveTenant(string $slug): Tenant
    {
        $t = Tenant::resolve($slug);
        if (!$t) {
            http_response_code(404);
            echo $this->view->render('errors/404', ['message' => 'Portal no encontrado'], 'public');
            exit;
        }
        $this->app->tenant = $t;
        return $t;
    }

    public function index(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $this->render('portal/index', ['title' => 'Portal de soporte · ' . $tenant->name, 'tenant' => $tenant, 'showPoweredFooter' => true], 'public');
    }

    public function create(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $categories = $this->db->all('SELECT * FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $companyId = (int)($_GET['company'] ?? 0);
        $company = null;
        $contacts = [];
        if ($companyId > 0) {
            $company = $this->db->one('SELECT id, name, industry FROM companies WHERE id=? AND tenant_id=?', [$companyId, $tenant->id]);
            if ($company) {
                $contacts = $this->db->all(
                    'SELECT id, name, email, phone, title FROM contacts WHERE tenant_id=? AND company_id=? AND email IS NOT NULL ORDER BY name LIMIT 100',
                    [$tenant->id, (int)$company['id']]
                );
            }
        }
        $this->render('portal/create', [
            'title' => 'Crear ticket · ' . $tenant->name,
            'tenant' => $tenant,
            'categories' => $categories,
            'company' => $company,
            'contacts' => $contacts,
            'showPoweredFooter' => true,
        ], 'public');
    }

    public function store(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $this->validateCsrf();
        $name = trim((string)$this->input('name'));
        $email = trim((string)$this->input('email'));
        $subject = trim((string)$this->input('subject'));
        $body = trim((string)$this->input('description'));
        if (!$name || !$email || !$subject || !$body) {
            $this->session->flash('error','Todos los campos son obligatorios.');
            $this->redirect('/portal/' . $tenant->slug . '/new');
        }

        $monthly = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tenant->id]);
        $maxMonthly = \App\Core\Plan::limit($tenant, 'tickets_per_month');
        if (is_int($maxMonthly) && $monthly >= $maxMonthly) {
            $this->session->flash('error', sprintf('Este workspace alcanzó su límite mensual de tickets (%d). Volvé pronto.', $maxMonthly));
            $this->redirect('/portal/' . $tenant->slug);
        }

        $token = bin2hex(random_bytes(16));
        $companyId = (int)$this->input('company_id', 0);
        if ($companyId > 0) {
            $exists = $this->db->one('SELECT id FROM companies WHERE id=? AND tenant_id=?', [$companyId, $tenant->id]);
            if (!$exists) $companyId = 0;
        }
        // Auto-detect company by requester email domain if not selected
        if (!$companyId) {
            $auto = Helpers::findCompanyByEmail($tenant->id, $email);
            if ($auto) $companyId = $auto;
        }
        $id = $this->db->insert('tickets', [
            'tenant_id' => $tenant->id,
            'code' => 'TMP-' . bin2hex(random_bytes(4)),
            'subject' => $subject,
            'description' => $body,
            'category_id' => ((int)$this->input('category_id',0)) ?: null,
            'company_id' => $companyId ?: null,
            'priority' => (string)$this->input('priority','medium'),
            'status' => 'open',
            'channel' => 'portal',
            'requester_name' => $name,
            'requester_email' => $email,
            'requester_phone' => (string)$this->input('phone',''),
            'public_token' => $token,
        ]);
        $code = Helpers::ticketCode($tenant->id, $id);
        $this->db->update('tickets', ['code' => $code], 'id=?', [$id]);

        // Upsert contacto del solicitante (vinculado a empresa si la hay)
        Helpers::upsertContact($tenant->id, $companyId ?: null, $name, $email, (string)$this->input('phone','') ?: null);

        // Disparar evento → automatizaciones, integraciones, webhooks
        $row = $this->db->one('SELECT * FROM tickets WHERE id = ?', [$id]);
        Events::emit(Events::TICKET_CREATED, $tenant->id, 'ticket', $id, $row ?: [], null);

        // Notificar al solicitante + buzón del workspace
        try {
            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $publicUrl = $appUrl . '/portal/' . $tenant->slug . '/ticket/' . $token;
            $mailer = new Mailer();

            $innerReq = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
                . '<p>Recibimos tu solicitud y la registramos con el código <strong>' . htmlspecialchars($code) . '</strong>.</p>'
                . '<p><strong>Asunto:</strong> ' . htmlspecialchars($subject) . '</p>'
                . '<p>Te enviaremos actualizaciones a este correo. También puedes seguir el progreso desde el enlace.</p>';
            $mailer->send(
                ['email' => $email, 'name' => $name],
                '[' . $code . '] Recibimos tu ticket · ' . $tenant->name,
                Mailer::template('Tu ticket fue creado', $innerReq, 'Ver mi ticket', $publicUrl)
            );

            // Buzón interno del workspace
            $support = trim((string)($tenant->support_email ?? ''));
            if ($support && $support !== $email) {
                $internalUrl = $appUrl . '/t/' . $tenant->slug . '/tickets/' . $id;
                $innerSup = '<p>Nuevo ticket desde el portal público.</p>'
                    . '<p><strong>De:</strong> ' . htmlspecialchars($name) . ' &lt;' . htmlspecialchars($email) . '&gt;</p>'
                    . '<p><strong>Asunto:</strong> ' . htmlspecialchars($subject) . '</p>'
                    . '<p><strong>Prioridad:</strong> ' . htmlspecialchars((string)$this->input('priority','medium')) . '</p>'
                    . '<hr><p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</p>';
                $mailer->send(
                    $support,
                    '[' . $code . '] Nuevo ticket · ' . $subject,
                    Mailer::template('Nuevo ticket recibido', $innerSup, 'Abrir ticket', $internalUrl),
                    null,
                    ['reply_to' => $email]
                );
            }
        } catch (\Throwable $e) { /* no bloquear creación */ }

        $this->session->flash('success','Ticket creado. Guarda el enlace para seguir su avance.');
        $this->redirect('/portal/' . $tenant->slug . '/ticket/' . $token);
    }

    public function show(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $ticket = $this->db->one(
            "SELECT t.*, c.name category_name, c.color category_color, u.name assigned_name
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id = t.category_id
             LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.tenant_id = ? AND t.public_token = ?",
            [$tenant->id, $params['token']]
        );
        if (!$ticket) { http_response_code(404); echo $this->view->render('errors/404', ['message'=>'Ticket no encontrado'], 'public'); exit; }

        $comments = $this->db->all(
            "SELECT cm.*, u.name user_name FROM ticket_comments cm LEFT JOIN users u ON u.id = cm.user_id
             WHERE cm.ticket_id=? AND cm.is_internal=0 ORDER BY cm.created_at ASC",
            [$ticket['id']]
        );

        $this->render('portal/show', [
            'title' => $ticket['code'] . ' · ' . $tenant->name,
            'tenant' => $tenant,
            'ticket' => $ticket,
            'comments' => $comments,
            'showPoweredFooter' => true,
        ], 'public');
    }

    public function kb(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $q = trim((string)($_GET['q'] ?? ''));
        $where = ['a.tenant_id=?', "a.status='published'", "a.visibility='public'"];
        $args = [$tenant->id];
        if ($q) { $where[] = '(a.title LIKE ? OR a.body LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
        $articles = $this->db->all(
            "SELECT a.*, c.name cat_name, c.color cat_color, c.icon cat_icon FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id = a.category_id
             WHERE " . implode(' AND ', $where) . " ORDER BY a.views DESC",
            $args
        );
        $cats = $this->db->all('SELECT * FROM kb_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $this->render('portal/kb', ['title'=>'Centro de ayuda','tenant'=>$tenant,'articles'=>$articles,'cats'=>$cats,'q'=>$q,'showPoweredFooter'=>true], 'public');
    }

    public function article(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $art = $this->db->one(
            "SELECT a.*, c.name cat_name, c.color cat_color FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id = a.category_id
             WHERE a.tenant_id=? AND a.slug=? AND a.status='published' AND a.visibility='public'",
            [$tenant->id, $params['articleSlug']]
        );
        if (!$art) { http_response_code(404); echo $this->view->render('errors/404', ['message'=>'Artículo no encontrado'], 'public'); exit; }
        $this->db->run('UPDATE kb_articles SET views=views+1 WHERE id=?', [$art['id']]);
        $this->render('portal/article', ['title'=>$art['title'],'tenant'=>$tenant,'art'=>$art,'showPoweredFooter'=>true], 'public');
    }

    public function reply(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $this->validateCsrf();
        $ticket = $this->db->one('SELECT * FROM tickets WHERE tenant_id=? AND public_token=?', [$tenant->id, $params['token']]);
        if (!$ticket) $this->redirect('/portal/' . $tenant->slug);
        $body = trim((string)$this->input('body'));
        if ($body === '') $this->redirect('/portal/' . $tenant->slug . '/ticket/' . $params['token']);
        $this->db->insert('ticket_comments', [
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket['id'],
            'author_name' => $ticket['requester_name'],
            'author_email' => $ticket['requester_email'],
            'body' => $body,
            'is_internal' => 0,
        ]);
        $this->db->update('tickets', ['updated_at' => date('Y-m-d H:i:s'), 'status' => $ticket['status'] === 'closed' ? 'open' : $ticket['status']], 'id=?', ['id' => $ticket['id']]);

        try {
            $support = trim((string)($tenant->support_email ?? ''));
            if ($support) {
                $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
                $internalUrl = $appUrl . '/t/' . $tenant->slug . '/tickets/' . $ticket['id'];
                $inner = '<p><strong>' . htmlspecialchars($ticket['requester_name']) . '</strong> respondió en el ticket <strong>' . htmlspecialchars($ticket['code']) . '</strong>.</p>'
                    . '<hr><p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</p>';
                (new Mailer())->send(
                    $support,
                    '[' . $ticket['code'] . '] Nueva respuesta del cliente',
                    Mailer::template('Nueva respuesta del solicitante', $inner, 'Abrir ticket', $internalUrl),
                    null,
                    ['reply_to' => $ticket['requester_email']]
                );
            }
        } catch (\Throwable $e) { /* ignore */ }

        $this->session->flash('success','Mensaje enviado.');
        $this->redirect('/portal/' . $tenant->slug . '/ticket/' . $params['token']);
    }
}
