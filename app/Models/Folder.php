<?php
// DocuFlow Cloud — Folder model (extracted from Document.php: the autoloader
// maps one class to one file, so Folder needs its own file to load reliably).
declare(strict_types=1);

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

    /** Breadcrumb chain (root → … → folder) for navigation. */
    public static function breadcrumb(int $tenantId, ?int $folderId): array
    {
        $chain = [];
        $guard = 0;
        while ($folderId && $guard++ < 50) {
            $row = self::findForTenant($folderId, $tenantId);
            if (!$row) break;
            array_unshift($chain, $row);
            $folderId = $row['parent_id'] ? (int) $row['parent_id'] : null;
        }
        return $chain;
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
