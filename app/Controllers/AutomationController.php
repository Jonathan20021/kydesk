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
