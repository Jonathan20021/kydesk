<?php
/**
 * Seed v4.9.0 — CRM avanzado: leads, oportunidades, pipelines y conversión a clientes.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V49') { http_response_code(403); exit('forbidden'); }
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

$entry = [
    'version'         => 'v4.9.0',
    'release_type'    => 'major',
    'title'           => 'CRM avanzado · gestión de leads, oportunidades y conversión de clientes',
    'summary'         => 'Pipeline kanban con drag & drop · oportunidades con probabilidad · actividades, notas, tags y scoring · convertí leads a clientes (con empresa + portal) en un click · 9 pipelines, 30 orígenes y 18 tags pre-cargados.',
    'hero_pill_label' => 'v4.9 · CRM avanzado de leads y clientes',
    'is_featured'     => 1,
    'is_published'    => 1,
    'published_at'    => date('Y-m-d H:i:s'),
    'items' => [
        // CRM core
        ['feature', 'Módulo CRM completo (Business / Enterprise) · gestión integral del ciclo comercial · leads, oportunidades, actividades, notas y conversión a clientes en una sola pantalla'],
        ['feature', 'Pipeline kanban con drag & drop · arrastrá tarjetas entre etapas y la probabilidad / estado se actualiza automáticamente · etapas marcadas Won/Lost cierran la oportunidad'],
        ['feature', 'Múltiples pipelines por tenant · seed automático de 3 pipelines (Ventas, Onboarding, Renovaciones) con 17 etapas pre-configuradas y probabilidades calibradas'],
        ['feature', 'Lead scoring 0-100 + rating Frío/Tibio/Caliente · estados (Nuevo, Contactado, Calificado, Propuesta, Negociación, Cliente, Perdido, Archivado) con badges de color en toda la UI'],

        // Activities & follow-ups
        ['feature', 'Actividades del lead (llamadas, emails, reuniones, tareas, WhatsApp, SMS) con scheduled_at · marcado completed/no_answer/rescheduled · alerta visual de actividades vencidas'],
        ['feature', 'Notas internas con autor, timestamp y opción de "fijar arriba" · timeline cronológico por lead · independiente del campo Notas del perfil'],
        ['feature', 'Próximo follow-up por lead con datetime · dashboard muestra leads con follow-ups vencidos y top hot leads del momento'],

        // Sources & tagging
        ['feature', 'Orígenes de lead configurables (Web, Referido, Ads, LinkedIn, Cold call, Cold email, Evento, Partner, Formulario web, WhatsApp) · seed de 10 orígenes por tenant'],
        ['feature', 'Tags multi-color por lead · seed de 6 tags útiles (Enterprise, SMB, VIP, Riesgo de fuga, Upsell, Hot lead) · filtros de listado por tag'],

        // Deals
        ['feature', 'Oportunidades por lead con título, monto, moneda, probabilidad heredada de la etapa, fecha esperada de cierre y descripción · razón de pérdida cuando se cae'],
        ['feature', 'Edición inline de oportunidad desde el detalle del lead · cambio de etapa propaga la probabilidad · won_at/lost_at/actual_close_on se setean automáticamente'],

        // Conversion
        ['feature', 'Convertí lead a cliente con un click · checkbox opcional para crear automáticamente la empresa en el módulo Companies y el usuario en Portal Cliente con contraseña temporal'],
        ['feature', 'Vinculación con tickets existentes por email del lead · histórico cruzado en la ficha · base para reportes 360 del cliente'],

        // Permissions & plan gating
        ['feature', 'Permisos granulares crm.view / create / edit / delete / config / assign / convert · auto-asignados a roles owner y admin · agentes con view+edit+create por defecto'],
        ['feature', 'Módulo gateado por plan Business/Enterprise · super admin puede activar/desactivar CRM por tenant desde /admin/tenants/{id}/modules sin tocar el plan'],

        // UX
        ['feature', 'Dashboard del CRM con 4 KPIs (leads totales, abiertos, clientes, valor del pipeline), distribución por estado, top orígenes, próximas actividades y hot leads del día'],
        ['feature', 'Listado de leads con filtros combinados (búsqueda, estado, rating, origen, owner, tag) · pills coloreados para tags · ordenamiento por creación con cap de 300 resultados'],

        // Infra
        ['improvement', '9 tablas nuevas (crm_pipelines, crm_stages, crm_sources, crm_leads, crm_deals, crm_activities, crm_notes, crm_tags, crm_lead_tags) · migración idempotente con re-ejecución segura'],
        ['improvement', 'Códigos de lead auto-generados con formato LD-00001 secuencial por tenant · únicos a nivel UNIQUE KEY (tenant_id, code) sin colisión bajo carga'],
        ['improvement', 'Endpoint POST /crm/deals/{id}/move responde JSON · soporta drag & drop sin recargar (con auto-reload tras éxito) · CSRF validado'],
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
