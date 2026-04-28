<?php
/**
 * Migración: Service Catalog público + flujo de aprobación de tickets.
 *
 *  - service_catalog_items.is_public TINYINT(1) DEFAULT 0
 *  - tickets.catalog_item_id INT UNSIGNED NULL
 *  - tickets.approval_status ENUM('pending','approved','rejected') NULL
 *  - tickets.approval_user_id INT UNSIGNED NULL
 *  - tickets.approval_decided_at DATETIME NULL
 *
 * Uso:
 *   - CLI:   php database/migrate_service_catalog.php
 *   - Web:   /database/migrate_service_catalog.php?token=KYDESK_CATALOG
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CATALOG') { http_response_code(403); exit('forbidden'); }
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

function colExists(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function add(PDO $p, string $t, string $c, string $sql): void {
    if (!colExists($p, $t, $c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n";
}

add($pdo, 'service_catalog_items', 'is_public',
    "ALTER TABLE service_catalog_items ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");

add($pdo, 'tickets', 'catalog_item_id',
    "ALTER TABLE tickets ADD COLUMN catalog_item_id INT UNSIGNED NULL AFTER asset_id, ADD INDEX idx_tk_catalog (catalog_item_id)");

add($pdo, 'tickets', 'approval_status',
    "ALTER TABLE tickets ADD COLUMN approval_status ENUM('pending','approved','rejected') NULL AFTER status");

add($pdo, 'tickets', 'approval_user_id',
    "ALTER TABLE tickets ADD COLUMN approval_user_id INT UNSIGNED NULL AFTER approval_status");

add($pdo, 'tickets', 'approval_decided_at',
    "ALTER TABLE tickets ADD COLUMN approval_decided_at DATETIME NULL AFTER approval_user_id");

echo "\n✓ Migración completada.\n";
