<?php $pageTitle = 'Tenants'; partial('super-open', compact('user', 'pageTitle')); ?>
<form method="get" class="d-flex gap-2 mb-3" style="max-width:420px">
  <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search companies…">
  <button class="btn btn-purple"><i class="bi bi-search"></i></button>
</form>
<div class="panel p-0">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Company</th><th>Plan</th><th>Subscription</th><th>Users</th><th>Storage</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($tenants as $t): ?>
    <tr>
      <td><strong><?= e($t['name']) ?></strong><small class="d-block text-muted"><?= e($t['email']) ?> · slug: <?= e($t['slug']) ?> · since <?= e(substr((string) $t['created_at'], 0, 10)) ?></small></td>
      <td><?= e($t['plan'] ?? '—') ?></td>
      <td><span class="badge text-bg-light"><?= e($t['sub_status'] ?? 'none') ?></span></td>
      <td><?= (int) $t['users'] ?></td>
      <td><?= human_bytes($t['storage']) ?></td>
      <td><span class="badge text-bg-<?= $t['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($t['status']) ?></span></td>
      <td>
        <form method="post" action="<?= url('super/tenants/' . (int) $t['id'] . '/action') ?>" class="d-inline">
          <?= csrf_field() ?>
          <?php if ($t['status'] === 'active'): ?>
          <button class="btn btn-sm btn-outline-danger" name="action" value="suspend" onclick="return confirm('Suspend this tenant? Their users will be blocked immediately.')">Suspend</button>
          <?php else: ?>
          <button class="btn btn-sm btn-outline-success" name="action" value="reactivate">Reactivate</button>
          <?php endif; ?>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$tenants): ?><tr><td colspan="7" class="text-center text-muted py-4">No tenants found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php partial('app-close'); ?>
