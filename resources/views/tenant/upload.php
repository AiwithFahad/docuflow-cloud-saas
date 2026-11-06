<?php $pageTitle = 'Upload'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row justify-content-center"><div class="col-lg-8">
  <div class="panel">
    <h4 class="mb-1">Upload documents</h4>
    <p class="text-muted">Files are validated (type + content), stored privately outside the web root, and versioned automatically. Max file size on your plan: <strong><?= human_bytes($maxFile) ?></strong>.</p>
    <form method="post" action="<?= url('app/upload') ?>" enctype="multipart/form-data" id="uploadForm">
      <?= csrf_field() ?>
      <div class="dropzone" id="dropzone">
        <i class="bi bi-cloud-upload display-4 text-purple"></i>
        <p class="mt-2 mb-1"><strong>Drag & drop files here</strong> or click to browse</p>
        <p class="text-muted small mb-0">PDF, Word, Excel, PowerPoint, images, text, CSV, ZIP</p>
        <input type="file" name="files[]" id="fileInput" multiple hidden>
      </div>
      <ul class="list-group mt-3" id="fileList"></ul>
      <div class="row g-3 mt-1">
        <div class="col-md-6"><label class="form-label">Destination folder</label>
          <select class="form-select" name="folder_id"><option value="">Root</option>
            <?php foreach ($tree as $f): ?><option value="<?= (int) $f['id'] ?>" <?= $folderId === (int) $f['id'] ? 'selected' : '' ?>><?= str_repeat('— ', (int) $f['_depth']) . e($f['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-6"><label class="form-label">Category</label>
          <select class="form-select" name="category_id"><option value="">Uncategorized</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-12"><label class="form-label">Tags</label>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($tags as $t): ?>
            <label class="tag-check"><input type="checkbox" name="tag_ids[]" value="<?= (int) $t['id'] ?>"> <?= e($t['name']) ?></label>
            <?php endforeach; ?>
            <?php if (!$tags): ?><span class="text-muted small">No tags yet — create them under Categories & Tags.</span><?php endif; ?>
          </div></div>
        <div class="col-12"><label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="2" placeholder="What are these documents?"></textarea></div>
      </div>
      <button class="btn btn-purple btn-lg mt-4" type="submit"><i class="bi bi-cloud-upload me-1"></i> Upload securely</button>
    </form>
  </div>
</div></div>
<script>
const dz = document.getElementById('dropzone'), fi = document.getElementById('fileInput'), list = document.getElementById('fileList');
dz.addEventListener('click', () => fi.click());
['dragover','dragenter'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('drag'); }));
['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('drag'); }));
dz.addEventListener('drop', e => { fi.files = e.dataTransfer.files; render(); });
fi.addEventListener('change', render);
function render() {
  list.innerHTML = '';
  [...fi.files].forEach(f => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between';
    li.innerHTML = '<span><i class="bi bi-file-earmark me-2"></i></span><span class="badge text-bg-light"></span>';
    li.firstElementChild.append(f.name);
    li.lastElementChild.textContent = (f.size / 1024).toFixed(1) + ' KB';
    list.appendChild(li);
  });
}
</script>
<?php partial('app-close'); ?>
