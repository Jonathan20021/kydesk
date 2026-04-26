<?php
namespace App\Controllers\Developer;

use App\Core\BankInfo;
use App\Core\DevMailer;

class PaymentProofController extends DeveloperController
{
    public function paymentInfo(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $bank = BankInfo::all();

        // Pending invoices del developer (las que se pueden pagar)
        $pendingInvoices = $this->db->all(
            "SELECT i.* FROM dev_invoices i
             WHERE i.developer_id=? AND i.status IN ('pending','overdue','partial')
             ORDER BY i.due_date ASC",
            [$devId]
        );

        // Comprobantes ya enviados
        $proofs = $this->db->all(
            "SELECT p.*, i.invoice_number FROM payment_proofs p
             LEFT JOIN dev_invoices i ON i.id = p.dev_invoice_id
             WHERE p.developer_id = ? ORDER BY p.id DESC LIMIT 25",
            [$devId]
        );

        $this->render('developers/billing/payment-info', [
            'title' => 'Cómo pagar',
            'pageHeading' => 'Información de pago',
            'bank' => $bank,
            'pendingInvoices' => $pendingInvoices,
            'proofs' => $proofs,
        ]);
    }

    public function uploadProof(): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $dev = $this->devAuth->developer();

        $invoiceId = (int)$this->input('dev_invoice_id', 0) ?: null;
        $amount = (float)$this->input('amount', 0);
        $currency = (string)$this->input('currency', 'DOP');
        $reference = trim((string)$this->input('reference', ''));
        $notes = trim((string)$this->input('notes', ''));
        $transferDate = (string)$this->input('transfer_date', date('Y-m-d'));

        if ($amount <= 0) {
            $this->session->flash('error', 'Indica el monto depositado.');
            $this->redirect('/developers/billing/payment-info');
        }

        // Validar la factura pertenece al developer
        if ($invoiceId) {
            $inv = $this->db->one('SELECT id FROM dev_invoices WHERE id=? AND developer_id=?', [$invoiceId, $devId]);
            if (!$inv) {
                $this->session->flash('error', 'Factura no válida.');
                $this->redirect('/developers/billing/payment-info');
            }
        }

        // Manejar el archivo
        [$filePath, $fileMime, $fileSize] = $this->handleUpload();

        $maxMb = (int)BankInfo::get('payment_max_file_mb', '10');
        if ($fileSize > $maxMb * 1024 * 1024) {
            $this->session->flash('error', "El archivo excede el límite de {$maxMb}MB.");
            $this->redirect('/developers/billing/payment-info');
        }

        $proofId = $this->db->insert('payment_proofs', [
            'proof_type' => 'developer',
            'developer_id' => $devId,
            'dev_invoice_id' => $invoiceId,
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
            'submitter_email' => (string)$dev['email'],
            'submitter_name' => (string)$dev['name'],
        ]);

        // Email a jonathansandoval@kyrosrd.com con el comprobante adjunto
        $reviewUrl = rtrim($this->app->config['app']['url'], '/') . '/admin/payment-proofs/' . $proofId;
        $absoluteFilePath = $filePath ? BASE_PATH . $filePath : null;
        DevMailer::paymentProofSubmitted(
            $proofId,
            (string)$dev['name'],
            (string)$dev['email'],
            'Developer Portal · ' . ($invoiceId ? "factura #$invoiceId" : 'depósito directo'),
            $amount,
            $currency,
            $reference,
            $absoluteFilePath,
            $fileMime,
            $reviewUrl
        );

        $this->devAuth->log('payment_proof.submit', 'payment_proof', $proofId);
        $this->session->flash('success', '✓ Comprobante recibido. Te avisaremos por email cuando se valide (24-48h).');
        $this->redirect('/developers/billing/payment-info');
    }

    /**
     * Handle file upload — returns [filePath, mime, size]
     */
    protected function handleUpload(): array
    {
        if (empty($_FILES['proof_file']) || ($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, null, 0];
        }
        $f = $_FILES['proof_file'];
        $allowed = ['image/jpeg','image/png','image/webp','application/pdf','image/gif'];
        $mime = mime_content_type($f['tmp_name']) ?: ($f['type'] ?? 'application/octet-stream');
        if (!in_array($mime, $allowed, true)) {
            $this->session->flash('error', 'Solo se aceptan imágenes (JPG/PNG/WebP/GIF) o PDF.');
            $this->redirect('/developers/billing/payment-info');
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
