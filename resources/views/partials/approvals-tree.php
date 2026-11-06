<?php
// Shared approvals page body for the manager/employee trees.
// Expects: $treeBase ('manager'|'employee'), $inbox, $mine, $rows (my docs),
//          $workflows, $tenantUsers, optional $history.
$statusBadge = fn($st) => $st === 'approved' ? 'success' : ($st === 'rejected' ? 'danger' : ($st === 'changes_requested' ? 'info' : ($st === 'pending' ? 'warning' : 'secondary')));
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-inbox me-1"></i>Waiting for my decision</h5><span class="badge text-bg-warning"><?= count($inbox) ?></span></div>
      <?php foreach ($inbox as $a): ?>
      <div class="border-bottom py-3">
        <strong><?= e($a['title']) ?></strong>
        <div class="small text-muted mb-2"><?= e($a['doc_name']) ?> · requested by <?= e($a['requester_name']) ?> · stage <?= (int) $a['step_order'] ?> · <?= e(substr((string) $a['created_at'], 0, 10)) ?></div>
        <form method="post" action="<?= url($treeBase . '/approvals/' . (int) $a['id'] . '/decide') ?>" class="d-flex flex-wrap gap-2">
          <?= csrf_field() ?><input class="form-control" name="comment" placeholder="Comment (optional)" style="max-width:260px">
          <button class="btn btn-sm btn-success" name="decision" value="approved">Approve</button>
          <button class="btn btn-sm btn-outline-danger" name="decision" value="rejected">Reject</button>
          <button class="btn btn-sm btn-outline-info" name="decision" value="changes_requested">Request changes</button>
        </form>
      </div>
      <?php endforeach; ?>
      <?php if (!$inbox): ?><p class="text-muted mb-0">Nothing waiting for you.</p><?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-send me-1"></i>My requests</h5></div>
      <?php foreach ($mine as $a): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <span><?= e($a['title']) ?><br><span class="small text-muted"><?= e($a['doc_name']) ?> · <?= e(substr((string) $a['created_at'], 0, 10)) ?></span></span>
        <span class="badge text-bg-<?= $statusBadge($a['status']) ?> align-self-start"><?= e(str_replace('_', ' ', $a['status'])) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$mine): ?><p class="text-muted mb-0">You have not requested any approvals yet.</p><?php endif; ?>
    </div>
    <?php if (!empty($history)): ?>
    <div class="panel mt-4">
      <div class="panel-head"><h5><i class="bi bi-clock-history me-1"></i>My decision history</h5></div>
      <?php foreach ($history as $h): ?>
      <div class="d-flex justify-content-between border-bottom py-2 small">
        <span><?= e($h['title']) ?> · <?= e($h['doc_name']) ?><?= $h['comment'] ? ' · “' . e($h['comment']) . '”' : '' ?></span>
        <span class="badge text-bg-<?= $statusBadge($h['status']) ?>"><?= e(str_replace('_', ' ', $h['status'])) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-head"><h5>Request approval</h5></div>
      <form method="post" action="<?= url($treeBase . '/approvals/request') ?>">
        <?= csrf_field() ?>
        <label class="form-label">Document</label>
        <select class="form-select mb-3" name="document_id" required>
          <?php foreach ($rows as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label">Title</label><input class="form-control mb-3" name="title" placeholder="Please review…">
        <label class="form-label">Workflow (multi-stage)</label>
        <select class="form-select mb-3" name="workflow_id"><option value="">— none, pick a single approver —</option>
          <?php foreach ($workflows as $w): ?><option value="<?= (int) $w['id'] ?>"><?= e($w['name']) ?></option><?php endforeach; ?></select>
        <label class="form-label">…or single approver</label>
        <select class="form-select mb-3" name="approver_id"><option value="">—</option>
          <?php foreach ($tenantUsers as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</option><?php endforeach; ?></select>
        <button class="btn btn-purple w-100">Send request</button>
      </form>
    </div>
  </div>
</div>
