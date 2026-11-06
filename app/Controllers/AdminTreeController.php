<?php
// DocuFlow Cloud — Organization Admin dashboard TREE (admin/*).
// Company-admin only. Every query is tenant-scoped; document operations go
// through the same permission model as the classic workspace. The file
// manager page is driven by the JSON API (ApiController) + docuflow.js.
declare(strict_types=1);

final class AdminTreeController
{
    private array $user;
    private array $tenant;
    private int $tenantId;

    public function __construct()
    {
        $this->user = AuthMiddleware::tenant();
        if (($this->user['role'] ?? '') !== 'company_admin') {
            flash('error', 'The admin console is for organization admins.');
            redirect(($this->user['role'] ?? '') === 'manager' ? 'manager/dashboard' : 'employee/dashboard');
        }
        $this->tenant = auth_tenant();
        $this->tenantId = (int) $this->tenant['id'];
    }

    private function base(array $extra = []): array
    {
        $unread = (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [(int) $this->user['id']]);
        $notifs = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [(int) $this->user['id']]);
        return array_merge([
            'user' => $this->user, 'tenant' => $this->tenant, 'tree' => 'admin',
            'unread' => $unread, 'notifs' => $notifs,
            'sub' => Tenant::currentSubscription($this->tenantId),
        ], $extra);
    }

    /* ------------------------------------------------------------- dashboard */
    public function dashboard(): void
    {
        $tid = $this->tenantId;
        $used = Tenant::storageUsed($tid);
        $sub = Tenant::currentSubscription($tid);
        $cap = $sub && (int) $sub['storage_mb'] >= 0 ? (int) $sub['storage_mb'] * 1024 * 1024 : -1;
        $stats = [
            'documents' => Document::countForTenant($tid),
            'folders' => Folder::countForTenant($tid),
            'users' => (int) Database::value('SELECT COUNT(*) FROM users WHERE tenant_id = ? AND status = "active"', [$tid]),
            'teams' => (int) Database::value('SELECT COUNT(*) FROM teams WHERE tenant_id = ?', [$tid]),
            'storage_used' => $used, 'storage_cap' => $cap,
            'storage_pct' => $cap > 0 ? min(100, round($used / $cap * 100, 1)) : 0,
            'shared' => (int) Database::value('SELECT COUNT(DISTINCT document_id) FROM document_shares WHERE tenant_id = ? AND is_active = 1', [$tid]),
            'pending_approvals' => (int) Database::value('SELECT COUNT(*) FROM approvals WHERE tenant_id = ? AND status = "pending"', [$tid]),
            'trash' => (int) Database::value('SELECT COUNT(*) FROM documents WHERE tenant_id = ? AND deleted_at IS NOT NULL', [$tid]),
            'expiring' => (int) Database::value('SELECT COUNT(*) FROM documents WHERE tenant_id = ? AND deleted_at IS NULL AND expires_at IS NOT NULL AND expires_at <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)', [$tid]),
        ];
        $recent = Database::all('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id
                                 WHERE d.tenant_id = ? AND d.deleted_at IS NULL ORDER BY d.updated_at DESC LIMIT 8', [$tid]);
        $topDownloads = Database::all('SELECT * FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY download_count DESC LIMIT 5', [$tid]);
        $activity = Database::all('SELECT a.*, u.name AS user_name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                                   WHERE a.tenant_id = ? ORDER BY a.id DESC LIMIT 10', [$tid]);
        view('admin/dashboard', $this->base(compact('stats', 'recent', 'topDownloads', 'activity')));
    }

    /* ----------------------------------------------------------------- files */
    public function files(): void
    {
        $tid = $this->tenantId;
        view('admin/files', $this->base([
            'folderTree' => Folder::tree($tid),
            'categories' => Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY name', [$tid]),
            'tags' => Database::all('SELECT * FROM tags WHERE tenant_id = ? ORDER BY name', [$tid]),
            'departments' => Database::all('SELECT * FROM departments WHERE tenant_id = ? ORDER BY name', [$tid]),
            'tenantUsers' => Database::all('SELECT id, name FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]),
            'initialFolder' => isset($_GET['folder']) ? (int) $_GET['folder'] : null,
            'initialQ' => trim((string) ($_GET['q'] ?? '')),
        ]));
    }

    /* --------------------------------------------------------------- folders */
    public function folders(): void
    {
        $tid = $this->tenantId;
        $folders = Database::all(
            'SELECT f.*, u.name AS creator, p.name AS parent_name,
                    (SELECT COUNT(*) FROM documents d WHERE d.folder_id = f.id AND d.deleted_at IS NULL) AS docs,
                    (SELECT COUNT(*) FROM folders c WHERE c.parent_id = f.id) AS subs
             FROM folders f LEFT JOIN users u ON u.id = f.created_by LEFT JOIN folders p ON p.id = f.parent_id
             WHERE f.tenant_id = ? ORDER BY COALESCE(f.parent_id, 0), f.name', [$tid]);
        view('admin/folders', $this->base(['folders' => $folders, 'folderTree' => Folder::tree($tid)]));
    }

    public function saveFolder(): void
    {
        require_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? 'create');
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $parent = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
            if ($name === '') { flash('error', 'Folder name is required.'); redirect('admin/folders'); }
            if ($parent && !Folder::findForTenant($parent, $this->tenantId)) { flash('error', 'Invalid parent folder.'); redirect('admin/folders'); }
            Folder::create(['tenant_id' => $this->tenantId, 'parent_id' => $parent, 'name' => $name,
                'description' => trim((string) ($_POST['description'] ?? '')), 'color' => (string) ($_POST['color'] ?? '#f5b93f'),
                'created_by' => (int) $this->user['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            activity_log('folder.created', 'Created folder ' . $name, $this->tenantId);
            flash('success', 'Folder created.');
        } elseif ($id && $action === 'rename') {
            Folder::updateRow($id, ['name' => trim((string) ($_POST['name'] ?? '')), 'color' => (string) ($_POST['color'] ?? '#f5b93f'),
                'description' => trim((string) ($_POST['description'] ?? '')), 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
            flash('success', 'Folder updated.');
        } elseif ($id && $action === 'move') {
            $target = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
            if ($target && in_array($target, Folder::descendants($this->tenantId, $id), true)) {
                flash('error', 'A folder cannot be moved into itself.');
                redirect('admin/folders');
            }
            Folder::updateRow($id, ['parent_id' => $target, 'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
            flash('success', 'Folder moved.');
        } elseif ($id && $action === 'delete') {
            $hasChildren = Folder::countForTenant($this->tenantId, 'parent_id = ?', [$id]);
            $hasDocs = Document::countForTenant($this->tenantId, 'folder_id = ? AND deleted_at IS NULL', [$id]);
            if ($hasChildren || $hasDocs) { flash('error', 'Folder is not empty — move its contents first.'); redirect('admin/folders'); }
            Folder::deleteRow($id, $this->tenantId);
            flash('success', 'Folder deleted.');
        }
        redirect('admin/folders');
    }

    /* ----------------------------------------------------------------- users */
    public function users(): void
    {
        $users = Database::all('SELECT * FROM users WHERE tenant_id = ? ORDER BY name', [$this->tenantId]);
        $invites = Database::all('SELECT * FROM invitations WHERE tenant_id = ? ORDER BY id DESC', [$this->tenantId]);
        view('admin/users', $this->base(compact('users', 'invites')));
    }

    public function inviteUser(): void
    {
        require_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = (string) ($_POST['role'] ?? 'member');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['company_admin', 'manager', 'member', 'viewer'], true)) {
            flash('error', 'Valid email and role are required.');
            redirect('admin/users');
        }
        if (User::findByEmail($email)) { flash('error', 'That email already has an account.'); redirect('admin/users'); }
        if (!SubscriptionService::withinUserLimit($this->tenantId)) {
            flash('error', 'Your plan user limit has been reached — upgrade to invite more people.');
            redirect('app/subscription');
        }
        $token = bin2hex(random_bytes(24));
        Database::run('INSERT INTO invitations (tenant_id, email, role, token, status, invited_by, expires_at, created_at)
                       VALUES (?,?,?,?, "pending", ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())',
            [$this->tenantId, $email, $role, $token, (int) $this->user['id']]);
        flash('success', 'Invitation created. Share this link: ' . url('accept-invite?token=' . $token));
        redirect('admin/users');
    }

    public function updateUser(int $id): void
    {
        require_csrf();
        $target = User::findForTenant($id, $this->tenantId);
        if (!$target || (int) $target['id'] === (int) $this->user['id']) { redirect('admin/users'); }
        $role = (string) ($_POST['role'] ?? $target['role']);
        $status = (string) ($_POST['status'] ?? $target['status']);
        if (in_array($role, ['company_admin', 'manager', 'member', 'viewer'], true)) {
            User::updateRow($id, ['role' => $role, 'status' => in_array($status, ['active', 'disabled'], true) ? $status : 'active',
                'updated_at' => date('Y-m-d H:i:s')], $this->tenantId);
            audit_log('user', $id, 'updated', ['role' => $target['role']], ['role' => $role]);
            flash('success', 'User updated.');
        }
        redirect('admin/users');
    }

    /* ----------------------------------------------------------------- teams */
    public function teams(): void
    {
        $teams = Database::all('SELECT t.*, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id) AS members
                                FROM teams t WHERE t.tenant_id = ? ORDER BY t.name', [$this->tenantId]);
        $members = Database::all('SELECT tm.*, u.name, u.email, t.name AS team_name FROM team_members tm
                                  JOIN users u ON u.id = tm.user_id JOIN teams t ON t.id = tm.team_id WHERE tm.tenant_id = ?', [$this->tenantId]);
        $tenantUsers = Database::all('SELECT id, name FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$this->tenantId]);
        view('admin/teams', $this->base(compact('teams', 'members', 'tenantUsers')));
    }

    public function saveTeam(): void
    {
        require_csrf();
        $action = (string) ($_POST['action'] ?? 'create');
        if ($action === 'create') {
            if (!SubscriptionService::withinTeamLimit($this->tenantId)) {
                flash('error', 'Your plan team limit has been reached.');
                redirect('admin/teams');
            }
            Database::run('INSERT INTO teams (tenant_id, name, description, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())',
                [$this->tenantId, trim((string) $_POST['name']), trim((string) ($_POST['description'] ?? ''))]);
            flash('success', 'Team created.');
        } elseif ($action === 'member_add') {
            $userId = (int) $_POST['user_id'];
            if (User::findForTenant($userId, $this->tenantId)) {
                Database::run('INSERT IGNORE INTO team_members (tenant_id, team_id, user_id, role, created_at) VALUES (?,?,?, "member", NOW())',
                    [$this->tenantId, (int) $_POST['team_id'], $userId]);
                flash('success', 'Member added.');
            }
        } elseif ($action === 'member_remove') {
            Database::run('DELETE FROM team_members WHERE id = ? AND tenant_id = ?', [(int) $_POST['member_id'], $this->tenantId]);
            flash('success', 'Member removed.');
        } elseif ($action === 'delete') {
            Database::run('DELETE FROM teams WHERE id = ? AND tenant_id = ?', [(int) $_POST['team_id'], $this->tenantId]);
            flash('success', 'Team deleted.');
        }
        redirect('admin/teams');
    }

    /* ------------------------------------------- categories, tags, departments */
    public function categories(): void
    {
        $tid = $this->tenantId;
        view('admin/categories', $this->base([
            'categories' => Database::all('SELECT c.*, (SELECT COUNT(*) FROM documents d WHERE d.category_id = c.id AND d.deleted_at IS NULL) AS docs FROM categories c WHERE c.tenant_id = ? ORDER BY c.name', [$tid]),
            'tags' => Database::all('SELECT t.*, (SELECT COUNT(*) FROM document_tags dt WHERE dt.tag_id = t.id) AS docs FROM tags t WHERE t.tenant_id = ? ORDER BY t.name', [$tid]),
            'departments' => Database::all('SELECT dep.*, u.name AS head_name, (SELECT COUNT(*) FROM documents d WHERE d.department_id = dep.id AND d.deleted_at IS NULL) AS docs FROM departments dep LEFT JOIN users u ON u.id = dep.head_id WHERE dep.tenant_id = ? ORDER BY dep.name', [$tid]),
            'tenantUsers' => Database::all('SELECT id, name FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]),
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
        redirect('admin/categories');
    }

    public function saveDepartment(): void
    {
        require_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete' && $id) {
            Database::run('DELETE FROM departments WHERE id = ? AND tenant_id = ?', [$id, $this->tenantId]);
            flash('success', 'Department deleted. Documents keep existing; their department is cleared.');
        } elseif ($id) {
            Database::run('UPDATE departments SET name = ?, color = ?, head_id = ? WHERE id = ? AND tenant_id = ?',
                [trim((string) $_POST['name']), (string) ($_POST['color'] ?? '#6d28d9'),
                 !empty($_POST['head_id']) ? (int) $_POST['head_id'] : null, $id, $this->tenantId]);
            flash('success', 'Department updated.');
        } else {
            Database::run('INSERT INTO departments (tenant_id, name, color, head_id, created_at) VALUES (?,?,?,?,NOW())',
                [$this->tenantId, trim((string) $_POST['name']), (string) ($_POST['color'] ?? '#6d28d9'),
                 !empty($_POST['head_id']) ? (int) $_POST['head_id'] : null]);
            flash('success', 'Department created.');
        }
        redirect('admin/categories');
    }

    /* --------------------------------------------------------------- sharing */
    public function sharing(): void
    {
        $tid = $this->tenantId;
        $shares = Database::all('SELECT s.*, d.name AS doc_name, u.name AS creator FROM document_shares s
                                 JOIN documents d ON d.id = s.document_id JOIN users u ON u.id = s.created_by
                                 WHERE s.tenant_id = ? ORDER BY s.id DESC', [$tid]);
        $perms = Database::all('SELECT p.*, d.name AS doc_name FROM document_permissions p
                                JOIN documents d ON d.id = p.document_id WHERE p.tenant_id = ? ORDER BY p.id DESC', [$tid]);
        $logs = Database::all('SELECT l.*, d.name AS doc_name FROM share_access_logs l
                               JOIN document_shares s ON s.id = l.share_id JOIN documents d ON d.id = s.document_id
                               WHERE l.tenant_id = ? ORDER BY l.id DESC LIMIT 40', [$tid]);
        view('admin/sharing', $this->base(compact('shares', 'perms', 'logs')));
    }

    public function shareAction(int $shareId): void
    {
        require_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'revoke') {
            Database::run('UPDATE document_shares SET is_active = 0 WHERE id = ? AND tenant_id = ?', [$shareId, $this->tenantId]);
            flash('success', 'Share link revoked.');
        } elseif ($action === 'password') {
            $pw = trim((string) ($_POST['password'] ?? ''));
            Database::run('UPDATE document_shares SET password_hash = ? WHERE id = ? AND tenant_id = ?',
                [$pw !== '' ? password_hash($pw, PASSWORD_BCRYPT) : null, $shareId, $this->tenantId]);
            flash('success', $pw !== '' ? 'Link password set.' : 'Link password removed.');
        }
        redirect('admin/sharing');
    }

    public function removePermission(int $permId): void
    {
        require_csrf();
        Database::run('DELETE FROM document_permissions WHERE id = ? AND tenant_id = ?', [$permId, $this->tenantId]);
        flash('success', 'Permission removed.');
        redirect('admin/sharing');
    }

    /* ------------------------------------------------------------- approvals */
    public function approvals(): void
    {
        $tid = $this->tenantId;
        $inbox = ApprovalService::inbox($tid, (int) $this->user['id']);
        $all = Database::all('SELECT a.*, d.name AS doc_name, u.name AS requester_name, w.name AS workflow_name
                              FROM approvals a JOIN documents d ON d.id = a.document_id
                              JOIN users u ON u.id = a.requested_by
                              LEFT JOIN approval_workflows w ON w.id = a.workflow_id
                              WHERE a.tenant_id = ? ORDER BY a.id DESC LIMIT 50', [$tid]);
        $steps = Database::all('SELECT s.*, u.name AS approver_name FROM approval_steps s JOIN users u ON u.id = s.approver_id
                                WHERE s.approval_id IN (SELECT id FROM approvals WHERE tenant_id = ?) ORDER BY s.approval_id, s.step_order', [$tid]);
        $workflows = Database::all('SELECT w.*, dep.name AS department_name, c.name AS category_name,
                                           (SELECT COUNT(*) FROM approval_workflow_steps ws WHERE ws.workflow_id = w.id) AS step_count
                                    FROM approval_workflows w LEFT JOIN departments dep ON dep.id = w.department_id
                                    LEFT JOIN categories c ON c.id = w.category_id WHERE w.tenant_id = ? ORDER BY w.name', [$tid]);
        $docs = Database::all('SELECT id, name FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY name', [$tid]);
        $tenantUsers = Database::all('SELECT id, name, role FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]);
        $departments = Database::all('SELECT id, name FROM departments WHERE tenant_id = ? ORDER BY name', [$tid]);
        $categories = Database::all('SELECT id, name FROM categories WHERE tenant_id = ? ORDER BY name', [$tid]);
        view('admin/approvals', $this->base(compact('inbox', 'all', 'steps', 'workflows', 'docs', 'tenantUsers', 'departments', 'categories')));
    }

    public function requestApproval(): void
    {
        require_csrf();
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL',
            [(int) ($_POST['document_id'] ?? 0), $this->tenantId]);
        if (!$doc) { flash('error', 'Document not found.'); redirect('admin/approvals'); }
        try {
            ApprovalService::request($this->tenantId, $this->user, $doc, trim((string) ($_POST['title'] ?? '')),
                !empty($_POST['workflow_id']) ? (int) $_POST['workflow_id'] : null,
                !empty($_POST['approver_id']) ? (int) $_POST['approver_id'] : null);
            flash('success', 'Approval requested.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect('admin/approvals');
    }

    public function decideApproval(int $approvalId): void
    {
        require_csrf();
        [$ok, $msg] = ApprovalService::decide($this->tenantId, $this->user, $approvalId,
            (string) ($_POST['decision'] ?? ''), trim((string) ($_POST['comment'] ?? '')));
        flash($ok ? 'success' : 'error', $msg);
        redirect('admin/approvals');
    }

    public function saveWorkflow(): void
    {
        require_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete' && $id) {
            Database::run('DELETE FROM approval_workflows WHERE id = ? AND tenant_id = ?', [$id, $this->tenantId]);
            flash('success', 'Workflow deleted.');
            redirect('admin/approvals');
        }
        $data = [trim((string) $_POST['name']), trim((string) ($_POST['description'] ?? '')),
                 !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
                 !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
                 !empty($_POST['is_active']) ? 1 : 0];
        if ($id) {
            Database::run('UPDATE approval_workflows SET name = ?, description = ?, department_id = ?, category_id = ?, is_active = ? WHERE id = ? AND tenant_id = ?',
                array_merge($data, [$id, $this->tenantId]));
            Database::run('DELETE FROM approval_workflow_steps WHERE workflow_id = ?', [$id]);
            $wfId = $id;
        } else {
            $wfId = Database::insert('INSERT INTO approval_workflows (tenant_id, name, description, department_id, category_id, is_active, created_by, created_at, updated_at)
                                      VALUES (?,?,?,?,?,?,?,NOW(),NOW())',
                array_merge([$this->tenantId], $data, [(int) $this->user['id']]));
        }
        $approvers = (array) ($_POST['step_approver'] ?? []);
        $labels = (array) ($_POST['step_label'] ?? []);
        $order = 0;
        foreach ($approvers as $i => $approverId) {
            if (empty($approverId)) continue;
            $order++;
            Database::run('INSERT INTO approval_workflow_steps (workflow_id, step_order, approver_id, label) VALUES (?,?,?,?)',
                [$wfId, $order, (int) $approverId, trim((string) ($labels[$i] ?? '')) ?: null]);
        }
        flash('success', 'Workflow saved with ' . $order . ' step(s).');
        redirect('admin/approvals');
    }

    /* -------------------------------------------------------------- versions */
    public function versions(): void
    {
        $tid = $this->tenantId;
        $docs = Database::all('SELECT id, name, extension FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY name', [$tid]);
        $docId = isset($_GET['doc']) ? (int) $_GET['doc'] : (int) ($docs[0]['id'] ?? 0);
        $doc = $docId ? Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ?', [$docId, $tid]) : null;
        $versions = $doc ? Database::all('SELECT v.*, u.name AS uploader FROM document_versions v JOIN users u ON u.id = v.uploaded_by
                                          WHERE v.document_id = ? ORDER BY v.version_number DESC', [$docId]) : [];
        view('admin/versions', $this->base(compact('docs', 'doc', 'versions')));
    }

    /* --------------------------------------------------------------- storage */
    public function storage(): void
    {
        $tid = $this->tenantId;
        $used = Tenant::storageUsed($tid);
        $byType = Database::all('SELECT extension, COUNT(*) AS files, SUM(size_bytes) AS bytes FROM documents
                                 WHERE tenant_id = ? AND deleted_at IS NULL GROUP BY extension ORDER BY bytes DESC', [$tid]);
        $byUser = Database::all('SELECT u.name, COUNT(d.id) AS docs, COALESCE(SUM(d.size_bytes),0) AS bytes FROM users u
                                 LEFT JOIN documents d ON d.owner_id = u.id AND d.deleted_at IS NULL
                                 WHERE u.tenant_id = ? GROUP BY u.id ORDER BY bytes DESC', [$tid]);
        $largest = Database::all('SELECT * FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY size_bytes DESC LIMIT 10', [$tid]);
        $trash = Database::all('SELECT * FROM documents WHERE tenant_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC', [$tid]);
        view('admin/storage', $this->base(compact('used', 'byType', 'byUser', 'largest', 'trash')));
    }

    /* ------------------------------------------------------------ audit logs */
    public function auditLogs(): void
    {
        $tid = $this->tenantId;
        $entity = trim((string) ($_GET['entity'] ?? ''));
        $audit = Database::all('SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                                WHERE a.tenant_id = ?' . ($entity !== '' ? ' AND a.entity = ?' : '') . ' ORDER BY a.id DESC LIMIT 120',
            $entity !== '' ? [$tid, $entity] : [$tid]);
        $activity = Database::all('SELECT a.*, u.name AS user_name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                                   WHERE a.tenant_id = ? ORDER BY a.id DESC LIMIT 60', [$tid]);
        view('admin/audit-logs', $this->base(compact('audit', 'activity', 'entity')));
    }

    /* --------------------------------------------------------------- reports */
    public function reports(): void
    {
        $tid = $this->tenantId;
        $export = (string) ($_GET['export'] ?? '');
        if ($export !== '') { $this->exportCsv($export); }
        $report = [
            'uploads_by_month' => Database::all('SELECT DATE_FORMAT(created_at, "%b %Y") AS label, COUNT(*) AS total, SUM(size_bytes) AS bytes FROM documents WHERE tenant_id = ? GROUP BY label, DATE_FORMAT(created_at, "%Y-%m") ORDER BY DATE_FORMAT(created_at, "%Y-%m") DESC LIMIT 12', [$tid]),
            'top_downloads' => Database::all('SELECT name, download_count, size_bytes FROM documents WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY download_count DESC LIMIT 10', [$tid]),
            'by_user' => Database::all('SELECT u.name, COUNT(d.id) AS docs, COALESCE(SUM(d.size_bytes),0) AS bytes, COALESCE(SUM(d.download_count),0) AS downloads FROM users u LEFT JOIN documents d ON d.owner_id = u.id AND d.deleted_at IS NULL WHERE u.tenant_id = ? GROUP BY u.id ORDER BY docs DESC', [$tid]),
            'approvals' => Database::all('SELECT status, COUNT(*) AS total FROM approvals WHERE tenant_id = ? GROUP BY status', [$tid]),
            'sharing' => Database::all('SELECT COUNT(*) AS links, COALESCE(SUM(views),0) AS views FROM document_shares WHERE tenant_id = ?', [$tid])[0] ?? [],
            'share_actions' => Database::all('SELECT action, COUNT(*) AS total FROM share_access_logs WHERE tenant_id = ? GROUP BY action', [$tid]),
            'by_team' => Database::all('SELECT t.name, COUNT(DISTINCT d.id) AS docs FROM teams t LEFT JOIN team_members tm ON tm.team_id = t.id
                                        LEFT JOIN documents d ON d.owner_id = tm.user_id AND d.deleted_at IS NULL WHERE t.tenant_id = ? GROUP BY t.id ORDER BY docs DESC', [$tid]),
        ];
        view('admin/reports', $this->base(compact('report')));
    }

    private function exportCsv(string $export): void
    {
        $tid = $this->tenantId;
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="docuflow-' . $export . '-report.csv"');
        $fh = fopen('php://output', 'w');
        if ($export === 'documents') {
            fputcsv($fh, ['Name', 'Extension', 'Size (bytes)', 'Downloads', 'Owner', 'Category', 'Department', 'Status', 'Created']);
            $rows = Database::all('SELECT d.name, d.extension, d.size_bytes, d.download_count, u.name AS owner, c.name AS cat, dep.name AS dept, d.status, d.created_at
                                   FROM documents d JOIN users u ON u.id = d.owner_id LEFT JOIN categories c ON c.id = d.category_id
                                   LEFT JOIN departments dep ON dep.id = d.department_id WHERE d.tenant_id = ? AND d.deleted_at IS NULL', [$tid]);
            foreach ($rows as $r) fputcsv($fh, [$r['name'], $r['extension'], $r['size_bytes'], $r['download_count'], $r['owner'], $r['cat'], $r['dept'], $r['status'], $r['created_at']]);
        } elseif ($export === 'activity') {
            fputcsv($fh, ['When', 'User', 'Action', 'Description']);
            $rows = Database::all('SELECT a.created_at, u.name AS user_name, a.action, a.description FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id WHERE a.tenant_id = ? ORDER BY a.id DESC LIMIT 1000', [$tid]);
            foreach ($rows as $r) fputcsv($fh, [$r['created_at'], $r['user_name'], $r['action'], $r['description']]);
        } elseif ($export === 'approvals') {
            fputcsv($fh, ['Title', 'Document', 'Requested by', 'Status', 'Created']);
            $rows = Database::all('SELECT a.title, d.name AS doc, u.name AS requester, a.status, a.created_at FROM approvals a JOIN documents d ON d.id = a.document_id JOIN users u ON u.id = a.requested_by WHERE a.tenant_id = ? ORDER BY a.id DESC', [$tid]);
            foreach ($rows as $r) fputcsv($fh, [$r['title'], $r['doc'], $r['requester'], $r['status'], $r['created_at']]);
        } elseif ($export === 'sharing') {
            fputcsv($fh, ['Document', 'Created by', 'Downloads allowed', 'Password', 'Views', 'Active', 'Expires']);
            $rows = Database::all('SELECT d.name AS doc, u.name AS creator, s.can_download, s.password_hash, s.views, s.is_active, s.expires_at FROM document_shares s JOIN documents d ON d.id = s.document_id JOIN users u ON u.id = s.created_by WHERE s.tenant_id = ? ORDER BY s.id DESC', [$tid]);
            foreach ($rows as $r) fputcsv($fh, [$r['doc'], $r['creator'], $r['can_download'] ? 'yes' : 'no', $r['password_hash'] ? 'yes' : 'no', $r['views'], $r['is_active'] ? 'yes' : 'no', $r['expires_at']]);
        } else {
            fputcsv($fh, ['User', 'Documents', 'Storage bytes', 'Downloads']);
            $rows = Database::all('SELECT u.name, COUNT(d.id) AS docs, COALESCE(SUM(d.size_bytes),0) AS bytes, COALESCE(SUM(d.download_count),0) AS downloads FROM users u LEFT JOIN documents d ON d.owner_id = u.id AND d.deleted_at IS NULL WHERE u.tenant_id = ? GROUP BY u.id ORDER BY docs DESC', [$tid]);
            foreach ($rows as $r) fputcsv($fh, [$r['name'], $r['docs'], $r['bytes'], $r['downloads']]);
        }
        fclose($fh);
        exit;
    }

    /* -------------------------------------------------------------- settings */
    public function settings(): void
    {
        view('admin/settings', $this->base(['settings' => Tenant::settings($this->tenantId)]));
    }

    public function saveSettings(): void
    {
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
        redirect('admin/settings');
    }
}
