<?php $title = 'Integrations — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Integrations</span><h1 class="section-title">Connect DocuFlow to your stack</h1>
  <p class="text-muted mx-auto" style="max-width:580px">Import from the tools you already use, notify where your team already talks, and automate with our REST API and webhooks.</p>
</div></section>
<section class="pb-5"><div class="container"><div class="row g-4">
  <?php $ints = [
    ['Google Drive', 'One-click import of folders and files with metadata preserved.'],
    ['Microsoft 365', 'Open Office documents and sync shared libraries both ways.'],
    ['Dropbox', 'Migrate existing Dropbox folders into permissioned workspaces.'],
    ['Slack', 'Approval requests and share notifications delivered to channels.'],
    ['DocuSign', 'Send documents for e-signature and file signed copies automatically.'],
    ['Zapier', 'Trigger 5,000+ app workflows on upload, approval and share events.'],
    ['Salesforce', 'Attach account documents directly to CRM records.'],
    ['QuickBooks', 'Keep invoices and receipts linked to your accounting.'],
    ['REST API + Webhooks', 'Programmatic uploads, metadata and event hooks for custom systems.'],
  ]; foreach ($ints as [$t, $d]): ?>
  <div class="col-md-6 col-lg-4"><div class="feature-card h-100"><div class="feature-icon"><i class="bi bi-puzzle-fill"></i></div><h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p></div></div>
  <?php endforeach; ?>
</div>
<div class="compare-card mt-5"><h4>Developers</h4>
<p class="text-muted mb-2">Authenticate with API tokens, upload via multipart POST, and subscribe to <code>document.created</code>, <code>approval.decided</code> and <code>share.accessed</code> webhooks. Full details live in <code>docs/API.md</code> inside the repository.</p>
<a class="btn btn-outline-navy" href="<?= url('register') ?>">Get API access with a free trial</a></div>
</div></section>
<?php partial('site-footer'); ?>
