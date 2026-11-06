<?php $title = 'Accept invitation — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
  <div class="auth-card">
    <h3>You've been invited</h3>
    <p class="text-muted">Join the company workspace as <strong><?= e(ucwords(str_replace('_', ' ', (string) $invite['role']))) ?></strong> using <strong><?= e($invite['email']) ?></strong>. Set your name and password to activate your account.</p>
    <?php partial('flash'); ?>
    <form method="post" action="<?= url('accept-invite') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($invite['token']) ?>">
      <label class="form-label">Your full name</label>
      <input class="form-control form-control-lg mb-3" name="name" required>
      <label class="form-label">Password</label>
      <input type="password" class="form-control form-control-lg mb-3" name="password" required minlength="8" autocomplete="new-password">
      <button class="btn btn-purple btn-lg w-100" type="submit">Activate my account</button>
    </form>
  </div>
</div></div></div></section>
<?php partial('site-footer'); ?>
