<?php $pageTitle = 'Profile'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row justify-content-center"><div class="col-lg-6">
  <div class="panel">
    <div class="text-center mb-4">
      <span class="avatar xl"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
      <h4 class="mt-2 mb-0"><?= e($user['name']) ?></h4>
      <p class="text-muted"><?= e($user['email']) ?> · <?= e(ucwords(str_replace('_', ' ', $user['role']))) ?> at <?= e($tenant['name']) ?></p>
    </div>
    <form method="post" action="<?= url('app/profile') ?>">
      <?= csrf_field() ?>
      <label class="form-label">Full name</label>
      <input class="form-control mb-3" name="name" value="<?= e($user['name']) ?>" required>
      <hr><h5>Change password</h5>
      <label class="form-label">Current password</label>
      <input type="password" class="form-control mb-3" name="current_password" autocomplete="current-password">
      <label class="form-label">New password</label>
      <input type="password" class="form-control mb-4" name="new_password" minlength="8" autocomplete="new-password">
      <button class="btn btn-purple btn-lg w-100">Save profile</button>
    </form>
  </div>
</div></div>
<?php partial('app-close'); ?>
