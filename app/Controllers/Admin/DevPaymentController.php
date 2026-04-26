<?php
namespace App\Controllers\Admin;

class DevPaymentController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $rows = $this->db->all(
            "SELECT p.*, d.name AS dev_name, d.email AS dev_email, i.invoice_number,
                    pp.id AS proof_id, pp.bank_used AS proof_bank, pp.transfer_date AS proof_transfer_date,
                    pp.file_path AS proof_file_path
             FROM dev_payments p
             JOIN developers d ON d.id = p.developer_id
             LEFT JOIN dev_invoices i ON i.id = p.invoice_id
             LEFT JOIN payment_proofs pp ON pp.id = p.payment_proof_id
             ORDER BY p.id DESC LIMIT 200"
        );
        $totalCompleted = (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM dev_payments WHERE status='completed'");
        $monthCompleted = (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM dev_payments WHERE status='completed' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
        $this->render('admin/dev_payments/index', [
            'title' => 'Pagos Developers',
            'pageHeading' => 'Pagos de developers',
            'payments' => $rows,
            'totalCompleted' => $totalCompleted,
            'monthCompleted' => $monthCompleted,
        ]);
    }

    public function store(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $devId = (int)$this->input('developer_id', 0);
        $invId = (int)$this->input('invoice_id', 0) ?: null;
        $amount = (float)$this->input('amount', 0);
        if ($devId <= 0 || $amount <= 0) {
            $this->session->flash('error', 'Developer y monto son requeridos.');
            $this->redirect('/admin/dev-payments');
        }
        $this->db->insert('dev_payments', [
            'developer_id' => $devId,
            'invoice_id' => $invId,
            'amount' => $amount,
            'currency' => (string)$this->input('currency', 'USD'),
            'method' => (string)$this->input('method', 'manual'),
            'reference' => (string)$this->input('reference', ''),
            'status' => 'completed',
            'created_by' => $this->superAuth->id(),
            'paid_at' => date('Y-m-d H:i:s'),
            'notes' => (string)$this->input('notes', ''),
        ]);
        if ($invId) {
            $inv = $this->db->one('SELECT * FROM dev_invoices WHERE id=?', [$invId]);
            if ($inv) {
                $totalPaid = (float)$inv['amount_paid'] + $amount;
                $newStatus = $totalPaid >= (float)$inv['total'] - 0.01 ? 'paid' : 'partial';
                $this->db->update('dev_invoices', [
                    'amount_paid' => $totalPaid,
                    'status' => $newStatus,
                    'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null,
                ], 'id=?', [$invId]);
            }
        }
        $this->superAuth->log('dev_payment.create', 'dev_payment', null, ['developer_id' => $devId, 'amount' => $amount]);
        $this->session->flash('success', 'Pago registrado.');
        $this->redirect('/admin/dev-payments');
    }
}
