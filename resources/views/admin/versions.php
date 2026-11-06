<?php $pageTitle = 'Version Control'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="panel mb-4">
  <form class="d-flex gap-2 align-items-end" method="get">
    <div class="flex-grow-1"><label class="form-label">Document</label>
      <select class="form-select" name="doc" onchange="this.form.submit()">
        <?php foreach ($docs as $d): ?><option value="<?= (int) $d['id'] ?>" <?= $doc && (int) $doc['id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?>.<?= e($d['extension']) ?></option><?php endforeach; ?>
      </select></div>
    <a class="btn btn-outline-navy" href="<?= $doc ? url('app/documents/' . (int) $doc['id']) : url('admin/files') ?>">Open document page</a>
  </form>
</div>

<?php if ($doc): ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-clock-history me-1"></i><?= e($doc['name']) ?> — history</h5>
        <span class="text-muted small"><?= count($versions) ?> version(s) · all preserved</span></div>
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Version</th><th>Size</th><th>Uploaded by</th><th>Note</th><th>Date</th><th></th></tr></thead><tbody>
        <?php foreach ($versions as $v): $isCurrent = $v['stored_name'] === $doc['stored_name']; ?>
        <tr class="<?= $isCurrent ? 'table-light fw-semibold' : '' ?>">
          <td>v<?= (int) $v['version_number'] ?> <?= $isCurrent ? '<span class="badge text-bg-success">current</span>' : '' ?></td>
          <td><?= human_bytes((int) $v['size_bytes']) ?></td><td><?= e($v['uploader']) ?></td><td><?= e($v['note'] ?? '') ?></td>
          <td><?= e(substr((string) $v['created_at'], 0, 16)) ?></td>
          <td><?php if (!$isCurrent): ?>
            <form method="post" action="<?= url('app/documents/' . (int) $doc['id'] . '/version/' . (int) $v['id'] . '/restore') ?>"><?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-secondary">Restore</button></form><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody></table>
      <div class="p-3 border-top">
        <form method="post" enctype="multipart/form-data" action="<?= url('app/documents/' . (int) $doc['id'] . '/version') ?>" class="d-flex gap-2">
          <?= csrf_field() ?><input type="file" class="form-control" name="file" required>
          <input class="form-control" name="note" placeholder="Change note" style="max-width:220px">
          <button class="btn btn-purple">Upload new version</button></form>
        <p class="small text-muted mt-2 mb-0">Uploading keeps every older version intact and restorable.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel" data-df-vcompare>
      <div class="panel-head"><h5><i class="bi bi-arrow-left-right me-1"></i>Compare versions</h5></div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label small">Version A</label>
          <select class="form-select" data-df-vcompare-a>
            <?php foreach ($versions as $v): ?><option value="<?= (int) $v['id'] ?>" data-size="<?= human_bytes((int) $v['size_bytes']) ?>" data-by="<?= e($v['uploader']) ?>" data-date="<?= e(substr((string) $v['created_at'], 0, 16)) ?>" data-note="<?= e($v['note'] ?? '') ?>">v<?= (int) $v['version_number'] ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-6"><label class="form-label small">Version B</label>
          <select class="form-select" data-df-vcompare-b>
            <?php foreach ($versions as $v): ?><option value="<?= (int) $v['id'] ?>" data-size="<?= human_bytes((int) $v['size_bytes']) ?>" data-by="<?= e($v['uploader']) ?>" data-date="<?= e(substr((string) $v['created_at'], 0, 16)) ?>" data-note="<?= e($v['note'] ?? '') ?>" <?= (int) $v['version_number'] === 1 ? 'selected' : '' ?>>v<?= (int) $v['version_number'] ?></option><?php endforeach; ?>
          </select></div>
      </div>
      <table class="table table-sm mb-0">
        <thead><tr><th>Field</th><th>A</th><th>B</th></tr></thead><tbody>
        <tr data-df-vrow="size"><td>Size</td><td data-df-vc="a-size">—</td><td data-df-vc="b-size">—</td></tr>
        <tr data-df-vrow="by"><td>Uploaded by</td><td data-df-vc="a-by">—</td><td data-df-vc="b-by">—</td></tr>
        <tr data-df-vrow="date"><td>Date</td><td data-df-vc="a-date">—</td><td data-df-vc="b-date">—</td></tr>
        <tr data-df-vrow="note"><td>Change note</td><td data-df-vc="a-note">—</td><td data-df-vc="b-note">—</td></tr>
        </tbody></table>
      <p class="small text-muted mt-2 mb-0">Metadata comparison of the two selected versions; differing rows are highlighted. Current file metadata: <?= human_bytes((int) $doc['size_bytes']) ?> · updated <?= e(substr((string) $doc['updated_at'], 0, 16)) ?>.</p>
    </div>
  </div>
</div>
<?php else: ?>
<div class="panel"><p class="text-muted mb-0">No documents yet.</p></div>
<?php endif; ?>
<?php partial('app-close'); ?>
