<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'DocuFlow Cloud — Document Management SaaS') ?></title>
<meta name="description" content="DocuFlow Cloud — secure multi-tenant document management: upload, organize, share, approve and version documents in the cloud.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/site.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand brand" href="<?= url('') ?>"><span class="brand-mark"><i class="bi bi-cloud-check-fill"></i></span> DocuFlow <span class="text-purple">Cloud</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="<?= url('features') ?>">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('solutions') ?>">Solutions</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('industries') ?>">Industries</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('integrations') ?>">Integrations</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('security') ?>">Security</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('pricing') ?>">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('blog') ?>">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('contact') ?>">Contact</a></li>
        <li class="nav-item ms-lg-3"><a class="btn btn-outline-navy" href="<?= url('login') ?>">Sign in</a></li>
        <li class="nav-item"><a class="btn btn-purple" href="<?= url('register') ?>">Start free trial</a></li>
      </ul>
    </div>
  </div>
</nav>
<?php $ann = null; try { $ann = Database::one('SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 1'); } catch (Throwable) {} ?>
<?php if ($ann): ?><div class="announce-bar text-center py-2 px-3"><i class="bi bi-megaphone me-2"></i><strong><?= e($ann['title']) ?></strong> — <?= e($ann['body']) ?></div><?php endif; ?>
<main>
