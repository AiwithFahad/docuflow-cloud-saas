<?php $pageTitle = 'Platform Dashboard'; partial('super-open', compact('user', 'pageTitle')); ?>
<p class="text-muted">Every figure below is computed live from the platform database.</p>
<div class="row g-3 mb-4">
  <?php $cards = [
    ['bi-buildings', $stats['tenants'], 'Total tenants'],
    ['bi-building-check', $stats['tenants_active'], 'Active tenants'],
    ['bi-building-dash', $stats['tenants_suspended'], 'Suspended tenants'],
    ['bi-people', $stats['users'], 'Total users'],
    ['bi-arrow-repeat', $stats['subs_active'], 'Active subscriptions'],
    ['bi-hourglass-split', $stats['subs_expired'], 'Expired subscriptions'],
    ['bi-cash-coin', '$' . number_format($stats['mrr'], 2), 'MRR'],
    ['bi-graph-up', '$' . number_format($stats['arr'], 2), 'ARR'],
    ['bi-credit-card', $stats['transactions'], 'Transactions'],
    ['bi-check2-circle', $stats['payments_paid'], 'Paid payments'],
    ['bi-clock-history', $stats['payments_pending'], 'Pending verification'],
    ['bi-hdd', human_bytes($stats['storage_used']), 'Storage used'],
  ]; foreach ($cards as [$icon, $num, $label]): ?>
  <div class="col-6 col-md-4 col-xl-3"><div class="stat-card">
    <span class="stat-icon purple"><i class="bi <?= $icon ?>"></i></span>
    <div><div class="stat-value"><?= is_numeric($num) ? number_format((float) $num) : e($num) ?></div><div class="stat-name"><?= e($label) ?></div></div>
  </div></div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Revenue by month (paid payments)</h5><a href="<?= url('super/analytics') ?>">Analytics</a></div>
      <canvas id="revChart" height="110"></canvas>
    </div>
    <div class="panel p-0">
      <div class="panel-head px-4 pt-4"><h5>Latest tenants</h5><a href="<?= url('super/tenants') ?>">All tenants</a></div>
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Company</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($recentTenants as $t): ?>
        <tr><td><strong><?= e($t['name']) ?></strong><small class="d-block text-muted"><?= e($t['email']) ?> · /<?= e($t['slug']) ?></small></td>
          <td><span class="badge text-bg-<?= $t['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($t['status']) ?></span></td>
          <td><?= e(substr((string) $t['created_at'], 0, 10)) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Subscriptions by plan</h5></div>
      <canvas id="mixChart" height="200"></canvas>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Recent payments</h5><a href="<?= url('super/payments') ?>">All payments</a></div>
      <?php foreach ($recentPayments as $p): ?>
      <div class="d-flex justify-content-between border-bottom py-2 small">
        <span><?= e($p['tenant_name']) ?><br><span class="text-muted"><code><?= e($p['reference']) ?></code></span></span>
        <span class="text-end">$<?= number_format((float) $p['amount'], 2) ?><br>
        <span class="badge text-bg-<?= $p['status'] === 'paid' ? 'success' : ($p['status'] === 'failed' ? 'danger' : 'warning') ?>"><?= e($p['status']) ?></span></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$recentPayments): ?><p class="text-muted mb-0 small">No payments yet.</p><?php endif; ?>
      <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i><?= (int) $stats['open_tickets'] ?> open support ticket(s) need attention.</div>
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
