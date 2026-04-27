<?php
/**
 * Departamentos (PRO+) — módulo completo
 *  · Tabla `departments` con tenant_id, name, slug único por tenant, color, icono, manager
 *  · Pivote `department_users` (un agente puede pertenecer a varios departamentos)
 *  · Columna `tickets.department_id`
 *  · Columna opcional `sla_policies.department_id` para SLAs por departamento
 *  · Permisos globales (departments.*)
 *  · Seed de departamentos por defecto en tenants existentes
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_DEPTS') { http_response_code(403); exit('forbidden'); }
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

/* ───────── 1) departments ───────── */
if (!tableExists($pdo, 'departments')) {
    $pdo->exec("CREATE TABLE departments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(80) NOT NULL,
        description TEXT NULL,
        color VARCHAR(20) NOT NULL DEFAULT '#3b82f6',
        icon VARCHAR(40) NOT NULL DEFAULT 'layers',
        manager_user_id INT UNSIGNED NULL,
        email VARCHAR(180) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
        KEY idx_tenant (tenant_id),
        KEY idx_manager (manager_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + departments\n";
} else {
    echo "  • departments already exists\n";
}

/* ───────── 2) department_users (pivote) ───────── */
if (!tableExists($pdo, 'department_users')) {
    $pdo->exec("CREATE TABLE department_users (
        department_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        is_lead TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (department_id, user_id),
        KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + department_users\n";
} else {
    echo "  • department_users already exists\n";
}

/* ───────── 3) tickets.department_id ───────── */
if (!columnExists($pdo, 'tickets', 'department_id')) {
    $pdo->exec("ALTER TABLE tickets ADD COLUMN department_id INT UNSIGNED NULL AFTER category_id, ADD INDEX idx_department (department_id)");
    echo "  + tickets.department_id\n";
} else {
    echo "  • tickets.department_id already exists\n";
}

/* ───────── 4) sla_policies.department_id (per-dept SLA) ───────── */
if (!columnExists($pdo, 'sla_policies', 'department_id')) {
    $pdo->exec("ALTER TABLE sla_policies ADD COLUMN department_id INT UNSIGNED NULL AFTER tenant_id, ADD INDEX idx_dept (department_id)");
    echo "  + sla_policies.department_id\n";
} else {
    echo "  • sla_policies.department_id already exists\n";
}

/* ───────── 5) Permisos globales ───────── */
$permsToAdd = [
    'departments.view'   => ['departments', 'Ver departamentos'],
    'departments.create' => ['departments', 'Crear departamentos'],
    'departments.edit'   => ['departments', 'Editar departamentos'],
    'departments.delete' => ['departments', 'Eliminar departamentos'],
    'departments.assign' => ['departments', 'Asignar agentes a departamentos'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
$added = 0;
foreach ($permsToAdd as $slug => [$mod, $label]) {
    $stmt->execute([$slug, $mod, $label]);
    if ($stmt->rowCount() > 0) $added++;
}
echo "  + permisos sembrados ($added nuevos)\n";

/* ───────── 6) Otorgar permisos al rol owner de cada tenant ───────── */
$owners = $pdo->query("SELECT id FROM roles WHERE slug='owner'")->fetchAll(PDO::FETCH_COLUMN);
$permIds = $pdo->query("SELECT id FROM permissions WHERE module='departments'")->fetchAll(PDO::FETCH_COLUMN);
$grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
$grants = 0;
foreach ($owners as $rid) {
    foreach ($permIds as $pid) {
        $grantStmt->execute([(int)$rid, (int)$pid]);
        if ($grantStmt->rowCount() > 0) $grants++;
    }
}
echo "  + role_permissions otorgadas: $grants\n";

/* ───────── 7) Seed de departamentos por defecto en tenants existentes ───────── */
$tenants = $pdo->query("SELECT id, slug FROM tenants WHERE is_developer_sandbox=0 OR is_developer_sandbox IS NULL")->fetchAll(PDO::FETCH_ASSOC);
$defaults = [
    ['Soporte Técnico', 'soporte-tecnico', 'Atención de incidencias técnicas y resolución de problemas operativos.', '#7c5cff', 'life-buoy'],
    ['Ventas',          'ventas',          'Oportunidades comerciales, cotizaciones y seguimiento de clientes.',     '#22c55e', 'trending-up'],
    ['Facturación',     'facturacion',     'Pagos, facturas, créditos y consultas administrativas.',                '#f59e0b', 'wallet'],
    ['Recursos Humanos','rrhh',            'Solicitudes internas del equipo: nómina, vacaciones, beneficios.',      '#0ea5e9', 'users'],
];
$insertDept = $pdo->prepare("INSERT IGNORE INTO departments (tenant_id, name, slug, description, color, icon, sort_order) VALUES (?,?,?,?,?,?,?)");
$seeded = 0;
foreach ($tenants as $t) {
    $existing = (int)$pdo->query("SELECT COUNT(*) FROM departments WHERE tenant_id=" . (int)$t['id'])->fetchColumn();
    if ($existing > 0) continue;
    foreach ($defaults as $i => [$n, $s, $d, $c, $ic]) {
        $insertDept->execute([(int)$t['id'], $n, $s, $d, $c, $ic, $i]);
        if ($insertDept->rowCount() > 0) $seeded++;
    }
}
echo "  + departamentos por defecto sembrados: $seeded\n";

echo "\n✓ Migración de departamentos completa.\n";
echo "  Recordatorio: el módulo está gateado por plan PRO en Plan::FEATURES.\n";
