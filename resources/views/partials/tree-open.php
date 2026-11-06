<?php
// Shared shell for the role dashboard trees (admin / manager / employee).
// Same DocuFlow design language as partials/app-open; only the navigation
// and the search target differ per tree. Expects: $tree, $user, $tenant,
// $sub, $unread, $notifs, $pageTitle.
$tree = $tree ?? 'employee';
$navs = [
  'admin' => [
    ['admin/dashboard', 'bi-speedometer2', 'Dashboard'],
    ['admin/files', 'bi-folder2-open', 'Files'],
    ['admin/folders', 'bi-folder', 'Folders'],
    ['admin/categories', 'bi-tags', 'Categories & Depts'],
    ['admin/teams', 'bi-people', 'Teams'],
    ['admin/users', 'bi-person-gear', 'Users'],
    ['admin/sharing', 'bi-share', 'Sharing'],
    ['admin/approvals', 'bi-check2-square', 'Approvals'],
    ['admin/versions', 'bi-clock-history', 'Versions'],
    ['_h', '', 'Insights'],
    ['admin/storage', 'bi-hdd-stack', 'Storage'],
    ['admin/reports', 'bi-bar-chart', 'Reports'],
    ['admin/audit-logs', 'bi-shield-lock', 'Audit Logs'],
    ['_h', '', 'Workspace'],
    ['app', 'bi-grid-1x2', 'Classic workspace'],
    ['app/subscription', 'bi-credit-card', 'Subscription'],
    ['admin/settings', 'bi-gear', 'Settings'],
  ],
  'manager' => [
    ['manager/dashboard', 'bi-speedometer2', 'Dashboard'],
    ['manager/files', 'bi-folder2-open', 'Team Files'],
    ['manager/approvals', 'bi-check2-square', 'Approvals'],
    ['manager/notifications', 'bi-bell', 'Notifications'],
    ['_h', '', 'Workspace'],
    ['app/upload', 'bi-cloud-upload', 'Upload'],
    ['app/profile', 'bi-person-circle', 'Profile'],
  ],
  'employee' => [
    ['employee/dashboard', 'bi-speedometer2', 'Dashboard'],
    ['employee/files', 'bi-folder2-open', 'My Files'],
    ['employee/approvals', 'bi-check2-square', 'Approvals'],
    ['employee/notifications', 'bi-bell', 'Notifications'],
    ['_h', '', 'Workspace'],
    ['app/upload', 'bi-cloud-upload', 'Upload'],
    ['app/profile', 'bi-person-circle', 'Profile'],
  ],
];
$searchAction = ['admin' => 'admin/files', 'manager' => 'manager/files', 'employee' => 'employee/files'][$tree];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="app-base" content="<?= e(url('')) ?>">
<title><?= e($pageTitle ?? 'Dashboard') ?> · <?= e($tenant['name'] ?? 'DocuFlow') ?> — DocuFlow Cloud</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
<style>:root{--brand: <?= e($tenant['brand_color'] ?? '#1e3a5f') ?>;}</style>
</head>
<body>
<div class="app-shell">
  <aside class="app-sidebar" id="appSidebar">
    <a class="sidebar-brand" href="<?= url($tree . '/dashboard') ?>">
      <?php if (!empty($tenant['logo'])): ?><img src="<?= url($tenant['logo']) ?>" alt="" class="sidebar-logo">
      <?php else: ?><span class="brand-mark"><i class="bi bi-cloud-check-fill"></i></span><?php endif; ?>
      <span class="sidebar-brand-text"><?= e($tenant['name'] ?? 'DocuFlow') ?></span>
    </a>
    <nav class="sidebar-nav">
      <?php foreach ($navs[$tree] as $item): ?>
        <?php if ($item[0] === '_h'): ?><div class="sidebar-heading"><?= e($item[2]) ?></div>
        <?php else: ?><a href="<?= url($item[0]) ?>"><i class="bi <?= e($item[1]) ?>"></i> <?= e($item[2]) ?></a><?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php if (!empty($sub)): ?>
    <div class="sidebar-plan">
      <div class="d-flex justify-content-between small"><span><?= e($sub['plan_name']) ?> plan</span><a href="<?= url('app/subscription') ?>">Upgrade</a></div>
      <div class="text-white-50 small mt-1"><?= e(ucfirst(SubscriptionService::effectiveStatus($sub))) ?> · renews <?= e(substr((string) $sub['current_period_end'], 0, 10)) ?></div>
    </div>
    <?php endif; ?>
  </aside>
  <div class="app-main">
    <header class="app-topbar">
      <button class="btn btn-light d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
      <form class="topbar-search d-none d-md-flex" method="get" action="<?= url($searchAction) ?>">
        <i class="bi bi-search"></i>
        <input type="search" name="q" placeholder="Search documents…" value="<?= e($_GET['q'] ?? '') ?>">
      </form>
      <div class="ms-auto d-flex align-items-center gap-2">
        <a class="btn btn-purple btn-sm" href="<?= url('app/upload') ?>"><i class="bi bi-cloud-upload me-1"></i> Upload</a>
        <div class="dropdown">
          <button class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <?php if (!empty($unread)): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int) $unread ?></span><?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0 notif-menu">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center"><strong>Notifications</strong>
              <form method="post" action="<?= url('app/notifications/read') ?>"><?= csrf_field() ?><button class="btn btn-link btn-sm p-0">Mark all read</button></form>
            </div>
            <?php foreach (($notifs ?? []) as $n): ?>
              <div class="px-3 py-2 border-bottom <?= $n['is_read'] ? '' : 'bg-light' ?>">
                <div class="fw-semibold small"><?= e($n['title']) ?></div>
                <div class="small text-muted"><?= e($n['body']) ?></div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($notifs)): ?><div class="p-3 text-muted small">No notifications yet.</div><?php endif; ?>
          </div>
        </div>
        <div class="dropdown">
          <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <span class="avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
            <span class="d-none d-md-block text-start"><span class="d-block fw-semibold small lh-1"><?= e($user['name']) ?></span>
            <span class="text-muted small lh-1"><?= e(ucwords(str_replace('_', ' ', $user['role']))) ?></span></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= url('app/profile') ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="app-content">
      <?php partial('flash'); ?>
