<?php
/**
 * Seed v4.4.0 — Marketplace de Integraciones
 * Idempotente: salta si ya existe entry con esta version.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V44') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$entry = [
    'version' => 'v4.4.0',
    'release_type' => 'minor',
    'title' => 'Marketplace de Integraciones · Conecta Kydesk con tus herramientas',
    'summary' => 'Nuevo marketplace de integraciones con 12 proveedores listos: Slack, Discord, Telegram, Teams, Zapier, n8n, Make, Webhook genérico, Email, Pushover, Mattermost y Rocket.Chat. Disponible desde el plan Pro.',
    'hero_pill_label' => 'Integraciones · 12 proveedores listos',
    'is_featured' => 1,
    'is_published' => 1,
    'published_at' => date('Y-m-d H:i:s'),
    'items' => [
        ['feature', 'Nuevo marketplace de integraciones en /t/{slug}/integrations · disponible desde Pro'],
        ['feature', 'Slack: notifica a un canal vía Incoming Webhook con attachments coloreados por tipo de evento'],
        ['feature', 'Discord: embeds ricos con color int, fields y timestamp'],
        ['feature', 'Telegram: bot con sendMessage HTML · soporta canales, grupos y chats privados'],
        ['feature', 'Microsoft Teams: MessageCard adaptativa con facts y theme color'],
        ['feature', 'Zapier · n8n · Make: webhooks genéricos con auth header opcional'],
        ['feature', 'Webhook genérico: POST/PUT/PATCH JSON con firma HMAC-SHA256 opcional'],
        ['feature', 'Email forwarding: reenvía eventos a un email vía Resend con HTML branded'],
        ['feature', 'Pushover: push notifications a tu móvil con prioridad configurable'],
        ['feature', 'Mattermost · Rocket.Chat: payload compatible con Slack'],
        ['feature', 'Configuración 100% UI: cada proveedor define su propio config schema (URL, token, etc.)'],
        ['feature', 'Selector de eventos: marca cuáles disparan la integración (ticket.created, sla.breach, etc.)'],
        ['feature', 'Botón "Probar" envía un ping de test y muestra latencia + status code'],
        ['feature', 'Logs por integración: últimos 30 envíos con HTTP status, latencia y excerpt de respuesta'],
        ['feature', 'Auto-cap de logs por integración a 200 entradas (cleanup automático)'],
        ['feature', 'Toggle activar/pausar sin perder configuración'],
        ['feature', 'Counters de éxitos/errores y last_event_at por integración'],
        ['feature', 'Super admin /admin/integration-limits: configura por plan max integraciones + proveedores permitidos'],
        ['feature', 'Defaults: Pro=5 integraciones (chat+webhook+email) · Business=15 (+automation) · Enterprise=999 (todos)'],
        ['feature', 'Gating en cascada: feature flag por plan · provider whitelist por plan · count limit por plan'],
        ['feature', 'Banner de "límite alcanzado" cuando el tenant llega al máximo de su plan'],
        ['feature', 'Dispatcher hookeado a Events::emit() · cada acción del sistema dispara integraciones automáticamente'],
        ['feature', 'Filtro por categorías en marketplace: Chat, Automatización, DevOps, Notificaciones'],
        ['feature', '5 nuevos permisos: integrations.view/install/edit/delete/test'],
        ['improvement', 'Sidebar tenant: nuevo item "Integraciones" en sección Administración (visible en PRO+)'],
        ['improvement', 'Sidebar admin: nuevo item "Integraciones" en sección Sistema'],
        ['improvement', 'Audit log integrado: integration.installed/updated/toggled/deleted'],
        ['improvement', 'Esquema: integrations + integration_logs · indexed por tenant_id, integration_id, status'],
        ['improvement', 'Passwords/tokens en config: campos password no se reenvían al UI · "deja vacío para mantener"'],
    ],
];

$exists = $pdo->prepare('SELECT id FROM changelog_entries WHERE version = ?');
$exists->execute([$entry['version']]);
if ($row = $exists->fetch(PDO::FETCH_ASSOC)) {
    echo "  · {$entry['version']} ya existe (id={$row['id']}) — saltando\n";
    exit(0);
}

$items = $entry['items'];
unset($entry['items']);
$entry['created_by'] = (int)$pdo->query("SELECT id FROM super_admins ORDER BY id LIMIT 1")->fetchColumn() ?: null;

$cols = array_keys($entry);
$placeholders = array_map(fn($c) => ":$c", $cols);
$sql = "INSERT INTO changelog_entries (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
$stmt = $pdo->prepare($sql);
$stmt->execute($entry);
$entryId = (int)$pdo->lastInsertId();

$itemStmt = $pdo->prepare("INSERT INTO changelog_items (entry_id, item_type, text, sort_order) VALUES (?,?,?,?)");
foreach ($items as $i => [$type, $text]) {
    $itemStmt->execute([$entryId, $type, $text, $i]);
}
echo "  ✓ {$entry['version']} (#$entryId) · " . count($items) . " items\n";

$pdo->exec("UPDATE changelog_entries SET is_featured=0 WHERE id <> $entryId");
echo "  ✓ v4.4.0 marcada como featured\n";

echo "\n✓ DONE\n";
