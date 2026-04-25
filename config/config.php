<?php
return [
    'app' => [
        'name' => 'Kydesk Helpdesk',
        'url'  => 'http://localhost/kyros-helpdesk',
        'env'  => 'local',
        'debug' => true,
        'timezone' => 'America/Guatemala',
        'locale' => 'es',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'kyros_helpdesk',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'KYROS_SID',
        'lifetime' => 60 * 60 * 8,
        'secure' => false,
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
