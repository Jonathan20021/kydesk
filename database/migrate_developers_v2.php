<?php
/**
 * Kydesk — Migración Developer Portal V2
 *
 * Añade overrides de cuota por developer, log granular de requests,
 * tabla de webhooks y nuevos settings.
 *
 * Uso:
 *   CLI:        php database/migrate_developers_v2.php
 *   Navegador:  http://tu-dominio/kyros-helpdesk/database/migrate_developers_v2.php?token=KYDESK_DEV_V2
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'KYDESK_DEV_V2') {
        http_response_code(403);
        exit('Forbidden — usa ?token=KYDESK_DEV_V2');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];

echo "→ Conectando a {$cfg['host']}:{$cfg['port']}/{$cfg['name']}…\n";
try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
        $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "✓ Conexión OK.\n\n";
} catch (PDOException $e) {
    echo "✗ ERROR: {$e->getMessage()}\n"; exit(1);
}

function tableExists(PDO $pdo, string $t): bool {
    $s = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    return (bool)$pdo->query("SHOW TABLES LIKE '$s'")->fetch();
}
function columnExists(PDO $pdo, string $t, string $c): bool {
    if (!tableExists($pdo, $t)) return false;
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

// 1) Columnas en developers
echo "→ Añadiendo overrides en developers…\n";
$devCols = [
    'custom_max_apps' => 'INT NULL',
    'custom_max_requests_month' => 'INT NULL',
    'custom_max_tokens_per_app' => 'INT NULL',
    'custom_rate_limit_per_min' => 'INT NULL',
    'quota_alerts_enabled' => 'TINYINT(1) DEFAULT 1',
];
foreach ($devCols as $c => $def) {
    if (!columnExists($pdo, 'developers', $c)) {
        try { $pdo->exec("ALTER TABLE developers ADD COLUMN $c $def"); echo "  + developers.$c\n"; }
        catch (\Throwable $e) { echo "  ! developers.$c: " . substr($e->getMessage(), 0, 120) . "\n"; }
    }
}

// 2) Tabla dev_api_request_log
if (!tableExists($pdo, 'dev_api_request_log')) {
    echo "→ Creando dev_api_request_log…\n";
    $pdo->exec("CREATE TABLE dev_api_request_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        developer_id INT UNSIGNED NOT NULL,
        app_id INT UNSIGNED NULL,
        token_id INT UNSIGNED NULL,
        method VARCHAR(10) NOT NULL,
        path VARCHAR(255) NOT NULL,
        status_code SMALLINT UNSIGNED DEFAULT 0,
        duration_ms INT UNSIGNED DEFAULT 0,
        ip VARCHAR(45) NULL,
        ua VARCHAR(255) NULL,
        created_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
        KEY idx_dev_recent (developer_id, created_at),
        KEY idx_app_recent (app_id, created_at),
        KEY (token_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✓ creada\n";
}

// 3) Tabla dev_webhooks
if (!tableExists($pdo, 'dev_webhooks')) {
    echo "→ Creando dev_webhooks…\n";
    $pdo->exec("CREATE TABLE dev_webhooks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        developer_id INT UNSIGNED NOT NULL,
        app_id INT UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL,
        url VARCHAR(500) NOT NULL,
        secret VARCHAR(128) NULL,
        events VARCHAR(500) NOT NULL DEFAULT '*',
        is_active TINYINT(1) DEFAULT 1,
        last_triggered_at DATETIME NULL,
        last_status_code SMALLINT UNSIGNED NULL,
        failure_count INT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY (developer_id), KEY (app_id),
        CONSTRAINT fk_devwh_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
        CONSTRAINT fk_devwh_app FOREIGN KEY (app_id) REFERENCES dev_apps(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  ✓ creada\n";
}

// 4) Nuevos saas_settings
echo "→ Sembrando settings V2…\n";
$settings = [
    'dev_portal_enforce_quota' => '1',
    'dev_portal_enforce_rate_limit' => '1',
    'dev_portal_block_on_overage' => '0',
    'dev_portal_alert_at_pct' => '80',
    'dev_portal_company_label' => 'Kydesk Developers',
];
$stmt = $pdo->prepare("INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES (?, ?)");
foreach ($settings as $k => $v) {
    $stmt->execute([$k, $v]);
    echo "  + $k\n";
}

echo "\n══════════════════════════════════════\n";
echo "✓ MIGRACIÓN V2 COMPLETA\n";
echo "══════════════════════════════════════\n";
