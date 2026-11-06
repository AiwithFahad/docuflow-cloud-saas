<?php $pageTitle = 'Folders'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel"><div class="panel-head"><h5><i class="bi bi-folder me-1"></i>All folders</h5></div>
      <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Folder</th><th>Parent</th><th>Files</th><th>Subfolders</th><th>Created by</th><th></th></tr></thead><tbody>
        <?php foreach ($folders as $f): ?>
        <tr><td><i class="bi bi-folder-fill me-1" style="color:<?= e($f['color']) ?>"></i><strong><?= e($f['name']) ?></strong>
            <?php if ($f['description']): ?><div class="small text-muted"><?= e($f['description']) ?></div><?php endif; ?></td>
          <td><?= e($f['parent_name'] ?? 'Root') ?></td><td><?= (int) $f['docs'] ?></td><td><?= (int) $f['subs'] ?></td><td><?= e($f['creator'] ?? '—') ?></td>
          <td><button class="btn btn-sm btn-outline-navy" data-bs-toggle="collapse" data-bs-target="#folderEdit<?= (int) $f['id'] ?>">Manage</button></td></tr>
        <tr class="collapse" id="folderEdit<?= (int) $f['id'] ?>"><td colspan="6">
          <div class="d-flex flex-wrap gap-3">
            <form method="post" action="<?= url('admin/folders') ?>" class="d-flex gap-2 align-items-end">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="rename">
              <span><label class="form-label small">Name</label><input class="form-control form-control-sm" name="name" value="<?= e($f['name']) ?>"></span>
              <span><label class="form-label small">Colour</label><input type="color" class="form-control form-control-color form-control-sm" name="color" value="<?= e($f['color']) ?>"></span>
              <span><label class="form-label small">Description</label><input class="form-control form-control-sm" name="description" value="<?= e($f['description'] ?? '') ?>"></span>
              <button class="btn btn-sm btn-purple">Save</button></form>
            <form method="post" action="<?= url('admin/folders') ?>" class="d-flex gap-2 align-items-end">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="move">
              <span><label class="form-label small">Move to</label><select class="form-select form-select-sm" name="parent_id"><option value="">Root</option>
                <?php foreach ($folderTree as $tf): if ((int) $tf['id'] === (int) $f['id']) continue; ?><option value="<?= (int) $tf['id'] ?>" <?= (int) ($f['parent_id'] ?? 0) === (int) $tf['id'] ? 'selected' : '' ?>><?= str_repeat('— ', (int) $tf['_depth']) ?><?= e($tf['name']) ?></option><?php endforeach; ?></select></span>
              <button class="btn btn-sm btn-outline-navy">Move</button></form>
            <form method="post" action="<?= url('admin/folders') ?>" onsubmit="return confirm('Delete this folder? It must be empty.')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-outline-danger">Delete</button></form>
          </div></td></tr>
        <?php endforeach; ?>
        <?php if (!$folders): ?><tr><td colspan="6" class="text-muted">No folders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel"><div class="panel-head"><h5>Create folder</h5></div>
      <form method="post" action="<?= url('admin/folders') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label class="form-label">Name</label><input class="form-control mb-3" name="name" required>
        <label class="form-label">Parent folder</label>
        <select class="form-select mb-3" name="parent_id"><option value="">Root</option>
          <?php foreach ($folderTree as $tf): ?><option value="<?= (int) $tf['id'] ?>"><?= str_repeat('— ', (int) $tf['_depth']) ?><?= e($tf['name']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Description</label><input class="form-control mb-3" name="description">
        <label class="form-label">Colour</label><input type="color" class="form-control form-control-color mb-3" name="color" value="#f5b93f">
        <button class="btn btn-purple w-100">Create folder</button>
      </form>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
