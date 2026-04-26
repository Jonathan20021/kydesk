<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CHANGELOG') { http_response_code(403); exit('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}
define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
    $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = file_get_contents(BASE_PATH . '/database/changelog_migration.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
foreach (array_filter(array_map('trim', explode(';', $sql))) as $s) {
    if ($s === '') continue;
    try { $pdo->exec($s); echo "  ✓ " . substr($s, 0, 50) . "…\n"; }
    catch (\Throwable $e) { echo "  ! " . $e->getMessage() . "\n"; }
}

// Seed if empty
$count = (int)$pdo->query("SELECT COUNT(*) FROM changelog_entries")->fetchColumn();
if ($count === 0) {
    echo "→ Seeding…\n";
    $entries = [
        [
            'version' => 'v3.1.0', 'release_type' => 'minor',
            'title' => 'Tablero Kanban + Automatizaciones IA',
            'summary' => 'Vista Kanban arrastrable y reglas inteligentes que clasifican tickets automáticamente.',
            'hero_pill_label' => 'Tablero Kanban + Automatizaciones IA',
            'is_featured' => 1, 'is_published' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'items' => [
                ['feature', 'Tablero Kanban con drag-and-drop nativo entre columnas de estado.'],
                ['feature', 'Automatizaciones con disparadores: ticket creado, actualizado, escalado, SLA en riesgo, resuelto.'],
                ['feature', 'Condiciones combinables: prioridad + categoría + palabra clave en el asunto.'],
                ['feature', 'Acciones: cambiar prioridad/estado, asignar técnico, notificar email, agregar comentario interno.'],
            ],
        ],
        [
            'version' => 'v3.0.0', 'release_type' => 'major',
            'title' => 'API REST completa + Centro de ayuda',
            'summary' => 'API REST v1 con tokens Bearer, scopes y documentación interactiva en cada workspace.',
            'is_featured' => 0, 'is_published' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'items' => [
                ['feature', 'API REST v1 con 18 endpoints (tickets, comentarios, categorías, empresas, KB, SLA, stats).'],
                ['feature', 'Tokens Bearer con scopes granulares (read / write / *) gestionables desde el panel.'],
                ['feature', 'Centro de ayuda con FAQ y buscador en vivo dentro del workspace.'],
                ['feature', 'Soporte directo: comunicación con el equipo Kydesk desde el workspace.'],
                ['improvement', 'Mailer dual: Resend como primario + SMTP de respaldo automático.'],
            ],
        ],
        [
            'version' => 'v2.4.1', 'release_type' => 'patch',
            'title' => 'Mejoras de UI y performance',
            'is_featured' => 0, 'is_published' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
            'items' => [
                ['fix', 'Layout del dashboard ya no se estira con tablas grandes.'],
                ['fix', 'Charts de Chart.js con altura controlada — sin loops infinitos de redraw.'],
                ['improvement', 'Sidebar colapsable con modo iconos en desktop y drawer en mobile.'],
                ['improvement', 'Tablas con scroll horizontal automático en mobile.'],
            ],
        ],
        [
            'version' => 'v2.4.0', 'release_type' => 'minor',
            'title' => 'Planes dinámicos en landing',
            'is_featured' => 0, 'is_published' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-21 days')),
            'items' => [
                ['feature', 'La página /pricing y el home pricing leen planes desde la BD en tiempo real.'],
                ['feature', 'Cualquier cambio del super admin se refleja al instante en la landing.'],
                ['feature', 'Toggle Mensual/Anual con switch dinámico de precios.'],
            ],
        ],
    ];
    foreach ($entries as $e) {
        $stmt = $pdo->prepare("INSERT INTO changelog_entries (version, release_type, title, summary, hero_pill_label, is_featured, is_published, published_at) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$e['version'], $e['release_type'], $e['title'], $e['summary'] ?? null, $e['hero_pill_label'] ?? null, $e['is_featured'], $e['is_published'], $e['published_at']]);
        $entryId = (int)$pdo->lastInsertId();
        foreach ($e['items'] as $i => [$type, $text]) {
            $pdo->prepare("INSERT INTO changelog_items (entry_id, item_type, text, sort_order) VALUES (?,?,?,?)")->execute([$entryId, $type, $text, $i]);
        }
        echo "  ✓ {$e['version']} — {$e['title']}\n";
    }
}

echo "\n✓ Migración changelog completada.\n";
