<?php
// DocuFlow Cloud — tenant (company) workspace: overview, file manager,
// folders, categories, tags, uploads, preview, downloads, teams, users,
// sharing, permissions, approvals, versions, comments, activity, audit,
// storage, reports, settings. Every query is tenant-scoped.
declare(strict_types=1);

final class TenantController
{
    private array $user;
    private array $tenant;
    private int $tenantId;

    public function __construct()
    {
        $this->user = AuthMiddleware::tenant();
        $this->tenant = auth_tenant();
        $this->tenantId = (int) $this->tenant['id'];
    }

    private function base(array $extra = []): array
    {
        $unread = (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [(int) $this->user['id']]);
        $notifs = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [(int) $this->user['id']]);
        return array_merge([
            'user' => $this->user, 'tenant' => $this->tenant,
            'unread' => $unread, 'notifs' => $notifs,
            'sub' => Tenant::currentSubscription($this->tenantId),
        ], $extra);
    }

    /* ------------------------------------------------------------ overview */
    public function dashboard(): void
    {
        $tid = $this->tenantId;
        $stats = [
            'documents' => Document::countForTenant($tid),
            'folders'   => Folder::countForTenant($tid),
            'users'     => (int) Database::value('SELECT COUNT(*) FROM users WHERE tenant_id = ?', [$tid]),
            'teams'     => (int) Database::value('SELECT COUNT(*) FROM teams WHERE tenant_id = ?', [$tid]),
            'storage'   => Tenant::storageUsed($tid),
            'pending_approvals' => (int) Database::value(
                'SELECT COUNT(*) FROM approvals a JOIN approval_steps s ON s.approval_id = a.id
                 WHERE a.tenant_id = ? AND a.status = "pending" AND s.approver_id = ? AND s.status = "pending"',
                [$tid, (int) $this->user['id']]),
            'shares'    => (int) Database::value('SELECT COUNT(*) FROM document_shares WHERE tenant_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())', [$tid]),
        ];
        $recent = Database::all(
            'SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id
             WHERE d.tenant_id = ? AND d.deleted_at IS NULL ORDER BY d.updated_at DESC LIMIT 8', [$tid]);
        $byCategory = Database::all(
            'SELECT COALESCE(c.name,"Uncategorized") AS label, COUNT(*) AS total FROM documents d
             LEFT JOIN categories c ON c.id = d.category_id
             WHERE d.tenant_id = ? AND d.deleted_at IS NULL GROUP BY label ORDER BY total DESC LIMIT 6', [$tid]);
        $uploads = Database::all(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS total FROM documents
             WHERE tenant_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month', [$tid]);
        $activity = Database::all('SELECT * FROM activity_logs WHERE tenant_id = ? ORDER BY id DESC LIMIT 10', [$tid]);
        view('tenant/dashboard', $this->base(compact('stats', 'recent', 'byCategory', 'uploads', 'activity')));
    }

    /* --------------------------------------------------------- file manager */
    public function files(): void
    {
        $tid = $this->tenantId;
        $folderId = isset($_GET['folder']) ? (int) $_GET['folder'] : null;
        $folder = $folderId ? Folder::findForTenant($folderId, $tid) : null;
        if ($folderId && !$folder) { flash('error', 'Folder not found.'); redirect('app/files'); }

        $folders = Database::all('SELECT * FROM folders WHERE tenant_id = ? AND ' .
            ($folderId ? 'parent_id = ?' : 'parent_id IS NULL') . ' ORDER BY name',
            $folderId ? [$tid, $folderId] : [$tid]);
        $docs = Database::all(
            'SELECT d.*, u.name AS owner_name, c.name AS category_name FROM documents d
             JOIN users u ON u.id = d.owner_id LEFT JOIN categories c ON c.id = d.category_id
             WHERE d.tenant_id = ? AND d.deleted_at IS NULL AND ' .
            ($folderId ? 'd.folder_id = ?' : 'd.folder_id IS NULL') . ' ORDER BY d.updated_at DESC',
            $folderId ? [$tid, $folderId] : [$tid]);

        view('tenant/files', $this->base([
            'folder' => $folder, 'folders' => $folders, 'docs' => $docs,
            'tree' => Folder::tree($tid),
            'categories' => Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY name', [$tid]),
            'tags' => Database::all('SELECT * FROM tags WHERE tenant_id = ? ORDER BY name', [$tid]),
            'viewMode' => ($_GET['view'] ?? 'grid') === 'list' ? 'list' : 'grid',
        ]));
    }

    public function search(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $cat = isset($_GET['category']) ? (int) $_GET['category'] : null;
        $tag = isset($_GET['tag']) ? (int) $_GET['tag'] : null;
        $sort = (string) ($_GET['sort'] ?? 'new');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = 20;
        $total = count(Document::search($this->tenantId, $q, $cat, $tag, 100000, 0));
        $pg = paginate($total, $per, $page);
        $docs = Document::search($this->tenantId, $q, $cat, $tag, $per, $pg['offset']);
        if ($sort === 'name') usort($docs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        if ($sort === 'size') usort($docs, fn($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);
        view('tenant/search', $this->base([
            'q' => $q, 'docs' => $docs, 'total' => $total, 'pg' => $pg, 'sort' => $sort,
            'categories' => Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY name', [$this->tenantId]),
            'tags' => Database::all('SELECT * FROM tags WHERE tenant_id = ? ORDER BY name', [$this->tenantId]),
        ]));
    }

    /* ------------------------------------------------------------- uploads */
    public function uploadForm(): void
    {
        view('tenant/upload', $this->base([
            'tree' => Folder::tree($this->tenantId),
            'categories' => Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY name', [$this->tenantId]),
            'tags' => Database::all('SELECT * FROM tags WHERE tenant_id = ? ORDER BY name', [$this->tenantId]),
            'folderId' => isset($_GET['folder']) ? (int) $_GET['folder'] : null,
            'maxFile' => SubscriptionService::maxFileBytes($this->tenantId),
        ]));
    }

    public function upload(): void
    {
        AuthMiddleware::permission('documents.upload');
        require_csrf();
        $tid = $this->tenantId;
        $folderId = !empty($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;
        if ($folderId && !Folder::findForTenant($folderId, $tid)) {
            flash('error', 'Invalid destination folder.');
            redirect('app/upload');
        }
        $categoryId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $description = trim((string) ($_POST['description'] ?? ''));
        $maxFile = SubscriptionService::maxFileBytes($tid);
        $files = $_FILES['files'] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) {
            flash('error', 'No files were received.');
            redirect('app/upload');
        }
        $storageDir = (string) config('storage_path');
        if (!is_dir($storageDir)) mkdir($storageDir, 0770, true);
        $created = 0; $errors = [];

        foreach ($files['name'] as $i => $original) {
            $file = ['name' => $original, 'tmp_name' => $files['tmp_name'][$i],
                     'size' => $files['size'][$i], 'error' => $files['error'][$i]];
            [$ok, $err] = FileSecurity::validate($file, $maxFile);
            if (!$ok) { $errors[] = $original . ': ' . $err; continue; }
            if (!SubscriptionService::withinStorageLimit($tid, (int) $file['size'])) {
                $errors[] = $original . ': storage quota for your plan is full.';
                continue;
            }
            $stored = FileSecurity::storedName($original);
            if (!move_uploaded_file((string) $file['tmp_name'], $storageDir . DIRECTORY_SEPARATOR . $stored)) {
                $errors[] = $original . ': could not be stored.';
                continue;
            }
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $docId = Database::insert(
                'INSERT INTO documents (tenant_id, folder_id, category_id, owner_id, name, description,
                    stored_name, original_name, extension, mime_type, size_bytes, status, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,"active",NOW(),NOW())',
                [$tid, $folderId, $categoryId, (int) $this->user['id'], pathinfo($original, PATHINFO_FILENAME),
                 $description, $stored, $original, $ext,
                 (string) (new finfo(FILEINFO_MIME_TYPE))->file($storageDir . DIRECTORY_SEPARATOR . $stored),
                 (int) $file['size']]
            );
            Database::run('INSERT INTO document_versions (tenant_id, document_id, version_number, stored_name, size_bytes, uploaded_by, note, created_at)
                           VALUES (?,?,1,?,?,?,"Initial upload",NOW())',
                [$tid, $docId, $stored, (int) $file['size'], (int) $this->user['id']]);
            if (!empty($_POST['tag_ids']) && is_array($_POST['tag_ids'])) {
                foreach ($_POST['tag_ids'] as $tagId) {
                    Database::run('INSERT IGNORE INTO document_tags (document_id, tag_id) VALUES (?,?)',
                        [$docId, (int) $tagId]);
                }
            }
            activity_log('document.uploaded', 'Uploaded ' . $original, $tid);
            audit_log('document', $docId, 'created', null, ['name' => $original]);
            $created++;
        }
        if ($created) flash('success', $created . ' file(s) uploaded securely.');
        if ($errors) flash('error', implode(' ', $errors));
        redirect('app/files' . ($folderId ? '?folder=' . $folderId : ''));
    }

    /* ------------------------------------------------------- document view */
    private function docOrFail(int $id): array
    {
        $doc = Database::one(
            'SELECT d.*, u.name AS owner_name, f.name AS folder_name, c.name AS category_name, dep.name AS department_name
             FROM documents d JOIN users u ON u.id = d.owner_id
             LEFT JOIN folders f ON f.id = d.folder_id LEFT JOIN categories c ON c.id = d.category_id
             LEFT JOIN departments dep ON dep.id = d.department_id
             WHERE d.id = ? AND d.tenant_id = ? AND d.deleted_at IS NULL',
            [$id, $this->tenantId]
        );
        if (!$doc || !Document::userCanAccess($doc, $this->user)) {
            http_response_code(404);
            exit('Document not found or access denied.');
        }
        return $doc;
    }

    public function show(int $id): void
    {
        $doc = $this->docOrFail($id);
        $versions = Database::all('SELECT v.*, u.name AS uploader FROM document_versions v JOIN users u ON u.id = v.uploaded_by
                                    WHERE v.document_id = ? ORDER BY v.version_number DESC', [$id]);
        $comments = Database::all('SELECT cm.*, u.name AS author FROM comments cm JOIN users u ON u.id = cm.user_id
                                    WHERE cm.document_id = ? ORDER BY cm.id DESC', [$id]);
        $shares = Database::all('SELECT * FROM document_shares WHERE document_id = ? ORDER BY id DESC', [$id]);
        $perms = Database::all('SELECT * FROM document_permissions WHERE document_id = ?', [$id]);
        $approvals = Database::all('SELECT a.*, u.name AS requester FROM approvals a JOIN users u ON u.id = a.requested_by
                                     WHERE a.document_id = ? ORDER BY a.id DESC', [$id]);
        $tags = Database::all('SELECT t.* FROM tags t JOIN document_tags dt ON dt.tag_id = t.id WHERE dt.document_id = ?', [$id]);
        $tenantUsers = Database::all('SELECT id, name, email, role FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$this->tenantId]);
        $teams = Database::all('SELECT id, name FROM teams WHERE tenant_id = ? ORDER BY name', [$this->tenantId]);
        $properties = Document::properties($id);
        $departments = Database::all('SELECT id, name FROM departments WHERE tenant_id = ? ORDER BY name', [$this->tenantId]);
        $allTags = Database::all('SELECT * FROM tags WHERE tenant_id = ? ORDER BY name', [$this->tenantId]);
        $isFavorite = (bool) Database::value('SELECT id FROM document_favorites WHERE user_id = ? AND document_id = ?', [(int) $this->user['id'], $id]);
        $shareLogs = Database::all('SELECT l.*, s.token FROM share_access_logs l JOIN document_shares s ON s.id = l.share_id
                                    WHERE s.document_id = ? ORDER BY l.id DESC LIMIT 15', [$id]);
        view('tenant/document', $this->base(compact('doc', 'versions', 'comments', 'shares', 'perms', 'approvals', 'tags', 'tenantUsers', 'teams', 'properties', 'departments', 'allTags', 'isFavorite', 'shareLogs')));
    }

    public function preview(int $id): void
    {
        $doc = $this->docOrFail($id);
        $path = FileSecurity::resolvePath((string) $doc['stored_name']);
        if (!$path) { http_response_code(404); exit('File missing.'); }
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . rawurlencode((string) $doc['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function download(int $id): void
    {
        $doc = $this->docOrFail($id);
        if (!Document::userCanAccess($doc, $this->user, 'download')) {
            http_response_code(403);
            exit('Download not permitted for this document.');
        }
        $path = FileSecurity::resolvePath((string) $doc['stored_name']);
        if (!$path) { http_response_code(404); exit('File missing.'); }
        Database::run('UPDATE documents SET download_count = download_count + 1 WHERE id = ?', [$id]);
        activity_log('document.downloaded', 'Downloaded ' . $doc['original_name'], $this->tenantId);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $doc['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function update(int $id): void
    {
        AuthMiddleware::permission('documents.edit');
        require_csrf();
        $doc = $this->docOrFail($id);
        $action = (string) ($_POST['action'] ?? 'rename');
        if ($action === 'rename') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name !== '') {
                $fields = ['name' => $name, 'description' => trim((string) ($_POST['description'] ?? '')),
                    'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
                    'updated_at' => date('Y-m-d H:i:s')];
                if (array_key_exists('department_id', $_POST)) {
                    $fields['department_id'] = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
                }
                if (array_key_exists('expires_at', $_POST)) {
                    $exp = trim((string) $_POST['expires_at']);
                    $fields['expires_at'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp) ? $exp : null;
                }
                if (!empty($_POST['status']) && in_array($_POST['status'], ['active', 'archived'], true)) {
                    $fields['status'] = (string) $_POST['status'];
                }
                Document::updateRow($id, $fields, $this->tenantId);
                audit_log('document', $id, 'renamed', ['name' => $doc['name']], ['name' => $name]);
            }
        } elseif ($action === 'meta') {
            // Custom key/value properties: replace the set for this document.
            Database::run('DELETE FROM document_properties WHERE document_id = ? AND tenant_id = ?', [$id, $this->tenantId]);
            $keys = (array) ($_POST['prop_key'] ?? []);
            $vals = (array) ($_POST['prop_value'] ?? []);
            foreach ($keys as $i => $k) {
                $k = trim((string) $k);
                if ($k === '') continue;
                Database::run('INSERT INTO document_properties (tenant_id, document_id, prop_key, prop_value) VALUES (?,?,?,?)',
                    [$this->tenantId, $id, substr($k, 0, 100), substr(trim((string) ($vals[$i] ?? '')), 0, 500)]);
            }
            // Tags sync from the metadata form.
            if (isset($_POST['tag_ids']) && is_array($_POST['tag_ids'])) {
                Database::run('DELETE FROM document_tags WHERE document_id = ?', [$id]);
                foreach ($_POST['tag_ids'] as $tagId) {
                    Database::run('INSERT IGNORE INTO document_tags (document_id, tag_id) VALUES (?,?)', [$id, (int) $tagId]);
                }
            }
            audit_log('document', $id, 'metadata_updated');
            flash('success', 'Metadata saved.');
            redirect('app/documents/' . $id);
        } elseif ($action === 'move' || $action === 'copy') {
            $target = !empty($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;
            if ($target && !Folder::findForTenant($target, $this->tenantId)) { flash('error', 'Invalid folder.'); redirect('app/documents/' . $id); }
            if ($action === 'move') {
                Document::updateRow($id, ['folder_id' => $target, 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
                audit_log('document', $id, 'moved', ['folder' => $doc['folder_id']], ['folder' => $target]);
            } else {
                $copyName = FileSecurity::storedName((string) $doc['original_name']);
                $src = FileSecurity::resolvePath((string) $doc['stored_name']);
                if ($src) copy($src, (string) config('storage_path') . DIRECTORY_SEPARATOR . $copyName);
                $newId = Database::insert(
                    'INSERT INTO documents (tenant_id, folder_id, category_id, owner_id, name, description, stored_name, original_name, extension, mime_type, size_bytes, status, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,"active",NOW(),NOW())',
                    [$this->tenantId, $target, $doc['category_id'], (int) $this->user['id'], $doc['name'] . ' (copy)',
                     $doc['description'], $copyName, $doc['original_name'], $doc['extension'], $doc['mime_type'], $doc['size_bytes']]);
                Database::run('INSERT INTO document_versions (tenant_id, document_id, version_number, stored_name, size_bytes, uploaded_by, note, created_at)
                               VALUES (?,?,1,?,?,?,"Copy of document #' . $id . '",NOW())',
                    [$this->tenantId, $newId, $copyName, $doc['size_bytes'], (int) $this->user['id']]);
                flash('success', 'Document copied.');
                redirect('app/documents/' . $newId);
            }
        }
        flash('success', 'Document updated.');
        redirect('app/documents/' . $id);
    }

    public function delete(int $id): void
    {
        AuthMiddleware::permission('documents.delete');
        require_csrf();
        $doc = $this->docOrFail($id);
        Database::run('UPDATE documents SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?', [$id, $this->tenantId]);
        activity_log('document.deleted', 'Moved to trash: ' . $doc['original_name'], $this->tenantId);
        audit_log('document', $id, 'deleted');
        flash('success', 'Document moved to trash.');
        redirect('app/files');
    }

    /** Restore a soft-deleted document from trash. */
    public function restore(int $id): void
    {
        require_csrf();
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ? AND deleted_at IS NOT NULL', [$id, $this->tenantId]);
        if (!$doc || ($this->user['role'] !== 'company_admin' && (int) $doc['owner_id'] !== (int) $this->user['id'] && !Document::userCanAccess($doc, $this->user, 'edit'))) {
            flash('error', 'Document not found in trash or access denied.');
            redirect('app/storage');
        }
        Database::run('UPDATE documents SET deleted_at = NULL, updated_at = NOW() WHERE id = ?', [$id]);
        activity_log('document.restored', 'Restored from trash: ' . $doc['original_name'], $this->tenantId);
        audit_log('document', $id, 'restored');
        flash('success', 'Document restored from trash.');
        redirect('app/storage');
    }

    public function newVersion(int $id): void
    {
        AuthMiddleware::permission('documents.edit');
        require_csrf();
        $doc = $this->docOrFail($id);
        $file = $_FILES['file'] ?? null;
        if (!$file) { flash('error', 'No file received.'); redirect('app/documents/' . $id); }
        [$ok, $err] = FileSecurity::validate($file, SubscriptionService::maxFileBytes($this->tenantId));
        if (!$ok) { flash('error', $err); redirect('app/documents/' . $id); }
        $stored = FileSecurity::storedName((string) $file['name']);
        move_uploaded_file((string) $file['tmp_name'], (string) config('storage_path') . DIRECTORY_SEPARATOR . $stored);
        $next = (int) Database::value('SELECT COALESCE(MAX(version_number),0) FROM document_versions WHERE document_id = ?', [$id]) + 1;
        Database::run('INSERT INTO document_versions (tenant_id, document_id, version_number, stored_name, size_bytes, uploaded_by, note, created_at)
                       VALUES (?,?,?,?,?,?,?,NOW())',
            [$this->tenantId, $id, $next, $stored, (int) $file['size'], (int) $this->user['id'], trim((string) ($_POST['note'] ?? ''))]);
        Document::updateRow($id, ['stored_name' => $stored, 'size_bytes' => (int) $file['size'], 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
        // Version history is preserved in full: older versions stay downloadable
        // and restorable (the plan's version_history figure is a guide, not a
        // destructive cap — upgraded per the DocuFlow upgrade spec).
        audit_log('document', $id, 'version_added', null, ['version' => $next]);
        flash('success', 'Version ' . $next . ' uploaded.');
        redirect('app/documents/' . $id);
    }

    public function restoreVersion(int $id, int $versionId): void
    {
        AuthMiddleware::permission('documents.edit');
        require_csrf();
        $doc = $this->docOrFail($id);
        $v = Database::one('SELECT * FROM document_versions WHERE id = ? AND document_id = ? AND tenant_id = ?',
            [$versionId, $id, $this->tenantId]);
        if (!$v) { flash('error', 'Version not found.'); redirect('app/documents/' . $id); }
        Document::updateRow($id, ['stored_name' => $v['stored_name'], 'size_bytes' => (int) $v['size_bytes'], 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
        audit_log('document', $id, 'version_restored', null, ['version' => (int) $v['version_number']]);
        flash('success', 'Restored version ' . (int) $v['version_number'] . '.');
        redirect('app/documents/' . $id);
    }

    public function comment(int $id): void
    {
        require_csrf();
        $this->docOrFail($id);
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body !== '') {
            Database::run('INSERT INTO comments (tenant_id, document_id, user_id, body, created_at) VALUES (?,?,?,?,NOW())',
                [$this->tenantId, $id, (int) $this->user['id'], $body]);
            activity_log('document.commented', 'Comment on document #' . $id, $this->tenantId);
        }
        redirect('app/documents/' . $id);
    }

    /* -------------------------------------------------------------- sharing */
    public function createShare(int $id): void
    {
        AuthMiddleware::permission('documents.share');
        require_csrf();
        $doc = $this->docOrFail($id);
        $days = max(1, min(365, (int) ($_POST['days'] ?? 7)));
        $token = bin2hex(random_bytes(24));
        $password = trim((string) ($_POST['password'] ?? ''));
        Database::run('INSERT INTO document_shares (tenant_id, document_id, created_by, token, can_download, password_hash, expires_at, is_active, created_at)
                       VALUES (?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL ? DAY), 1, NOW())',
            [$this->tenantId, $id, (int) $this->user['id'], $token, !empty($_POST['can_download']) ? 1 : 0,
             $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null, $days]);
        activity_log('document.shared', 'Share link created for ' . $doc['original_name'], $this->tenantId);
        flash('success', 'Share link created: ' . url('s/' . $token));
        redirect('app/documents/' . $id);
    }

    public function revokeShare(int $id, int $shareId): void
    {
        require_csrf();
        Database::run('UPDATE document_shares SET is_active = 0 WHERE id = ? AND document_id = ? AND tenant_id = ?',
            [$shareId, $id, $this->tenantId]);
        flash('success', 'Share link revoked.');
        redirect('app/documents/' . $id);
    }

    public function savePermission(int $id): void
    {
        AuthMiddleware::permission('documents.share');
        require_csrf();
        $this->docOrFail($id);
        $type = (string) ($_POST['subject_type'] ?? 'user');
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        if ($subjectId <= 0 || !in_array($type, ['user', 'team'], true)) {
            flash('error', 'Choose who to share with.');
            redirect('app/documents/' . $id);
        }
        if ($type === 'user' && !User::findForTenant($subjectId, $this->tenantId)) { flash('error', 'User not in your company.'); redirect('app/documents/' . $id); }
        Database::run('INSERT INTO document_permissions (tenant_id, document_id, subject_type, subject_id, can_view, can_download, can_edit, created_at)
                       VALUES (?,?,?,?,?,?,?,NOW())
                       ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_download = VALUES(can_download), can_edit = VALUES(can_edit)',
            [$this->tenantId, $id, $type, $subjectId, 1, !empty($_POST['can_download']) ? 1 : 0, !empty($_POST['can_edit']) ? 1 : 0]);
        audit_log('document', $id, 'permission_updated');
        flash('success', 'Permissions updated.');
        redirect('app/documents/' . $id);
    }

    /* ------------------------------------------------------------ approvals */
    public function approvals(): void
    {
        $tid = $this->tenantId;
        $inbox = Database::all(
            'SELECT a.*, d.name AS doc_name, u.name AS requester, s.id AS step_id
             FROM approval_steps s JOIN approvals a ON a.id = s.approval_id
             JOIN documents d ON d.id = a.document_id JOIN users u ON u.id = a.requested_by
             WHERE a.tenant_id = ? AND s.approver_id = ? AND s.status = "pending" AND a.status = "pending"
             ORDER BY a.id DESC', [$tid, (int) $this->user['id']]);
        $mine = Database::all(
            'SELECT a.*, d.name AS doc_name FROM approvals a JOIN documents d ON d.id = a.document_id
             WHERE a.tenant_id = ? AND a.requested_by = ? ORDER BY a.id DESC LIMIT 30', [$tid, (int) $this->user['id']]);
        $tenantUsers = Database::all('SELECT id, name, role FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]);
        $myDocs = Database::all('SELECT id, name FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY name', [$tid]);
        view('tenant/approvals', $this->base(compact('inbox', 'mine', 'tenantUsers', 'myDocs')));
    }

    public function requestApproval(): void
    {
        require_csrf();
        $docId = (int) ($_POST['document_id'] ?? 0);
        $approverId = (int) ($_POST['approver_id'] ?? 0);
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL', [$docId, $this->tenantId]);
        $approver = User::findForTenant($approverId, $this->tenantId);
        if (!$doc || !$approver) { flash('error', 'Document or approver not found.'); redirect('app/approvals'); }
        $approvalId = Database::insert(
            'INSERT INTO approvals (tenant_id, document_id, requested_by, title, status, created_at, updated_at) VALUES (?,?,?,?,"pending",NOW(),NOW())',
            [$this->tenantId, $docId, (int) $this->user['id'], trim((string) ($_POST['title'] ?? 'Approval request'))]);
        Database::run('INSERT INTO approval_steps (approval_id, step_order, approver_id, status, created_at) VALUES (?,1,?,"pending",NOW())',
            [$approvalId, $approverId]);
        notify_user($approverId, $this->tenantId, 'Approval requested', 'Please review: ' . $doc['name'], 'app/approvals');
        activity_log('approval.requested', 'Approval requested for ' . $doc['name'], $this->tenantId);
        flash('success', 'Approval requested.');
        redirect('app/approvals');
    }

    public function decideApproval(int $stepId): void
    {
        require_csrf();
        $step = Database::one('SELECT s.*, a.tenant_id FROM approval_steps s JOIN approvals a ON a.id = s.approval_id
                               WHERE s.id = ? AND s.approver_id = ? AND s.status = "pending"',
            [$stepId, (int) $this->user['id']]);
        if (!$step || (int) $step['tenant_id'] !== $this->tenantId) { flash('error', 'Approval step not found.'); redirect('app/approvals'); }
        $decision = (string) ($_POST['decision'] ?? '');
        if (!in_array($decision, ['approved', 'rejected'], true)) { redirect('app/approvals'); }
        Database::run('UPDATE approval_steps SET status = ?, comment = ?, decided_at = NOW() WHERE id = ?',
            [$decision, trim((string) ($_POST['comment'] ?? '')), $stepId]);
        $remaining = (int) Database::value('SELECT COUNT(*) FROM approval_steps WHERE approval_id = ? AND status = "pending"', [(int) $step['approval_id']]);
        if ($decision === 'rejected' || $remaining === 0) {
            Database::run('UPDATE approvals SET status = ?, updated_at = NOW() WHERE id = ?', [$decision, (int) $step['approval_id']]);
            $approval = Database::one('SELECT * FROM approvals WHERE id = ?', [(int) $step['approval_id']]);
            notify_user((int) $approval['requested_by'], $this->tenantId, 'Approval ' . $decision,
                'Your request "' . $approval['title'] . '" was ' . $decision . '.', 'app/approvals');
        }
        audit_log('approval', (int) $step['approval_id'], $decision);
        flash('success', 'Decision recorded: ' . $decision . '.');
        redirect('app/approvals');
    }

    /* -------------------------------------------------------------- folders */
    public function createFolder(): void
    {
        AuthMiddleware::permission('folders.manage');
        require_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $parent = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        if ($name === '') { flash('error', 'Folder name is required.'); redirect('app/files'); }
        if ($parent && !Folder::findForTenant($parent, $this->tenantId)) { flash('error', 'Invalid parent folder.'); redirect('app/files'); }
        Folder::create(['tenant_id' => $this->tenantId, 'parent_id' => $parent, 'name' => $name,
            'description' => trim((string) ($_POST['description'] ?? '')), 'color' => (string) ($_POST['color'] ?? '#6d28d9'),
            'created_by' => (int) $this->user['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        activity_log('folder.created', 'Created folder ' . $name, $this->tenantId);
        flash('success', 'Folder created.');
        redirect('app/files' . ($parent ? '?folder=' . $parent : ''));
    }

    public function updateFolder(int $id): void
    {
        AuthMiddleware::permission('folders.manage');
        require_csrf();
        $folder = Folder::findForTenant($id, $this->tenantId);
        if (!$folder) { flash('error', 'Folder not found.'); redirect('app/files'); }
        $action = (string) ($_POST['action'] ?? 'rename');
        if ($action === 'delete') {
            $hasChildren = Folder::countForTenant($this->tenantId, 'parent_id = ?', [$id]);
            $hasDocs = Document::countForTenant($this->tenantId, 'folder_id = ? AND deleted_at IS NULL', [$id]);
            if ($hasChildren || $hasDocs) { flash('error', 'Folder is not empty — move its contents first.'); redirect('app/files'); }
            Folder::deleteRow($id, $this->tenantId);
            flash('success', 'Folder deleted.');
        } elseif ($action === 'move') {
            $target = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
            if ($target && in_array($target, Folder::descendants($this->tenantId, $id), true)) {
                flash('error', 'A folder cannot be moved into itself.');
                redirect('app/files');
            }
            Folder::updateRow($id, ['parent_id' => $target, 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
            flash('success', 'Folder moved.');
        } else {
            Folder::updateRow($id, ['name' => trim((string) ($_POST['name'] ?? $folder['name'])),
                'color' => (string) ($_POST['color'] ?? $folder['color']), 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
            flash('success', 'Folder updated.');
        }
        redirect('app/files');
    }

    /* -------------------------------------------------- categories & tags */
    public function taxonomy(): void
    {
        view('tenant/taxonomy', $this->base([
            'categories' => Database::all('SELECT c.*, (SELECT COUNT(*) FROM documents d WHERE d.category_id = c.id AND d.deleted_at IS NULL) AS docs FROM categories c WHERE c.tenant_id = ? ORDER BY c.name', [$this->tenantId]),
            'tags' => Database::all('SELECT t.*, (SELECT COUNT(*) FROM document_tags dt WHERE dt.tag_id = t.id) AS docs FROM tags t WHERE t.tenant_id = ? ORDER BY t.name', [$this->tenantId]),
        ]));
    }

    public function saveTaxonomy(string $kind): void
    {
        require_csrf();
        $table = $kind === 'category' ? 'categories' : 'tags';
        $name = trim((string) ($_POST['name'] ?? ''));
        $id = (int) ($_POST['id'] ?? 0);
        if ((string) ($_POST['action'] ?? '') === 'delete' && $id) {
            Database::run("DELETE FROM $table WHERE id = ? AND tenant_id = ?", [$id, $this->tenantId]);
            flash('success', ucfirst($kind) . ' deleted.');
        } elseif ($name !== '' && $id) {
            Database::run("UPDATE $table SET name = ? WHERE id = ? AND tenant_id = ?", [$name, $id, $this->tenantId]);
            flash('success', ucfirst($kind) . ' updated.');
        } elseif ($name !== '') {
            Database::run("INSERT INTO $table (tenant_id, name, color, created_at) VALUES (?,?,?,NOW())",
                [$this->tenantId, $name, (string) ($_POST['color'] ?? '#1e3a5f')]);
            flash('success', ucfirst($kind) . ' created.');
        }
        redirect('app/taxonomy');
    }

    /* ------------------------------------------------------------ teams ---- */
    public function teams(): void
    {
        $teams = Database::all('SELECT t.*, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id) AS members
                                FROM teams t WHERE t.tenant_id = ? ORDER BY t.name', [$this->tenantId]);
        $members = Database::all('SELECT tm.*, u.name, u.email, t.name AS team_name FROM team_members tm
                                  JOIN users u ON u.id = tm.user_id JOIN teams t ON t.id = tm.team_id WHERE tm.tenant_id = ?', [$this->tenantId]);
        $tenantUsers = Database::all('SELECT id, name FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$this->tenantId]);
        view('tenant/teams', $this->base(compact('teams', 'members', 'tenantUsers')));
    }

    public function saveTeam(): void
    {
        AuthMiddleware::permission('teams.manage');
        require_csrf();
        $action = (string) ($_POST['action'] ?? 'create');
        if ($action === 'create') {
            if (!SubscriptionService::withinTeamLimit($this->tenantId)) {
                flash('error', 'Your plan team limit has been reached.');
                redirect('app/teams');
            }
            Database::run('INSERT INTO teams (tenant_id, name, description, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())',
                [$this->tenantId, trim((string) $_POST['name']), trim((string) ($_POST['description'] ?? ''))]);
            flash('success', 'Team created.');
        } elseif ($action === 'member_add') {
            $teamId = (int) $_POST['team_id']; $userId = (int) $_POST['user_id'];
            if (User::findForTenant($userId, $this->tenantId)) {
                Database::run('INSERT IGNORE INTO team_members (tenant_id, team_id, user_id, role, created_at) VALUES (?,?,?, "member", NOW())',
                    [$this->tenantId, $teamId, $userId]);
                flash('success', 'Member added.');
            }
        } elseif ($action === 'member_remove') {
            Database::run('DELETE FROM team_members WHERE id = ? AND tenant_id = ?', [(int) $_POST['member_id'], $this->tenantId]);
            flash('success', 'Member removed.');
        } elseif ($action === 'delete') {
            Database::run('DELETE FROM teams WHERE id = ? AND tenant_id = ?', [(int) $_POST['team_id'], $this->tenantId]);
            flash('success', 'Team deleted.');
        }
        redirect('app/teams');
    }

    /* ------------------------------------------------------------ users ---- */
    public function users(): void
    {
        AuthMiddleware::permission('users.manage');
        $users = Database::all('SELECT * FROM users WHERE tenant_id = ? ORDER BY name', [$this->tenantId]);
        $invites = Database::all('SELECT * FROM invitations WHERE tenant_id = ? ORDER BY id DESC', [$this->tenantId]);
        view('tenant/users', $this->base(compact('users', 'invites')));
    }

    public function inviteUser(): void
    {
        AuthMiddleware::permission('users.manage');
        require_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = (string) ($_POST['role'] ?? 'member');
        $allowedRoles = ['company_admin', 'manager', 'member', 'viewer'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowedRoles, true)) {
            flash('error', 'Valid email and role are required.');
            redirect('app/users');
        }
        if (User::findByEmail($email)) { flash('error', 'That email already has an account.'); redirect('app/users'); }
        if (!SubscriptionService::withinUserLimit($this->tenantId)) {
            flash('error', 'Your plan user limit has been reached — upgrade to invite more people.');
            redirect('app/subscription');
        }
        $token = bin2hex(random_bytes(24));
        Database::run('INSERT INTO invitations (tenant_id, email, role, token, status, invited_by, expires_at, created_at)
                       VALUES (?,?,?,?,"pending",?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())',
            [$this->tenantId, $email, $role, $token, (int) $this->user['id']]);
        flash('success', 'Invitation created. Share this link: ' . url('accept-invite?token=' . $token));
        redirect('app/users');
    }

    public function updateUser(int $id): void
    {
        AuthMiddleware::permission('users.manage');
        require_csrf();
        $target = User::findForTenant($id, $this->tenantId);
        if (!$target || (int) $target['id'] === (int) $this->user['id']) { redirect('app/users'); }
        $role = (string) ($_POST['role'] ?? $target['role']);
        $status = (string) ($_POST['status'] ?? $target['status']);
        if (in_array($role, ['company_admin', 'manager', 'member', 'viewer'], true)) {
            User::updateRow($id, ['role' => $role, 'status' => in_array($status, ['active', 'disabled'], true) ? $status : 'active',
                'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
            audit_log('user', $id, 'updated', ['role' => $target['role']], ['role' => $role]);
            flash('success', 'User updated.');
        }
        redirect('app/users');
    }

    /* ------------------------------------------------- activity & reports  */
    public function activity(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = (int) Database::value('SELECT COUNT(*) FROM activity_logs WHERE tenant_id = ?', [$this->tenantId]);
        $pg = paginate($total, 25, $page);
        $logs = Database::all('SELECT a.*, u.name AS user_name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                               WHERE a.tenant_id = ? ORDER BY a.id DESC LIMIT 25 OFFSET ' . $pg['offset'], [$this->tenantId]);
        view('tenant/activity', $this->base(compact('logs', 'pg', 'total')));
    }

    public function audit(): void
    {
        $logs = Database::all('SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                               WHERE a.tenant_id = ? ORDER BY a.id DESC LIMIT 100', [$this->tenantId]);
        view('tenant/audit', $this->base(compact('logs')));
    }

    public function storage(): void
    {
        $used = Tenant::storageUsed($this->tenantId);
        $byType = Database::all('SELECT extension, COUNT(*) AS files, SUM(size_bytes) AS bytes FROM documents
                                 WHERE tenant_id = ? AND deleted_at IS NULL GROUP BY extension ORDER BY bytes DESC', [$this->tenantId]);
        $largest = Database::all('SELECT * FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY size_bytes DESC LIMIT 10', [$this->tenantId]);
        $trash = Database::all('SELECT * FROM documents WHERE tenant_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC', [$this->tenantId]);
        view('tenant/storage', $this->base(compact('used', 'byType', 'largest', 'trash')));
    }

    public function purgeTrash(int $id): void
    {
        AuthMiddleware::permission('documents.delete');
        require_csrf();
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ? AND deleted_at IS NOT NULL', [$id, $this->tenantId]);
        if ($doc) {
            $path = FileSecurity::resolvePath((string) $doc['stored_name']);
            if ($path) @unlink($path);
            Database::run('DELETE FROM documents WHERE id = ?', [$id]);
            audit_log('document', $id, 'purged');
        }
        flash('success', 'Document permanently deleted.');
        redirect('app/storage');
    }

    public function reports(): void
    {
        $tid = $this->tenantId;
        $report = [
            'uploads_by_month' => Database::all('SELECT DATE_FORMAT(created_at, "%b %Y") AS label, COUNT(*) AS total, SUM(size_bytes) AS bytes FROM documents WHERE tenant_id = ? GROUP BY label, DATE_FORMAT(created_at, "%Y-%m") ORDER BY DATE_FORMAT(created_at, "%Y-%m") DESC LIMIT 12', [$tid]),
            'top_downloads' => Database::all('SELECT name, download_count FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY download_count DESC LIMIT 10', [$tid]),
            'by_user' => Database::all('SELECT u.name, COUNT(d.id) AS docs, COALESCE(SUM(d.size_bytes),0) AS bytes FROM users u LEFT JOIN documents d ON d.owner_id = u.id AND d.deleted_at IS NULL WHERE u.tenant_id = ? GROUP BY u.id ORDER BY docs DESC', [$tid]),
            'approvals' => Database::all('SELECT status, COUNT(*) AS total FROM approvals WHERE tenant_id = ? GROUP BY status', [$tid]),
        ];
        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="docuflow-documents-report.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Extension', 'Size (bytes)', 'Downloads', 'Owner', 'Created']);
            foreach (Database::all('SELECT d.name, d.extension, d.size_bytes, d.download_count, u.name AS owner, d.created_at FROM documents d JOIN users u ON u.id = d.owner_id WHERE d.tenant_id = ? AND d.deleted_at IS NULL', [$tid]) as $r) {
                fputcsv($out, [$r['name'], $r['extension'], $r['size_bytes'], $r['download_count'], $r['owner'], $r['created_at']]);
            }
            fclose($out);
            exit;
        }
        view('tenant/reports', $this->base(compact('report')));
    }

    /* ------------------------------------------------------------ settings  */
    public function settings(): void
    {
        view('tenant/settings', $this->base([
            'settings' => Tenant::settings($this->tenantId),
        ]));
    }

    public function saveSettings(): void
    {
        AuthMiddleware::permission('settings.manage');
        require_csrf();
        $name = trim((string) ($_POST['company_name'] ?? $this->tenant['name']));
        $brand = (string) ($_POST['brand_color'] ?? '#1e3a5f');
        $logoPath = $this->tenant['logo'] ?? null;
        if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? 1) === UPLOAD_ERR_OK) {
            [$ok] = FileSecurity::validate($_FILES['logo'], 2 * 1024 * 1024);
            $ext = strtolower(pathinfo((string) $_FILES['logo']['name'], PATHINFO_EXTENSION));
            if ($ok && in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                $logoName = 'logo-' . $this->tenantId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                move_uploaded_file((string) $_FILES['logo']['tmp_name'], (string) config('upload_path') . DIRECTORY_SEPARATOR . $logoName);
                $logoPath = 'assets/uploads/' . $logoName;
            }
        }
        Tenant::updateRow($this->tenantId, ['name' => $name, 'brand_color' => $brand, 'logo' => $logoPath,
            'updated_at' => date('Y-m-d H:i:s')]);
        Database::run('INSERT INTO tenant_settings (tenant_id, default_approval_required, share_link_days, allow_public_shares)
                       VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE default_approval_required = VALUES(default_approval_required),
                       share_link_days = VALUES(share_link_days), allow_public_shares = VALUES(allow_public_shares)',
            [$this->tenantId, !empty($_POST['default_approval_required']) ? 1 : 0,
             max(1, (int) ($_POST['share_link_days'] ?? 7)), !empty($_POST['allow_public_shares']) ? 1 : 0]);
        audit_log('tenant', $this->tenantId, 'settings_updated');
        flash('success', 'Settings saved.');
        redirect('app/settings');
    }

    public function profile(): void
    {
        view('tenant/profile', $this->base());
    }

    public function saveProfile(): void
    {
        require_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            User::updateRow((int) $this->user['id'], ['name' => $name, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        $new = (string) ($_POST['new_password'] ?? '');
        if ($new !== '' && strlen($new) >= 8 &&
            password_verify((string) ($_POST['current_password'] ?? ''), (string) $this->user['password_hash'])) {
            User::updateRow((int) $this->user['id'], ['password_hash' => password_hash($new, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s')]);
            flash('success', 'Profile and password updated.');
        } elseif ($new !== '') {
            flash('error', 'Current password incorrect or new password too short.');
        } else {
            flash('success', 'Profile updated.');
        }
        redirect('app/profile');
    }

    public function markNotificationsRead(): void
    {
        require_csrf();
        Database::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [(int) $this->user['id']]);
        redirect('app');
    }
}
