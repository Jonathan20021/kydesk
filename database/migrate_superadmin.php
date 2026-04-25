<?php
/**
 * Kydesk — Migración Super Admin / SaaS Management
 *
 * Crea las tablas necesarias para el panel de super admin:
 * super_admins, plans, subscriptions, invoices, payments, saas_settings,
 * super_audit_logs, saas_support_tickets.
 *
 * Uso:
 *   CLI:        php database/migrate_superadmin.php
 *   Navegador:  http://tu-dominio/kyros-helpdesk/database/migrate_superadmin.php?token=KYDESK_SUPERADMIN
 *
 * Después del primer login, BORRA este archivo o cambia el token.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'KYDESK_SUPERADMIN') {
        http_response_code(403);
        exit('Forbidden — usa ?token=KYDESK_SUPERADMIN');
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

// 1) Ejecutar el SQL del esquema
echo "→ Aplicando esquema super admin…\n";
$sql = file_get_contents(BASE_PATH . '/database/superadmin_migration.sql');
// MySQL no soporta ADD COLUMN IF NOT EXISTS en versiones antiguas — partimos en sentencias.
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--') || str_starts_with(strtoupper($stmt), 'SET ')) {
        if ($stmt !== '') { try { $pdo->exec($stmt); } catch (\Throwable $e) {} }
        continue;
    }
    // Saltar ALTER TABLE tenants — lo manejamos columna a columna abajo
    if (preg_match('/^ALTER\s+TABLE\s+`?tenants`?/i', $stmt)) continue;
    try {
        $pdo->exec($stmt);
    } catch (\PDOException $e) {
        // Ignorar si la tabla ya existe
        if (str_contains($e->getMessage(), 'already exists')) continue;
        echo "  ! " . substr($e->getMessage(), 0, 120) . "\n";
    }
}
echo "✓ Tablas creadas.\n";

// 2) Columnas adicionales en tenants
echo "→ Verificando columnas adicionales en tenants…\n";
$tenantCols = [
    'subscription_id' => "INT UNSIGNED NULL",
    'billing_email' => "VARCHAR(150) NULL",
    'country' => "VARCHAR(80) NULL",
    'suspended_at' => "DATETIME NULL",
    'suspended_reason' => "VARCHAR(255) NULL",
    'notes' => "TEXT NULL",
];
foreach ($tenantCols as $col => $def) {
    if (!columnExists($pdo, 'tenants', $col)) {
        $pdo->exec("ALTER TABLE tenants ADD COLUMN $col $def");
        echo "  + tenants.$col\n";
    }
}
echo "✓ Columnas tenants OK.\n";

// 3) Sembrar super admin si la tabla está vacía
$count = (int)$pdo->query("SELECT COUNT(*) FROM super_admins")->fetchColumn();
if ($count === 0) {
    echo "→ Sembrando super admin por defecto…\n";
    $hash = password_hash('superadmin123', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO super_admins (name, email, password, role, is_active) VALUES (?,?,?,?,1)");
    $stmt->execute(['Super Administrador', 'superadmin@kydesk.com', $hash, 'owner']);
    echo "✓ Super admin creado.\n";
    echo "    Email:    superadmin@kydesk.com\n";
    echo "    Password: superadmin123\n";
    echo "    ⚠ CAMBIA LA PASSWORD DESPUÉS DEL PRIMER LOGIN\n";
} else {
    echo "• Super admin ya existe ($count) — omitido.\n";
}

// 4) Crear suscripciones para tenants existentes que no tengan
echo "→ Creando suscripciones para tenants existentes…\n";
$plans = $pdo->query("SELECT id, slug FROM plans")->fetchAll();
$planMap = []; foreach ($plans as $p) $planMap[$p['slug']] = (int)$p['id'];
$tenants = $pdo->query("SELECT id, plan, created_at FROM tenants WHERE id NOT IN (SELECT IFNULL(tenant_id,0) FROM subscriptions)")->fetchAll();
$ins = 0;
foreach ($tenants as $t) {
    $pSlug = $t['plan'] ?? 'pro';
    if (!isset($planMap[$pSlug])) $pSlug = 'pro';
    if (!isset($planMap[$pSlug])) continue;
    $pdo->prepare("INSERT INTO subscriptions (tenant_id, plan_id, status, billing_cycle, started_at, current_period_start, current_period_end, auto_renew) VALUES (?,?,?,?,?,?,?,1)")
        ->execute([$t['id'], $planMap[$pSlug], 'active', 'monthly', $t['created_at'], $t['created_at'], date('Y-m-d H:i:s', strtotime($t['created_at'].' +1 month'))]);
    $ins++;
}
echo "✓ $ins suscripciones creadas para tenants existentes.\n";

echo "\n══════════════════════════════════════\n";
echo "✓ MIGRACIÓN SUPER ADMIN COMPLETA\n";
echo "══════════════════════════════════════\n";
echo "Acceso:  /admin/login\n";
echo "Email:   superadmin@kydesk.com\n";
echo "Password: superadmin123\n";
echo "\n⚠ BORRA database/migrate_superadmin.php después de migrar.\n";
