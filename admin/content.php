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
    } elseif (($_POST['action'] ?? '') === 'save_all') {
        $de = $_POST['value_de'] ?? [];
        $en = $_POST['value_en'] ?? [];
        $ar = $_POST['value_ar'] ?? [];
        $stmt = $pdo->prepare('UPDATE site_strings SET value_de = :de, value_en = :en, value_ar = :ar WHERE str_key = :k');
        $count = 0;
        foreach ($de as $key => $value) {
            $stmt->execute([
                'de' => $value,
                'en' => $en[$key] ?? '',
                'ar' => $ar[$key] ?? '',
                'k' => $key,
            ]);
            $count++;
        }
        $flash = "Saved {$count} text strings.";
    }
}

$strings = $pdo->query('SELECT * FROM site_strings ORDER BY str_key ASC')->fetchAll(PDO::FETCH_ASSOC);

$groups = [];
foreach ($strings as $row) {
    $prefix = explode('_', $row['str_key'])[0];
    $groups[$prefix][] = $row;
}
ksort($groups);

$current_admin_page = 'content';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Site Content</h1>
    <p>Edit any text on the site, in all three languages, without touching code.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="content-search-bar">
  <input type="search" id="contentSearch" placeholder="Search by key or text…" autocomplete="off">
</div>

<form method="post" action="/admin/content.php" id="contentForm">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="save_all">

  <?php foreach ($groups as $prefix => $rows): ?>
    <details class="admin-panel content-group" open>
      <summary class="content-group__summary"><?= e(ucfirst($prefix)) ?> <span class="content-group__count"><?= count($rows) ?></span></summary>
      <div class="content-group__body">
        <?php foreach ($rows as $row): ?>
          <div class="content-row" data-search="<?= e(mb_strtolower($row['str_key'] . ' ' . $row['value_de'] . ' ' . $row['value_en'] . ' ' . $row['value_ar'])) ?>">
            <div class="content-row__key"><?= e($row['str_key']) ?></div>
            <div class="content-row__fields">
              <div class="form-row">
                <label>Deutsch</label>
                <textarea name="value_de[<?= e($row['str_key']) ?>]" rows="2"><?= e($row['value_de']) ?></textarea>
              </div>
              <div class="form-row">
                <label>English</label>
                <textarea name="value_en[<?= e($row['str_key']) ?>]" rows="2"><?= e($row['value_en']) ?></textarea>
              </div>
              <div class="form-row">
                <label>العربية</label>
                <textarea name="value_ar[<?= e($row['str_key']) ?>]" dir="rtl" rows="2"><?= e($row['value_ar']) ?></textarea>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endforeach; ?>

  <div class="admin-form-actions admin-save-bar">
    <button type="submit" class="btn btn--primary btn--sm">Save All Changes</button>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
