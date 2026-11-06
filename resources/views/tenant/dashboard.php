<?php $pageTitle = 'Overview'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div><h3 class="mb-0">Welcome back, <?= e(explode(' ', $user['name'])[0]) ?></h3>
  <p class="text-muted mb-0">Here's what's happening in <?= e($tenant['name']) ?>.</p></div>
  <a class="btn btn-purple" href="<?= url('app/upload') ?>"><i class="bi bi-cloud-upload me-1"></i> Upload documents</a>
</div>

<div class="row g-3 mb-4">
  <?php $cards = [
    ['bi-file-earmark-text', 'purple', $stats['documents'], 'Documents'],
    ['bi-folder2', 'navy', $stats['folders'], 'Folders'],
    ['bi-people', 'navy', $stats['users'], 'Team users'],
    ['bi-diagram-3', 'purple', $stats['teams'], 'Teams'],
    ['bi-check2-square', 'navy', $stats['pending_approvals'], 'Approvals waiting on you'],
    ['bi-link-45deg', 'purple', $stats['shares'], 'Active share links'],
  ]; foreach ($cards as [$icon, $color, $num, $label]): ?>
  <div class="col-6 col-md-4 col-xl-2"><div class="stat-card">
    <span class="stat-icon <?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
    <div><div class="stat-value"><?= number_format((int) $num) ?></div><div class="stat-name"><?= e($label) ?></div></div>
  </div></div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Uploads — last 6 months</h5><a href="<?= url('app/reports') ?>">Full reports</a></div>
      <canvas id="uploadsChart" height="110"></canvas>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Recent documents</h5><a href="<?= url('app/files') ?>">Open file manager</a></div>
      <?php if (!$recent): ?><p class="text-muted mb-0">No documents yet — upload your first file.</p><?php endif; ?>
      <div class="list-group list-group-flush">
        <?php foreach ($recent as $d): ?>
        <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-0" href="<?= url('app/documents/' . (int) $d['id']) ?>">
          <i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> fs-4"></i>
          <span class="flex-grow-1"><strong><?= e($d['name']) ?></strong><small class="d-block text-muted"><?= e($d['owner_name']) ?> · <?= human_bytes($d['size_bytes']) ?> · <?= e(substr((string) $d['updated_at'], 0, 10)) ?></small></span>
          <i class="bi bi-chevron-right text-muted"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Documents by category</h5></div>
      <canvas id="catChart" height="200"></canvas>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Storage</h5><a href="<?= url('app/storage') ?>">Manage</a></div>
      <?php $cap = $sub && (int) $sub['storage_mb'] >= 0 ? (int) $sub['storage_mb'] * 1024 * 1024 : 0;
            $pct = $cap > 0 ? min(100, round($stats['storage'] / $cap * 100, 1)) : 0; ?>
      <div class="d-flex justify-content-between small mb-1"><span><?= human_bytes($stats['storage']) ?> used</span><span><?= $cap > 0 ? human_bytes($cap) : 'Unlimited' ?></span></div>
      <div class="progress" style="height:10px"><div class="progress-bar bg-purple" style="width:<?= $pct ?>%"></div></div>
      <p class="small text-muted mt-2 mb-0"><?= $cap > 0 ? $pct . '% of your ' . e($sub['plan_name']) . ' plan quota' : 'Your plan includes unlimited storage.' ?></p>
    </div>
    <div class="panel mt-4">
      <div class="panel-head"><h5>Recent activity</h5><a href="<?= url('app/activity') ?>">View all</a></div>
      <?php foreach ($activity as $a): ?>
      <div class="activity-item"><i class="bi bi-dot"></i><div><strong><?= e($a['action']) ?></strong>
        <small class="d-block text-muted"><?= e($a['description']) ?> · <?= e(substr((string) $a['created_at'], 0, 16)) ?></small></div></div>
      <?php endforeach; ?>
      <?php if (!$activity): ?><p class="text-muted mb-0 small">No activity yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const u = document.getElementById('uploadsChart');
  if (u) new Chart(u, {type:'line', data:{labels: <?= json_encode(array_column($uploads, 'month')) ?>,
    datasets:[{label:'Uploads', data: <?= json_encode(array_map('intval', array_column($uploads, 'total'))) ?>, borderColor:'#7c3aed', backgroundColor:'rgba(124,58,237,.12)', fill:true, tension:.35}]},
    options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{precision:0}}}}});
  const c = document.getElementById('catChart');
  if (c) new Chart(c, {type:'doughnut', data:{labels: <?= json_encode(array_column($byCategory, 'label')) ?>,
    datasets:[{data: <?= json_encode(array_map('intval', array_column($byCategory, 'total'))) ?>, backgroundColor:['#7c3aed','#1e3a5f','#a78bfa','#3b82c4','#c4b5fd','#64748b']}]},
    options:{plugins:{legend:{position:'bottom'}}}});
});
</script>
<?php partial('app-close'); ?>
