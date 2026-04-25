<?php
namespace App\Controllers;

use App\Core\Controller;

class SlaController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('sla');
        $this->requireCan('sla.view');
        $policies = $this->db->all('SELECT * FROM sla_policies WHERE tenant_id=? ORDER BY FIELD(priority,"urgent","high","medium","low")', [$tenant->id]);
        $compliance = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status IN ('resolved','closed') AND sla_breached=0", [$tenant->id]);
        $breached = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND sla_breached=1", [$tenant->id]);
        $atRisk = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status IN ('open','in_progress') AND sla_due_at IS NOT NULL AND TIMESTAMPDIFF(MINUTE, NOW(), sla_due_at) BETWEEN 0 AND 60", [$tenant->id]);
        $this->render('sla/index', ['title'=>'Políticas SLA','policies'=>$policies,'compliance'=>$compliance,'breached'=>$breached,'atRisk'=>$atRisk]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('sla');
        $this->requireCan('sla.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('sla_policies', [
            'name' => (string)$this->input('name','Policy'),
            'response_minutes' => (int)$this->input('response_minutes',60),
            'resolve_minutes' => (int)$this->input('resolve_minutes',1440),
            'active' => (int)($this->input('active',0) ? 1 : 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Política actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/sla');
    }
}
