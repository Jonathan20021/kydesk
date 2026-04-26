<?php
/**
 * Kydesk — Migración avanzada (V3)
 *  - Email verification + password reset tokens
 *  - Webhook delivery queue
 *  - Token IP allowlist + last_used precision
 *  - Saved requests in API Console
 *  - Activity stream (cross-resource events)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_ADV') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ DB connected\n\n";

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

// 1) Email verification & password reset tokens for developers
if (!tableExists($pdo, 'dev_email_tokens')) {
    $pdo->exec("CREATE TABLE dev_email_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        developer_id INT UNSIGNED NOT NULL,
        token CHAR(64) NOT NULL UNIQUE,
        purpose ENUM('verify_email','password_reset') NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY (developer_id), KEY (purpose), KEY (expires_at),
        CONSTRAINT fk_devtok_email_dev FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + dev_email_tokens\n";
}

// 2) Webhook delivery queue (events to deliver)
if (!tableExists($pdo, 'webhook_event_queue')) {
    $pdo->exec("CREATE TABLE webhook_event_queue (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        event VARCHAR(80) NOT NULL,
        payload_json MEDIUMTEXT NOT NULL,
        delivered TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pending (delivered, created_at),
        KEY (tenant_id), KEY (event)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + webhook_event_queue\n";
}

// 3) Token IP allowlist + custom scopes + expiration
$tokCols = [
    'allowed_ips' => 'VARCHAR(255) NULL',
    'rate_limit_override' => 'INT NULL',
    'description' => 'VARCHAR(255) NULL',
];
foreach ($tokCols as $c => $def) {
    if (!columnExists($pdo, 'dev_api_tokens', $c)) {
        $pdo->exec("ALTER TABLE dev_api_tokens ADD COLUMN $c $def");
        echo "  + dev_api_tokens.$c\n";
    }
}

// 4) Saved requests for API Console
if (!tableExists($pdo, 'dev_console_saved')) {
    $pdo->exec("CREATE TABLE dev_console_saved (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        developer_id INT UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL,
        method VARCHAR(10) NOT NULL,
        path VARCHAR(500) NOT NULL,
        body MEDIUMTEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY (developer_id),
        CONSTRAINT fk_consaved_dev FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + dev_console_saved\n";
}

// 5) Activity stream — generic event bus
if (!tableExists($pdo, 'activity_events')) {
    $pdo->exec("CREATE TABLE activity_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        event VARCHAR(80) NOT NULL,
        entity VARCHAR(50) NULL,
        entity_id INT UNSIGNED NULL,
        actor_user_id INT UNSIGNED NULL,
        payload_json MEDIUMTEXT NULL,
        created_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
        KEY (tenant_id), KEY idx_recent (tenant_id, created_at), KEY (event)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  + activity_events\n";
}

// 6) Settings additions
$settings = [
    'dev_portal_email_verification_required' => '0',
    'dev_portal_password_reset_ttl_minutes' => '60',
    'dev_portal_email_verification_ttl_minutes' => '1440',
];
$stmt = $pdo->prepare("INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES (?, ?)");
foreach ($settings as $k => $v) { $stmt->execute([$k, $v]); }
echo "  + saas_settings (3 keys)\n";

// 7) developers.email_verified_at
if (!columnExists($pdo, 'developers', 'email_verified_at')) {
    $pdo->exec("ALTER TABLE developers ADD COLUMN email_verified_at DATETIME NULL");
    echo "  + developers.email_verified_at\n";
}

echo "\n✓ MIGRATION COMPLETE\n";
