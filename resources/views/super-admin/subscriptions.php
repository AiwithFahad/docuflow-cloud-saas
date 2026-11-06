<?php $pageTitle = 'Subscriptions'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-sm <?= $status === '' ? 'btn-purple' : 'btn-outline-secondary' ?>" href="<?= url('super-admin/subscriptions') ?>">All</a>
  <?php foreach ($counts as $c): ?>
  <a class="btn btn-sm <?= $status === $c['status'] ? 'btn-purple' : 'btn-outline-secondary' ?>" href="<?= url('super-admin/subscriptions?status=' . e($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?> (<?= (int) $c['total'] ?>)</a>
  <?php endforeach; ?>
</div>
<div class="panel"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Organization</th><th>Plan</th><th>Cycle</th><th>Status</th><th>Effective</th><th>Period</th><th>Created</th></tr></thead><tbody>
  <?php foreach ($subs as $s): ?>
  <tr><td><strong><?= e($s['tenant_name']) ?></strong></td><td><?= e($s['plan_name']) ?></td><td><?= e($s['billing_cycle']) ?></td>
    <td><span class="badge text-bg-<?= $s['status'] === 'active' ? 'success' : ($s['status'] === 'trialing' ? 'info' : 'secondary') ?>"><?= e($s['status']) ?></span></td>
    <td><span class="badge text-bg-<?= $s['effective'] === 'active' ? 'success' : ($s['effective'] === 'grace' ? 'warning' : 'danger') ?>"><?= e($s['effective']) ?></span></td>
    <td class="small"><?= e(substr((string) $s['current_period_start'], 0, 10)) ?> → <?= e(substr((string) $s['current_period_end'], 0, 10)) ?></td>
    <td class="small"><?= e(substr((string) $s['created_at'], 0, 10)) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$subs): ?><tr><td colspan="7" class="text-muted">No subscriptions match.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<p class="small text-muted mt-2">Effective status applies expiry + the 7-day grace window (SubscriptionService). Plans change only through the tenant subscription page, and paid changes activate solely after verified payment.</p>
<?php partial('app-close'); ?>
