<?php $pageTitle = 'Reports'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <span class="align-self-center fw-semibold me-1">Export CSV:</span>
  <?php foreach (['documents', 'activity', 'users', 'sharing', 'approvals'] as $x): ?>
  <a class="btn btn-sm btn-outline-navy" href="<?= url('admin/reports?export=' . $x) ?>"><i class="bi bi-filetype-csv me-1"></i><?= ucfirst($x) ?></a>
  <?php endforeach; ?>
  <button class="btn btn-sm btn-purple ms-auto" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / save as PDF</button>
</div>

<div class="panel mb-4"><div class="panel-head"><h5>Live charts</h5>
  <form id="repFilters" class="d-flex gap-2"><input type="date" class="form-control form-control-sm" name="from"><input type="date" class="form-control form-control-sm" name="to"></form></div>
  <div class="row g-4">
    <div class="col-lg-6"><h6 class="text-muted">Daily activity</h6><canvas data-df-chart="activity" data-form="#repFilters" height="130"></canvas></div>
    <div class="col-lg-6"><h6 class="text-muted">Monthly uploads</h6><canvas data-df-chart="uploads" data-form="#repFilters" height="130"></canvas></div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Uploads by month</h5></div>
    <table class="table table-sm mb-0"><thead><tr><th>Month</th><th>Files</th><th>Size</th></tr></thead><tbody>
    <?php foreach ($report['uploads_by_month'] as $r): ?><tr><td><?= e($r['label']) ?></td><td><?= (int) $r['total'] ?></td><td><?= human_bytes((int) $r['bytes']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Most downloaded</h5></div>
    <table class="table table-sm mb-0"><thead><tr><th>Document</th><th>Downloads</th><th>Size</th></tr></thead><tbody>
    <?php foreach ($report['top_downloads'] as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int) $r['download_count'] ?></td><td><?= human_bytes((int) $r['size_bytes']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>User activity</h5></div>
    <table class="table table-sm mb-0"><thead><tr><th>User</th><th>Docs</th><th>Storage</th><th>Downloads</th></tr></thead><tbody>
    <?php foreach ($report['by_user'] as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int) $r['docs'] ?></td><td><?= human_bytes((int) $r['bytes']) ?></td><td><?= (int) $r['downloads'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Team performance</h5></div>
    <table class="table table-sm mb-3"><thead><tr><th>Team</th><th>Member documents</th></tr></thead><tbody>
    <?php foreach ($report['by_team'] as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int) $r['docs'] ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <div class="panel-head"><h5>Approvals</h5></div>
    <table class="table table-sm mb-3"><tbody>
    <?php foreach ($report['approvals'] as $r): ?><tr><td><?= e(ucwords(str_replace('_', ' ', $r['status']))) ?></td><td class="text-end fw-semibold"><?= (int) $r['total'] ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <div class="panel-head"><h5>Sharing</h5></div>
    <p class="small mb-1"><?= (int) ($report['sharing']['links'] ?? 0) ?> link(s) created · <?= (int) ($report['sharing']['views'] ?? 0) ?> total views</p>
    <table class="table table-sm mb-0"><tbody>
    <?php foreach ($report['share_actions'] as $r): ?><tr><td><?= e($r['action']) ?></td><td class="text-end fw-semibold"><?= (int) $r['total'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>
<?php partial('app-close'); ?>
