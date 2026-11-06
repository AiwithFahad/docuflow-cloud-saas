<?php $pageTitle = 'Organization Settings'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row"><div class="col-lg-7">
<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-gear me-1"></i>Organization settings</h5></div>
  <form method="post" enctype="multipart/form-data" action="<?= url('admin/settings') ?>">
    <?= csrf_field() ?>
    <label class="form-label">Organization name</label>
    <input class="form-control mb-3" name="company_name" value="<?= e($tenant['name']) ?>" required>
    <label class="form-label">Brand colour</label>
    <input type="color" class="form-control form-control-color mb-3" name="brand_color" value="<?= e($tenant['brand_color'] ?? '#1e3a5f') ?>">
    <label class="form-label">Logo (png/jpg/webp, max 2 MB)</label>
    <input type="file" class="form-control mb-3" name="logo" accept="image/png,image/jpeg,image/webp">
    <hr>
    <label class="form-label">Default share link lifetime (days)</label>
    <input type="number" class="form-control mb-3" name="share_link_days" min="1" max="365" value="<?= (int) ($settings['share_link_days'] ?? 7) ?>">
    <label class="form-check d-block mb-2"><input type="checkbox" class="form-check-input" name="allow_public_shares" value="1" <?= !empty($settings['allow_public_shares']) ? 'checked' : '' ?>> Allow public share links</label>
    <label class="form-check d-block mb-4"><input type="checkbox" class="form-check-input" name="default_approval_required" value="1" <?= !empty($settings['default_approval_required']) ? 'checked' : '' ?>> Documents need approval by default</label>
    <button class="btn btn-purple">Save settings</button>
  </form>
  <hr>
  <p class="small text-muted mb-0">Subscription, invoices and plan changes live under <a href="<?= url('app/subscription') ?>">Subscription</a>. Paid plan changes activate only after verified payment — never automatically.</p>
</div>
</div></div>
<?php partial('app-close'); ?>
