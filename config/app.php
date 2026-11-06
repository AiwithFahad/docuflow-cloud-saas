<?php
// DocuFlow Cloud — application configuration (values overridden by .env / environment)
declare(strict_types=1);

function env(string $key, $default = null) {
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $file = dirname(__DIR__) . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v);
            }
        }
    }
    return $_ENV[$key] ?? $vars[$key] ?? $default;
}

return [
    'name'        => env('APP_NAME', 'DocuFlow Cloud'),
    'url'         => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'env'         => env('APP_ENV', 'production'),
    'debug'       => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'trial_days'  => (int) env('TRIAL_DAYS', 14),
    'grace_days'  => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),
    // Private file storage — OUTSIDE the public web root (public/ is the docroot).
    'storage_path'=> dirname(__DIR__) . '/storage/private',
    'upload_path' => dirname(__DIR__) . '/storage/uploads',
    'max_upload'  => (int) env('MAX_UPLOAD_BYTES', 52428800), // 50 MB hard ceiling
    'session' => [
        'name'     => 'DOCUFLOWSESSID',
        'lifetime' => 7200,
    ],
    'mail' => [
        'from' => env('MAIL_FROM', 'no-reply@docuflow.example'),
    ],
];
