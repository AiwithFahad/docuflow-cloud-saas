<?php
// AJAX file manager (driven by public/assets/js/docuflow.js + ApiController).
// Expects: $categories, $tags, $departments, $tenantUsers, $tree,
//          $initialFolder, $initialQ.
?>
<div data-df-filemanager data-folder="<?= !empty($initialFolder) ? (int) $initialFolder : '' ?>" data-q="<?= e($initialQ ?? '') ?>">
  <div class="panel p-3 mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <div class="input-group" style="max-width:300px">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input class="form-control" data-df-search placeholder="Search in this view…" value="<?= e($initialQ ?? '') ?>">
      </div>
      <select class="form-select w-auto" data-df-status>
        <option value="active">Active</option><option value="archived">Archived</option>
        <option value="favorites">★ Favourites</option><option value="recent">Recent (30d)</option>
        <option value="any">All documents</option><option value="trash">Trash</option>
      </select>
      <select class="form-select w-auto" data-df-category><option value="">All categories</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <select class="form-select w-auto" data-df-tag><option value="">All tags</option>
        <?php foreach ($tags as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select>
      <select class="form-select w-auto" data-df-department><option value="">All departments</option>
        <?php foreach ($departments as $dep): ?><option value="<?= (int) $dep['id'] ?>"><?= e($dep['name']) ?></option><?php endforeach; ?></select>
      <select class="form-select w-auto" data-df-sort>
        <option value="new">Newest first</option><option value="old">Oldest first</option>
        <option value="name">Name A–Z</option><option value="size">Largest first</option><option value="downloads">Most downloaded</option>
      </select>
      <div class="btn-group ms-auto">
        <button type="button" class="btn btn-outline-secondary" data-df-view="grid" title="Grid view"><i class="bi bi-grid-3x3-gap"></i></button>
        <button type="button" class="btn btn-outline-secondary" data-df-view="list" title="List view"><i class="bi bi-list-ul"></i></button>
      </div>
      <button type="button" class="btn btn-outline-navy" data-bs-toggle="modal" data-bs-target="#dfFolderModal"><i class="bi bi-folder-plus me-1"></i>New folder</button>
      <label class="btn btn-purple mb-0"><i class="bi bi-cloud-upload me-1"></i>Upload<input type="file" class="d-none" data-df-fileinput multiple></label>
    </div>
    <nav class="mt-3" aria-label="breadcrumb"><ol class="breadcrumb mb-0" data-df-crumb></ol></nav>
  </div>

  <div class="df-bulkbar d-none" data-df-bulkbar>
    <span class="fw-semibold"><span data-df-selcount>0</span> selected</span>
    <button type="button" class="btn btn-sm btn-light" data-df-bulk="move"><i class="bi bi-folder-symlink me-1"></i>Move</button>
    <button type="button" class="btn btn-sm btn-light" data-df-bulk="download"><i class="bi bi-download me-1"></i>Download</button>
    <button type="button" class="btn btn-sm btn-light" data-df-bulk="share"><i class="bi bi-link-45deg me-1"></i>Share</button>
    <button type="button" class="btn btn-sm btn-light" data-df-bulk="favorite"><i class="bi bi-star me-1"></i>Favourite</button>
    <button type="button" class="btn btn-sm btn-light" data-df-bulk="archive"><i class="bi bi-archive me-1"></i>Archive</button>
    <button type="button" class="btn btn-sm btn-light" data-df-bulk="restore"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
    <button type="button" class="btn btn-sm btn-danger" data-df-bulk="delete"><i class="bi bi-trash me-1"></i>Delete</button>
    <button type="button" class="btn btn-sm btn-danger d-none" data-df-bulk="purge"><i class="bi bi-trash3 me-1"></i>Purge</button>
    <button type="button" class="btn btn-sm btn-outline-light ms-auto" data-df-clear>Clear</button>
  </div>

  <div class="position-relative" data-df-dropzone>
    <div class="df-drop-overlay d-none" data-df-dropoverlay><div><i class="bi bi-cloud-upload display-5"></i><div class="fw-semibold mt-2">Drop files to upload</div></div></div>
    <div class="row g-3 mb-1" data-df-folders></div>
    <div data-df-docs></div>
    <div class="text-center text-muted py-5 d-none" data-df-empty>
      <i class="bi bi-folder2-open display-4 text-purple"></i>
      <div class="mt-2">Nothing here yet. Upload files or adjust the filters.</div>
    </div>
  </div>

  <div class="df-uploads d-none" data-df-uploads></div>
</div>

<!-- New folder modal -->
<div class="modal fade" id="dfFolderModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">New folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Name</label><input class="form-control mb-3" data-df-folder-name placeholder="e.g. Client contracts">
    <label class="form-label">Colour</label><input type="color" class="form-control form-control-color" data-df-folder-color value="#f5b93f">
    <p class="small text-muted mt-3 mb-0">The folder is created inside the folder you are currently viewing.</p>
  </div>
  <div class="modal-footer"><button class="btn btn-purple" data-df-folder-create>Create folder</button></div>
</div></div></div>

<!-- Move modal -->
<div class="modal fade" id="dfMoveModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Move documents</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Destination folder</label>
    <select class="form-select" data-df-move-target><option value="">Root (no folder)</option>
      <?php foreach ($folderTree as $f): ?><option value="<?= (int) $f['id'] ?>"><?= str_repeat('— ', (int) $f['_depth']) ?><?= e($f['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="modal-footer"><button class="btn btn-purple" data-df-move-confirm>Move here</button></div>
</div></div></div>

<!-- Share link modal -->
<div class="modal fade" id="dfShareModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Create share link</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Expires after</label>
    <select class="form-select mb-3" data-df-share-days><option value="1">1 day</option><option value="7" selected>7 days</option><option value="30">30 days</option><option value="90">90 days</option><option value="365">1 year</option></select>
    <label class="form-label">Password (optional)</label>
    <input class="form-control mb-3" data-df-share-password placeholder="Leave blank for no password" autocomplete="new-password">
    <label class="form-check"><input type="checkbox" class="form-check-input" data-df-share-download checked> Allow downloads</label>
    <div class="d-none mt-3" data-df-share-result>
      <label class="form-label">Share link</label>
      <div class="input-group"><input class="form-control" data-df-share-url readonly>
      <button class="btn btn-outline-navy" data-df-share-copy>Copy</button></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-purple" data-df-share-create>Create link</button></div>
</div></div></div>
