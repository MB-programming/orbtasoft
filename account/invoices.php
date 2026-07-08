<?php
$current_account_page = 'invoices';
require __DIR__ . '/../includes/account-top.php';

$invoices = client_invoices((int) $accountUser['id']);
?>

<div class="account-page-head">
  <h1><?= e(t('invoices_heading')) ?></h1>
  <p><?= e(t('invoices_desc')) ?></p>
</div>

<div class="account-panel">
  <?php if (!$invoices): ?>
    <div class="account-empty"><?= e(t('invoices_empty')) ?></div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="account-table">
        <thead>
          <tr>
            <th><?= e(t('invoice_number_label')) ?></th>
            <th><?= e(t('invoice_issued_label')) ?></th>
            <th><?= e(t('invoice_due_label')) ?></th>
            <th><?= e(t('invoice_total_label')) ?></th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td class="cell-strong"><?= e($inv['invoice_number']) ?></td>
              <td><?= e(format_date($inv['created_at'])) ?></td>
              <td><?= $inv['due_date'] ? e(format_date($inv['due_date'])) : '—' ?></td>
              <td><?= e(format_money((float) $inv['total'], $inv['currency'])) ?></td>
              <td><span class="invoice-status invoice-status--<?= e($inv['status']) ?>"><?= e(invoice_status_label($inv['status'])) ?></span></td>
              <td><a href="/account/invoice.php?id=<?= (int) $inv['id'] ?>" class="btn btn--outline btn--sm"><?= e(t('invoice_view')) ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
