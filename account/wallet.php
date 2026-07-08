<?php
$current_account_page = 'wallet';
require __DIR__ . '/../includes/account-top.php';

$userId = (int) $accountUser['id'];
$balance = wallet_balance($userId);
$transactions = wallet_transactions($userId);
?>

<div class="account-page-head">
  <h1><?= e(t('wallet_heading')) ?></h1>
  <p><?= e(t('wallet_desc')) ?></p>
</div>

<div class="account-stats">
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('wallet_balance_label')) ?></div>
    <div class="account-stat-card__value"><?= e(format_money($balance)) ?></div>
  </div>
</div>

<div class="account-panel">
  <h2><?= e(t('wallet_heading')) ?></h2>
  <?php if (!$transactions): ?>
    <div class="account-empty"><?= e(t('wallet_no_transactions')) ?></div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="account-table">
        <thead><tr><th><?= e(t('wallet_date')) ?></th><th><?= e(t('wallet_type')) ?></th><th><?= e(t('wallet_note')) ?></th><th><?= e(t('wallet_amount')) ?></th></tr></thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td><?= e(format_date($tx['created_at'])) ?></td>
              <td><?= $tx['type'] === 'credit' ? e(t('wallet_credit')) : e(t('wallet_debit')) ?></td>
              <td><?= e($tx['note']) ?></td>
              <td class="<?= $tx['type'] === 'credit' ? 'money-credit' : 'money-debit' ?>">
                <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= e(format_money((float) $tx['amount'])) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
