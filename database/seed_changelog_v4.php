<?php
/**
 * Seed entradas del changelog para todo el trabajo de v4.x.
 * Idempotente: salta si ya existe una entry con la misma version.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V4') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected to {$cfg['name']}\n\n";

$entries = [
    // ─────────────────── v4.0.0 — Developer Portal ───────────────────
    [
        'version' => 'v4.0.0',
        'release_type' => 'major',
        'title' => 'Developer Portal · Construye apps con la API de Kydesk',
        'summary' => 'Portal completo para developers con planes, apps aisladas, tokens API y billing — todo administrable desde super admin.',
        'hero_pill_label' => null,
        'is_featured' => 0,
        'is_published' => 1,
        'published_at' => '2026-04-26 10:00:00',
        'items' => [
            ['feature', 'Nuevo portal externo /developers · separado del helpdesk de clientes con su propio dashboard, billing y panel'],
            ['feature', 'Cuentas de developer con login/registro y verificación opcional por email'],
            ['feature', '5 planes API públicos: Free (10K req/mes), Starter $19, Growth $49, Scale $149, Enterprise $499'],
            ['feature', 'Cada app del developer obtiene su propio tenant aislado · sin contaminación entre proyectos'],
            ['feature', 'Bearer tokens API con scopes (read/write/*) · revocables · preview de últimas letras'],
            ['feature', 'Suscripciones, facturas y pagos para developers — flujo completo con super admin'],
            ['feature', 'Panel super admin: Developers, Apps, Planes Dev, Suscripciones, Facturas, Pagos · CRUD total'],
            ['feature', 'Dashboard super admin con KPIs de developer portal: MRR, requests MTD, top developers'],
            ['feature', 'Tenants sandbox de developers ocultos del listado de empresas regulares'],
        ],
    ],

    // ─────────────────── v4.1.0 — API completa + Plan enforcement ───────────────────
    [
        'version' => 'v4.1.0',
        'release_type' => 'minor',
        'title' => 'La mejor API REST · OpenAPI, paginación, idempotencia y enforcement',
        'summary' => 'Refactor completo de la API a una arquitectura modular con 45+ endpoints, OpenAPI 3.1, Postman, headers DX y enforcement de cuotas en tiempo real.',
        'hero_pill_label' => null,
        'is_featured' => 0,
        'is_published' => 1,
        'published_at' => '2026-04-26 13:00:00',
        'items' => [
            ['feature', 'BaseApiController modular con paginación uniforme, sort, expand, fields y idempotencia'],
            ['feature', 'CRUD completo en Tickets, Companies, Categories, Users, KB, SLA, Automations, Assets'],
            ['feature', 'Sub-recursos: comments, escalate, assign, batch (50 ops/request)'],
            ['feature', 'Búsqueda global GET /api/v1/search?q= cross-resource'],
            ['feature', 'Health check público GET /api/v1/health · sin auth'],
            ['feature', 'OpenAPI 3.1 spec auto-generado en /api/v1/openapi.json'],
            ['feature', 'Postman collection descargable en /api/v1/postman.json'],
            ['feature', 'Idempotency-Key header en POSTs · respuesta cacheada 24h'],
            ['feature', 'Headers DX: X-API-Version, X-Request-Id, X-RateLimit-Limit, X-Quota-Used/Limit/Pct'],
            ['feature', 'ETag + If-Match para concurrency control'],
            ['feature', 'Plan enforcement en tiempo real: rate limit por minuto, cuota mensual, status de suscripción'],
            ['feature', 'Overrides de cuota por developer — super admin sobrescribe límites del plan'],
            ['feature', 'Per-request audit log granular (dev_api_request_log) con method/path/status/latencia'],
            ['feature', 'CSV exports: /tickets.csv, /companies.csv, /users.csv'],
            ['feature', 'AI Studio: 8 modelos (Claude, GPT, Gemini, Codex, Cursor, Copilot, Continue, Cline)'],
            ['feature', 'Generadores: system prompt builder, .cursorrules, AI digest, MCP config'],
            ['feature', 'Documentación interactiva con sidebar de navegación + tabs por lenguaje'],
            ['feature', 'API Console (try-it inline) con cURL builder · token persistido'],
            ['improvement', 'Errores con formato consistente { error: { type, message, status, request_id } }'],
            ['improvement', 'CORS preflight + 11 headers expuestos para debugging desde browsers'],
        ],
    ],

    // ─────────────────── v4.2.0 — Webhooks, AI Chat, Email Resend ───────────────────
    [
        'version' => 'v4.2.0',
        'release_type' => 'minor',
        'title' => 'Webhooks reales, AI Chat con tu workspace y emails con Resend',
        'summary' => 'Event bus con webhooks firmados HMAC, AI Chat browser-direct con tool calling, IP allowlist en tokens y 10 emails transaccionales vía Resend.',
        'hero_pill_label' => 'AI Chat + Webhooks · disponibles ahora',
        'is_featured' => 1,
        'is_published' => 1,
        'published_at' => '2026-04-26 17:00:00',
        'items' => [
            ['feature', 'Event bus real (App\Core\Events): cada acción de la API emite eventos a webhooks suscritos'],
            ['feature', '7 eventos cableados: ticket.created, ticket.updated, ticket.assigned, ticket.resolved, ticket.escalated, ticket.deleted, comment.created'],
            ['feature', 'Entregas firmadas con HMAC-SHA256 (X-Kydesk-Signature) + retries con backoff'],
            ['feature', 'Auto-deshabilitar webhook tras 10 fallos consecutivos · email automático al developer'],
            ['feature', 'Activity events stream: GET /api/v1/events/recent + Server-Sent Events /events/stream'],
            ['feature', 'AI Chat (/developers/ai/chat): chatea con tu workspace usando OpenAI/Anthropic/Google · tool calling con Kydesk · BYO key directo desde el browser'],
            ['feature', 'MCP config download para Claude Desktop, Continue, Cline'],
            ['feature', 'API Console: persistencia de token, historial de 25 requests, favoritos en DB, tabs Respuesta/Headers/Historial/Favoritos'],
            ['feature', 'Email verification + password reset flow completo con token de un solo uso'],
            ['feature', 'Token IP allowlist (CIDR soportado), expiración configurable, descripción de propósito'],
            ['feature', '10 emails transaccionales centralizados vía Resend con branding consistente'],
            ['feature', 'Welcome email tras registro · invoice/payment notifications · token security alert · webhook disabled · subscription change · quota warnings (80% y 100%, una vez por mes)'],
            ['feature', 'Super admin: ajustes del developer portal (toggles enforcement, alert %, branding) · auditoría cross-developer · webhook deliveries log'],
            ['feature', 'Banners contextuales: cuota al 80%/100%, email no verificado, suscripción en trial, pago pendiente'],
            ['improvement', 'DevMailer helper: todos los envíos pasan por Resend con logging en email_log y error_log en fallos'],
            ['improvement', 'Anti-spam de quota alerts: marcadores en developers.notes para no enviar más de una vez por mes'],
            ['improvement', 'Sidebar developer con barras de progreso live de cuota y rate limit'],
            ['improvement', 'Idempotency table api_idempotency con TTL 24h evita duplicados'],
            ['fix', 'Variable $e (escape helper) ya no se sobreescribe en views por catch (\\Throwable $e)'],
            ['fix', 'Enums TICKETS/COMPANIES/KB validan correctamente al crear (data truncated bug)'],
            ['fix', 'Paginación uniforme con meta.total y links navegables'],
        ],
    ],
];

foreach ($entries as $entry) {
    $exists = $pdo->prepare('SELECT id FROM changelog_entries WHERE version = ?');
    $exists->execute([$entry['version']]);
    if ($row = $exists->fetch(PDO::FETCH_ASSOC)) {
        echo "  · {$entry['version']} ya existe (id={$row['id']}) — saltando\n";
        continue;
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
}

// Asegurar que solo una entry está marcada como featured (la más reciente con flag)
$featured = $pdo->query("SELECT id, version FROM changelog_entries WHERE is_featured=1 ORDER BY published_at DESC")->fetchAll();
if (count($featured) > 1) {
    $keep = (int)$featured[0]['id'];
    $pdo->exec("UPDATE changelog_entries SET is_featured=0 WHERE id <> $keep");
    echo "\n  ⚠ Múltiples featured detectados — solo se mantiene v" . $featured[0]['version'] . "\n";
}

echo "\n══════════════════════════════════════\n";
echo "✓ CHANGELOG SEED COMPLETO\n";
echo "══════════════════════════════════════\n";
echo "Total entries: " . $pdo->query("SELECT COUNT(*) FROM changelog_entries")->fetchColumn() . "\n";
echo "Featured: " . ($pdo->query("SELECT version FROM changelog_entries WHERE is_featured=1 LIMIT 1")->fetchColumn() ?: 'ninguna') . "\n";
