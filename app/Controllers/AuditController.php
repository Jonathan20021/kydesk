<?php
namespace App\Controllers;

use App\Core\Controller;

class AuditController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('audit');
        $this->requireCan('audit.view');
        $action = (string)($_GET['action'] ?? '');
        $entity = (string)($_GET['entity'] ?? '');
        $where = ['al.tenant_id=?']; $args = [$tenant->id];
        if ($action) { $where[] = 'al.action = ?'; $args[] = $action; }
        if ($entity) { $where[] = 'al.entity = ?'; $args[] = $entity; }
        $logs = $this->db->all(
            "SELECT al.*, u.name user_name, u.email user_email FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE " . implode(' AND ', $where) . " ORDER BY al.created_at DESC LIMIT 200",
            $args
        );
        $this->render('audit/index', ['title'=>'Auditoría','logs'=>$logs,'actionFilter'=>$action,'entityFilter'=>$entity]);
    }
}
