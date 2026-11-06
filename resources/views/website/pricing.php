<?php $title = 'Pricing — DocuFlow Cloud'; partial('site-header', compact('title')); ?>
<section class="page-hero">
  <div class="container text-center py-5">
    <span class="section-tag">Pricing</span>
    <h1 class="section-title">Simple pricing for document-heavy teams</h1>
    <p class="text-muted mx-auto" style="max-width:560px">Start free for 14 days. Upgrade, downgrade or cancel anytime — plan limits apply to storage, users, file size, teams and version history.</p>
    <?php partial('pricing-toggle'); ?>
  </div>
</section>
<section class="pb-5">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <?php foreach ($plans as $p): ?>
      <div class="col-md-6 col-lg">
        <div class="price-card h-100 <?= $p['slug'] === 'professional' ? 'featured' : '' ?>">
          <?php if ($p['slug'] === 'professional'): ?><span class="price-badge">Most popular</span><?php endif; ?>
          <h5><?= e($p['name']) ?></h5>
          <div class="price-num">$<span data-monthly="<?= e($p['price_monthly']) ?>" data-yearly="<?= e($p['price_yearly']) ?>"><?= e($p['price_monthly']) ?></span><small class="billing-note">/mo</small></div>
          <p class="text-muted small">Billed <span class="billing-note">monthly</span></p>
          <ul class="check-list small">
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['storage_mb'] < 0 ? 'Unlimited storage' : human_bytes((int) $p['storage_mb'] * 1024 * 1024) . ' storage' ?></li>
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['max_users'] < 0 ? 'Unlimited users' : (int) $p['max_users'] . ' team users' ?></li>
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['max_file_mb'] < 0 ? 'Unlimited file size' : 'Files up to ' . (int) $p['max_file_mb'] . ' MB' ?></li>
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['max_teams'] < 0 ? 'Unlimited teams' : (int) $p['max_teams'] . ' teams' ?></li>
            <li><i class="bi bi-check-circle-fill"></i> <?= (int) $p['version_history'] ?> document versions</li>
            <?php foreach (array_filter(array_map('trim', explode(',', (string) $p['features']))) as $f): ?>
            <li><i class="bi bi-check-circle-fill"></i> <?= e($f) ?></li>
            <?php endforeach; ?>
          </ul>
          <a class="btn <?= $p['slug'] === 'professional' ? 'btn-purple' : 'btn-outline-navy' ?> w-100" href="<?= url('register') ?>">Start 14-day trial</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="compare-card mt-5">
      <h4 class="mb-3">How billing works</h4>
      <div class="row g-4 text-muted">
        <div class="col-md-4"><i class="bi bi-receipt text-purple fs-4"></i><p class="mt-2 mb-0"><strong class="text-dark">Invoices first.</strong> Changing plans creates an invoice and a payment recorded as <em>pending</em>.</p></div>
        <div class="col-md-4"><i class="bi bi-patch-check text-purple fs-4"></i><p class="mt-2 mb-0"><strong class="text-dark">Verified payments only.</strong> A payment is marked successful only after verified confirmation — never automatically.</p></div>
        <div class="col-md-4"><i class="bi bi-arrow-repeat text-purple fs-4"></i><p class="mt-2 mb-0"><strong class="text-dark">Grace period.</strong> If a period lapses, you get a 7-day grace window before the subscription expires.</p></div>
      </div>
      <p class="mt-3 mb-0">Have a coupon? Apply codes like <code>WELCOME10</code> when you change plans inside your workspace.</p>
    </div>
  </div>
</section>
<?php partial('site-footer'); ?>
