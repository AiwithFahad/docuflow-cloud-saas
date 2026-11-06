<?php
// DocuFlow Cloud — front controller. Point the web server docroot here
// (public/) so /storage, /app and /config stay outside the web root.
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Serve real static files directly under the PHP built-in server.
if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/routes/web.php';

dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
