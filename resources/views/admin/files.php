<?php $pageTitle = 'File Manager'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-folder2-open me-1"></i>File manager</h4>
  <span class="text-muted small">Drag &amp; drop files anywhere here to upload · everything is permission-checked server-side</span>
</div>
<?php partial('filemanager', compact('categories', 'tags', 'departments', 'tenantUsers', 'folderTree', 'initialFolder', 'initialQ')); ?>
<?php partial('app-close'); ?>
