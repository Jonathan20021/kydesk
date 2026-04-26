<?php
namespace App\Controllers\Admin;

class DeveloperController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $q = trim((string)$this->input('q', ''));
        $args = [];
        $where = '1=1';
        if ($q !== '') {
            $where .= ' AND (d.name LIKE ? OR d.email LIKE ? OR d.company LIKE ?)';
            $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%";
        }
        $devs = $this->db->all(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM dev_apps a WHERE a.developer_id=d.id) AS apps_count,
                    (SELECT COUNT(*) FROM dev_api_tokens t WHERE t.developer_id=d.id AND t.revoked_at IS NULL) AS active_tokens,
                    (SELECT s.id FROM dev_subscriptions s WHERE s.developer_id=d.id AND s.status IN ('trial','active','past_due') ORDER BY s.id DESC LIMIT 1) AS sub_id,
                    (SELECT p.name FROM dev_subscriptions s JOIN dev_plans p ON p.id=s.plan_id WHERE s.developer_id=d.id AND s.status IN ('trial','active','past_due') ORDER BY s.id DESC LIMIT 1) AS plan_name,
                    IFNULL((SELECT SUM(u.requests) FROM dev_api_usage u WHERE u.developer_id=d.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) AS month_requests
             FROM developers d WHERE $where ORDER BY d.id DESC LIMIT 200",
            $args
        );
        $this->render('admin/developers/index', [
            'title' => 'Developers',
            'pageHeading' => 'Developers',
            'developers' => $devs,
            'q' => $q,
        ]);
    }

    public function create(): void
    {
        $this->requireSuperAuth();
        $plans = $this->db->all("SELECT * FROM dev_plans WHERE is_active=1 ORDER BY sort_order ASC");
        $this->render('admin/developers/edit', [
            'title' => 'Nuevo developer',
            'pageHeading' => 'Crear developer',
            'd' => null,
            'plans' => $plans,
        ]);
    }

    public function store(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $email = trim((string)$this->input('email', ''));
        $name = trim((string)$this->input('name', ''));
        $password = (string)$this->input('password', '');
        if ($name === '' || $email === '' || strlen($password) < 6) {
            $this->session->flash('error', 'Nombre, email y password (mín 6) son requeridos.');
            $this->redirect('/admin/developers/create');
        }
        if ($this->db->one('SELECT id FROM developers WHERE email=?', [$email])) {
            $this->session->flash('error', 'Email ya en uso.');
            $this->redirect('/admin/developers/create');
        }
        $id = $this->db->insert('developers', [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'company' => (string)$this->input('company', ''),
            'website' => (string)$this->input('website', ''),
            'country' => (string)$this->input('country', ''),
            'phone' => (string)$this->input('phone', ''),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'is_verified' => (int)($this->input('is_verified') ? 1 : 0),
            'notes' => (string)$this->input('notes', ''),
        ]);

        $planId = (int)$this->input('plan_id', 0);
        if ($planId) {
            $plan = $this->db->one('SELECT * FROM dev_plans WHERE id=?', [$planId]);
            if ($plan) {
                $now = date('Y-m-d H:i:s');
                $this->db->insert('dev_subscriptions', [
                    'developer_id' => $id,
                    'plan_id' => $planId,
                    'status' => 'active',
                    'billing_cycle' => 'monthly',
                    'amount' => $plan['price_monthly'],
                    'started_at' => $now,
                    'current_period_start' => $now,
                    'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month')),
                    'auto_renew' => 1,
                ]);
            }
        }
        $this->superAuth->log('developer.create', 'developer', $id);
        $this->session->flash('success', 'Developer creado.');
        $this->redirect('/admin/developers/' . $id);
    }

    public function show(array $params): void
    {
        $this->requireSuperAuth();
        $id = (int)$params['id'];
        $d = $this->db->one('SELECT * FROM developers WHERE id=?', [$id]);
        if (!$d) $this->redirect('/admin/developers');

        $sub = $this->db->one(
            "SELECT s.*, p.slug AS plan_slug, p.name AS plan_name, p.color AS plan_color, p.icon AS plan_icon
             FROM dev_subscriptions s JOIN dev_plans p ON p.id=s.plan_id
             WHERE s.developer_id=? AND s.status IN ('trial','active','past_due') ORDER BY s.id DESC LIMIT 1",
            [$id]
        );
        $apps = $this->db->all('SELECT * FROM dev_apps WHERE developer_id=? ORDER BY id DESC', [$id]);
        $tokens = $this->db->all('SELECT t.*, a.name AS app_name FROM dev_api_tokens t LEFT JOIN dev_apps a ON a.id=t.app_id WHERE t.developer_id=? ORDER BY t.id DESC LIMIT 50', [$id]);
        $invoices = $this->db->all('SELECT * FROM dev_invoices WHERE developer_id=? ORDER BY id DESC LIMIT 25', [$id]);
        $payments = $this->db->all('SELECT * FROM dev_payments WHERE developer_id=? ORDER BY id DESC LIMIT 25', [$id]);
        $usage = $this->db->all(
            "SELECT period_date, SUM(requests) AS requests, SUM(errors) AS errors
             FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY period_date ORDER BY period_date ASC",
            [$id]
        );
        $monthRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$id]
        );
        $plans = $this->db->all('SELECT * FROM dev_plans WHERE is_active=1 ORDER BY sort_order ASC');

        $this->render('admin/developers/show', [
            'title' => $d['name'],
            'pageHeading' => $d['name'],
            'd' => $d,
            'sub' => $sub,
            'apps' => $apps,
            'tokens' => $tokens,
            'invoices' => $invoices,
            'payments' => $payments,
            'usage' => $usage,
            'monthRequests' => $monthRequests,
            'plans' => $plans,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $d = $this->db->one('SELECT * FROM developers WHERE id=?', [$id]);
        if (!$d) $this->redirect('/admin/developers');
        $data = [
            'name' => trim((string)$this->input('name', $d['name'])),
            'company' => (string)$this->input('company', ''),
            'website' => (string)$this->input('website', ''),
            'country' => (string)$this->input('country', ''),
            'phone' => (string)$this->input('phone', ''),
            'bio' => (string)$this->input('bio', ''),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'is_verified' => (int)($this->input('is_verified') ? 1 : 0),
            'notes' => (string)$this->input('notes', ''),
        ];
        $newPass = (string)$this->input('password', '');
        if ($newPass !== '') {
            $data['password'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('developers', $data, 'id=?', [$id]);
        $this->superAuth->log('developer.update', 'developer', $id);
        $this->session->flash('success', 'Developer actualizado.');
        $this->redirect('/admin/developers/' . $id);
    }

    public function suspend(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $reason = (string)$this->input('reason', 'Suspendido por super admin');
        $this->db->update('developers', [
            'suspended_at' => date('Y-m-d H:i:s'),
            'suspended_reason' => $reason,
            'is_active' => 0,
        ], 'id=?', [$id]);
        $this->superAuth->log('developer.suspend', 'developer', $id, ['reason' => $reason]);
        $this->session->flash('success', 'Developer suspendido.');
        $this->redirect('/admin/developers/' . $id);
    }

    public function activate(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('developers', [
            'suspended_at' => null,
            'suspended_reason' => null,
            'is_active' => 1,
        ], 'id=?', [$id]);
        $this->superAuth->log('developer.activate', 'developer', $id);
        $this->session->flash('success', 'Developer reactivado.');
        $this->redirect('/admin/developers/' . $id);
    }

    public function delete(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('developers', 'id=?', [$id]);
        $this->superAuth->log('developer.delete', 'developer', $id);
        $this->session->flash('success', 'Developer eliminado.');
        $this->redirect('/admin/developers');
    }

    public function changePlan(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $planId = (int)$this->input('plan_id', 0);
        $cycle = (string)$this->input('billing_cycle', 'monthly');
        if (!in_array($cycle, ['monthly','yearly','lifetime'], true)) $cycle = 'monthly';
        $plan = $this->db->one('SELECT * FROM dev_plans WHERE id=?', [$planId]);
        if (!$plan) {
            $this->session->flash('error', 'Plan inválido.');
            $this->redirect('/admin/developers/' . $id);
        }
        $now = date('Y-m-d H:i:s');
        $this->db->update('dev_subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => $now,
        ], "developer_id=? AND status IN ('trial','active','past_due')", [$id]);

        $amount = $cycle === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly'];
        $end = $cycle === 'yearly' ? date('Y-m-d H:i:s', strtotime('+1 year'))
            : ($cycle === 'lifetime' ? date('Y-m-d H:i:s', strtotime('+50 years'))
                : date('Y-m-d H:i:s', strtotime('+1 month')));
        $subId = $this->db->insert('dev_subscriptions', [
            'developer_id' => $id,
            'plan_id' => $planId,
            'status' => 'active',
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'started_at' => $now,
            'current_period_start' => $now,
            'current_period_end' => $end,
            'auto_renew' => $cycle === 'lifetime' ? 0 : 1,
        ]);
        $this->superAuth->log('developer.change_plan', 'dev_subscription', $subId, ['plan_id' => $planId, 'cycle' => $cycle]);
        $this->session->flash('success', 'Plan asignado correctamente.');
        $this->redirect('/admin/developers/' . $id);
    }
}
