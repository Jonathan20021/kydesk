<?php
/**
 * Conecta payments + dev_payments con payment_proofs.
 * Añade payment_proof_id a payments y dev_payments para trazabilidad bidireccional.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_PAYLINK') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

function columnExists(PDO $pdo, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

if (!columnExists($pdo, 'payments', 'payment_proof_id')) {
    $pdo->exec("ALTER TABLE payments ADD COLUMN payment_proof_id INT UNSIGNED NULL AFTER invoice_id, ADD INDEX idx_proof (payment_proof_id)");
    echo "  + payments.payment_proof_id\n";
}
if (!columnExists($pdo, 'dev_payments', 'payment_proof_id')) {
    $pdo->exec("ALTER TABLE dev_payments ADD COLUMN payment_proof_id INT UNSIGNED NULL AFTER invoice_id, ADD INDEX idx_proof (payment_proof_id)");
    echo "  + dev_payments.payment_proof_id\n";
}

// Add reverse lookup column on payment_proofs (which payment row it generated)
if (!columnExists($pdo, 'payment_proofs', 'payment_id')) {
    $pdo->exec("ALTER TABLE payment_proofs ADD COLUMN payment_id INT UNSIGNED NULL AFTER status, ADD COLUMN dev_payment_id INT UNSIGNED NULL AFTER payment_id");
    echo "  + payment_proofs.payment_id + dev_payment_id\n";
}

echo "\n✓ Done\n";
