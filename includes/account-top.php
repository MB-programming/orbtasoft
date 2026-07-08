<?php
/** Expects: $current_account_page = 'overview'|'messages'|'wallet'|'invoices'|'reports'|'notifications' */
require_once __DIR__ . '/client.php';
$accountUser = client_require_login();
$current_account_page = $current_account_page ?? 'overview';
$current_page = 'account';

$accountNav = [
    'overview'      => ['label' => t('account_nav_overview'), 'href' => '/account/index.php', 'icon' => 'layout-dashboard'],
    'messages'      => ['label' => t('account_nav_messages'), 'href' => '/account/messages.php', 'icon' => 'mail', 'badge' => unread_message_count((int) $accountUser['id'])],
    'wallet'        => ['label' => t('account_nav_wallet'), 'href' => '/account/wallet.php', 'icon' => 'briefcase'],
    'invoices'      => ['label' => t('account_nav_invoices'), 'href' => '/account/invoices.php', 'icon' => 'file-text'],
    'reports'       => ['label' => t('account_nav_reports'), 'href' => '/account/reports.php', 'icon' => 'globe'],
    'notifications' => ['label' => t('account_nav_notifications'), 'href' => '/account/notifications.php', 'icon' => 'send', 'badge' => unread_notification_count((int) $accountUser['id'])],
];

require __DIR__ . '/header.php';
?>

<main>
  <section class="account-shell">
    <div class="container account-shell__inner">
      <aside class="account-sidebar">
        <div class="account-sidebar__user">
          <div class="account-sidebar__avatar"><?= e(initials($accountUser['name'])) ?></div>
          <div>
            <p class="account-sidebar__name"><?= e($accountUser['name']) ?></p>
            <p class="account-sidebar__email"><?= e($accountUser['email']) ?></p>
          </div>
        </div>
        <nav class="account-nav">
          <?php foreach ($accountNav as $key => $item): ?>
            <a href="<?= e($item['href']) ?>" class="<?= $current_account_page === $key ? 'is-active' : '' ?>">
              <?= icon($item['icon']) ?>
              <span><?= e($item['label']) ?></span>
              <?php if (!empty($item['badge'])): ?><span class="account-nav__badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </nav>
        <a href="/auth-logout.php" class="account-sidebar__logout"><?= icon('log-out') ?> <span><?= e(t('nav_logout')) ?></span></a>
      </aside>

      <div class="account-main">
