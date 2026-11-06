<?php $title = 'Password required — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="py-5"><div class="container" style="max-width:640px">
  <div class="feature-card text-center">
    <div class="feature-icon mx-auto" style="width:72px;height:72px;font-size:2rem"><i class="bi bi-lock-fill"></i></div>
    <h3 class="mt-3">This link is password-protected</h3>
    <p class="text-muted"><strong><?= e($share['company']) ?></strong> protected this shared document with a password.<br>Enter it to continue.</p>
    <?php if (!empty($error)): ?><div class="alert alert-danger small"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= url('s/' . $token . '/unlock') ?>" class="d-flex gap-2 justify-content-center">
      <?= csrf_field() ?>
      <input type="password" class="form-control" name="password" placeholder="Link password" style="max-width:280px" required autofocus>
      <button class="btn btn-purple">Unlock</button>
    </form>
    <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Served securely by DocuFlow Cloud — failed attempts are logged for the owner.</p>
  </div>
</div></section>
<?php partial('site-footer'); ?>
