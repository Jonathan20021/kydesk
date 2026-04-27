<?php
/**
 * Seed v4.5.0 — Igualas (Retainers) + Control de Módulos por Tenant
 * Idempotente: salta si ya existe entry con esta version.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V45') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$entry = [
    'version' => 'v4.5.0',
    'release_type' => 'minor',
    'title' => 'Igualas configurables · Soporte TI, Dev, Sistemas y más',
    'summary' => 'Igualas (contratos recurrentes) configurables: 11 categorías, items por línea, 10 plantillas, períodos con consumos. Business+Enterprise. Más: super admin activa/desactiva módulos por tenant.',
    'hero_pill_label' => 'Igualas configurables · 11 categorías + 10 plantillas',
    'is_featured' => 1,
    'is_published' => 1,
    'published_at' => date('Y-m-d H:i:s'),
    'items' => [
        ['feature', 'Nuevo módulo Igualas (Retainers) en /t/{slug}/retainers · disponible en Business y Enterprise'],
        ['feature', 'Soporta empresas y clientes individuales (persona física) en el mismo módulo'],
        ['feature', 'Categorías de servicio configurables desde la UI: Soporte TI, Desarrollo de software, Sistemas, Cloud & DevOps, Ciberseguridad, Consultoría, Marketing, Legal, Contable, Mantenimiento web, Licencias SaaS'],
        ['feature', 'Cada categoría con icono + color + unidad por defecto (horas, tickets, usuarios, licencias, proyectos, meses, custom)'],
        ['feature', 'Items por línea en cada iguala con título, categoría, cantidad, unidad, tarifa, importe, recurrente/único y facturable/no'],
        ['feature', 'Cálculo automático de importe por línea (qty × tarifa) y total recurrente facturable en tiempo real'],
        ['feature', 'Plantillas reutilizables con items pre-cargados — 10 plantillas seed: Soporte TI Básico/Premium, Dev Sprint/Maintenance, Cloud Ops 24/7, Pentest mensual, Consultoría Senior, Marketing 360, Legal Corp, Contable'],
        ['feature', 'Builder de plantilla "1-click": crear iguala desde plantilla pre-llena formulario + items'],
        ['feature', 'Filtro de igualas por categoría con pills de color, búsqueda por código/nombre/cliente y filtro por estado'],
        ['feature', 'Ciclos de facturación: mensual, trimestral, anual'],
        ['feature', 'Horas y tickets incluidos por período, tarifa de excedente configurable'],
        ['feature', 'Impuesto (%) y términos de pago configurables por iguala'],
        ['feature', 'SLA opcional por contrato (tiempo de respuesta + resolución en minutos)'],
        ['feature', 'Apertura automática del primer período al activar la iguala'],
        ['feature', 'Cierre manual de período con avance automático al siguiente si auto_renew=1'],
        ['feature', 'Registro de consumos asociado a tickets · cálculo automático de horas consumidas y excedente'],
        ['feature', 'Visualización de progreso del período actual con barra y alerta si supera horas incluidas'],
        ['feature', 'Códigos auto-generados IGL-XXXXX únicos por tenant'],
        ['feature', 'Control de módulos por tenant en /admin/tenants/{id}/modules · super admin habilita/deshabilita features individualmente'],
        ['feature', 'Tres estados por módulo: Heredar (del plan), Activar (override on), Desactivar (override off) · con campo de motivo para auditoría'],
        ['feature', 'Override de módulos respeta plan base pero permite excepciones — ej: dar Igualas a un Pro específico, quitar Integraciones a un Business'],
        ['feature', 'Catálogo de 17 módulos clasificados por nivel: core, pro, business, enterprise'],
        ['feature', '6 nuevos permisos: retainers.view/create/edit/delete/bill/config'],
        ['improvement', 'Sidebar tenant: nuevo item "Igualas" en sección Gestión · oculto si el plan no lo incluye'],
        ['improvement', 'Vista de detalle con tabs: Items, Consumos, Períodos, Editar'],
        ['improvement', 'Editor visual de items con Alpine.js: agregar/quitar líneas y cálculo en vivo'],
        ['improvement', 'Vista admin de tenant con botón directo "Gestionar módulos" + columna en listado'],
        ['improvement', 'Vista de upsell extendida con tarjeta dedicada para "Igualas" cuando un Pro intenta acceder'],
        ['improvement', 'Esquema: 6 nuevas tablas (retainers, retainer_periods, retainer_consumptions, retainer_categories, retainer_items, retainer_templates, retainer_template_items, tenant_module_overrides)'],
        ['improvement', '11 categorías + 10 plantillas seedadas automáticamente en cada tenant existente al correr la migración'],
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
echo "  ✓ v4.5.0 marcada como featured\n";

echo "\n✓ DONE\n";
