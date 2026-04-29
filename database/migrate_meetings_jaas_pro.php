<?php
/**
 * JaaS pro · webhooks + grabaciones + transcripciones + tracking de participantes.
 *
 *   - meeting_participants: cada usuario que entra a la conferencia (join/leave timestamps)
 *   - meeting_recordings:   grabaciones cloud subidas por JaaS
 *   - meeting_settings:     toggles para recording / transcription / lobby / prejoin + webhook secret
 *   - meeting_types:        flags por tipo (override del tenant)
 *   - meetings:             columnas para transcript + estados live
 *
 * Idempotente.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_JAAS_PRO') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

function jpTbl(PDO $p, string $t): bool { return (bool)$p->query("SHOW TABLES LIKE " . $p->quote($t))->fetch(); }
function jpCol(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function jpMk(PDO $p, string $t, string $sql): void { if (!jpTbl($p,$t)) { $p->exec($sql); echo "  + $t\n"; } else echo "  • $t ya existe\n"; }
function jpAdd(PDO $p, string $t, string $c, string $sql): void { if (!jpCol($p,$t,$c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n"; }

/* ─── meeting_participants ─── */
jpMk($pdo, 'meeting_participants', "CREATE TABLE meeting_participants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    meeting_id INT UNSIGNED NOT NULL,
    participant_jid VARCHAR(120) NULL,
    name VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    role VARCHAR(20) NULL,
    is_moderator TINYINT(1) NOT NULL DEFAULT 0,
    joined_at DATETIME NULL,
    left_at DATETIME NULL,
    duration_seconds INT UNSIGNED NULL,
    raw_event JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_meeting (meeting_id),
    KEY idx_jid (participant_jid),
    KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ─── meeting_recordings ─── */
jpMk($pdo, 'meeting_recordings', "CREATE TABLE meeting_recordings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    meeting_id INT UNSIGNED NOT NULL,
    kind ENUM('recording','transcription','chat','sip-jibri-recording') NOT NULL DEFAULT 'recording',
    file_url VARCHAR(1000) NULL,
    file_id VARCHAR(120) NULL,
    duration_seconds INT UNSIGNED NULL,
    file_size_bytes BIGINT UNSIGNED NULL,
    mime_type VARCHAR(80) NULL,
    transcript_text MEDIUMTEXT NULL,
    ai_processed TINYINT(1) NOT NULL DEFAULT 0,
    ai_summary TEXT NULL,
    ai_action_items JSON NULL,
    raw_event JSON NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_meeting (meeting_id),
    KEY idx_kind (kind),
    KEY idx_file (file_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ─── meeting_jaas_events (audit log de webhooks) ─── */
jpMk($pdo, 'meeting_jaas_events', "CREATE TABLE meeting_jaas_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    meeting_id INT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    fqn VARCHAR(255) NULL,
    payload JSON NULL,
    signature_valid TINYINT(1) NOT NULL DEFAULT 0,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_event (event_type),
    KEY idx_meeting (meeting_id),
    KEY idx_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ─── meeting_settings: webhooks + toggles ─── */
jpAdd($pdo, 'meeting_settings', 'jaas_webhook_secret',     "ALTER TABLE meeting_settings ADD COLUMN jaas_webhook_secret VARCHAR(80) NULL AFTER livekit_api_secret");
jpAdd($pdo, 'meeting_settings', 'recording_enabled',       "ALTER TABLE meeting_settings ADD COLUMN recording_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER jaas_webhook_secret");
jpAdd($pdo, 'meeting_settings', 'recording_auto_start',    "ALTER TABLE meeting_settings ADD COLUMN recording_auto_start TINYINT(1) NOT NULL DEFAULT 0 AFTER recording_enabled");
jpAdd($pdo, 'meeting_settings', 'transcription_enabled',   "ALTER TABLE meeting_settings ADD COLUMN transcription_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER recording_auto_start");
jpAdd($pdo, 'meeting_settings', 'lobby_enabled',           "ALTER TABLE meeting_settings ADD COLUMN lobby_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER transcription_enabled");
jpAdd($pdo, 'meeting_settings', 'prejoin_enabled',         "ALTER TABLE meeting_settings ADD COLUMN prejoin_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER lobby_enabled");
jpAdd($pdo, 'meeting_settings', 'livestream_enabled',      "ALTER TABLE meeting_settings ADD COLUMN livestream_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER prejoin_enabled");
jpAdd($pdo, 'meeting_settings', 'transcript_ai_summary',   "ALTER TABLE meeting_settings ADD COLUMN transcript_ai_summary TINYINT(1) NOT NULL DEFAULT 1 AFTER livestream_enabled");

/* ─── meeting_types: overrides por tipo ─── */
jpAdd($pdo, 'meeting_types', 'recording_mode',     "ALTER TABLE meeting_types ADD COLUMN recording_mode ENUM('inherit','always','never','optional') NOT NULL DEFAULT 'inherit' AFTER reminder_minutes");
jpAdd($pdo, 'meeting_types', 'transcription_mode', "ALTER TABLE meeting_types ADD COLUMN transcription_mode ENUM('inherit','always','never') NOT NULL DEFAULT 'inherit' AFTER recording_mode");

/* ─── meetings: estados live de conferencia ─── */
jpAdd($pdo, 'meetings', 'conference_subject', "ALTER TABLE meetings ADD COLUMN conference_subject VARCHAR(200) NULL AFTER conference_room_id");
jpAdd($pdo, 'meetings', 'participants_count',  "ALTER TABLE meetings ADD COLUMN participants_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER conference_ended_at");
jpAdd($pdo, 'meetings', 'transcript_text',     "ALTER TABLE meetings ADD COLUMN transcript_text MEDIUMTEXT NULL AFTER ai_followup");
jpAdd($pdo, 'meetings', 'transcript_summary',  "ALTER TABLE meetings ADD COLUMN transcript_summary TEXT NULL AFTER transcript_text");
jpAdd($pdo, 'meetings', 'transcript_action_items', "ALTER TABLE meetings ADD COLUMN transcript_action_items JSON NULL AFTER transcript_summary");

/* ─── auto-generar webhook secret para tenants existentes que no tengan ─── */
$nullSecret = $pdo->query("SELECT tenant_id FROM meeting_settings WHERE jaas_webhook_secret IS NULL")->fetchAll(PDO::FETCH_COLUMN);
foreach ($nullSecret as $tid) {
    $secret = bin2hex(random_bytes(24));
    $pdo->prepare("UPDATE meeting_settings SET jaas_webhook_secret=? WHERE tenant_id=?")->execute([$secret, (int)$tid]);
    echo "  + webhook secret para tenant $tid\n";
}

echo "\n✓ Migración JaaS Pro completa.\n";
