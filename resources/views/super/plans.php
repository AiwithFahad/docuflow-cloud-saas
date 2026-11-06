<?php $pageTitle = 'Plans & Coupons'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-head"><h5>Subscription plans</h5></div>
      <?php foreach ($plans as $p): ?>
      <form method="post" action="<?= url('super/plans/save') ?>" class="border rounded-3 p-3 mb-3">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-3"><label class="form-label small">Name</label><input class="form-control" name="name" value="<?= e($p['name']) ?>"></div>
          <div class="col-md-2"><label class="form-label small">$/month</label><input type="number" step="0.01" class="form-control" name="price_monthly" value="<?= e($p['price_monthly']) ?>"></div>
          <div class="col-md-2"><label class="form-label small">$/year</label><input type="number" step="0.01" class="form-control" name="price_yearly" value="<?= e($p['price_yearly']) ?>"></div>
          <div class="col-md-2"><label class="form-label small">Storage MB (-1 = ∞)</label><input type="number" class="form-control" name="storage_mb" value="<?= (int) $p['storage_mb'] ?>"></div>
          <div class="col-md-2"><label class="form-label small">Users (-1 = ∞)</label><input type="number" class="form-control" name="max_users" value="<?= (int) $p['max_users'] ?>"></div>
          <div class="col-md-1"><label class="form-label small">File MB</label><input type="number" class="form-control" name="max_file_mb" value="<?= (int) $p['max_file_mb'] ?>"></div>
          <div class="col-md-2"><label class="form-label small">Teams</label><input type="number" class="form-control" name="max_teams" value="<?= (int) $p['max_teams'] ?>"></div>
          <div class="col-md-2"><label class="form-label small">Versions</label><input type="number" class="form-control" name="version_history" value="<?= (int) $p['version_history'] ?>"></div>
          <div class="col-md-5"><label class="form-label small">Features (comma separated)</label><input class="form-control" name="features" value="<?= e($p['features']) ?>"></div>
          <div class="col-md-2"><label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $p['is_active'] ? 'checked' : '' ?>> Active</label></div>
          <div class="col-md-1"><button class="btn btn-purple w-100">Save</button></div>
        </div>
      </form>
      <?php endforeach; ?>
      <details><summary class="fw-semibold">+ Add a new plan</summary>
        <form method="post" action="<?= url('super/plans/save') ?>" class="border rounded-3 p-3 mt-2">
          <?= csrf_field() ?><input type="hidden" name="id" value="0">
          <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Name</label><input class="form-control" name="name" required></div>
            <div class="col-md-2"><label class="form-label small">$/month</label><input type="number" step="0.01" class="form-control" name="price_monthly" value="0"></div>
            <div class="col-md-2"><label class="form-label small">$/year</label><input type="number" step="0.01" class="form-control" name="price_yearly" value="0"></div>
            <div class="col-md-2"><label class="form-label small">Storage MB</label><input type="number" class="form-control" name="storage_mb" value="1024"></div>
            <div class="col-md-2"><label class="form-label small">Users</label><input type="number" class="form-control" name="max_users" value="5"></div>
            <div class="col-md-1"><label class="form-label small">File MB</label><input type="number" class="form-control" name="max_file_mb" value="25"></div>
            <div class="col-md-2"><label class="form-label small">Teams</label><input type="number" class="form-control" name="max_teams" value="3"></div>
            <div class="col-md-2"><label class="form-label small">Versions</label><input type="number" class="form-control" name="version_history" value="10"></div>
            <div class="col-md-5"><label class="form-label small">Features</label><input class="form-control" name="features"></div>
            <div class="col-md-2"><label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" checked> Active</label></div>
            <div class="col-md-1"><button class="btn btn-purple w-100">Add</button></div>
          </div>
        </form>
      </details>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-head"><h5>Coupons</h5></div>
      <form method="post" action="<?= url('super/coupons/save') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <div class="col-4"><input class="form-control" name="code" placeholder="CODE" required></div>
        <div class="col-3"><select class="form-select" name="type"><option value="percent">% off</option><option value="fixed">$ off</option></select></div>
        <div class="col-2"><input type="number" step="0.01" class="form-control" name="value" placeholder="10" required></div>
        <div class="col-3"><button class="btn btn-purple w-100">Create</button></div>
        <div class="col-6"><input type="number" class="form-control" name="max_uses" placeholder="Max uses (blank = ∞)"></div>
        <div class="col-6"><input type="date" class="form-control" name="expires_at"></div>
      </form>
      <?php foreach ($coupons as $c): ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <span><code class="fw-bold"><?= e($c['code']) ?></code>
          <small class="d-block text-muted"><?= $c['type'] === 'percent' ? e($c['value']) . '% off' : '$' . e($c['value']) . ' off' ?> · used <?= (int) $c['uses'] ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?><?= $c['expires_at'] ? ' · expires ' . e(substr((string) $c['expires_at'], 0, 10)) : '' ?></small></span>
        <span class="d-flex gap-1">
          <span class="badge text-bg-<?= $c['is_active'] ? 'success' : 'secondary' ?> align-self-center"><?= $c['is_active'] ? 'active' : 'off' ?></span>
          <form method="post" action="<?= url('super/coupons/save') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-outline-secondary" name="action" value="toggle">Toggle</button></form>
          <form method="post" action="<?= url('super/coupons/save') ?>" onsubmit="return confirm('Delete coupon?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="delete"><i class="bi bi-trash"></i></button></form>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
