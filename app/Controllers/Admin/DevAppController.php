<?php
namespace App\Controllers\Admin;

class DevAppController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $q = trim((string)$this->input('q', ''));
        $args = []; $where = '1=1';
        if ($q !== '') {
            $where .= ' AND (a.name LIKE ? OR a.slug LIKE ? OR d.email LIKE ?)';
            $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%";
        }
        $apps = $this->db->all(
            "SELECT a.*, d.name AS dev_name, d.email AS dev_email,
                    (SELECT COUNT(*) FROM dev_api_tokens t WHERE t.app_id=a.id AND t.revoked_at IS NULL) AS active_tokens,
                    IFNULL((SELECT SUM(u.requests) FROM dev_api_usage u WHERE u.app_id=a.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) AS month_requests
             FROM dev_apps a
             JOIN developers d ON d.id = a.developer_id
             WHERE $where ORDER BY a.id DESC LIMIT 200",
            $args
        );
        $this->render('admin/dev_apps/index', [
            'title' => 'Apps Developers',
            'pageHeading' => 'Apps creadas por developers',
            'apps' => $apps,
            'q' => $q,
        ]);
    }

    public function suspend(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $reason = (string)$this->input('reason', 'Suspendida por super admin');
        $this->db->update('dev_apps', [
            'status' => 'suspended',
            'suspended_reason' => $reason,
        ], 'id=?', [$id]);
        $this->superAuth->log('dev_app.suspend', 'dev_app', $id, ['reason' => $reason]);
        $this->session->flash('success', 'App suspendida.');
        $this->back();
    }

    public function activate(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('dev_apps', [
            'status' => 'active',
            'suspended_reason' => null,
        ], 'id=?', [$id]);
        $this->superAuth->log('dev_app.activate', 'dev_app', $id);
        $this->session->flash('success', 'App reactivada.');
        $this->back();
    }

    public function delete(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        // Borrar también el tenant asociado (sandbox)
        $app = $this->db->one('SELECT tenant_id FROM dev_apps WHERE id=?', [$id]);
        $this->db->delete('dev_apps', 'id=?', [$id]);
        if ($app && !empty($app['tenant_id'])) {
            $this->db->delete('tenants', 'id=? AND is_developer_sandbox=1', [(int)$app['tenant_id']]);
        }
        $this->superAuth->log('dev_app.delete', 'dev_app', $id);
        $this->session->flash('success', 'App eliminada.');
        $this->redirect('/admin/dev-apps');
    }
}
