<?php $title = 'Set a new password — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
  <div class="auth-card">
    <h3>Choose a new password</h3><p class="text-muted">This link expires in 1 hour and can be used once.</p>
    <?php partial('flash'); ?>
    <form method="post" action="<?= url('reset-password') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label class="form-label">New password</label>
      <input type="password" class="form-control form-control-lg mb-3" name="password" required minlength="8" autocomplete="new-password">
      <button class="btn btn-purple btn-lg w-100" type="submit">Update password</button>
    </form>
  </div>
</div></div></div></section>
<?php partial('site-footer'); ?>
