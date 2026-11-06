<?php $title = 'Workspace suspended — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="py-5 text-center"><div class="container py-5">
  <i class="bi bi-pause-circle display-3 text-warning"></i>
  <h3 class="mt-3">Your company workspace is suspended</h3>
  <p class="text-muted mx-auto" style="max-width:480px">Access to <strong><?= e($tenant['name'] ?? 'your company') ?></strong> has been paused by the platform team. Your documents are safe — contact support to reactivate.</p>
  <a class="btn btn-purple" href="<?= url('contact') ?>">Contact support</a>
  <form method="post" action="<?= url('logout') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-outline-secondary" type="submit">Sign out</button></form>
</div></section>
<?php partial('site-footer'); ?>
