<?php
/**
 * Seed v4.7.0 — Módulo de Agenda de Reuniones (Calendly-style).
 *   Página pública para que clientes reserven reuniones, con control de
 *   tipos, disponibilidad semanal, días bloqueados, buffers, preguntas
 *   personalizadas, recordatorios y reprogramación/cancelación pública.
 *   Disponible en Business y Enterprise.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHL_V47') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected\n\n";

$entry = [
    'version' => 'v4.7.0',
    'release_type' => 'major',
    'title' => 'Agenda de reuniones · Página pública estilo Calendly',
    'summary' => 'Compartí un único enlace y dejá que clientes reserven reuniones con tu equipo. Control total de tipos, disponibilidad semanal, días bloqueados, buffers, preguntas personalizadas y reprogramación pública. Disponible en Business y Enterprise.',
    'hero_pill_label' => 'Nuevo · Agenda de reuniones',
    'is_featured' => 1,
    'is_published' => 1,
    'published_at' => date('Y-m-d H:i:s'),
    'items' => [
        ['feature', 'Página pública /book/{slug} estilo Calendly · branding propio (logo, color, mensajes) · multi-tipo de reunión'],
        ['feature', 'Selector de fecha/hora con calendario mensual responsive · slots calculados en tiempo real respetando disponibilidad, buffers, bloqueos y reuniones existentes'],
        ['feature', 'Tipos de reunión configurables: nombre, descripción, duración (5–480 min), color/ícono, ubicación (virtual/teléfono/presencial/custom), buffer antes/después, slot step'],
        ['feature', 'Política por tipo: aviso mínimo (horas), anticipación máxima (días), requiere confirmación manual, permitir cancelar/reprogramar al cliente, redirect post-reserva'],
        ['feature', 'Preguntas personalizadas por tipo con 5 formatos (texto, párrafo, número, teléfono, select con opciones) · obligatorias u opcionales'],
        ['feature', 'Disponibilidad semanal por host (usuario) con múltiples franjas por día · seed por defecto Lun–Vie 9–17 para todos los técnicos del tenant'],
        ['feature', 'Días bloqueados por host o globales · día completo o ventana parcial · motivo opcional (vacaciones, feriado, fuera de oficina)'],
        ['feature', 'Detección de conflictos automática: el motor de slots resta reuniones existentes, bloqueos, buffers y aviso mínimo'],
        ['feature', 'Email de confirmación al cliente con plantilla branded + ICS adjunto descargable + link único de gestión por reserva'],
        ['feature', 'Notificaciones internas al equipo (host + emails extra configurables) en cada nueva reserva o reprogramación'],
        ['feature', 'Página de gestión pública /book/{slug}/manage/{token}: ver detalles · cancelar con motivo · reprogramar con calendario nuevo'],
        ['feature', 'Detección automática de empresa por dominio del email (reusa el helper de tickets) · upsert de contacto con teléfono'],
        ['feature', 'Modo manual: agendá reuniones desde el panel sin necesidad de página pública (útil para llamadas espontáneas)'],
        ['feature', 'Dashboard interno con KPIs (hoy, próximas, este mes, total) + lista de próximas + tipos activos + recientes'],
        ['feature', 'Vistas adicionales: listado filtrable (estado, tipo, host, fechas, búsqueda) + calendario mensual con grilla 7×N'],
        ['feature', 'Estados granulares: agendada · confirmada · cancelada · completada · no-show · reprogramada'],
        ['improvement', '5 permisos nuevos: meetings.view (granted a agent/manager) + meetings.create/edit/delete/config (granted a owner/admin)'],
        ['improvement', '5 tipos seed por tenant: Demo 15min · Consulta 30min · Sesión estratégica 60min · Llamada telefónica · Presencial 60min'],
        ['improvement', 'meeting_settings con slug público único, validación anti-clash · timezone, branding, mensajes, requerir teléfono/empresa, footer "Powered by"'],
        ['improvement', 'Plan::FEATURES y MODULE_CATALOG con la entrada "meetings" tier business · sidebar tenant con item "Reuniones" en sección Gestión'],
        ['improvement', 'Migración idempotente con 5 tablas nuevas + columnas extra + seeds + asignación auto de host por defecto'],
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
