<?php
// DocuFlow Cloud — database configuration (PDO / MySQL)
declare(strict_types=1);

return [
    'driver'  => 'mysql',
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => (int) env('DB_PORT', 3306),
    'name'    => env('DB_NAME', 'docuflow_cloud'),
    'user'    => env('DB_USER', 'docuflow'),
    'pass'    => env('DB_PASS', ''),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
