<?php
namespace App\Controllers;

use App\Core\BankInfo;
use App\Core\Controller;
use App\Core\DevMailer;

/**
 * Billing dentro del panel del tenant (cliente del helpdesk).
 * Permite ver facturas, datos bancarios y subir comprobantes de pago.
 */
class BillingController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $tid = $tenant->id;

        $bank = BankInfo::all();

        // Suscripción activa
        $sub = null;
        try {
            $sub = $this->db->one(
                "SELECT s.*, p.name AS plan_name, p.color AS plan_color
                 FROM subscriptions s LEFT JOIN plans p ON p.id = s.plan_id
                 WHERE s.tenant_id = ? ORDER BY s.id DESC LIMIT 1",
                [$tid]
            );
        } catch (\Throwable $e) {}

        // Facturas
        $invoices = [];
        try {
            $invoices = $this->db->all('SELECT * FROM invoices WHERE tenant_id=? ORDER BY id DESC LIMIT 25', [$tid]);
        } catch (\Throwable $e) {}

        $payments = [];
        try {
            $payments = $this->db->all('SELECT * FROM payments WHERE tenant_id=? ORDER BY id DESC LIMIT 25', [$tid]);
        } catch (\Throwable $e) {}

        $proofs = [];
        try {
            $proofs = $this->db->all(
                "SELECT pp.*, i.invoice_number FROM payment_proofs pp
                 LEFT JOIN invoices i ON i.id = pp.invoice_id
                 WHERE pp.tenant_id = ? ORDER BY pp.id DESC LIMIT 25",
                [$tid]
            );
        } catch (\Throwable $e) {}

        $totalPending = 0;
        foreach ($invoices as $i) {
            if (in_array($i['status'], ['pending','overdue','partial'], true)) {
                $totalPending += (float)$i['total'] - (float)$i['amount_paid'];
            }
        }

        $this->render('billing/index', [
            'title' => 'Facturación',
            'bank' => $bank,
            'sub' => $sub,
            'invoices' => $invoices,
            'payments' => $payments,
            'proofs' => $proofs,
            'totalPending' => $totalPending,
        ]);
    }

    public function paymentInfo(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $tid = $tenant->id;

        $bank = BankInfo::all();
        $pendingInvoices = [];
        try {
            $pendingInvoices = $this->db->all(
                "SELECT * FROM invoices WHERE tenant_id=? AND status IN ('pending','overdue','partial') ORDER BY due_date ASC",
                [$tid]
            );
        } catch (\Throwable $e) {}

        $proofs = [];
        try {
            $proofs = $this->db->all(
                "SELECT pp.*, i.invoice_number FROM payment_proofs pp
                 LEFT JOIN invoices i ON i.id = pp.invoice_id
                 WHERE pp.tenant_id = ? ORDER BY pp.id DESC LIMIT 25",
                [$tid]
            );
        } catch (\Throwable $e) {}

        $this->render('billing/payment-info', [
            'title' => 'Cómo pagar',
            'bank' => $bank,
            'pendingInvoices' => $pendingInvoices,
            'proofs' => $proofs,
        ]);
    }

    public function uploadProof(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $this->validateCsrf();
        $tid = $tenant->id;
        $user = $this->auth->user();

        $invoiceId = (int)$this->input('invoice_id', 0) ?: null;
        $amount = (float)$this->input('amount', 0);
        if ($amount <= 0) {
            $this->session->flash('error', 'Indica el monto depositado.');
            $this->redirect('/t/' . $tenant->slug . '/billing/payment-info');
        }
        $currency = (string)$this->input('currency', 'DOP');
        $reference = trim((string)$this->input('reference', ''));
        $notes = trim((string)$this->input('notes', ''));
        $transferDate = (string)$this->input('transfer_date', date('Y-m-d'));

        if ($invoiceId) {
            $inv = $this->db->one('SELECT id FROM invoices WHERE id=? AND tenant_id=?', [$invoiceId, $tid]);
            if (!$inv) {
                $this->session->flash('error', 'Factura no válida.');
                $this->redirect('/t/' . $tenant->slug . '/billing/payment-info');
            }
        }

        [$filePath, $fileMime, $fileSize] = $this->handleUpload($tenant->slug);

        $maxMb = (int)BankInfo::get('payment_max_file_mb', '10');
        if ($fileSize > $maxMb * 1024 * 1024) {
            $this->session->flash('error', "El archivo excede el límite de {$maxMb}MB.");
            $this->redirect('/t/' . $tenant->slug . '/billing/payment-info');
        }

        $proofId = $this->db->insert('payment_proofs', [
            'proof_type' => 'tenant',
            'tenant_id' => $tid,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'currency' => $currency,
            'transfer_date' => $transferDate ?: null,
            'reference' => $reference ?: null,
            'bank_used' => (string)$this->input('bank_used', '') ?: null,
            'notes' => $notes ?: null,
            'file_path' => $filePath,
            'file_mime' => $fileMime,
            'file_size' => $fileSize,
            'status' => 'pending',
            'submitter_email' => (string)$user['email'],
            'submitter_name' => (string)$user['name'],
        ]);

        $reviewUrl = rtrim($this->app->config['app']['url'], '/') . '/admin/payment-proofs/' . $proofId;
        $absoluteFilePath = $filePath ? BASE_PATH . $filePath : null;
        DevMailer::paymentProofSubmitted(
            $proofId,
            (string)$user['name'],
            (string)$user['email'],
            'Helpdesk · ' . $tenant->name . ($invoiceId ? " · factura #$invoiceId" : ''),
            $amount,
            $currency,
            $reference,
            $absoluteFilePath,
            $fileMime,
            $reviewUrl
        );

        $this->session->flash('success', '✓ Comprobante recibido. Te avisaremos por email cuando se valide (24-48h).');
        $this->redirect('/t/' . $tenant->slug . '/billing/payment-info');
    }

    protected function handleUpload(string $slug): array
    {
        if (empty($_FILES['proof_file']) || ($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, null, 0];
        }
        $f = $_FILES['proof_file'];
        $allowed = ['image/jpeg','image/png','image/webp','application/pdf','image/gif'];
        $mime = mime_content_type($f['tmp_name']) ?: ($f['type'] ?? 'application/octet-stream');
        if (!in_array($mime, $allowed, true)) {
            $this->session->flash('error', 'Solo se aceptan imágenes (JPG/PNG/WebP/GIF) o PDF.');
            $this->redirect('/t/' . $slug . '/billing/payment-info');
        }
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'application/pdf' => 'pdf'][$mime];
        $name = 'pp_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $relDir = '/public/uploads/payment_proofs';
        $absDir = BASE_PATH . $relDir;
        if (!is_dir($absDir)) @mkdir($absDir, 0755, true);
        $absPath = $absDir . '/' . $name;
        if (!move_uploaded_file($f['tmp_name'], $absPath)) {
            return [null, null, 0];
        }
        return [$relDir . '/' . $name, $mime, (int)$f['size']];
    }
}
