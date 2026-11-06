<?php $pageTitle = 'Teams'; partial('app-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle')); ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel mb-4">
      <div class="panel-head"><h5>Create team</h5></div>
      <form method="post" action="<?= url('app/teams') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label class="form-label">Team name</label><input class="form-control mb-3" name="name" required placeholder="e.g. Finance">
        <label class="form-label">Description</label><input class="form-control mb-3" name="description">
        <button class="btn btn-purple">Create team</button>
      </form>
    </div>
    <div class="panel">
      <div class="panel-head"><h5>Your teams</h5></div>
      <?php foreach ($teams as $t): ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <span><strong><?= e($t['name']) ?></strong><small class="d-block text-muted"><?= e($t['description'] ?? '') ?></small></span>
        <span class="d-flex gap-2 align-items-center"><span class="badge text-bg-light"><?= (int) $t['members'] ?> members</span>
        <form method="post" action="<?= url('app/teams') ?>" onsubmit="return confirm('Delete this team?')"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="team_id" value="<?= (int) $t['id'] ?>">
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></span>
      </div>
      <?php endforeach; ?>
      <?php if (!$teams): ?><p class="text-muted mb-0">No teams yet.</p><?php endif; ?>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-head"><h5>Team members</h5></div>
      <form method="post" action="<?= url('app/teams') ?>" class="row g-2 mb-3">
        <?= csrf_field() ?><input type="hidden" name="action" value="member_add">
        <div class="col-md-5"><select class="form-select" name="team_id"><?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-5"><select class="form-select" name="user_id"><?php foreach ($tenantUsers as $tu): ?><option value="<?= (int) $tu['id'] ?>"><?= e($tu['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><button class="btn btn-purple w-100">Add</button></div>
      </form>
      <table class="table table-sm align-middle mb-0"><thead><tr><th>Member</th><th>Team</th><th>Role</th><th></th></tr></thead><tbody>
      <?php foreach ($members as $m): ?>
        <tr><td><?= e($m['name']) ?><small class="d-block text-muted"><?= e($m['email']) ?></small></td>
          <td><?= e($m['team_name']) ?></td><td><?= e($m['role']) ?></td>
          <td><form method="post" action="<?= url('app/teams') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="member_remove">
            <input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button></form></td></tr>
      <?php endforeach; ?>
      <?php if (!$members): ?><tr><td colspan="4" class="text-muted">No team members yet.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
</div>
<?php partial('app-close'); ?>
