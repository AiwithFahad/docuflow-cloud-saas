<?php $pageTitle = 'Payments'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="alert alert-light border small"><i class="bi bi-shield-lock me-1"></i>
  <strong>Verified-only rule:</strong> a payment is marked paid only after verification. “Verify &amp; activate” runs PaymentService::confirmPayment(), which marks the invoice paid and activates the subscription. Nothing here can fake a successful payment.</div>
<div class="panel"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>#</th><th>Organization</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
  <?php foreach ($payments as $p): ?>
  <tr><td><?= (int) $p['id'] ?></td><td><?= e($p['tenant_name']) ?></td><td><?= e($p['invoice_number'] ?? '—') ?></td>
    <td>$<?= number_format((float) $p['amount'], 2) ?></td><td><?= e($p['method']) ?></td><td><code class="small"><?= e($p['reference'] ?? '—') ?></code></td>
    <td><span class="badge text-bg-<?= $p['status'] === 'paid' ? 'success' : ($p['status'] === 'pending' ? 'warning' : ($p['status'] === 'refunded' ? 'info' : 'danger')) ?>"><?= e($p['status']) ?></span></td>
    <td class="small"><?= e(substr((string) ($p['paid_at'] ?? $p['created_at']), 0, 10)) ?></td>
    <td><?php if ($p['status'] === 'pending'): ?>
      <form method="post" action="<?= url('super-admin/payments/' . (int) $p['id'] . '/action') ?>" class="d-flex gap-1"><?= csrf_field() ?>
        <button class="btn btn-sm btn-success" name="action" value="verify" onclick="return confirm('Verify this payment and activate the subscription?')">Verify &amp; activate</button>
        <button class="btn btn-sm btn-outline-danger" name="action" value="fail">Mark failed</button>
      </form><?php endif; ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$payments): ?><tr><td colspan="9" class="text-muted">No payments yet.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php partial('app-close'); ?>
