<?php
// DocuFlow Cloud — Manager dashboard TREE (manager/*).
// Team-scoped view: team documents, pending approvals, documents shared with
// the manager's teams, team activity and recent team uploads. All document
// access still goes through Document::userCanAccess / accessibleWhere.
declare(strict_types=1);

final class ManagerTreeController
{
    private array $user;
    private array $tenant;
    private int $tenantId;

    public function __construct()
    {
        $this->user = AuthMiddleware::tenant();
        $role = (string) ($this->user['role'] ?? '');
        if ($role === 'company_admin') redirect('admin/dashboard');
        if ($role !== 'manager') redirect('employee/dashboard');
        $this->tenant = auth_tenant();
        $this->tenantId = (int) $this->tenant['id'];
    }

    private function base(array $extra = []): array
    {
        $unread = (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [(int) $this->user['id']]);
        $notifs = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [(int) $this->user['id']]);
        return array_merge([
            'user' => $this->user, 'tenant' => $this->tenant, 'tree' => 'manager',
            'unread' => $unread, 'notifs' => $notifs,
            'sub' => Tenant::currentSubscription($this->tenantId),
        ], $extra);
    }

    /** Ids of users who share a team with the manager (including the manager). */
    private function teamMateIds(): array
    {
        $ids = Database::all('SELECT DISTINCT user_id FROM team_members
                              WHERE team_id IN (SELECT team_id FROM team_members WHERE user_id = ?)',
            [(int) $this->user['id']]);
        $out = array_map(fn($r) => (int) $r['user_id'], $ids);
        return $out ?: [(int) $this->user['id']];
    }

    public function dashboard(): void
    {
        $tid = $this->tenantId;
        $uid = (int) $this->user['id'];
        $mates = $this->teamMateIds();
        $in = implode(',', array_map('intval', $mates));
        $myTeams = Database::all('SELECT t.*, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id) AS members
                                  FROM teams t JOIN team_members me ON me.team_id = t.id AND me.user_id = ?
                                  WHERE t.tenant_id = ? ORDER BY t.name', [$uid, $tid]);
        $teamDocs = Database::all("SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id
                                   WHERE d.tenant_id = ? AND d.deleted_at IS NULL AND d.owner_id IN ($in)
                                   ORDER BY d.updated_at DESC LIMIT 10", [$tid]);
        $shared = Database::all('SELECT DISTINCT d.*, u.name AS owner_name FROM documents d
                                 JOIN users u ON u.id = d.owner_id
                                 JOIN document_permissions p ON p.document_id = d.id AND p.can_view = 1
                                   AND p.subject_type = "team"
                                   AND p.subject_id IN (SELECT team_id FROM team_members WHERE user_id = ?)
                                 WHERE d.tenant_id = ? AND d.deleted_at IS NULL ORDER BY d.updated_at DESC LIMIT 8', [$uid, $tid]);
        $activity = Database::all("SELECT a.*, u.name AS user_name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                                   WHERE a.tenant_id = ? AND a.user_id IN ($in) ORDER BY a.id DESC LIMIT 10", [$tid]);
        $uploads = Database::all("SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id
                                  WHERE d.tenant_id = ? AND d.deleted_at IS NULL AND d.owner_id IN ($in)
                                  ORDER BY d.created_at DESC LIMIT 6", [$tid]);
        $inbox = ApprovalService::inbox($tid, $uid);
        view('manager/dashboard', $this->base(compact('myTeams', 'teamDocs', 'shared', 'activity', 'uploads', 'inbox')));
    }

    public function files(): void
    {
        $tid = $this->tenantId;
        view('manager/files', $this->base([
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
        $history = Database::all('SELECT s.*, a.title, d.name AS doc_name FROM approval_steps s
                                  JOIN approvals a ON a.id = s.approval_id JOIN documents d ON d.id = a.document_id
                                  WHERE a.tenant_id = ? AND s.approver_id = ? AND s.status != "pending" ORDER BY s.decided_at DESC LIMIT 20', [$tid, $uid]);
        [$rows] = Document::listing($tid, $this->user, ['status' => 'any', 'per' => 100]);
        $workflows = Database::all('SELECT id, name FROM approval_workflows WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tid]);
        $tenantUsers = Database::all('SELECT id, name, role FROM users WHERE tenant_id = ? AND status = "active" ORDER BY name', [$tid]);
        view('manager/approvals', $this->base(compact('inbox', 'mine', 'history', 'rows', 'workflows', 'tenantUsers')));
    }

    public function requestApproval(): void
    {
        require_csrf();
        $doc = Database::one('SELECT * FROM documents WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL',
            [(int) ($_POST['document_id'] ?? 0), $this->tenantId]);
        if (!$doc || !Document::userCanAccess($doc, $this->user)) { flash('error', 'Document not found.'); redirect('manager/approvals'); }
        try {
            ApprovalService::request($this->tenantId, $this->user, $doc, trim((string) ($_POST['title'] ?? '')),
                !empty($_POST['workflow_id']) ? (int) $_POST['workflow_id'] : null,
                !empty($_POST['approver_id']) ? (int) $_POST['approver_id'] : null);
            flash('success', 'Approval requested.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect('manager/approvals');
    }

    public function decideApproval(int $approvalId): void
    {
        require_csrf();
        [$ok, $msg] = ApprovalService::decide($this->tenantId, $this->user, $approvalId,
            (string) ($_POST['decision'] ?? ''), trim((string) ($_POST['comment'] ?? '')));
        flash($ok ? 'success' : 'error', $msg);
        redirect('manager/approvals');
    }

    public function notifications(): void
    {
        $rows = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50', [(int) $this->user['id']]);
        view('manager/notifications', $this->base(['notifications' => $rows]));
    }

    public function markNotificationsRead(): void
    {
        require_csrf();
        Database::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [(int) $this->user['id']]);
        redirect('manager/notifications');
    }
}
