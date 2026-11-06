<?php
// DocuFlow Cloud — Tenant (company) model
declare(strict_types=1);

final class Tenant extends Model
{
    protected static string $table = 'tenants';

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM tenants WHERE slug = ?', [$slug]);
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = slugify($base);
        $candidate = $slug;
        $i = 1;
        while (self::findBySlug($candidate)) {
            $candidate = $slug . '-' . (++$i);
        }
        return $candidate;
    }

    public static function settings(int $tenantId): array
    {
        $row = Database::one('SELECT * FROM tenant_settings WHERE tenant_id = ?', [$tenantId]);
        return $row ?? ['tenant_id' => $tenantId, 'default_approval_required' => 0, 'share_link_days' => 7];
    }

    /** Current subscription joined with its plan (null when none). */
    public static function currentSubscription(int $tenantId): ?array
    {
        return Database::one(
            'SELECT s.*, p.name AS plan_name, p.slug AS plan_slug, p.storage_mb, p.max_users,
                    p.max_file_mb, p.max_teams, p.version_history, p.price_monthly, p.price_yearly
             FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
             WHERE s.tenant_id = ? AND s.status IN ("active","trialing","past_due","cancelled")
             ORDER BY s.id DESC LIMIT 1',
            [$tenantId]
        );
    }

    /** Storage used by a tenant in bytes (from document metadata, real records). */
    public static function storageUsed(int $tenantId): int
    {
        return (int) Database::value(
            'SELECT COALESCE(SUM(size_bytes),0) FROM documents WHERE tenant_id = ? AND deleted_at IS NULL',
            [$tenantId]
        );
    }
}
