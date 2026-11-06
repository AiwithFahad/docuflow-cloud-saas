<?php $title = 'Forgot password — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
  <div class="auth-card">
    <h3>Reset your password</h3><p class="text-muted">Enter your account email and we'll create a secure, expiring reset link.</p>
    <?php partial('flash'); ?>
    <form method="post" action="<?= url('forgot-password') ?>">
      <?= csrf_field() ?>
      <label class="form-label">Email</label>
      <input type="email" class="form-control form-control-lg mb-3" name="email" required>
      <button class="btn btn-purple btn-lg w-100" type="submit">Send reset link</button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="<?= url('login') ?>">Back to sign in</a></p>
  </div>
</div></div></div></section>
<?php partial('site-footer'); ?>
