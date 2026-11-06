<?php $pageTitle = 'Payments'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Payments recorded by tenants start as <strong>pending</strong>. Verify a manual transfer here to mark it paid — verification activates the tenant's subscription and invoice automatically. Nothing is ever auto-marked successful.</div>
<div class="panel p-0">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Reference</th><th>Tenant</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
    <tr>
      <td><code><?= e($p['reference']) ?></code></td>
      <td><?= e($p['tenant_name']) ?></td>
      <td><?= e($p['invoice_number'] ?? '—') ?></td>
      <td><?= e($p['method']) ?></td>
      <td>$<?= number_format((float) $p['amount'], 2) ?></td>
      <td><span class="badge text-bg-<?= $p['status'] === 'paid' ? 'success' : ($p['status'] === 'failed' ? 'danger' : 'warning') ?>"><?= e($p['status']) ?></span>
        <?php if ($p['paid_at']): ?><small class="d-block text-muted"><?= e(substr((string) $p['paid_at'], 0, 10)) ?></small><?php endif; ?></td>
      <td class="small"><?= e(substr((string) $p['created_at'], 0, 10)) ?></td>
      <td>
        <?php if ($p['status'] === 'pending'): ?>
        <form method="post" action="<?= url('super/payments/' . (int) $p['id'] . '/action') ?>" class="d-flex gap-1">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-success" name="action" value="verify" onclick="return confirm('Confirm you verified this payment was received?')">Verify & mark paid</button>
          <button class="btn btn-sm btn-outline-danger" name="action" value="fail">Fail</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="8" class="text-center text-muted py-4">No payments yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php partial('app-close'); ?>
