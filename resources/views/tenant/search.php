<?php $pageTitle = 'Search'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="panel mb-4">
  <form method="get" action="<?= url('app/search') ?>" class="row g-2 align-items-end">
    <div class="col-md-5"><label class="form-label">Search documents</label>
      <input class="form-control form-control-lg" name="q" value="<?= e($q) ?>" placeholder="Name or description…"></div>
    <div class="col-md-2"><label class="form-label">Category</label>
      <select class="form-select" name="category"><option value="">All</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (($_GET['category'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Tag</label>
      <select class="form-select" name="tag"><option value="">All</option>
        <?php foreach ($tags as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (($_GET['tag'] ?? '') == $t['id']) ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Sort by</label>
      <select class="form-select" name="sort">
        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
        <option value="size" <?= $sort === 'size' ? 'selected' : '' ?>>Size</option>
      </select></div>
    <div class="col-md-1"><button class="btn btn-purple w-100"><i class="bi bi-search"></i></button></div>
  </form>
</div>

<p class="text-muted"><?= number_format($total) ?> result(s)<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?></p>
<div class="panel p-0">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Category</th><th>Owner</th><th>Size</th><th>Updated</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($docs as $d): ?>
      <tr>
        <td><i class="bi <?= e(FileSecurity::iconFor((string) $d['extension'])) ?> me-2"></i><a href="<?= url('app/documents/' . (int) $d['id']) ?>"><?= e($d['name']) ?></a>
          <small class="d-block text-muted"><?= e($d['description'] ?? '') ?></small></td>
        <td><?= e($d['category_name'] ?? '—') ?></td><td><?= e($d['owner_name']) ?></td>
        <td><?= human_bytes($d['size_bytes']) ?></td><td><?= e(substr((string) $d['updated_at'], 0, 10)) ?></td>
        <td><a class="btn btn-sm btn-light" href="<?= url('app/documents/' . (int) $d['id'] . '/download') ?>"><i class="bi bi-download"></i></a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$docs): ?><tr><td colspan="6" class="text-center text-muted py-4">No documents match your search.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($pg['pages'] > 1): ?>
<nav class="mt-3"><ul class="pagination">
  <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
  <li class="page-item <?= $i === $pg['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= url('app/search?q=' . urlencode($q) . '&page=' . $i) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php partial('app-close'); ?>
