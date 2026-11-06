<?php
// DocuFlow Cloud — routes. Tiny method+path dispatcher.
// Usage: route('GET', 'app/documents/{id}', [TenantController::class, 'show']);
declare(strict_types=1);

$GLOBALS['routes'] = [];

function route(string $method, string $path, array $handler): void
{
    $GLOBALS['routes'][] = [$method, trim($path, '/'), $handler];
}

function dispatch(string $method, string $uri): void
{
    $path = trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
    foreach ($GLOBALS['routes'] as [$m, $pattern, $handler]) {
        if ($m !== $method) continue;
        $regex = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern) . '$#';
        if (preg_match($regex, $path, $matches)) {
            array_shift($matches);
            $args = array_map(fn($v) => ctype_digit((string) $v) ? (int) $v : $v, $matches);
            if ($handler instanceof Closure) {
                $handler();
                return;
            }
            [$class, $action] = $handler;
            (new $class())->$action(...$args);
            return;
        }
    }
    http_response_code(404);
    view('errors/404');
}

/* ---------------------------------------------------------- public site */
route('GET', '', [WebsiteController::class, 'home']);
route('GET', 'about', fn() => (new WebsiteController())->page('about'));
route('GET', 'features', fn() => (new WebsiteController())->page('features'));
route('GET', 'solutions', fn() => (new WebsiteController())->page('solutions'));
route('GET', 'industries', fn() => (new WebsiteController())->page('industries'));
route('GET', 'integrations', fn() => (new WebsiteController())->page('integrations'));
route('GET', 'security', fn() => (new WebsiteController())->page('security'));
route('GET', 'blog', fn() => (new WebsiteController())->page('blog'));
route('GET', 'blog/post', fn() => (new WebsiteController())->page('blog-details'));
route('GET', 'contact', fn() => (new WebsiteController())->page('contact'));
route('GET', 'privacy', fn() => (new WebsiteController())->page('privacy'));
route('GET', 'terms', fn() => (new WebsiteController())->page('terms'));
route('GET', 'pricing', [WebsiteController::class, 'pricing']);
route('POST', 'contact', [WebsiteController::class, 'contactSubmit']);
route('POST', 'newsletter', [WebsiteController::class, 'newsletter']);

/* ----------------------------------------------------------------- auth */
route('GET', 'login', [AuthController::class, 'showLogin']);
route('POST', 'login', [AuthController::class, 'login']);
route('POST', 'logout', [AuthController::class, 'logout']);
route('GET', 'register', [AuthController::class, 'showRegister']);
route('POST', 'register', [AuthController::class, 'register']);
route('GET', 'verify-email', [AuthController::class, 'verifyEmail']);
route('GET', 'forgot-password', [AuthController::class, 'showForgot']);
route('POST', 'forgot-password', [AuthController::class, 'forgot']);
route('GET', 'reset-password', [AuthController::class, 'showReset']);
route('POST', 'reset-password', [AuthController::class, 'reset']);
route('GET', 'accept-invite', [AuthController::class, 'acceptInvite']);
route('POST', 'accept-invite', [AuthController::class, 'acceptInvite']);

/* ---------------------------------------------------------- public share */
route('GET', 's/{token}', [ShareController::class, 'show']);
route('GET', 's/{token}/download', [ShareController::class, 'download']);

/* ------------------------------------------------------------ tenant app */
route('GET', 'app', [TenantController::class, 'dashboard']);
route('GET', 'app/files', [TenantController::class, 'files']);
route('GET', 'app/search', [TenantController::class, 'search']);
route('GET', 'app/upload', [TenantController::class, 'uploadForm']);
route('POST', 'app/upload', [TenantController::class, 'upload']);
route('GET', 'app/documents/{id}', [TenantController::class, 'show']);
route('GET', 'app/documents/{id}/preview', [TenantController::class, 'preview']);
route('GET', 'app/documents/{id}/download', [TenantController::class, 'download']);
route('POST', 'app/documents/{id}/update', [TenantController::class, 'update']);
route('POST', 'app/documents/{id}/delete', [TenantController::class, 'delete']);
route('POST', 'app/documents/{id}/version', [TenantController::class, 'newVersion']);
route('POST', 'app/documents/{id}/version/{versionId}/restore', [TenantController::class, 'restoreVersion']);
route('POST', 'app/documents/{id}/comment', [TenantController::class, 'comment']);
route('POST', 'app/documents/{id}/share', [TenantController::class, 'createShare']);
route('POST', 'app/documents/{id}/share/{shareId}/revoke', [TenantController::class, 'revokeShare']);
route('POST', 'app/documents/{id}/permission', [TenantController::class, 'savePermission']);
route('GET', 'app/approvals', [TenantController::class, 'approvals']);
route('POST', 'app/approvals/request', [TenantController::class, 'requestApproval']);
route('POST', 'app/approvals/step/{stepId}/decide', [TenantController::class, 'decideApproval']);
route('POST', 'app/folders', [TenantController::class, 'createFolder']);
route('POST', 'app/folders/{id}/update', [TenantController::class, 'updateFolder']);
route('GET', 'app/taxonomy', [TenantController::class, 'taxonomy']);
route('POST', 'app/taxonomy/category', fn() => (new TenantController())->saveTaxonomy('category'));
route('POST', 'app/taxonomy/tag', fn() => (new TenantController())->saveTaxonomy('tag'));
route('GET', 'app/teams', [TenantController::class, 'teams']);
route('POST', 'app/teams', [TenantController::class, 'saveTeam']);
route('GET', 'app/users', [TenantController::class, 'users']);
route('POST', 'app/users/invite', [TenantController::class, 'inviteUser']);
route('POST', 'app/users/{id}/update', [TenantController::class, 'updateUser']);
route('GET', 'app/activity', [TenantController::class, 'activity']);
route('GET', 'app/audit', [TenantController::class, 'audit']);
route('GET', 'app/storage', [TenantController::class, 'storage']);
route('POST', 'app/storage/{id}/purge', [TenantController::class, 'purgeTrash']);
route('GET', 'app/reports', [TenantController::class, 'reports']);
route('GET', 'app/settings', [TenantController::class, 'settings']);
route('POST', 'app/settings', [TenantController::class, 'saveSettings']);
route('GET', 'app/profile', [TenantController::class, 'profile']);
route('POST', 'app/profile', [TenantController::class, 'saveProfile']);
route('POST', 'app/notifications/read', [TenantController::class, 'markNotificationsRead']);
route('GET', 'app/subscription', [SubscriptionController::class, 'index']);
route('POST', 'app/subscription/change', [SubscriptionController::class, 'changePlan']);
route('POST', 'app/subscription/cancel', [SubscriptionController::class, 'cancel']);
route('POST', 'app/subscription/renew', [SubscriptionController::class, 'renew']);
route('GET', 'app/invoices/{id}', [SubscriptionController::class, 'invoicePdf']);

/* ----------------------------------------------------------- super admin */
route('GET', 'super', [SuperAdminController::class, 'dashboard']);
route('GET', 'super/tenants', [SuperAdminController::class, 'tenants']);
route('POST', 'super/tenants/{id}/action', [SuperAdminController::class, 'tenantAction']);
route('GET', 'super/users', [SuperAdminController::class, 'users']);
route('POST', 'super/users/{id}/action', [SuperAdminController::class, 'userAction']);
route('GET', 'super/plans', [SuperAdminController::class, 'plans']);
route('POST', 'super/plans/save', [SuperAdminController::class, 'savePlan']);
route('POST', 'super/coupons/save', [SuperAdminController::class, 'saveCoupon']);
route('GET', 'super/payments', [SuperAdminController::class, 'payments']);
route('POST', 'super/payments/{id}/action', [SuperAdminController::class, 'paymentAction']);
route('GET', 'super/invoices', [SuperAdminController::class, 'invoices']);
route('GET', 'super/tickets', [SuperAdminController::class, 'tickets']);
route('POST', 'super/tickets/{id}/action', [SuperAdminController::class, 'ticketAction']);
route('GET', 'super/announcements', [SuperAdminController::class, 'announcements']);
route('POST', 'super/announcements/save', [SuperAdminController::class, 'saveAnnouncement']);
route('GET', 'super/settings', [SuperAdminController::class, 'settings']);
route('POST', 'super/settings', [SuperAdminController::class, 'saveSettings']);
route('GET', 'super/logs', [SuperAdminController::class, 'logs']);
route('GET', 'super/analytics', [SuperAdminController::class, 'analytics']);
