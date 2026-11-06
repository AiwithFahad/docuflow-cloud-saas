<?php $pageTitle = 'Activity Timeline'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-activity me-1"></i>Activity timeline</h5><span class="text-muted small"><?= number_format($total) ?> events</span></div>
  <?php foreach ($logs as $l): ?>
  <div class="activity-item">
    <i class="bi bi-dot"></i>
    <div><strong><?= e($l['action']) ?></strong> <span class="text-muted">by <?= e($l['user_name'] ?? 'system') ?></span>
      <small class="d-block text-muted"><?= e($l['description']) ?> · <?= e($l['created_at']) ?> · IP <?= e($l['ip_address'] ?? '—') ?></small></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$logs): ?><p class="text-muted mb-0">No activity recorded yet.</p><?php endif; ?>
</div>
<?php if ($pg['pages'] > 1): ?>
<nav class="mt-3"><ul class="pagination">
  <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
  <li class="page-item <?= $i === $pg['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= url('app/activity?page=' . $i) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php partial('app-close'); ?>
