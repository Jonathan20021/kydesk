<?php
namespace App\Controllers\Admin;

use App\Core\BankInfo;
use App\Core\DevMailer;

class PaymentProofController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $status = (string)$this->input('status', '');
        $type = (string)$this->input('type', '');
        $where = ['1=1']; $args = [];
        if ($status !== '') { $where[] = 'pp.status = ?'; $args[] = $status; }
        if ($type !== '')   { $where[] = 'pp.proof_type = ?'; $args[] = $type; }
        $whereSql = implode(' AND ', $where);

        $rows = $this->db->all(
            "SELECT pp.*,
                    t.name AS tenant_name, t.slug AS tenant_slug,
                    d.name AS dev_name, d.email AS dev_email,
                    i.invoice_number AS tenant_invoice_number,
                    di.invoice_number AS dev_invoice_number
             FROM payment_proofs pp
             LEFT JOIN tenants t ON t.id = pp.tenant_id
             LEFT JOIN developers d ON d.id = pp.developer_id
             LEFT JOIN invoices i ON i.id = pp.invoice_id
             LEFT JOIN dev_invoices di ON di.id = pp.dev_invoice_id
             WHERE $whereSql
             ORDER BY pp.id DESC LIMIT 200",
            $args
        );

        $stats = [
            'pending' => (int)$this->db->val("SELECT COUNT(*) FROM payment_proofs WHERE status='pending'"),
            'approved' => (int)$this->db->val("SELECT COUNT(*) FROM payment_proofs WHERE status='approved'"),
            'rejected' => (int)$this->db->val("SELECT COUNT(*) FROM payment_proofs WHERE status='rejected'"),
            'pending_amount' => (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM payment_proofs WHERE status='pending'"),
        ];

        $this->render('admin/payment_proofs/index', [
            'title' => 'Comprobantes de pago',
            'pageHeading' => 'Comprobantes de pago',
            'rows' => $rows,
            'stats' => $stats,
            'status' => $status,
            'type' => $type,
        ]);
    }

    public function show(array $params): void
    {
        $this->requireSuperAuth();
        $id = (int)$params['id'];
        $proof = $this->db->one(
            "SELECT pp.*,
                    t.name AS tenant_name, t.slug AS tenant_slug,
                    d.name AS dev_name, d.email AS dev_email, d.id AS d_id,
                    i.invoice_number AS tenant_invoice_number, i.total AS tenant_invoice_total,
                    di.invoice_number AS dev_invoice_number, di.total AS dev_invoice_total
             FROM payment_proofs pp
             LEFT JOIN tenants t ON t.id = pp.tenant_id
             LEFT JOIN developers d ON d.id = pp.developer_id
             LEFT JOIN invoices i ON i.id = pp.invoice_id
             LEFT JOIN dev_invoices di ON di.id = pp.dev_invoice_id
             WHERE pp.id = ?",
            [$id]
        );
        if (!$proof) $this->redirect('/admin/payment-proofs');
        $this->render('admin/payment_proofs/show', [
            'title' => 'Comprobante #' . $id,
            'pageHeading' => 'Comprobante #' . $id,
            'proof' => $proof,
        ]);
    }

    public function approve(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $notes = (string)$this->input('review_notes', '');
        $proof = $this->db->one('SELECT * FROM payment_proofs WHERE id=?', [$id]);
        if (!$proof) $this->redirect('/admin/payment-proofs');
        if ($proof['status'] === 'approved') {
            $this->session->flash('info', 'Este comprobante ya estaba aprobado.');
            $this->redirect('/admin/payment-proofs/' . $id);
        }

        $this->db->update('payment_proofs', [
            'status' => 'approved',
            'reviewed_by' => $this->superAuth->id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => $notes ?: null,
        ], 'id=?', [$id]);

        // Si está atado a una factura, registrar pago + marcar pagada
        $context = '';
        if ($proof['proof_type'] === 'developer' && $proof['dev_invoice_id']) {
            $invId = (int)$proof['dev_invoice_id'];
            $inv = $this->db->one('SELECT * FROM dev_invoices WHERE id=?', [$invId]);
            if ($inv) {
                $this->db->insert('dev_payments', [
                    'developer_id' => (int)$proof['developer_id'],
                    'invoice_id' => $invId,
                    'amount' => $proof['amount'],
                    'currency' => $proof['currency'],
                    'method' => 'transfer',
                    'reference' => $proof['reference'],
                    'status' => 'completed',
                    'created_by' => $this->superAuth->id(),
                    'paid_at' => date('Y-m-d H:i:s'),
                    'notes' => "Auto-registrado desde comprobante #$id",
                ]);
                $totalPaid = (float)$inv['amount_paid'] + (float)$proof['amount'];
                $newStatus = $totalPaid >= (float)$inv['total'] - 0.01 ? 'paid' : 'partial';
                $this->db->update('dev_invoices', [
                    'amount_paid' => $totalPaid,
                    'status' => $newStatus,
                    'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null,
                ], 'id=?', [$invId]);
                $context = 'factura ' . $inv['invoice_number'];
            }
        } elseif ($proof['proof_type'] === 'tenant' && $proof['invoice_id']) {
            $invId = (int)$proof['invoice_id'];
            $inv = $this->db->one('SELECT * FROM invoices WHERE id=?', [$invId]);
            if ($inv) {
                $this->db->insert('payments', [
                    'tenant_id' => (int)$proof['tenant_id'],
                    'invoice_id' => $invId,
                    'amount' => $proof['amount'],
                    'currency' => $proof['currency'],
                    'method' => 'transfer',
                    'reference' => $proof['reference'],
                    'status' => 'completed',
                    'created_by' => $this->superAuth->id(),
                    'paid_at' => date('Y-m-d H:i:s'),
                    'notes' => "Auto-registrado desde comprobante #$id",
                ]);
                $totalPaid = (float)$inv['amount_paid'] + (float)$proof['amount'];
                $newStatus = $totalPaid >= (float)$inv['total'] - 0.01 ? 'paid' : 'partial';
                $this->db->update('invoices', [
                    'amount_paid' => $totalPaid,
                    'status' => $newStatus,
                    'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null,
                ], 'id=?', [$invId]);
                $context = 'factura ' . $inv['invoice_number'];
            }
        }

        // Email al submitter
        if ($proof['proof_type'] === 'developer' && $proof['developer_id']) {
            $dev = $this->db->one('SELECT email, name FROM developers WHERE id=?', [(int)$proof['developer_id']]);
            if ($dev) {
                DevMailer::paymentProofApproved((string)$dev['email'], (string)$dev['name'], (float)$proof['amount'], (string)$proof['currency'], $context ?: 'depósito directo');
            }
        } else {
            // Tenant: email al submitter directamente
            if (!empty($proof['submitter_email'])) {
                DevMailer::paymentProofApproved((string)$proof['submitter_email'], (string)($proof['submitter_name'] ?: 'Cliente'), (float)$proof['amount'], (string)$proof['currency'], $context ?: 'tu depósito', null);
            }
        }

        $this->superAuth->log('payment_proof.approve', 'payment_proof', $id);
        $this->session->flash('success', '✓ Comprobante aprobado, pago registrado y email enviado.');
        $this->redirect('/admin/payment-proofs/' . $id);
    }

    public function reject(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $reason = trim((string)$this->input('review_notes', ''));
        if ($reason === '') {
            $this->session->flash('error', 'Indica el motivo del rechazo.');
            $this->redirect('/admin/payment-proofs/' . $id);
        }
        $proof = $this->db->one('SELECT * FROM payment_proofs WHERE id=?', [$id]);
        if (!$proof) $this->redirect('/admin/payment-proofs');

        $this->db->update('payment_proofs', [
            'status' => 'rejected',
            'reviewed_by' => $this->superAuth->id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => $reason,
        ], 'id=?', [$id]);

        // Email al submitter
        $context = $proof['proof_type'] === 'developer' ? 'tu factura developer' : 'tu factura';
        if ($proof['proof_type'] === 'developer' && $proof['developer_id']) {
            $dev = $this->db->one('SELECT email, name FROM developers WHERE id=?', [(int)$proof['developer_id']]);
            if ($dev) DevMailer::paymentProofRejected((string)$dev['email'], (string)$dev['name'], $reason, $context);
        } else {
            if (!empty($proof['submitter_email'])) {
                DevMailer::paymentProofRejected((string)$proof['submitter_email'], (string)($proof['submitter_name'] ?: 'Cliente'), $reason, $context, null);
            }
        }

        $this->superAuth->log('payment_proof.reject', 'payment_proof', $id, ['reason' => $reason]);
        $this->session->flash('success', 'Comprobante rechazado y email enviado.');
        $this->redirect('/admin/payment-proofs/' . $id);
    }

    public function downloadFile(array $params): void
    {
        $this->requireSuperAuth();
        $id = (int)$params['id'];
        $proof = $this->db->one('SELECT file_path, file_mime FROM payment_proofs WHERE id=?', [$id]);
        if (!$proof || empty($proof['file_path'])) {
            http_response_code(404);
            echo 'No file';
            exit;
        }
        $abs = BASE_PATH . $proof['file_path'];
        if (!is_file($abs)) {
            http_response_code(404);
            echo 'File missing';
            exit;
        }
        header('Content-Type: ' . ($proof['file_mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . basename($abs) . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }

    // ─── Bank settings ───────────────────────────────────────────────

    public function bankSettings(): void
    {
        $this->requireSuperAuth();
        $bank = BankInfo::all();
        $this->render('admin/payment_proofs/bank_settings', [
            'title' => 'Datos bancarios',
            'pageHeading' => 'Datos bancarios para depósitos',
            'bank' => $bank,
        ]);
    }

    public function updateBankSettings(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $keys = ['bank_name','bank_account_type','bank_account_number','bank_id_number','bank_account_holder','bank_currency','billing_approval_email','payment_proof_required','payment_max_file_mb'];
        foreach ($keys as $k) {
            $v = (string)$this->input($k, '');
            $exists = $this->db->val('SELECT 1 FROM saas_settings WHERE `key`=?', [$k]);
            if ($exists) {
                $this->db->update('saas_settings', ['value' => $v], '`key`=?', [$k]);
            } else {
                $this->db->insert('saas_settings', ['key' => $k, 'value' => $v]);
            }
        }
        $this->superAuth->log('bank_settings.update');
        $this->session->flash('success', '✓ Datos bancarios actualizados.');
        $this->redirect('/admin/bank-settings');
    }
}
