<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Super Admin') ?> — DocuFlow Cloud</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
  <aside class="app-sidebar super" id="appSidebar">
    <a class="sidebar-brand" href="<?= url('super') ?>"><span class="brand-mark"><i class="bi bi-cloud-check-fill"></i></span>
      <span class="sidebar-brand-text">DocuFlow <small class="d-block text-white-50">Super Admin</small></span></a>
    <nav class="sidebar-nav">
      <a href="<?= url('super') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('super/analytics') ?>"><i class="bi bi-graph-up-arrow"></i> Analytics</a>
      <a href="<?= url('super/tenants') ?>"><i class="bi bi-buildings"></i> Tenants</a>
      <a href="<?= url('super/users') ?>"><i class="bi bi-people"></i> Users</a>
      <a href="<?= url('super/plans') ?>"><i class="bi bi-layers"></i> Plans & Coupons</a>
      <a href="<?= url('super/payments') ?>"><i class="bi bi-credit-card-2-front"></i> Payments</a>
      <a href="<?= url('super/invoices') ?>"><i class="bi bi-receipt"></i> Invoices</a>
      <a href="<?= url('super/tickets') ?>"><i class="bi bi-life-preserver"></i> Support Tickets</a>
      <a href="<?= url('super/announcements') ?>"><i class="bi bi-megaphone"></i> Announcements</a>
      <a href="<?= url('super/logs') ?>"><i class="bi bi-journal-text"></i> Activity Logs</a>
      <a href="<?= url('super/settings') ?>"><i class="bi bi-gear"></i> Platform Settings</a>
    </nav>
  </aside>
  <div class="app-main">
    <header class="app-topbar">
      <button class="btn btn-light d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
      <strong class="ms-2"><?= e($pageTitle ?? 'Super Admin') ?></strong>
      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="badge text-bg-warning"><i class="bi bi-shield-fill-check me-1"></i>Platform owner</span>
        <div class="dropdown">
          <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <span class="avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
            <span class="d-none d-md-block fw-semibold small"><?= e($user['name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="app-content">
      <?php partial('flash'); ?>
