<?php $title = 'Security — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Security</span><h1 class="section-title">Security is the architecture, not an add-on</h1>
  <p class="text-muted mx-auto" style="max-width:600px">How DocuFlow protects your company's documents at every layer — storage, transport, access and audit.</p>
</div></section>
<section class="pb-5"><div class="container"><div class="row g-4">
  <?php $secs = [
    ['bi-database-lock', 'Tenant isolation', 'Every business table carries a tenant_id. Every query — including every file operation — is scoped and authorized against the signed-in user\'s company.'],
    ['bi-hdd-network', 'Private file storage', 'Uploaded files are stored outside the public web root with randomized names. There is no direct URL to any file, ever.'],
    ['bi-file-earmark-lock', 'Upload validation', 'Extension allow-list, real MIME sniffing, per-plan size limits, and hard blocks on executable types before anything is written to disk.'],
    ['bi-signpost-split', 'Path-traversal protection', 'Stored paths are resolved against the private storage directory and rejected if they escape it. Downloads stream through permission checks only.'],
    ['bi-key', 'Share token security', 'Public links use 192-bit random tokens, support expiry, download toggles, view counts and instant revocation.'],
    ['bi-person-lock', 'Authentication hardening', 'Bcrypt password hashing, secure session cookies (HttpOnly, SameSite), session regeneration on login, and rate limiting after repeated failures.'],
    ['bi-shield-plus', 'Request integrity', 'CSRF tokens on every state-changing form, PDO prepared statements throughout, and output escaping on every page.'],
    ['bi-journal-check', 'Audit everything', 'Activity and audit logs capture uploads, downloads, permission changes, approvals and admin actions with user, IP and timestamps.'],
    ['bi-credit-card-2-front', 'Honest billing', 'Payments are recorded as pending and only marked successful after verified confirmation. No silent charges, no fake "paid" states.'],
  ]; foreach ($secs as [$icon, $t, $d]): ?>
  <div class="col-md-6 col-lg-4"><div class="feature-card h-100"><div class="feature-icon"><i class="bi <?= $icon ?>"></i></div><h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p></div></div>
  <?php endforeach; ?>
</div></div></section>
<?php partial('site-footer'); ?>
