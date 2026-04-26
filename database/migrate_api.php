<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_API') { http_response_code(403); exit('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}
define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];

$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
    $cfg['user'], $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sql = file_get_contents(BASE_PATH . '/database/api_migration.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
foreach (array_filter(array_map('trim', explode(';', $sql))) as $s) {
    if ($s === '') continue;
    try { $pdo->exec($s); echo "  ✓ " . substr($s, 0, 50) . "…\n"; }
    catch (\Throwable $e) { echo "  ! " . $e->getMessage() . "\n"; }
}
echo "\n✓ Migración API completada.\n";
