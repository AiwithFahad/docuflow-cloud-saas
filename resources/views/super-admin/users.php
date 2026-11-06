<?php $pageTitle = 'Users'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-people me-1"></i>All users</h5>
    <form class="d-flex gap-2" method="get"><input class="form-control form-control-sm" name="q" placeholder="Search name or email…" value="<?= e($q) ?>">
    <button class="btn btn-sm btn-purple">Search</button></form></div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>User</th><th>Organization</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead><tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><span class="avatar me-1"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></span><strong><?= e($u['name']) ?></strong>
        <div class="small text-muted"><?= e($u['email']) ?></div></td>
      <td><?= e($u['tenant_name'] ?? 'Platform') ?></td>
      <td><span class="badge text-bg-light"><?= e(ucwords(str_replace('_', ' ', $u['role']))) ?></span></td>
      <td><span class="badge text-bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td>
      <td><?= e(substr((string) $u['created_at'], 0, 10)) ?></td>
      <td><?php if ((int) $u['id'] !== (int) $user['id']): ?>
        <form method="post" action="<?= url('super-admin/users/' . (int) $u['id'] . '/action') ?>"><?= csrf_field() ?>
          <?php if ($u['status'] === 'active'): ?><button class="btn btn-sm btn-outline-danger" name="status" value="disabled">Disable</button>
          <?php else: ?><button class="btn btn-sm btn-outline-success" name="status" value="active">Enable</button><?php endif; ?>
        </form><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="6" class="text-muted">No users found.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
<?php partial('app-close'); ?>
