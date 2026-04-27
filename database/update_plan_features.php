<?php
/**
 * Actualiza la columna `features` JSON de la tabla plans para reflejar
 * las features actuales (departamentos + integraciones añadidas en v4.3.0/v4.4.0).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_PLAN_FEAT') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['name']}\n\n";

$updates = [
    'starter' => [
        'name' => 'Starter',
        'description' => 'Ideal para equipos pequeños que recién inician con helpdesk',
        'features' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings'],
    ],
    'pro' => [
        'name' => 'Pro',
        'description' => 'Para equipos en crecimiento que necesitan automatización y SLAs',
        'features' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations'],
    ],
    'business' => [
        'name' => 'Business',
        'description' => 'Para empresas medianas con múltiples áreas y herramientas',
        'features' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations'],
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'description' => 'Solución completa con SSO, branding y todas las integraciones',
        'features' => ['tickets','kb','notes','todos','companies','assets','reports','users','roles','settings','automations','sla','audit','departments','integrations','sso','custom_branding'],
    ],
];

$stmt = $pdo->prepare('UPDATE plans SET features = ?, description = ? WHERE slug = ?');
foreach ($updates as $slug => $data) {
    $featuresJson = json_encode($data['features'], JSON_UNESCAPED_UNICODE);
    $stmt->execute([$featuresJson, $data['description'], $slug]);
    echo "  ✓ {$data['name']} (" . count($data['features']) . " features)\n";
    foreach ($data['features'] as $f) echo "      · $f\n";
    echo "\n";
}

echo "✓ Plans actualizados.\n";
