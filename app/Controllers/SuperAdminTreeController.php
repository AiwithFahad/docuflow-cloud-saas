<?php
// DocuFlow Cloud — Super Admin dashboard TREE (super-admin/*).
// Same platform data and verified-only payment rules as the classic console,
// organised as dedicated pages: dashboard, organizations, users, plans,
// subscriptions, payments, storage, reports, settings.
declare(strict_types=1);

final class SuperAdminTreeController
{
    private array $admin;

    public function __construct()
    {
        $this->admin = AuthMiddleware::superAdmin();
    }

    private function base(array $extra = []): array
    {
        return array_merge(['user' => $this->admin], $extra);
    }

    public function dashboard(): void
    {
        $stats = [
            'tenants'          => (int) Database::value('SELECT COUNT(*) FROM tenants'),
            'tenants_active'   => (int) Database::value('SELECT COUNT(*) FROM tenants WHERE status = "active"'),
            'tenants_suspended'=> (int) Database::value('SELECT COUNT(*) FROM tenants WHERE status = "suspended"'),
            'users'            => (int) Database::value('SELECT COUNT(*) FROM users'),
            'subs_active'      => (int) Database::value('SELECT COUNT(*) FROM subscriptions WHERE status IN ("active","trialing")'),
            'payments_pending' => (int) Database::value('SELECT COUNT(*) FROM payments WHERE status = "pending"'),
            'storage_used'     => (int) Database::value('SELECT COALESCE(SUM(size_bytes),0) FROM documents WHERE deleted_at IS NULL'),
            'documents'        => (int) Database::value('SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL'),
            'open_tickets'     => (int) Database::value('SELECT COUNT(*) FROM support_tickets WHERE status IN ("open","in_progress")'),
            'shares'           => (int) Database::value('SELECT COUNT(*) FROM document_shares WHERE is_active = 1'),
        ];
        $stats['mrr'] = (float) Database::value(
            'SELECT COALESCE(SUM(CASE WHEN s.billing_cycle = "yearly" THEN p.price_yearly / 12 ELSE p.price_monthly END), 0)
             FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id WHERE s.status = "active"');
        $stats['arr'] = $stats['mrr'] * 12;
        $revenueByMonth = Database::all(
            'SELECT DATE_FORMAT(paid_at, "%b %Y") AS label, SUM(amount) AS total FROM payments
             WHERE status = "paid" AND paid_at IS NOT NULL GROUP BY label, DATE_FORMAT(paid_at, "%Y-%m")
             ORDER BY DATE_FORMAT(paid_at, "%Y-%m") DESC LIMIT 12');
        $planMix = Database::all(
            'SELECT p.name AS label, COUNT(*) AS total FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
             WHERE s.status IN ("active","trialing") GROUP BY p.name ORDER BY total DESC');
        $recentTenants = Database::all('SELECT * FROM tenants ORDER BY id DESC LIMIT 8');
        $recentPayments = Database::all('SELECT pay.*, t.name AS tenant_name FROM payments pay JOIN tenants t ON t.id = pay.tenant_id ORDER BY pay.id DESC LIMIT 8');
        view('super-admin/dashboard', $this->base(compact('stats', 'revenueByMonth', 'planMix', 'recentTenants', 'recentPayments')));
    }

    /* --------------------------------------------------------- organizations */
    public function organizations(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT t.*, (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS users,
                       (SELECT COALESCE(SUM(size_bytes),0) FROM documents d WHERE d.tenant_id = t.id AND d.deleted_at IS NULL) AS storage,
                       (SELECT COUNT(*) FROM documents d WHERE d.tenant_id = t.id AND d.deleted_at IS NULL) AS docs,
                       (SELECT p.name FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id WHERE s.tenant_id = t.id ORDER BY s.id DESC LIMIT 1) AS plan,
                       (SELECT s2.status FROM subscriptions s2 WHERE s2.tenant_id = t.id ORDER BY s2.id DESC LIMIT 1) AS sub_status
                FROM tenants t';
        $params = [];
        if ($q !== '') { $sql .= ' WHERE t.name LIKE ? OR t.email LIKE ? OR t.slug LIKE ?'; $params = ["%$q%", "%$q%", "%$q%"]; }
        $sql .= ' ORDER BY t.id DESC';
        view('super-admin/organizations', $this->base(['tenants' => Database::all($sql, $params), 'q' => $q]));
    }

    public function organizationAction(int $id): void
    {
        require_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'suspend') {
            Database::run('UPDATE tenants SET status = "suspended", updated_at = NOW() WHERE id = ?', [$id]);
            activity_log('tenant.suspended', 'Tenant #' . $id . ' suspended', $id);
            flash('success', 'Organization suspended.');
        } elseif ($action === 'reactivate') {
            Database::run('UPDATE tenants SET status = "active", updated_at = NOW() WHERE id = ?', [$id]);
            activity_log('tenant.reactivated', 'Tenant #' . $id . ' reactivated', $id);
            flash('success', 'Organization reactivated.');
        }
        redirect('super-admin/organizations');
    }

    /* ------------------------------------------------------------------ users */
    public function users(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT u.*, t.name AS tenant_name FROM users u LEFT JOIN tenants t ON t.id = u.tenant_id';
        $params = [];
        if ($q !== '') { $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ?'; $params = ["%$q%", "%$q%"]; }
        $sql .= ' ORDER BY u.id DESC LIMIT 300';
        view('super-admin/users', $this->base(['users' => Database::all($sql, $params), 'q' => $q]));
    }

    public function userAction(int $id): void
    {
        require_csrf();
        $status = (string) ($_POST['status'] ?? '');
        if (in_array($status, ['active', 'disabled'], true)) {
            Database::run('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? AND id != ?', [$status, $id, (int) $this->admin['id']]);
            flash('success', 'User status updated.');
        }
        redirect('super-admin/users');
    }

    /* ------------------------------------------------------------------ plans */
    public function plans(): void
    {
        view('super-admin/plans', $this->base([
            'plans' => Database::all('SELECT * FROM subscription_plans ORDER BY price_monthly'),
            'coupons' => Database::all('SELECT * FROM coupons ORDER BY id DESC'),
        ]));
    }

    public function savePlan(): void
    {
        require_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim((string) $_POST['name']), 'price_monthly' => (float) $_POST['price_monthly'],
            'price_yearly' => (float) $_POST['price_yearly'], 'storage_mb' => (int) $_POST['storage_mb'],
            'max_users' => (int) $_POST['max_users'], 'max_file_mb' => (int) $_POST['max_file_mb'],
            'max_teams' => (int) $_POST['max_teams'], 'version_history' => (int) $_POST['version_history'],
            'features' => trim((string) ($_POST['features'] ?? '')), 'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            $sets = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
            Database::run("UPDATE subscription_plans SET $sets WHERE id = ?", array_merge(array_values($data), [$id]));
        } else {
            $data['slug'] = slugify($data['name']);
            $data['created_at'] = date('Y-m-d H:i:s');
            Database::insert('INSERT INTO subscription_plans (' . implode(',', array_keys($data)) . ') VALUES (' .
                implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
        }
        flash('success', 'Plan saved.');
        redirect('super-admin/plans');
    }

    public function saveCoupon(): void
    {
        require_csrf();
        $action = (string) ($_POST['action'] ?? 'create');
        if ($action === 'delete') {
            Database::run('DELETE FROM coupons WHERE id = ?', [(int) $_POST['id']]);
        } elseif ($action === 'toggle') {
            Database::run('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?', [(int) $_POST['id']]);
        } else {
            Database::run('INSERT INTO coupons (code, type, value, max_uses, expires_at, is_active, created_at)
                           VALUES (?,?,?,?,?,1,NOW())',
                [strtoupper(trim((string) $_POST['code'])), (string) $_POST['type'], (float) $_POST['value'],
                 !empty($_POST['max_uses']) ? (int) $_POST['max_uses'] : null,
                 !empty($_POST['expires_at']) ? $_POST['expires_at'] : null]);
        }
        flash('success', 'Coupons updated.');
        redirect('super-admin/plans');
    }

    /* ------------------------------------------------------------ subscriptions */
    public function subscriptions(): void
    {
        $status = (string) ($_GET['status'] ?? '');
        $sql = 'SELECT s.*, t.name AS tenant_name, p.name AS plan_name
                FROM subscriptions s JOIN tenants t ON t.id = s.tenant_id JOIN subscription_plans p ON p.id = s.plan_id';
        $params = [];
        if ($status !== '') { $sql .= ' WHERE s.status = ?'; $params[] = $status; }
        $sql .= ' ORDER BY s.id DESC LIMIT 300';
        $subs = Database::all($sql, $params);
        foreach ($subs as &$s) $s['effective'] = SubscriptionService::effectiveStatus($s);
        unset($s);
        $counts = Database::all('SELECT status, COUNT(*) AS total FROM subscriptions GROUP BY status');
        view('super-admin/subscriptions', $this->base(compact('subs', 'counts', 'status')));
    }

    /* ---------------------------------------------------------------- payments */
    public function payments(): void
    {
        $payments = Database::all('SELECT pay.*, t.name AS tenant_name, i.number AS invoice_number
                                   FROM payments pay JOIN tenants t ON t.id = pay.tenant_id
                                   LEFT JOIN invoices i ON i.id = pay.invoice_id ORDER BY pay.id DESC LIMIT 300');
        view('super-admin/payments', $this->base(compact('payments')));
    }

    public function paymentAction(int $id): void
    {
        require_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'verify') {
            // Verified-only rule: activation happens exclusively inside
            // PaymentService::confirmPayment() after human verification.
            $ok = PaymentService::confirmPayment($id, 'super_admin');
            flash($ok ? 'success' : 'error', $ok ? 'Payment verified, marked paid, subscription activated.' : 'Payment could not be verified.');
        } elseif ($action === 'fail') {
            Database::run('UPDATE payments SET status = "failed" WHERE id = ? AND status = "pending"', [$id]);
            flash('success', 'Payment marked failed.');
        }
        redirect('super-admin/payments');
    }

    /* ----------------------------------------------------------------- storage */
    public function storage(): void
    {
        $byTenant = Database::all(
            'SELECT t.id, t.name, COUNT(d.id) AS docs, COALESCE(SUM(d.size_bytes),0) AS bytes,
                    (SELECT p.storage_mb FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
                     WHERE s.tenant_id = t.id ORDER BY s.id DESC LIMIT 1) AS quota_mb,
                    (SELECT COUNT(*) FROM documents d2 WHERE d2.tenant_id = t.id AND d2.deleted_at IS NOT NULL) AS trash_docs,
                    (SELECT COALESCE(SUM(size_bytes),0) FROM documents d3 WHERE d3.tenant_id = t.id AND d3.deleted_at IS NOT NULL) AS trash_bytes
             FROM tenants t LEFT JOIN documents d ON d.tenant_id = t.id AND d.deleted_at IS NULL
             GROUP BY t.id ORDER BY bytes DESC');
        $byType = Database::all('SELECT extension, COUNT(*) AS files, COALESCE(SUM(size_bytes),0) AS bytes FROM documents
                                 WHERE deleted_at IS NULL GROUP BY extension ORDER BY bytes DESC');
        $largest = Database::all('SELECT d.name, d.size_bytes, d.extension, t.name AS tenant_name FROM documents d
                                  JOIN tenants t ON t.id = d.tenant_id WHERE d.deleted_at IS NULL ORDER BY d.size_bytes DESC LIMIT 15');
        $total = (int) Database::value('SELECT COALESCE(SUM(size_bytes),0) FROM documents WHERE deleted_at IS NULL');
        view('super-admin/storage', $this->base(compact('byTenant', 'byType', 'largest', 'total')));
    }

    /* ----------------------------------------------------------------- reports */
    public function reports(): void
    {
        if (($_GET['export'] ?? '') === 'revenue') {
            $rows = Database::all('SELECT DATE_FORMAT(paid_at, "%Y-%m") AS month, COUNT(*) AS payments, SUM(amount) AS revenue
                                   FROM payments WHERE status = "paid" AND paid_at IS NOT NULL GROUP BY month ORDER BY month');
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="docuflow-revenue-report.csv"');
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Month', 'Payments', 'Revenue']);
            foreach ($rows as $r) fputcsv($fh, [$r['month'], (int) $r['payments'], (float) $r['revenue']]);
            fclose($fh);
            exit;
        }
        $revenue = Database::all('SELECT DATE_FORMAT(paid_at, "%b %Y") AS label, SUM(amount) AS total FROM payments
                                  WHERE status = "paid" AND paid_at IS NOT NULL GROUP BY label, DATE_FORMAT(paid_at, "%Y-%m")
                                  ORDER BY DATE_FORMAT(paid_at, "%Y-%m") DESC LIMIT 12');
        $signups = Database::all('SELECT DATE_FORMAT(created_at, "%b %Y") AS label, COUNT(*) AS total FROM tenants
                                  GROUP BY label, DATE_FORMAT(created_at, "%Y-%m") ORDER BY DATE_FORMAT(created_at, "%Y-%m") DESC LIMIT 12');
        $subsByStatus = Database::all('SELECT status AS label, COUNT(*) AS total FROM subscriptions GROUP BY status');
        $planMix = Database::all('SELECT p.name AS label, COUNT(*) AS total FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
                                  WHERE s.status IN ("active","trialing") GROUP BY p.name ORDER BY total DESC');
        $topTenants = Database::all('SELECT t.name, COUNT(d.id) AS docs, COALESCE(SUM(d.size_bytes),0) AS bytes
                                     FROM tenants t LEFT JOIN documents d ON d.tenant_id = t.id AND d.deleted_at IS NULL
                                     GROUP BY t.id ORDER BY bytes DESC LIMIT 10');
        view('super-admin/reports', $this->base(compact('revenue', 'signups', 'subsByStatus', 'planMix', 'topTenants')));
    }

    /* ---------------------------------------------------------------- settings */
    public function settings(): void
    {
        view('super-admin/settings', $this->base([
            'settings' => Database::all('SELECT * FROM platform_settings ORDER BY setting_key'),
        ]));
    }

    public function saveSettings(): void
    {
        require_csrf();
        foreach ((array) ($_POST['settings'] ?? []) as $key => $value) {
            Database::run('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?)
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [(string) $key, (string) $value]);
        }
        flash('success', 'Platform settings saved.');
        redirect('super-admin/settings');
    }
}
