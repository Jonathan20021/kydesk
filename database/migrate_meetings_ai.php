<?php
/**
 * IA en el módulo de Reuniones.
 *   · Columnas en `meetings` para persistir análisis y briefings de IA
 *   · Flags en `meeting_settings` para habilitar el suggester público
 *   · Idempotente
 *
 * CLI:  php database/migrate_meetings_ai.php
 * Web:  /database/migrate_meetings_ai.php?token=KYDESK_MEETINGS_AI
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_MEETINGS_AI') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

function maiCol(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function maiAdd(PDO $p, string $t, string $c, string $sql): void {
    if (!maiCol($p, $t, $c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n";
}

/* ────────── Columnas IA en meetings ────────── */
maiAdd($pdo, 'meetings', 'ai_summary',     "ALTER TABLE meetings ADD COLUMN ai_summary TEXT NULL AFTER notes");
maiAdd($pdo, 'meetings', 'ai_briefing',    "ALTER TABLE meetings ADD COLUMN ai_briefing MEDIUMTEXT NULL AFTER ai_summary");
maiAdd($pdo, 'meetings', 'ai_intent',      "ALTER TABLE meetings ADD COLUMN ai_intent VARCHAR(40) NULL AFTER ai_briefing");
maiAdd($pdo, 'meetings', 'ai_sentiment',   "ALTER TABLE meetings ADD COLUMN ai_sentiment VARCHAR(20) NULL AFTER ai_intent");
maiAdd($pdo, 'meetings', 'ai_urgency',     "ALTER TABLE meetings ADD COLUMN ai_urgency VARCHAR(20) NULL AFTER ai_sentiment");
maiAdd($pdo, 'meetings', 'ai_followup',    "ALTER TABLE meetings ADD COLUMN ai_followup TEXT NULL AFTER ai_urgency");
maiAdd($pdo, 'meetings', 'ai_topics',      "ALTER TABLE meetings ADD COLUMN ai_topics JSON NULL AFTER ai_followup");
maiAdd($pdo, 'meetings', 'ai_processed_at',"ALTER TABLE meetings ADD COLUMN ai_processed_at DATETIME NULL AFTER ai_topics");
maiAdd($pdo, 'meetings', 'ai_briefing_at', "ALTER TABLE meetings ADD COLUMN ai_briefing_at DATETIME NULL AFTER ai_processed_at");

/* ────────── Flags en meeting_settings ────────── */
maiAdd($pdo, 'meeting_settings', 'ai_auto_analyze',     "ALTER TABLE meeting_settings ADD COLUMN ai_auto_analyze TINYINT(1) NOT NULL DEFAULT 1 AFTER show_powered_by");
maiAdd($pdo, 'meeting_settings', 'ai_public_suggester', "ALTER TABLE meeting_settings ADD COLUMN ai_public_suggester TINYINT(1) NOT NULL DEFAULT 1 AFTER ai_auto_analyze");
maiAdd($pdo, 'meeting_settings', 'ai_briefing_enabled', "ALTER TABLE meeting_settings ADD COLUMN ai_briefing_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER ai_public_suggester");

/* ────────── Indexes ────────── */
try {
    $idx = $pdo->query("SHOW INDEX FROM meetings WHERE Key_name='idx_ai_intent'")->fetch();
    if (!$idx) {
        $pdo->exec("ALTER TABLE meetings ADD INDEX idx_ai_intent (ai_intent), ADD INDEX idx_ai_sentiment (ai_sentiment)");
        echo "  + idx_ai_intent + idx_ai_sentiment\n";
    } else echo "  • indexes ya existen\n";
} catch (\Throwable $e) { echo "  ! " . $e->getMessage() . "\n"; }

echo "\n✓ Migración IA de meetings completa.\n";
