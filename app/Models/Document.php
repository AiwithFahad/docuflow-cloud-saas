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

    /**
     * SQL fragment + params restricting a documents query (alias $a) to the
     * rows $user may view. Company admins see the whole tenant workspace;
     * everyone else sees their own documents plus anything shared with them
     * directly or through a team.
     *
     * @return array{0:string,1:array}
     */
    public static function accessibleWhere(array $user, string $a = 'd'): array
    {
        if (($user['role'] ?? '') === 'company_admin') return ['1=1', []];
        $uid = (int) $user['id'];
        $sql = "($a.owner_id = ? OR EXISTS (SELECT 1 FROM document_permissions p
                 WHERE p.document_id = $a.id AND p.can_view = 1 AND
                   ((p.subject_type = 'user' AND p.subject_id = ?)
                    OR (p.subject_type = 'team' AND p.subject_id IN
                        (SELECT team_id FROM team_members WHERE user_id = ?)))))";
        return [$sql, [$uid, $uid, $uid]];
    }

    /**
     * Filtered, permission-aware document listing used by the JSON API and
     * the role dashboard trees. Returns [rows, total].
     * Filters: q, folder ('root'|int|null), category_id, tag_id,
     * department_id, owner_id, status (active|archived|trash|favorites|recent),
     * sort (new|old|name|size|downloads), page, per.
     */
    public static function listing(int $tenantId, array $user, array $f): array
    {
        $where = ['d.tenant_id = ?'];
        $params = [$tenantId];
        $status = (string) ($f['status'] ?? 'active');

        if ($status === 'trash') {
            $where[] = 'd.deleted_at IS NOT NULL';
        } else {
            $where[] = 'd.deleted_at IS NULL';
            if ($status === 'archived') $where[] = "d.status = 'archived'";
            elseif ($status === 'active') $where[] = "d.status = 'active'";
            elseif ($status === 'favorites') {
                $where[] = 'EXISTS (SELECT 1 FROM document_favorites fv WHERE fv.document_id = d.id AND fv.user_id = ?)';
                $params[] = (int) $user['id'];
            } elseif ($status === 'recent') {
                $where[] = 'd.updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)';
            }
        }

        [$accSql, $accParams] = self::accessibleWhere($user);
        $where[] = $accSql;
        $params = array_merge($params, $accParams);

        if (isset($f['folder'])) {
            if ($f['folder'] === 'root') $where[] = 'd.folder_id IS NULL';
            elseif ($f['folder'] !== null && $f['folder'] !== '') { $where[] = 'd.folder_id = ?'; $params[] = (int) $f['folder']; }
        }
        if (!empty($f['q'])) {
            $where[] = '(d.name LIKE ? OR d.description LIKE ?)';
            $params[] = '%' . $f['q'] . '%';
            $params[] = '%' . $f['q'] . '%';
        }
        if (!empty($f['category_id'])) { $where[] = 'd.category_id = ?'; $params[] = (int) $f['category_id']; }
        if (!empty($f['department_id'])) { $where[] = 'd.department_id = ?'; $params[] = (int) $f['department_id']; }
        if (!empty($f['owner_id'])) { $where[] = 'd.owner_id = ?'; $params[] = (int) $f['owner_id']; }
        if (!empty($f['tag_id'])) {
            $where[] = 'd.id IN (SELECT document_id FROM document_tags WHERE tag_id = ?)';
            $params[] = (int) $f['tag_id'];
        }
        if (!empty($f['from'])) { $where[] = 'DATE(d.created_at) >= ?'; $params[] = (string) $f['from']; }
        if (!empty($f['to'])) { $where[] = 'DATE(d.created_at) <= ?'; $params[] = (string) $f['to']; }

        $order = match ((string) ($f['sort'] ?? 'new')) {
            'old' => 'd.updated_at ASC',
            'name' => 'd.name ASC',
            'size' => 'd.size_bytes DESC',
            'downloads' => 'd.download_count DESC',
            default => 'd.updated_at DESC',
        };
        $w = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM documents d WHERE $w", $params);

        $per = max(1, min(100, (int) ($f['per'] ?? 50)));
        $page = max(1, (int) ($f['page'] ?? 1));
        $offset = ($page - 1) * $per;
        $rows = Database::all(
            "SELECT d.*, u.name AS owner_name, c.name AS category_name, dep.name AS department_name,
                    f.name AS folder_name,
                    EXISTS (SELECT 1 FROM document_favorites fv WHERE fv.document_id = d.id AND fv.user_id = ?) AS is_favorite
             FROM documents d
             JOIN users u ON u.id = d.owner_id
             LEFT JOIN categories c ON c.id = d.category_id
             LEFT JOIN departments dep ON dep.id = d.department_id
             LEFT JOIN folders f ON f.id = d.folder_id
             WHERE $w ORDER BY $order LIMIT $per OFFSET $offset",
            array_merge([(int) $user['id']], $params)
        );
        return [$rows, $total];
    }

    /** Toggle a favourite star; returns the new state. */
    public static function toggleFavorite(int $tenantId, int $userId, int $docId): bool
    {
        $exists = Database::value('SELECT id FROM document_favorites WHERE user_id = ? AND document_id = ?', [$userId, $docId]);
        if ($exists) {
            Database::run('DELETE FROM document_favorites WHERE user_id = ? AND document_id = ?', [$userId, $docId]);
            return false;
        }
        Database::insert('INSERT INTO document_favorites (tenant_id, user_id, document_id) VALUES (?,?,?)', [$tenantId, $userId, $docId]);
        return true;
    }

    /** Custom key/value metadata for a document. */
    public static function properties(int $docId): array
    {
        return Database::all('SELECT prop_key, prop_value FROM document_properties WHERE document_id = ? ORDER BY prop_key', [$docId]);
    }
}
