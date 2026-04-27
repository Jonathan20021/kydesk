<?php
/**
 * Integraciones (PRO+ excepto starter) — módulo de marketplace de integraciones
 *
 *  · Tabla `integrations` — instalaciones de integraciones por tenant
 *  · Tabla `integration_logs` — log de entregas (éxito/fallo)
 *  · Permisos globales (integrations.*)
 *  · Settings por plan en saas_settings: integrations_max_<plan> y integrations_providers_<plan>
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_INTEG') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function tableExists(PDO $pdo, string $t): bool {
    return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
}
function columnExists(PDO $pdo, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

/* 1) integrations */
if (!tableExists($pdo, 'integrations')) {
    $pdo->exec("CREATE TABLE integrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        provider VARCHAR(40) NOT NULL,
        name VARCHAR(120) NOT NULL,
        config TEXT NULL,
        events TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_event_at DATETIME NULL,
        last_status VARCHAR(20) NULL,
        success_count INT UNSIGNED NOT NULL DEFAULT 0,
        error_count INT UNSIGNED NOT NULL DEFAULT 0,
        notes TEXT NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_provider (provider),
        KEY idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + integrations\n";
} else {
    echo "  • integrations already exists\n";
}

/* 2) integration_logs */
if (!tableExists($pdo, 'integration_logs')) {
    $pdo->exec("CREATE TABLE integration_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        integration_id INT UNSIGNED NOT NULL,
        tenant_id INT UNSIGNED NOT NULL,
        event_type VARCHAR(60) NOT NULL,
        status VARCHAR(20) NOT NULL,
        status_code INT NULL,
        latency_ms INT NULL,
        response_excerpt VARCHAR(500) NULL,
        error_message VARCHAR(500) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_integration (integration_id, created_at),
        KEY idx_tenant (tenant_id, created_at),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + integration_logs\n";
} else {
    echo "  • integration_logs already exists\n";
}

/* 3) Permisos */
$permsToAdd = [
    'integrations.view'    => ['integrations', 'Ver integraciones'],
    'integrations.install' => ['integrations', 'Instalar integraciones'],
    'integrations.edit'    => ['integrations', 'Editar integraciones'],
    'integrations.delete'  => ['integrations', 'Eliminar integraciones'],
    'integrations.test'    => ['integrations', 'Probar integraciones'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
$added = 0;
foreach ($permsToAdd as $slug => [$mod, $label]) {
    $stmt->execute([$slug, $mod, $label]);
    if ($stmt->rowCount() > 0) $added++;
}
echo "  + permisos sembrados ($added nuevos)\n";

/* 4) Otorgar permisos al rol owner de cada tenant */
$owners = $pdo->query("SELECT id FROM roles WHERE slug='owner'")->fetchAll(PDO::FETCH_COLUMN);
$permIds = $pdo->query("SELECT id FROM permissions WHERE module='integrations'")->fetchAll(PDO::FETCH_COLUMN);
$grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
$grants = 0;
foreach ($owners as $rid) {
    foreach ($permIds as $pid) {
        $grantStmt->execute([(int)$rid, (int)$pid]);
        if ($grantStmt->rowCount() > 0) $grants++;
    }
}
echo "  + role_permissions otorgadas: $grants\n";

/* 5) Settings: límites por plan + providers permitidos por plan */
$defaults = [
    'integrations_max_starter'    => '0',
    'integrations_max_free'       => '0',
    'integrations_max_pro'        => '5',
    'integrations_max_business'   => '15',
    'integrations_max_enterprise' => '999',
    // Lista CSV de providers permitidos (vacío = todos)
    'integrations_providers_starter'    => '',
    'integrations_providers_free'       => '',
    'integrations_providers_pro'        => 'slack,discord,telegram,teams,webhook,email',
    'integrations_providers_business'   => 'slack,discord,telegram,teams,webhook,email,zapier,n8n,make',
    'integrations_providers_enterprise' => '',
];
$settingStmt = $pdo->prepare("INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES (?, ?)");
foreach ($defaults as $k => $v) {
    $settingStmt->execute([$k, $v]);
}
echo "  + saas_settings sembrados (defaults por plan)\n";

echo "\n✓ Migración de integraciones completa.\n";
echo "  Recordatorio: el módulo está gateado por plan (no disponible en starter/free).\n";
