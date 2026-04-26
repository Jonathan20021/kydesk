<?php
namespace App\Controllers\Admin;

class DevInvoiceController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $status = (string)$this->input('status', '');
        $args = []; $where = '1=1';
        if ($status !== '') { $where .= ' AND i.status=?'; $args[] = $status; }
        $rows = $this->db->all(
            "SELECT i.*, d.name AS dev_name, d.email AS dev_email
             FROM dev_invoices i JOIN developers d ON d.id=i.developer_id
             WHERE $where ORDER BY i.id DESC LIMIT 200",
            $args
        );
        $this->render('admin/dev_invoices/index', [
            'title' => 'Facturas Developers',
            'pageHeading' => 'Facturas de developers',
            'invoices' => $rows,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->requireSuperAuth();
        $devs = $this->db->all('SELECT id, name, email, company FROM developers ORDER BY name');
        $this->render('admin/dev_invoices/edit', [
            'title' => 'Nueva factura developer',
            'pageHeading' => 'Crear factura',
            'inv' => null,
            'developers' => $devs,
        ]);
    }

    public function store(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $devId = (int)$this->input('developer_id', 0);
        $sub = $this->db->one("SELECT id FROM dev_subscriptions WHERE developer_id=? AND status IN ('active','trial','past_due') ORDER BY id DESC LIMIT 1", [$devId]);
        $subtotal = (float)$this->input('subtotal', 0);
        $taxRate = (float)$this->input('tax_rate', 0);
        $discount = (float)$this->input('discount', 0);
        $taxAmount = round(($subtotal - $discount) * ($taxRate / 100), 2);
        $total = round($subtotal - $discount + $taxAmount, 2);
        $invNumber = 'DEV-' . date('Ymd') . '-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $id = $this->db->insert('dev_invoices', [
            'invoice_number' => $invNumber,
            'developer_id' => $devId,
            'subscription_id' => $sub['id'] ?? null,
            'status' => (string)$this->input('status', 'pending'),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount' => $discount,
            'total' => $total,
            'currency' => (string)$this->input('currency', 'USD'),
            'description' => (string)$this->input('description', ''),
            'issue_date' => (string)$this->input('issue_date', date('Y-m-d')),
            'due_date' => (string)$this->input('due_date', date('Y-m-d', strtotime('+7 days'))),
            'notes' => (string)$this->input('notes', ''),
        ]);
        $this->superAuth->log('dev_invoice.create', 'dev_invoice', $id);
        $this->session->flash('success', 'Factura creada.');
        $this->redirect('/admin/dev-invoices/' . $id);
    }

    public function show(array $params): void
    {
        $this->requireSuperAuth();
        $id = (int)$params['id'];
        $inv = $this->db->one('SELECT * FROM dev_invoices WHERE id=?', [$id]);
        if (!$inv) $this->redirect('/admin/dev-invoices');
        $dev = $this->db->one('SELECT * FROM developers WHERE id=?', [$inv['developer_id']]);
        $payments = $this->db->all('SELECT * FROM dev_payments WHERE invoice_id=? ORDER BY id DESC', [$id]);
        $this->render('admin/dev_invoices/show', [
            'title' => 'Factura ' . $inv['invoice_number'],
            'pageHeading' => 'Factura ' . $inv['invoice_number'],
            'inv' => $inv,
            'dev' => $dev,
            'payments' => $payments,
        ]);
    }

    public function markPaid(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $inv = $this->db->one('SELECT * FROM dev_invoices WHERE id=?', [$id]);
        if (!$inv) $this->redirect('/admin/dev-invoices');
        $amount = (float)$this->input('amount', $inv['total'] - $inv['amount_paid']);
        $this->db->insert('dev_payments', [
            'developer_id' => $inv['developer_id'],
            'invoice_id' => $id,
            'amount' => $amount,
            'currency' => $inv['currency'],
            'method' => (string)$this->input('method', 'manual'),
            'reference' => (string)$this->input('reference', ''),
            'status' => 'completed',
            'created_by' => $this->superAuth->id(),
            'paid_at' => date('Y-m-d H:i:s'),
            'notes' => (string)$this->input('notes', ''),
        ]);
        $totalPaid = (float)$inv['amount_paid'] + $amount;
        $newStatus = $totalPaid >= (float)$inv['total'] - 0.01 ? 'paid' : 'partial';
        $this->db->update('dev_invoices', [
            'amount_paid' => $totalPaid,
            'status' => $newStatus,
            'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null,
        ], 'id=?', [$id]);
        $this->superAuth->log('dev_invoice.pay', 'dev_invoice', $id, ['amount' => $amount]);
        $this->session->flash('success', 'Pago registrado.');
        $this->redirect('/admin/dev-invoices/' . $id);
    }

    public function delete(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('dev_invoices', 'id=?', [$id]);
        $this->superAuth->log('dev_invoice.delete', 'dev_invoice', $id);
        $this->session->flash('success', 'Factura eliminada.');
        $this->redirect('/admin/dev-invoices');
    }
}
