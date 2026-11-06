<?php $pageTitle = 'Users'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel p-0">
      <div class="panel-head px-4 pt-4"><h5>Company users</h5><span class="text-muted small"><?= count($users) ?> of <?= $sub && (int) $sub['max_users'] >= 0 ? (int) $sub['max_users'] : '∞' ?> on your plan</span></div>
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><span class="avatar me-2"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></span><strong><?= e($u['name']) ?></strong>
            <small class="d-block text-muted"><?= e($u['email']) ?></small></td>
          <td colspan="1">
            <form method="post" action="<?= url('app/users/' . (int) $u['id'] . '/update') ?>" class="d-flex gap-1">
              <?= csrf_field() ?>
              <select class="form-select form-select-sm" name="role" <?= (int) $u['id'] === (int) $user['id'] ? 'disabled' : '' ?>>
                <?php foreach (['company_admin', 'manager', 'member', 'viewer'] as $r): ?>
                <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $r))) ?></option>
                <?php endforeach; ?>
              </select>
              <select class="form-select form-select-sm" name="status" <?= (int) $u['id'] === (int) $user['id'] ? 'disabled' : '' ?>>
                <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="disabled" <?= $u['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
              </select>
              <?php if ((int) $u['id'] !== (int) $user['id']): ?><button class="btn btn-sm btn-outline-secondary">Save</button><?php endif; ?>
            </form>
          </td>
          <td><span class="badge text-bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td>
          <td class="small"><?= e($u['last_login_at'] ? substr((string) $u['last_login_at'], 0, 10) : '—') ?></td>
          <td></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Invite employee</h5></div>
      <form method="post" action="<?= url('app/users/invite') ?>">
        <?= csrf_field() ?>
        <label class="form-label">Email</label><input type="email" class="form-control mb-3" name="email" required>
        <label class="form-label">Role</label>
        <select class="form-select mb-3" name="role">
          <option value="member">Member</option><option value="manager">Manager</option>
          <option value="viewer">Viewer</option><option value="company_admin">Company Admin</option>
        </select>
        <button class="btn btn-purple w-100">Create invitation</button>
        <p class="small text-muted mt-2 mb-0">The invite link is shown after creation (and emailed in production). Links expire in 7 days.</p>
      </form>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Invitations</h5></div>
      <?php foreach ($invites as $i): ?>
      <div class="small border-bottom py-2 d-flex justify-content-between">
        <span><?= e($i['email']) ?><br><span class="text-muted"><?= e(str_replace('_', ' ', $i['role'])) ?></span></span>
        <span class="badge text-bg-<?= $i['status'] === 'pending' ? 'warning' : 'success' ?> align-self-start"><?= e($i['status']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$invites): ?><p class="text-muted small mb-0">No invitations yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
