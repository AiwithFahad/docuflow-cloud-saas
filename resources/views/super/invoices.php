<?php $pageTitle = 'Invoices'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="panel p-0">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Number</th><th>Tenant</th><th>Description</th><th>Amount</th><th>Discount</th><th>Total</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $i): ?>
    <tr><td><?= e($i['number']) ?></td><td><?= e($i['tenant_name']) ?></td><td><?= e($i['description']) ?></td>
      <td>$<?= number_format((float) $i['amount'], 2) ?></td><td>$<?= number_format((float) $i['discount'], 2) ?></td>
      <td><strong>$<?= number_format((float) $i['total'], 2) ?></strong></td>
      <td><span class="badge text-bg-<?= $i['status'] === 'paid' ? 'success' : 'warning' ?>"><?= e($i['status']) ?></span></td>
      <td class="small"><?= e(substr((string) $i['created_at'], 0, 10)) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$invoices): ?><tr><td colspan="8" class="text-center text-muted py-4">No invoices yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php partial('app-close'); ?>
