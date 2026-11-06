<?php
// DocuFlow Cloud — authentication / authorization middleware
declare(strict_types=1);

final class AuthMiddleware
{
    /** Require a logged-in user; redirect guests to login. */
    public static function auth(): array
    {
        $user = auth_user();
        if (!$user) {
            flash('error', 'Please sign in to continue.');
            redirect('login');
        }
        return $user;
    }

    /** Require the platform Super Admin (tenant_id must be NULL). */
    public static function superAdmin(): array
    {
        $user = self::auth();
        if (!is_super_admin($user)) {
            http_response_code(403);
            exit('Forbidden — Super Admin area.');
        }
        return $user;
    }

    /**
     * Require a tenant user whose company is not suspended, and whose
     * subscription is active, trialing, or inside the grace period.
     */
    public static function tenant(): array
    {
        $user = self::auth();
        if (is_super_admin($user)) {
            redirect('super-admin/dashboard');
        }
        $tenant = auth_tenant();
        if (!$tenant) {
            http_response_code(403);
            exit('No company account is attached to this user.');
        }
        if ($tenant['status'] === 'suspended') {
            http_response_code(403);
            view('errors/suspended', ['tenant' => $tenant]);
            exit;
        }
        return $user;
    }

    /** Require a specific permission for the current user's role. */
    public static function permission(string $permission): void
    {
        if (!can($permission)) {
            http_response_code(403);
            exit('Forbidden — missing permission: ' . e($permission));
        }
    }
}
