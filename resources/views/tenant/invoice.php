<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice <?= e($invoice['number']) ?> — DocuFlow Cloud</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f5fa}.sheet{background:#fff;max-width:760px;margin:32px auto;padding:40px;border-radius:16px;box-shadow:0 10px 40px rgba(20,36,62,.08)}
@media print{body{background:#fff}.sheet{box-shadow:none;margin:0}.no-print{display:none}}</style></head>
<body>
<div class="sheet">
  <div class="d-flex justify-content-between align-items-start">
    <div><h3 style="color:#1e3a5f">DocuFlow <span style="color:#6d28d9">Cloud</span></h3><p class="text-muted mb-0">Document Management SaaS</p></div>
    <div class="text-end"><h4>INVOICE</h4><div><?= e($invoice['number']) ?></div>
      <span class="badge text-bg-<?= $invoice['status'] === 'paid' ? 'success' : 'warning' ?>"><?= strtoupper(e($invoice['status'])) ?></span></div>
  </div>
  <hr>
  <div class="row">
    <div class="col-6"><strong>Billed to</strong><br><?= e($invoice['tenant_name']) ?><br><span class="text-muted"><?= e($invoice['tenant_email']) ?></span></div>
    <div class="col-6 text-end"><strong>Issued</strong><br><?= e(substr((string) $invoice['created_at'], 0, 10)) ?>
      <?php if ($invoice['paid_at']): ?><br><strong>Paid</strong><br><?= e(substr((string) $invoice['paid_at'], 0, 10)) ?><?php endif; ?></div>
  </div>
  <table class="table mt-4">
    <thead><tr><th>Description</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
      <tr><td><?= e($invoice['description']) ?></td><td class="text-end">$<?= number_format((float) $invoice['amount'], 2) ?></td></tr>
      <?php if ((float) $invoice['discount'] > 0): ?><tr><td>Discount (coupon)</td><td class="text-end">−$<?= number_format((float) $invoice['discount'], 2) ?></td></tr><?php endif; ?>
      <tr class="fw-bold"><td>Total</td><td class="text-end">$<?= number_format((float) $invoice['total'], 2) ?> USD</td></tr>
    </tbody>
  </table>
  <?php if ($invoice['status'] !== 'paid'): ?>
  <div class="alert alert-warning">This invoice is <strong>unpaid</strong>. The related subscription activates only after the payment is verified — DocuFlow never marks payments successful without verified confirmation.</div>
  <?php endif; ?>
  <div class="d-flex gap-2 no-print mt-4">
    <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
    <a class="btn btn-outline-secondary" href="<?= url('app/subscription') ?>">Back to subscription</a>
  </div>
</div>
</body></html>
