<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/client.php';
admin_require_login();
$pdo = get_db();

$clients = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM client_projects WHERE user_id = :uid');
foreach ($clients as &$c) {
    $countStmt->execute(['uid' => $c['id']]);
    $c['project_count'] = (int) $countStmt->fetchColumn();
    $c['balance'] = wallet_balance((int) $c['id']);
    $c['unread'] = unread_message_count((int) $c['id'], 'client');
}
unset($c);

$current_admin_page = 'clients';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Clients</h1>
    <p>All registered client accounts — manage their projects, messages, wallet and invoices.</p>
  </div>
</div>

<div class="admin-panel">
  <?php if (empty($clients)): ?>
    <div class="admin-empty">No clients have registered yet.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Projects</th><th>Wallet</th><th>Unread</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td class="cell-strong"><?= e($c['name']) ?></td>
              <td class="cell-muted"><?= e($c['email']) ?></td>
              <td class="cell-muted"><?= e(substr($c['created_at'], 0, 10)) ?></td>
              <td class="cell-muted"><?= (int) $c['project_count'] ?></td>
              <td class="cell-muted"><?= e(format_money((float) $c['balance'])) ?></td>
              <td><?php if ($c['unread'] > 0): ?><span class="admin-badge is-on"><?= (int) $c['unread'] ?> new</span><?php endif; ?></td>
              <td><a class="btn btn--outline btn--sm" href="/admin/client.php?id=<?= (int) $c['id'] ?>">Manage</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
