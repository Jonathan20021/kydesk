<?php
/**
 * Fix: jitsi_app_secret y livekit_api_secret quedaron como VARCHAR(255) pero
 * las claves privadas RSA de JaaS / LiveKit ocupan 1700-3000 caracteres.
 * Cambiamos a TEXT para que el INSERT/UPDATE no falle con "Data too long".
 *
 * Idempotente: detecta el tipo actual y solo migra si todavía es VARCHAR.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_SECRET_TEXT') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$cols = ['jitsi_app_secret', 'livekit_api_secret'];
foreach ($cols as $col) {
    $row = $pdo->query("SHOW COLUMNS FROM meeting_settings WHERE Field = " . $pdo->quote($col))->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "  • $col no existe (skip)\n";
        continue;
    }
    if (stripos($row['Type'], 'text') !== false) {
        echo "  • $col ya es TEXT\n";
        continue;
    }
    $pdo->exec("ALTER TABLE meeting_settings MODIFY COLUMN $col TEXT NULL");
    echo "  + $col → TEXT\n";
}

echo "\n✓ Migración completa.\n";
