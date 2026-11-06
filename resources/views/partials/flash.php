<?php $ok = flash('success'); $err = flash('error'); ?>
<?php if ($ok): ?><div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <i class="bi bi-check-circle me-2"></i><?= e($ok) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
