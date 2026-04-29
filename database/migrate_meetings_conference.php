<?php
/**
 * Video conferencia / audio en el módulo de Reuniones.
 *
 *   - Soporte para múltiples providers (Jitsi por defecto, LiveKit para futuro)
 *   - Auto-generación de rooms al crear reservas
 *   - JWT opcional para Jitsi 8x8.vc (producción)
 *
 * CLI:  php database/migrate_meetings_conference.php
 * Web:  /database/migrate_meetings_conference.php?token=KYDESK_CONF
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CONF') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

function mcfCol(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function mcfAdd(PDO $p, string $t, string $c, string $sql): void {
    if (!mcfCol($p, $t, $c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n";
}

/* ───── Columnas en meetings ───── */
mcfAdd($pdo, 'meetings', 'conference_provider', "ALTER TABLE meetings ADD COLUMN conference_provider VARCHAR(20) NULL AFTER meeting_url");
mcfAdd($pdo, 'meetings', 'conference_room_id',  "ALTER TABLE meetings ADD COLUMN conference_room_id VARCHAR(120) NULL AFTER conference_provider");
mcfAdd($pdo, 'meetings', 'conference_meta',     "ALTER TABLE meetings ADD COLUMN conference_meta JSON NULL AFTER conference_room_id");
mcfAdd($pdo, 'meetings', 'conference_started_at', "ALTER TABLE meetings ADD COLUMN conference_started_at DATETIME NULL AFTER conference_meta");
mcfAdd($pdo, 'meetings', 'conference_ended_at',   "ALTER TABLE meetings ADD COLUMN conference_ended_at DATETIME NULL AFTER conference_started_at");

/* ───── Columnas en meeting_settings ───── */
mcfAdd($pdo, 'meeting_settings', 'conference_enabled',  "ALTER TABLE meeting_settings ADD COLUMN conference_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER ai_briefing_enabled");
mcfAdd($pdo, 'meeting_settings', 'conference_provider', "ALTER TABLE meeting_settings ADD COLUMN conference_provider VARCHAR(20) NOT NULL DEFAULT 'jitsi' AFTER conference_enabled");
mcfAdd($pdo, 'meeting_settings', 'jitsi_domain',        "ALTER TABLE meeting_settings ADD COLUMN jitsi_domain VARCHAR(120) NOT NULL DEFAULT 'meet.jit.si' AFTER conference_provider");
mcfAdd($pdo, 'meeting_settings', 'jitsi_app_id',        "ALTER TABLE meeting_settings ADD COLUMN jitsi_app_id VARCHAR(120) NULL AFTER jitsi_domain");
mcfAdd($pdo, 'meeting_settings', 'jitsi_app_secret',    "ALTER TABLE meeting_settings ADD COLUMN jitsi_app_secret VARCHAR(255) NULL AFTER jitsi_app_id");
mcfAdd($pdo, 'meeting_settings', 'jitsi_audio_only',    "ALTER TABLE meeting_settings ADD COLUMN jitsi_audio_only TINYINT(1) NOT NULL DEFAULT 0 AFTER jitsi_app_secret");
mcfAdd($pdo, 'meeting_settings', 'livekit_url',         "ALTER TABLE meeting_settings ADD COLUMN livekit_url VARCHAR(255) NULL AFTER jitsi_audio_only");
mcfAdd($pdo, 'meeting_settings', 'livekit_api_key',     "ALTER TABLE meeting_settings ADD COLUMN livekit_api_key VARCHAR(255) NULL AFTER livekit_url");
mcfAdd($pdo, 'meeting_settings', 'livekit_api_secret',  "ALTER TABLE meeting_settings ADD COLUMN livekit_api_secret VARCHAR(255) NULL AFTER livekit_api_key");

/* ───── Index ───── */
try {
    $idx = $pdo->query("SHOW INDEX FROM meetings WHERE Key_name='idx_conf_room'")->fetch();
    if (!$idx) {
        $pdo->exec("ALTER TABLE meetings ADD INDEX idx_conf_room (conference_room_id)");
        echo "  + idx_conf_room\n";
    } else echo "  • idx_conf_room ya existe\n";
} catch (\Throwable $e) { echo "  ! " . $e->getMessage() . "\n"; }

echo "\n✓ Migración conference completa.\n";
