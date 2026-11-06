<?php $title = 'Features — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Features</span><h1 class="section-title">Everything your documents need</h1>
  <p class="text-muted mx-auto" style="max-width:580px">Deep-dive into the file manager, collaboration, approvals, versioning and controls that make DocuFlow a platform — not a folder.</p>
</div></section>
<section class="pb-5"><div class="container">
  <?php $blocks = [
    ['File Manager', 'bi-folder2-open', 'A fast grid & list file manager with nested folders, color-coded organization, drag-and-drop multi-file upload, rename / move / copy / delete, and a trash with restore-by-design (soft delete) plus permanent purge for admins.'],
    ['Document Preview', 'bi-eye', 'Preview PDFs and images inline without downloading. Downloads are always permission-checked and counted, and every access lands in the audit log.'],
    ['Version Control', 'bi-clock-history', 'Upload a new version without breaking existing links. View the full history with uploader, size and notes — and restore any earlier version in one click, up to your plan limit.'],
    ['Comments & Collaboration', 'bi-chat-dots', 'Discuss documents where they live. Comments are attached to the file, timestamped, and visible to everyone with access.'],
    ['Approval Workflows', 'bi-check2-square', 'Send any document for approval. Approvers get notified, decide with comments, and the requester sees the outcome instantly. Multi-step-ready data model, full decision trail.'],
    ['Sharing & Permissions', 'bi-link-45deg', 'Two sharing models: internal permissions per user or team (view / download / edit), and public links protected by random tokens with expiry dates, view counts and revoke.'],
    ['Search & Discovery', 'bi-search', 'Global search across document names, descriptions, categories and tags with sorting and pagination — plus advanced filtering by category and tag.'],
    ['Reports & Exports', 'bi-bar-chart', 'Real reports from your own data: uploads per month, top downloads, storage by file type and by user, approval outcomes — exportable to CSV (Excel-compatible).'],
    ['Security & Audit', 'bi-shield-lock', 'Private storage outside the web root, extension + MIME validation, randomized stored filenames, path-traversal protection, CSRF tokens, login rate limiting, and both activity and audit logs.'],
  ]; ?>
  <div class="row g-4">
    <?php foreach ($blocks as [$t, $icon, $d]): ?>
    <div class="col-md-6 col-lg-4"><div class="feature-card h-100">
      <div class="feature-icon"><i class="bi <?= $icon ?>"></i></div>
      <h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p>
    </div></div>
    <?php endforeach; ?>
  </div>
  <div class="cta-inline mt-5"><h4>See it with your own files</h4><p class="text-muted">Create a free company workspace — it takes two minutes.</p>
  <a class="btn btn-purple btn-lg" href="<?= url('register') ?>">Start free trial</a></div>
</div></section>
<?php partial('site-footer'); ?>
