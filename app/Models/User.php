<?php
// DocuFlow Cloud — User model
declare(strict_types=1);

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
    }

    /** Record a failed login attempt and enforce rate limiting (5 / 15 min). */
    public static function loginRateLimited(string $email): bool
    {
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)',
            [strtolower(trim($email))]
        );
        return $count >= 5;
    }

    public static function recordFailedLogin(string $email): void
    {
        Database::run('INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?,?,NOW())',
            [strtolower(trim($email)), $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    public static function clearFailedLogins(string $email): void
    {
        Database::run('DELETE FROM login_attempts WHERE email = ?', [strtolower(trim($email))]);
    }
}
