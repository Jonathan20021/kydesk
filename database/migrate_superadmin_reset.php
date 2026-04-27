<?php
/**
 * Add password reset support to super_admins table.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_SA_RESET') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function colExists(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

if (!colExists($pdo, 'super_admins', 'reset_token')) {
    $pdo->exec("ALTER TABLE super_admins ADD COLUMN reset_token VARCHAR(64) NULL AFTER notes, ADD INDEX idx_reset_token (reset_token)");
    echo "  + super_admins.reset_token\n";
} else echo "  • reset_token ya existe\n";

if (!colExists($pdo, 'super_admins', 'reset_expires_at')) {
    $pdo->exec("ALTER TABLE super_admins ADD COLUMN reset_expires_at DATETIME NULL AFTER reset_token");
    echo "  + super_admins.reset_expires_at\n";
} else echo "  • reset_expires_at ya existe\n";

if (!colExists($pdo, 'super_admins', 'reset_requested_ip')) {
    $pdo->exec("ALTER TABLE super_admins ADD COLUMN reset_requested_ip VARCHAR(60) NULL AFTER reset_expires_at");
    echo "  + super_admins.reset_requested_ip\n";
} else echo "  • reset_requested_ip ya existe\n";

echo "\n✓ Migración completa.\n";
