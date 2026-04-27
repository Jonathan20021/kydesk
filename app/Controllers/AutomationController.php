<?php
namespace App\Controllers;

use App\Core\Controller;

class AutomationController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('automations');
        $this->requireCan('automations.view');
        $automations = $this->db->all(
            "SELECT a.*, u.name author_name FROM automations a LEFT JOIN users u ON u.id = a.created_by
             WHERE a.tenant_id=? ORDER BY a.active DESC, a.run_count DESC",
            [$tenant->id]
        );
        $stats = [
            'total' => count($automations),
            'active' => (int)$this->db->val('SELECT COUNT(*) FROM automations WHERE tenant_id=? AND active=1', [$tenant->id]),
            'runs' => (int)$this->db->val('SELECT SUM(run_count) FROM automations WHERE tenant_id=?', [$tenant->id]) ?: 0,
        ];
        $this->render('automations/index', ['title'=>'Automatizaciones','automations'=>$automations,'stats'=>$stats]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('automations');
        $this->requireCan('automations.edit');
        $categories = $this->db->all('SELECT id, name FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $technicians = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_technician=1 ORDER BY name', [$tenant->id]);
        $departments = [];
        if (\App\Core\Plan::has($tenant, 'departments')) {
            try {
                $departments = $this->db->all('SELECT id, name FROM departments WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
            } catch (\Throwable $_e) { /* tabla no existe */ }
        }
        $this->render('automations/create', [
            'title' => 'Nueva automatización',
            'categories' => $categories,
            'technicians' => $technicians,
            'departments' => $departments,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('automations');
        $this->requireCan('automations.edit');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        $trigger = (string)$this->input('trigger_event', 'ticket.created');
        $allowedTriggers = ['ticket.created','ticket.updated','ticket.sla_breach','ticket.escalated','ticket.resolved'];
        if (!in_array($trigger, $allowedTriggers, true)) $trigger = 'ticket.created';
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/automations/create');
        }

        $conditions = [];
        if ($pri = (string)$this->input('cond_priority','')) $conditions['priority'] = $pri;
        if ($cat = (int)$this->input('cond_category_id',0)) $conditions['category_id'] = $cat;
        if ($dept = (int)$this->input('cond_department_id',0)) $conditions['department_id'] = $dept;
        if ($keyword = trim((string)$this->input('cond_keyword',''))) $conditions['keyword'] = $keyword;

        $actions = [];
        if ($act = (string)$this->input('act_set_priority','')) $actions['set_priority'] = $act;
        if ($assignTo = (int)$this->input('act_assign_to',0)) $actions['assign_to'] = $assignTo;
        if ($assignDept = (int)$this->input('act_assign_to_department',0)) $actions['assign_to_department'] = $assignDept;
        if ($status = (string)$this->input('act_set_status','')) $actions['set_status'] = $status;
        if ($notify = (string)$this->input('act_notify_email','')) $actions['notify_email'] = $notify;
        if ($comment = trim((string)$this->input('act_add_comment',''))) $actions['add_comment'] = $comment;

        $this->db->insert('automations', [
            'tenant_id' => $tenant->id,
            'name' => $name,
            'description' => (string)$this->input('description',''),
            'trigger_event' => $trigger,
            'conditions' => json_encode($conditions, JSON_UNESCAPED_UNICODE),
            'actions' => json_encode($actions, JSON_UNESCAPED_UNICODE),
            'active' => (int)($this->input('active',1) ? 1 : 0),
            'created_by' => $this->auth->userId(),
        ]);
        $this->session->flash('success','Automatización creada.');
        $this->redirect('/t/' . $tenant->slug . '/automations');
    }

    public function toggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('automations');
        $this->requireCan('automations.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $a = $this->db->one('SELECT active FROM automations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if ($a) {
            $this->db->update('automations', ['active' => $a['active'] ? 0 : 1], 'id=?', ['id' => $id]);
        }
        $this->redirect('/t/' . $tenant->slug . '/automations');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('automations');
        $this->requireCan('automations.delete');
        $this->validateCsrf();
        $this->db->delete('automations', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success','Automatización eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/automations');
    }
}
