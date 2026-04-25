<?php
$env = getenv('KYDESK_ENV') ?: (
    isset($_SERVER['HTTP_HOST']) && (
        str_contains($_SERVER['HTTP_HOST'], 'kydesk.kyrosrd.com') ||
        str_contains($_SERVER['HTTP_HOST'], 'kyrosrd.com')
    ) ? 'production' : 'local'
);

$databases = [
    'local' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'kyros_helpdesk',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'production' => [
        'host' => '129.121.81.172',
        'port' => 3306,
        'name' => 'neetjbte_kydesk',
        'user' => 'neetjbte_kydesk',
        'pass' => 'Kydesk.2026!',
        'charset' => 'utf8mb4',
    ],
];

$urls = [
    'local'      => 'http://localhost/kyros-helpdesk',
    'production' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://kydesk.kyrosrd.com',
];

$basePaths = [
    'local'      => '/kyros-helpdesk',
    'production' => '',
];

return [
    'app' => [
        'name' => 'Kydesk Helpdesk',
        'url'  => $urls[$env] ?? $urls['production'],
        'base' => $basePaths[$env] ?? '',
        'env'  => $env,
        'debug' => $env !== 'production',
        'timezone' => 'America/Santo_Domingo',
        'locale' => 'es',
    ],
    'db' => $databases[$env] ?? $databases['production'],
    'session' => [
        'name' => 'KYDESK_SID',
        'lifetime' => 60 * 60 * 8,
        'secure' => $env === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'security' => [
        'password_algo' => PASSWORD_BCRYPT,
        'password_cost' => 12,
        'csrf_ttl' => 3600,
    ],
    'uploads' => [
        'path' => __DIR__ . '/../public/uploads',
        'max_size' => 10 * 1024 * 1024,
        'allowed' => ['png','jpg','jpeg','gif','webp','pdf','doc','docx','xls','xlsx','txt','zip'],
    ],
];
