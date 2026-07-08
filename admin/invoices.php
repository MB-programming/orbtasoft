<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/client.php';
admin_require_login();
$pdo = get_db();

$clientId = (int) ($_GET['client_id'] ?? 0);

$sql = 'SELECT i.*, u.name AS client_name FROM invoices i JOIN users u ON u.id = i.user_id';
if ($clientId > 0) {
    $sql .= ' WHERE i.user_id = ' . $clientId;
}
$sql .= ' ORDER BY i.created_at DESC';
$invoices = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'invoices';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Invoices</h1>
    <p>All client invoices — create, edit, and track payment status.</p>
  </div>
  <a href="/admin/invoice-edit.php<?= $clientId ? '?client_id=' . $clientId : '' ?>" class="btn btn--primary btn--sm">Create Invoice</a>
</div>

<div class="admin-panel">
  <?php if (empty($invoices)): ?>
    <div class="admin-empty">No invoices yet.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Number</th><th>Client</th><th>Status</th><th>Total</th><th>Due</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td class="cell-strong"><?= e($inv['invoice_number']) ?></td>
              <td class="cell-muted"><a href="/admin/client.php?id=<?= (int) $inv['user_id'] ?>"><?= e($inv['client_name']) ?></a></td>
              <td><span class="admin-badge <?= $inv['status'] === 'paid' ? 'is-on' : 'is-off' ?>"><?= ucfirst($inv['status']) ?></span></td>
              <td class="cell-muted"><?= e(format_money((float) $inv['total'], $inv['currency'])) ?></td>
              <td class="cell-muted"><?= $inv['due_date'] ? e(substr($inv['due_date'], 0, 10)) : '—' ?></td>
              <td><a class="admin-icon-btn" href="/admin/invoice-edit.php?id=<?= (int) $inv['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
