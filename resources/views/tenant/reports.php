<?php $pageTitle = 'Reports'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <p class="text-muted mb-0">All figures are calculated live from your company's database records.</p>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-navy" href="<?= url('app/reports?export=csv') ?>"><i class="bi bi-filetype-csv me-1"></i>Export documents CSV</a>
    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / PDF</button>
  </div>
</div>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Uploads by month</h5></div>
      <canvas id="repChart" height="140"></canvas>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Approval outcomes</h5></div>
      <?php foreach ($report['approvals'] as $a): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span class="text-capitalize"><?= e($a['status']) ?></span><strong><?= (int) $a['total'] ?></strong></div>
      <?php endforeach; ?>
      <?php if (!$report['approvals']): ?><p class="text-muted mb-0">No approvals yet.</p><?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Top downloaded</h5></div>
      <?php foreach ($report['top_downloads'] as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span><?= e($d['name']) ?></span><span class="badge text-bg-light"><?= (int) $d['download_count'] ?> downloads</span></div>
      <?php endforeach; ?>
      <?php if (!$report['top_downloads']): ?><p class="text-muted mb-0">No downloads yet.</p><?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Contributions by user</h5></div>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>User</th><th>Documents</th><th>Storage</th></tr></thead><tbody>
      <?php foreach ($report['by_user'] as $u): ?>
        <tr><td><?= e($u['name']) ?></td><td><?= (int) $u['docs'] ?></td><td><?= human_bytes($u['bytes']) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const c = document.getElementById('repChart');
  if (c) new Chart(c, {type:'bar', data:{labels: <?= json_encode(array_column($report['uploads_by_month'], 'label')) ?>,
    datasets:[{label:'Uploads', data: <?= json_encode(array_map('intval', array_column($report['uploads_by_month'], 'total'))) ?>, backgroundColor:'#7c3aed', borderRadius:6}]},
    options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{precision:0}}}}});
});
</script>
<?php partial('app-close'); ?>
