<?php
// DocuFlow Cloud — bootstrap: autoloading, hardened session, error handling.
declare(strict_types=1);

$base = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($base) {
    foreach (['app/Controllers/', 'app/Models/', 'app/Services/', 'app/Middleware/', 'app/Helpers/'] as $dir) {
        $file = $base . '/' . $dir . $class . '.php';
        if (is_file($file)) { require $file; return; }
    }
});
require $base . '/app/Helpers/functions.php';

$app = require $base . '/config/app.php';

// Secure session cookie settings (must be set before session_start).
session_name($app['session']['name']);
session_set_cookie_params([
    'lifetime' => $app['session']['lifetime'],
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
]);
session_start();

if ($app['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $base . '/storage/logs/php-error.log');
}
date_default_timezone_set('UTC');
