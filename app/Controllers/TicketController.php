<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Mailer;

class TicketController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.view');

        $q = trim((string)($_GET['q'] ?? ''));
        $status = (string)($_GET['status'] ?? '');
        $priority = (string)($_GET['priority'] ?? '');
        $category = (int)($_GET['category'] ?? 0);
        $department = (int)($_GET['department'] ?? 0);
        $assigned = (string)($_GET['assigned'] ?? '');

        $hasDepts = $this->hasDepartments();

        $where = ['t.tenant_id = ?']; $args = [$tenant->id];
        if ($q !== '')          { $where[] = '(t.subject LIKE ? OR t.code LIKE ? OR t.description LIKE ? OR t.requester_email LIKE ?)'; $qq = "%$q%"; array_push($args, $qq, $qq, $qq, $qq); }
        if ($status !== '')     { $where[] = 't.status = ?';  $args[] = $status; }
        if ($priority !== '')   { $where[] = 't.priority = ?'; $args[] = $priority; }
        if ($category)          { $where[] = 't.category_id = ?'; $args[] = $category; }
        if ($department && $hasDepts) { $where[] = 't.department_id = ?'; $args[] = $department; }
        if ($assigned === 'me') { $where[] = 't.assigned_to = ?'; $args[] = $this->auth->userId(); }
        elseif ($assigned === 'unassigned') { $where[] = 't.assigned_to IS NULL'; }
        elseif ($assigned !== '' && ctype_digit($assigned)) { $where[] = 't.assigned_to = ?'; $args[] = (int)$assigned; }

        $deptSelect = $hasDepts ? ', d.name AS department_name, d.color AS department_color, d.icon AS department_icon' : '';
        $deptJoin   = $hasDepts ? ' LEFT JOIN departments d ON d.id = t.department_id' : '';

        $tickets = $this->db->all(
            "SELECT t.*, c.name AS category_name, c.color AS category_color, co.name AS company_name,
                    u.name AS assigned_name, u.email AS assigned_email" . $deptSelect . "
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id = t.category_id
             LEFT JOIN companies co ON co.id = t.company_id
             LEFT JOIN users u ON u.id = t.assigned_to" . $deptJoin . "
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                FIELD(t.priority,'urgent','high','medium','low'),
                FIELD(t.status,'open','in_progress','on_hold','resolved','closed'),
                t.updated_at DESC
             LIMIT 200",
            $args
        );

        $categories = $this->db->all('SELECT id, name, color FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $technicians = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_technician=1 ORDER BY name', [$tenant->id]);
        $departments = $hasDepts ? $this->db->all('SELECT id, name, color, icon FROM departments WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]) : [];

        $counts = [
            'all'         => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=?", [$tenant->id]),
            'open'        => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='open'", [$tenant->id]),
            'in_progress' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='in_progress'", [$tenant->id]),
            'on_hold'     => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='on_hold'", [$tenant->id]),
            'resolved'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='resolved'", [$tenant->id]),
            'closed'      => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='closed'", [$tenant->id]),
            'mine'        => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND assigned_to=?", [$tenant->id, $this->auth->userId()]),
        ];

        $this->render('tickets/index', [
            'title' => 'Tickets',
            'tickets' => $tickets,
            'categories' => $categories,
            'technicians' => $technicians,
            'departments' => $departments,
            'counts' => $counts,
            'filters' => compact('q','status','priority','category','department','assigned'),
        ]);
    }

    public function board(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.view');
        $groups = [];
        foreach (['open','in_progress','on_hold','resolved'] as $st) {
            $groups[$st] = $this->db->all(
                "SELECT t.*, c.name AS category_name, c.color AS category_color, u.name AS assigned_name, u.email AS assigned_email
                 FROM tickets t
                 LEFT JOIN ticket_categories c ON c.id = t.category_id
                 LEFT JOIN users u ON u.id = t.assigned_to
                 WHERE t.tenant_id=? AND t.status=?
                 ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.updated_at DESC
                 LIMIT 50",
                [$tenant->id, $st]
            );
        }
        $this->render('tickets/board', ['title'=>'Tablero','groups'=>$groups]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.create');
        $categories = $this->db->all('SELECT * FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $technicians = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_technician=1 ORDER BY name', [$tenant->id]);
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $departments = $this->hasDepartments() ? $this->db->all('SELECT id, name, color, icon FROM departments WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]) : [];
        $this->render('tickets/create', ['title'=>'Nuevo ticket','categories'=>$categories,'technicians'=>$technicians,'companies'=>$companies,'departments'=>$departments]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.create');
        $this->validateCsrf();

        $subject = trim((string)$this->input('subject'));
        if ($subject === '') {
            $this->session->flash('error','El asunto es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/tickets/create');
        }

        $monthly = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tenant->id]);
        $this->enforceLimit('tickets_per_month', $monthly, 'tickets este mes', '/t/' . $tenant->slug . '/tickets');

        $channel = (string)$this->input('channel','internal');
        $this->enforceChannel($channel, '/t/' . $tenant->slug . '/tickets/create');

        $deptId = ((int)$this->input('department_id',0)) ?: null;
        $assignedTo = ((int)$this->input('assigned_to',0)) ?: null;

        // Auto-asignar al líder del departamento si se eligió dept y no se asignó técnico
        if ($deptId && !$assignedTo && $this->hasDepartments()) {
            $lead = $this->db->val(
                'SELECT du.user_id FROM department_users du
                 JOIN users u ON u.id = du.user_id
                 WHERE du.department_id=? AND u.tenant_id=? AND u.is_active=1
                 ORDER BY du.is_lead DESC, RAND() LIMIT 1',
                [$deptId, $tenant->id]
            );
            if ($lead) $assignedTo = (int)$lead;
        }

        $data = [
            'tenant_id' => $tenant->id,
            'code'      => 'TMP-' . bin2hex(random_bytes(4)),
            'subject'   => $subject,
            'description' => (string)$this->input('description',''),
            'category_id' => ((int)$this->input('category_id',0)) ?: null,
            'company_id' => ((int)$this->input('company_id',0)) ?: null,
            'priority'  => (string)$this->input('priority','medium'),
            'status'    => 'open',
            'channel'   => $channel,
            'requester_name'  => (string)$this->input('requester_name', $this->auth->user()['name']),
            'requester_email' => (string)$this->input('requester_email', $this->auth->user()['email']),
            'requester_phone' => (string)$this->input('requester_phone',''),
            'assigned_to' => $assignedTo,
            'created_by'  => $this->auth->userId(),
            'tags' => (string)$this->input('tags',''),
            'public_token' => bin2hex(random_bytes(16)),
        ];
        if ($this->hasDepartments()) $data['department_id'] = $deptId;
        $id = $this->db->insert('tickets', $data);
        $this->db->update('tickets', ['code' => Helpers::ticketCode($tenant->id, $id)], 'id=?', [$id]);

        $this->logAudit('ticket.created', 'ticket', $id, ['subject' => $subject]);
        $this->session->flash('success', 'Ticket creado con éxito.');
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $id);
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.view');
        $id = (int)$params['id'];

        $hasDepts = $this->hasDepartments();
        $deptSelect = $hasDepts ? ', d.name AS department_name, d.color AS department_color, d.icon AS department_icon' : '';
        $deptJoin   = $hasDepts ? ' LEFT JOIN departments d ON d.id = t.department_id' : '';

        $ticket = $this->db->one(
            "SELECT t.*, c.name AS category_name, c.color AS category_color,
                    co.name AS company_name, co.tier AS company_tier,
                    u.name AS assigned_name, u.email AS assigned_email, u.title AS assigned_title,
                    cr.name AS creator_name" . $deptSelect . "
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id = t.category_id
             LEFT JOIN companies co ON co.id = t.company_id
             LEFT JOIN users u ON u.id = t.assigned_to
             LEFT JOIN users cr ON cr.id = t.created_by" . $deptJoin . "
             WHERE t.id = ? AND t.tenant_id = ?",
            [$id, $tenant->id]
        );
        if (!$ticket) {
            $this->session->flash('error', 'Ticket no encontrado.');
            $this->redirect('/t/' . $tenant->slug . '/tickets');
        }

        $comments = $this->db->all(
            "SELECT cm.*, u.name AS user_name, u.email AS user_email, u.title AS user_title
             FROM ticket_comments cm
             LEFT JOIN users u ON u.id = cm.user_id
             WHERE cm.ticket_id = ? ORDER BY cm.created_at ASC",
            [$id]
        );
        $escalations = $this->db->all(
            "SELECT e.*, uf.name AS from_name, ut.name AS to_name
             FROM ticket_escalations e
             LEFT JOIN users uf ON uf.id = e.from_user_id
             LEFT JOIN users ut ON ut.id = e.to_user_id
             WHERE e.ticket_id = ? ORDER BY e.created_at DESC",
            [$id]
        );
        $categories = $this->db->all('SELECT * FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $technicians = $this->db->all('SELECT id, name, email FROM users WHERE tenant_id=? AND is_technician=1 ORDER BY name', [$tenant->id]);
        $departments = $hasDepts ? $this->db->all('SELECT id, name, color, icon FROM departments WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]) : [];
        $relatedArticles = $this->db->all(
            "SELECT id, title, slug, excerpt FROM kb_articles WHERE tenant_id=? AND status='published' ORDER BY views DESC LIMIT 3",
            [$tenant->id]
        );

        $macros = [];
        try {
            $macroTable = $this->db->one("SHOW TABLES LIKE 'macros'");
            if ($macroTable) {
                $macros = $this->db->all(
                    "SELECT id, name, body, category, is_internal FROM macros WHERE tenant_id=? ORDER BY use_count DESC, id DESC LIMIT 8",
                    [$tenant->id]
                );
            }
        } catch (\Throwable $e) { /* ignore */ }

        $this->render('tickets/show', [
            'title' => $ticket['code'] . ' — ' . $ticket['subject'],
            'ticket' => $ticket,
            'comments' => $comments,
            'escalations' => $escalations,
            'categories' => $categories,
            'technicians' => $technicians,
            'departments' => $departments,
            'relatedArticles' => $relatedArticles,
            'macros' => $macros,
        ]);
    }

    public function comment(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.comment');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $body = trim((string)$this->input('body'));
        if ($body === '') { $this->back(); return; }

        $u = $this->auth->user();
        $isInternal = (int)($this->input('is_internal',0) ? 1 : 0);
        $this->db->insert('ticket_comments', [
            'tenant_id' => $tenant->id,
            'ticket_id' => $id,
            'user_id'   => $u['id'],
            'author_name'  => $u['name'],
            'author_email' => $u['email'],
            'body'      => $body,
            'is_internal' => $isInternal,
        ]);
        // Primer response_at si no existe
        $this->db->run("UPDATE tickets SET updated_at = NOW(), first_response_at = COALESCE(first_response_at, NOW()) WHERE id = ?", [$id]);

        // Notificar al solicitante si la respuesta es pública
        if (!$isInternal) {
            try {
                $tk = $this->db->one('SELECT code, subject, requester_name, requester_email, public_token FROM tickets WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
                if ($tk && filter_var($tk['requester_email'], FILTER_VALIDATE_EMAIL)) {
                    $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
                    $publicUrl = $appUrl . '/portal/' . $tenant->slug . '/ticket/' . $tk['public_token'];
                    $inner = '<p>Hola <strong>' . htmlspecialchars($tk['requester_name']) . '</strong>,</p>'
                        . '<p><strong>' . htmlspecialchars($u['name']) . '</strong> ha respondido a tu ticket <strong>' . htmlspecialchars($tk['code']) . '</strong>:</p>'
                        . '<blockquote style="border-left:3px solid #6366f1;padding:8px 14px;margin:14px 0;background:#f4f5f8;color:#3a3946;white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</blockquote>';
                    (new Mailer())->send(
                        ['email' => $tk['requester_email'], 'name' => $tk['requester_name']],
                        '[' . $tk['code'] . '] Nueva respuesta · ' . $tk['subject'],
                        Mailer::template('Nueva respuesta en tu ticket', $inner, 'Ver respuesta', $publicUrl),
                        null,
                        ['reply_to' => $u['email']]
                    );
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        $this->session->flash('success','Comentario agregado.');
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $id);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];

        $data = [];
        $editable = ['status','priority','category_id','subject','description','tags'];
        if ($this->hasDepartments()) $editable[] = 'department_id';
        foreach ($editable as $f) {
            $v = $this->input($f, null);
            if ($v !== null) $data[$f] = in_array($f, ['category_id','department_id'], true) ? (((int)$v) ?: null) : $v;
        }
        if (($data['status'] ?? null) === 'resolved') $data['resolved_at'] = date('Y-m-d H:i:s');
        if (($data['status'] ?? null) === 'closed')   $data['closed_at']   = date('Y-m-d H:i:s');

        if ($data) {
            $this->db->update('tickets', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
            $this->logAudit('ticket.updated','ticket', $id, $data);
        }
        $this->session->flash('success','Ticket actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $id);
    }

    public function move(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $status = (string)$this->input('status','');
        $allowed = ['open','in_progress','on_hold','resolved','closed'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['ok' => false, 'error' => 'Estado inválido'], 400);
        }
        $exists = $this->db->one('SELECT id, status FROM tickets WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$exists) $this->json(['ok' => false, 'error' => 'No encontrado'], 404);

        $data = ['status' => $status];
        if ($status === 'resolved') $data['resolved_at'] = date('Y-m-d H:i:s');
        if ($status === 'closed')   $data['closed_at']   = date('Y-m-d H:i:s');
        $this->db->update('tickets', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('ticket.moved','ticket', $id, ['from' => $exists['status'], 'to' => $status]);
        $this->json(['ok' => true, 'id' => $id, 'status' => $status]);
    }

    public function assign(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.assign');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $assignTo = ((int)$this->input('assigned_to',0)) ?: null;
        $this->db->update('tickets', ['assigned_to' => $assignTo], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('ticket.assigned','ticket', $id, ['assigned_to'=>$assignTo]);
        $this->session->flash('success','Ticket asignado.');
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $id);
    }

    public function escalate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.escalate');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $toUser = ((int)$this->input('to_user_id',0)) ?: null;
        $reason = trim((string)$this->input('reason',''));

        $ticket = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$ticket) { $this->back(); return; }

        $newLevel = (int)$ticket['escalation_level'] + 1;
        $this->db->insert('ticket_escalations', [
            'tenant_id' => $tenant->id,
            'ticket_id' => $id,
            'from_user_id' => $ticket['assigned_to'],
            'to_user_id' => $toUser,
            'from_level' => (int)$ticket['escalation_level'],
            'to_level'   => $newLevel,
            'reason'     => $reason,
        ]);
        $this->db->update('tickets', [
            'escalation_level' => $newLevel,
            'assigned_to' => $toUser ?: $ticket['assigned_to'],
            'priority' => $ticket['priority'] === 'urgent' ? 'urgent' : 'high',
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->db->insert('ticket_comments', [
            'tenant_id' => $tenant->id, 'ticket_id' => $id, 'user_id' => $this->auth->userId(),
            'author_name' => $this->auth->user()['name'], 'author_email' => $this->auth->user()['email'],
            'body' => "Ticket escalado a nivel N$newLevel" . ($reason ? "\n\nMotivo: $reason" : ''),
            'is_internal' => 1,
        ]);
        $this->logAudit('ticket.escalated','ticket', $id, ['to_level'=>$newLevel,'to_user'=>$toUser]);
        $this->session->flash('success','Ticket escalado a nivel N' . ($newLevel + 1));
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $id);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('tickets', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('ticket.deleted','ticket',$id);
        $this->session->flash('success','Ticket eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/tickets');
    }

    protected function hasDepartments(): bool
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        try {
            $tenant = $this->app->tenant;
            if (!$tenant) return $cached = false;
            $hasFeature = \App\Core\Plan::has($tenant, 'departments');
            $hasColumn = (bool)$this->db->one("SHOW COLUMNS FROM tickets LIKE 'department_id'");
            return $cached = ($hasFeature && $hasColumn);
        } catch (\Throwable $_e) { return $cached = false; }
    }

    protected function logAudit(string $action, string $entity, int $entityId, array $meta = []): void
    {
        $tenant = $this->app->tenant;
        if (!$tenant) return;
        $this->db->insert('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $this->auth->userId(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => json_encode($meta),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
