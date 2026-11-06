<?php $title = 'DocuFlow Cloud — Secure Document Management SaaS'; partial('site-header', compact('title')); ?>

<!-- HERO -->
<section class="hero">
  <div class="container py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="hero-badge"><i class="bi bi-stars me-1"></i> Multi-tenant document cloud</span>
        <h1 class="hero-title">Every document your team needs. <span class="text-purple">Organized, secure, approved.</span></h1>
        <p class="hero-sub">DocuFlow Cloud gives your company a private document workspace — upload, organize in nested folders, share with expiring links, run approval workflows and keep every version, all isolated per tenant.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a class="btn btn-purple btn-lg" href="<?= url('register') ?>">Start free trial <i class="bi bi-arrow-right ms-1"></i></a>
          <a class="btn btn-outline-navy btn-lg" href="<?= url('pricing') ?>">View pricing</a>
        </div>
        <div class="hero-points mt-4">
          <span><i class="bi bi-check-circle-fill"></i> 14-day free trial</span>
          <span><i class="bi bi-check-circle-fill"></i> No card required</span>
          <span><i class="bi bi-check-circle-fill"></i> Cancel anytime</span>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="dash-preview shadow-lg">
          <div class="dash-preview-bar"><span></span><span></span><span></span><em>app.docuflow.cloud/files</em></div>
          <div class="dash-preview-body">
            <div class="dp-side">
              <div class="dp-logo"></div><div class="dp-item active"></div><div class="dp-item"></div><div class="dp-item"></div><div class="dp-item"></div><div class="dp-item w-75"></div>
            </div>
            <div class="dp-main">
              <div class="dp-cards">
                <div class="dp-card"><i class="bi bi-file-earmark-text text-purple"></i><b><?= number_format($stats['documents']) ?></b><small>Documents</small></div>
                <div class="dp-card"><i class="bi bi-folder2 text-navy"></i><b>Folders</b><small>Nested & tagged</small></div>
                <div class="dp-card"><i class="bi bi-pie-chart text-purple"></i><b><?= e($stats['storage']) ?></b><small>Stored securely</small></div>
              </div>
              <div class="dp-chart"><canvas id="heroChart" height="120"></canvas></div>
              <div class="dp-files">
                <div class="dp-file"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Q3-Report.pdf <span class="badge text-bg-success ms-auto">Approved</span></div>
                <div class="dp-file"><i class="bi bi-file-earmark-excel-fill text-success"></i> Budget-2026.xlsx <span class="badge text-bg-warning ms-auto">In review</span></div>
                <div class="dp-file"><i class="bi bi-file-earmark-word-fill text-primary"></i> Policy-v4.docx <span class="badge text-bg-info ms-auto">v4</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUSTED COMPANIES -->
<section class="py-4 bg-white border-bottom">
  <div class="container">
    <p class="text-center text-muted text-uppercase small fw-semibold mb-3">Trusted by document-driven teams at</p>
    <div class="logo-marquee"><div class="logo-track">
      <?php foreach (['Northwind', 'Acme Corp', 'Helios Legal', 'BrightMed', 'Vertex Labs', 'BluePeak', 'Kite & Co', 'Stonebridge'] as $logo): ?>
        <span class="logo-item"><i class="bi bi-building"></i> <?= e($logo) ?></span>
      <?php endforeach; ?>
      <?php foreach (['Northwind', 'Acme Corp', 'Helios Legal', 'BrightMed', 'Vertex Labs', 'BluePeak', 'Kite & Co', 'Stonebridge'] as $logo): ?>
        <span class="logo-item"><i class="bi bi-building"></i> <?= e($logo) ?></span>
      <?php endforeach; ?>
    </div></div>
  </div>
</section>

<!-- FEATURES -->
<section class="py-5">
  <div class="container py-4">
    <div class="text-center mx-auto" style="max-width:640px">
      <span class="section-tag">Features</span>
      <h2 class="section-title">A document platform, not just cloud storage</h2>
      <p class="text-muted">Everything a growing business needs to control its files — built for companies, teams and approvals, not just uploads.</p>
    </div>
    <div class="row g-4 mt-3">
      <?php $feats = [
        ['bi-folder2-open', 'Nested file manager', 'Organize documents in unlimited nested folders with grid & list views, colors and drag-and-drop uploads.'],
        ['bi-tags', 'Categories & tags', 'Classify files with categories and multi-tag search so anything is findable in seconds.'],
        ['bi-clock-history', 'Version control', 'Every upload keeps a version history with restore points, capped by your plan — never lose work again.'],
        ['bi-check2-square', 'Approval workflows', 'Route documents to approvers, track decisions and comments, and keep an auditable trail.'],
        ['bi-link-45deg', 'Secure sharing', 'Share with expiring token links, optional download permission, view counts and instant revoke.'],
        ['bi-people', 'Teams & permissions', 'Granular per-document permissions for users and teams: view, download and edit — enforced server-side.'],
        ['bi-shield-lock', 'Enterprise security', 'Private storage outside the web root, MIME validation, randomized filenames and a full audit log.'],
        ['bi-bar-chart', 'Reports & analytics', 'Real usage reports from your data: uploads, downloads, storage by type and top contributors.'],
      ]; foreach ($feats as [$icon, $t, $d]): ?>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card h-100">
          <div class="feature-icon"><i class="bi <?= $icon ?>"></i></div>
          <h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- STATISTICS -->
<section class="stats-band">
  <div class="container py-5">
    <div class="row text-center g-4">
      <div class="col-6 col-lg-3"><div class="stat-num"><?= number_format($stats['companies']) ?>+</div><div class="stat-label">Companies onboard</div></div>
      <div class="col-6 col-lg-3"><div class="stat-num"><?= number_format($stats['documents']) ?>+</div><div class="stat-label">Documents managed</div></div>
      <div class="col-6 col-lg-3"><div class="stat-num"><?= number_format($stats['users']) ?>+</div><div class="stat-label">Team members</div></div>
      <div class="col-6 col-lg-3"><div class="stat-num"><?= e($stats['storage']) ?></div><div class="stat-label">Stored & protected</div></div>
    </div>
  </div>
</section>

<!-- FOLDER ORGANIZATION SHOWCASE -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="section-tag">Organization</span>
        <h2 class="section-title">Folder structures that mirror how your business works</h2>
        <p class="text-muted">Create nested folders for departments, clients or projects. Move documents between them, color-code folders, and jump anywhere with one global search across names, categories and tags.</p>
        <ul class="check-list">
          <li><i class="bi bi-check-circle-fill"></i> Unlimited nesting with safe move protection</li>
          <li><i class="bi bi-check-circle-fill"></i> Grid & list views with sorting and pagination</li>
          <li><i class="bi bi-check-circle-fill"></i> CSV export of your full document register</li>
        </ul>
        <a class="btn btn-outline-navy" href="<?= url('features') ?>">Explore the file manager</a>
      </div>
      <div class="col-lg-6">
        <div class="showcase-card">
          <div class="folder-tree-demo">
            <div><i class="bi bi-folder-fill text-warning"></i> Finance <span class="text-muted small">/ Invoices 2026</span></div>
            <div class="ms-4"><i class="bi bi-folder-fill text-warning"></i> Suppliers <span class="badge text-bg-light">24 files</span></div>
            <div class="ms-4"><i class="bi bi-file-earmark-pdf text-danger"></i> Contract-Helios.pdf</div>
            <div><i class="bi bi-folder-fill text-warning"></i> HR <span class="text-muted small">/ Policies</span></div>
            <div class="ms-4"><i class="bi bi-file-earmark-word text-primary"></i> Remote-Work-Policy.docx</div>
            <div><i class="bi bi-folder-fill text-warning"></i> Legal <span class="badge text-bg-light">Approvals on</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- UPLOAD WORKFLOW + COLLABORATION -->
<section class="py-5">
  <div class="container py-4">
    <div class="text-center mx-auto" style="max-width:620px">
      <span class="section-tag">Workflow</span>
      <h2 class="section-title">From upload to approved in four steps</h2>
    </div>
    <div class="row g-4 mt-3 text-center">
      <?php $steps = [
        ['bi-cloud-upload', '1. Upload', 'Drag & drop multiple files. Type, size and content are validated before storage.'],
        ['bi-diagram-3', '2. Organize', 'File it into folders, add a category and tags so the whole team can find it.'],
        ['bi-chat-dots', '3. Collaborate', 'Comment, share with permissions, and keep everyone working on the latest version.'],
        ['bi-patch-check', '4. Approve', 'Request approval, get a decision with comments, and keep the full audit trail.'],
      ]; foreach ($steps as [$icon, $t, $d]): ?>
      <div class="col-md-6 col-lg-3"><div class="step-card h-100"><div class="feature-icon mx-auto"><i class="bi <?= $icon ?>"></i></div><h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- SECURITY -->
<section class="py-5 security-band">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="showcase-card dark">
          <h6 class="text-uppercase text-white-50 small">Access control matrix</h6>
          <table class="table table-dark table-sm align-middle mb-0">
            <thead><tr><th>Person</th><th>View</th><th>Download</th><th>Edit</th></tr></thead>
            <tbody>
              <tr><td>Finance team</td><td><i class="bi bi-check-lg text-success"></i></td><td><i class="bi bi-check-lg text-success"></i></td><td><i class="bi bi-x-lg text-danger"></i></td></tr>
              <tr><td>Sara (Manager)</td><td><i class="bi bi-check-lg text-success"></i></td><td><i class="bi bi-check-lg text-success"></i></td><td><i class="bi bi-check-lg text-success"></i></td></tr>
              <tr><td>External guest</td><td><i class="bi bi-x-lg text-danger"></i></td><td><i class="bi bi-x-lg text-danger"></i></td><td><i class="bi bi-x-lg text-danger"></i></td></tr>
            </tbody>
          </table>
          <div class="small text-white-50 mt-3"><i class="bi bi-shield-lock me-1"></i> Enforced on the server for every file operation — not just hidden in the UI.</div>
        </div>
      </div>
      <div class="col-lg-6">
        <span class="section-tag light">Security & access control</span>
        <h2 class="section-title text-white">Your documents never touch the public web root</h2>
        <p class="text-white-50">Files are stored privately with randomized names, served only through permission-checked downloads. Tenant data is isolated at the database level, every action is audit-logged, and share links expire automatically.</p>
        <a class="btn btn-light" href="<?= url('security') ?>">Read our security model</a>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="text-center"><span class="section-tag">Customers</span><h2 class="section-title">Teams that stopped losing files</h2></div>
    <div id="testiCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
      <div class="carousel-inner">
        <?php $testis = [
          ['Operations Director, logistics group', 'We moved 40,000 contracts into DocuFlow. The approval workflow alone cut our sign-off time from days to hours — and the audit log ended every "which version is final?" argument.'],
          ['Founder, accounting practice', 'Client documents used to live in email threads. Now each client has a folder, permissions per team, and expiring share links. Our auditors love it.'],
          ['HR Manager, healthcare provider', 'Onboarding paperwork, policies, signed forms — all versioned and searchable. DocuFlow paid for itself in the first month.'],
        ]; foreach ($testis as $i => [$role, $quote]): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
          <figure class="testi-card mx-auto">
            <div class="stars">★★★★★</div>
            <blockquote>“<?= e($quote) ?>”</blockquote>
            <figcaption><span class="avatar lg"><?= e(strtoupper(substr($role, 0, 1))) ?></span> <?= e($role) ?></figcaption>
          </figure>
        </div>
        <?php endforeach; ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#testiCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
      <button class="carousel-control-next" type="button" data-bs-target="#testiCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>
  </div>
</section>

<!-- PRICING PREVIEW -->
<section class="py-5">
  <div class="container py-4">
    <div class="text-center">
      <span class="section-tag">Pricing</span>
      <h2 class="section-title">Plans that scale with your files</h2>
      <?php partial('pricing-toggle'); ?>
    </div>
    <div class="row g-4 mt-2 justify-content-center">
      <?php foreach ($plans as $p): ?>
      <div class="col-md-6 col-lg-3">
        <div class="price-card h-100 <?= $p['slug'] === 'professional' ? 'featured' : '' ?>">
          <?php if ($p['slug'] === 'professional'): ?><span class="price-badge">Most popular</span><?php endif; ?>
          <h5><?= e($p['name']) ?></h5>
          <div class="price-num">$<span data-monthly="<?= e($p['price_monthly']) ?>" data-yearly="<?= e($p['price_yearly']) ?>"><?= e($p['price_monthly']) ?></span><small class="billing-note">/mo</small></div>
          <ul class="check-list small">
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['storage_mb'] < 0 ? 'Unlimited storage' : human_bytes((int) $p['storage_mb'] * 1024 * 1024) . ' storage' ?></li>
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['max_users'] < 0 ? 'Unlimited users' : (int) $p['max_users'] . ' users' ?></li>
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['version_history'] ?> versions kept</li>
          </ul>
          <a class="btn <?= $p['slug'] === 'professional' ? 'btn-purple' : 'btn-outline-navy' ?> w-100" href="<?= url('register') ?>">Start trial</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="text-center text-muted mt-3">Use coupon <code>WELCOME10</code> for 10% off your first paid plan. <a href="<?= url('pricing') ?>">Full pricing →</a></p>
  </div>
</section>

<!-- INTEGRATIONS -->
<section class="py-5 bg-white border-top">
  <div class="container text-center py-3">
    <span class="section-tag">Integrations</span>
    <h2 class="section-title">Plays nicely with your stack</h2>
    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
      <?php foreach (['Google Drive', 'Slack', 'Microsoft 365', 'Dropbox', 'Zapier', 'DocuSign', 'Salesforce', 'QuickBooks'] as $name): ?>
        <span class="integration-chip"><i class="bi bi-puzzle-fill text-purple"></i> <?= e($name) ?></span>
      <?php endforeach; ?>
    </div>
    <p class="text-muted mt-3">Plus a documented REST API and webhooks for custom workflows. <a href="<?= url('integrations') ?>">See all integrations</a></p>
  </div>
</section>

<!-- FAQ PREVIEW -->
<section class="py-5">
  <div class="container" style="max-width:820px">
    <div class="text-center"><span class="section-tag">FAQ</span><h2 class="section-title">Questions, answered</h2></div>
    <div class="accordion mt-4" id="faqHome">
      <?php $faqs = [
        ['Is my company data separated from other companies?', 'Yes. DocuFlow is multi-tenant: every record and file carries your company ID, and authorization is checked on every request and every file operation.'],
        ['Where are files stored?', 'Files live in private storage outside the public web root with randomized names. They are only served through authenticated, permission-checked downloads or expiring share tokens.'],
        ['Can I try before paying?', 'Every plan starts with a 14-day free trial. Paid plans are invoiced and recorded as pending until payment is verified — we never mark a payment successful without confirmation.'],
      ]; foreach ($faqs as $i => [$q, $a]): ?>
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button <?= $i ? 'collapsed' : '' ?>" data-bs-toggle="collapse" data-bs-target="#hf<?= $i ?>"><?= e($q) ?></button></h2>
        <div id="hf<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqHome"><div class="accordion-body text-muted"><?= e($a) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-band">
  <div class="container text-center py-5">
    <h2 class="text-white">Bring order to your company's documents today</h2>
    <p class="text-white-50">Set up your workspace in minutes. Your first 14 days are free.</p>
    <a class="btn btn-light btn-lg" href="<?= url('register') ?>">Create your company <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const hc = document.getElementById('heroChart');
if (hc) new Chart(hc, {type:'bar', data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat'], datasets:[{label:'Uploads', data:[24,38,31,46,52,40], backgroundColor:'#7c3aed', borderRadius:6},{label:'Approvals', data:[12,19,24,21,30,27], backgroundColor:'#1e3a5f', borderRadius:6}]}, options:{plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}}, y:{display:false}}, maintainAspectRatio:false}});
</script>
<?php partial('site-footer'); ?>
