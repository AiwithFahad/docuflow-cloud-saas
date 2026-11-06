<?php $pageTitle = 'Admin Dashboard'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['bi-file-earmark-text', 'Documents', $stats['documents'], 'documents'],
    ['bi-folder', 'Folders', $stats['folders'], 'folders'],
    ['bi-people', 'Active users', $stats['users'], 'active_users'],
    ['bi-diagram-3', 'Teams', $stats['teams'], null],
    ['bi-link-45deg', 'Shared documents', $stats['shared'], 'shared_documents'],
    ['bi-check2-square', 'Pending approvals', $stats['pending_approvals'], null],
    ['bi-trash3', 'In trash', $stats['trash'], 'trash'],
    ['bi-calendar-x', 'Expiring ≤ 30 days', $stats['expiring'], null],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi <?= $c[0] ?>"></i></span>
    <div><div class="stat-value" <?= $c[3] ? 'data-df-stat="' . $c[3] . '"' : '' ?>><?= (int) $c[2] ?></div><div class="stat-name"><?= e($c[1]) ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="panel mb-4">
  <div class="panel-head"><h5><i class="bi bi-hdd-stack me-1"></i>Storage</h5><a href="<?= url('admin/storage') ?>">Storage details</a></div>
  <div class="d-flex justify-content-between"><span><strong data-df-stat="storage_used_human"><?= human_bytes($stats['storage_used']) ?></strong> used</span>
  <span class="text-muted">of <?= $stats['storage_cap'] >= 0 ? human_bytes($stats['storage_cap']) : 'unlimited' ?> on the <?= e($sub['plan_name'] ?? '') ?> plan</span></div>
  <div class="progress mt-2" style="height:12px"><div class="progress-bar bg-purple" data-df-storagebar style="width:<?= $stats['storage_pct'] ?>%"></div></div>
</div>

<div class="panel mb-4">
  <div class="panel-head"><h5><i class="bi bi-graph-up me-1"></i>Workspace analytics</h5>
    <form id="chartFilters" class="d-flex gap-2 align-items-center">
      <input type="date" class="form-control form-control-sm" name="from" title="From">
      <input type="date" class="form-control form-control-sm" name="to" title="To">
      <select class="form-select form-select-sm" name="user"><option value="">All users</option>
        <?php foreach (Database::all('SELECT id, name FROM users WHERE tenant_id = ? ORDER BY name', [(int) $tenant['id']]) as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select>
      <select class="form-select form-select-sm" name="category"><option value="">All categories</option>
        <?php foreach (Database::all('SELECT id, name FROM categories WHERE tenant_id = ? ORDER BY name', [(int) $tenant['id']]) as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
    </form></div>
  <div class="row g-4">
    <div class="col-lg-6"><h6 class="text-muted">Monthly uploads</h6><canvas data-df-chart="uploads" data-form="#chartFilters" height="140"></canvas></div>
    <div class="col-lg-6"><h6 class="text-muted">Storage growth (MB)</h6><canvas data-df-chart="storage" height="140"></canvas></div>
    <div class="col-lg-4"><h6 class="text-muted">Documents by category</h6><canvas data-df-chart="categories" height="160"></canvas></div>
    <div class="col-lg-4"><h6 class="text-muted">Team performance (uploads)</h6><canvas data-df-chart="teams" height="160"></canvas></div>
    <div class="col-lg-4"><h6 class="text-muted">Approvals by status</h6><canvas data-df-chart="approvals" height="160"></canvas></div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7"><div class="panel h-100"><div class="panel-head"><h5>Recent documents</h5><a href="<?= url('admin/files') ?>">File manager</a></div>
    <?php foreach ($recent as $d): ?>
    <div class="d-flex justify-content-between border-bottom py-2">
      <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><?= e($d['name']) ?></a>
      <span class="text-muted small"><?= e($d['owner_name']) ?> · <?= e(substr((string) $d['updated_at'], 0, 10)) ?></span></div>
    <?php endforeach; ?>
    <?php if (!$recent): ?><p class="text-muted mb-0">No documents yet.</p><?php endif; ?>
  </div></div>
  <div class="col-lg-5">
    <div class="panel mb-4"><div class="panel-head"><h5>Most downloaded</h5></div>
      <?php foreach ($topDownloads as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span><?= e($d['name']) ?></span><span class="badge text-bg-light"><?= (int) $d['download_count'] ?> downloads</span></div>
      <?php endforeach; ?>
    </div>
    <div class="panel"><div class="panel-head"><h5>Recent activity</h5><a href="<?= url('admin/audit-logs') ?>">Logs</a></div>
      <?php foreach ($activity as $a): ?>
      <div class="small border-bottom py-2"><strong><?= e($a['user_name'] ?? 'System') ?></strong> · <?= e($a['description']) ?>
        <div class="text-muted"><?= e(substr((string) $a['created_at'], 0, 16)) ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
