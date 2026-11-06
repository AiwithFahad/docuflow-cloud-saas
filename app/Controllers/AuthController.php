<?php
// DocuFlow Cloud — authentication: register (company onboarding), login,
// logout, forgot/reset password, employee invitations, email verification.
declare(strict_types=1);

final class AuthController
{
    public function showLogin(): void
    {
        if (auth_user()) redirect(is_super_admin() ? 'super' : 'app');
        view('auth/login');
    }

    public function login(): void
    {
        require_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (User::loginRateLimited($email)) {
            flash('error', 'Too many failed attempts. Please wait 15 minutes and try again.');
            redirect('login');
        }
        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            User::recordFailedLogin($email);
            stash_old($_POST);
            flash('error', 'Invalid email or password.');
            redirect('login');
        }
        if ($user['status'] !== 'active') {
            flash('error', 'This account is not active. Check your email for the verification link.');
            redirect('login');
        }
        if (!empty($user['tenant_id'])) {
            $tenant = Database::one('SELECT status FROM tenants WHERE id = ?', [(int) $user['tenant_id']]);
            if ($tenant && $tenant['status'] === 'suspended') {
                flash('error', 'Your company account has been suspended. Please contact support.');
                redirect('login');
            }
        }
        User::clearFailedLogins($email);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        Database::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [(int) $user['id']]);
        activity_log('auth.login', 'User signed in', $user['tenant_id'] ? (int) $user['tenant_id'] : null, (int) $user['id']);
        redirect(is_super_admin($user) ? 'super' : 'app');
    }

    public function logout(): void
    {
        require_csrf();
        activity_log('auth.logout', 'User signed out');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        redirect('login');
    }

    /* ----------------------------------------------------- registration -- */
    public function showRegister(): void
    {
        $plans = Database::all('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price_monthly');
        view('auth/register', ['plans' => $plans]);
    }

    public function register(): void
    {
        require_csrf();
        $company = trim((string) ($_POST['company_name'] ?? ''));
        $name    = trim((string) ($_POST['name'] ?? ''));
        $email   = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass    = (string) ($_POST['password'] ?? '');
        $planId  = (int) ($_POST['plan_id'] ?? 0);

        $errors = [];
        if ($company === '') $errors[] = 'Company name is required.';
        if ($name === '') $errors[] = 'Your name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (User::findByEmail($email)) $errors[] = 'That email is already registered.';
        $plan = $planId ? Database::one('SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1', [$planId]) : null;
        if (!$plan) $errors[] = 'Please choose a plan.';
        if ($errors) {
            stash_old($_POST);
            flash('error', implode(' ', $errors));
            redirect('register');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $tenantId = Database::insert(
                'INSERT INTO tenants (name, slug, email, status, created_at, updated_at) VALUES (?,?,?,"active",NOW(),NOW())',
                [$company, Tenant::uniqueSlug($company), $email]
            );
            Database::run('INSERT INTO tenant_settings (tenant_id, share_link_days) VALUES (?, 7)', [$tenantId]);
            $token = bin2hex(random_bytes(32));
            $userId = Database::insert(
                'INSERT INTO users (tenant_id, name, email, password_hash, role, status, email_verification_token, created_at, updated_at)
                 VALUES (?,?,?,?, "company_admin", "pending", ?, NOW(), NOW())',
                [$tenantId, $name, $email, password_hash($pass, PASSWORD_DEFAULT), $token]
            );
            SubscriptionService::startTrial($tenantId, (int) $plan['id']);
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash('error', 'Registration failed. Please try again.');
            redirect('register');
        }

        activity_log('tenant.registered', 'Company registered with trial', $tenantId, $userId);
        // In production the verification link is emailed; shown once here for demo installs.
        flash('success', 'Company created! Verify your account to sign in: ' . url('verify-email?token=' . $token));
        redirect('login');
    }

    public function verifyEmail(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $user = $token ? Database::one('SELECT * FROM users WHERE email_verification_token = ?', [$token]) : null;
        if (!$user) {
            flash('error', 'Invalid or expired verification link.');
            redirect('login');
        }
        Database::run('UPDATE users SET status = "active", email_verified_at = NOW(), email_verification_token = NULL WHERE id = ?', [(int) $user['id']]);
        flash('success', 'Email verified. You can now sign in.');
        redirect('login');
    }

    /* ------------------------------------------------- password reset ---- */
    public function showForgot(): void
    {
        view('auth/forgot');
    }

    public function forgot(): void
    {
        require_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::run('INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())',
                [$email, hash('sha256', $token)]);
            flash('success', 'Reset link (demo display): ' . url('reset-password?token=' . $token));
        } else {
            flash('success', 'If that account exists, a reset link has been sent.');
        }
        redirect('login');
    }

    public function showReset(): void
    {
        view('auth/reset', ['token' => (string) ($_GET['token'] ?? '')]);
    }

    public function reset(): void
    {
        require_csrf();
        $token = (string) ($_POST['token'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $row = Database::one('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [hash('sha256', $token)]);
        if (!$row || strlen($pass) < 8) {
            flash('error', 'Invalid/expired reset link or password too short (min 8).');
            redirect('forgot-password');
        }
        Database::run('UPDATE users SET password_hash = ? WHERE email = ?',
            [password_hash($pass, PASSWORD_DEFAULT), $row['email']]);
        Database::run('DELETE FROM password_resets WHERE email = ?', [$row['email']]);
        flash('success', 'Password updated. Please sign in.');
        redirect('login');
    }

    /* ---------------------------------------------------- invitations ---- */
    public function acceptInvite(): void
    {
        $token = (string) ($_GET['token'] ?? ($_POST['token'] ?? ''));
        $invite = $token ? Database::one('SELECT * FROM invitations WHERE token = ? AND status = "pending" AND expires_at > NOW()', [$token]) : null;
        if (!$invite) {
            flash('error', 'This invitation is invalid or has expired.');
            redirect('login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $name = trim((string) ($_POST['name'] ?? ''));
            $pass = (string) ($_POST['password'] ?? '');
            if ($name === '' || strlen($pass) < 8) {
                flash('error', 'Name and a password of at least 8 characters are required.');
                redirect('accept-invite?token=' . $token);
            }
            if (!SubscriptionService::withinUserLimit((int) $invite['tenant_id'])) {
                flash('error', 'Your company has reached its plan user limit.');
                redirect('login');
            }
            Database::insert(
                'INSERT INTO users (tenant_id, name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
                 VALUES (?,?,?,?,?, "active", NOW(), NOW(), NOW())',
                [(int) $invite['tenant_id'], $name, $invite['email'], password_hash($pass, PASSWORD_DEFAULT), $invite['role']]
            );
            Database::run('UPDATE invitations SET status = "accepted" WHERE id = ?', [(int) $invite['id']]);
            flash('success', 'Welcome aboard! Your account is ready — sign in.');
            redirect('login');
        }
        view('auth/accept-invite', ['invite' => $invite]);
    }
}
