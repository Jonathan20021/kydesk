<?php
namespace App\Controllers\Admin;

class InvoiceController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('invoices.view');
        $status = (string)$this->input('status', '');
        $q = trim((string)$this->input('q', ''));
        $where = ['1=1']; $params = [];
        if ($status) { $where[] = 'i.status = ?'; $params[] = $status; }
        if ($q !== '') {
            $where[] = '(i.invoice_number LIKE ? OR t.name LIKE ?)';
            $like = "%$q%"; $params[] = $like; $params[] = $like;
        }

        $invoices = $this->db->all(
            "SELECT i.*, t.name AS tenant_name, t.slug AS tenant_slug
             FROM invoices i JOIN tenants t ON t.id = i.tenant_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY i.created_at DESC LIMIT 200",
            $params
        );

        $stats = [
            'paid' => (float)$this->db->val("SELECT COALESCE(SUM(amount_paid),0) FROM invoices WHERE status='paid'"),
            'pending' => (float)$this->db->val("SELECT COALESCE(SUM(total - amount_paid),0) FROM invoices WHERE status IN ('pending','partial','overdue')"),
            'overdue' => (int)$this->db->val("SELECT COUNT(*) FROM invoices WHERE status='overdue'"),
            'total' => (int)$this->db->val("SELECT COUNT(*) FROM invoices"),
        ];

        $this->render('admin/invoices/index', [
            'title' => 'Facturas',
            'pageHeading' => 'Facturación',
            'invoices' => $invoices,
            'status' => $status,
            'q' => $q,
            'stats' => $stats,
        ]);
    }

    public function create(): void
    {
        $this->requireCan('invoices.create');
        $tenants = $this->db->all('SELECT id, name FROM tenants ORDER BY name ASC');
        $this->render('admin/invoices/create', [
            'title' => 'Nueva factura',
            'pageHeading' => 'Generar factura',
            'tenants' => $tenants,
            'tenantId' => (int)$this->input('tenant_id', 0),
        ]);
    }

    public function store(): void
    {
        $this->requireCan('invoices.create');
        $this->validateCsrf();
        $tenantId = (int)$this->input('tenant_id', 0);
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$tenantId]);
        if (!$tenant) {
            $this->session->flash('error', 'Empresa inválida.');
            $this->redirect('/admin/invoices/create');
        }
        $subtotal = (float)$this->input('subtotal', 0);
        $taxRate = (float)$this->input('tax_rate', 0);
        $discount = (float)$this->input('discount', 0);
        $taxAmount = round(($subtotal - $discount) * $taxRate / 100, 2);
        $total = round($subtotal - $discount + $taxAmount, 2);

        $prefix = (string)($this->db->val("SELECT `value` FROM saas_settings WHERE `key`='saas_invoice_prefix'") ?? 'INV');
        $next = (int)$this->db->val('SELECT COUNT(*) FROM invoices') + 1;
        $invoiceNumber = $prefix . '-' . date('Y') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);

        $sub = $this->db->one('SELECT id FROM subscriptions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1', [$tenantId]);

        $id = $this->db->insert('invoices', [
            'invoice_number' => $invoiceNumber,
            'tenant_id' => $tenantId,
            'subscription_id' => $sub['id'] ?? null,
            'status' => (string)$this->input('status', 'pending'),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount' => $discount,
            'total' => $total,
            'amount_paid' => 0,
            'currency' => (string)$this->input('currency', 'USD'),
            'description' => (string)$this->input('description', ''),
            'issue_date' => $this->input('issue_date') ?: date('Y-m-d'),
            'due_date' => $this->input('due_date') ?: date('Y-m-d', strtotime('+15 days')),
            'notes' => (string)$this->input('notes', ''),
        ]);
        $this->superAuth->log('invoice.create', 'invoice', $id, ['number' => $invoiceNumber, 'total' => $total]);
        $this->session->flash('success', "Factura {$invoiceNumber} creada.");
        $this->redirect('/admin/invoices/' . $id);
    }

    public function show(array $params): void
    {
        $this->requireCan('invoices.view');
        $id = (int)$params['id'];
        $inv = $this->db->one(
            "SELECT i.*, t.name AS tenant_name, t.slug AS tenant_slug, t.support_email, t.billing_email, t.website
             FROM invoices i JOIN tenants t ON t.id = i.tenant_id WHERE i.id = ?",
            [$id]
        );
        if (!$inv) $this->redirect('/admin/invoices');
        $payments = $this->db->all('SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at DESC', [$id]);
        $this->render('admin/invoices/show', [
            'title' => $inv['invoice_number'],
            'pageHeading' => 'Factura ' . $inv['invoice_number'],
            'inv' => $inv,
            'payments' => $payments,
        ]);
    }

    public function markPaid(array $params): void
    {
        $this->requireCan('invoices.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $inv = $this->db->one('SELECT * FROM invoices WHERE id = ?', [$id]);
        if (!$inv) $this->redirect('/admin/invoices');
        $remaining = $inv['total'] - $inv['amount_paid'];

        $this->db->insert('payments', [
            'tenant_id' => $inv['tenant_id'],
            'invoice_id' => $id,
            'amount' => $remaining,
            'currency' => $inv['currency'],
            'method' => (string)$this->input('method', 'manual'),
            'reference' => (string)$this->input('reference', ''),
            'status' => 'completed',
            'notes' => (string)$this->input('notes', ''),
            'created_by' => $this->superAuth->id(),
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->update('invoices', [
            'status' => 'paid',
            'amount_paid' => $inv['total'],
            'paid_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);
        $this->superAuth->log('invoice.paid', 'invoice', $id);
        $this->session->flash('success', 'Factura marcada como pagada.');
        $this->redirect('/admin/invoices/' . $id);
    }

    public function delete(array $params): void
    {
        $this->requireCan('invoices.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('invoices', 'id = :id', ['id' => $id]);
        $this->superAuth->log('invoice.delete', 'invoice', $id);
        $this->session->flash('success', 'Factura eliminada.');
        $this->redirect('/admin/invoices');
    }
}
