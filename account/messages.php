<?php
$current_account_page = 'messages';
require __DIR__ . '/../includes/account-top.php';

$userId = (int) $accountUser['id'];
$pdo = get_db();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
    } else {
        $body = trim($_POST['body'] ?? '');
        if ($body !== '' && $pdo) {
            $stmt = $pdo->prepare("INSERT INTO client_messages (user_id, sender, body) VALUES (:uid, 'client', :body)");
            $stmt->execute(['uid' => $userId, 'body' => $body]);
        }
        header('Location: /account/messages.php');
        exit;
    }
}

mark_messages_read($userId, 'admin');
$messages = client_messages($userId);
?>

<div class="account-page-head">
  <h1><?= e(t('chat_heading')) ?></h1>
  <p><?= e(t('chat_desc')) ?></p>
</div>

<?php if ($flash): ?><div class="admin-flash is-error"><?= e($flash) ?></div><?php endif; ?>

<div class="account-panel">
  <div class="chat-window" id="chatWindow">
    <?php if (!$messages): ?>
      <div class="account-empty"><?= e(t('chat_empty')) ?></div>
    <?php else: ?>
      <?php foreach ($messages as $msg): ?>
        <div class="chat-bubble chat-bubble--<?= $msg['sender'] === 'client' ? 'client' : 'admin' ?>">
          <?= nl2br(e($msg['body'])) ?>
          <span class="chat-bubble__meta"><?= $msg['sender'] === 'client' ? e(t('chat_you')) : e(t('chat_team')) ?> · <?= e(format_date($msg['created_at'])) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <form method="post" action="/account/messages.php" class="chat-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="send">
    <textarea name="body" placeholder="<?= e(t('chat_placeholder')) ?>" required></textarea>
    <button type="submit" class="btn btn--primary btn--sm"><?= e(t('chat_send')) ?></button>
  </form>
</div>

<script>
  var chatWindow = document.getElementById('chatWindow');
  if (chatWindow) { chatWindow.scrollTop = chatWindow.scrollHeight; }
</script>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
