<?php $pageTitle = 'Audit & Activity Logs'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-shield-lock me-1"></i>Audit logs</h5>
        <form class="d-flex gap-2" method="get"><select class="form-select form-select-sm" name="entity">
          <option value="">All entities</option>
          <?php foreach (['document', 'user', 'tenant', 'approvals', 'subscription'] as $en): ?><option value="<?= $en ?>" <?= $entity === $en ? 'selected' : '' ?>><?= e(ucfirst($en)) ?></option><?php endforeach; ?>
        </select><button class="btn btn-sm btn-purple">Filter</button></form></div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>When</th><th>User</th><th>Entity</th><th>Action</th><th>IP</th></tr></thead><tbody>
        <?php foreach ($audit as $a): ?>
        <tr><td class="small"><?= e(substr((string) $a['created_at'], 0, 16)) ?></td><td><?= e($a['user_name'] ?? 'System') ?></td>
          <td><?= e($a['entity']) ?> #<?= (int) $a['entity_id'] ?></td><td><span class="badge text-bg-light"><?= e($a['action']) ?></span></td>
          <td class="small text-muted"><?= e($a['ip_address'] ?? '') ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$audit): ?><tr><td colspan="5" class="text-muted">No audit entries match.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-activity me-1"></i>Recent activity</h5></div>
      <?php foreach ($activity as $a): ?>
      <div class="small border-bottom py-2"><strong><?= e($a['user_name'] ?? 'System') ?></strong> · <?= e($a['description']) ?>
        <div class="text-muted"><?= e($a['action']) ?> · <?= e(substr((string) $a['created_at'], 0, 16)) ?></div></div>
      <?php endforeach; ?>
      <?php if (!$activity): ?><p class="text-muted small mb-0">No activity yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
