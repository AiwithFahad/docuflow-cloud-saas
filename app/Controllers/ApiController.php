<?php
// DocuFlow Cloud — JSON API for the workspace JavaScript layer.
// Every endpoint is session-authenticated, tenant-scoped and permission
// checked; document reads/writes go through Document::userCanAccess() so no
// file is reachable without verified rights. POST endpoints require the CSRF
// token (form field _csrf or X-CSRF-Token header).
declare(strict_types=1);

final class ApiController
{
    private array $user;
    private array $tenant;
    private int $tenantId;

    public function __construct()
    {
        $u = auth_user();
        if (!$u) $this->fail(401, 'Not signed in.');
        if (($u['role'] ?? '') === 'super_admin' || empty($u['tenant_id'])) {
            $this->fail(403, 'This API serves tenant workspaces.');
        }
        $this->user = $u;
        $this->tenant = auth_tenant();
        if (!$this->tenant || ($this->tenant['status'] ?? '') === 'suspended') $this->fail(403, 'Workspace suspended.');
        $this->tenantId = (int) $this->tenant['id'];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_verify()) {
            $this->fail(419, 'CSRF token mismatch. Please refresh the page.');
        }
    }

    private function fail(int $code, string $msg): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $msg]);
        exit;
    }

    private function out(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(array_merge(['ok' => true], $data));
        exit;
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }

    private function docOrFail(int $id, string $action = 'view', bool $includeTrash = false): array
    {
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ?', [$id, $this->tenantId]);
        if (!$doc || (!$includeTrash && $doc['deleted_at']) || !Document::userCanAccess($doc, $this->user, $action)) {
            $this->fail(404, 'Document not found or access denied.');
        }
        return $doc;
    }

    private function docJson(array $d): array
    {
        return [
            'id' => (int) $d['id'], 'name' => $d['name'], 'extension' => $d['extension'],
            'icon' => FileSecurity::iconFor((string) $d['extension']),
            'description' => $d['description'], 'size_bytes' => (int) $d['size_bytes'],
            'size' => human_bytes((int) $d['size_bytes']),
            'folder_id' => $d['folder_id'] ? (int) $d['folder_id'] : null,
            'folder_name' => $d['folder_name'] ?? null,
            'category_name' => $d['category_name'] ?? null,
            'department_name' => $d['department_name'] ?? null,
            'owner_name' => $d['owner_name'] ?? null, 'owner_id' => (int) $d['owner_id'],
            'status' => $d['status'], 'expires_at' => $d['expires_at'],
            'downloads' => (int) $d['download_count'],
            'favorite' => !empty($d['is_favorite']),
            'updated_at' => $d['updated_at'], 'created_at' => $d['created_at'],
            'deleted_at' => $d['deleted_at'],
            'url' => url('app/documents/' . (int) $d['id']),
            'download_url' => url('app/documents/' . (int) $d['id'] . '/download'),
            'previewable' => FileSecurity::isPreviewable((string) $d['extension']),
            'can_download' => Document::userCanAccess($d, $this->user, 'download'),
            'can_edit' => Document::userCanAccess($d, $this->user, 'edit') || can('documents.edit'),
        ];
    }

    /* ------------------------------------------------------------- stats */
    public function stats(): void
    {
        $tid = $this->tenantId;
        $uid = (int) $this->user['id'];
        [$accSql, $accParams] = Document::accessibleWhere($this->user);
        $used = Tenant::storageUsed($tid);
        $sub = Tenant::currentSubscription($tid);
        $capBytes = $sub && (int) $sub['storage_mb'] >= 0 ? (int) $sub['storage_mb'] * 1024 * 1024 : -1;
        $this->out(['stats' => [
            'documents' => (int) Database::value("SELECT COUNT(*) FROM documents d WHERE d.tenant_id = ? AND d.deleted_at IS NULL AND $accSql", array_merge([$tid], $accParams)),
            'folders' => (int) Database::value('SELECT COUNT(*) FROM folders WHERE tenant_id = ?', [$tid]),
            'storage_used' => $used, 'storage_used_human' => human_bytes($used),
            'storage_cap' => $capBytes, 'storage_cap_human' => $capBytes >= 0 ? human_bytes($capBytes) : 'Unlimited',
            'storage_pct' => $capBytes > 0 ? min(100, round($used / $capBytes * 100, 1)) : 0,
            'active_users' => (int) Database::value('SELECT COUNT(*) FROM users WHERE tenant_id = ? AND status = "active"', [$tid]),
            'shared_documents' => (int) Database::value('SELECT COUNT(DISTINCT document_id) FROM document_shares WHERE tenant_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())', [$tid]),
            'pending_approvals' => count(ApprovalService::inbox($tid, $uid)),
            'my_pending_requests' => (int) Database::value('SELECT COUNT(*) FROM approvals WHERE tenant_id = ? AND requested_by = ? AND status = "pending"', [$tid, $uid]),
            'favorites' => (int) Database::value('SELECT COUNT(*) FROM document_favorites WHERE user_id = ?', [$uid]),
            'trash' => (int) Database::value('SELECT COUNT(*) FROM documents WHERE tenant_id = ? AND deleted_at IS NOT NULL', [$tid]),
            'unread_notifications' => (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$uid]),
            'plan' => $sub['plan_name'] ?? null,
        ]]);
    }

    /* ------------------------------------------------------------ charts */
    public function charts(): void
    {
        $tid = $this->tenantId;
        $type = (string) ($_GET['type'] ?? 'uploads');
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from'] ?? '')) ? (string) $_GET['from'] : null;
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to'] ?? '')) ? (string) $_GET['to'] : null;
        $userId = !empty($_GET['user']) ? (int) $_GET['user'] : null;
        $catId = !empty($_GET['category']) ? (int) $_GET['category'] : null;
        $range = '';
        $rp = [];
        if ($from) { $range .= ' AND DATE(created_at) >= ?'; $rp[] = $from; }
        if ($to) { $range .= ' AND DATE(created_at) <= ?'; $rp[] = $to; }

        switch ($type) {
            case 'uploads':
                $w = 'tenant_id = ?' . $range;
                $p = array_merge([$tid], $rp);
                if ($userId) { $w .= ' AND owner_id = ?'; $p[] = $userId; }
                if ($catId) { $w .= ' AND category_id = ?'; $p[] = $catId; }
                $rows = Database::all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, COUNT(*) AS total
                                       FROM documents WHERE $w GROUP BY label ORDER BY label", $p);
                $this->out(['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'total'))]);

            case 'storage':
                $rows = Database::all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, SUM(size_bytes) AS total
                                       FROM documents WHERE tenant_id = ? AND deleted_at IS NULL $range
                                       GROUP BY label ORDER BY label", array_merge([$tid], $rp));
                $run = 0; $data = [];
                foreach ($rows as $r) { $run += (int) $r['total']; $data[] = round($run / 1048576, 2); }
                $this->out(['labels' => array_column($rows, 'label'), 'data' => $data, 'unit' => 'MB (cumulative)']);

            case 'activity':
                $w = 'tenant_id = ?' . $range;
                $p = array_merge([$tid], $rp);
                if ($userId) { $w .= ' AND user_id = ?'; $p[] = $userId; }
                $rows = Database::all("SELECT DATE(created_at) AS label, COUNT(*) AS total
                                       FROM activity_logs WHERE $w GROUP BY label ORDER BY label", $p);
                $this->out(['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'total'))]);

            case 'categories':
                $rows = Database::all('SELECT COALESCE(c.name, "Uncategorised") AS label, COUNT(*) AS total
                                       FROM documents d LEFT JOIN categories c ON c.id = d.category_id
                                       WHERE d.tenant_id = ? AND d.deleted_at IS NULL GROUP BY label ORDER BY total DESC', [$tid]);
                $this->out(['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'total'))]);

            case 'teams':
                $rows = Database::all('SELECT t.name AS label, COUNT(DISTINCT d.id) AS total
                                       FROM teams t
                                       LEFT JOIN team_members tm ON tm.team_id = t.id
                                       LEFT JOIN documents d ON d.owner_id = tm.user_id AND d.tenant_id = t.tenant_id AND d.deleted_at IS NULL
                                       WHERE t.tenant_id = ? GROUP BY t.id ORDER BY total DESC', [$tid]);
                $this->out(['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'total')), 'unit' => 'documents uploaded by members']);

            case 'approvals':
                $rows = Database::all('SELECT status AS label, COUNT(*) AS total FROM approvals WHERE tenant_id = ? GROUP BY status', [$tid]);
                $this->out(['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'total'))]);

            default:
                $this->fail(400, 'Unknown chart type.');
        }
    }

    /* --------------------------------------------------------- documents */
    public function documents(): void
    {
        $f = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'folder' => array_key_exists('folder', $_GET)
                ? (($_GET['folder'] === '' || $_GET['folder'] === 'root') ? 'root' : (int) $_GET['folder']) : null,
            'category_id' => $_GET['category'] ?? null, 'tag_id' => $_GET['tag'] ?? null,
            'department_id' => $_GET['department'] ?? null, 'owner_id' => $_GET['owner'] ?? null,
            'status' => (string) ($_GET['status'] ?? 'active'),
            'sort' => (string) ($_GET['sort'] ?? 'new'),
            'page' => (int) ($_GET['page'] ?? 1), 'per' => (int) ($_GET['per'] ?? 60),
            'from' => $_GET['from'] ?? null, 'to' => $_GET['to'] ?? null,
        ];
        [$rows, $total] = Document::listing($this->tenantId, $this->user, $f);
        $this->out(['documents' => array_map(fn($d) => $this->docJson($d), $rows), 'total' => $total]);
    }

    public function document(int $id): void
    {
        $doc = $this->docOrFail($id);
        $versions = Database::all('SELECT v.version_number, v.size_bytes, v.note, v.created_at, u.name AS uploader
                                   FROM document_versions v JOIN users u ON u.id = v.uploaded_by
                                   WHERE v.document_id = ? ORDER BY v.version_number DESC', [$id]);
        $tags = Database::all('SELECT t.name FROM tags t JOIN document_tags dt ON dt.tag_id = t.id WHERE dt.document_id = ?', [$id]);
        $this->out(['document' => $this->docJson($doc) + [
            'versions' => $versions,
            'tags' => array_column($tags, 'name'),
            'properties' => Document::properties($id),
        ]]);
    }

    public function versions(int $id): void
    {
        $doc = $this->docOrFail($id);
        $versions = Database::all('SELECT v.*, u.name AS uploader FROM document_versions v JOIN users u ON u.id = v.uploaded_by
                                   WHERE v.document_id = ? ORDER BY v.version_number DESC', [$id]);
        $this->out(['current' => [
            'stored_name' => $doc['stored_name'], 'size_bytes' => (int) $doc['size_bytes'],
            'name' => $doc['name'], 'extension' => $doc['extension'], 'updated_at' => $doc['updated_at'],
        ], 'versions' => $versions]);
    }

    public function folders(): void
    {
        $tid = $this->tenantId;
        $folderId = isset($_GET['folder']) && $_GET['folder'] !== '' ? (int) $_GET['folder'] : null;
        if ($folderId && !Folder::findForTenant($folderId, $tid)) $this->fail(404, 'Folder not found.');
        $folders = Database::all(
            'SELECT f.*, (SELECT COUNT(*) FROM documents d WHERE d.folder_id = f.id AND d.deleted_at IS NULL) AS doc_count,
                    (SELECT COUNT(*) FROM folders c WHERE c.parent_id = f.id) AS sub_count
             FROM folders f WHERE f.tenant_id = ? AND ' . ($folderId ? 'f.parent_id = ?' : 'f.parent_id IS NULL') . ' ORDER BY f.name',
            $folderId ? [$tid, $folderId] : [$tid]);
        $this->out([
            'folders' => $folders,
            'breadcrumb' => Folder::breadcrumb($tid, $folderId),
            'tree' => Folder::tree($tid),
        ]);
    }

    public function createFolder(): void
    {
        if (!can('folders.manage') && $this->user['role'] !== 'company_admin') $this->fail(403, 'Your role cannot manage folders.');
        $b = $this->body();
        $name = trim((string) ($b['name'] ?? ''));
        $parent = !empty($b['parent_id']) ? (int) $b['parent_id'] : null;
        if ($name === '') $this->fail(422, 'Folder name is required.');
        if ($parent && !Folder::findForTenant($parent, $this->tenantId)) $this->fail(422, 'Invalid parent folder.');
        $id = Database::insert('INSERT INTO folders (tenant_id, parent_id, name, description, color, created_by, created_at, updated_at)
                                VALUES (?,?,?,?,?,?,NOW(),NOW())',
            [$this->tenantId, $parent, $name, trim((string) ($b['description'] ?? '')), (string) ($b['color'] ?? '#f5b93f'), (int) $this->user['id']]);
        activity_log('folder.created', 'Created folder ' . $name, $this->tenantId);
        $this->out(['id' => (int) $id, 'message' => 'Folder created.']);
    }

    /* -------------------------------------------------------------- bulk */
    public function bulk(): void
    {
        $b = $this->body();
        $action = (string) ($b['action'] ?? '');
        $ids = array_values(array_filter(array_map('intval', (array) ($b['ids'] ?? []))));
        if (!$ids) $this->fail(422, 'No documents selected.');
        $done = 0; $errors = [];

        foreach ($ids as $id) {
            $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ?', [$id, $this->tenantId]);
            if (!$doc) { $errors[] = "#$id not found"; continue; }
            switch ($action) {
                case 'delete':
                    if (!Document::userCanAccess($doc, $this->user, 'edit') && !can('documents.delete')) { $errors[] = $doc['name'] . ': not permitted'; break; }
                    Database::run('UPDATE documents SET deleted_at = NOW() WHERE id = ?', [$id]);
                    audit_log('document', $id, 'deleted');
                    $done++; break;
                case 'restore':
                    if ($this->user['role'] !== 'company_admin' && (int) $doc['owner_id'] !== (int) $this->user['id']) { $errors[] = $doc['name'] . ': not permitted'; break; }
                    Database::run('UPDATE documents SET deleted_at = NULL WHERE id = ?', [$id]);
                    audit_log('document', $id, 'restored');
                    $done++; break;
                case 'purge':
                    if ($this->user['role'] !== 'company_admin') { $errors[] = $doc['name'] . ': admins only'; break; }
                    $path = FileSecurity::resolvePath((string) $doc['stored_name']);
                    if ($path) @unlink($path);
                    Database::run('DELETE FROM documents WHERE id = ?', [$id]);
                    audit_log('document', $id, 'purged');
                    $done++; break;
                case 'move':
                    $target = !empty($b['folder_id']) ? (int) $b['folder_id'] : null;
                    if ($target && !Folder::findForTenant($target, $this->tenantId)) { $errors[] = 'Invalid target folder'; break 2; }
                    if (!Document::userCanAccess($doc, $this->user, 'edit') && !can('documents.edit')) { $errors[] = $doc['name'] . ': not permitted'; break; }
                    Database::run('UPDATE documents SET folder_id = ?, updated_at = NOW() WHERE id = ?', [$target, $id]);
                    audit_log('document', $id, 'moved', ['folder' => $doc['folder_id']], ['folder' => $target]);
                    $done++; break;
                case 'archive':
                case 'unarchive':
                    if (!Document::userCanAccess($doc, $this->user, 'edit') && !can('documents.edit')) { $errors[] = $doc['name'] . ': not permitted'; break; }
                    Database::run('UPDATE documents SET status = ?, updated_at = NOW() WHERE id = ?', [$action === 'archive' ? 'archived' : 'active', $id]);
                    $done++; break;
                case 'favorite':
                case 'unfavorite':
                    if (!Document::userCanAccess($doc, $this->user)) { $errors[] = $doc['name'] . ': no access'; break; }
                    if ($action === 'favorite') {
                        Database::run('INSERT IGNORE INTO document_favorites (tenant_id, user_id, document_id) VALUES (?,?,?)', [$this->tenantId, (int) $this->user['id'], $id]);
                    } else {
                        Database::run('DELETE FROM document_favorites WHERE user_id = ? AND document_id = ?', [(int) $this->user['id'], $id]);
                    }
                    $done++; break;
                default:
                    $this->fail(422, 'Unknown bulk action.');
            }
        }
        if ($done) activity_log('document.bulk_' . $action, "Bulk $action on $done document(s)", $this->tenantId);
        $this->out(['done' => $done, 'errors' => $errors, 'message' => "$done document(s) updated."]);
    }

    public function favorite(int $id): void
    {
        $this->docOrFail($id);
        $state = Document::toggleFavorite($this->tenantId, (int) $this->user['id'], $id);
        $this->out(['favorite' => $state]);
    }

    public function rename(int $id): void
    {
        $doc = $this->docOrFail($id, 'edit');
        $b = $this->body();
        $name = trim((string) ($b['name'] ?? ''));
        if ($name === '') $this->fail(422, 'Name cannot be empty.');
        Database::run('UPDATE documents SET name = ?, updated_at = NOW() WHERE id = ?', [$name, $id]);
        audit_log('document', $id, 'renamed', ['name' => $doc['name']], ['name' => $name]);
        $this->out(['name' => $name, 'message' => 'Renamed.']);
    }

    /* ------------------------------------------------------------ upload */
    public function upload(): void
    {
        if (!can('documents.upload')) $this->fail(403, 'Your role cannot upload documents.');
        $tid = $this->tenantId;
        $folderId = !empty($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;
        if ($folderId && !Folder::findForTenant($folderId, $tid)) $this->fail(422, 'Invalid destination folder.');
        $categoryId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $departmentId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
        $description = trim((string) ($_POST['description'] ?? ''));
        $maxFile = SubscriptionService::maxFileBytes($tid);
        $files = $_FILES['files'] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) $this->fail(422, 'No files received.');

        $storageDir = (string) config('storage_path');
        if (!is_dir($storageDir)) mkdir($storageDir, 0770, true);
        $created = []; $errors = [];
        foreach ($files['name'] as $i => $original) {
            $file = ['name' => $original, 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'error' => $files['error'][$i]];
            [$ok, $err] = FileSecurity::validate($file, $maxFile);
            if (!$ok) { $errors[] = $original . ': ' . $err; continue; }
            if (!SubscriptionService::withinStorageLimit($tid, (int) $file['size'])) { $errors[] = $original . ': storage quota full.'; continue; }
            $stored = FileSecurity::storedName($original);
            if (!move_uploaded_file((string) $file['tmp_name'], $storageDir . DIRECTORY_SEPARATOR . $stored)) { $errors[] = $original . ': could not be stored.'; continue; }
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $docId = Database::insert(
                'INSERT INTO documents (tenant_id, folder_id, category_id, department_id, owner_id, name, description,
                    stored_name, original_name, extension, mime_type, size_bytes, status, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,"active",NOW(),NOW())',
                [$tid, $folderId, $categoryId, $departmentId, (int) $this->user['id'], pathinfo($original, PATHINFO_FILENAME),
                 $description, $stored, $original, $ext,
                 (string) (new finfo(FILEINFO_MIME_TYPE))->file($storageDir . DIRECTORY_SEPARATOR . $stored), (int) $file['size']]);
            Database::run('INSERT INTO document_versions (tenant_id, document_id, version_number, stored_name, size_bytes, uploaded_by, note, created_at)
                           VALUES (?,?,1,?,?,?,"Initial upload",NOW())',
                [$tid, $docId, $stored, (int) $file['size'], (int) $this->user['id']]);
            activity_log('document.uploaded', 'Uploaded ' . $original, $tid);
            audit_log('document', (int) $docId, 'created', null, ['name' => $original]);
            $created[] = ['id' => (int) $docId, 'name' => pathinfo($original, PATHINFO_FILENAME)];
        }
        $this->out(['uploaded' => $created, 'errors' => $errors,
            'message' => count($created) . ' file(s) uploaded.' . ($errors ? ' ' . implode(' ', $errors) : '')]);
    }

    /* ------------------------------------------------------------- share */
    public function createShare(int $id): void
    {
        if (!can('documents.share')) $this->fail(403, 'Your role cannot create share links.');
        $doc = $this->docOrFail($id);
        $settings = Tenant::settings($this->tenantId);
        if (empty($settings['allow_public_shares'])) $this->fail(403, 'Public share links are disabled for this workspace.');
        $b = $this->body();
        $days = max(1, min(365, (int) ($b['days'] ?? 7)));
        $password = (string) ($b['password'] ?? '');
        $token = bin2hex(random_bytes(24));
        Database::run('INSERT INTO document_shares (tenant_id, document_id, created_by, token, can_download, password_hash, expires_at, is_active, created_at)
                       VALUES (?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL ? DAY), 1, NOW())',
            [$this->tenantId, $id, (int) $this->user['id'], $token, !empty($b['can_download']) ? 1 : 0,
             $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null, $days]);
        activity_log('document.shared', 'Share link created for ' . $doc['original_name'], $this->tenantId);
        $this->out(['url' => url('s/' . $token), 'expires_in_days' => $days, 'password_protected' => $password !== '']);
    }

    /* -------------------------------------------------- search / misc -- */
    public function search(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q === '') $this->out(['documents' => [], 'folders' => []]);
        [$rows] = Document::listing($this->tenantId, $this->user, ['q' => $q, 'status' => 'any', 'per' => 12]);
        $folders = Database::all('SELECT id, name, color FROM folders WHERE tenant_id = ? AND name LIKE ? ORDER BY name LIMIT 8',
            [$this->tenantId, '%' . $q . '%']);
        $this->out(['documents' => array_map(fn($d) => $this->docJson($d), $rows), 'folders' => $folders]);
    }

    public function notifications(): void
    {
        $rows = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 25', [(int) $this->user['id']]);
        $this->out(['notifications' => $rows,
            'unread' => (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [(int) $this->user['id']])]);
    }

    public function markNotificationsRead(): void
    {
        $b = $this->body();
        if (!empty($b['id'])) {
            Database::run('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [(int) $b['id'], (int) $this->user['id']]);
        } else {
            Database::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [(int) $this->user['id']]);
        }
        $this->out(['message' => 'Notifications marked as read.']);
    }

    public function activity(): void
    {
        [$accSql, $accParams] = $this->user['role'] === 'company_admin' ? ['1=1', []] : ['user_id = ?', [(int) $this->user['id']]];
        $rows = Database::all("SELECT a.*, u.name AS user_name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                               WHERE a.tenant_id = ? AND $accSql ORDER BY a.id DESC LIMIT 30",
            array_merge([$this->tenantId], $accParams));
        $this->out(['activity' => $rows]);
    }
}
