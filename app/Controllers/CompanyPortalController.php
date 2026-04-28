<?php
namespace App\Controllers;

use App\Core\Attachments;
use App\Core\Controller;
use App\Core\Events;
use App\Core\Helpers;
use App\Core\Mailer;
use App\Core\Pdf;
use App\Core\Tenant;

/**
 * Portal de Empresas (Company Portal).
 *
 * Acceso autenticado: portal_users vinculados a una company de un tenant.
 * Todas las queries filtran por tenant_id + company_id (doble candado).
 *
 * Vistas para todos los miembros:
 *   - dashboard, tickets list, ticket detail (responder), nuevo ticket
 *
 * Vistas solo manager (is_company_manager = 1):
 *   - reports, team, exports CSV/PDF, crear ticket en nombre de otro contacto
 */
class CompanyPortalController extends Controller
{
    /* ─────────────────────── Acceso ─────────────────────── */

    protected function resolveTenant(string $slug): Tenant
    {
        $t = Tenant::resolve($slug);
        if (!$t) { http_response_code(404); echo $this->view->render('errors/404', ['message' => 'Portal no encontrado'], 'public'); exit; }
        $this->app->tenant = $t;
        return $t;
    }

    /** Devuelve el portal_user autenticado o redirige a login. */
    protected function requirePortalUser(Tenant $tenant): array
    {
        $userId = (int)$this->session->get('portal_user_id_' . $tenant->id, 0);
        if (!$userId) $this->redirect('/portal/' . $tenant->slug . '/login');
        $user = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=? AND is_active=1', [$userId, $tenant->id]);
        if (!$user) {
            $this->session->forget('portal_user_id_' . $tenant->id);
            $this->redirect('/portal/' . $tenant->slug . '/login');
        }
        if (empty($user['company_id'])) {
            $this->session->flash('error', 'Tu usuario no está vinculado a una empresa. Contactá al administrador del portal.');
            $this->redirect('/portal/' . $tenant->slug . '/account');
        }
        return $user;
    }

    /** Carga la empresa del usuario o aborta. */
    protected function requireCompany(Tenant $tenant, array $user): array
    {
        $company = $this->db->one('SELECT * FROM companies WHERE id=? AND tenant_id=?', [(int)$user['company_id'], $tenant->id]);
        if (!$company) {
            $this->session->flash('error', 'Empresa no encontrada.');
            $this->redirect('/portal/' . $tenant->slug . '/account');
        }
        return $company;
    }

    protected function requireManager(array $user): void
    {
        if ((int)($user['is_company_manager'] ?? 0) !== 1) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['message' => 'Esta sección está reservada para managers de la empresa.'], 'public');
            exit;
        }
    }

    /* ─────────────────────── Dashboard ─────────────────────── */

    public function dashboard(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $company = $this->requireCompany($tenant, $user);

        $base = ['tenant_id' => $tenant->id, 'company_id' => (int)$company['id']];

        // KPIs
        $kpis = [
            'total'      => (int)$this->db->val('SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND company_id=?', array_values($base)),
            'open'       => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND company_id=? AND status IN ('open','in_progress','on_hold')", array_values($base)),
            'resolved'   => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND company_id=? AND status IN ('resolved','closed')", array_values($base)),
            'urgent'     => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND company_id=? AND priority='urgent' AND status IN ('open','in_progress','on_hold')", array_values($base)),
            'breached'   => (int)$this->db->val('SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND company_id=? AND sla_breached=1', array_values($base)),
            'csat_avg'   => $this->db->val('SELECT AVG(satisfaction_rating) FROM tickets WHERE tenant_id=? AND company_id=? AND satisfaction_rating IS NOT NULL', array_values($base)),
            'first_resp' => $this->db->val("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) FROM tickets WHERE tenant_id=? AND company_id=? AND first_response_at IS NOT NULL", array_values($base)),
            'resolve_t'  => $this->db->val("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) FROM tickets WHERE tenant_id=? AND company_id=? AND resolved_at IS NOT NULL", array_values($base)),
        ];

        // Series 6 meses
        $monthly = $this->db->all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total,
                    SUM(status IN ('resolved','closed')) AS resolved
             FROM tickets WHERE tenant_id=? AND company_id=?
                          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY ym ORDER BY ym ASC",
            array_values($base)
        );

        $byStatus = $this->db->all(
            'SELECT status, COUNT(*) AS n FROM tickets WHERE tenant_id=? AND company_id=? GROUP BY status',
            array_values($base)
        );
        $byPriority = $this->db->all(
            'SELECT priority, COUNT(*) AS n FROM tickets WHERE tenant_id=? AND company_id=? GROUP BY priority',
            array_values($base)
        );
        $byCategory = $this->db->all(
            "SELECT COALESCE(c.name,'Sin categoría') AS name, COALESCE(c.color,'#94a3b8') AS color, COUNT(*) AS n
             FROM tickets t LEFT JOIN ticket_categories c ON c.id = t.category_id
             WHERE t.tenant_id=? AND t.company_id=? GROUP BY c.id ORDER BY n DESC LIMIT 8",
            array_values($base)
        );

        $recent = $this->db->all(
            "SELECT t.id, t.code, t.subject, t.status, t.priority, t.created_at, t.requester_name
             FROM tickets t WHERE t.tenant_id=? AND t.company_id=?
             ORDER BY t.created_at DESC LIMIT 8",
            array_values($base)
        );

        $this->render('company_portal/dashboard', [
            'title'        => 'Dashboard · ' . $company['name'],
            'tenantPublic' => $tenant,
            'portalUser'   => $user,
            'company'      => $company,
            'kpis'         => $kpis,
            'monthly'      => $monthly,
            'byStatus'     => $byStatus,
            'byPriority'   => $byPriority,
            'byCategory'   => $byCategory,
            'recent'       => $recent,
            'nav'          => 'dashboard',
        ], 'public');
    }

    /* ─────────────────────── Tickets list ─────────────────────── */

    public function tickets(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $company = $this->requireCompany($tenant, $user);

        $f = $this->ticketFilters($tenant->id, (int)$company['id']);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $total = (int)$this->db->val("SELECT COUNT(*) FROM tickets t WHERE {$f['where']}", $f['args']);
        $rows = $this->db->all(
            "SELECT t.id, t.code, t.subject, t.status, t.priority, t.requester_name, t.requester_email,
                    t.created_at, t.updated_at, t.resolved_at, t.public_token,
                    c.name AS category_name, c.color AS category_color, u.name AS assigned_name
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id = t.category_id
             LEFT JOIN users u ON u.id = t.assigned_to
             WHERE {$f['where']}
             ORDER BY t.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $f['args']
        );

        $cats = $this->db->all('SELECT id, name FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $this->render('company_portal/tickets', [
            'title'        => 'Tickets · ' . $company['name'],
            'tenantPublic' => $tenant,
            'portalUser'   => $user,
            'company'      => $company,
            'tickets'      => $rows,
            'cats'         => $cats,
            'filters'      => $f['filters'],
            'page'         => $page,
            'perPage'      => $perPage,
            'total'        => $total,
            'nav'          => 'tickets',
        ], 'public');
    }

    /** Genera WHERE + args + filtros normalizados para listados/exports. */
    protected function ticketFilters(int $tenantId, int $companyId): array
    {
        $where = ['t.tenant_id=?', 't.company_id=?'];
        $args = [$tenantId, $companyId];

        $filters = [
            'q'        => trim((string)($_GET['q'] ?? '')),
            'status'   => (string)($_GET['status'] ?? ''),
            'priority' => (string)($_GET['priority'] ?? ''),
            'category' => (int)($_GET['category'] ?? 0),
            'from'     => (string)($_GET['from'] ?? ''),
            'to'       => (string)($_GET['to'] ?? ''),
        ];

        if ($filters['q'] !== '') {
            $where[] = '(t.subject LIKE ? OR t.code LIKE ? OR t.requester_email LIKE ? OR t.requester_name LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($args, $like, $like, $like, $like);
        }
        if (in_array($filters['status'], ['open','in_progress','on_hold','resolved','closed'], true)) {
            $where[] = 't.status=?'; $args[] = $filters['status'];
        }
        if (in_array($filters['priority'], ['low','medium','high','urgent'], true)) {
            $where[] = 't.priority=?'; $args[] = $filters['priority'];
        }
        if ($filters['category'] > 0) {
            $where[] = 't.category_id=?'; $args[] = $filters['category'];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from'])) {
            $where[] = 't.created_at >= ?'; $args[] = $filters['from'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to'])) {
            $where[] = 't.created_at <= ?'; $args[] = $filters['to'] . ' 23:59:59';
        }
        return ['where' => implode(' AND ', $where), 'args' => $args, 'filters' => $filters];
    }

    /* ─────────────────────── Ticket detail + reply ─────────────────────── */

    public function ticketShow(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $company = $this->requireCompany($tenant, $user);
        $id = (int)$params['id'];

        $ticket = $this->db->one(
            "SELECT t.*, c.name AS category_name, c.color AS category_color, u.name AS assigned_name
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id = t.category_id
             LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.id=? AND t.tenant_id=? AND t.company_id=?",
            [$id, $tenant->id, (int)$company['id']]
        );
        if (!$ticket) { http_response_code(404); echo $this->view->render('errors/404', ['message' => 'Ticket no encontrado'], 'public'); exit; }

        $comments = $this->db->all(
            "SELECT cm.*, u.name AS user_name FROM ticket_comments cm
             LEFT JOIN users u ON u.id = cm.user_id
             WHERE cm.ticket_id=? AND cm.is_internal=0 ORDER BY cm.created_at ASC",
            [$id]
        );

        $attachments = $this->db->all(
            "SELECT a.id, a.comment_id, a.filename, a.original_name, a.mime, a.size, a.created_at
             FROM ticket_attachments a
             LEFT JOIN ticket_comments c ON c.id = a.comment_id
             WHERE a.ticket_id=? AND a.tenant_id=? AND (a.comment_id IS NULL OR c.is_internal=0)
             ORDER BY a.created_at ASC",
            [$id, $tenant->id]
        );
        $attachmentsByComment = ['main' => []];
        foreach ($attachments as $a) {
            if (!empty($a['comment_id'])) $attachmentsByComment[(int)$a['comment_id']][] = $a;
            else $attachmentsByComment['main'][] = $a;
        }

        $this->render('company_portal/ticket_show', [
            'title'                 => $ticket['code'] . ' · ' . $company['name'],
            'tenantPublic'          => $tenant,
            'portalUser'            => $user,
            'company'               => $company,
            'ticket'                => $ticket,
            'comments'              => $comments,
            'attachmentsByComment'  => $attachmentsByComment,
            'nav'                   => 'tickets',
        ], 'public');
    }

    public function ticketReply(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $company = $this->requireCompany($tenant, $user);
        $this->validateCsrf();
        $id = (int)$params['id'];
        $ticket = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=? AND company_id=?', [$id, $tenant->id, (int)$company['id']]);
        if (!$ticket) { $this->redirect('/portal/' . $tenant->slug . '/company/tickets'); return; }

        $body = trim((string)$this->input('body'));
        if ($body === '' && empty($_FILES['attachments']['name'])) {
            $this->redirect('/portal/' . $tenant->slug . '/company/tickets/' . $id);
            return;
        }

        $commentId = $this->db->insert('ticket_comments', [
            'tenant_id'    => $tenant->id,
            'ticket_id'    => $id,
            'author_name'  => $user['name'],
            'author_email' => $user['email'],
            'body'         => $body !== '' ? $body : '(adjuntos)',
            'is_internal'  => 0,
        ]);
        Attachments::persistFromInput('attachments', (int)$tenant->id, $id, (int)$commentId);

        $this->db->update('tickets', [
            'updated_at' => date('Y-m-d H:i:s'),
            'status'     => $ticket['status'] === 'closed' ? 'open' : $ticket['status'],
        ], 'id=?', [$id]);

        try {
            $support = trim((string)($tenant->data['support_email'] ?? ''));
            if ($support) {
                $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
                $internalUrl = $appUrl . '/t/' . $tenant->slug . '/tickets/' . $id;
                $inner = '<p><strong>' . htmlspecialchars($user['name']) . '</strong> respondió en el ticket <strong>' . htmlspecialchars($ticket['code']) . '</strong> desde el portal de <strong>' . htmlspecialchars($company['name']) . '</strong>.</p>'
                    . '<hr><p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</p>';
                (new Mailer())->send(
                    $support,
                    '[' . $ticket['code'] . '] Nueva respuesta del cliente',
                    Mailer::template('Nueva respuesta del solicitante', $inner, 'Abrir ticket', $internalUrl),
                    null,
                    ['reply_to' => $user['email']]
                );
            }
        } catch (\Throwable $e) { /* ignore */ }

        $this->session->flash('success', 'Mensaje enviado.');
        $this->redirect('/portal/' . $tenant->slug . '/company/tickets/' . $id);
    }

    /* ─────────────────────── New ticket (auth) ─────────────────────── */

    public function ticketCreate(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $company = $this->requireCompany($tenant, $user);
        $cats = $this->db->all('SELECT * FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $contacts = [];
        if (!empty($user['is_company_manager'])) {
            $contacts = $this->db->all(
                'SELECT id, name, email, phone, title FROM contacts WHERE tenant_id=? AND company_id=? AND email IS NOT NULL ORDER BY name LIMIT 200',
                [$tenant->id, (int)$company['id']]
            );
        }

        $this->render('company_portal/ticket_new', [
            'title'        => 'Nuevo ticket · ' . $company['name'],
            'tenantPublic' => $tenant,
            'portalUser'   => $user,
            'company'      => $company,
            'cats'         => $cats,
            'contacts'     => $contacts,
            'nav'          => 'tickets',
        ], 'public');
    }

    public function ticketStore(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $company = $this->requireCompany($tenant, $user);
        $this->validateCsrf();

        $subject = trim((string)$this->input('subject'));
        $body    = trim((string)$this->input('description'));
        if ($subject === '' || $body === '') {
            $this->session->flash('error', 'Completá el asunto y la descripción.');
            $this->redirect('/portal/' . $tenant->slug . '/company/tickets/new');
        }

        // Por defecto, el solicitante es el propio portal_user
        $reqName  = $user['name'];
        $reqEmail = $user['email'];
        $reqPhone = (string)($user['phone'] ?? '');

        // Manager puede crear en nombre de otro contacto de la empresa
        if (!empty($user['is_company_manager'])) {
            $onBehalf = (int)$this->input('on_behalf_of', 0);
            if ($onBehalf > 0) {
                $contact = $this->db->one('SELECT * FROM contacts WHERE id=? AND tenant_id=? AND company_id=?', [$onBehalf, $tenant->id, (int)$company['id']]);
                if ($contact && !empty($contact['email'])) {
                    $reqName  = $contact['name'];
                    $reqEmail = $contact['email'];
                    $reqPhone = (string)($contact['phone'] ?? '');
                }
            }
        }

        // Plan limit
        $monthly = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tenant->id]);
        $maxMonthly = \App\Core\Plan::limit($tenant, 'tickets_per_month');
        if (is_int($maxMonthly) && $monthly >= $maxMonthly) {
            $this->session->flash('error', sprintf('Este workspace alcanzó su límite mensual de tickets (%d).', $maxMonthly));
            $this->redirect('/portal/' . $tenant->slug . '/company/tickets');
        }

        $token = bin2hex(random_bytes(16));
        $id = $this->db->insert('tickets', [
            'tenant_id'        => $tenant->id,
            'code'             => 'TMP-' . bin2hex(random_bytes(4)),
            'subject'          => $subject,
            'description'      => $body,
            'category_id'      => ((int)$this->input('category_id', 0)) ?: null,
            'company_id'       => (int)$company['id'],
            'priority'         => (string)$this->input('priority', 'medium'),
            'status'           => 'open',
            'channel'          => 'portal',
            'requester_name'   => $reqName,
            'requester_email'  => $reqEmail,
            'requester_phone'  => $reqPhone ?: null,
            'portal_user_id'   => (int)$user['id'],
            'public_token'     => $token,
        ]);
        $code = Helpers::ticketCode($tenant->id, $id);
        $this->db->update('tickets', ['code' => $code], 'id=?', [$id]);

        $attachIds = Attachments::persistFromInput('attachments', $tenant->id, (int)$id);
        Helpers::upsertContact($tenant->id, (int)$company['id'], $reqName, $reqEmail, $reqPhone ?: null);

        $row = $this->db->one('SELECT * FROM tickets WHERE id = ?', [$id]);
        if ($row && !empty($attachIds)) $row['attachments_count'] = count($attachIds);
        Events::emit(Events::TICKET_CREATED, $tenant->id, 'ticket', $id, $row ?: [], null);

        // Notificar buzón interno
        try {
            $support = trim((string)($tenant->data['support_email'] ?? ''));
            if ($support) {
                $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
                $internalUrl = $appUrl . '/t/' . $tenant->slug . '/tickets/' . $id;
                $inner = '<p>Nuevo ticket creado desde el portal de <strong>' . htmlspecialchars($company['name']) . '</strong>.</p>'
                    . '<p><strong>De:</strong> ' . htmlspecialchars($reqName) . ' &lt;' . htmlspecialchars($reqEmail) . '&gt;</p>'
                    . '<p><strong>Asunto:</strong> ' . htmlspecialchars($subject) . '</p>'
                    . '<hr><p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</p>';
                (new Mailer())->send(
                    $support,
                    '[' . $code . '] Nuevo ticket · ' . $subject,
                    Mailer::template('Nuevo ticket recibido', $inner, 'Abrir ticket', $internalUrl),
                    null,
                    ['reply_to' => $reqEmail]
                );
            }
        } catch (\Throwable $e) { /* ignore */ }

        $this->session->flash('success', 'Ticket ' . $code . ' creado correctamente.');
        $this->redirect('/portal/' . $tenant->slug . '/company/tickets/' . $id);
    }

    /* ─────────────────────── Reports (manager) ─────────────────────── */

    public function reports(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $this->requireManager($user);
        $company = $this->requireCompany($tenant, $user);

        $data = $this->buildReport($tenant->id, (int)$company['id']);

        $this->render('company_portal/reports', array_merge([
            'title'        => 'Reportes · ' . $company['name'],
            'tenantPublic' => $tenant,
            'portalUser'   => $user,
            'company'      => $company,
            'nav'          => 'reports',
        ], $data), 'public');
    }

    /** Construye dataset de reportes para vista o export. */
    protected function buildReport(int $tenantId, int $companyId): array
    {
        $rangeDays = max(7, min(365, (int)($_GET['days'] ?? 30)));
        $sinceDt = date('Y-m-d 00:00:00', strtotime("-{$rangeDays} days"));
        $prevSinceDt = date('Y-m-d 00:00:00', strtotime("-" . ($rangeDays * 2) . " days"));
        $prevUntilDt = date('Y-m-d 00:00:00', strtotime("-{$rangeDays} days"));

        $base = [$tenantId, $companyId];

        // Resumen del rango
        $totals = $this->db->one(
            "SELECT COUNT(*) AS total,
                    SUM(status IN ('resolved','closed')) AS resolved,
                    SUM(status IN ('open','in_progress','on_hold')) AS open,
                    SUM(sla_breached=1) AS breached,
                    AVG(satisfaction_rating) AS csat,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) AS first_resp,
                    AVG(TIMESTAMPDIFF(HOUR,   created_at, resolved_at))      AS resolve_t
             FROM tickets WHERE tenant_id=? AND company_id=? AND created_at >= ?",
            [$tenantId, $companyId, $sinceDt]
        ) ?: [];

        $prev = $this->db->one(
            "SELECT COUNT(*) AS total,
                    SUM(status IN ('resolved','closed')) AS resolved,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) AS resolve_t
             FROM tickets WHERE tenant_id=? AND company_id=? AND created_at >= ? AND created_at < ?",
            [$tenantId, $companyId, $prevSinceDt, $prevUntilDt]
        ) ?: [];

        // Series por día
        $daily = $this->db->all(
            "SELECT DATE(created_at) AS d, COUNT(*) AS total,
                    SUM(status IN ('resolved','closed')) AS resolved
             FROM tickets WHERE tenant_id=? AND company_id=? AND created_at >= ?
             GROUP BY d ORDER BY d ASC",
            [$tenantId, $companyId, $sinceDt]
        );

        $byPriority = $this->db->all(
            "SELECT priority, COUNT(*) AS n FROM tickets
             WHERE tenant_id=? AND company_id=? AND created_at >= ? GROUP BY priority",
            [$tenantId, $companyId, $sinceDt]
        );
        $byStatus = $this->db->all(
            "SELECT status, COUNT(*) AS n FROM tickets
             WHERE tenant_id=? AND company_id=? AND created_at >= ? GROUP BY status",
            [$tenantId, $companyId, $sinceDt]
        );
        $byCategory = $this->db->all(
            "SELECT COALESCE(c.name,'Sin categoría') AS name, COALESCE(c.color,'#94a3b8') AS color, COUNT(*) AS n
             FROM tickets t LEFT JOIN ticket_categories c ON c.id = t.category_id
             WHERE t.tenant_id=? AND t.company_id=? AND t.created_at >= ?
             GROUP BY c.id ORDER BY n DESC LIMIT 12",
            [$tenantId, $companyId, $sinceDt]
        );
        $byChannel = $this->db->all(
            "SELECT channel, COUNT(*) AS n FROM tickets
             WHERE tenant_id=? AND company_id=? AND created_at >= ? GROUP BY channel",
            [$tenantId, $companyId, $sinceDt]
        );
        $byRequester = $this->db->all(
            "SELECT COALESCE(requester_name, requester_email) AS name, requester_email AS email, COUNT(*) AS n
             FROM tickets WHERE tenant_id=? AND company_id=? AND created_at >= ?
             GROUP BY requester_email ORDER BY n DESC LIMIT 10",
            [$tenantId, $companyId, $sinceDt]
        );
        $byAgent = $this->db->all(
            "SELECT u.name, COUNT(*) AS n FROM tickets t
             JOIN users u ON u.id = t.assigned_to
             WHERE t.tenant_id=? AND t.company_id=? AND t.created_at >= ?
             GROUP BY t.assigned_to ORDER BY n DESC LIMIT 10",
            [$tenantId, $companyId, $sinceDt]
        );

        return [
            'rangeDays'  => $rangeDays,
            'since'      => $sinceDt,
            'totals'     => $totals,
            'prev'       => $prev,
            'daily'      => $daily,
            'byPriority' => $byPriority,
            'byStatus'   => $byStatus,
            'byCategory' => $byCategory,
            'byChannel'  => $byChannel,
            'byRequester'=> $byRequester,
            'byAgent'    => $byAgent,
        ];
    }

    /* ─────────────────────── Team (manager) ─────────────────────── */

    public function team(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $this->requireManager($user);
        $company = $this->requireCompany($tenant, $user);

        $portalUsers = $this->db->all(
            "SELECT pu.*,
                    (SELECT COUNT(*) FROM tickets t WHERE t.tenant_id=pu.tenant_id AND t.company_id=pu.company_id AND (t.portal_user_id = pu.id OR t.requester_email = pu.email)) AS tickets_count,
                    (SELECT COUNT(*) FROM tickets t WHERE t.tenant_id=pu.tenant_id AND t.company_id=pu.company_id AND (t.portal_user_id = pu.id OR t.requester_email = pu.email) AND t.status IN ('open','in_progress','on_hold')) AS open_count
             FROM portal_users pu
             WHERE pu.tenant_id=? AND pu.company_id=?
             ORDER BY pu.is_company_manager DESC, pu.name ASC",
            [$tenant->id, (int)$company['id']]
        );

        $contacts = $this->db->all(
            'SELECT id, name, email, phone, title FROM contacts WHERE tenant_id=? AND company_id=? ORDER BY name',
            [$tenant->id, (int)$company['id']]
        );

        $this->render('company_portal/team', [
            'title'        => 'Equipo · ' . $company['name'],
            'tenantPublic' => $tenant,
            'portalUser'   => $user,
            'company'      => $company,
            'portalUsers'  => $portalUsers,
            'contacts'     => $contacts,
            'nav'          => 'team',
        ], 'public');
    }

    /* ─────────────────────── Exports (manager) ─────────────────────── */

    public function exportTicketsCsv(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $this->requireManager($user);
        $company = $this->requireCompany($tenant, $user);

        $f = $this->ticketFilters($tenant->id, (int)$company['id']);
        $rows = $this->db->all(
            "SELECT t.code, t.subject, t.status, t.priority, t.channel,
                    t.requester_name, t.requester_email,
                    c.name AS category_name, u.name AS assigned_name,
                    t.satisfaction_rating, t.first_response_at, t.resolved_at, t.created_at, t.updated_at
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id=t.category_id
             LEFT JOIN users u ON u.id=t.assigned_to
             WHERE {$f['where']}
             ORDER BY t.created_at DESC",
            $f['args']
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tickets-' . $this->slugify($company['name']) . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM para Excel
        fputcsv($out, ['Código','Asunto','Estado','Prioridad','Canal','Solicitante','Email','Categoría','Asignado','CSAT','Primera resp.','Resuelto','Creado','Actualizado']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['code'], $r['subject'], $r['status'], $r['priority'], $r['channel'],
                $r['requester_name'], $r['requester_email'],
                $r['category_name'] ?? '', $r['assigned_name'] ?? '',
                $r['satisfaction_rating'] ?? '',
                $r['first_response_at'] ?? '', $r['resolved_at'] ?? '',
                $r['created_at'], $r['updated_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function exportTicketsPdf(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $this->requireManager($user);
        $company = $this->requireCompany($tenant, $user);

        $f = $this->ticketFilters($tenant->id, (int)$company['id']);
        $rows = $this->db->all(
            "SELECT t.code, t.subject, t.status, t.priority,
                    t.requester_name, t.requester_email,
                    c.name AS category_name, u.name AS assigned_name,
                    t.created_at, t.resolved_at, t.satisfaction_rating
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id=t.category_id
             LEFT JOIN users u ON u.id=t.assigned_to
             WHERE {$f['where']}
             ORDER BY t.created_at DESC",
            $f['args']
        );

        $html = $this->view->render('company_portal/export_tickets_pdf', [
            'title'        => 'Tickets · ' . $company['name'],
            'tenantPublic' => $tenant,
            'company'      => $company,
            'tickets'      => $rows,
            'filters'      => $f['filters'],
            'generatedAt'  => date('Y-m-d H:i'),
            'generatedBy'  => $user['name'],
        ], null);

        $filename = 'tickets-' . $this->slugify($company['name']) . '-' . date('Y-m-d') . '.pdf';
        Pdf::stream($html, $filename, 'landscape', 'A4', true);
    }

    public function exportReportPdf(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $this->requireManager($user);
        $company = $this->requireCompany($tenant, $user);

        $data = $this->buildReport($tenant->id, (int)$company['id']);
        $html = $this->view->render('company_portal/export_report_pdf', array_merge([
            'title'        => 'Reporte · ' . $company['name'],
            'tenantPublic' => $tenant,
            'company'      => $company,
            'generatedAt'  => date('Y-m-d H:i'),
            'generatedBy'  => $user['name'],
        ], $data), null);

        $filename = 'reporte-' . $this->slugify($company['name']) . '-' . (int)$data['rangeDays'] . 'd-' . date('Y-m-d') . '.pdf';
        Pdf::stream($html, $filename, 'portrait', 'A4', true);
    }

    public function exportReportCsv(array $params): void
    {
        $tenant = $this->resolveTenant($params['slug']);
        $user = $this->requirePortalUser($tenant);
        $this->requireManager($user);
        $company = $this->requireCompany($tenant, $user);

        $data = $this->buildReport($tenant->id, (int)$company['id']);
        $rangeDays = (int)$data['rangeDays'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-' . $this->slugify($company['name']) . '-' . $rangeDays . 'd-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Reporte', $company['name']]);
        fputcsv($out, ['Período', "Últimos $rangeDays días"]);
        fputcsv($out, ['Generado', date('Y-m-d H:i')]);
        fputcsv($out, []);

        $totals = $data['totals'] ?: [];
        fputcsv($out, ['Resumen']);
        fputcsv($out, ['Tickets totales', (int)($totals['total'] ?? 0)]);
        fputcsv($out, ['Resueltos', (int)($totals['resolved'] ?? 0)]);
        fputcsv($out, ['Abiertos', (int)($totals['open'] ?? 0)]);
        fputcsv($out, ['SLA breach', (int)($totals['breached'] ?? 0)]);
        fputcsv($out, ['CSAT promedio', $totals['csat'] !== null ? round((float)$totals['csat'], 2) : '']);
        fputcsv($out, ['Primera respuesta promedio (min)', $totals['first_resp'] !== null ? round((float)$totals['first_resp']) : '']);
        fputcsv($out, ['Tiempo de resolución promedio (h)',  $totals['resolve_t']  !== null ? round((float)$totals['resolve_t'], 1) : '']);
        fputcsv($out, []);

        fputcsv($out, ['Por día']);
        fputcsv($out, ['Fecha','Total','Resueltos']);
        foreach ($data['daily'] as $r) fputcsv($out, [$r['d'], (int)$r['total'], (int)$r['resolved']]);
        fputcsv($out, []);

        fputcsv($out, ['Por estado']);
        foreach ($data['byStatus'] as $r) fputcsv($out, [$r['status'], (int)$r['n']]);
        fputcsv($out, []);

        fputcsv($out, ['Por prioridad']);
        foreach ($data['byPriority'] as $r) fputcsv($out, [$r['priority'], (int)$r['n']]);
        fputcsv($out, []);

        fputcsv($out, ['Por categoría']);
        foreach ($data['byCategory'] as $r) fputcsv($out, [$r['name'], (int)$r['n']]);
        fputcsv($out, []);

        fputcsv($out, ['Top solicitantes']);
        fputcsv($out, ['Solicitante','Email','Tickets']);
        foreach ($data['byRequester'] as $r) fputcsv($out, [$r['name'], $r['email'], (int)$r['n']]);
        fputcsv($out, []);

        fputcsv($out, ['Top agentes']);
        fputcsv($out, ['Agente','Tickets']);
        foreach ($data['byAgent'] as $r) fputcsv($out, [$r['name'], (int)$r['n']]);

        fclose($out);
        exit;
    }

    protected function slugify(string $s): string
    {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
        return trim($s, '-') ?: 'empresa';
    }
}
