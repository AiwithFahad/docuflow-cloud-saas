<?php $title = 'Not found — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="py-5 text-center"><div class="container py-5">
  <div class="display-1 text-purple fw-bold">404</div>
  <h3>That page doesn't exist</h3>
  <p class="text-muted">It may have been moved, or the link is wrong.</p>
  <a class="btn btn-purple" href="<?= url('') ?>">Back to home</a>
</div></section>
<?php partial('site-footer'); ?>
