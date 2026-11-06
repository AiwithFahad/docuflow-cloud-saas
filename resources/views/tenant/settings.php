<?php $pageTitle = 'Settings'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row justify-content-center"><div class="col-lg-8">
  <div class="panel">
    <h4 class="mb-4">Company settings</h4>
    <form method="post" action="<?= url('app/settings') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label class="form-label">Company name</label>
      <input class="form-control mb-3" name="company_name" value="<?= e($tenant['name']) ?>" required>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Brand color</label>
          <input type="color" class="form-control form-control-color w-100" name="brand_color" value="<?= e($tenant['brand_color'] ?? '#1e3a5f') ?>"></div>
        <div class="col-md-6"><label class="form-label">Company logo</label>
          <input type="file" class="form-control" name="logo" accept=".png,.jpg,.jpeg,.webp">
          <?php if (!empty($tenant['logo'])): ?><small class="text-muted">Current: <?= e($tenant['logo']) ?></small><?php endif; ?></div>
      </div>
      <hr>
      <h5>Document defaults</h5>
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="default_approval_required" value="1" id="sw1" <?= !empty($settings['default_approval_required']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="sw1">Documents require approval by default</label>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="allow_public_shares" value="1" id="sw2" <?= !empty($settings['allow_public_shares']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="sw2">Allow public share links</label>
      </div>
      <label class="form-label">Default share link lifetime (days)</label>
      <input type="number" class="form-control mb-4" name="share_link_days" min="1" max="365" value="<?= (int) ($settings['share_link_days'] ?? 7) ?>">
      <div class="alert alert-light border small">
        <strong>Workspace:</strong> <?= e($tenant['name']) ?> · slug <code><?= e($tenant['slug']) ?></code> · status <?= e($tenant['status']) ?> ·
        member since <?= e(substr((string) $tenant['created_at'], 0, 10)) ?>
      </div>
      <button class="btn btn-purple btn-lg">Save settings</button>
    </form>
  </div>
</div></div>
<?php partial('app-close'); ?>
