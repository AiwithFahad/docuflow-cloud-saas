<?php
// DocuFlow Cloud — Employee dashboard TREE (employee/*).
// Personal workspace for members and viewers: my documents, recent files,
// shared with me, my approval requests and assigned steps, my folders,
// notifications and my storage usage. Permission-filtered throughout.
declare(strict_types=1);

final class EmployeeTreeController
{
    private array $user;
    private array $tenant;
    private int $tenantId;

    public function __construct()
    {
        $this->user = AuthMiddleware::tenant();
        $role = (string) ($this->user['role'] ?? '');
        if ($role === 'company_admin') redirect('admin/dashboard');
        if ($role === 'manager') redirect('manager/dashboard');
        $this->tenant = auth_tenant();
        $this->tenantId = (int) $this->tenant['id'];
    }

    private function base(array $extra = []): array
    {
        $unread = (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [(int) $this->user['id']]);
        $notifs = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [(int) $this->user['id']]);
        return array_merge([
            'user' => $this->user, 'tenant' => $this->tenant, 'tree' => 'employee',
            'unread' => $unread, 'notifs' => $notifs,
            'sub' => Tenant::currentSubscription($this->tenantId),
        ], $extra);
    }

    public function dashboard(): void
    {
        $tid = $this->tenantId;
        $uid = (int) $this->user['id'];
        $myDocs = Database::all('SELECT d.*, c.name AS category_name FROM documents d LEFT JOIN categories c ON c.id = d.category_id
                                 WHERE d.tenant_id = ? AND d.owner_id = ? AND d.deleted_at IS NULL ORDER BY d.updated_at DESC LIMIT 8', [$tid, $uid]);
        [$recent] = Document::listing($tid, $this->user, ['status' => 'recent', 'per' => 8]);
        $shared = Database::all('SELECT DISTINCT d.*, u.name AS owner_name, p.can_download, p.can_edit FROM documents d
                                 JOIN users u ON u.id = d.owner_id
                                 JOIN document_permissions p ON p.document_id = d.id AND p.can_view = 1 AND d.owner_id != ?
                                   AND ((p.subject_type = "user" AND p.subject_id = ?)
                                        OR (p.subject_type = "team" AND p.subject_id IN (SELECT team_id FROM team_members WHERE user_id = ?)))
                                 WHERE d.tenant_id = ? AND d.deleted_at IS NULL ORDER BY d.updated_at DESC LIMIT 8', [$uid, $uid, $uid, $tid]);
        $myFolders = Database::all('SELECT f.*, (SELECT COUNT(*) FROM documents d WHERE d.folder_id = f.id AND d.deleted_at IS NULL) AS docs
                                    FROM folders f WHERE f.tenant_id = ? AND f.created_by = ? ORDER BY f.name', [$tid, $uid]);
        $myBytes = (int) Database::value('SELECT COALESCE(SUM(size_bytes),0) FROM documents WHERE tenant_id = ? AND owner_id = ? AND deleted_at IS NULL', [$tid, $uid]);
        $tenantBytes = Tenant::storageUsed($tid);
        $inbox = ApprovalService::inbox($tid, $uid);
        $myPending = (int) Database::value('SELECT COUNT(*) FROM approvals WHERE tenant_id = ? AND requested_by = ? AND status = "pending"', [$tid, $uid]);
        $favCount = (int) Database::value('SELECT COUNT(*) FROM document_favorites WHERE user_id = ?', [$uid]);
        $notifList = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 6', [$uid]);
        view('employee/dashboard', $this->base(compact('myDocs', 'recent', 'shared', 'myFolders', 'myBytes', 'tenantBytes', 'inbox', 'myPending', 'favCount', 'notifList')));
    }

    public function files(): void
    {
        $tid = $this->tenantId;
        view('employee/files', $this->base([
            'folderTree' => Folder::tree($tid),
            'categories' => Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY name', [$tid]),
            'tags' => Database::all('SELECT * FROM tags WHERE tenant_id = ? ORDER BY name', [$tid]),
            'departments' => Database::all('SELECT * FROM departments WHERE tenant_id = ? ORDER BY name', [$tid]),
            'tenantUsers' => Database::all('SELECT id, name FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]),
            'initialFolder' => isset($_GET['folder']) ? (int) $_GET['folder'] : null,
            'initialQ' => trim((string) ($_GET['q'] ?? '')),
        ]));
    }

    public function approvals(): void
    {
        $tid = $this->tenantId;
        $uid = (int) $this->user['id'];
        $inbox = ApprovalService::inbox($tid, $uid);
        $mine = Database::all('SELECT a.*, d.name AS doc_name FROM approvals a JOIN documents d ON d.id = a.document_id
                               WHERE a.tenant_id = ? AND a.requested_by = ? ORDER BY a.id DESC LIMIT 30', [$tid, $uid]);
        [$rows] = Document::listing($tid, $this->user, ['status' => 'any', 'per' => 100]);
        $workflows = Database::all('SELECT id, name FROM approval_workflows WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tid]);
        $tenantUsers = Database::all('SELECT id, name, role FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]);
        view('employee/approvals', $this->base(compact('inbox', 'mine', 'rows', 'workflows', 'tenantUsers')));
    }

    public function requestApproval(): void
    {
        require_csrf();
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL',
            [(int) ($_POST['document_id'] ?? 0), $this->tenantId]);
        if (!$doc || !Document::userCanAccess($doc, $this->user)) { flash('error', 'Document not found.'); redirect('employee/approvals'); }
        try {
            ApprovalService::request($this->tenantId, $this->user, $doc, trim((string) ($_POST['title'] ?? '')),
                !empty($_POST['workflow_id']) ? (int) $_POST['workflow_id'] : null,
                !empty($_POST['approver_id']) ? (int) $_POST['approver_id'] : null);
            flash('success', 'Approval requested.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect('employee/approvals');
    }

    public function decideApproval(int $approvalId): void
    {
        require_csrf();
        [$ok, $msg] = ApprovalService::decide($this->tenantId, $this->user, $approvalId,
            (string) ($_POST['decision'] ?? ''), trim((string) ($_POST['comment'] ?? '')));
        flash($ok ? 'success' : 'error', $msg);
        redirect('employee/approvals');
    }

    public function notifications(): void
    {
        $rows = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50', [(int) $this->user['id']]);
        view('employee/notifications', $this->base(['notifications' => $rows]));
    }

    public function markNotificationsRead(): void
    {
        require_csrf();
        Database::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [(int) $this->user['id']]);
        redirect('employee/notifications');
    }
}
