<?php
/**
 * Token tracking en ai_settings.
 *
 *   - Agrega tokens_in_this_month y tokens_out_this_month
 *   - Permite ver consumo real de tokens (in/out separados) además del contador de requests
 *   - El enforcement de cuota sigue usando monthly_quota vs used_this_month (requests)
 *
 * CLI:  php database/migrate_ai_token_tracking.php
 * Web:  /database/migrate_ai_token_tracking.php?token=KYDESK_AI_TOKENS
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_AI_TOKENS') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

function aitkCol(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function aitkAdd(PDO $p, string $t, string $c, string $sql): void {
    if (!aitkCol($p, $t, $c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n";
}

aitkAdd($pdo, 'ai_settings', 'tokens_in_this_month',  "ALTER TABLE ai_settings ADD COLUMN tokens_in_this_month BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER used_this_month");
aitkAdd($pdo, 'ai_settings', 'tokens_out_this_month', "ALTER TABLE ai_settings ADD COLUMN tokens_out_this_month BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER tokens_in_this_month");
aitkAdd($pdo, 'ai_settings', 'token_quota_monthly',   "ALTER TABLE ai_settings ADD COLUMN token_quota_monthly BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER tokens_out_this_month");

// Para los tenants existentes ya activos, calcular tokens_used desde ai_completions del mes en curso
echo "\n→ Backfill tokens_in/out con ai_completions del mes en curso...\n";
$rows = $pdo->query("
    SELECT tenant_id,
           SUM(IFNULL(tokens_in,0))  AS tin,
           SUM(IFNULL(tokens_out,0)) AS tout
    FROM ai_completions
    WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') AND status='ok'
    GROUP BY tenant_id
")->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
$st = $pdo->prepare("UPDATE ai_settings SET tokens_in_this_month = ?, tokens_out_this_month = ? WHERE tenant_id = ?");
foreach ($rows as $r) {
    $st->execute([(int)$r['tin'], (int)$r['tout'], (int)$r['tenant_id']]);
    if ($st->rowCount() > 0) {
        $updated++;
        echo "  + tenant_id={$r['tenant_id']}: in=" . (int)$r['tin'] . " out=" . (int)$r['tout'] . "\n";
    }
}
echo "  · $updated tenants actualizados\n";

echo "\n✓ Migración token tracking completa.\n";
