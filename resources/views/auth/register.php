<?php $title = 'Create your company — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-8 col-lg-6">
  <div class="auth-card">
    <div class="text-center mb-4"><span class="brand-mark lg"><i class="bi bi-buildings-fill"></i></span>
      <h3 class="mt-3">Register your company</h3>
      <p class="text-muted mb-0">Get a private, isolated workspace with a 14-day free trial.</p></div>
    <?php partial('flash'); ?>
    <form method="post" action="<?= url('register') ?>">
      <?= csrf_field() ?>
      <label class="form-label">Company / organization name</label>
      <input class="form-control form-control-lg mb-3" name="company_name" required value="<?= old('company_name') ?>" placeholder="e.g. BrightMed Clinics">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Your name</label>
          <input class="form-control" name="name" required value="<?= old('name') ?>"></div>
        <div class="col-md-6"><label class="form-label">Work email</label>
          <input type="email" class="form-control" name="email" required value="<?= old('email') ?>"></div>
      </div>
      <label class="form-label mt-3">Password</label>
      <input type="password" class="form-control mb-1" name="password" required minlength="8" autocomplete="new-password">
      <div class="form-text">Minimum 8 characters. Stored with bcrypt hashing.</div>
      <label class="form-label mt-3">Start on plan (trial applies to all)</label>
      <div class="row g-2">
        <?php foreach ($plans as $i => $p): ?>
        <div class="col-md-6">
          <label class="plan-pick w-100">
            <input type="radio" name="plan_id" value="<?= (int) $p['id'] ?>" <?= $i === 1 ? 'checked' : '' ?>>
            <span><strong><?= e($p['name']) ?></strong> — $<?= e($p['price_monthly']) ?>/mo<br>
            <small class="text-muted"><?= (int) $p['storage_mb'] < 0 ? 'Unlimited storage' : human_bytes((int) $p['storage_mb'] * 1024 * 1024) ?> · <?= (int) $p['max_users'] < 0 ? 'Unlimited' : (int) $p['max_users'] ?> users</small></span>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-purple btn-lg w-100 mt-4" type="submit">Create company & start trial</button>
      <p class="small text-muted text-center mt-3 mb-0">By registering you agree to our <a href="<?= url('terms') ?>">Terms</a> and <a href="<?= url('privacy') ?>">Privacy Policy</a>. You'll verify your email to activate the account (onboarding step 1 of 3).</p>
    </form>
    <p class="text-center text-muted mt-3 mb-0">Already have a workspace? <a href="<?= url('login') ?>">Sign in</a></p>
  </div>
</div></div></div></section>
<?php partial('site-footer'); ?>
