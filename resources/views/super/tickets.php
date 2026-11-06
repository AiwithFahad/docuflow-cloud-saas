<?php $pageTitle = 'Support Tickets'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="row g-3">
<?php foreach ($tickets as $t): ?>
  <div class="col-lg-6">
    <div class="panel h-100">
      <div class="d-flex justify-content-between">
        <strong><?= e($t['subject']) ?></strong>
        <span class="badge text-bg-<?= $t['status'] === 'resolved' || $t['status'] === 'closed' ? 'success' : ($t['status'] === 'in_progress' ? 'info' : 'warning') ?>"><?= e($t['status']) ?></span>
      </div>
      <p class="small text-muted mb-1"><?= e($t['name']) ?> · <?= e($t['email']) ?><?= $t['tenant_name'] ? ' · ' . e($t['tenant_name']) : ' · website' ?> · <?= e(substr((string) $t['created_at'], 0, 10)) ?> · priority <?= e($t['priority']) ?></p>
      <p class="mb-2"><?= e($t['message']) ?></p>
      <?php if ($t['admin_reply']): ?><div class="alert alert-light border small mb-2"><strong>Reply:</strong> <?= e($t['admin_reply']) ?></div><?php endif; ?>
      <form method="post" action="<?= url('super/tickets/' . (int) $t['id'] . '/action') ?>" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input class="form-control" name="reply" placeholder="Reply to customer…">
        <select class="form-select" name="status" style="max-width:150px">
          <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $s): ?><option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-purple">Save</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$tickets): ?><div class="col-12"><div class="panel text-center text-muted">No tickets yet.</div></div><?php endif; ?>
</div>
<?php partial('app-close'); ?>
