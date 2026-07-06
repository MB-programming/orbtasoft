<?php
require __DIR__ . '/includes/auth.php';
admin_require_login();
$pdo = get_db();

if (($_GET['export'] ?? '') === 'csv') {
    $rows = $pdo->query('SELECT email, created_at FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'subscribed_at']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['email'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Subscriber deleted.';
    }
}

$subscribers = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'newsletter';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Newsletter Subscribers</h1>
    <p>Emails collected from the "Stay in the orbit" signup on the homepage.</p>
  </div>
  <a href="/admin/newsletter.php?export=csv" class="btn btn--outline btn--sm">Export CSV</a>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>All Subscribers (<?= count($subscribers) ?>)</h2>
  <?php if (empty($subscribers)): ?>
    <div class="admin-empty">No subscribers yet.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Email</th><th>Subscribed</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($subscribers as $s): ?>
            <tr>
              <td class="cell-strong"><?= e($s['email']) ?></td>
              <td class="cell-muted"><?= e($s['created_at']) ?></td>
              <td>
                <form method="post" action="/admin/newsletter.php" data-confirm="Delete this subscriber?">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
