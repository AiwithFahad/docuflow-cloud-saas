<?php $title = 'Link unavailable — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="py-5 text-center"><div class="container py-5">
  <i class="bi bi-link-45deg display-3 text-muted"></i>
  <h3 class="mt-3">This share link is no longer available</h3>
  <p class="text-muted">It may have expired, been revoked by the owner, or the document was removed.</p>
  <a class="btn btn-outline-navy" href="<?= url('') ?>">Go to DocuFlow Cloud</a>
</div></section>
<?php partial('site-footer'); ?>
