<?php $pageTitle = 'Dashboard'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['bi-buildings', 'Organizations', $stats['tenants'], $stats['tenants_active'] . ' active · ' . $stats['tenants_suspended'] . ' suspended'],
    ['bi-people', 'Users', $stats['users'], 'across all organizations'],
    ['bi-arrow-repeat', 'Active subscriptions', $stats['subs_active'], 'incl. trials'],
    ['bi-cash-stack', 'MRR', '$' . number_format($stats['mrr'], 2), 'ARR $' . number_format($stats['arr'], 2)],
    ['bi-file-earmark-text', 'Documents', $stats['documents'], human_bytes($stats['storage_used']) . ' stored'],
    ['bi-hourglass-split', 'Pending payments', $stats['payments_pending'], 'awaiting verification'],
    ['bi-link-45deg', 'Active share links', $stats['shares'], 'public links live'],
    ['bi-life-preserver', 'Open tickets', $stats['open_tickets'], 'support queue'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-xl-3"><div class="stat-card h-100"><span class="stat-icon"><i class="bi <?= $c[0] ?>"></i></span>
    <div><div class="stat-value"><?= e((string) $c[2]) ?></div><div class="stat-name"><?= e($c[1]) ?></div>
    <div class="small text-muted"><?= e($c[3]) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel mb-4"><div class="panel-head"><h5>Revenue by month</h5><a href="<?= url('super-admin/reports') ?>">Reports</a></div>
      <canvas id="revChart" height="110"></canvas></div>
    <div class="panel"><div class="panel-head"><h5>Recent payments</h5><a href="<?= url('super-admin/payments') ?>">All payments</a></div>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>Organization</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($recentPayments as $p): ?><tr><td><?= e($p['tenant_name']) ?></td><td>$<?= number_format((float) $p['amount'], 2) ?></td>
        <td><?= e($p['method']) ?></td><td><span class="badge text-bg-<?= $p['status'] === 'paid' ? 'success' : ($p['status'] === 'pending' ? 'warning' : 'danger') ?>"><?= e($p['status']) ?></span></td>
        <td><?= e(substr((string) $p['created_at'], 0, 10)) ?></td></tr><?php endforeach; ?>
      <?php if (!$recentPayments): ?><tr><td colspan="5" class="text-muted">No payments yet.</td></tr><?php endif; ?>
      </tbody></table></div>
  </div>
  <div class="col-lg-4">
    <div class="panel mb-4"><div class="panel-head"><h5>Plan mix</h5></div><canvas id="mixChart" height="200"></canvas></div>
    <div class="panel"><div class="panel-head"><h5>Newest organizations</h5><a href="<?= url('super-admin/organizations') ?>">All</a></div>
      <?php foreach ($recentTenants as $t): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span><?= e($t['name']) ?></span>
        <span class="badge text-bg-<?= $t['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($t['status']) ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const r = document.getElementById('revChart');
  if (r) new Chart(r, {type:'bar', data:{labels: <?= json_encode(array_reverse(array_column($revenueByMonth, 'label'))) ?>,
    datasets:[{label:'Revenue $', data: <?= json_encode(array_reverse(array_map('floatval', array_column($revenueByMonth, 'total')))) ?>, backgroundColor:'#7c3aed', borderRadius:6}]},
    options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}});
  const m = document.getElementById('mixChart');
  if (m) new Chart(m, {type:'doughnut', data:{labels: <?= json_encode(array_column($planMix, 'label')) ?>,
    datasets:[{data: <?= json_encode(array_map('intval', array_column($planMix, 'total'))) ?>, backgroundColor:['#7c3aed','#1e3a5f','#a78bfa','#3b82c4','#c4b5fd']}]},
    options:{plugins:{legend:{position:'bottom'}}}});
});
</script>
<?php partial('app-close'); ?>
