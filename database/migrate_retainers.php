<?php
/**
 * Igualas (Retainers) — módulo Business / Enterprise
 *
 *  · Tabla `retainers` — contratos recurrentes para empresas o clientes individuales
 *  · Tabla `retainer_periods` — ciclos facturados (mensual / trimestral / anual)
 *  · Tabla `retainer_consumptions` — consumos (horas / servicios) sobre el período activo
 *  · Tabla `tenant_module_overrides` — super admin habilita/deshabilita módulos por tenant
 *  · Permisos retainers.*
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_RETAINERS') { http_response_code(403); exit('forbidden'); }
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

/* ───────── 1) retainers ───────── */
if (!tableExists($pdo, 'retainers')) {
    $pdo->exec("CREATE TABLE retainers (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        code VARCHAR(40) NOT NULL,
        name VARCHAR(150) NOT NULL,
        client_type ENUM('company','individual') NOT NULL DEFAULT 'company',
        company_id INT UNSIGNED NULL,
        contact_id INT UNSIGNED NULL,
        client_name VARCHAR(150) NULL,
        client_email VARCHAR(180) NULL,
        client_phone VARCHAR(40) NULL,
        client_doc VARCHAR(60) NULL,
        description TEXT NULL,
        scope TEXT NULL,
        billing_cycle ENUM('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(8) NOT NULL DEFAULT 'USD',
        included_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
        included_tickets INT UNSIGNED NOT NULL DEFAULT 0,
        overage_hour_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
        starts_on DATE NOT NULL,
        ends_on DATE NULL,
        next_invoice_on DATE NULL,
        auto_renew TINYINT(1) NOT NULL DEFAULT 1,
        status ENUM('draft','active','paused','cancelled','expired') NOT NULL DEFAULT 'active',
        notes TEXT NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_code (tenant_id, code),
        KEY idx_tenant (tenant_id),
        KEY idx_company (company_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainers\n";
} else {
    echo "  • retainers ya existe\n";
}

/* ───────── 2) retainer_periods ───────── */
if (!tableExists($pdo, 'retainer_periods')) {
    $pdo->exec("CREATE TABLE retainer_periods (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        retainer_id INT UNSIGNED NOT NULL,
        tenant_id INT UNSIGNED NOT NULL,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        included_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
        consumed_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
        overage_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        invoice_id INT UNSIGNED NULL,
        status ENUM('open','invoiced','paid','closed','overdue') NOT NULL DEFAULT 'open',
        invoiced_at DATETIME NULL,
        paid_at DATETIME NULL,
        notes VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_retainer_period (retainer_id, period_start),
        KEY idx_tenant (tenant_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainer_periods\n";
} else {
    echo "  • retainer_periods ya existe\n";
}

/* ───────── 3) retainer_consumptions ───────── */
if (!tableExists($pdo, 'retainer_consumptions')) {
    $pdo->exec("CREATE TABLE retainer_consumptions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        retainer_id INT UNSIGNED NOT NULL,
        period_id INT UNSIGNED NULL,
        tenant_id INT UNSIGNED NOT NULL,
        ticket_id INT UNSIGNED NULL,
        user_id INT UNSIGNED NULL,
        consumed_at DATETIME NOT NULL,
        hours DECIMAL(8,2) NOT NULL DEFAULT 0,
        description VARCHAR(255) NULL,
        billable TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_retainer (retainer_id),
        KEY idx_period (period_id),
        KEY idx_tenant (tenant_id),
        KEY idx_ticket (ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainer_consumptions\n";
} else {
    echo "  • retainer_consumptions ya existe\n";
}

/* ───────── 4) tenant_module_overrides ───────── */
if (!tableExists($pdo, 'tenant_module_overrides')) {
    $pdo->exec("CREATE TABLE tenant_module_overrides (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        feature VARCHAR(60) NOT NULL,
        state ENUM('on','off') NOT NULL,
        reason VARCHAR(255) NULL,
        set_by_admin INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_feature (tenant_id, feature),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + tenant_module_overrides\n";
} else {
    echo "  • tenant_module_overrides ya existe\n";
}

/* ───────── 5) Permisos ───────── */
$permsToAdd = [
    'retainers.view'   => ['retainers', 'Ver igualas'],
    'retainers.create' => ['retainers', 'Crear igualas'],
    'retainers.edit'   => ['retainers', 'Editar igualas'],
    'retainers.delete' => ['retainers', 'Eliminar igualas'],
    'retainers.bill'   => ['retainers', 'Facturar y registrar consumos'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
$added = 0;
foreach ($permsToAdd as $slug => [$mod, $label]) {
    $stmt->execute([$slug, $mod, $label]);
    if ($stmt->rowCount() > 0) $added++;
}
echo "  + permisos sembrados ($added nuevos)\n";

/* ───────── 6) Otorgar al rol owner y admin de cada tenant ───────── */
$roles = $pdo->query("SELECT id FROM roles WHERE slug IN ('owner','admin')")->fetchAll(PDO::FETCH_COLUMN);
$permIds = $pdo->query("SELECT id FROM permissions WHERE module='retainers'")->fetchAll(PDO::FETCH_COLUMN);
$grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
$grants = 0;
foreach ($roles as $rid) {
    foreach ($permIds as $pid) {
        $grantStmt->execute([(int)$rid, (int)$pid]);
        if ($grantStmt->rowCount() > 0) $grants++;
    }
}
echo "  + role_permissions otorgadas: $grants\n";

echo "\n✓ Migración de igualas + module overrides completa.\n";
echo "  · Recordatorio: el módulo 'retainers' está gateado por plan Business / Enterprise (Plan::FEATURES).\n";
echo "  · El super admin puede habilitar/deshabilitar módulos por tenant en /admin/tenants/{id}/modules.\n";
