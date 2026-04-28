<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;

class ItsmController extends Controller
{
    /* ─────────── Hub principal ─────────── */
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.view');

        $stats = [
            'catalog'         => (int)$this->db->val('SELECT COUNT(*) FROM service_catalog_items WHERE tenant_id=? AND is_active=1', [$tenant->id]),
            'changes_pending' => (int)$this->db->val("SELECT COUNT(*) FROM change_requests WHERE tenant_id=? AND status IN ('draft','pending_approval')", [$tenant->id]),
            'changes_active'  => (int)$this->db->val("SELECT COUNT(*) FROM change_requests WHERE tenant_id=? AND status IN ('approved','scheduled','in_progress')", [$tenant->id]),
            'problems_open'   => (int)$this->db->val("SELECT COUNT(*) FROM problems WHERE tenant_id=? AND status NOT IN ('resolved','closed')", [$tenant->id]),
        ];

        $catalog = $this->db->all(
            'SELECT s.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             FROM service_catalog_items s LEFT JOIN ticket_categories c ON c.id = s.category_id
             WHERE s.tenant_id = ? AND s.is_active = 1 ORDER BY s.sort_order, s.id',
            [$tenant->id]
        );

        $changes = $this->db->all(
            'SELECT cr.*, u.name AS requester_name FROM change_requests cr LEFT JOIN users u ON u.id = cr.requester_id WHERE cr.tenant_id = ? ORDER BY cr.created_at DESC LIMIT 30',
            [$tenant->id]
        );

        $problems = $this->db->all(
            'SELECT p.*, u.name AS assignee_name FROM problems p LEFT JOIN users u ON u.id = p.assignee_id WHERE p.tenant_id = ? ORDER BY p.created_at DESC LIMIT 30',
            [$tenant->id]
        );

        $categories = $this->db->all('SELECT id, name FROM ticket_categories WHERE tenant_id = ? ORDER BY name', [$tenant->id]);
        $departments = [];
        try { $departments = $this->db->all('SELECT id, name FROM departments WHERE tenant_id = ? ORDER BY name', [$tenant->id]); } catch (\Throwable $e) {}
        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tenant->id]);

        $this->render('itsm/index', [
            'title' => 'ITSM',
            'stats' => $stats,
            'catalog' => $catalog,
            'changes' => $changes,
            'problems' => $problems,
            'categories' => $categories,
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    /* ─────────── Service Catalog ─────────── */
    public function catalogStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $name = trim((string)$this->input('name',''));
        if ($name === '') { $this->session->flash('error','Nombre requerido.'); $this->redirect('/t/' . $tenant->slug . '/itsm'); }

        $this->db->insert('service_catalog_items', [
            'tenant_id'         => $tenant->id,
            'category_id'       => ((int)$this->input('category_id', 0)) ?: null,
            'department_id'     => ((int)$this->input('department_id', 0)) ?: null,
            'name'              => $name,
            'description'       => (string)$this->input('description','') ?: null,
            'icon'              => (string)$this->input('icon','package') ?: 'package',
            'color'             => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->input('color','#7c5cff')) ? (string)$this->input('color') : '#7c5cff',
            'sla_minutes'       => ((int)$this->input('sla_minutes', 0)) ?: null,
            'requires_approval' => (int)($this->input('requires_approval') ? 1 : 0),
            'approver_user_id'  => ((int)$this->input('approver_user_id', 0)) ?: null,
            'is_active'         => 1,
            'is_public'         => (int)($this->input('is_public') ? 1 : 0),
            'sort_order'        => (int)$this->input('sort_order', 0),
        ]);
        $this->session->flash('success','Item del catálogo creado.');
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    public function catalogUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $item = $this->db->one('SELECT id FROM service_catalog_items WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$item) $this->redirect('/t/' . $tenant->slug . '/itsm');

        $name = trim((string)$this->input('name',''));
        if ($name === '') { $this->session->flash('error','Nombre requerido.'); $this->redirect('/t/' . $tenant->slug . '/itsm'); }

        $this->db->update('service_catalog_items', [
            'category_id'       => ((int)$this->input('category_id', 0)) ?: null,
            'department_id'     => ((int)$this->input('department_id', 0)) ?: null,
            'name'              => $name,
            'description'       => (string)$this->input('description','') ?: null,
            'icon'              => (string)$this->input('icon','package') ?: 'package',
            'color'             => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->input('color','#7c5cff')) ? (string)$this->input('color') : '#7c5cff',
            'sla_minutes'       => ((int)$this->input('sla_minutes', 0)) ?: null,
            'requires_approval' => (int)($this->input('requires_approval') ? 1 : 0),
            'approver_user_id'  => ((int)$this->input('approver_user_id', 0)) ?: null,
            'is_active'         => (int)($this->input('is_active') ? 1 : 0),
            'is_public'         => (int)($this->input('is_public') ? 1 : 0),
            'sort_order'        => (int)$this->input('sort_order', 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Item actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    public function catalogToggleVisibility(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $item = $this->db->one('SELECT * FROM service_catalog_items WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if ($item) {
            $this->db->update('service_catalog_items', ['is_public' => $item['is_public'] ? 0 : 1], 'id=?', [$id]);
            $this->session->flash('success', $item['is_public'] ? 'Item ocultado del portal público.' : 'Item visible en portal público.');
        }
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    public function catalogDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $this->db->delete('service_catalog_items', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    /* ─────────── Change Requests ─────────── */
    public function changeStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();

        $title = trim((string)$this->input('title',''));
        if ($title === '') { $this->session->flash('error','Título requerido.'); $this->redirect('/t/' . $tenant->slug . '/itsm'); }

        $code = $this->generateChangeCode($tenant->id);
        $approverId = ((int)$this->input('approver_user_id', 0)) ?: null;
        $needsApproval = (bool)$this->input('requires_approval') || $approverId;

        $changeId = $this->db->insert('change_requests', [
            'tenant_id'         => $tenant->id,
            'code'              => $code,
            'title'             => $title,
            'description'       => (string)$this->input('description','') ?: null,
            'type'              => in_array($this->input('type'), ['standard','normal','emergency'], true) ? (string)$this->input('type') : 'normal',
            'risk'              => in_array($this->input('risk'), ['low','medium','high'], true) ? (string)$this->input('risk') : 'medium',
            'impact'            => in_array($this->input('impact'), ['low','medium','high'], true) ? (string)$this->input('impact') : 'medium',
            'status'            => $needsApproval ? 'pending_approval' : 'draft',
            'requester_id'      => $this->auth->userId(),
            'assignee_id'       => ((int)$this->input('assignee_id', 0)) ?: null,
            'planned_start'     => $this->parseDate($this->input('planned_start')),
            'planned_end'       => $this->parseDate($this->input('planned_end')),
            'rollback_plan'     => (string)$this->input('rollback_plan','') ?: null,
            'test_plan'         => (string)$this->input('test_plan','') ?: null,
            'affected_services' => (string)$this->input('affected_services','') ?: null,
        ]);

        if ($approverId) {
            $this->db->insert('change_approvals', [
                'tenant_id'   => $tenant->id,
                'change_id'   => $changeId,
                'approver_id' => $approverId,
                'status'      => 'pending',
                'sort_order'  => 0,
            ]);
        }

        $this->session->flash('success', "Change $code creado." . ($needsApproval ? ' Pendiente de aprobación.' : ''));
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    public function changeShow(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.view');
        $id = (int)$params['id'];
        $change = $this->db->one(
            'SELECT cr.*, u.name AS requester_name, a.name AS assignee_name FROM change_requests cr LEFT JOIN users u ON u.id = cr.requester_id LEFT JOIN users a ON a.id = cr.assignee_id WHERE cr.id=? AND cr.tenant_id=?',
            [$id, $tenant->id]
        );
        if (!$change) { $this->redirect('/t/' . $tenant->slug . '/itsm'); }
        $approvals = $this->db->all('SELECT a.*, u.name AS approver_name FROM change_approvals a JOIN users u ON u.id = a.approver_id WHERE a.change_id = ? ORDER BY a.sort_order, a.id', [$id]);
        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tenant->id]);

        $this->render('itsm/change_show', [
            'title' => $change['code'] . ' · ' . $change['title'],
            'change' => $change,
            'approvals' => $approvals,
            'users' => $users,
        ]);
    }

    public function changeUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $allowed = ['draft','pending_approval','approved','rejected','scheduled','in_progress','completed','cancelled','failed'];
        $status = in_array($this->input('status'), $allowed, true) ? (string)$this->input('status') : 'draft';
        $this->db->update('change_requests', [
            'title' => trim((string)$this->input('title','')),
            'description' => (string)$this->input('description','') ?: null,
            'status' => $status,
            'assignee_id' => ((int)$this->input('assignee_id', 0)) ?: null,
            'planned_start' => $this->parseDate($this->input('planned_start')),
            'planned_end' => $this->parseDate($this->input('planned_end')),
            'rollback_plan' => (string)$this->input('rollback_plan','') ?: null,
            'test_plan' => (string)$this->input('test_plan','') ?: null,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Change actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/itsm/changes/' . $id);
    }

    public function changeApprove(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.approve');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $decision = (string)$this->input('decision', 'approved');
        if (!in_array($decision, ['approved','rejected'], true)) $decision = 'approved';

        $approval = $this->db->one('SELECT * FROM change_approvals WHERE id = ? AND tenant_id = ? AND approver_id = ?', [$id, $tenant->id, $this->auth->userId()]);
        if (!$approval) { $this->session->flash('error','No tenés permiso para aprobar esto.'); $this->back(); }

        $this->db->update('change_approvals', [
            'status' => $decision,
            'comment' => (string)$this->input('comment','') ?: null,
            'decided_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);

        // Si todas las aprobaciones del change están en 'approved', mover el change a 'approved'
        $changeId = (int)$approval['change_id'];
        $allApproved = (int)$this->db->val("SELECT COUNT(*) FROM change_approvals WHERE change_id = ? AND status <> 'approved'", [$changeId]) === 0;
        if ($decision === 'approved' && $allApproved) {
            $this->db->update('change_requests', ['status' => 'approved'], 'id = :id', ['id' => $changeId]);
        } elseif ($decision === 'rejected') {
            $this->db->update('change_requests', ['status' => 'rejected'], 'id = :id', ['id' => $changeId]);
        }
        $this->session->flash('success', $decision === 'approved' ? 'Change aprobado.' : 'Change rechazado.');
        $this->redirect('/t/' . $tenant->slug . '/itsm/changes/' . $changeId);
    }

    /* ─────────── Problems ─────────── */
    public function problemStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $title = trim((string)$this->input('title',''));
        if ($title === '') { $this->session->flash('error','Título requerido.'); $this->redirect('/t/' . $tenant->slug . '/itsm'); }

        $this->db->insert('problems', [
            'tenant_id' => $tenant->id,
            'code'      => 'PRB-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)),
            'title'     => $title,
            'description' => (string)$this->input('description','') ?: null,
            'priority'  => in_array($this->input('priority'), ['low','medium','high','urgent'], true) ? (string)$this->input('priority') : 'medium',
            'assignee_id' => ((int)$this->input('assignee_id', 0)) ?: null,
            'status'    => 'new',
        ]);
        $this->session->flash('success','Problem creado.');
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    public function problemUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('itsm');
        $this->requireCan('itsm.create');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $status = in_array($this->input('status'), ['new','investigating','known_error','resolved','closed'], true) ? (string)$this->input('status') : 'new';
        $data = [
            'title' => trim((string)$this->input('title','')),
            'description' => (string)$this->input('description','') ?: null,
            'root_cause' => (string)$this->input('root_cause','') ?: null,
            'workaround' => (string)$this->input('workaround','') ?: null,
            'status' => $status,
            'priority' => in_array($this->input('priority'), ['low','medium','high','urgent'], true) ? (string)$this->input('priority') : 'medium',
            'assignee_id' => ((int)$this->input('assignee_id', 0)) ?: null,
        ];
        if (in_array($status, ['resolved','closed'])) $data['resolved_at'] = date('Y-m-d H:i:s');
        $this->db->update('problems', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Problem actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/itsm');
    }

    /* ─────────── helpers ─────────── */

    protected function generateChangeCode(int $tenantId): string
    {
        for ($i = 0; $i < 6; $i++) {
            $code = 'CHG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            if (!$this->db->val('SELECT id FROM change_requests WHERE tenant_id=? AND code=?', [$tenantId, $code])) return $code;
        }
        return 'CHG-' . substr((string)time(), -6);
    }

    protected function parseDate($v): ?string
    {
        $v = (string)$v;
        if ($v === '') return null;
        $v = str_replace('T', ' ', $v);
        if (strlen($v) === 16) $v .= ':00';
        return $v;
    }
}
