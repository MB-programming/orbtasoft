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
        $stmt = $pdo->prepare('DELETE FROM discounts WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Discount deleted.';
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $stmt = $pdo->prepare('UPDATE discounts SET is_active = NOT is_active WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Discount updated.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $amount = (float) ($_POST['amount'] ?? 0);
        $expiresAt = trim($_POST['expires_at'] ?? '') ?: null;
        if ($code === '' || $amount <= 0) {
            $flash = 'Please enter a code and a positive amount.';
            $flashType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO discounts (code, type, amount, expires_at) VALUES (:code, :type, :amount, :expires)');
                $stmt->execute(['code' => $code, 'type' => $type, 'amount' => $amount, 'expires' => $expiresAt]);
                $flash = 'Discount created.';
            } catch (PDOException $e) {
                $flash = str_contains($e->getMessage(), 'Duplicate') ? 'That code already exists.' : 'Something went wrong, please try again.';
                $flashType = 'error';
            }
        }
    }
}

$discounts = $pdo->query('SELECT * FROM discounts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'discounts';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Discounts</h1>
    <p>Discount codes that can be applied to client invoices.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>Create Discount</h2>
  <form method="post" action="/admin/discounts.php" class="admin-form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <div class="form-row"><label>Code</label><input type="text" name="code" placeholder="e.g. WELCOME10" required></div>
    <div class="form-row">
      <label>Type</label>
      <select name="type"><option value="percent">Percentage (%)</option><option value="fixed">Fixed Amount</option></select>
    </div>
    <div class="form-row"><label>Amount</label><input type="number" name="amount" step="0.01" min="0.01" required></div>
    <div class="form-row"><label>Expires (optional)</label><input type="date" name="expires_at"></div>
    <div class="form-row" style="align-self:end;"><button type="submit" class="btn btn--primary btn--sm">Create</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Discounts (<?= count($discounts) ?>)</h2>
  <?php if (empty($discounts)): ?>
    <div class="admin-empty">No discount codes yet.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Code</th><th>Type</th><th>Amount</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($discounts as $d): ?>
            <tr>
              <td class="cell-strong"><?= e($d['code']) ?></td>
              <td class="cell-muted"><?= ucfirst($d['type']) ?></td>
              <td class="cell-muted"><?= $d['type'] === 'percent' ? e($d['amount']) . '%' : e(format_money((float) $d['amount'])) ?></td>
              <td class="cell-muted"><?= $d['expires_at'] ? e($d['expires_at']) : '—' ?></td>
              <td>
                <form method="post" action="/admin/discounts.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                  <button type="submit" class="admin-badge <?= $d['is_active'] ? 'is-on' : 'is-off' ?>" style="border:none; cursor:pointer;"><?= $d['is_active'] ? 'Active' : 'Inactive' ?></button>
                </form>
              </td>
              <td>
                <form method="post" action="/admin/discounts.php" data-confirm="Delete this discount code?">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
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
