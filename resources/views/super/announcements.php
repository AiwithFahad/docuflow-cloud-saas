<?php $pageTitle = 'Announcements'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-head"><h5>New announcement</h5></div>
      <form method="post" action="<?= url('super/announcements/save') ?>">
        <?= csrf_field() ?>
        <label class="form-label">Title</label><input class="form-control mb-3" name="title" required>
        <label class="form-label">Message</label><textarea class="form-control mb-3" name="body" rows="3" required></textarea>
        <label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_active" value="1" checked> Show on the public website</label>
        <button class="btn btn-purple w-100">Publish</button>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-head"><h5>All announcements</h5></div>
      <?php foreach ($announcements as $a): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <span><strong><?= e($a['title']) ?></strong><small class="d-block text-muted"><?= e($a['body']) ?> · <?= e(substr((string) $a['created_at'], 0, 10)) ?></small></span>
        <span class="d-flex gap-2 align-items-start">
          <span class="badge text-bg-<?= $a['is_active'] ? 'success' : 'secondary' ?>"><?= $a['is_active'] ? 'live' : 'hidden' ?></span>
          <form method="post" action="<?= url('super/announcements/save') ?>" onsubmit="return confirm('Delete announcement?')"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </span>
      </div>
      <?php endforeach; ?>
      <?php if (!$announcements): ?><p class="text-muted mb-0">None yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
