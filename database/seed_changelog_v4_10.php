<?php
/**
 * Seed v4.10.0 — Cotizaciones profesionales con PDF, ITBIS configurable y branding propio.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V410') { http_response_code(403); exit('forbidden'); }
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
    'version'         => 'v4.10.0',
    'release_type'    => 'major',
    'title'           => 'Cotizaciones profesionales con PDF, ITBIS configurable y portal de aceptación',
    'summary'         => 'Generador completo · plantillas reutilizables · ITBIS/IVA/VAT configurables · descuentos por línea y global · PDF con tu logo · link público para que el cliente acepte online · conexión con CRM, Companies y Service Catalog.',
    'hero_pill_label' => 'v4.10 · Cotizaciones profesionales con PDF y branding',
    'is_featured'     => 1,
    'is_published'    => 1,
    'published_at'    => date('Y-m-d H:i:s'),
    'items' => [
        // Core generador
        ['feature', 'Módulo de Cotizaciones (Business / Enterprise) · cabecera + N líneas de items · cálculo en vivo de subtotal, descuentos, ITBIS, envío y otros cargos en JS sin recargar'],
        ['feature', 'Numeración automática configurable (prefijo + año + secuencial · ej: COT-2026-0001) · contador por tenant · próximo número editable desde la UI'],
        ['feature', 'Items con cantidad decimal (0.001), unidad (hora/unidad/licencia/servicio/proyecto/mes/custom), precio unitario, descuento por línea (%) y flag taxable individual'],
        ['feature', 'Descuento global (%) que se aplica proporcionalmente a la base imponible · sin afectar items exentos · cálculo igual al PDF para evitar discrepancias'],

        // Impuestos configurables
        ['feature', 'Impuestos 100% configurables desde la UI · seed inicial con ITBIS 18% (RD), IVA 21% (ES/AR), IVA 16% (MX), VAT 7% y Exento 0% · podés crear los que necesites con tasa decimal de hasta 3 dígitos'],
        ['feature', 'Switch rápido de impuesto desde el formulario · uno marcado como default se carga automáticamente · soporte para tax_rate personalizado fuera de los presets'],
        ['feature', 'Items con flag is_taxable independiente · útil para mezclar productos exentos (servicios profesionales, exportación) con productos gravados en la misma cotización'],

        // PDF profesional
        ['feature', 'PDF profesional con mPDF (mejor soporte CSS que dompdf) · header de cada página con tu logo, razón social, RNC, dirección, teléfono y email'],
        ['feature', 'Branding total: color primario y de acento configurables · aparecen en el badge de estado, eyebrow, total destacado y línea separadora del header'],
        ['feature', 'Subida de logo con validación (JPG/PNG/WebP/SVG) · se guarda en /public/uploads/quote_logos · path absoluto resuelto en runtime para que mPDF lo embeba correctamente'],
        ['feature', 'Tabla de items con número, descripción + descripción larga, cantidad con unidad, precio unitario, descuento %, marca de "exento de impuestos" y subtotal por línea con zebra rows'],
        ['feature', 'Bloque de totales con subtotal, descuento (rojo), impuesto con tasa, envío, otros cargos, y el GRAN TOTAL destacado en una caja color brand con tipografía monoespaciada'],
        ['feature', 'Sección de "Datos de pago" opcional (banco, cuenta, RNC) · "Notas internas" opcional · ambos como cards con borde lateral de color'],
        ['feature', 'Términos y condiciones al final · línea de firma con nombre y cargo configurables · si la cotización está aceptada, badge verde con quién y cuándo aceptó reemplaza la firma'],
        ['feature', 'Footer con texto personalizable y paginación "Página X de Y" · tipografía DejaVu Sans con soporte completo de UTF-8 (acentos, ñ, símbolos de moneda)'],

        // Portal cliente
        ['feature', 'Link público único por cotización (token de 32 hex chars · UNIQUE KEY) · cliente abre /q/{token} sin login · branding completo del tenant en la página'],
        ['feature', 'Cliente puede descargar el PDF, aceptar la cotización con su nombre + email firmado, o rechazarla con motivo opcional · todo se registra en quote_events'],
        ['feature', 'Auto-marcado de "vista" al primer load del cliente · timestamp en viewed_at · tracking de actor (agent/client/system) para cada evento'],
        ['feature', 'Email automático al equipo cuando el cliente acepta (configurable) · CTA al panel para arrancar la entrega · destinatario configurable por separado del support_email'],

        // Plantillas y reusabilidad
        ['feature', 'Plantillas de cotización con items prearmados, intro, términos y validez por defecto · cargá una plantilla y la cotización queda lista para enviar en segundos'],
        ['feature', 'Conexión con CRM: desde el detalle del lead, ?lead_id= prefija nombre, email, teléfono y dirección · conexión con Companies para autocompletar al elegir empresa registrada'],
        ['feature', 'Conexión con Service Catalog: botones de "cargar desde catálogo" agregan items con título y descripción del catálogo de servicios ITSM existente'],

        // Estados y workflow
        ['feature', 'Estados completos: borrador, enviada, vista, aceptada, rechazada, expirada, revisada, convertida · transiciones automáticas + cambio manual desde el panel'],
        ['feature', 'Auto-marcado de "expirada" cuando valid_until < hoy · al editar una expirada vuelve a "revisada" para indicar que se actualizó · ciclo limpio de re-envío'],
        ['feature', 'Duplicar cotización con un click · clona items, totales, intro y términos · genera código nuevo, token nuevo y arranca como borrador'],
        ['feature', 'Timeline de eventos (created, sent, viewed, accepted, rejected, pdf_downloaded, etc.) con actor_name, actor_email y meta JSON para auditoría completa'],

        // Permisos & gating
        ['improvement', 'Permisos granulares quotes.view / create / edit / delete / send / config · auto-asignados a owner y admin · agentes con view+create+edit+send'],
        ['improvement', 'Módulo gateado por plan Business / Enterprise · super admin puede activar/desactivar quotes por tenant desde /admin/tenants/{id}/modules sin tocar el plan'],

        // Infra
        ['improvement', '7 tablas nuevas (quote_settings, quote_taxes, quotes, quote_items, quote_templates, quote_template_items, quote_events) · migración idempotente con seed de impuestos y settings por tenant'],
        ['improvement', 'Cálculo de totales centralizado en QuoteController::computeTotals · misma fórmula en backend (PHP) y frontend (JS) · garantiza paridad entre lo que el agente ve y lo que se guarda'],
        ['improvement', 'Validación de status para edición (no se puede editar accepted/rejected/converted) · re-cálculo automático al actualizar items · borrado y re-inserción transaccional'],
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
