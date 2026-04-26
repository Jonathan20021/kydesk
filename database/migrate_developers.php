<?php
/**
 * Kydesk — Migración Developer Portal
 *
 * Crea las tablas necesarias para el portal de developers:
 * developers, dev_plans, dev_subscriptions, dev_apps, dev_api_tokens,
 * dev_invoices, dev_payments, dev_api_usage, dev_audit_logs.
 *
 * Uso:
 *   CLI:        php database/migrate_developers.php
 *   Navegador:  http://tu-dominio/kyros-helpdesk/database/migrate_developers.php?token=KYDESK_DEVELOPERS
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'KYDESK_DEVELOPERS') {
        http_response_code(403);
        exit('Forbidden — usa ?token=KYDESK_DEVELOPERS');
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

echo "→ Aplicando esquema developer portal…\n";
$sql = file_get_contents(BASE_PATH . '/database/developers_migration.sql');
// Remove SQL comment lines so split-by-; isn't fooled by leading "-- ..." comments
$lines = preg_split('/\R/', $sql);
$lines = array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l));
$sql = implode("\n", $lines);
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    // ALTER TABLE tenants → manejarlo manualmente más abajo
    if (preg_match('/^ALTER\s+TABLE\s+`?tenants`?/i', $stmt)) continue;
    try {
        $pdo->exec($stmt);
    } catch (\PDOException $e) {
        if (str_contains($e->getMessage(), 'already exists')) continue;
        if (str_contains($e->getMessage(), 'Duplicate')) continue;
        echo "  ! " . substr($e->getMessage(), 0, 200) . "\n";
    }
}
echo "✓ Tablas creadas.\n";

// Columnas adicionales en tenants
echo "→ Verificando columnas adicionales en tenants…\n";
$tenantCols = [
    'is_developer_sandbox' => "TINYINT(1) DEFAULT 0",
    'dev_app_id' => "INT UNSIGNED NULL",
];
foreach ($tenantCols as $col => $def) {
    if (!columnExists($pdo, 'tenants', $col)) {
        try {
            $pdo->exec("ALTER TABLE tenants ADD COLUMN $col $def");
            echo "  + tenants.$col\n";
        } catch (\Throwable $e) {
            echo "  ! tenants.$col: " . substr($e->getMessage(), 0, 120) . "\n";
        }
    }
}

// Sembrar developer demo si no existe ninguno
$count = (int)$pdo->query("SELECT COUNT(*) FROM developers")->fetchColumn();
if ($count === 0) {
    echo "→ Sembrando developer demo…\n";
    $hash = password_hash('developer123', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO developers (name, email, password, company, is_active, is_verified) VALUES (?,?,?,?,1,1)");
    $stmt->execute(['Developer Demo', 'developer@kydesk.com', $hash, 'Demo Studio']);
    $devId = (int)$pdo->lastInsertId();

    // Auto-suscribir al plan free
    $planId = (int)$pdo->query("SELECT id FROM dev_plans WHERE slug='dev_free'")->fetchColumn();
    if ($planId) {
        $pdo->prepare("INSERT INTO dev_subscriptions (developer_id, plan_id, status, billing_cycle, started_at, current_period_start, current_period_end, auto_renew) VALUES (?,?,?,?,?,?,?,1)")
            ->execute([$devId, $planId, 'active', 'monthly', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+1 month'))]);
    }
    echo "✓ Developer demo creado.\n";
    echo "    Email:    developer@kydesk.com\n";
    echo "    Password: developer123\n";
} else {
    echo "• Developers ya existen ($count) — omitido.\n";
}

echo "\n══════════════════════════════════════\n";
echo "✓ MIGRACIÓN DEVELOPER PORTAL COMPLETA\n";
echo "══════════════════════════════════════\n";
echo "Acceso developer:  /developers/login\n";
echo "Panel super admin: /admin/developers\n";
echo "\n⚠ BORRA database/migrate_developers.php después de migrar.\n";
