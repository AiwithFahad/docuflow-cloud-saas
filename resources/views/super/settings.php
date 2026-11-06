<?php $pageTitle = 'Platform Settings'; partial('super-open', compact('user', 'pageTitle')); ?>
<div class="row justify-content-center"><div class="col-lg-8">
  <div class="panel">
    <h4 class="mb-1">Platform settings</h4>
    <p class="text-muted">Key-value settings used across the platform (support email, trial length messaging, maintenance mode…).</p>
    <form method="post" action="<?= url('super/settings') ?>">
      <?= csrf_field() ?>
      <?php foreach ($settings as $s): ?>
      <label class="form-label"><code><?= e($s['setting_key']) ?></code></label>
      <input class="form-control mb-3" name="settings[<?= e($s['setting_key']) ?>]" value="<?= e($s['setting_value']) ?>">
      <?php endforeach; ?>
      <label class="form-label">Add / update key</label>
      <div class="input-group mb-4">
        <input class="form-control" name="settings_new_key" placeholder="new_key" id="newKey">
        <input class="form-control" placeholder="value" id="newVal">
        <button class="btn btn-outline-secondary" type="button" onclick="addSetting()">Add</button>
      </div>
      <div id="extraSettings"></div>
      <button class="btn btn-purple btn-lg">Save settings</button>
    </form>
  </div>
</div></div>
<script>
function addSetting() {
  const k = document.getElementById('newKey').value.trim(), v = document.getElementById('newVal').value;
  if (!k) return;
  const wrap = document.getElementById('extraSettings');
  const label = document.createElement('label'); label.className = 'form-label'; label.innerHTML = '<code></code>'; label.firstChild.textContent = k;
  const input = document.createElement('input'); input.className = 'form-control mb-3'; input.name = 'settings[' + k + ']'; input.value = v;
  wrap.append(label, input);
  document.getElementById('newKey').value = ''; document.getElementById('newVal').value = '';
}
</script>
<?php partial('app-close'); ?>
