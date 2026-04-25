<?php
/**
 * Kydesk — Migración a producción
 *
 * Ejecuta este script una vez para preparar la DB de producción.
 * Uso (desde CLI):
 *   php database/migrate_production.php
 *
 * O desde el navegador (solo durante setup, después borrar):
 *   http://tu-dominio/kyros-helpdesk/database/migrate_production.php?token=KYDESK_MIGRATE
 *
 * Operaciones:
 *  1) Conecta a la DB de producción usando config.php (env=production).
 *  2) Crea las 22 tablas del schema base si no existen.
 *  3) Aplica la migración de demos (columnas is_demo, demo_expires_at, demo_plan).
 *  4) Crea la tabla `macros` si no existe.
 *  5) Agrega columna `users.preferences` si no existe.
 *  6) Siembra permisos globales si están vacíos.
 *  7) Crea el tenant demo y los usuarios base si la DB está vacía.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'KYDESK_MIGRATE') {
        http_response_code(403);
        exit('Forbidden — agrega ?token=KYDESK_MIGRATE');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

putenv('KYDESK_ENV=production');
$config = require BASE_PATH . '/config/config.php';

$cfg = $config['db'];
echo "→ Conectando a {$cfg['host']}:{$cfg['port']}/{$cfg['name']} como {$cfg['user']}…\n";

try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
        $cfg['user'],
        $cfg['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "✓ Conexión OK.\n\n";
} catch (PDOException $e) {
    echo "✗ ERROR de conexión: {$e->getMessage()}\n";
    echo "  Verifica que el servidor 129.121.81.172 acepte conexiones desde tu IP.\n";
    exit(1);
}

function tableExists(PDO $pdo, string $table): bool
{
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    return (bool)$pdo->query("SHOW TABLES LIKE '$safe'")->fetch();
}

function columnExists(PDO $pdo, string $table, string $col): bool
{
    if (!tableExists($pdo, $table)) return false;
    $safeT = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeC = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$safeT` LIKE '$safeC'")->fetch();
}

/* ───────── 1) Schema base ───────── */
if (!tableExists($pdo, 'tenants')) {
    echo "→ Creando schema base (22 tablas)…\n";
    $sql = file_get_contents(BASE_PATH . '/database/schema.sql');
    $pdo->exec($sql);
    echo "✓ Schema base creado.\n";
} else {
    echo "• Schema base ya existe — omitido.\n";
}

/* ───────── 2) Migración demos ───────── */
echo "→ Aplicando migración de demos…\n";
if (!columnExists($pdo, 'tenants', 'is_demo')) {
    $pdo->exec("ALTER TABLE tenants ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0");
    echo "  + tenants.is_demo\n";
}
if (!columnExists($pdo, 'tenants', 'demo_expires_at')) {
    $pdo->exec("ALTER TABLE tenants ADD COLUMN demo_expires_at DATETIME NULL");
    echo "  + tenants.demo_expires_at\n";
}
if (!columnExists($pdo, 'tenants', 'demo_plan')) {
    $pdo->exec("ALTER TABLE tenants ADD COLUMN demo_plan VARCHAR(20) NULL");
    echo "  + tenants.demo_plan\n";
}
$idxExists = $pdo->query("SHOW INDEX FROM tenants WHERE Key_name='idx_tenants_demo_expiry'")->fetch();
if (!$idxExists) {
    $pdo->exec("CREATE INDEX idx_tenants_demo_expiry ON tenants (is_demo, demo_expires_at)");
    echo "  + idx_tenants_demo_expiry\n";
}
echo "✓ Migración de demos OK.\n";

/* ───────── 3) Tabla macros ───────── */
if (!tableExists($pdo, 'macros')) {
    echo "→ Creando tabla macros…\n";
    $pdo->exec("CREATE TABLE macros (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL,
        body TEXT NOT NULL,
        category VARCHAR(40) DEFAULT 'general',
        shortcut VARCHAR(20) NULL,
        is_internal TINYINT(1) DEFAULT 0,
        use_count INT UNSIGNED DEFAULT 0,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_macros_tenant (tenant_id),
        CONSTRAINT fk_macros_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ macros creada.\n";
} else {
    echo "• macros ya existe — omitido.\n";
}

/* ───────── 4) users.preferences ───────── */
if (!columnExists($pdo, 'users', 'preferences')) {
    echo "→ Agregando users.preferences…\n";
    $pdo->exec("ALTER TABLE users ADD COLUMN preferences JSON NULL");
    echo "✓ users.preferences agregada.\n";
} else {
    echo "• users.preferences ya existe — omitido.\n";
}

/* ───────── 5) Permisos globales ───────── */
$permCount = (int)$pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
if ($permCount === 0) {
    echo "→ Sembrando permisos globales…\n";
    $modules = [
        'dashboard' => ['view' => 'Ver dashboard'],
        'tickets' => ['view'=>'Ver tickets','create'=>'Crear tickets','edit'=>'Editar tickets','delete'=>'Eliminar tickets','assign'=>'Asignar tickets','escalate'=>'Escalar tickets','comment'=>'Comentar tickets'],
        'notes' => ['view'=>'Ver notas','create'=>'Crear notas','edit'=>'Editar notas','delete'=>'Eliminar notas'],
        'todos' => ['view'=>'Ver tareas','create'=>'Crear tareas','edit'=>'Editar tareas','delete'=>'Eliminar tareas'],
        'kb' => ['view'=>'Ver base de conocimiento','create'=>'Crear artículos','edit'=>'Editar artículos','delete'=>'Eliminar artículos'],
        'companies' => ['view'=>'Ver empresas','create'=>'Crear empresas','edit'=>'Editar empresas','delete'=>'Eliminar empresas'],
        'assets' => ['view'=>'Ver activos','create'=>'Crear activos','edit'=>'Editar activos','delete'=>'Eliminar activos'],
        'automations' => ['view'=>'Ver automatizaciones','create'=>'Crear automatizaciones','edit'=>'Editar automatizaciones','delete'=>'Eliminar automatizaciones'],
        'sla' => ['view'=>'Ver SLA','edit'=>'Editar SLA'],
        'audit' => ['view'=>'Ver auditoría'],
        'users' => ['view'=>'Ver usuarios','create'=>'Crear usuarios','edit'=>'Editar usuarios','delete'=>'Eliminar usuarios'],
        'roles' => ['view'=>'Ver roles','create'=>'Crear roles','edit'=>'Editar roles','delete'=>'Eliminar roles'],
        'reports' => ['view'=>'Ver reportes'],
        'settings' => ['view'=>'Ver ajustes','edit'=>'Editar ajustes'],
    ];
    $count = 0;
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
    foreach ($modules as $mod => $actions) {
        foreach ($actions as $act => $label) {
            $stmt->execute(["$mod.$act", $mod, $label]);
            $count++;
        }
    }
    echo "✓ $count permisos sembrados.\n";
} else {
    echo "• Permisos ya sembrados ($permCount) — omitido.\n";
}

/* ───────── 6) Tenant demo + admin ───────── */
$tenantCount = (int)$pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
if ($tenantCount === 0) {
    echo "\n⚠ DB vacía. Para sembrar tenant principal corré /install desde el navegador.\n";
    echo "  URL: http://tu-dominio/kyros-helpdesk/install\n";
} else {
    echo "• $tenantCount tenant(s) ya existen.\n";
}

echo "\n══════════════════════════════════════\n";
echo "✓ MIGRACIÓN COMPLETA\n";
echo "══════════════════════════════════════\n";
echo "  Servidor:  {$cfg['host']}\n";
echo "  Database:  {$cfg['name']}\n";
echo "  Usuario:   {$cfg['user']}\n";
echo "\nPróximos pasos:\n";
echo "  1. Si DB vacía → visitá /install\n";
echo "  2. Login con admin@demo.com / admin123\n";
echo "  3. Cambia la password del admin\n";
echo "  4. BORRA este archivo migrate_production.php por seguridad\n";
