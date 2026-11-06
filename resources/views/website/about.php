<?php $title = 'About — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">About</span><h1 class="section-title">We build calm for document chaos</h1>
  <p class="text-muted mx-auto" style="max-width:600px">DocuFlow Cloud was created for a simple reason: growing businesses drown in files scattered across inboxes, drives and chat threads. We built the workspace we always wanted — organized, permission-aware and auditable.</p>
</div></section>
<section class="pb-5"><div class="container">
  <div class="row g-4">
    <?php $values = [
      ['bi-bullseye', 'Our mission', 'Give every company — from a 3-person studio to a 3,000-person enterprise — a single, trustworthy home for its documents.'],
      ['bi-shield-check', 'Privacy by design', 'Tenant isolation is an architectural rule, not a feature toggle. Your files are never visible to another company, ever.'],
      ['bi-lightning-charge', 'Craft & speed', 'We obsess over the boring details: fast search, honest billing, predictable versioning, and interfaces that need no manual.'],
    ]; foreach ($values as [$icon, $t, $d]): ?>
    <div class="col-md-4"><div class="feature-card h-100"><div class="feature-icon"><i class="bi <?= $icon ?>"></i></div><h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p></div></div>
    <?php endforeach; ?>
  </div>
  <div class="stats-band rounded-4 mt-5"><div class="row text-center g-4 py-4">
    <div class="col-6 col-lg-3"><div class="stat-num"><?= number_format($stats['companies']) ?>+</div><div class="stat-label">Companies</div></div>
    <div class="col-6 col-lg-3"><div class="stat-num"><?= number_format($stats['documents']) ?>+</div><div class="stat-label">Documents</div></div>
    <div class="col-6 col-lg-3"><div class="stat-num"><?= number_format($stats['users']) ?>+</div><div class="stat-label">Users</div></div>
    <div class="col-6 col-lg-3"><div class="stat-num">99.9%</div><div class="stat-label">Uptime target</div></div>
  </div></div>
</div></section>
<?php partial('site-footer'); ?>
