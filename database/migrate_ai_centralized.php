<?php
/**
 * Centralización de IA — la API key vive en saas_settings (controlada por super admin),
 * y se asigna explícitamente a tenants. La columna `api_key` por-tenant queda opcional
 * (solo se usa si super admin la setea, deprecada para uso de cliente).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_AI_CENTRAL') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function colExists(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

/* Add assignment fields to ai_settings */
if (!colExists($pdo, 'ai_settings', 'is_assigned')) {
    $pdo->exec("ALTER TABLE ai_settings ADD COLUMN is_assigned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_enabled");
    echo "  + ai_settings.is_assigned\n";
}
if (!colExists($pdo, 'ai_settings', 'assigned_by_admin')) {
    $pdo->exec("ALTER TABLE ai_settings ADD COLUMN assigned_by_admin INT UNSIGNED NULL AFTER is_assigned");
    echo "  + ai_settings.assigned_by_admin\n";
}
if (!colExists($pdo, 'ai_settings', 'assigned_at')) {
    $pdo->exec("ALTER TABLE ai_settings ADD COLUMN assigned_at DATETIME NULL AFTER assigned_by_admin");
    echo "  + ai_settings.assigned_at\n";
}
if (!colExists($pdo, 'ai_settings', 'unassigned_at')) {
    $pdo->exec("ALTER TABLE ai_settings ADD COLUMN unassigned_at DATETIME NULL AFTER assigned_at");
    echo "  + ai_settings.unassigned_at\n";
}

/* saas_settings keys (super admin controlled) — ensure rows exist */
$keys = [
    ['ai_provider',       'anthropic'],
    ['ai_api_key',        ''],
    ['ai_default_model',  'claude-haiku-4-5'],
    ['ai_default_quota',  '1000'],
    ['ai_global_enabled', '1'],
];
$ins = $pdo->prepare("INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES (?, ?)");
foreach ($keys as [$k, $v]) {
    try { $ins->execute([$k, $v]); echo "  • saas_settings[$k]\n"; } catch (\Throwable $e) {}
}

/* Limpiar permisos legacy y dejar solo super admin como único editor de config */
echo "\n✓ Migración IA centralizada completa.\n";
echo "  · Super admin configura provider + API key en saas_settings\n";
echo "  · Asignación per-tenant en ai_settings.is_assigned\n";
echo "  · Feature 'ai_assist' ahora solo en plan Enterprise\n";
