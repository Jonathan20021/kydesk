<?php
namespace App\Controllers\Admin;

class DevSubscriptionController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $status = (string)$this->input('status', '');
        $args = []; $where = '1=1';
        if ($status !== '') { $where .= ' AND s.status=?'; $args[] = $status; }
        $rows = $this->db->all(
            "SELECT s.*, d.name AS dev_name, d.email AS dev_email, p.name AS plan_name, p.color AS plan_color
             FROM dev_subscriptions s
             JOIN developers d ON d.id = s.developer_id
             JOIN dev_plans p ON p.id = s.plan_id
             WHERE $where ORDER BY s.id DESC LIMIT 200",
            $args
        );
        $this->render('admin/dev_subscriptions/index', [
            'title' => 'Suscripciones Developers',
            'pageHeading' => 'Suscripciones de developers',
            'subs' => $rows,
            'status' => $status,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $sub = $this->db->one('SELECT * FROM dev_subscriptions WHERE id=?', [$id]);
        if (!$sub) $this->redirect('/admin/dev-subscriptions');
        $data = [
            'status' => (string)$this->input('status', $sub['status']),
            'billing_cycle' => (string)$this->input('billing_cycle', $sub['billing_cycle']),
            'amount' => (float)$this->input('amount', $sub['amount']),
            'auto_renew' => (int)($this->input('auto_renew') ? 1 : 0),
            'notes' => (string)$this->input('notes', ''),
        ];
        $this->db->update('dev_subscriptions', $data, 'id=?', [$id]);
        $this->superAuth->log('dev_sub.update', 'dev_subscription', $id);
        $this->session->flash('success', 'Suscripción actualizada.');
        $this->back();
    }

    public function cancel(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('dev_subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'auto_renew' => 0,
        ], 'id=?', [$id]);
        $this->superAuth->log('dev_sub.cancel', 'dev_subscription', $id);
        $this->session->flash('success', 'Suscripción cancelada.');
        $this->back();
    }
}
