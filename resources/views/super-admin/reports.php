<?php $pageTitle = 'Platform Reports'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="d-flex gap-2 mb-3">
  <a class="btn btn-purple btn-sm" href="<?= url('super-admin/reports?export=revenue') ?>"><i class="bi bi-filetype-csv me-1"></i>Export revenue CSV</a>
  <button class="btn btn-outline-navy btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / PDF</button>
</div>
<div class="row g-4">
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Revenue by month</h5></div><canvas id="revChart" height="150"></canvas></div></div>
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Organization signups</h5></div><canvas id="signChart" height="150"></canvas></div></div>
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Subscriptions by status</h5></div>
    <table class="table table-sm mb-0"><tbody>
    <?php foreach ($subsByStatus as $r): ?><tr><td><?= e(ucfirst($r['label'])) ?></td><td class="text-end fw-semibold"><?= (int) $r['total'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h5>Plan mix (active + trials)</h5></div>
    <table class="table table-sm mb-0"><tbody>
    <?php foreach ($planMix as $r): ?><tr><td><?= e($r['label']) ?></td><td class="text-end fw-semibold"><?= (int) $r['total'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="col-12"><div class="panel"><div class="panel-head"><h5>Top organizations by storage</h5></div>
    <table class="table table-sm align-middle mb-0"><thead><tr><th>Organization</th><th>Documents</th><th>Storage</th></tr></thead><tbody>
    <?php foreach ($topTenants as $t): ?><tr><td><?= e($t['name']) ?></td><td><?= (int) $t['docs'] ?></td><td><?= human_bytes((int) $t['bytes']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const mk = (id, labels, data, color) => { const el = document.getElementById(id); if (el) new Chart(el, {type:'bar',
    data:{labels, datasets:[{data, backgroundColor:color, borderRadius:6}]}, options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}}); };
  mk('revChart', <?= json_encode(array_reverse(array_column($revenue, 'label'))) ?>, <?= json_encode(array_reverse(array_map('floatval', array_column($revenue, 'total')))) ?>, '#7c3aed');
  mk('signChart', <?= json_encode(array_reverse(array_column($signups, 'label'))) ?>, <?= json_encode(array_reverse(array_map('intval', array_column($signups, 'total')))) ?>, '#1e3a5f');
});
</script>
<?php partial('app-close'); ?>
