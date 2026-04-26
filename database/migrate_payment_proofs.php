<?php
/**
 * Migration: payment_proofs (comprobantes de pago manual subidos por clientes/devs)
 * + saas_settings bancarios (Banco Popular Dominicana)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_PAYPROOFS') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

function tableExists(PDO $pdo, string $t): bool {
    $s = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    return (bool)$pdo->query("SHOW TABLES LIKE '$s'")->fetch();
}

if (!tableExists($pdo, 'payment_proofs')) {
    $pdo->exec("CREATE TABLE payment_proofs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        proof_type ENUM('tenant','developer') NOT NULL,
        tenant_id INT UNSIGNED NULL,
        developer_id INT UNSIGNED NULL,
        invoice_id INT UNSIGNED NULL,
        dev_invoice_id INT UNSIGNED NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'DOP',
        transfer_date DATE NULL,
        reference VARCHAR(150) NULL,
        bank_used VARCHAR(120) NULL,
        notes TEXT NULL,
        file_path VARCHAR(500) NULL,
        file_mime VARCHAR(120) NULL,
        file_size INT UNSIGNED DEFAULT 0,
        status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
        reviewed_by INT UNSIGNED NULL,
        reviewed_at DATETIME NULL,
        review_notes VARCHAR(500) NULL,
        submitter_email VARCHAR(150) NULL,
        submitter_name VARCHAR(150) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY (status), KEY (tenant_id), KEY (developer_id), KEY (invoice_id), KEY (dev_invoice_id),
        KEY idx_pending (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + payment_proofs\n";
}

// Saas settings — datos bancarios + email aprobador
$settings = [
    'bank_name' => 'Banco Popular Dominicana',
    'bank_account_type' => 'Corriente',
    'bank_account_number' => '849693106',
    'bank_id_number' => '402-3417388-4',
    'bank_account_holder' => 'Kyros RD',
    'bank_currency' => 'DOP',
    'billing_approval_email' => 'jonathansandoval@kyrosrd.com',
    'payment_proof_required' => '1',
    'payment_max_file_mb' => '10',
];
$stmt = $pdo->prepare("INSERT INTO saas_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = IF(`value` = '', VALUES(`value`), `value`)");
foreach ($settings as $k => $v) {
    $stmt->execute([$k, $v]);
    echo "  + saas_settings.$k = $v\n";
}

// Crear directorio de uploads si no existe (para storage local de comprobantes)
$dir = BASE_PATH . '/public/uploads/payment_proofs';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    echo "  + dir public/uploads/payment_proofs/\n";
    file_put_contents($dir . '/.htaccess', "Order Deny,Allow\nDeny from all\n");
    echo "  + .htaccess deny (acceso solo via controlador autenticado)\n";
}

echo "\n✓ MIGRATION COMPLETE\n";
