<?php $pageTitle = 'Storage'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<?php $cap = $sub && (int) $sub['storage_mb'] >= 0 ? (int) $sub['storage_mb'] * 1024 * 1024 : 0;
      $pct = $cap > 0 ? min(100, round($used / $cap * 100, 1)) : 0; ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Storage usage</h5><a href="<?= url('app/subscription') ?>"><?= e($sub['plan_name'] ?? '') ?> plan</a></div>
      <div class="display-6 fw-bold"><?= human_bytes($used) ?></div>
      <p class="text-muted">of <?= $cap > 0 ? human_bytes($cap) : 'unlimited' ?> used (<?= $pct ?>%)</p>
      <div class="progress" style="height:12px"><div class="progress-bar bg-purple" style="width:<?= $pct ?>%"></div></div>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>By file type</h5></div>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>Type</th><th>Files</th><th>Size</th></tr></thead><tbody>
      <?php foreach ($byType as $r): ?>
        <tr><td><i class="bi <?= e(FileSecurity::iconFor((string) $r['extension'])) ?> me-1"></i>.<?= e($r['extension']) ?></td>
          <td><?= (int) $r['files'] ?></td><td><?= human_bytes($r['bytes']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$byType): ?><tr><td colspan="3" class="text-muted">No files stored.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Largest documents</h5></div>
      <?php foreach ($largest as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <a href="<?= url('app/documents/' . (int) $d['id']) ?>"><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><?= e($d['name']) ?></a>
        <span class="text-muted"><?= human_bytes($d['size_bytes']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$largest): ?><p class="text-muted mb-0">No documents yet.</p><?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-head"><h5><i class="bi bi-trash3 me-1"></i>Trash</h5><span class="text-muted small">Soft-deleted documents</span></div>
      <?php foreach ($trash as $d): ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <span><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><?= e($d['name']) ?>
          <small class="text-muted d-block">deleted <?= e(substr((string) $d['deleted_at'], 0, 10)) ?> · <?= human_bytes($d['size_bytes']) ?></small></span>
        <span class="d-flex gap-1">
        <form method="post" action="<?= url('app/documents/' . (int) $d['id'] . '/restore') ?>">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-success">Restore</button></form>
        <form method="post" action="<?= url('app/storage/' . (int) $d['id'] . '/purge') ?>" onsubmit="return confirm('Permanently delete this file? This cannot be undone.')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Purge permanently</button></form>
        </span>
      </div>
      <?php endforeach; ?>
      <?php if (!$trash): ?><p class="text-muted mb-0">Trash is empty.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
