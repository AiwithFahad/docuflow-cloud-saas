<?php
// DocuFlow Cloud — subscription lifecycle + plan-limit enforcement
declare(strict_types=1);

final class SubscriptionService
{
    /** Effective status taking expiry + grace period into account. */
    public static function effectiveStatus(array $sub): string
    {
        if (in_array($sub['status'], ['active', 'trialing'], true) && !empty($sub['current_period_end'])) {
            $end = strtotime((string) $sub['current_period_end']);
            $graceEnd = $end + ((int) config('grace_days') * 86400);
            if (time() > $graceEnd) return 'expired';
            if (time() > $end) return 'past_due'; // inside grace period
        }
        return (string) $sub['status'];
    }

    public static function startTrial(int $tenantId, int $planId): int
    {
        $days = (int) config('trial_days');
        return Database::insert(
            'INSERT INTO subscriptions (tenant_id, plan_id, billing_cycle, status, trial_ends_at,
                                        current_period_start, current_period_end, created_at, updated_at)
             VALUES (?,?,"monthly","trialing", DATE_ADD(NOW(), INTERVAL ? DAY), NOW(),
                     DATE_ADD(NOW(), INTERVAL ? DAY), NOW(), NOW())',
            [$tenantId, $planId, $days, $days]
        );
    }

    public static function activate(int $tenantId, int $planId, string $cycle): int
    {
        Database::run('UPDATE subscriptions SET status = "cancelled", updated_at = NOW()
                       WHERE tenant_id = ? AND status IN ("active","trialing","past_due")', [$tenantId]);
        $months = $cycle === 'yearly' ? 12 : 1;
        return Database::insert(
            'INSERT INTO subscriptions (tenant_id, plan_id, billing_cycle, status,
                                        current_period_start, current_period_end, created_at, updated_at)
             VALUES (?,?,?,"active", NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH), NOW(), NOW())',
            [$tenantId, $planId, $cycle, $months]
        );
    }

    public static function cancel(int $subscriptionId, int $tenantId): void
    {
        Database::run('UPDATE subscriptions SET status = "cancelled", cancelled_at = NOW(), updated_at = NOW()
                       WHERE id = ? AND tenant_id = ?', [$subscriptionId, $tenantId]);
    }

    public static function renew(array $sub): void
    {
        $months = $sub['billing_cycle'] === 'yearly' ? 12 : 1;
        Database::run('UPDATE subscriptions SET status = "active",
                        current_period_start = NOW(),
                        current_period_end = DATE_ADD(NOW(), INTERVAL ? MONTH), updated_at = NOW()
                       WHERE id = ?', [$months, (int) $sub['id']]);
    }

    /** Price for a plan/cycle after an optional coupon. Returns [amount, discount]. */
    public static function priceWithCoupon(array $plan, string $cycle, ?array $coupon): array
    {
        $amount = (float) ($cycle === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly']);
        $discount = 0.0;
        if ($coupon) {
            $discount = $coupon['type'] === 'percent'
                ? round($amount * ((float) $coupon['value'] / 100), 2)
                : min($amount, (float) $coupon['value']);
        }
        return [max(0.0, $amount - $discount), $discount];
    }

    public static function validCoupon(string $code): ?array
    {
        $c = Database::one('SELECT * FROM coupons WHERE code = ? AND is_active = 1
                            AND (expires_at IS NULL OR expires_at > NOW())
                            AND (max_uses IS NULL OR uses < max_uses)', [strtoupper(trim($code))]);
        return $c ?: null;
    }

    /* -------------------------------------------------- limit checks ----- */
    public static function withinStorageLimit(int $tenantId, int $extraBytes): bool
    {
        $sub = Tenant::currentSubscription($tenantId);
        if (!$sub || (int) $sub['storage_mb'] < 0) return true; // unlimited
        return Tenant::storageUsed($tenantId) + $extraBytes <= (int) $sub['storage_mb'] * 1024 * 1024;
    }

    public static function withinUserLimit(int $tenantId): bool
    {
        $sub = Tenant::currentSubscription($tenantId);
        if (!$sub || (int) $sub['max_users'] < 0) return true;
        $count = (int) Database::value('SELECT COUNT(*) FROM users WHERE tenant_id = ?', [$tenantId]);
        return $count < (int) $sub['max_users'];
    }

    public static function withinTeamLimit(int $tenantId): bool
    {
        $sub = Tenant::currentSubscription($tenantId);
        if (!$sub || (int) $sub['max_teams'] < 0) return true;
        return (int) Database::value('SELECT COUNT(*) FROM teams WHERE tenant_id = ?', [$tenantId]) < (int) $sub['max_teams'];
    }

    public static function maxFileBytes(int $tenantId): int
    {
        $sub = Tenant::currentSubscription($tenantId);
        $planCap = (!$sub || (int) $sub['max_file_mb'] < 0) ? PHP_INT_MAX : (int) $sub['max_file_mb'] * 1024 * 1024;
        return min($planCap, (int) config('max_upload'));
    }

    public static function versionLimit(int $tenantId): int
    {
        $sub = Tenant::currentSubscription($tenantId);
        return $sub ? (int) $sub['version_history'] : 5;
    }
}
