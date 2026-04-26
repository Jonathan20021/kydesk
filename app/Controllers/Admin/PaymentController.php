<?php
namespace App\Controllers\Admin;

class PaymentController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('payments.view');
        $payments = $this->db->all(
            "SELECT p.*, t.name AS tenant_name, t.slug AS tenant_slug, i.invoice_number,
                    pp.id AS proof_id, pp.bank_used AS proof_bank, pp.transfer_date AS proof_transfer_date,
                    pp.file_path AS proof_file_path, pp.submitter_name AS proof_submitter
             FROM payments p
             JOIN tenants t ON t.id = p.tenant_id
             LEFT JOIN invoices i ON i.id = p.invoice_id
             LEFT JOIN payment_proofs pp ON pp.id = p.payment_proof_id
             ORDER BY p.paid_at DESC, p.created_at DESC LIMIT 200"
        );
        $stats = [
            'total' => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'"),
            'count' => (int)$this->db->val("SELECT COUNT(*) FROM payments WHERE status='completed'"),
            'this_month' => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE_FORMAT(paid_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"),
        ];
        $this->render('admin/payments/index', [
            'title' => 'Pagos',
            'pageHeading' => 'Historial de Pagos',
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    public function store(): void
    {
        $this->requireCan('payments.create');
        $this->validateCsrf();
        $tenantId = (int)$this->input('tenant_id', 0);
        $invoiceId = ((int)$this->input('invoice_id', 0)) ?: null;
        $amount = (float)$this->input('amount', 0);
        if (!$tenantId || $amount <= 0) {
            $this->session->flash('error', 'Datos inválidos.');
            $this->back();
        }
        $id = $this->db->insert('payments', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'currency' => (string)$this->input('currency', 'USD'),
            'method' => (string)$this->input('method', 'manual'),
            'reference' => (string)$this->input('reference', ''),
            'status' => 'completed',
            'notes' => (string)$this->input('notes', ''),
            'created_by' => $this->superAuth->id(),
            'paid_at' => $this->input('paid_at') ?: date('Y-m-d H:i:s'),
        ]);
        if ($invoiceId) {
            $inv = $this->db->one('SELECT total, amount_paid FROM invoices WHERE id = ?', [$invoiceId]);
            if ($inv) {
                $newPaid = $inv['amount_paid'] + $amount;
                $status = $newPaid >= $inv['total'] ? 'paid' : 'partial';
                $this->db->update('invoices', [
                    'amount_paid' => $newPaid,
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
                ], 'id = :id', ['id' => $invoiceId]);
            }
        }
        $this->superAuth->log('payment.create', 'payment', $id, ['amount' => $amount]);
        $this->session->flash('success', 'Pago registrado.');
        $this->back();
    }
}
