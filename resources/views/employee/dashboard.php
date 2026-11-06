<?php $pageTitle = 'My Dashboard'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree'));
$pctMine = $tenantBytes > 0 ? round($myBytes / $tenantBytes * 100, 1) : 0; ?>
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-file-earmark-text"></i></span>
    <div><div class="stat-value"><?= count($myDocs) ?></div><div class="stat-name">Recent of my documents</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-share"></i></span>
    <div><div class="stat-value"><?= count($shared) ?></div><div class="stat-name">Shared with me</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-check2-square"></i></span>
    <div><div class="stat-value"><?= count($inbox) + $myPending ?></div><div class="stat-name">Pending approvals (mine + to decide)</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-star"></i></span>
    <div><div class="stat-value"><?= $favCount ?></div><div class="stat-name">Favourites</div></div></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4"><div class="panel-head"><h5>My documents</h5><a href="<?= url('employee/files') ?>">My files</a></div>
      <?php foreach ($myDocs as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><?= e($d['name']) ?></a>
        <span class="text-muted small"><?= e($d['category_name'] ?? '—') ?> · <?= e(substr((string) $d['updated_at'], 0, 10)) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$myDocs): ?><p class="text-muted mb-0">You have not uploaded anything yet.</p><?php endif; ?>
    </div>
    <div class="panel mb-4"><div class="panel-head"><h5>Recent files (accessible to me)</h5></div>
      <?php foreach ($recent as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><?= e($d['name']) ?></a>
        <span class="text-muted small"><?= e($d['owner_name'] ?? '') ?> · <?= e(substr((string) $d['updated_at'], 0, 10)) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$recent): ?><p class="text-muted mb-0">No recent files.</p><?php endif; ?>
    </div>
    <div class="panel"><div class="panel-head"><h5>Shared with me</h5></div>
      <?php foreach ($shared as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><?= e($d['name']) ?></a>
        <span class="small text-muted">by <?= e($d['owner_name']) ?> · <?= $d['can_download'] ? 'download ✓' : 'view only' ?><?= $d['can_edit'] ? ' · edit ✓' : '' ?></span></div>
      <?php endforeach; ?>
      <?php if (!$shared): ?><p class="text-muted mb-0">Nothing has been shared with you yet.</p><?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel mb-4"><div class="panel-head"><h5><i class="bi bi-hdd-stack me-1"></i>My storage</h5></div>
      <div class="display-6 fw-bold"><?= human_bytes($myBytes) ?></div>
      <p class="text-muted"><?= $pctMine ?>% of the workspace total (<?= human_bytes($tenantBytes) ?>)</p>
      <div class="progress" style="height:12px"><div class="progress-bar bg-purple" style="width:<?= min(100, $pctMine) ?>%"></div></div>
    </div>
    <div class="panel mb-4"><div class="panel-head"><h5>My folders</h5></div>
      <?php foreach ($myFolders as $f): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('employee/files?folder=' . (int) $f['id']) ?>"><i class="bi bi-folder-fill me-1" style="color:<?= e($f['color']) ?>"></i><?= e($f['name']) ?></a>
        <span class="badge text-bg-light"><?= (int) $f['docs'] ?> files</span></div>
      <?php endforeach; ?>
      <?php if (!$myFolders): ?><p class="text-muted mb-0">You have not created any folders.</p><?php endif; ?>
    </div>
    <div class="panel mb-4"><div class="panel-head"><h5>Approvals</h5><a href="<?= url('employee/approvals') ?>">Open</a></div>
      <?php foreach ($inbox as $a): ?>
      <div class="small border-bottom py-2"><strong>To decide:</strong> <?= e($a['title']) ?> <span class="text-muted">(<?= e($a['doc_name']) ?>)</span></div>
      <?php endforeach; ?>
      <div class="small py-2"><?= $myPending ?> of my requests still pending.</div>
    </div>
    <div class="panel"><div class="panel-head"><h5>Notifications</h5><a href="<?= url('employee/notifications') ?>">All</a></div>
      <?php foreach ($notifList as $n): ?>
      <div class="small border-bottom py-2 <?= $n['is_read'] ? '' : 'fw-semibold' ?>"><?= e($n['title']) ?>
        <div class="text-muted fw-normal"><?= e($n['body']) ?></div></div>
      <?php endforeach; ?>
      <?php if (!$notifList): ?><p class="text-muted small mb-0">No notifications.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
