<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/client.php';
admin_require_login();
$pdo = get_db();

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = $invoiceId ? invoice_by_id($invoiceId) : null;
$clientId = $invoice ? (int) $invoice['user_id'] : (int) ($_GET['client_id'] ?? 0);

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } else {
        $postClientId = (int) ($_POST['client_id'] ?? 0);
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unitPrices = $_POST['unit_price'] ?? [];

        $items = [];
        $subtotal = 0.0;
        foreach ($descriptions as $i => $desc) {
            $desc = trim($desc);
            if ($desc === '') {
                continue;
            }
            $qty = (float) ($quantities[$i] ?? 1);
            $price = (float) ($unitPrices[$i] ?? 0);
            $items[] = ['description' => $desc, 'quantity' => $qty, 'unit_price' => $price, 'sort_order' => count($items)];
            $subtotal += $qty * $price;
        }

        $discountId = (int) ($_POST['discount_id'] ?? 0) ?: null;
        $discountAmount = 0.0;
        if ($discountId) {
            $stmt = $pdo->prepare('SELECT * FROM discounts WHERE id = :id AND is_active = 1');
            $stmt->execute(['id' => $discountId]);
            $discount = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($discount) {
                $discountAmount = $discount['type'] === 'percent' ? $subtotal * ((float) $discount['amount'] / 100) : (float) $discount['amount'];
                $discountAmount = min($discountAmount, $subtotal);
            }
        }
        $total = $subtotal - $discountAmount;
        $status = $_POST['status'] ?? 'draft';
        $dueDate = trim($_POST['due_date'] ?? '') ?: null;

        if (!$postClientId || !$items) {
            $flash = 'Choose a client and add at least one line item.';
            $flashType = 'error';
        } else {
            $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;

            if ($invoice) {
                $statusChanged = $invoice['status'] !== $status;
                $stmt = $pdo->prepare('UPDATE invoices SET status = :status, discount_id = :did, discount_amount = :damt, subtotal = :subtotal, total = :total, due_date = :due, paid_at = :paid, notes = :notes WHERE id = :id');
                $stmt->execute([
                    'status' => $status, 'did' => $discountId, 'damt' => $discountAmount, 'subtotal' => $subtotal,
                    'total' => $total, 'due' => $dueDate, 'paid' => $paidAt, 'notes' => trim($_POST['notes'] ?? ''), 'id' => $invoiceId,
                ]);
                $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = :id')->execute(['id' => $invoiceId]);
                $newInvoiceId = $invoiceId;
                if ($statusChanged && in_array($status, ['sent', 'paid'], true)) {
                    $title = $status === 'paid' ? 'Your invoice has been marked as paid' : 'An invoice was sent to you';
                    notify_user($postClientId, 'invoice', $title, $invoice['invoice_number'] . ' — ' . format_money($total), '/account/invoices.php');
                }
            } else {
                $number = generate_invoice_number($pdo);
                $stmt = $pdo->prepare('INSERT INTO invoices (user_id, invoice_number, status, discount_id, discount_amount, subtotal, total, due_date, paid_at, notes) VALUES (:uid, :num, :status, :did, :damt, :subtotal, :total, :due, :paid, :notes)');
                $stmt->execute([
                    'uid' => $postClientId, 'num' => $number, 'status' => $status, 'did' => $discountId, 'damt' => $discountAmount,
                    'subtotal' => $subtotal, 'total' => $total, 'due' => $dueDate, 'paid' => $paidAt, 'notes' => trim($_POST['notes'] ?? ''),
                ]);
                $newInvoiceId = (int) $pdo->lastInsertId();
                notify_user($postClientId, 'invoice', 'A new invoice was issued', $number . ' — ' . format_money($total), '/account/invoices.php');
            }

            $itemStmt = $pdo->prepare('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, sort_order) VALUES (:iid, :desc, :qty, :price, :order)');
            foreach ($items as $item) {
                $itemStmt->execute(['iid' => $newInvoiceId, 'desc' => $item['description'], 'qty' => $item['quantity'], 'price' => $item['unit_price'], 'order' => $item['sort_order']]);
            }

            header('Location: /admin/invoice-edit.php?id=' . $newInvoiceId . '&saved=1');
            exit;
        }
    }
}

if ($invoiceId && isset($_GET['saved'])) {
    $invoice = invoice_by_id($invoiceId);
}

$clients = $pdo->query('SELECT id, name, email FROM users ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$discounts = $pdo->query("SELECT * FROM discounts WHERE is_active = 1 ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
$items = $invoice['items'] ?? [[ 'description' => '', 'quantity' => 1, 'unit_price' => 0 ]];

$current_admin_page = 'invoices';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1><?= $invoice ? e($invoice['invoice_number']) : 'New Invoice' ?></h1>
    <p>Line items, discount, and status.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php elseif (isset($_GET['saved'])): ?><div class="admin-flash is-success">Invoice saved.</div><?php endif; ?>

<form method="post" action="/admin/invoice-edit.php<?= $invoiceId ? '?id=' . $invoiceId : '' ?>" id="invoiceForm">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <div class="admin-panel">
    <div class="admin-form-grid">
      <div class="form-row">
        <label>Client</label>
        <select name="client_id" <?= $invoice ? 'disabled' : '' ?> required>
          <option value="">Select a client…</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $clientId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <?php if ($invoice): ?><input type="hidden" name="client_id" value="<?= (int) $invoice['user_id'] ?>"><?php endif; ?>
      </div>
      <div class="form-row">
        <label>Status</label>
        <select name="status">
          <?php foreach (['draft', 'sent', 'paid', 'overdue', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($invoice['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Due Date</label><input type="date" name="due_date" value="<?= e(substr($invoice['due_date'] ?? '', 0, 10)) ?>"></div>

      <div class="form-row">
        <label>Discount</label>
        <select name="discount_id">
          <option value="">None</option>
          <?php foreach ($discounts as $d): ?>
            <option value="<?= (int) $d['id'] ?>" <?= (int) ($invoice['discount_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>>
              <?= e($d['code']) ?> (<?= $d['type'] === 'percent' ? e($d['amount']) . '%' : e(format_money((float) $d['amount'])) ?> off)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row span-2"><label>Notes</label><input type="text" name="notes" value="<?= e($invoice['notes'] ?? '') ?>" placeholder="Optional notes shown on the invoice"></div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Line Items</h2>
    <table class="admin-table" id="itemsTable">
      <thead><tr><th>Description</th><th style="width:100px;">Qty</th><th style="width:140px;">Unit Price</th><th style="width:40px;"></th></tr></thead>
      <tbody id="itemsBody">
        <?php foreach ($items as $item): ?>
          <tr class="item-row">
            <td><input type="text" name="description[]" value="<?= e($item['description']) ?>" placeholder="e.g. Website design"></td>
            <td><input type="number" name="quantity[]" value="<?= e((string) $item['quantity']) ?>" step="0.01" min="0"></td>
            <td><input type="number" name="unit_price[]" value="<?= e((string) $item['unit_price']) ?>" step="0.01" min="0"></td>
            <td><button type="button" class="admin-icon-btn is-danger remove-row" aria-label="Remove"><?= icon('trash') ?></button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="btn btn--outline btn--sm" id="addRowBtn" style="margin-block-start:14px;">+ Add Line Item</button>
  </div>

  <div class="admin-form-actions">
    <button type="submit" class="btn btn--primary btn--sm">Save Invoice</button>
    <a href="/admin/invoices.php" class="btn btn--outline btn--sm">Cancel</a>
  </div>
</form>

<template id="rowTemplate">
  <tr class="item-row">
    <td><input type="text" name="description[]" placeholder="e.g. Website design"></td>
    <td><input type="number" name="quantity[]" value="1" step="0.01" min="0"></td>
    <td><input type="number" name="unit_price[]" value="0" step="0.01" min="0"></td>
    <td><button type="button" class="admin-icon-btn is-danger remove-row" aria-label="Remove"><?= icon('trash') ?></button></td>
  </tr>
</template>

<script>
  document.getElementById('addRowBtn').addEventListener('click', function () {
    var tpl = document.getElementById('rowTemplate');
    document.getElementById('itemsBody').appendChild(tpl.content.cloneNode(true));
  });
  document.getElementById('itemsBody').addEventListener('click', function (e) {
    var btn = e.target.closest('.remove-row');
    if (btn) { btn.closest('tr').remove(); }
  });
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
