<?php
/**
 * Kydesk — Migración de tabla email_log y semilla de mail settings.
 *
 * CLI:        php database/migrate_email.php
 * Navegador:  /kyros-helpdesk/database/migrate_email.php?token=KYDESK_EMAIL
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_EMAIL') {
        http_response_code(403);
        exit('Forbidden — usa ?token=KYDESK_EMAIL');
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

$sql = file_get_contents(BASE_PATH . '/database/email_migration.sql');
// Strip line comments
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    try {
        $pdo->exec($stmt);
        $first = strtoupper(substr($stmt, 0, 30));
        echo "  ✓ " . substr($first, 0, 60) . "…\n";
    } catch (\Throwable $e) {
        echo "  ! " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Migración email completada.\n";
echo "Recuerda borrar este archivo en producción.\n";
