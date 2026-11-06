<?php $pageTitle = 'Audit Logs'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="panel p-0">
  <div class="panel-head px-4 pt-4"><h5><i class="bi bi-shield-lock me-1"></i>Audit logs</h5><span class="text-muted small">Immutable record of sensitive changes</span></div>
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>When</th><th>User</th><th>Entity</th><th>Action</th><th>Changes</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td class="small"><?= e(substr((string) $l['created_at'], 0, 16)) ?></td>
        <td><?= e($l['user_name'] ?? 'system') ?></td>
        <td><?= e($l['entity']) ?> #<?= (int) $l['entity_id'] ?></td>
        <td><span class="badge text-bg-light"><?= e($l['action']) ?></span></td>
        <td class="small text-muted">
          <?php if ($l['old_values']): ?>from <code><?= e(substr((string) $l['old_values'], 0, 60)) ?></code> <?php endif; ?>
          <?php if ($l['new_values']): ?>to <code><?= e(substr((string) $l['new_values'], 0, 60)) ?></code><?php endif; ?>
        </td>
        <td class="small"><?= e($l['ip_address'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No audit entries yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php partial('app-close'); ?>
