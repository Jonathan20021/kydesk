<?php
/**
 * Migración: Portal de Empresas
 *  - Agrega flag `is_company_manager` a portal_users.
 *
 * Uso:
 *   - CLI:   php database/migrate_company_portal.php
 *   - Web:   /database/migrate_company_portal.php?token=KYDESK_COMPANY
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_COMPANY') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];

try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
        $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\Throwable $e) {
    echo "❌ No pude conectarme a la BD: " . $e->getMessage() . "\n";
    exit(1);
}
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function cex(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function idx(PDO $p, string $t, string $name): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $st2 = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    return (bool)$p->query("SHOW INDEX FROM `$st` WHERE Key_name = '$st2'")->fetch();
}

if (!cex($pdo, 'portal_users', 'is_company_manager')) {
    $pdo->exec("ALTER TABLE portal_users
        ADD COLUMN is_company_manager TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
    echo "  + portal_users.is_company_manager\n";
} else {
    echo "  • portal_users.is_company_manager ya existe\n";
}

if (!idx($pdo, 'portal_users', 'idx_pu_manager')) {
    $pdo->exec("ALTER TABLE portal_users
        ADD INDEX idx_pu_manager (tenant_id, company_id, is_company_manager)");
    echo "  + idx_pu_manager (tenant_id, company_id, is_company_manager)\n";
} else {
    echo "  • idx_pu_manager ya existe\n";
}

echo "\n✓ Migración completada.\n";
