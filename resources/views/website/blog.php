<?php $title = 'Blog — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero"><div class="container text-center py-5">
  <span class="section-tag">Blog</span><h1 class="section-title">Notes on documents, workflows & security</h1>
</div></section>
<section class="pb-5"><div class="container"><div class="row g-4">
  <?php $posts = [
    ['bi-folder2-open', 'Document Management', 'Why "final_v7.docx" is costing your business money', 'Version chaos is a process problem. Here is the folder-and-version system growing teams use to end it for good.'],
    ['bi-check2-square', 'Workflows', 'Approval workflows that people actually use', 'The secret is not more steps — it is fewer, clearer ones, with notifications that arrive where work happens.'],
    ['bi-shield-lock', 'Security', 'Share links without the anxiety: expiring tokens explained', 'Public links are convenient and risky. Expiry dates, download toggles and view counts make them safe.'],
    ['bi-tags', 'Productivity', 'Categories vs tags: how to organize 10,000 files', 'A practical taxonomy guide — when to use folders, when categories, and when tags win.'],
    ['bi-people', 'Teams', 'Permissions that scale: users, teams and roles', 'Grant access to teams, not individuals, and your permission model survives every re-org.'],
    ['bi-cloud-check', 'SaaS', 'Multi-tenant, explained simply', 'What tenant isolation really means, and the questions to ask any SaaS vendor about your data.'],
  ]; foreach ($posts as [$icon, $cat, $t, $d]): ?>
  <div class="col-md-6 col-lg-4">
    <a class="blog-card h-100" href="<?= url('blog/post') ?>">
      <div class="blog-thumb"><i class="bi <?= $icon ?>"></i></div>
      <div class="p-4">
        <span class="badge text-bg-light text-purple"><?= e($cat) ?></span>
        <h5 class="mt-2"><?= e($t) ?></h5>
        <p class="text-muted mb-0"><?= e($d) ?></p>
        <span class="read-more">Read article <i class="bi bi-arrow-right"></i></span>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div></div></section>
<?php partial('site-footer'); ?>
