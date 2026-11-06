<?php $pageTitle = 'Activity Logs'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="panel p-0">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>When</th><th>Tenant</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
    <tr><td class="small"><?= e(substr((string) $l['created_at'], 0, 16)) ?></td>
      <td><?= e($l['tenant_name'] ?? '—') ?></td><td><?= e($l['user_name'] ?? 'system') ?></td>
      <td><span class="badge text-bg-light"><?= e($l['action']) ?></span></td>
      <td class="small text-muted"><?= e($l['description']) ?></td><td class="small"><?= e($l['ip_address'] ?? '—') ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No activity yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php partial('app-close'); ?>
