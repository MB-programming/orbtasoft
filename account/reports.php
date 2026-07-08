<?php
$current_account_page = 'reports';
require __DIR__ . '/../includes/account-top.php';

$userId = (int) $accountUser['id'];
$projects = client_projects($userId);
$invoices = client_invoices($userId);

$totalProjects = count($projects);
$completedProjects = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
$totalInvoiced = array_sum(array_map(fn($i) => (float) $i['total'], $invoices));
$totalPaid = array_sum(array_map(fn($i) => $i['status'] === 'paid' ? (float) $i['total'] : 0, $invoices));
$outstanding = array_sum(array_map(fn($i) => in_array($i['status'], ['sent', 'overdue'], true) ? (float) $i['total'] : 0, $invoices));
$balance = wallet_balance($userId);
?>

<div class="account-page-head">
  <h1><?= e(t('reports_heading')) ?></h1>
  <p><?= e(t('reports_desc')) ?></p>
</div>

<div class="account-stats">
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('reports_total_projects')) ?></div>
    <div class="account-stat-card__value"><?= (int) $totalProjects ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('reports_completed_projects')) ?></div>
    <div class="account-stat-card__value"><?= (int) $completedProjects ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('reports_total_invoiced')) ?></div>
    <div class="account-stat-card__value"><?= e(format_money($totalInvoiced)) ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('reports_total_paid')) ?></div>
    <div class="account-stat-card__value"><?= e(format_money($totalPaid)) ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('reports_outstanding')) ?></div>
    <div class="account-stat-card__value"><?= e(format_money($outstanding)) ?></div>
  </div>
  <div class="account-stat-card">
    <div class="account-stat-card__label"><?= e(t('reports_wallet_balance')) ?></div>
    <div class="account-stat-card__value"><?= e(format_money($balance)) ?></div>
  </div>
</div>

<?php require __DIR__ . '/../includes/account-bottom.php'; ?>
