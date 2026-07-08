<?php
$current_account_page = 'messages';
require __DIR__ . '/../includes/account-top.php';
require_once __DIR__ . '/../includes/uploads.php';

$userId = (int) $accountUser['id'];
$pdo = get_db();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
    } else {
        $body = trim($_POST['body'] ?? '');
        $attachment = handle_chat_attachment_upload('attachment');
        if (($body !== '' || $attachment) && $pdo) {
            $stmt = $pdo->prepare("INSERT INTO client_messages (user_id, sender, body, attachment_path, attachment_type, attachment_name) VALUES (:uid, 'client', :body, :apath, :atype, :aname)");
            $stmt->execute([
                'uid' => $userId, 'body' => $body,
                'apath' => $attachment['path'] ?? '', 'atype' => $attachment['type'] ?? '', 'aname' => $attachment['name'] ?? '',
            ]);
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
          <?= render_chat_attachment($msg) ?>
          <?php if ($msg['body'] !== ''): ?><?= nl2br(e($msg['body'])) ?><?php endif; ?>
          <span class="chat-bubble__meta"><?= $msg['sender'] === 'client' ? e(t('chat_you')) : e(t('chat_team')) ?> · <?= e(format_date($msg['created_at'])) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <form method="post" action="/account/messages.php" class="chat-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="send">

    <div class="chat-emoji-panel" id="emojiPanel">
      <?php foreach (chat_emoji_list() as $emoji): ?><button type="button"><?= $emoji ?></button><?php endforeach; ?>
    </div>

    <div class="chat-form__row">
      <div class="chat-toolbar">
        <button type="button" class="chat-emoji-btn" aria-label="Emoji"><?= icon('smile') ?></button>
        <button type="button" class="chat-attach-btn" aria-label="Attach file"><?= icon('paperclip') ?></button>
        <button type="button" class="chat-voice-btn" aria-label="Record voice message"><?= icon('mic') ?></button>
      </div>
      <textarea name="body" placeholder="<?= e(t('chat_placeholder')) ?>"></textarea>
      <button type="submit" class="btn btn--primary btn--sm"><?= e(t('chat_send')) ?></button>
    </div>
    <input type="file" name="attachment" class="chat-attach-input" style="display:none;" accept="image/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
    <span class="chat-attach-name"></span>
  </form>
</div>

<script>
  var chatWindow = document.getElementById('chatWindow');
  if (chatWindow) { chatWindow.scrollTop = chatWindow.scrollHeight; }
</script>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
