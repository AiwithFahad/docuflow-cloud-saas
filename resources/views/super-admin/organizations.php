<?php $pageTitle = 'Organizations'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-buildings me-1"></i>Organizations (tenants)</h5>
    <form class="d-flex gap-2" method="get"><input class="form-control form-control-sm" name="q" placeholder="Search name, email, slug…" value="<?= e($q) ?>">
    <button class="btn btn-sm btn-purple">Search</button></form></div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Organization</th><th>Plan</th><th>Subscription</th><th>Users</th><th>Documents</th><th>Storage</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($tenants as $t): ?>
    <tr>
      <td><strong><?= e($t['name']) ?></strong><div class="small text-muted"><?= e($t['email']) ?> · /<?= e($t['slug']) ?> · since <?= e(substr((string) $t['created_at'], 0, 10)) ?></div></td>
      <td><?= e($t['plan'] ?? '—') ?></td>
      <td><span class="badge text-bg-secondary"><?= e($t['sub_status'] ?? 'none') ?></span></td>
      <td><?= (int) $t['users'] ?></td><td><?= (int) $t['docs'] ?></td><td><?= human_bytes((int) $t['storage']) ?></td>
      <td><span class="badge text-bg-<?= $t['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($t['status']) ?></span></td>
      <td><form method="post" action="<?= url('super-admin/organizations/' . (int) $t['id'] . '/action') ?>"><?= csrf_field() ?>
        <?php if ($t['status'] === 'active'): ?><button class="btn btn-sm btn-outline-danger" name="action" value="suspend">Suspend</button>
        <?php else: ?><button class="btn btn-sm btn-outline-success" name="action" value="reactivate">Reactivate</button><?php endif; ?>
      </form></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$tenants): ?><tr><td colspan="8" class="text-muted">No organizations found.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
<?php partial('app-close'); ?>
