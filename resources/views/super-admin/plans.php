<?php $pageTitle = 'Plans & Coupons'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="panel mb-4">
  <div class="panel-head"><h5><i class="bi bi-layers me-1"></i>Subscription plans</h5><span class="text-muted small">Prompt tiers map onto these plans: Free · Starter · Professional · Business · Enterprise</span></div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Plan</th><th>Monthly</th><th>Yearly</th><th>Storage</th><th>Users</th><th>File MB</th><th>Teams</th><th>Versions</th><th>Active</th><th></th></tr></thead><tbody>
    <?php foreach ($plans as $p): ?>
    <tr><td><strong><?= e($p['name']) ?></strong><div class="small text-muted"><?= e($p['slug']) ?></div></td>
      <td>$<?= number_format((float) $p['price_monthly'], 2) ?></td><td>$<?= number_format((float) $p['price_yearly'], 2) ?></td>
      <td><?= (int) $p['storage_mb'] < 0 ? 'Unlimited' : number_format((int) $p['storage_mb']) . ' MB' ?></td>
      <td><?= (int) $p['max_users'] < 0 ? '∞' : (int) $p['max_users'] ?></td><td><?= (int) $p['max_file_mb'] < 0 ? '∞' : (int) $p['max_file_mb'] ?></td>
      <td><?= (int) $p['max_teams'] < 0 ? '∞' : (int) $p['max_teams'] ?></td><td><?= (int) $p['version_history'] ?></td>
      <td><?= $p['is_active'] ? '<span class="badge text-bg-success">yes</span>' : '<span class="badge text-bg-secondary">no</span>' ?></td>
      <td><button class="btn btn-sm btn-outline-navy" data-bs-toggle="collapse" data-bs-target="#planForm<?= (int) $p['id'] ?>">Edit</button></td></tr>
    <tr class="collapse" id="planForm<?= (int) $p['id'] ?>"><td colspan="10">
      <form method="post" action="<?= url('super-admin/plans/save') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <div class="col-md-2"><label class="form-label small">Name</label><input class="form-control" name="name" value="<?= e($p['name']) ?>" required></div>
        <div class="col-md-1"><label class="form-label small">Monthly $</label><input class="form-control" type="number" step="0.01" name="price_monthly" value="<?= e((string) $p['price_monthly']) ?>"></div>
        <div class="col-md-1"><label class="form-label small">Yearly $</label><input class="form-control" type="number" step="0.01" name="price_yearly" value="<?= e((string) $p['price_yearly']) ?>"></div>
        <div class="col-md-1"><label class="form-label small">Storage MB</label><input class="form-control" type="number" name="storage_mb" value="<?= (int) $p['storage_mb'] ?>"></div>
        <div class="col-md-1"><label class="form-label small">Users</label><input class="form-control" type="number" name="max_users" value="<?= (int) $p['max_users'] ?>"></div>
        <div class="col-md-1"><label class="form-label small">File MB</label><input class="form-control" type="number" name="max_file_mb" value="<?= (int) $p['max_file_mb'] ?>"></div>
        <div class="col-md-1"><label class="form-label small">Teams</label><input class="form-control" type="number" name="max_teams" value="<?= (int) $p['max_teams'] ?>"></div>
        <div class="col-md-1"><label class="form-label small">Versions</label><input class="form-control" type="number" name="version_history" value="<?= (int) $p['version_history'] ?>"></div>
        <div class="col-md-2"><label class="form-label small">Features (comma list)</label><input class="form-control" name="features" value="<?= e($p['features'] ?? '') ?>"></div>
        <div class="col-md-1"><label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $p['is_active'] ? 'checked' : '' ?>> Active</label>
        <button class="btn btn-purple w-100 mt-1">Save</button></div>
      </form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="p-3 border-top">
    <strong class="d-block mb-2">Create plan</strong>
    <form method="post" action="<?= url('super-admin/plans/save') ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-2"><label class="form-label small">Name</label><input class="form-control" name="name" required></div>
      <div class="col-md-1"><label class="form-label small">Monthly $</label><input class="form-control" type="number" step="0.01" name="price_monthly" value="0"></div>
      <div class="col-md-1"><label class="form-label small">Yearly $</label><input class="form-control" type="number" step="0.01" name="price_yearly" value="0"></div>
      <div class="col-md-1"><label class="form-label small">Storage MB</label><input class="form-control" type="number" name="storage_mb" value="1024"></div>
      <div class="col-md-1"><label class="form-label small">Users</label><input class="form-control" type="number" name="max_users" value="5"></div>
      <div class="col-md-1"><label class="form-label small">File MB</label><input class="form-control" type="number" name="max_file_mb" value="25"></div>
      <div class="col-md-1"><label class="form-label small">Teams</label><input class="form-control" type="number" name="max_teams" value="3"></div>
      <div class="col-md-1"><label class="form-label small">Versions</label><input class="form-control" type="number" name="version_history" value="10"></div>
      <div class="col-md-2"><label class="form-label small">Features</label><input class="form-control" name="features"></div>
      <div class="col-md-1"><input type="hidden" name="is_active" value="1"><button class="btn btn-outline-navy w-100">Create</button></div>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-ticket-perforated me-1"></i>Coupons</h5></div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Used / Max</th><th>Expires</th><th>Active</th><th></th></tr></thead><tbody>
    <?php foreach ($coupons as $c): ?>
    <tr><td><code><?= e($c['code']) ?></code></td><td><?= e($c['type']) ?></td>
      <td><?= $c['type'] === 'percent' ? e((string) $c['value']) . '%' : '$' . e((string) $c['value']) ?></td>
      <td><?= (int) $c['uses'] ?> / <?= $c['max_uses'] ? (int) $c['max_uses'] : '∞' ?></td>
      <td><?= $c['expires_at'] ? e(substr((string) $c['expires_at'], 0, 10)) : 'never' ?></td>
      <td><?= $c['is_active'] ? '<span class="badge text-bg-success">active</span>' : '<span class="badge text-bg-secondary">off</span>' ?></td>
      <td><form method="post" action="<?= url('super-admin/coupons/save') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <button class="btn btn-sm btn-outline-secondary" name="action" value="toggle">Toggle</button>
        <button class="btn btn-sm btn-outline-danger" name="action" value="delete" onclick="return confirm('Delete this coupon?')">Delete</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="p-3 border-top">
    <form method="post" action="<?= url('super-admin/coupons/save') ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-3"><label class="form-label small">Code</label><input class="form-control" name="code" placeholder="e.g. SAVE20" required></div>
      <div class="col-md-2"><label class="form-label small">Type</label><select class="form-select" name="type"><option value="percent">Percent</option><option value="fixed">Fixed $</option></select></div>
      <div class="col-md-2"><label class="form-label small">Value</label><input class="form-control" type="number" step="0.01" name="value" required></div>
      <div class="col-md-2"><label class="form-label small">Max uses</label><input class="form-control" type="number" name="max_uses" placeholder="unlimited"></div>
      <div class="col-md-2"><label class="form-label small">Expires</label><input class="form-control" type="date" name="expires_at"></div>
      <div class="col-md-1"><button class="btn btn-purple w-100">Add</button></div>
    </form>
  </div>
</div>
<?php partial('app-close'); ?>
