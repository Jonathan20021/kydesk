<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

$config = require BASE_PATH . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Autoloader PSR-4 simple
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, 4) !== 0) return;
    $relative = str_replace('\\', '/', substr($class, 4));
    $file = APP_PATH . '/' . $relative . '.php';
    if (is_file($file)) require $file;
});

use App\Core\Application;

try {
    $app = new Application($config);
    $app->run();
} catch (\Throwable $e) {
    http_response_code(500);
    if ($config['app']['debug']) {
        echo '<pre style="padding:20px;font-family:monospace;background:#1a1a1a;color:#f87171;">';
        echo "Error: " . htmlspecialchars($e->getMessage()) . "\n\n";
        echo "File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        echo 'Ha ocurrido un error. Intenta nuevamente.';
    }
}
