<?php
/**
 * Kydesk — Migración API v2 (mejoras avanzadas)
 *  - Tabla de idempotencia
 *  - Webhooks (compartido con dev_webhooks ya creada)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'KYDESK_API_V2') {
        http_response_code(403);
        exit('Forbidden — usa ?token=KYDESK_API_V2');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];

echo "→ Conectando a {$cfg['host']}:{$cfg['port']}/{$cfg['name']}…\n";
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
    $cfg['user'], $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
echo "✓ Conexión OK.\n\n";

function tableExists(PDO $pdo, string $t): bool {
    $s = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    return (bool)$pdo->query("SHOW TABLES LIKE '$s'")->fetch();
}

if (!tableExists($pdo, 'api_idempotency')) {
    echo "→ Creando api_idempotency…\n";
    $pdo->exec("CREATE TABLE api_idempotency (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        idem_key CHAR(64) NOT NULL,
        tenant_id INT UNSIGNED NULL,
        developer_id INT UNSIGNED NULL,
        method VARCHAR(10) NOT NULL,
        path VARCHAR(255) NOT NULL,
        status_code SMALLINT UNSIGNED NOT NULL,
        response_json MEDIUMTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_idem (idem_key, tenant_id, developer_id),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✓ Created\n";
}

if (!tableExists($pdo, 'webhook_deliveries')) {
    echo "→ Creando webhook_deliveries…\n";
    $pdo->exec("CREATE TABLE webhook_deliveries (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        webhook_id INT UNSIGNED NOT NULL,
        event VARCHAR(100) NOT NULL,
        payload_json MEDIUMTEXT NOT NULL,
        status_code SMALLINT UNSIGNED NULL,
        response_excerpt VARCHAR(500) NULL,
        attempt SMALLINT UNSIGNED DEFAULT 1,
        delivered_at DATETIME NULL,
        next_retry_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY (webhook_id), KEY (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✓ Created\n";
}

echo "\n══════════════════════════════════════\n";
echo "✓ MIGRACIÓN API V2 COMPLETA\n";
echo "══════════════════════════════════════\n";
