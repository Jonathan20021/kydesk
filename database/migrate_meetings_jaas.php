<?php
/**
 * Soporte JaaS (8x8.vc) — añade jitsi_kid (API Key ID) para firma RS256.
 * Idempotente.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_JAAS') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}
define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$exists = (bool)$pdo->query("SHOW COLUMNS FROM meeting_settings LIKE 'jitsi_kid'")->fetch();
if (!$exists) {
    $pdo->exec("ALTER TABLE meeting_settings ADD COLUMN jitsi_kid VARCHAR(120) NULL AFTER jitsi_app_id");
    echo "  + meeting_settings.jitsi_kid\n";
} else {
    echo "  • jitsi_kid ya existe\n";
}

echo "\n✓ Migración JaaS completa.\n";
