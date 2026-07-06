<?php
require __DIR__ . '/includes/auth.php';
admin_require_login();
$pdo = get_db();

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Message deleted.';
    }
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'messages';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Contact Messages</h1>
    <p>Submissions from the contact form on the website.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>All Messages (<?= count($messages) ?>)</h2>
  <?php if (empty($messages)): ?>
    <div class="admin-empty">No contact messages yet.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Received</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($messages as $m): ?>
            <tr>
              <td class="cell-strong"><?= e($m['name']) ?></td>
              <td class="cell-muted"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></td>
              <td class="cell-muted cell-wrap"><?= nl2br(e($m['message'])) ?></td>
              <td class="cell-muted"><?= e($m['created_at']) ?></td>
              <td>
                <form method="post" action="/admin/messages.php" data-confirm="Delete this message?">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <button type="submit" class="admin-icon-btn is-danger" aria-label="Delete"><?= icon('trash') ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
