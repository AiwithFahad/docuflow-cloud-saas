<?php $title = 'Sign in — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
  <div class="auth-card">
    <div class="text-center mb-4"><span class="brand-mark lg"><i class="bi bi-cloud-check-fill"></i></span>
      <h3 class="mt-3">Welcome back</h3><p class="text-muted mb-0">Sign in to your DocuFlow workspace</p></div>
    <?php partial('flash'); ?>
    <form method="post" action="<?= url('login') ?>">
      <?= csrf_field() ?>
      <label class="form-label">Email</label>
      <input type="email" class="form-control form-control-lg mb-3" name="email" required value="<?= old('email') ?>" autocomplete="email">
      <label class="form-label">Password</label>
      <input type="password" class="form-control form-control-lg mb-3" name="password" required autocomplete="current-password">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">Protected by rate limiting</span>
        <a href="<?= url('forgot-password') ?>" class="small">Forgot password?</a>
      </div>
      <button class="btn btn-purple btn-lg w-100" type="submit">Sign in</button>
    </form>
    <div class="demo-box mt-4">
      <strong class="small">Demo accounts</strong> <span class="text-muted small">(password: <code>password</code>)</span>
      <div class="small mt-1">Super Admin — super@docuflow.test<br>Company Admin — admin@demo.com<br>Manager — manager@demo.com · Member — member@demo.com</div>
    </div>
    <p class="text-center text-muted mt-3 mb-0">New company? <a href="<?= url('register') ?>">Start your free trial</a></p>
  </div>
</div></div></div></section>
<?php partial('site-footer'); ?>
