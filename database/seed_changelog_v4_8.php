<?php
/**
 * Seed v4.8.0 — Video conferencia y llamadas integradas en el módulo de reuniones.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V48') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$entry = [
    'version' => 'v4.8.0',
    'release_type' => 'major',
    'title' => 'Video conferencia y audio integrados en la agenda',
    'summary' => 'Cada reserva auto-genera un room embebido en el panel · Jitsi gratis para empezar, 8x8.vc/JaaS o LiveKit con un toggle · host y cliente entran desde el mismo lugar sin instalar nada · Kyros IA opcional para análisis automático y briefings.',
    'hero_pill_label' => 'v4.8 · Video conferencia + audio embebido',
    'is_featured' => 1,
    'is_published' => 1,
    'published_at' => date('Y-m-d H:i:s'),
    'items' => [
        // Video conferencia
        ['feature', 'Video conferencia integrada en el panel · auto-genera un room por reunión virtual o llamada de audio · cliente y host se conectan desde el mismo lugar sin instalar nada'],
        ['feature', 'Provider Jitsi Meet por defecto (gratis · meet.jit.si para testing) · auto-fallback a "abrir en pestaña nueva" cuando el embed gratuito tiene restricciones'],
        ['feature', 'Soporte completo para Jitsi as a Service (8x8.vc) · firma RS256 con clave RSA · header kid (appId/keyId) · sin restricciones de embed · tier gratuito hasta 25 usuarios/mes'],
        ['feature', 'Soporte para Jitsi self-hosted con shared secret HS256 · pegás tu dominio + secret en ajustes y listo'],
        ['feature', 'LiveKit en BETA · access tokens RS256 firmados ya implementados · cambio de provider con un toggle cuando se quiera activar SDK + grabación'],
        ['feature', 'Capa de abstracción Provider con factory pattern · Jitsi/LiveKit/futuros providers detrás de la misma interfaz · cero cambios en controllers/vistas al migrar'],

        // Embed UX
        ['feature', 'Card oscura con CTA "Iniciar conferencia" en el detalle de la reunión (panel host) · embed inline 600px alto · botón "Salir y minimizar" deja la reunión sin recargar'],
        ['feature', 'Botón "Entrar a la reunión" en la página de gestión pública del cliente · habilitado solo entre 15 min antes y 30 min después · disabled gracefully fuera de ese rango'],
        ['feature', 'Email de confirmación con CTA gigante "Entrar a la videoconferencia" · gradient violeta-oscuro · click directo desde el inbox del cliente'],
        ['feature', 'ICS calendar invite descargable · funciona con Google Calendar / Outlook / Apple Calendar · incluye el meeting URL en el campo location'],
        ['feature', 'Detección automática de modo audio-only · location_type=phone inicia con cámara apagada y oculta toggle de cámara'],

        // Setup wizard
        ['feature', 'Wizard guiado de 4 pasos para configurar 8x8.vc · enlace directo a jaas.8x8.vc/start-guide · auto-detección de App ID JaaS (vpaas-magic-cookie-) → switch automático del dominio a 8x8.vc'],
        ['feature', 'Botón "Probar configuración" sin guardar · firma un JWT de test con las credenciales pegadas · valida algoritmo (HS256/RS256), kid, embed mode · preview del token resultante'],
        ['feature', 'Validación server-side: si el App ID es JaaS pero falta el kid o la private key, bloquea el guardado con mensaje explicativo · evita "Authentication failed" en producción'],
        ['feature', 'Refresh automático de meeting_url para reservas futuras cuando cambia el dominio o provider · sin re-crear las reuniones'],

        // Auto-room generation
        ['feature', 'Booking público auto-genera el room al confirmar la reserva (location_type virtual o phone) · room_id determinístico desde el public_token (cripto-seguro · sin colisiones)'],
        ['feature', 'Manual booking desde el panel también auto-genera room cuando el host crea reuniones virtuales/audio sin URL custom'],

        // Infra
        ['improvement', 'Migración idempotente con 5 columnas en meetings (conference_provider, conference_room_id, conference_meta, conference_started_at, conference_ended_at) + 9 columnas en meeting_settings'],
        ['improvement', 'jitsi_app_secret y livekit_api_secret extendidos a TEXT (eran VARCHAR 255) · soporte para claves RSA 2048-bit que ocupan ~1700 chars'],
        ['improvement', 'Helper Jwt::signRS256 con openssl_sign · Jwt::looksLikePem para auto-detección de formato · header kid configurable'],
        ['improvement', 'Cache en memoria del provider por tenant en ConferenceFactory · clearCache automático al guardar settings'],
        ['fix', 'meet.jit.si gratis tenía corte de embed a los 5 min · ahora se detecta el dominio y forzamos "abrir en pestaña nueva" cuando no hay JWT configurado'],
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
echo "  ✓ {$entry['version']} marcada como featured\n";

echo "\n✓ DONE\n";
