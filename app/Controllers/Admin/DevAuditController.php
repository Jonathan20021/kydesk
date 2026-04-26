<?php
namespace App\Controllers\Admin;

class DevAuditController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $devId = (int)$this->input('developer_id', 0);
        $action = trim((string)$this->input('action', ''));
        $where = '1=1'; $args = [];
        if ($devId > 0) { $where .= ' AND l.developer_id = ?'; $args[] = $devId; }
        if ($action !== '') { $where .= ' AND l.action LIKE ?'; $args[] = "%$action%"; }

        $rows = $this->db->all(
            "SELECT l.*, d.name AS dev_name, d.email AS dev_email
             FROM dev_audit_logs l
             LEFT JOIN developers d ON d.id = l.developer_id
             WHERE $where ORDER BY l.id DESC LIMIT 300",
            $args
        );

        $devs = $this->db->all('SELECT id, name, email FROM developers ORDER BY name');

        $this->render('admin/dev_audit/index', [
            'title' => 'Auditoría Developers',
            'pageHeading' => 'Auditoría del Developer Portal',
            'logs' => $rows,
            'developers' => $devs,
            'devId' => $devId,
            'action' => $action,
        ]);
    }

    public function requestLog(): void
    {
        $this->requireSuperAuth();
        $devId = (int)$this->input('developer_id', 0);
        $appId = (int)$this->input('app_id', 0);
        $where = '1=1'; $args = [];
        if ($devId > 0) { $where .= ' AND r.developer_id = ?'; $args[] = $devId; }
        if ($appId > 0) { $where .= ' AND r.app_id = ?'; $args[] = $appId; }

        $rows = $this->db->all(
            "SELECT r.*, d.name AS dev_name, d.email AS dev_email, a.name AS app_name
             FROM dev_api_request_log r
             LEFT JOIN developers d ON d.id = r.developer_id
             LEFT JOIN dev_apps a ON a.id = r.app_id
             WHERE $where ORDER BY r.id DESC LIMIT 500",
            $args
        );

        $devs = $this->db->all('SELECT id, name, email FROM developers ORDER BY name');

        $this->render('admin/dev_audit/requests', [
            'title' => 'Log de requests API',
            'pageHeading' => 'Log de requests API (developers)',
            'logs' => $rows,
            'developers' => $devs,
            'devId' => $devId,
            'appId' => $appId,
        ]);
    }
}
