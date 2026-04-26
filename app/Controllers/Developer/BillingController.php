<?php
namespace App\Controllers\Developer;

class BillingController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();

        $sub = $this->devAuth->activeSubscription();
        $invoices = $this->db->all('SELECT * FROM dev_invoices WHERE developer_id=? ORDER BY created_at DESC LIMIT 25', [$devId]);
        $payments = $this->db->all('SELECT * FROM dev_payments WHERE developer_id=? ORDER BY created_at DESC LIMIT 25', [$devId]);

        $totalPaid = (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM dev_payments WHERE developer_id=? AND status='completed'", [$devId]);
        $totalPending = (float)$this->db->val("SELECT IFNULL(SUM(total - amount_paid),0) FROM dev_invoices WHERE developer_id=? AND status IN ('pending','overdue','partial')", [$devId]);

        $this->render('developers/billing/index', [
            'title' => 'Facturación',
            'pageHeading' => 'Facturación',
            'sub' => $sub,
            'invoices' => $invoices,
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
        ]);
    }

    public function plans(): void
    {
        $this->requireDeveloper();
        $plans = $this->db->all("SELECT * FROM dev_plans WHERE is_active=1 AND is_public=1 ORDER BY sort_order ASC, price_monthly ASC");
        $sub = $this->devAuth->activeSubscription();
        $this->render('developers/billing/plans', [
            'title' => 'Planes API',
            'pageHeading' => 'Elige tu plan',
            'plans' => $plans,
            'currentPlanId' => $sub['plan_id'] ?? null,
            'sub' => $sub,
        ]);
    }

    public function checkout(array $params): void
    {
        $this->requireDeveloper();
        $planId = (int)$params['id'];
        $plan = $this->db->one('SELECT * FROM dev_plans WHERE id=? AND is_active=1', [$planId]);
        if (!$plan) $this->redirect('/developers/billing/plans');
        $this->render('developers/billing/checkout', [
            'title' => 'Confirmar plan',
            'pageHeading' => 'Confirmar plan: ' . $plan['name'],
            'plan' => $plan,
        ]);
    }

    public function subscribe(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $planId = (int)$params['id'];
        $plan = $this->db->one('SELECT * FROM dev_plans WHERE id=? AND is_active=1', [$planId]);
        if (!$plan) $this->redirect('/developers/billing/plans');

        $cycle = (string)$this->input('billing_cycle', 'monthly');
        if (!in_array($cycle, ['monthly','yearly'], true)) $cycle = 'monthly';
        $amount = $cycle === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly'];
        $isFree = (float)$amount == 0.0;
        $now = date('Y-m-d H:i:s');
        $endDate = $isFree ? date('Y-m-d H:i:s', strtotime('+10 years'))
            : ($cycle === 'yearly' ? date('Y-m-d H:i:s', strtotime('+1 year')) : date('Y-m-d H:i:s', strtotime('+1 month')));

        // Capturar plan anterior para el email
        $prevSub = $this->db->one(
            "SELECT p.name AS plan_name FROM dev_subscriptions s JOIN dev_plans p ON p.id=s.plan_id
             WHERE s.developer_id=? AND s.status IN ('trial','active','past_due') ORDER BY s.id DESC LIMIT 1",
            [$devId]
        );
        $prevPlanName = $prevSub['plan_name'] ?? 'Sin plan';

        // Cancelar la suscripción activa anterior
        $this->db->update('dev_subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => $now,
        ], "developer_id=? AND status IN ('trial','active','past_due')", [$devId]);

        // status: trial si el plan tiene trial y no es free, active en otro caso
        $trialDays = (int)$plan['trial_days'];
        $status = ($isFree || $trialDays === 0) ? 'active' : 'trial';
        $trialEnds = ($status === 'trial') ? date('Y-m-d H:i:s', strtotime("+{$trialDays} days")) : null;

        $subId = $this->db->insert('dev_subscriptions', [
            'developer_id' => $devId,
            'plan_id' => $planId,
            'status' => $status,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'started_at' => $now,
            'trial_ends_at' => $trialEnds,
            'current_period_start' => $now,
            'current_period_end' => $endDate,
            'auto_renew' => $isFree ? 0 : 1,
        ]);

        // Si no es free, crear factura en pending para que el super admin confirme el pago
        if (!$isFree) {
            $invNumber = 'DEV-' . date('Ymd') . '-' . str_pad((string)$subId, 5, '0', STR_PAD_LEFT);
            $invId = $this->db->insert('dev_invoices', [
                'invoice_number' => $invNumber,
                'developer_id' => $devId,
                'subscription_id' => $subId,
                'status' => 'pending',
                'subtotal' => $amount,
                'total' => $amount,
                'currency' => $plan['currency'] ?? 'USD',
                'description' => "Suscripción {$plan['name']} ({$cycle})",
                'issue_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+7 days')),
            ]);
            // Email: nueva factura creada
            $dev = $this->devAuth->developer();
            \App\Core\DevMailer::invoiceCreated((string)$dev['email'], (string)$dev['name'], $invNumber, (float)$amount, (string)($plan['currency'] ?? 'USD'), date('Y-m-d', strtotime('+7 days')), $invId);
        }

        // Email: cambio de plan
        $dev = $dev ?? $this->devAuth->developer();
        \App\Core\DevMailer::subscriptionChanged((string)$dev['email'], (string)$dev['name'], $prevPlanName, (string)$plan['name']);

        $this->devAuth->log('subscription.change', 'dev_subscription', $subId, ['plan_id' => $planId, 'cycle' => $cycle]);
        $this->session->flash('success', $isFree ? 'Plan activado.' : 'Suscripción creada — generamos una factura pendiente. Contacta al equipo de billing para completar el pago.');
        $this->redirect('/developers/billing');
    }

    public function cancel(): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $sub = $this->devAuth->activeSubscription();
        if ($sub) {
            $this->db->update('dev_subscriptions', [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'auto_renew' => 0,
            ], 'id=? AND developer_id=?', [$sub['id'], $devId]);
            $this->devAuth->log('subscription.cancel', 'dev_subscription', (int)$sub['id']);
            $this->session->flash('success', 'Suscripción cancelada. Seguirás con acceso hasta el final del periodo actual.');
        }
        $this->redirect('/developers/billing');
    }

    public function invoiceShow(array $params): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $id = (int)$params['id'];
        $inv = $this->db->one('SELECT * FROM dev_invoices WHERE id=? AND developer_id=?', [$id, $devId]);
        if (!$inv) $this->redirect('/developers/billing');
        $payments = $this->db->all('SELECT * FROM dev_payments WHERE invoice_id=? ORDER BY id DESC', [$id]);
        $this->render('developers/billing/invoice', [
            'title' => 'Factura ' . $inv['invoice_number'],
            'pageHeading' => 'Factura ' . $inv['invoice_number'],
            'inv' => $inv,
            'payments' => $payments,
        ]);
    }
}
