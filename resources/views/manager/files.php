<?php $pageTitle = 'Team Files'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-folder2-open me-1"></i>Team files</h4>
  <span class="text-muted small">Showing documents you own or that are shared with you / your teams</span>
</div>
<?php partial('filemanager', compact('categories', 'tags', 'departments', 'tenantUsers', 'folderTree', 'initialFolder', 'initialQ')); ?>
<?php partial('app-close'); ?>
