<?php
/**
 * Seed v4.3.0 — Departamentos (PRO+)
 * Idempotente: salta si ya existe entry con esta version.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V43') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$entry = [
    'version' => 'v4.3.0',
    'release_type' => 'minor',
    'title' => 'Departamentos · Organiza tu equipo y enruta tickets automáticamente',
    'summary' => 'Módulo completo de departamentos (PRO+) con asignación de agentes, enrutamiento automático, SLAs por área y reportes independientes.',
    'hero_pill_label' => 'Departamentos PRO · disponibles ahora',
    'is_featured' => 1,
    'is_published' => 1,
    'published_at' => date('Y-m-d H:i:s'),
    'items' => [
        ['feature', 'Nueva sección /t/{slug}/departments en el panel del tenant — disponible desde el plan Pro'],
        ['feature', 'CRUD completo de departamentos: nombre, color, icono, descripción, email, líder y orden'],
        ['feature', 'Asignación de agentes (técnicos) por departamento · pivote many-to-many con flag de líder'],
        ['feature', 'Manager/líder configurable que recibe la asignación automática de tickets nuevos'],
        ['feature', 'Selector de departamento al crear ticket · auto-asigna al líder del depto si no hay técnico'],
        ['feature', 'Filtro de departamento en /tickets · listado de tickets muestra badge del depto'],
        ['feature', 'Cambio de departamento desde la vista de detalle del ticket'],
        ['feature', 'Automations: nueva condición "departamento" + acción "enrutar a departamento"'],
        ['feature', 'SLAs por departamento · políticas globales o específicas para áreas concretas'],
        ['feature', 'Reportes: nuevo panel "Desempeño por departamento" con volumen, brechas SLA y tasa de resolución'],
        ['feature', 'Detalle del departamento muestra agentes, tickets recientes, stats y SLAs propios'],
        ['feature', '4 departamentos por defecto sembrados en cada tenant: Soporte Técnico, Ventas, Facturación, RRHH'],
        ['feature', '5 nuevos permisos: departments.view/create/edit/delete/assign · auto-otorgados al rol owner'],
        ['feature', 'Gating PRO: starter/free ven la pantalla de upsell con CTA al pricing'],
        ['improvement', 'Sidebar tenant: nuevo item "Departamentos" en la sección Gestión (visible solo en PRO+)'],
        ['improvement', 'Audit log integrado: department.created/updated/deleted/agent_added/agent_removed'],
        ['improvement', 'Esquema: departments + department_users (pivote) + tickets.department_id + sla_policies.department_id'],
        ['improvement', 'Slugs únicos por tenant para departamentos · auto-generados con deduplicación numérica'],
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

// Despublicar el featured anterior (debe ser solo uno)
$pdo->exec("UPDATE changelog_entries SET is_featured=0 WHERE id <> $entryId");
echo "  ✓ v4.3.0 marcada como featured\n";

echo "\n✓ DONE\n";
