<?php // Shared notification centre body. Expects: $treeBase, $notifications. ?>
<div class="panel">
  <div class="panel-head"><h5><i class="bi bi-bell me-1"></i>Notification centre</h5>
    <form method="post" action="<?= url($treeBase . '/notifications/read') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-navy">Mark all read</button></form></div>
  <?php foreach ($notifications as $n): ?>
  <div class="d-flex gap-3 border-bottom py-3 <?= $n['is_read'] ? '' : 'bg-light px-2 rounded' ?>">
    <span class="avatar"><?= e(strtoupper(substr($n['title'], 0, 1))) ?></span>
    <div class="flex-grow-1"><strong><?= e($n['title']) ?></strong>
      <div class="small"><?= e($n['body']) ?></div>
      <div class="small text-muted"><?= e(substr((string) $n['created_at'], 0, 16)) ?></div></div>
    <?php if (!empty($n['link'])): ?><a class="btn btn-sm btn-outline-navy align-self-start" href="<?= url($n['link']) ?>">Open</a><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php if (!$notifications): ?><p class="text-muted mb-0">No notifications yet.</p><?php endif; ?>
</div>
