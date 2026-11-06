<?php $title = 'Industries — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Industries</span><h1 class="section-title">Built for document-heavy industries</h1>
  <p class="text-muted mx-auto" style="max-width:580px">Wherever paper trails matter, DocuFlow keeps them digital, permissioned and provable.</p>
</div></section>
<section class="pb-5"><div class="container"><div class="row g-4">
  <?php $inds = [
    ['bi-heart-pulse', 'Healthcare', 'Patient forms, lab reports and policies with role-based access and full audit history.'],
    ['bi-bank', 'Finance & Insurance', 'KYC files, claims and statements organized by client with expiring secure shares.'],
    ['bi-scale', 'Legal', 'Case files and contracts with version control and approval decisions on record.'],
    ['bi-mortarboard', 'Education', 'Admissions documents, certificates and staff files across departments and campuses.'],
    ['bi-buildings', 'Real Estate', 'Listings, agreements and tenant documents shared with clients through token links.'],
    ['bi-truck', 'Logistics', 'Bills of lading, customs papers and PODs searchable by tag from anywhere.'],
    ['bi-shop', 'Retail & E-commerce', 'Supplier contracts, invoices and brand assets in one controlled library.'],
    ['bi-gear-wide-connected', 'Manufacturing', 'Drawings, QA reports and compliance documents with strict version restore.'],
    ['bi-briefcase', 'Agencies', 'Client deliverables with per-client folders and view-tracked share links.'],
  ]; foreach ($inds as [$icon, $t, $d]): ?>
  <div class="col-md-6 col-lg-4"><div class="feature-card h-100"><div class="feature-icon"><i class="bi <?= $icon ?>"></i></div><h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p></div></div>
  <?php endforeach; ?>
</div></div></section>
<?php partial('site-footer'); ?>
