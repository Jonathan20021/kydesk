<?php
/**
 * Seed v4.6.0 — La actualización más grande hasta ahora
 *   10 módulos nuevos + IA centralizada Enterprise
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V46') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$entry = [
    'version' => 'v4.6.0',
    'release_type' => 'major',
    'title' => '10 módulos nuevos · La actualización más grande hasta ahora',
    'summary' => 'Email-to-ticket inbound · Live Chat · Kyros IA · CSAT/NPS · ITSM · Time Tracking · Status Page · Customer Portal · Reports Builder · Custom Fields. Enterprise full-stack helpdesk en una sola release.',
    'hero_pill_label' => '10 módulos nuevos · v4.6 mayor release',
    'is_featured' => 1,
    'is_published' => 1,
    'published_at' => date('Y-m-d H:i:s'),
    'items' => [
        // EMAIL INBOUND
        ['feature', 'Email-to-Ticket inbound: convertí emails en tickets automáticamente vía IMAP o forward webhook. Disponible en Pro+'],
        ['feature', 'IMAP fetcher con extracción de body multipart, threading por Message-ID/In-Reply-To, dedupe automático'],
        ['feature', 'Forward webhook universal compatible con Mailgun, Postmark, SendGrid, n8n, Zapier — header x-forward-token con HMAC-style auth'],
        ['feature', 'Auto-asignación de empresa por dominio del email del solicitante'],
        ['feature', 'Categoría y prioridad por defecto configurables por buzón, auto-assign opcional a un agente'],

        // LIVE CHAT
        ['feature', 'Live Chat / Messenger: widget embebible 1-line para tu sitio web. Disponible en Business+'],
        ['feature', 'Snippet único por widget: <script async src="...kydesk.../chat-widget/{key}.js"></script>'],
        ['feature', 'Visitor JS auto-contenido con bubble flotante, panel responsive, persistencia con localStorage'],
        ['feature', 'Agent inbox con polling en tiempo real, asignación, conversión 1-click a ticket'],
        ['feature', 'Color, mensaje de bienvenida, requerir email, orígenes CORS — todo configurable desde la UI'],

        // AI ASSISTANT (Enterprise centralized)
        ['feature', 'Kyros IA — solo plan Enterprise · infraestructura gestionada centralmente por el equipo de Kydesk'],
        ['feature', '5 acciones IA: sugerir respuesta, resumir hilo, auto-categorizar, detectar sentiment, traducir'],
        ['feature', 'Super admin: panel /admin/ai con configuración global de provider/API key/modelo, asignación explícita por tenant, control de cuota'],
        ['feature', 'Tenant Enterprise: solo toggle qué acciones usar — sin manejar API keys ni billing del proveedor'],
        ['feature', 'Cuota mensual por tenant con barra visual, kill-switch global, log cross-tenant de completions'],
        ['feature', 'Resumen de IA y sentiment se persisten en columnas tickets.ai_summary y tickets.ai_sentiment'],

        // CSAT / NPS
        ['feature', 'CSAT (1-5 emojis) y NPS (0-10) post-resolución de tickets · disponible en todos los planes'],
        ['feature', 'Auto-trigger configurable al cerrar tickets en estado resolved'],
        ['feature', 'Encuesta pública en /csat/{token} con UI mobile-first, branding del tenant'],
        ['feature', 'Dashboard con NPS Score (promotores - detractores), % satisfechos CSAT, promedio, distribución'],
        ['feature', 'Hook automático en TicketController::update() y move() — si el tenant habilitó, dispara encuesta al resolver'],

        // ITSM
        ['feature', 'ITSM completo (ITIL): Service Catalog · Change Management · Problem Management · Approvals · disponible en Business+'],
        ['feature', 'Service Catalog: items con SLA propio, categoría, departamento, aprobador específico'],
        ['feature', 'Change Requests: tipos standard/normal/emergency, riesgo/impacto low/medium/high, plan de rollback y testing, fechas planeadas'],
        ['feature', 'Multi-step approvals: todos los aprobadores deben aprobar para que un Change pase a estado "approved"'],
        ['feature', 'Problems con root cause, workaround y estado known_error (siguiendo ITIL v4)'],
        ['feature', 'Códigos auto-generados CHG-XXXXX y PRB-XXXXX únicos por tenant'],

        // TIME TRACKING
        ['feature', 'Time Tracking integrado a Igualas · disponible en Pro+'],
        ['feature', 'Timer en vivo con cronómetro x-data Alpine, 1 timer running por usuario, auto-stop si abrís otro'],
        ['feature', 'Manual entry con horas, fecha de inicio, tarifa, facturable/no'],
        ['feature', 'Auto-detecta iguala activa de la empresa del ticket y descuenta horas del período abierto'],
        ['feature', 'Recalcula consumed_hours y overage_amount del período automáticamente al stop'],
        ['feature', 'Filtros por usuario, rango de fechas, facturable/no — KPIs de horas, billable, monto'],

        // STATUS PAGE
        ['feature', 'Status Page pública en /status/{slug} · disponible en todos los planes'],
        ['feature', 'Componentes con 5 estados: operational/degraded/partial_outage/major_outage/maintenance'],
        ['feature', 'Incidentes con severidad minor/major/critical/maintenance y timeline de updates investigando→identified→monitoring→resolved'],
        ['feature', 'Suscripción por email con confirmación double-opt-in y notificaciones automáticas en cada update'],
        ['feature', 'Estado general calculado automáticamente del peor estado de los componentes'],
        ['feature', 'Historial público de incidentes resueltos (últimos 12)'],

        // CUSTOMER PORTAL LOGIN
        ['feature', 'Customer Portal con login autenticado en /portal/{slug}/{login,register,account}'],
        ['feature', 'Sistema de auth completo: registro, verificación email, login, forgot/reset password, perfil'],
        ['feature', 'Cliente ve histórico autenticado de SUS tickets (filtra por portal_user_id o requester_email)'],
        ['feature', 'Vinculación opcional con company al crear cuenta'],
        ['feature', 'Super tenant: gestión /t/{slug}/portal-users con activar/desactivar/eliminar usuarios del portal'],

        // REPORTS BUILDER
        ['feature', 'Reports Builder con 10 widgets pre-construidos · disponible en Business+'],
        ['feature', 'Widgets: tickets por estado/prioridad/categoría/agente/día, tiempo medio resolución, cumplimiento SLA, tickets abiertos, CSAT score, top 10 empresas'],
        ['feature', 'Builder Alpine.js con add/remove/reorder de widgets'],
        ['feature', 'Filtros guardados (rango de fechas) por reporte'],
        ['feature', 'Marcar como favorito, compartir con el equipo, scheduled emails (config inicial)'],

        // CUSTOM FIELDS
        ['feature', 'Custom Fields por categoría · disponible en todos los planes'],
        ['feature', '10 tipos: text, textarea, number, date, select, multiselect, checkbox, url, email, phone'],
        ['feature', 'Globales (todos los tickets) o filtrados por categoría específica'],
        ['feature', 'Required, visible en portal, orden, opciones (para select/multiselect), placeholder, help_text — todo configurable'],
        ['feature', 'Helpers estáticos fieldsFor() / valuesFor() / saveValues() para integración con tickets'],

        // INFRA / IMPROVEMENTS
        ['improvement', 'Sidebar tenant: 9 items nuevos (Igualas, Time Tracking, Live Chat, Reports Builder, Email-to-Ticket, IA, ITSM, CSAT/NPS, Status Page, Custom Fields, Usuarios Portal)'],
        ['improvement', 'Sidebar admin: nuevo item "IA Asistente" en sección Sistema con panel de configuración global y asignación por tenant'],
        ['improvement', 'admin-* CSS classes (admin-tabs, admin-table, admin-pill, admin-card, admin-btn) ahora globales en app.css y disponibles en tenant views'],
        ['improvement', 'Plan::FEATURES y MODULE_CATALOG actualizados con 13 nuevas entradas tier-aware'],
        ['improvement', '20 permisos nuevos seedeados a roles owner+admin (custom_fields.*, csat.*, status.*, portal.manage, time.*, email.*, chat.*, ai.*, itsm.*, reports.builder)'],
        ['improvement', 'Migración v5 idempotente con 16 tablas nuevas + columnas extra en tickets'],
        ['improvement', 'Migración ai_centralized convierte ai_settings a modelo de asignación super-admin-controlado'],
        ['improvement', 'AI quota cascade: kill switch global → asignación tenant → enabled tenant → API key disponible → cuota no agotada'],
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
echo "  ✓ v4.6.0 marcada como featured\n";

echo "\n✓ DONE\n";
