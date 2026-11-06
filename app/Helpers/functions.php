<?php
// DocuFlow Cloud — global helper functions (escaping, CSRF, flash, auth, view)
declare(strict_types=1);

/** HTML-escape any value for safe output (XSS prevention). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function config(string $key = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
    }
    return $key === null ? $cfg : ($cfg[$key] ?? null);
}

function url(string $path = ''): string
{
    return config('url') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ---------------------------------------------------------------- CSRF --- */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['_csrf'] ?? '', $sent);
}

function require_csrf(): void
{
    if (!csrf_verify()) {
        http_response_code(419);
        exit('CSRF token mismatch.');
    }
}

/* --------------------------------------------------------------- Flash --- */
function flash(string $key, string $message = null)
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function stash_old(array $input): void
{
    unset($input['password'], $input['password_confirmation'], $input['_csrf']);
    $_SESSION['_old'] = $input;
}

/* ---------------------------------------------------------------- Auth --- */
function auth_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $user = Database::one('SELECT * FROM users WHERE id = ? AND status = "active"', [(int) $_SESSION['user_id']]);
    }
    return $user ?: null;
}

function auth_tenant(): ?array
{
    $u = auth_user();
    if (!$u || empty($u['tenant_id'])) return null;
    static $tenant = null;
    if ($tenant === null) {
        $tenant = Database::one('SELECT * FROM tenants WHERE id = ?', [(int) $u['tenant_id']]);
    }
    return $tenant ?: null;
}

function is_super_admin(?array $user = null): bool
{
    $user = $user ?? auth_user();
    return $user && $user['role'] === 'super_admin' && empty($user['tenant_id']);
}

function can(string $permission): bool
{
    $u = auth_user();
    if (!$u) return false;
    if ($u['role'] === 'super_admin' || $u['role'] === 'company_admin') return true;
    static $perms = null;
    if ($perms === null) {
        $rows = Database::all(
            'SELECT p.slug FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN roles r ON r.id = rp.role_id
             WHERE r.slug = ?',
            [$u['role']]
        );
        $perms = array_column($rows, 'slug');
    }
    return in_array($permission, $perms, true);
}

/* ------------------------------------------------------------- Logging --- */
function activity_log(string $action, string $description = '', ?int $tenantId = null, ?int $userId = null): void
{
    try {
        Database::run(
            'INSERT INTO activity_logs (tenant_id, user_id, action, description, ip_address, created_at)
             VALUES (?,?,?,?,?,NOW())',
            [$tenantId ?? (auth_user()['tenant_id'] ?? null), $userId ?? (auth_user()['id'] ?? null),
             $action, $description, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    } catch (Throwable) { /* logging must never break the request */ }
}

function audit_log(string $entity, int $entityId, string $action, $before = null, $after = null): void
{
    try {
        $u = auth_user();
        Database::run(
            'INSERT INTO audit_logs (tenant_id, user_id, entity, entity_id, action, old_values, new_values, ip_address, created_at)
             VALUES (?,?,?,?,?,?,?,?,NOW())',
            [$u['tenant_id'] ?? null, $u['id'] ?? null, $entity, $entityId, $action,
             $before !== null ? json_encode($before) : null,
             $after !== null ? json_encode($after) : null,
             $_SERVER['REMOTE_ADDR'] ?? null]
        );
    } catch (Throwable) { /* ignore */ }
}

function notify_user(int $userId, ?int $tenantId, string $title, string $body = '', string $link = ''): void
{
    Database::run(
        'INSERT INTO notifications (tenant_id, user_id, title, body, link, created_at) VALUES (?,?,?,?,?,NOW())',
        [$tenantId, $userId, $title, $body, $link ?: null]
    );
}

/* --------------------------------------------------------------- Views --- */
function view(string $template, array $data = []): void
{
    extract($data);
    $file = dirname(__DIR__, 2) . '/resources/views/' . $template . '.php';
    if (!is_file($file)) {
        http_response_code(500);
        exit('View not found: ' . e($template));
    }
    require $file;
}

function partial(string $template, array $data = []): void
{
    extract($data);
    require dirname(__DIR__, 2) . '/resources/views/partials/' . $template . '.php';
}

/* --------------------------------------------------------------- Misc ---- */
function human_bytes($bytes): string
{
    $bytes = (float) $bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'company';
}

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'per' => $perPage];
}
