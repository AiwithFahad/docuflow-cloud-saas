<?php $pageTitle = 'Manager Dashboard'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-people"></i></span>
    <div><div class="stat-value"><?= count($myTeams) ?></div><div class="stat-name">My teams</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-file-earmark-text"></i></span>
    <div><div class="stat-value"><?= count($teamDocs) ?></div><div class="stat-name">Recent team documents</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-check2-square"></i></span>
    <div><div class="stat-value" data-df-stat="pending_approvals"><?= count($inbox) ?></div><div class="stat-name">Approvals waiting for me</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi bi-share"></i></span>
    <div><div class="stat-value"><?= count($shared) ?></div><div class="stat-name">Shared with my teams</div></div></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4"><div class="panel-head"><h5><i class="bi bi-inbox me-1"></i>Assigned tasks — approvals</h5><a href="<?= url('manager/approvals') ?>">Open approvals</a></div>
      <?php foreach ($inbox as $a): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <span><?= e($a['title']) ?><br><span class="small text-muted"><?= e($a['doc_name']) ?> · from <?= e($a['requester_name']) ?></span></span>
        <a class="btn btn-sm btn-purple align-self-start" href="<?= url('manager/approvals') ?>">Decide</a></div>
      <?php endforeach; ?>
      <?php if (!$inbox): ?><p class="text-muted mb-0">No approvals waiting for you.</p><?php endif; ?>
    </div>
    <div class="panel mb-4"><div class="panel-head"><h5>Team documents</h5><a href="<?= url('manager/files') ?>">Team files</a></div>
      <?php foreach ($teamDocs as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><?= e($d['name']) ?></a>
        <span class="text-muted small"><?= e($d['owner_name']) ?> · <?= e(substr((string) $d['updated_at'], 0, 10)) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$teamDocs): ?><p class="text-muted mb-0">No team documents yet.</p><?php endif; ?>
    </div>
    <div class="panel"><div class="panel-head"><h5>Recent team uploads</h5></div>
      <?php foreach ($uploads as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span><?= e($d['name']) ?></span>
        <span class="text-muted small"><?= e($d['owner_name']) ?> · <?= e(substr((string) $d['created_at'], 0, 10)) ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel mb-4"><div class="panel-head"><h5>My teams</h5></div>
      <?php foreach ($myTeams as $t): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span><strong><?= e($t['name']) ?></strong><br><span class="small text-muted"><?= e($t['description'] ?? '') ?></span></span>
        <span class="badge text-bg-light align-self-start"><?= (int) $t['members'] ?> members</span></div>
      <?php endforeach; ?>
      <?php if (!$myTeams): ?><p class="text-muted mb-0">You are not on any team yet — ask an admin to add you.</p><?php endif; ?>
    </div>
    <div class="panel mb-4"><div class="panel-head"><h5>Shared with my teams</h5></div>
      <?php foreach ($shared as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><?= e($d['name']) ?></a>
        <span class="text-muted small">by <?= e($d['owner_name']) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$shared): ?><p class="text-muted mb-0">Nothing shared with your teams yet.</p><?php endif; ?>
    </div>
    <div class="panel"><div class="panel-head"><h5>Team activity</h5></div>
      <?php foreach ($activity as $a): ?>
      <div class="small border-bottom py-2"><strong><?= e($a['user_name'] ?? 'System') ?></strong> · <?= e($a['description']) ?>
        <div class="text-muted"><?= e(substr((string) $a['created_at'], 0, 16)) ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
