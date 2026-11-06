<?php $pageTitle = 'Categories, Tags & Departments'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel h-100"><div class="panel-head"><h5><i class="bi bi-bookmark me-1"></i>Categories</h5></div>
      <?php foreach ($categories as $c): ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <span><span class="badge me-1" style="background:<?= e($c['color']) ?>">&nbsp;</span><?= e($c['name']) ?> <span class="text-muted small"><?= (int) $c['docs'] ?> docs</span></span>
        <form method="post" action="<?= url('admin/categories/category') ?>" onsubmit="return confirm('Delete this category? Documents keep existing, uncategorised.')"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button></form></div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('admin/categories/category') ?>" class="d-flex gap-2 mt-3">
        <?= csrf_field() ?><input class="form-control" name="name" placeholder="New category" required>
        <input type="color" class="form-control form-control-color" name="color" value="#1e3a5f">
        <button class="btn btn-purple">Add</button></form>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel h-100"><div class="panel-head"><h5><i class="bi bi-tag me-1"></i>Tags</h5></div>
      <?php foreach ($tags as $t): ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <span><span class="tag-pill"><i class="bi bi-tag me-1"></i><?= e($t['name']) ?></span> <span class="text-muted small"><?= (int) $t['docs'] ?> docs</span></span>
        <form method="post" action="<?= url('admin/categories/tag') ?>" onsubmit="return confirm('Delete this tag?')"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button></form></div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('admin/categories/tag') ?>" class="d-flex gap-2 mt-3">
        <?= csrf_field() ?><input class="form-control" name="name" placeholder="New tag" required>
        <input type="color" class="form-control form-control-color" name="color" value="#6d28d9">
        <button class="btn btn-purple">Add</button></form>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel h-100"><div class="panel-head"><h5><i class="bi bi-diagram-3 me-1"></i>Departments</h5></div>
      <?php foreach ($departments as $dep): ?>
      <div class="border-bottom py-2">
        <div class="d-flex justify-content-between align-items-center">
          <span><span class="badge me-1" style="background:<?= e($dep['color']) ?>">&nbsp;</span><strong><?= e($dep['name']) ?></strong>
            <span class="text-muted small"><?= (int) $dep['docs'] ?> docs<?= $dep['head_name'] ? ' · head: ' . e($dep['head_name']) : '' ?></span></span>
          <form method="post" action="<?= url('admin/departments') ?>" onsubmit="return confirm('Delete this department? Documents keep existing; department is cleared.')"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $dep['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button></form>
        </div>
        <form method="post" action="<?= url('admin/departments') ?>" class="d-flex gap-2 mt-2">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $dep['id'] ?>">
          <input class="form-control form-control-sm" name="name" value="<?= e($dep['name']) ?>">
          <select class="form-select form-select-sm" name="head_id"><option value="">No head</option>
            <?php foreach ($tenantUsers as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) ($dep['head_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
          <input type="color" class="form-control form-control-color form-control-sm" name="color" value="<?= e($dep['color']) ?>">
          <button class="btn btn-sm btn-outline-navy">Save</button></form>
      </div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('admin/departments') ?>" class="mt-3">
        <?= csrf_field() ?>
        <label class="form-label small">New department</label>
        <div class="d-flex gap-2"><input class="form-control" name="name" placeholder="e.g. Finance" required>
        <input type="color" class="form-control form-control-color" name="color" value="#6d28d9">
        <button class="btn btn-purple">Add</button></div>
      </form>
      <p class="small text-muted mt-3 mb-0">Departments drive document metadata, filters and per-department approval workflows.</p>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
