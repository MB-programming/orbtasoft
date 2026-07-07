<?php
/** Expects (optional): $current_admin_page */
$admin = admin_require_login();
$current_admin_page = $current_admin_page ?? 'dashboard';

$adminNav = [
    'dashboard'    => ['label' => 'Dashboard', 'href' => '/admin/index.php', 'icon' => 'layout-dashboard'],
    'services'     => ['label' => 'Services', 'href' => '/admin/services.php', 'icon' => 'box'],
    'portfolio'    => ['label' => 'Portfolio', 'href' => '/admin/portfolio.php', 'icon' => 'layout-panel-top'],
    'blog'         => ['label' => 'Blog', 'href' => '/admin/blog.php', 'icon' => 'file-text'],
    'content'      => ['label' => 'Site Content', 'href' => '/admin/content.php', 'icon' => 'pencil'],
    'team'         => ['label' => 'Team', 'href' => '/admin/team.php', 'icon' => 'user-star'],
    'testimonials' => ['label' => 'Testimonials', 'href' => '/admin/testimonials.php', 'icon' => 'star'],
    'partners'     => ['label' => 'Partners', 'href' => '/admin/partners.php', 'icon' => 'briefcase'],
    'stack'        => ['label' => 'Tech Stack', 'href' => '/admin/stack.php', 'icon' => 'code-2'],
    'messages'     => ['label' => 'Contact Messages', 'href' => '/admin/messages.php', 'icon' => 'mail'],
    'newsletter'   => ['label' => 'Newsletter Subscribers', 'href' => '/admin/newsletter.php', 'icon' => 'send'],
    'settings'     => ['label' => 'Settings', 'href' => '/admin/settings.php', 'icon' => 'settings'],
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Orbtasoft</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="is-ltr admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="/admin/index.php" class="admin-sidebar__brand"><?= icon('brand-mark') ?> <span>Orbtasoft</span></a>
    <nav class="admin-sidebar__nav">
      <?php foreach ($adminNav as $key => $item): ?>
        <a href="<?= e($item['href']) ?>" class="<?= $current_admin_page === $key ? 'is-active' : '' ?>">
          <?= icon($item['icon']) ?> <span><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar__footer">
      <div class="admin-sidebar__user"><?= icon('user-star') ?> <span><?= e($admin['username']) ?></span></div>
      <a href="/admin/logout.php" class="admin-sidebar__logout"><?= icon('log-out') ?> <span>Logout</span></a>
      <a href="/index.php" class="admin-sidebar__view-site">&larr; View site</a>
    </div>
  </aside>
  <main class="admin-main">
