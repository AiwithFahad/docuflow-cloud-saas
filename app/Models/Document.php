<?php
// DocuFlow Cloud — Document model (all queries tenant-scoped)
declare(strict_types=1);

final class Document extends Model
{
    protected static string $table = 'documents';

    /** Access check: owner, tenant admin, explicit permission row, or team access. */
    public static function userCanAccess(array $doc, array $user, string $action = 'view'): bool
    {
        if ((int) $doc['tenant_id'] !== (int) ($user['tenant_id'] ?? 0)) return false;
        if ($user['role'] === 'company_admin') return true;
        if ((int) $doc['owner_id'] === (int) $user['id']) return true;

        $perm = Database::one(
            'SELECT can_view, can_download, can_edit FROM document_permissions
             WHERE document_id = ? AND ((subject_type = "user" AND subject_id = ?)
                OR (subject_type = "team" AND subject_id IN
                    (SELECT team_id FROM team_members WHERE user_id = ?))) LIMIT 1',
            [(int) $doc['id'], (int) $user['id'], (int) $user['id']]
        );
        if (!$perm) return false;
        return match ($action) {
            'download' => (bool) $perm['can_download'],
            'edit'     => (bool) $perm['can_edit'],
            default    => (bool) $perm['can_view'],
        };
    }

    public static function search(int $tenantId, string $q, ?int $categoryId, ?int $tagId, int $limit, int $offset): array
    {
        $sql = 'SELECT d.*, c.name AS category_name, u.name AS owner_name
                FROM documents d
                LEFT JOIN categories c ON c.id = d.category_id
                LEFT JOIN users u ON u.id = d.owner_id
                WHERE d.tenant_id = ? AND d.deleted_at IS NULL';
        $params = [$tenantId];
        if ($q !== '') {
            $sql .= ' AND (d.name LIKE ? OR d.description LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($categoryId) { $sql .= ' AND d.category_id = ?'; $params[] = $categoryId; }
        if ($tagId) {
            $sql .= ' AND d.id IN (SELECT document_id FROM document_tags WHERE tag_id = ?)';
            $params[] = $tagId;
        }
        $sql .= ' ORDER BY d.updated_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        return Database::all($sql, $params);
    }
}

final class Folder extends Model
{
    protected static string $table = 'folders';

    /** Flat list ordered for indented tree rendering. */
    public static function tree(int $tenantId): array
    {
        $rows = Database::all('SELECT * FROM folders WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);
        $byParent = [];
        foreach ($rows as $r) $byParent[(int) ($r['parent_id'] ?? 0)][] = $r;
        $out = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$out, $byParent) {
            foreach ($byParent[$parent] ?? [] as $row) {
                $row['_depth'] = $depth;
                $out[] = $row;
                $walk((int) $row['id'], $depth + 1);
            }
        };
        $walk(0, 0);
        return $out;
    }

    /** All descendant folder ids (used to prevent moving a folder into itself). */
    public static function descendants(int $tenantId, int $folderId): array
    {
        $ids = [$folderId];
        $frontier = [$folderId];
        while ($frontier) {
            $in = implode(',', array_map('intval', $frontier));
            $kids = Database::all("SELECT id FROM folders WHERE tenant_id = ? AND parent_id IN ($in)", [$tenantId]);
            $frontier = array_map(fn($r) => (int) $r['id'], $kids);
            $ids = array_merge($ids, $frontier);
        }
        return $ids;
    }
}
