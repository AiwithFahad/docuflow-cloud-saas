<?php $title = 'Shared document — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="py-5"><div class="container" style="max-width:640px">
  <div class="feature-card text-center">
    <div class="feature-icon mx-auto" style="width:72px;height:72px;font-size:2rem"><i class="bi <?= e(FileSecurity::iconFor((string) $share['extension'])) ?>"></i></div>
    <h3 class="mt-3"><?= e($share['name']) ?>.<?= e($share['extension']) ?></h3>
    <p class="text-muted">Shared by <strong><?= e($share['company']) ?></strong> via DocuFlow Cloud<br>
    <?= human_bytes($share['size_bytes']) ?> · link expires <?= $share['expires_at'] ? e(substr((string) $share['expires_at'], 0, 10)) : 'never' ?></p>
    <?php if (FileSecurity::isPreviewable((string) $share['extension'])): ?>
      <div class="alert alert-info small text-start"><i class="bi bi-eye me-1"></i>Preview is available after sign-in for protected files. Use download below if enabled by the owner.</div>
    <?php endif; ?>
    <?php if (!empty($share['can_download'])): ?>
      <a class="btn btn-purple btn-lg" href="<?= url('s/' . $token . '/download') ?>"><i class="bi bi-download me-2"></i>Download file</a>
    <?php else: ?>
      <div class="alert alert-warning mb-0">The owner disabled downloads for this link (view-only share).</div>
    <?php endif; ?>
    <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Served securely by DocuFlow Cloud — this link can be revoked by the owner at any time.</p>
  </div>
</div></section>
<?php partial('site-footer'); ?>
