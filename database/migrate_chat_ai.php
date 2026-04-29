<?php
/**
 * AI fallback para Live Chat.
 *
 * Agrega columnas a chat_widgets, chat_conversations y chat_messages para que Claude
 * pueda responder cuando no hay agentes humanos disponibles, usando la cuota Enterprise
 * gestionada por el super admin (ai_settings).
 *
 *   chat_widgets:
 *     - ai_fallback_mode  ENUM('off','no_agent','always')  modo de respuesta IA
 *     - ai_max_turns      INT                              tope de turnos antes de forzar escalación
 *     - ai_persona_name   VARCHAR(80)                      nombre que ve el visitante (ej: "Aurora")
 *     - ai_system_prompt  TEXT                             instrucciones extra del tenant (opcional)
 *     - ai_use_kb         TINYINT(1)                       grounding contra kb_articles publicados
 *
 *   chat_conversations:
 *     - ai_handled        TINYINT(1)                       hubo participación de IA
 *     - ai_turns          INT                              turnos consumidos por la IA
 *     - ai_tokens_in      BIGINT                           tokens input acumulados
 *     - ai_tokens_out     BIGINT                           tokens output acumulados
 *     - ai_escalated_at   DATETIME                         timestamp de handoff a humano
 *
 *   chat_messages:
 *     - is_ai             TINYINT(1)                       marca mensajes generados por IA
 *
 * CLI:  php database/migrate_chat_ai.php
 * Web:  /database/migrate_chat_ai.php?token=KYDESK_CHAT_AI
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHAT_AI') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
    $cfg['user'],
    $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "✓ Connected\n\n";

function chataiCol(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function chataiAdd(PDO $p, string $t, string $c, string $sql): void {
    if (!chataiCol($p, $t, $c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n";
}

echo "→ chat_widgets\n";
chataiAdd($pdo, 'chat_widgets', 'ai_fallback_mode',
    "ALTER TABLE chat_widgets ADD COLUMN ai_fallback_mode ENUM('off','no_agent','always') NOT NULL DEFAULT 'off' AFTER allowed_origins");
chataiAdd($pdo, 'chat_widgets', 'ai_max_turns',
    "ALTER TABLE chat_widgets ADD COLUMN ai_max_turns INT UNSIGNED NOT NULL DEFAULT 6 AFTER ai_fallback_mode");
chataiAdd($pdo, 'chat_widgets', 'ai_persona_name',
    "ALTER TABLE chat_widgets ADD COLUMN ai_persona_name VARCHAR(80) NOT NULL DEFAULT 'Asistente' AFTER ai_max_turns");
chataiAdd($pdo, 'chat_widgets', 'ai_system_prompt',
    "ALTER TABLE chat_widgets ADD COLUMN ai_system_prompt TEXT NULL AFTER ai_persona_name");
chataiAdd($pdo, 'chat_widgets', 'ai_use_kb',
    "ALTER TABLE chat_widgets ADD COLUMN ai_use_kb TINYINT(1) NOT NULL DEFAULT 1 AFTER ai_system_prompt");

echo "\n→ chat_conversations\n";
chataiAdd($pdo, 'chat_conversations', 'ai_handled',
    "ALTER TABLE chat_conversations ADD COLUMN ai_handled TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
chataiAdd($pdo, 'chat_conversations', 'ai_turns',
    "ALTER TABLE chat_conversations ADD COLUMN ai_turns INT UNSIGNED NOT NULL DEFAULT 0 AFTER ai_handled");
chataiAdd($pdo, 'chat_conversations', 'ai_tokens_in',
    "ALTER TABLE chat_conversations ADD COLUMN ai_tokens_in BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER ai_turns");
chataiAdd($pdo, 'chat_conversations', 'ai_tokens_out',
    "ALTER TABLE chat_conversations ADD COLUMN ai_tokens_out BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER ai_tokens_in");
chataiAdd($pdo, 'chat_conversations', 'ai_escalated_at',
    "ALTER TABLE chat_conversations ADD COLUMN ai_escalated_at DATETIME NULL AFTER ai_tokens_out");

echo "\n→ chat_messages\n";
chataiAdd($pdo, 'chat_messages', 'is_ai',
    "ALTER TABLE chat_messages ADD COLUMN is_ai TINYINT(1) NOT NULL DEFAULT 0 AFTER user_id");

echo "\n✓ Migración chat AI completa.\n";
