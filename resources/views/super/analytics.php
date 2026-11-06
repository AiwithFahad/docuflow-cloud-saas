<?php $pageTitle = 'Usage Analytics'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Tenant signups by month</h5></div>
      <canvas id="signupChart" height="150"></canvas>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Subscriptions by status</h5></div>
      <?php foreach ($subsByStatus as $s): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span class="text-capitalize"><?= e($s['status']) ?></span><strong><?= (int) $s['total'] ?></strong></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-head"><h5>Storage by tenant (top 10)</h5></div>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>Tenant</th><th>Documents</th><th>Storage</th></tr></thead><tbody>
      <?php foreach ($storageByTenant as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><?= (int) $r['docs'] ?></td><td><?= human_bytes($r['bytes']) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const c = document.getElementById('signupChart');
  if (c) new Chart(c, {type:'line', data:{labels: <?= json_encode(array_reverse(array_column($signups, 'label'))) ?>,
    datasets:[{label:'New tenants', data: <?= json_encode(array_reverse(array_map('intval', array_column($signups, 'total')))) ?>, borderColor:'#1e3a5f', backgroundColor:'rgba(30,58,95,.1)', fill:true, tension:.35}]},
    options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{precision:0}}}}});
});
</script>
<?php partial('app-close'); ?>
