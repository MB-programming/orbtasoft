<?php
$current_account_page = 'overview';
require __DIR__ . '/../includes/account-top.php';

$projects = client_projects((int) $accountUser['id']);
$activeCount = count(array_filter($projects, fn($p) => $p['status'] !== 'completed'));
$unreadMessages = unread_message_count((int) $accountUser['id']);
$balance = wallet_balance((int) $accountUser['id']);
$invoices = client_invoices((int) $accountUser['id']);
$outstandingCount = count(array_filter($invoices, fn($i) => in_array($i['status'], ['sent', 'overdue'], true)));
?>

<div class="account-page-head">
  <h1><?= e(t('account_welcome_back')) ?>, <?= e($accountUser['name']) ?></h1>
  <p><?= e(t('account_overview_desc')) ?></p>
</div>

<div class="account-stats">
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('account_active_projects')) ?></div>
    <div class="account-stat-card__value"><?= (int) $activeCount ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('account_unread_messages')) ?></div>
    <div class="account-stat-card__value"><?= (int) $unreadMessages ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('account_wallet_balance')) ?></div>
    <div class="account-stat-card__value"><?= e(format_money($balance)) ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('account_outstanding_invoices')) ?></div>
    <div class="account-stat-card__value"><?= (int) $outstandingCount ?></div>
  </div>
</div>

<div class="account-panel">
  <h2><?= e(t('account_nav_projects')) ?></h2>
  <?php if (!$projects): ?>
    <div class="account-empty"><?= e(t('account_no_projects')) ?></div>
  <?php else: ?>
    <?php foreach ($projects as $project): ?>
      <div class="project-card">
        <div class="project-card__head">
          <span class="project-card__title"><?= e($project['title']) ?></span>
          <span class="project-status project-status--<?= e($project['status']) ?>"><?= e(project_status_label($project['status'])) ?></span>
        </div>
        <?php if ($project['description']): ?><p class="project-card__desc"><?= e($project['description']) ?></p><?php endif; ?>
        <div class="project-progress">
          <div class="project-progress__track"><div class="project-progress__fill" style="width: <?= (int) $project['progress'] ?>%;"></div></div>
          <span class="project-progress__pct"><?= (int) $project['progress'] ?>%</span>
        </div>
        <?php if ($project['milestones']): ?>
          <div class="project-milestones">
            <?php foreach ($project['milestones'] as $m): ?>
              <div class="project-milestone <?= $m['is_done'] ? 'is-done' : '' ?>">
                <span class="project-milestone__dot"><?= $m['is_done'] ? icon('check') : '' ?></span>
                <span><?= e($m['title']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="project-card__meta"><?= e(t('account_project_updated')) ?>: <?= e(format_date($project['updated_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
