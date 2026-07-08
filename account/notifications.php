<?php
$current_account_page = 'notifications';
require __DIR__ . '/../includes/account-top.php';

$userId = (int) $accountUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all_read' && csrf_valid($_POST['csrf_token'] ?? '')) {
    $pdo = get_db();
    if ($pdo) {
        $stmt = $pdo->prepare('UPDATE client_notifications SET is_read = 1 WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
    }
    header('Location: /account/notifications.php');
    exit;
}

$notifications = client_notifications($userId);
?>

<div class="account-page-head">
  <h1><?= e(t('notifications_heading')) ?></h1>
</div>

<div class="account-panel">
  <?php if (!$notifications): ?>
    <div class="account-empty"><?= e(t('notifications_empty')) ?></div>
  <?php else: ?>
    <form method="post" action="/account/notifications.php" class="admin-form-actions" style="margin-block-end:20px;">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="mark_all_read">
      <button type="submit" class="btn btn--outline btn--sm"><?= e(t('notifications_mark_all_read')) ?></button>
    </form>
    <?php foreach ($notifications as $n): ?>
      <div class="notification-item <?= $n['is_read'] ? '' : 'is-unread' ?>">
        <?php if (!$n['is_read']): ?><span class="notification-item__dot"></span><?php else: ?><span style="width:8px;flex-shrink:0;"></span><?php endif; ?>
        <div>
          <div class="notification-item__title"><?= e($n['title']) ?></div>
          <?php if ($n['body']): ?><div class="notification-item__body"><?= e($n['body']) ?></div><?php endif; ?>
          <div class="notification-item__time"><?= e(format_date($n['created_at'])) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
