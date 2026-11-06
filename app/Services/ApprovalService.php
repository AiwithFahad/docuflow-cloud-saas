<?php
// DocuFlow Cloud — Approval workflow service.
// Shared by the classic workspace, the role dashboard trees and the JSON API.
// Supports single-approver requests and multi-stage workflow templates with
// sequential steps and approved / rejected / changes_requested decisions.
declare(strict_types=1);

final class ApprovalService
{
    /**
     * Create an approval request for a document.
     * Either $workflowId (steps copied from the template) or $approverId
     * (single step) must be given. Returns the new approval id.
     */
    public static function request(int $tenantId, array $requester, array $doc, string $title, ?int $workflowId, ?int $approverId): int
    {
        $steps = [];
        if ($workflowId) {
            $wf = Database::one('SELECT * FROM approval_workflows WHERE id = ? AND tenant_id = ? AND is_active = 1', [$workflowId, $tenantId]);
            if (!$wf) throw new RuntimeException('Workflow not found.');
            $tpl = Database::all('SELECT * FROM approval_workflow_steps WHERE workflow_id = ? ORDER BY step_order, id', [$workflowId]);
            foreach ($tpl as $t) {
                $uid = $t['approver_id'] ? (int) $t['approver_id'] : self::userForRole($tenantId, (string) ($t['role_name'] ?? ''));
                if ($uid) $steps[] = ['order' => (int) $t['step_order'], 'approver' => $uid];
            }
            if (!$steps) throw new RuntimeException('That workflow has no resolvable approvers.');
        } elseif ($approverId) {
            $ok = Database::value('SELECT id FROM users WHERE id = ? AND tenant_id = ? AND status = "active"', [$approverId, $tenantId]);
            if (!$ok) throw new RuntimeException('Approver not found in this workspace.');
            $steps[] = ['order' => 1, 'approver' => $approverId];
        } else {
            throw new RuntimeException('Choose a workflow or an approver.');
        }

        $approvalId = Database::insert(
            'INSERT INTO approvals (tenant_id, document_id, workflow_id, requested_by, title, status, created_at, updated_at)
             VALUES (?,?,?,?,?,"pending",NOW(),NOW())',
            [$tenantId, (int) $doc['id'], $workflowId, (int) $requester['id'],
             $title !== '' ? $title : 'Approval: ' . $doc['name']]);
        foreach ($steps as $s) {
            Database::insert(
                'INSERT INTO approval_steps (approval_id, step_order, approver_id, status, created_at)
                 VALUES (?,?,?,"pending",NOW())',
                [$approvalId, $s['order'], $s['approver']]);
        }
        notify_user($steps[0]['approver'], $tenantId, 'Approval requested',
            $requester['name'] . ' asked you to review "' . $doc['name'] . '".', 'app/approvals');
        activity_log('approval.requested', 'Requested approval for ' . $doc['name'], $tenantId, (int) $requester['id']);
        return $approvalId;
    }

    private static function userForRole(int $tenantId, string $role): ?int
    {
        if ($role === '') return null;
        $id = Database::value('SELECT id FROM users WHERE tenant_id = ? AND role = ? AND status = "active" ORDER BY id LIMIT 1', [$tenantId, $role]);
        return $id ? (int) $id : null;
    }

    /** The currently actionable step (lowest pending order) of an approval. */
    public static function currentStep(int $approvalId): ?array
    {
        return Database::one('SELECT * FROM approval_steps WHERE approval_id = ? AND status = "pending" ORDER BY step_order, id LIMIT 1', [$approvalId]);
    }

    /**
     * Record a decision on the current step. $decision is one of
     * approved | rejected | changes_requested. Returns [ok, message].
     */
    public static function decide(int $tenantId, array $user, int $approvalId, string $decision, string $comment): array
    {
        if (!in_array($decision, ['approved', 'rejected', 'changes_requested'], true)) return [false, 'Invalid decision.'];
        $appr = Database::one('SELECT a.*, d.name AS doc_name FROM approvals a JOIN documents d ON d.id = a.document_id
                               WHERE a.id = ? AND a.tenant_id = ?', [$approvalId, $tenantId]);
        if (!$appr) return [false, 'Approval request not found.'];
        if ($appr['status'] !== 'pending') return [false, 'This request is already ' . $appr['status'] . '.'];

        $step = self::currentStep($approvalId);
        if (!$step) return [false, 'No pending step found.'];
        // Only the assigned approver may decide the current step — stages run
        // strictly in order and approvals cannot be recorded on someone
        // else's behalf (not even by a company admin).
        if ((int) $step['approver_id'] !== (int) $user['id']) {
            return [false, 'It is not your turn to decide this step.'];
        }

        Database::run('UPDATE approval_steps SET status = ?, comment = ?, decided_at = NOW() WHERE id = ?',
            [$decision, $comment !== '' ? $comment : null, (int) $step['id']]);
        audit_log('approvals', $approvalId, $decision, null, ['comment' => $comment]);

        if ($decision === 'approved') {
            $next = self::currentStep($approvalId);
            if ($next) {
                notify_user((int) $next['approver_id'], $tenantId, 'Approval requested',
                    'Your turn to review "' . $appr['doc_name'] . '" (' . $appr['title'] . ').', 'app/approvals');
                return [true, 'Step approved — moved to the next reviewer.'];
            }
            Database::run('UPDATE approvals SET status = "approved" WHERE id = ?', [$approvalId]);
            notify_user((int) $appr['requested_by'], $tenantId, 'Approval approved',
                '"' . $appr['doc_name'] . '" was approved.', 'app/approvals');
            activity_log('approval.approved', 'Approved ' . $appr['doc_name'], $tenantId, (int) $user['id']);
            return [true, 'Request approved.'];
        }

        // rejected / changes_requested finalise the request; remaining steps are skipped.
        Database::run('UPDATE approval_steps SET status = "skipped" WHERE approval_id = ? AND status = "pending"', [$approvalId]);
        Database::run('UPDATE approvals SET status = ? WHERE id = ?', [$decision, $approvalId]);
        $label = $decision === 'rejected' ? 'rejected' : 'sent back with change requests';
        notify_user((int) $appr['requested_by'], $tenantId, 'Approval ' . $label,
            '"' . $appr['doc_name'] . '" was ' . $label . ($comment !== '' ? ': ' . $comment : '') . '.', 'app/approvals');
        activity_log('approval.' . $decision, ucfirst($label) . ' ' . $appr['doc_name'], $tenantId, (int) $user['id']);
        return [true, 'Request ' . $label . '.'];
    }

    /** Approvals whose current pending step belongs to $userId. */
    public static function inbox(int $tenantId, int $userId): array
    {
        return Database::all(
            'SELECT a.*, d.name AS doc_name, d.extension, u.name AS requester_name, s.id AS step_id, s.step_order
             FROM approvals a
             JOIN documents d ON d.id = a.document_id
             JOIN users u ON u.id = a.requested_by
             JOIN approval_steps s ON s.approval_id = a.id AND s.status = "pending" AND s.approver_id = ?
             WHERE a.tenant_id = ? AND a.status = "pending"
               AND s.step_order = (SELECT MIN(s2.step_order) FROM approval_steps s2
                                   WHERE s2.approval_id = a.id AND s2.status = "pending")
             ORDER BY a.created_at',
            [$userId, $tenantId]
        );
    }
}
