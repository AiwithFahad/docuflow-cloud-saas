<?php $pageTitle = 'File Manager'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-3">
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-folder2-open me-1"></i> Folders</h5>
        <button class="btn btn-sm btn-purple" data-bs-toggle="modal" data-bs-target="#folderModal"><i class="bi bi-plus-lg"></i></button></div>
      <a class="tree-item <?= !$folder ? 'active' : '' ?>" href="<?= url('app/files') ?>"><i class="bi bi-house me-2"></i>Root</a>
      <?php foreach ($tree as $f): ?>
      <div class="d-flex align-items-center tree-row" style="padding-left:<?= 6 + ((int) $f['_depth'] * 18) ?>px">
        <a class="tree-item flex-grow-1 <?= $folder && (int) $folder['id'] === (int) $f['id'] ? 'active' : '' ?>" href="<?= url('app/files?folder=' . (int) $f['id']) ?>">
          <i class="bi bi-folder-fill me-2" style="color:<?= e($f['color'] ?: '#f5b93f') ?>"></i><?= e($f['name']) ?></a>
        <div class="dropdown"><button class="btn btn-sm btn-light py-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#folderEdit<?= (int) $f['id'] ?>">Rename / move</button></li>
            <li><form method="post" action="<?= url('app/folders/' . (int) $f['id'] . '/update') ?>" onsubmit="return confirm('Delete this folder? It must be empty.')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="dropdown-item text-danger">Delete</button></form></li>
          </ul></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-9">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
      <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= url('app/files') ?>">Files</a></li>
        <?php if ($folder): ?><li class="breadcrumb-item active"><?= e($folder['name']) ?></li><?php endif; ?>
      </ol></nav>
      <div class="d-flex gap-2">
        <div class="btn-group" role="group">
          <a class="btn btn-sm <?= $viewMode === 'grid' ? 'btn-purple' : 'btn-outline-secondary' ?>" href="<?= url('app/files?view=grid' . ($folder ? '&folder=' . (int) $folder['id'] : '')) ?>"><i class="bi bi-grid"></i></a>
          <a class="btn btn-sm <?= $viewMode === 'list' ? 'btn-purple' : 'btn-outline-secondary' ?>" href="<?= url('app/files?view=list' . ($folder ? '&folder=' . (int) $folder['id'] : '')) ?>"><i class="bi bi-list-ul"></i></a>
        </div>
        <a class="btn btn-sm btn-purple" href="<?= url('app/upload' . ($folder ? '?folder=' . (int) $folder['id'] : '')) ?>"><i class="bi bi-cloud-upload me-1"></i>Upload here</a>
      </div>
    </div>

    <?php if ($folders): ?>
    <div class="row g-3 mb-4">
      <?php foreach ($folders as $f): ?>
      <div class="col-6 col-md-4 col-xl-3">
        <a class="folder-card" href="<?= url('app/files?folder=' . (int) $f['id']) ?>">
          <i class="bi bi-folder-fill" style="color:<?= e($f['color'] ?: '#f5b93f') ?>"></i>
          <span><?= e($f['name']) ?></span><small class="text-muted">Folder</small>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$docs && !$folders): ?>
      <div class="panel text-center py-5"><i class="bi bi-cloud display-4 text-muted"></i>
        <h5 class="mt-3">This folder is empty</h5><p class="text-muted">Upload documents or create a subfolder to get started.</p>
        <a class="btn btn-purple" href="<?= url('app/upload' . ($folder ? '?folder=' . (int) $folder['id'] : '')) ?>">Upload documents</a></div>
    <?php elseif ($viewMode === 'grid'): ?>
    <div class="row g-3">
      <?php foreach ($docs as $d): ?>
      <div class="col-6 col-md-4 col-xl-3">
        <a class="doc-card" href="<?= url('app/documents/' . (int) $d['id']) ?>">
          <i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?>"></i>
          <span class="doc-name"><?= e($d['name']) ?></span>
          <small class="text-muted">.<?= e($d['extension']) ?> · <?= human_bytes($d['size_bytes']) ?></small>
          <?php if ($d['category_name']): ?><span class="badge text-bg-light"><?= e($d['category_name']) ?></span><?php endif; ?>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="panel p-0"><table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Category</th><th>Owner</th><th>Size</th><th>Downloads</th><th>Updated</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($docs as $d): ?>
        <tr>
          <td><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><a href="<?= url('app/documents/' . (int) $d['id']) ?>"><?= e($d['name']) ?></a></td>
          <td><?= e($d['category_name'] ?? '—') ?></td><td><?= e($d['owner_name']) ?></td>
          <td><?= human_bytes($d['size_bytes']) ?></td><td><?= (int) $d['download_count'] ?></td>
          <td><?= e(substr((string) $d['updated_at'], 0, 10)) ?></td>
          <td><a class="btn btn-sm btn-light" href="<?= url('app/documents/' . (int) $d['id'] . '/download') ?>"><i class="bi bi-download"></i></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </div>
</div>

<!-- New folder modal -->
<div class="modal fade" id="folderModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= url('app/folders') ?>">
  <?= csrf_field() ?>
  <div class="modal-header"><h5 class="modal-title">New folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Name</label><input class="form-control mb-3" name="name" required>
    <label class="form-label">Parent folder</label>
    <select class="form-select mb-3" name="parent_id"><option value="">Root</option>
      <?php foreach ($tree as $f): ?><option value="<?= (int) $f['id'] ?>" <?= $folder && (int) $folder['id'] === (int) $f['id'] ? 'selected' : '' ?>><?= str_repeat('— ', (int) $f['_depth']) . e($f['name']) ?></option><?php endforeach; ?>
    </select>
    <label class="form-label">Color</label><input type="color" class="form-control form-control-color" name="color" value="#6d28d9">
    <label class="form-label mt-3">Description</label><input class="form-control" name="description">
  </div>
  <div class="modal-footer"><button class="btn btn-purple" type="submit">Create folder</button></div>
</form></div></div>

<?php foreach ($tree as $f): ?>
<div class="modal fade" id="folderEdit<?= (int) $f['id'] ?>" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= url('app/folders/' . (int) $f['id'] . '/update') ?>">
  <?= csrf_field() ?><input type="hidden" name="action" value="rename">
  <div class="modal-header"><h5 class="modal-title">Folder: <?= e($f['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Name</label><input class="form-control mb-3" name="name" value="<?= e($f['name']) ?>">
    <label class="form-label">Color</label><input type="color" class="form-control form-control-color mb-3" name="color" value="<?= e($f['color'] ?: '#6d28d9') ?>">
    <hr><label class="form-label">Move to parent</label>
    <div class="input-group"><select class="form-select" name="parent_id"><option value="">Root</option>
      <?php foreach ($tree as $pf): if ((int) $pf['id'] === (int) $f['id']) continue; ?><option value="<?= (int) $pf['id'] ?>"><?= e($pf['name']) ?></option><?php endforeach; ?>
    </select><button class="btn btn-outline-secondary" type="submit" name="action" value="move" formaction="<?= url('app/folders/' . (int) $f['id'] . '/update') ?>">Move</button></div>
  </div>
  <div class="modal-footer"><button class="btn btn-purple" type="submit">Save changes</button></div>
</form></div></div>
<?php endforeach; ?>
<?php partial('app-close'); ?>
