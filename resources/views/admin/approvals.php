<?php $pageTitle = 'Approvals'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree'));
$stepsByApproval = [];
foreach ($steps as $s) $stepsByApproval[(int) $s['approval_id']][] = $s;
$statusBadge = fn($st) => $st === 'approved' ? 'success' : ($st === 'rejected' ? 'danger' : ($st === 'changes_requested' ? 'info' : ($st === 'pending' ? 'warning' : 'secondary')));
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-inbox me-1"></i>Waiting for my decision</h5><span class="badge text-bg-warning"><?= count($inbox) ?></span></div>
      <?php foreach ($inbox as $a): ?>
      <div class="border-bottom py-3">
        <div class="d-flex justify-content-between"><strong><?= e($a['title']) ?></strong><span class="text-muted small">step <?= (int) $a['step_order'] ?></span></div>
        <div class="small text-muted mb-2"><?= e($a['doc_name']) ?> · requested by <?= e($a['requester_name']) ?> · <?= e(substr((string) $a['created_at'], 0, 10)) ?></div>
        <form method="post" action="<?= url('admin/approvals/' . (int) $a['id'] . '/decide') ?>" class="d-flex flex-wrap gap-2">
          <?= csrf_field() ?><input class="form-control" name="comment" placeholder="Comment (optional)" style="max-width:280px">
          <button class="btn btn-sm btn-success" name="decision" value="approved">Approve</button>
          <button class="btn btn-sm btn-outline-danger" name="decision" value="rejected">Reject</button>
          <button class="btn btn-sm btn-outline-info" name="decision" value="changes_requested">Request changes</button>
        </form>
      </div>
      <?php endforeach; ?>
      <?php if (!$inbox): ?><p class="text-muted mb-0">Nothing waiting for you.</p><?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-list-check me-1"></i>All requests</h5></div>
      <?php foreach ($all as $a): ?>
      <div class="border-bottom py-3">
        <div class="d-flex justify-content-between align-items-start">
          <span><strong><?= e($a['title']) ?></strong><br>
          <span class="small text-muted"><?= e($a['doc_name']) ?> · by <?= e($a['requester_name']) ?><?= $a['workflow_name'] ? ' · workflow: ' . e($a['workflow_name']) : '' ?> · <?= e(substr((string) $a['created_at'], 0, 10)) ?></span></span>
          <span class="badge text-bg-<?= $statusBadge($a['status']) ?>"><?= e(str_replace('_', ' ', $a['status'])) ?></span>
        </div>
        <?php if (!empty($stepsByApproval[(int) $a['id']])): ?>
        <div class="d-flex flex-wrap gap-1 mt-2">
          <?php foreach ($stepsByApproval[(int) $a['id']] as $s): ?>
          <span class="badge text-bg-<?= $statusBadge($s['status']) ?>"><?= (int) $s['step_order'] ?>. <?= e($s['approver_name']) ?> — <?= e(str_replace('_', ' ', $s['status'])) ?><?= $s['comment'] ? ' · “' . e($s['comment']) . '”' : '' ?></span>
          <?php endforeach; ?>
        </div><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (!$all): ?><p class="text-muted mb-0">No approval requests yet.</p><?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Request approval</h5></div>
      <form method="post" action="<?= url('admin/approvals/request') ?>">
        <?= csrf_field() ?>
        <label class="form-label">Document</label>
        <select class="form-select mb-3" name="document_id" required>
          <?php foreach ($docs as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Title</label><input class="form-control mb-3" name="title" placeholder="Please review…">
        <label class="form-label">Workflow (multi-stage)</label>
        <select class="form-select mb-3" name="workflow_id"><option value="">— none, pick a single approver —</option>
          <?php foreach ($workflows as $w): if (!$w['is_active']) continue; ?><option value="<?= (int) $w['id'] ?>"><?= e($w['name']) ?> (<?= (int) $w['step_count'] ?> steps)</option><?php endforeach; ?></select>
        <label class="form-label">…or single approver</label>
        <select class="form-select mb-3" name="approver_id"><option value="">—</option>
          <?php foreach ($tenantUsers as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</option><?php endforeach; ?></select>
        <button class="btn btn-purple w-100">Send request</button>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-diagram-2 me-1"></i>Workflow templates</h5></div>
      <?php foreach ($workflows as $w): ?>
      <div class="border-bottom py-2">
        <div class="d-flex justify-content-between"><strong><?= e($w['name']) ?></strong>
          <span class="badge text-bg-<?= $w['is_active'] ? 'success' : 'secondary' ?>"><?= $w['is_active'] ? 'active' : 'off' ?></span></div>
        <div class="small text-muted"><?= (int) $w['step_count'] ?> step(s)<?= $w['department_name'] ? ' · dept: ' . e($w['department_name']) : '' ?><?= $w['category_name'] ? ' · category: ' . e($w['category_name']) : '' ?></div>
        <?php if ($w['description']): ?><div class="small"><?= e($w['description']) ?></div><?php endif; ?>
        <form method="post" action="<?= url('admin/workflows') ?>" class="mt-1" onsubmit="return confirm('Delete this workflow template? Existing requests are unaffected.')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
          <button class="btn btn-sm btn-link text-danger p-0">Delete template</button></form>
      </div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('admin/workflows') ?>" class="mt-3">
        <?= csrf_field() ?>
        <label class="form-label">New workflow name</label><input class="form-control mb-2" name="name" required placeholder="e.g. Contract review">
        <input class="form-control mb-2" name="description" placeholder="Description (optional)">
        <div class="row g-2 mb-2">
          <div class="col-6"><select class="form-select" name="department_id"><option value="">Any department</option>
            <?php foreach ($departments as $dep): ?><option value="<?= (int) $dep['id'] ?>"><?= e($dep['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-6"><select class="form-select" name="category_id"><option value="">Any category</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <label class="form-label small">Steps (in order)</label>
        <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="d-flex gap-2 mb-2">
          <select class="form-select" name="step_approver[]"><option value="">— step <?= $i + 1 ?> approver —</option>
            <?php foreach ($tenantUsers as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select>
          <input class="form-control" name="step_label[]" placeholder="Label (optional)">
        </div>
        <?php endfor; ?>
        <label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_active" value="1" checked> Active</label>
        <button class="btn btn-outline-navy w-100">Create workflow</button>
      </form>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
