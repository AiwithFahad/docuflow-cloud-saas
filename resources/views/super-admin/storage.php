<?php $pageTitle = 'Platform Storage'; partial('superadmin-open', compact('user', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-hdd-stack me-1"></i>Storage by organization</h5><span class="text-muted">Total live: <?= human_bytes($total) ?></span></div>
      <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Organization</th><th>Documents</th><th>Used</th><th>Quota</th><th style="width:28%">Usage</th><th>Trash</th></tr></thead><tbody>
        <?php foreach ($byTenant as $t): $cap = $t['quota_mb'] !== null && (int) $t['quota_mb'] >= 0 ? (int) $t['quota_mb'] * 1024 * 1024 : -1;
          $pct = $cap > 0 ? min(100, round((int) $t['bytes'] / $cap * 100, 1)) : 0; ?>
        <tr><td><strong><?= e($t['name']) ?></strong></td><td><?= (int) $t['docs'] ?></td><td><?= human_bytes((int) $t['bytes']) ?></td>
          <td><?= $cap >= 0 ? human_bytes($cap) : 'Unlimited' ?></td>
          <td><?php if ($cap > 0): ?><div class="progress" style="height:10px"><div class="progress-bar bg-purple" style="width:<?= $pct ?>%"></div></div><span class="small text-muted"><?= $pct ?>%</span><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
          <td class="small"><?= (int) $t['trash_docs'] ?> files · <?= human_bytes((int) $t['trash_bytes']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
    <div class="panel"><div class="panel-head"><h5>Largest files platform-wide</h5></div>
      <?php foreach ($largest as $d): ?>
      <div class="d-flex justify-content-between border-bottom py-2"><span><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><?= e($d['name']) ?> <span class="text-muted small">· <?= e($d['tenant_name']) ?></span></span>
      <span class="text-muted"><?= human_bytes((int) $d['size_bytes']) ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel"><div class="panel-head"><h5>By file type</h5></div>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>Type</th><th>Files</th><th>Size</th></tr></thead><tbody>
      <?php foreach ($byType as $r): ?><tr><td>.<?= e($r['extension']) ?></td><td><?= (int) $r['files'] ?></td><td><?= human_bytes((int) $r['bytes']) ?></td></tr><?php endforeach; ?>
      <?php if (!$byType): ?><tr><td colspan="3" class="text-muted">No files stored.</td></tr><?php endif; ?>
      </tbody></table>
      <p class="small text-muted mt-3 mb-0">Files live in private storage outside the web root under randomized names and are only ever streamed by the authenticated download controller or a valid share token.</p>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
