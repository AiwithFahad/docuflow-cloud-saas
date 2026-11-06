<?php $pageTitle = 'Categories & Tags'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-folder-symlink me-1"></i>Categories</h5></div>
      <form method="post" action="<?= url('app/taxonomy/category') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <input class="form-control" name="name" placeholder="New category name" required>
        <input type="color" class="form-control form-control-color" name="color" value="#1e3a5f">
        <button class="btn btn-purple">Add</button>
      </form>
      <?php foreach ($categories as $c): ?>
      <div class="d-flex align-items-center gap-2 border-bottom py-2">
        <span class="dot" style="background:<?= e($c['color'] ?: '#1e3a5f') ?>"></span>
        <form method="post" action="<?= url('app/taxonomy/category') ?>" class="d-flex gap-2 flex-grow-1">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <input class="form-control form-control-sm" name="name" value="<?= e($c['name']) ?>">
          <button class="btn btn-sm btn-outline-secondary">Save</button>
        </form>
        <span class="badge text-bg-light"><?= (int) $c['docs'] ?> docs</span>
        <form method="post" action="<?= url('app/taxonomy/category') ?>" onsubmit="return confirm('Delete category? Documents become uncategorized.')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="delete">
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-tags me-1"></i>Tags</h5></div>
      <form method="post" action="<?= url('app/taxonomy/tag') ?>" class="d-flex gap-2 mb-3">
        <?= csrf_field() ?>
        <input class="form-control" name="name" placeholder="New tag name" required>
        <input type="color" class="form-control form-control-color" name="color" value="#6d28d9">
        <button class="btn btn-purple">Add</button>
      </form>
      <div class="d-flex flex-wrap gap-2">
      <?php foreach ($tags as $t): ?>
        <span class="tag-pill">#<?= e($t['name']) ?> <span class="badge text-bg-light"><?= (int) $t['docs'] ?></span>
          <form method="post" action="<?= url('app/taxonomy/tag') ?>" class="d-inline" onsubmit="return confirm('Delete tag?')">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-link text-danger p-0 ms-1"><i class="bi bi-x-circle"></i></button></form>
        </span>
      <?php endforeach; ?>
      </div>
      <?php if (!$tags): ?><p class="text-muted mb-0 mt-2">No tags yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
