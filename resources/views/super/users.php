<?php $pageTitle = 'Users'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="panel p-0">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>User</th><th>Company</th><th>Role</th><th>Status</th><th>Last login</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><span class="avatar me-2"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></span><strong><?= e($u['name']) ?></strong>
        <small class="d-block text-muted"><?= e($u['email']) ?></small></td>
      <td><?= e($u['tenant_name'] ?? '— Platform —') ?></td>
      <td><span class="badge text-bg-light"><?= e(str_replace('_', ' ', $u['role'])) ?></span></td>
      <td><span class="badge text-bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td>
      <td class="small"><?= e($u['last_login_at'] ? substr((string) $u['last_login_at'], 0, 10) : '—') ?></td>
      <td>
        <?php if ((int) $u['id'] !== (int) $user['id']): ?>
        <form method="post" action="<?= url('super/users/' . (int) $u['id'] . '/action') ?>">
          <?= csrf_field() ?>
          <?php if ($u['status'] === 'active'): ?><button class="btn btn-sm btn-outline-danger" name="status" value="disabled">Disable</button>
          <?php else: ?><button class="btn btn-sm btn-outline-success" name="status" value="active">Enable</button><?php endif; ?>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php partial('app-close'); ?>
