<?php $pageTitle = 'Platform Settings'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="row"><div class="col-lg-7">
<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-gear me-1"></i>Platform settings</h5></div>
  <form method="post" action="<?= url('super-admin/settings') ?>">
    <?= csrf_field() ?>
    <?php foreach ($settings as $s): ?>
    <div class="mb-3"><label class="form-label"><?= e(ucwords(str_replace('_', ' ', $s['setting_key']))) ?> <code class="small"><?= e($s['setting_key']) ?></code></label>
      <input class="form-control" name="settings[<?= e($s['setting_key']) ?>]" value="<?= e($s['setting_value'] ?? '') ?>"></div>
    <?php endforeach; ?>
    <button class="btn btn-purple">Save settings</button>
  </form>
  <hr>
  <p class="small text-muted mb-0">Announcements, support tickets and invoices are managed in the <a href="<?= url('super') ?>">classic console</a>. Payment gateways are configured via environment variables — never stored in code or in this panel.</p>
</div>
</div></div>
<?php partial('app-close'); ?>
