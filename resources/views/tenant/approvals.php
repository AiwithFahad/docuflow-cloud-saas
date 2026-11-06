<?php $pageTitle = 'Approvals'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-inbox me-1"></i>Waiting for your decision</h5></div>
      <?php foreach ($inbox as $a): ?>
      <div class="border rounded-3 p-3 mb-3">
        <div class="d-flex justify-content-between">
          <strong><?= e($a['title']) ?></strong>
          <span class="badge text-bg-warning">pending</span>
        </div>
        <p class="text-muted small mb-2">Document: <a href="<?= url('app/documents/' . (int) $a['document_id']) ?>"><?= e($a['doc_name']) ?></a> · requested by <?= e($a['requester']) ?> · <?= e(substr((string) $a['created_at'], 0, 10)) ?></p>
        <form method="post" action="<?= url('app/approvals/step/' . (int) $a['step_id'] . '/decide') ?>" class="d-flex gap-2">
          <?= csrf_field() ?>
          <input class="form-control" name="comment" placeholder="Decision comment (optional)">
          <button class="btn btn-success" name="decision" value="approved"><i class="bi bi-check-lg me-1"></i>Approve</button>
          <button class="btn btn-outline-danger" name="decision" value="rejected"><i class="bi bi-x-lg me-1"></i>Reject</button>
        </form>
      </div>
      <?php endforeach; ?>
      <?php if (!$inbox): ?><p class="text-muted mb-0">Your approval inbox is clear. 🎉</p><?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Your requests</h5></div>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>Title</th><th>Document</th><th>Status</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($mine as $a): ?>
        <tr><td><?= e($a['title']) ?></td><td><?= e($a['doc_name']) ?></td>
          <td><span class="badge text-bg-<?= $a['status'] === 'approved' ? 'success' : ($a['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e($a['status']) ?></span></td>
          <td><?= e(substr((string) $a['created_at'], 0, 10)) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$mine): ?><tr><td colspan="4" class="text-muted">No requests yet.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-head"><h5>Request approval</h5></div>
      <form method="post" action="<?= url('app/approvals/request') ?>">
        <?= csrf_field() ?>
        <label class="form-label">Title</label><input class="form-control mb-3" name="title" required placeholder="e.g. Please approve the Q3 supplier contract">
        <label class="form-label">Document</label>
        <select class="form-select mb-3" name="document_id" required>
          <?php foreach ($myDocs as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label">Approver</label>
        <select class="form-select mb-3" name="approver_id" required>
          <?php foreach ($tenantUsers as $tu): if ((int) $tu['id'] === (int) $user['id']) continue; ?>
          <option value="<?= (int) $tu['id'] ?>"><?= e($tu['name']) ?> (<?= e(str_replace('_', ' ', $tu['role'])) ?>)</option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-purple w-100">Send request</button>
      </form>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
