<?php $pageTitle = 'Sharing'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="panel mb-4">
  <div class="panel-head"><h5><i class="bi bi-link-45deg me-1"></i>Public share links</h5></div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Document</th><th>Created by</th><th>Link</th><th>Views</th><th>Downloads</th><th>Password</th><th>Expires</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($shares as $s): ?>
    <tr><td><a href="<?= url('app/documents/' . (int) $s['document_id']) ?>"><?= e($s['doc_name']) ?></a></td>
      <td><?= e($s['creator']) ?></td>
      <td><code class="small text-break"><?= url('s/' . $s['token']) ?></code></td>
      <td><?= (int) $s['views'] ?></td><td><?= $s['can_download'] ? 'allowed' : 'view only' ?></td>
      <td><?= !empty($s['password_hash']) ? '<i class="bi bi-lock-fill text-purple"></i> set' : '—' ?></td>
      <td class="small"><?= $s['expires_at'] ? e(substr((string) $s['expires_at'], 0, 10)) : 'never' ?></td>
      <td><span class="badge text-bg-<?= $s['is_active'] ? 'success' : 'secondary' ?>"><?= $s['is_active'] ? 'active' : 'revoked' ?></span></td>
      <td><div class="d-flex gap-1">
        <?php if ($s['is_active']): ?>
        <form method="post" action="<?= url('admin/sharing/' . (int) $s['id']) ?>"><?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger" name="action" value="revoke">Revoke</button></form>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-navy" data-bs-toggle="collapse" data-bs-target="#sharePw<?= (int) $s['id'] ?>">Password</button>
      </div></td></tr>
    <tr class="collapse" id="sharePw<?= (int) $s['id'] ?>"><td colspan="9">
      <form method="post" action="<?= url('admin/sharing/' . (int) $s['id']) ?>" class="d-flex gap-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <input class="form-control" name="password" placeholder="New password (blank removes protection)" style="max-width:340px" autocomplete="new-password">
        <button class="btn btn-sm btn-purple">Save password</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$shares): ?><tr><td colspan="9" class="text-muted">No share links yet. Create one from any document page or the file manager.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel h-100"><div class="panel-head"><h5><i class="bi bi-people me-1"></i>Internal permissions</h5></div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Document</th><th>Shared with</th><th>Rights</th><th></th></tr></thead><tbody>
        <?php foreach ($perms as $p): ?>
        <tr><td><?= e($p['doc_name']) ?></td><td><?= e(ucfirst($p['subject_type'])) ?> #<?= (int) $p['subject_id'] ?></td>
          <td class="small">view <?= $p['can_view'] ? '✓' : '—' ?> · download <?= $p['can_download'] ? '✓' : '—' ?> · edit <?= $p['can_edit'] ? '✓' : '—' ?></td>
          <td><form method="post" action="<?= url('admin/sharing/permission/' . (int) $p['id'] . '/remove') ?>"><?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr>
        <?php endforeach; ?>
        <?php if (!$perms): ?><tr><td colspan="4" class="text-muted">No internal permissions granted yet.</td></tr><?php endif; ?>
        </tbody></table></div>
      <p class="small text-muted mt-2 mb-0">Grant permissions from any document page (Permissions panel) or via the file manager.</p>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel h-100"><div class="panel-head"><h5><i class="bi bi-activity me-1"></i>Sharing activity log</h5></div>
      <?php foreach ($logs as $l): ?>
      <div class="small d-flex justify-content-between border-bottom py-2">
        <span><i class="bi <?= $l['action'] === 'download' ? 'bi-download' : ($l['action'] === 'unlock_failed' ? 'bi-lock text-danger' : 'bi-eye') ?> me-1"></i>
          <?= e($l['action']) ?> · <?= e($l['doc_name']) ?></span>
        <span class="text-muted"><?= e(substr((string) $l['created_at'], 0, 16)) ?><?= $l['ip_address'] ? ' · ' . e($l['ip_address']) : '' ?></span></div>
      <?php endforeach; ?>
      <?php if (!$logs): ?><p class="text-muted small mb-0">No public link activity recorded yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
