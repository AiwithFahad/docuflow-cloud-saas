<?php
// DocuFlow Cloud — tiny PDO model base (prepared statements everywhere)
declare(strict_types=1);

abstract class Model
{
    protected static string $table = '';

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    /** Find a row that MUST belong to the given tenant (tenant isolation). */
    public static function findForTenant(int $id, int $tenantId): ?array
    {
        return Database::one(
            'SELECT * FROM ' . static::$table . ' WHERE id = ? AND tenant_id = ?',
            [$id, $tenantId]
        );
    }

    public static function allForTenant(int $tenantId, string $order = 'id DESC'): array
    {
        return Database::all(
            'SELECT * FROM ' . static::$table . ' WHERE tenant_id = ? ORDER BY ' . $order,
            [$tenantId]
        );
    }

    public static function create(array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . static::$table . ' (' . implode(',', $cols) . ')
                VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        return Database::insert($sql, array_values($data));
    }

    public static function updateRow(int $id, array $data, ?int $tenantId = null): void
    {
        $sets = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        $sql = 'UPDATE ' . static::$table . ' SET ' . $sets . ' WHERE id = ?';
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = ?';
            $params[] = $tenantId;
        }
        Database::run($sql, $params);
    }

    public static function deleteRow(int $id, ?int $tenantId = null): void
    {
        $sql = 'DELETE FROM ' . static::$table . ' WHERE id = ?';
        $params = [$id];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = ?';
            $params[] = $tenantId;
        }
        Database::run($sql, $params);
    }

    public static function countForTenant(int $tenantId, string $where = '1=1', array $params = []): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM ' . static::$table . ' WHERE tenant_id = ? AND ' . $where,
            array_merge([$tenantId], $params)
        );
    }
}
