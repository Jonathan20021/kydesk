<?php
namespace App\Controllers\Admin;

class SubscriptionController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('subscriptions.view');
        $status = (string)$this->input('status', '');
        $where = ['1=1'];
        $params = [];
        if ($status) { $where[] = 's.status = ?'; $params[] = $status; }

        $subs = $this->db->all(
            "SELECT s.*, t.name AS tenant_name, t.slug AS tenant_slug, p.name AS plan_name, p.color AS plan_color
             FROM subscriptions s
             JOIN tenants t ON t.id = s.tenant_id
             JOIN plans p ON p.id = s.plan_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY s.created_at DESC LIMIT 200",
            $params
        );

        $stats = [
            'active' => (int)$this->db->val("SELECT COUNT(*) FROM subscriptions WHERE status='active'"),
            'trial'  => (int)$this->db->val("SELECT COUNT(*) FROM subscriptions WHERE status='trial'"),
            'cancelled' => (int)$this->db->val("SELECT COUNT(*) FROM subscriptions WHERE status='cancelled'"),
            'mrr' => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND billing_cycle='monthly'"),
        ];

        $this->render('admin/subscriptions/index', [
            'title' => 'Suscripciones',
            'pageHeading' => 'Suscripciones',
            'subs' => $subs,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCan('subscriptions.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $sub = $this->db->one('SELECT * FROM subscriptions WHERE id = ?', [$id]);
        if (!$sub) $this->redirect('/admin/subscriptions');

        $data = [
            'plan_id' => (int)$this->input('plan_id', $sub['plan_id']),
            'status' => (string)$this->input('status', $sub['status']),
            'billing_cycle' => (string)$this->input('billing_cycle', $sub['billing_cycle']),
            'amount' => (float)$this->input('amount', $sub['amount']),
            'auto_renew' => (int)($this->input('auto_renew') ? 1 : 0),
            'current_period_end' => $this->input('current_period_end', $sub['current_period_end']) ?: null,
        ];

        // Sync tenant.plan
        $plan = $this->db->one('SELECT slug FROM plans WHERE id = ?', [$data['plan_id']]);
        if ($plan) {
            $this->db->update('tenants', ['plan' => $plan['slug']], 'id = :id', ['id' => $sub['tenant_id']]);
        }

        $this->db->update('subscriptions', $data, 'id = :id', ['id' => $id]);
        $this->superAuth->log('subscription.update', 'subscription', $id, $data);
        $this->session->flash('success', 'Suscripción actualizada.');
        $this->redirect('/admin/tenants/' . $sub['tenant_id']);
    }

    public function cancel(array $params): void
    {
        $this->requireCan('subscriptions.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'auto_renew' => 0,
        ], 'id = :id', ['id' => $id]);
        $this->superAuth->log('subscription.cancel', 'subscription', $id);
        $this->session->flash('success', 'Suscripción cancelada.');
        $this->back();
    }
}
