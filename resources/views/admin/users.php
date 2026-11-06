<?php $pageTitle = 'Users'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel"><div class="panel-head"><h5><i class="bi bi-person-gear me-1"></i>Workspace users</h5></div>
      <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead><tbody>
        <?php foreach ($users as $u): ?>
        <tr><td><span class="avatar me-1"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></span><strong><?= e($u['name']) ?></strong>
            <div class="small text-muted"><?= e($u['email']) ?></div></td>
          <td><?= e(ucwords(str_replace('_', ' ', $u['role']))) ?></td>
          <td><span class="badge text-bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td>
          <td class="small"><?= e(substr((string) $u['created_at'], 0, 10)) ?></td>
          <td><?php if ((int) $u['id'] !== (int) $user['id']): ?>
            <button class="btn btn-sm btn-outline-navy" data-bs-toggle="collapse" data-bs-target="#userEdit<?= (int) $u['id'] ?>">Edit</button><?php endif; ?></td></tr>
        <?php if ((int) $u['id'] !== (int) $user['id']): ?>
        <tr class="collapse" id="userEdit<?= (int) $u['id'] ?>"><td colspan="5">
          <form method="post" action="<?= url('admin/users/' . (int) $u['id']) ?>" class="d-flex gap-2 align-items-end">
            <?= csrf_field() ?>
            <span><label class="form-label small">Role</label><select class="form-select form-select-sm" name="role">
              <?php foreach (['company_admin', 'manager', 'member', 'viewer'] as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $r))) ?></option><?php endforeach; ?></select></span>
            <span><label class="form-label small">Status</label><select class="form-select form-select-sm" name="status">
              <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="disabled" <?= $u['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option></select></span>
            <button class="btn btn-sm btn-purple">Save</button></form></td></tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel mb-4"><div class="panel-head"><h5>Invite user</h5></div>
      <form method="post" action="<?= url('admin/users/invite') ?>">
        <?= csrf_field() ?>
        <label class="form-label">Email</label><input class="form-control mb-3" type="email" name="email" required>
        <label class="form-label">Role</label>
        <select class="form-select mb-3" name="role">
          <option value="member">Member</option><option value="manager">Manager</option>
          <option value="viewer">Viewer</option><option value="company_admin">Company Admin</option></select>
        <button class="btn btn-purple w-100">Send invitation</button>
        <p class="small text-muted mt-2 mb-0">Plan seat limits are enforced — if the workspace is full you will be asked to upgrade.</p>
      </form>
    </div>
    <div class="panel"><div class="panel-head"><h5>Pending invitations</h5></div>
      <?php foreach ($invites as $i): if ($i['status'] !== 'pending') continue; ?>
      <div class="small border-bottom py-2"><?= e($i['email']) ?> · <?= e(ucwords(str_replace('_', ' ', $i['role']))) ?>
        <div class="text-muted">expires <?= e(substr((string) $i['expires_at'], 0, 10)) ?></div></div>
      <?php endforeach; ?>
      <?php if (!array_filter($invites, fn($i) => $i['status'] === 'pending')): ?><p class="text-muted small mb-0">No pending invitations.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
