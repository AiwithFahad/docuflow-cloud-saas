<?php $pageTitle = $doc['name']; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<a href="<?= url('app/files' . ($doc['folder_id'] ? '?folder=' . (int) $doc['folder_id'] : '')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to files</a>

<div class="row g-4 mt-1">
  <div class="col-lg-8">
    <div class="panel mb-4">
      <div class="d-flex flex-wrap align-items-start gap-3">
        <i class="bi <?= e(FileSecurity::iconFor((string) $doc['extension'])) ?>" style="font-size:3rem"></i>
        <div class="flex-grow-1">
          <h4 class="mb-1"><?= e($doc['name']) ?><span class="text-muted">.<?= e($doc['extension']) ?></span></h4>
          <p class="text-muted mb-1"><?= e($doc['description'] ?: 'No description') ?></p>
          <div class="small text-muted">
            <?= human_bytes($doc['size_bytes']) ?> · Owner <?= e($doc['owner_name']) ?> ·
            Folder <?= e($doc['folder_name'] ?? 'Root') ?> · Category <?= e($doc['category_name'] ?? '—') ?> ·
            <?= (int) $doc['download_count'] ?> downloads · Updated <?= e(substr((string) $doc['updated_at'], 0, 10)) ?>
          </div>
          <div class="small mt-1">
            <?php if (!empty($doc['department_name'])): ?><span class="badge text-bg-light"><i class="bi bi-diagram-3 me-1"></i><?= e($doc['department_name']) ?></span><?php endif; ?>
            <?php if (!empty($doc['expires_at'])): ?><span class="badge <?= $doc['expires_at'] < date('Y-m-d') ? 'text-bg-danger' : 'text-bg-warning' ?>"><i class="bi bi-calendar-x me-1"></i><?= $doc['expires_at'] < date('Y-m-d') ? 'Expired' : 'Expires' ?> <?= e($doc['expires_at']) ?></span><?php endif; ?>
            <?php if (($doc['status'] ?? 'active') === 'archived'): ?><span class="badge text-bg-secondary">Archived</span><?php endif; ?>
          </div>
          <?php foreach ($tags as $t): ?><span class="badge text-bg-light me-1"><i class="bi bi-tag me-1"></i><?= e($t['name']) ?></span><?php endforeach; ?>
        </div>
      </div>
      <hr>
      <div class="d-flex flex-wrap gap-2">
        <?php if (FileSecurity::isPreviewable((string) $doc['extension'])): ?>
        <button class="btn btn-outline-navy" data-bs-toggle="collapse" data-bs-target="#previewBox"><i class="bi bi-eye me-1"></i>Preview</button>
        <?php endif; ?>
        <a class="btn btn-purple" href="<?= url('app/documents/' . (int) $doc['id'] . '/download') ?>"><i class="bi bi-download me-1"></i>Download</a>
        <button type="button" class="btn <?= $isFavorite ? 'btn-warning' : 'btn-outline-secondary' ?>" data-df-fav="<?= (int) $doc['id'] ?>" title="Favourite">
          <i class="bi <?= $isFavorite ? 'bi-star-fill' : 'bi-star' ?>"></i></button>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil me-1"></i>Rename / move / copy</button>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#versionModal"><i class="bi bi-clock-history me-1"></i>New version</button>
        <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/delete') ?>" onsubmit="return confirm('Move this document to trash?')">
          <?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button></form>
      </div>
      <?php if (FileSecurity::isPreviewable((string) $doc['extension'])): ?>
      <div class="collapse mt-3" id="previewBox">
        <?php if (in_array($doc['extension'], ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)): ?>
          <img src="<?= url('app/documents/' . (int) $doc['id'] . '/preview') ?>" class="img-fluid rounded border" alt="preview">
        <?php else: ?>
          <iframe src="<?= url('app/documents/' . (int) $doc['id'] . '/preview') ?>" class="w-100 rounded border" style="height:520px" title="Document preview"></iframe>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Versions -->
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-clock-history me-1"></i>Version history</h5><span class="text-muted small"><?= count($versions) ?> version(s)</span></div>
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Version</th><th>Size</th><th>Uploaded by</th><th>Note</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($versions as $v): ?>
          <tr class="<?= $v['stored_name'] === $doc['stored_name'] ? 'table-light fw-semibold' : '' ?>">
            <td>v<?= (int) $v['version_number'] ?> <?= $v['stored_name'] === $doc['stored_name'] ? '<span class="badge text-bg-success">current</span>' : '' ?></td>
            <td><?= human_bytes($v['size_bytes']) ?></td><td><?= e($v['uploader']) ?></td><td><?= e($v['note'] ?? '') ?></td>
            <td><?= e(substr((string) $v['created_at'], 0, 10)) ?></td>
            <td><?php if ($v['stored_name'] !== $doc['stored_name']): ?>
              <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/version/' . (int) $v['id'] . '/restore') ?>"><?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-secondary">Restore</button></form><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Comments -->
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-chat-dots me-1"></i>Comments</h5></div>
      <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/comment') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <input class="form-control" name="body" placeholder="Add a comment…" required>
        <button class="btn btn-purple">Post</button>
      </form>
      <?php foreach ($comments as $c): ?>
      <div class="d-flex gap-2 mb-3"><span class="avatar"><?= e(strtoupper(substr($c['author'], 0, 1))) ?></span>
        <div><strong><?= e($c['author']) ?></strong> <small class="text-muted"><?= e(substr((string) $c['created_at'], 0, 16)) ?></small>
        <div><?= e($c['body']) ?></div></div></div>
      <?php endforeach; ?>
      <?php if (!$comments): ?><p class="text-muted mb-0 small">No comments yet.</p><?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Sharing -->
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-link-45deg me-1"></i>Share links</h5></div>
      <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/share') ?>" class="mb-3">
        <?= csrf_field() ?>
        <div class="d-flex gap-2 mb-2">
          <select class="form-select" name="days"><option value="1">1 day</option><option value="7" selected>7 days</option><option value="30">30 days</option><option value="90">90 days</option><option value="365">1 year</option></select>
          <label class="form-check form-switch align-self-center m-0"><input type="checkbox" class="form-check-input" name="can_download" value="1" checked> DL</label>
          <button class="btn btn-purple">Create</button>
        </div>
        <input class="form-control form-control-sm" name="password" placeholder="Optional link password (leave blank for none)" autocomplete="new-password">
      </form>
      <?php foreach ($shares as $s): ?>
      <div class="share-row">
        <code class="small text-break"><?= url('s/' . $s['token']) ?></code>
        <div class="small text-muted"><?= $s['is_active'] ? 'Active' : 'Revoked' ?> · <?= (int) $s['views'] ?> views · expires <?= e(substr((string) $s['expires_at'], 0, 10)) ?> · <?= $s['can_download'] ? 'download on' : 'view only' ?><?= !empty($s['password_hash']) ? ' · <i class="bi bi-lock-fill"></i> password' : '' ?></div>
        <?php if ($s['is_active']): ?><form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/share/' . (int) $s['id'] . '/revoke') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger mt-1">Revoke</button></form><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (!$shares): ?><p class="text-muted small mb-0">No share links yet.</p><?php endif; ?>
    </div>

    <!-- Permissions -->
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-people me-1"></i>Permissions</h5></div>
      <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/permission') ?>">
        <?= csrf_field() ?>
        <div class="row g-2">
          <div class="col-5"><select class="form-select" name="subject_type" id="permType"><option value="user">User</option><option value="team">Team</option></select></div>
          <div class="col-7"><select class="form-select" name="subject_id" id="permSubject">
            <?php foreach ($tenantUsers as $tu): ?><option data-type="user" value="<?= (int) $tu['id'] ?>"><?= e($tu['name']) ?></option><?php endforeach; ?>
            <?php foreach ($teams as $tm): ?><option data-type="team" value="<?= (int) $tm['id'] ?>">Team: <?= e($tm['name']) ?></option><?php endforeach; ?>
          </select></div>
          <div class="col-12 d-flex gap-3">
            <label class="form-check"><input type="checkbox" class="form-check-input" name="can_download" value="1"> Download</label>
            <label class="form-check"><input type="checkbox" class="form-check-input" name="can_edit" value="1"> Edit</label>
            <button class="btn btn-sm btn-purple ms-auto">Grant view +</button>
          </div>
        </div>
      </form>
      <hr>
      <?php foreach ($perms as $p): ?>
      <div class="small d-flex justify-content-between py-1 border-bottom">
        <span><?= e(ucfirst($p['subject_type'])) ?> #<?= (int) $p['subject_id'] ?></span>
        <span class="text-muted">view <?= $p['can_view'] ? '✓' : '—' ?> · download <?= $p['can_download'] ? '✓' : '—' ?> · edit <?= $p['can_edit'] ? '✓' : '—' ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$perms): ?><p class="text-muted small mb-0">Only the owner and company admins can access this file.</p><?php endif; ?>
    </div>

    <!-- Metadata -->
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-sliders me-1"></i>Metadata</h5></div>
      <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/update') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="rename">
        <input type="hidden" name="name" value="<?= e($doc['name']) ?>">
        <input type="hidden" name="description" value="<?= e($doc['description'] ?? '') ?>">
        <input type="hidden" name="category_id" value="<?= (int) ($doc['category_id'] ?? 0) ?>">
        <label class="form-label small">Department</label>
        <select class="form-select form-select-sm mb-2" name="department_id">
          <option value="">— None —</option>
          <?php foreach ($departments as $dep): ?><option value="<?= (int) $dep['id'] ?>" <?= (int) ($doc['department_id'] ?? 0) === (int) $dep['id'] ? 'selected' : '' ?>><?= e($dep['name']) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label small">Expiry / review date</label>
        <input type="date" class="form-control form-control-sm mb-2" name="expires_at" value="<?= e($doc['expires_at'] ?? '') ?>">
        <label class="form-label small">Status</label>
        <select class="form-select form-select-sm mb-3" name="status">
          <option value="active" <?= ($doc['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="archived" <?= ($doc['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
        <button class="btn btn-sm btn-outline-navy w-100">Save metadata</button>
      </form>
      <hr>
      <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/update') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="meta">
        <label class="form-label small">Custom properties</label>
        <div id="propRows">
          <?php foreach ($properties as $p): ?>
          <div class="d-flex gap-1 mb-1"><input class="form-control form-control-sm" name="prop_key[]" value="<?= e($p['prop_key']) ?>" placeholder="Key">
            <input class="form-control form-control-sm" name="prop_value[]" value="<?= e($p['prop_value'] ?? '') ?>" placeholder="Value"></div>
          <?php endforeach; ?>
          <div class="d-flex gap-1 mb-1"><input class="form-control form-control-sm" name="prop_key[]" placeholder="Key">
            <input class="form-control form-control-sm" name="prop_value[]" placeholder="Value"></div>
        </div>
        <label class="form-label small mt-2">Tags</label>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php $docTagIds = array_map(fn($t) => (int) $t['id'], $tags); foreach ($allTags as $t): ?>
          <label class="form-check small"><input type="checkbox" class="form-check-input" name="tag_ids[]" value="<?= (int) $t['id'] ?>" <?= in_array((int) $t['id'], $docTagIds, true) ? 'checked' : '' ?>> <?= e($t['name']) ?></label>
          <?php endforeach; ?>
        </div>
        <button class="btn btn-sm btn-purple w-100">Save properties &amp; tags</button>
      </form>
    </div>

    <!-- Share activity -->
    <?php if ($shareLogs): ?>
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-activity me-1"></i>Share activity</h5></div>
      <?php foreach ($shareLogs as $l): ?>
      <div class="small d-flex justify-content-between py-1 border-bottom">
        <span><i class="bi <?= $l['action'] === 'download' ? 'bi-download' : ($l['action'] === 'unlock_failed' ? 'bi-lock text-danger' : 'bi-eye') ?> me-1"></i><?= e($l['action']) ?></span>
        <span class="text-muted"><?= e(substr((string) $l['created_at'], 0, 16)) ?><?= $l['ip_address'] ? ' · ' . e($l['ip_address']) : '' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Approvals -->
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-check2-square me-1"></i>Approvals</h5>
        <a class="btn btn-sm btn-outline-navy" href="<?= url('app/approvals') ?>">Request</a></div>
      <?php foreach ($approvals as $a): ?>
      <div class="d-flex justify-content-between py-1 border-bottom small">
        <span><?= e($a['title']) ?><br><span class="text-muted">by <?= e($a['requester']) ?></span></span>
        <span class="badge text-bg-<?= $a['status'] === 'approved' ? 'success' : ($a['status'] === 'rejected' ? 'danger' : 'warning') ?> align-self-start"><?= e($a['status']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$approvals): ?><p class="text-muted small mb-0">No approval requests for this document.</p><?php endif; ?>
    </div>
  </div>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Edit document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/update') ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="rename">
      <label class="form-label">Name</label><input class="form-control mb-3" name="name" value="<?= e($doc['name']) ?>">
      <label class="form-label">Description</label><textarea class="form-control mb-3" name="description" rows="2"><?= e($doc['description']) ?></textarea>
      <button class="btn btn-purple">Save</button>
    </form>
    <hr>
    <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/update') ?>" class="d-flex gap-2">
      <?= csrf_field() ?>
      <select class="form-select" name="folder_id"><option value="">Root</option>
        <?php foreach (Database::all('SELECT id, name FROM folders WHERE tenant_id = ? ORDER BY name', [(int) $doc['tenant_id']]) as $f): ?><option value="<?= (int) $f['id'] ?>" <?= (int) ($doc['folder_id'] ?? 0) === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-outline-navy" name="action" value="move">Move</button>
      <button class="btn btn-outline-secondary" name="action" value="copy">Copy</button>
    </form>
  </div>
</div></div></div>

<!-- New version modal -->
<div class="modal fade" id="versionModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" enctype="multipart/form-data" action="<?= url('app/documents/' . (int) $doc['id'] . '/version') ?>">
  <?= csrf_field() ?>
  <div class="modal-header"><h5 class="modal-title">Upload new version</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">File</label><input type="file" class="form-control mb-3" name="file" required>
    <label class="form-label">Change note</label><input class="form-control" name="note" placeholder="What changed in this version?">
  </div>
  <div class="modal-footer"><button class="btn btn-purple" type="submit">Upload version</button></div>
</form></div></div>
<script>
const pt = document.getElementById('permType'), ps = document.getElementById('permSubject');
if (pt && ps) { const sync = () => { [...ps.options].forEach(o => o.hidden = o.dataset.type !== pt.value); ps.value = [...ps.options].find(o => !o.hidden)?.value; }; pt.addEventListener('change', sync); sync(); }
</script>
<?php partial('app-close'); ?>
