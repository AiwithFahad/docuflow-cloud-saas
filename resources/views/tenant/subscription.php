<?php $pageTitle = 'Subscription'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<?php partial('flash'); ?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Current plan</h5>
        <?php if ($sub): ?><span class="badge text-bg-<?= $effective === 'active' || $effective === 'trialing' ? 'success' : ($effective === 'past_due' ? 'warning' : 'danger') ?>"><?= e($effective) ?></span><?php endif; ?></div>
      <?php if ($sub): ?>
      <div class="display-6 fw-bold"><?= e($sub['plan_name']) ?></div>
      <p class="text-muted mb-1">$<?= e($sub['billing_cycle'] === 'yearly' ? $sub['price_yearly'] : $sub['price_monthly']) ?> / <?= e($sub['billing_cycle']) ?>
      <?php if ($sub['status'] === 'trialing'): ?><br>Trial ends <?= e(substr((string) $sub['trial_ends_at'], 0, 10)) ?><?php endif; ?>
      <br>Current period ends <?= e(substr((string) $sub['current_period_end'], 0, 10)) ?></p>
      <?php if ($effective === 'past_due'): ?><div class="alert alert-warning small">Your period ended — you are in the 7-day grace period. Renew to avoid expiry.</div><?php endif; ?>
      <div class="d-flex gap-2">
        <form method="post" action="<?= url('app/subscription/renew') ?>"><?= csrf_field() ?><button class="btn btn-purple">Renew</button></form>
        <form method="post" action="<?= url('app/subscription/cancel') ?>" onsubmit="return confirm('Cancel subscription at period end?')"><?= csrf_field() ?><button class="btn btn-outline-danger">Cancel</button></form>
      </div>
      <?php else: ?><p class="text-muted">No subscription found.</p><?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Usage</h5></div>
      <?php $rows = [
        ['Storage', human_bytes($usage['storage']), $sub && (int) $sub['storage_mb'] >= 0 ? human_bytes((int) $sub['storage_mb'] * 1024 * 1024) : '∞', $sub && (int) $sub['storage_mb'] > 0 ? min(100, $usage['storage'] / ((int) $sub['storage_mb'] * 1024 * 1024) * 100) : 0],
        ['Users', (string) $usage['users'], $sub && (int) $sub['max_users'] >= 0 ? (string) (int) $sub['max_users'] : '∞', $sub && (int) $sub['max_users'] > 0 ? min(100, $usage['users'] / (int) $sub['max_users'] * 100) : 0],
        ['Teams', (string) $usage['teams'], $sub && (int) $sub['max_teams'] >= 0 ? (string) (int) $sub['max_teams'] : '∞', $sub && (int) $sub['max_teams'] > 0 ? min(100, $usage['teams'] / (int) $sub['max_teams'] * 100) : 0],
        ['Documents', (string) $usage['documents'], '∞', 0],
      ]; foreach ($rows as [$label, $used, $cap, $pct]): ?>
      <div class="mb-3"><div class="d-flex justify-content-between small"><span><?= e($label) ?></span><span><?= e($used) ?> / <?= e($cap) ?></span></div>
        <div class="progress" style="height:8px"><div class="progress-bar bg-purple" style="width:<?= round((float) $pct, 1) ?>%"></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Change plan</h5><span class="text-muted small">Paid changes create an invoice; activation follows verified payment.</span></div>
      <form method="post" action="<?= url('app/subscription/change') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
          <?php foreach ($plans as $p): ?>
          <div class="col-md-6">
            <label class="plan-pick w-100">
              <input type="radio" name="plan_id" value="<?= (int) $p['id'] ?>" <?= $sub && (int) $sub['plan_id'] === (int) $p['id'] ? 'checked' : '' ?>>
              <span><strong><?= e($p['name']) ?></strong> <?= $sub && (int) $sub['plan_id'] === (int) $p['id'] ? '<span class="badge text-bg-success">current</span>' : '' ?><br>
              <span class="fs-5">$<?= e($p['price_monthly']) ?></span><small class="text-muted">/mo · $<?= e($p['price_yearly']) ?>/yr</small><br>
              <small class="text-muted"><?= (int) $p['storage_mb'] < 0 ? 'Unlimited storage' : human_bytes((int) $p['storage_mb'] * 1024 * 1024) ?> · <?= (int) $p['max_users'] < 0 ? 'Unlimited users' : (int) $p['max_users'] . ' users' ?> · <?= (int) $p['version_history'] ?> versions</small></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4"><label class="form-label">Billing cycle</label>
            <select class="form-select" name="billing_cycle"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
          <div class="col-md-4"><label class="form-label">Coupon code</label>
            <input class="form-control" name="coupon" placeholder="WELCOME10"></div>
          <div class="col-md-4 d-flex align-items-end"><button class="btn btn-purple w-100">Change plan</button></div>
        </div>
      </form>
    </div>

    <div class="panel mb-4 p-0">
      <div class="panel-head px-4 pt-4"><h5>Invoices</h5></div>
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Number</th><th>Description</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $i): ?>
        <tr><td><?= e($i['number']) ?></td><td><?= e($i['description']) ?></td><td>$<?= number_format((float) $i['total'], 2) ?></td>
          <td><span class="badge text-bg-<?= $i['status'] === 'paid' ? 'success' : 'warning' ?>"><?= e($i['status']) ?></span></td>
          <td><?= e(substr((string) $i['created_at'], 0, 10)) ?></td>
          <td><a class="btn btn-sm btn-outline-secondary" href="<?= url('app/invoices/' . (int) $i['id']) ?>">View / PDF</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?><tr><td colspan="6" class="text-muted text-center py-3">No invoices yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="panel p-0">
      <div class="panel-head px-4 pt-4"><h5>Payment history</h5><span class="text-muted small">Pending = recorded, awaiting verification</span></div>
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Reference</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $pay): ?>
        <tr><td><code><?= e($pay['reference']) ?></code></td><td><?= e($pay['method']) ?></td><td>$<?= number_format((float) $pay['amount'], 2) ?></td>
          <td><span class="badge text-bg-<?= $pay['status'] === 'paid' ? 'success' : ($pay['status'] === 'failed' ? 'danger' : 'warning') ?>"><?= e($pay['status']) ?></span></td>
          <td><?= e(substr((string) $pay['created_at'], 0, 10)) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="5" class="text-muted text-center py-3">No payments yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
