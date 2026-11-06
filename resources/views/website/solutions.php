<?php $title = 'Solutions — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Solutions</span><h1 class="section-title">One workspace, every department</h1>
  <p class="text-muted mx-auto" style="max-width:580px">Folder templates, permissions and approvals adapt DocuFlow to how each team already works.</p>
</div></section>
<section class="pb-5"><div class="container"><div class="row g-4">
  <?php $sols = [
    ['bi-cash-stack', 'Finance & Accounting', 'Invoices, statements and contracts in nested folders with approval routing to finance leads and a complete audit trail for auditors.'],
    ['bi-people', 'Human Resources', 'Employee files, policies and signed forms with strict per-team permissions — only HR sees what HR should see.'],
    ['bi-scale', 'Legal & Compliance', 'Versioned contracts with expiring external share links, decision trails and retention-friendly trash controls.'],
    ['bi-kanban', 'Operations', 'SOPs, checklists and vendor documents kept current with version restore and comments where the work happens.'],
    ['bi-megaphone', 'Sales & Marketing', 'Proposals and collateral shared via token links with view counts — know when a prospect opened your deck.'],
    ['bi-building-gear', 'Company-wide', 'A single source of truth: global search, categories and tags replace the "final_v7_REAL.docx" guessing game.'],
  ]; foreach ($sols as [$icon, $t, $d]): ?>
  <div class="col-md-6 col-lg-4"><div class="feature-card h-100"><div class="feature-icon"><i class="bi <?= $icon ?>"></i></div><h5><?= e($t) ?></h5><p class="text-muted mb-0"><?= e($d) ?></p></div></div>
  <?php endforeach; ?>
</div></div></section>
<?php partial('site-footer'); ?>
