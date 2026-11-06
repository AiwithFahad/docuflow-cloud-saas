<?php $title = 'Contact — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Contact</span><h1 class="section-title">Talk to a human</h1>
  <p class="text-muted">Questions about plans, security reviews or migration help — we reply within one business day.</p>
</div></section>
<section class="pb-5"><div class="container"><div class="row g-4">
  <div class="col-lg-5">
    <div class="feature-card h-100">
      <h5><i class="bi bi-envelope text-purple me-2"></i>Email</h5><p class="text-muted">support@docuflow.example<br>sales@docuflow.example</p>
      <h5 class="mt-4"><i class="bi bi-geo-alt text-purple me-2"></i>Office</h5><p class="text-muted mb-0">DocuFlow Cloud HQ<br>Remote-first, worldwide</p>
      <h5 class="mt-4"><i class="bi bi-clock text-purple me-2"></i>Support hours</h5><p class="text-muted mb-0">Mon–Fri, 9:00–18:00 UTC</p>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="feature-card">
      <?php partial('flash'); ?>
      <form method="post" action="<?= url('contact') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Your name</label><input class="form-control" name="name" required value="<?= old('name') ?>"></div>
          <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required value="<?= old('email') ?>"></div>
          <div class="col-12"><label class="form-label">Subject</label>
            <select class="form-select" name="subject"><option>Sales inquiry</option><option>Support</option><option>Security review</option><option>Migration help</option><option>Other</option></select></div>
          <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required><?= old('message') ?></textarea></div>
          <div class="col-12"><button class="btn btn-purple btn-lg" type="submit">Send message</button></div>
        </div>
      </form>
    </div>
  </div>
</div></div></section>
<?php partial('site-footer'); ?>
