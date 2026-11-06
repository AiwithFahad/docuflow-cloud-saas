<?php $pageTitle = 'Teams'; partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree')); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <?php foreach ($teams as $t): ?>
    <div class="panel mb-4">
      <div class="panel-head"><h5><i class="bi bi-people me-1"></i><?= e($t['name']) ?></h5>
        <span class="text-muted small"><?= (int) $t['members'] ?> member(s)</span></div>
      <p class="text-muted small"><?= e($t['description'] ?? '') ?></p>
      <?php foreach ($members as $m): if ((int) $m['team_id'] !== (int) $t['id']) continue; ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <span><span class="avatar me-1"><?= e(strtoupper(substr($m['name'], 0, 1))) ?></span><?= e($m['name']) ?> <span class="text-muted small"><?= e($m['email']) ?> · <?= e($m['role']) ?></span></span>
        <form method="post" action="<?= url('admin/teams') ?>"><?= csrf_field() ?>
          <input type="hidden" name="action" value="member_remove"><input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Remove</button></form>
      </div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('admin/teams') ?>" class="d-flex gap-2 mt-3">
        <?= csrf_field() ?><input type="hidden" name="action" value="member_add"><input type="hidden" name="team_id" value="<?= (int) $t['id'] ?>">
        <select class="form-select" name="user_id"><?php foreach ($tenantUsers as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-outline-navy">Add member</button>
      </form>
      <form method="post" action="<?= url('admin/teams') ?>" class="mt-2" onsubmit="return confirm('Delete this team? Members keep their accounts; team document permissions are removed.')">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="team_id" value="<?= (int) $t['id'] ?>">
        <button class="btn btn-sm btn-link text-danger p-0">Delete team</button></form>
    </div>
    <?php endforeach; ?>
    <?php if (!$teams): ?><div class="panel"><p class="text-muted mb-0">No teams yet — create the first one.</p></div><?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="panel"><div class="panel-head"><h5>Create team</h5></div>
      <form method="post" action="<?= url('admin/teams') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label class="form-label">Name</label><input class="form-control mb-3" name="name" required>
        <label class="form-label">Description</label><input class="form-control mb-3" name="description">
        <button class="btn btn-purple w-100">Create team</button>
      </form>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
