<?php
require_once __DIR__ . '/../includes/client.php';
$accountUser = client_require_login();

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = $invoiceId ? invoice_by_id($invoiceId) : null;

if (!$invoice || (int) $invoice['user_id'] !== (int) $accountUser['id']) {
    http_response_code(404);
    $current_account_page = 'invoices';
    require __DIR__ . '/../includes/account-top.php';
    echo '<div class="account-panel"><div class="account-empty">' . e(t('invoice_not_found')) . '</div></div>';
    require __DIR__ . '/../includes/account-bottom.php';
    exit;
}

$current_account_page = 'invoices';
require __DIR__ . '/../includes/account-top.php';
?>

<div class="account-page-head">
  <a href="/account/invoices.php" class="back-link"><?= icon('arrow-left') ?> <?= e(t('invoice_back')) ?></a>
  <h1><?= e($invoice['invoice_number']) ?></h1>
</div>

<div class="account-panel" id="invoicePrintArea">
  <div class="invoice-detail__head">
    <div>
      <p class="cell-muted"><?= e(t('invoice_bill_to')) ?></p>
      <p class="cell-strong"><?= e($accountUser['name']) ?></p>
      <p class="cell-muted"><?= e($accountUser['email']) ?></p>
    </div>
    <div>
      <p class="cell-muted"><?= e(t('invoice_issued_label')) ?>: <?= e(format_date($invoice['created_at'])) ?></p>
      <?php if ($invoice['due_date']): ?><p class="cell-muted"><?= e(t('invoice_due_label')) ?>: <?= e(format_date($invoice['due_date'])) ?></p><?php endif; ?>
      <p><span class="invoice-status invoice-status--<?= e($invoice['status']) ?>"><?= e(invoice_status_label($invoice['status'])) ?></span></p>
    </div>
  </div>

  <h2><?= e(t('invoice_items_heading')) ?></h2>
  <div class="admin-table-wrap">
    <table class="account-table">
      <thead>
        <tr>
          <th><?= e(t('invoice_description_col')) ?></th>
          <th><?= e(t('invoice_qty_col')) ?></th>
          <th><?= e(t('invoice_unit_price_col')) ?></th>
          <th><?= e(t('invoice_amount_col')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoice['items'] as $item): ?>
          <tr>
            <td><?= e($item['description']) ?></td>
            <td><?= e(rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.')) ?></td>
            <td><?= e(format_money((float) $item['unit_price'], $invoice['currency'])) ?></td>
            <td><?= e(format_money((float) $item['quantity'] * (float) $item['unit_price'], $invoice['currency'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="invoice-detail__totals">
    <div><span><?= e(t('invoice_subtotal')) ?></span> <span><?= e(format_money((float) $invoice['subtotal'], $invoice['currency'])) ?></span></div>
    <?php if ((float) $invoice['discount_amount'] > 0): ?>
      <div><span><?= e(t('invoice_discount')) ?></span> <span>-<?= e(format_money((float) $invoice['discount_amount'], $invoice['currency'])) ?></span></div>
    <?php endif; ?>
    <div class="is-total"><span><?= e(t('invoice_total')) ?></span> <span><?= e(format_money((float) $invoice['total'], $invoice['currency'])) ?></span></div>
  </div>

  <?php if ($invoice['notes']): ?><p class="cell-muted" style="margin-block-start:20px;"><?= nl2br(e($invoice['notes'])) ?></p><?php endif; ?>
</div>

<div class="admin-form-actions">
  <button type="button" class="btn btn--primary btn--sm" onclick="window.print()"><?= e(t('invoice_print')) ?></button>
</div>

<style>
  @media print {
    .site-header, .account-sidebar, .site-footer, .account-page-head .back-link, .admin-form-actions { display: none !important; }
    .account-shell { padding: 0 !important; }
    .account-shell__inner { display: block !important; }
    body { background: #fff !important; color: #000 !important; }
  }
</style>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
