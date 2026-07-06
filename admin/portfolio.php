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
        $stmt = $pdo->prepare('DELETE FROM portfolio_items WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Portfolio item deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'image' => trim($_POST['image'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'tag_de' => trim($_POST['tag_de'] ?? ''),
            'tag_en' => trim($_POST['tag_en'] ?? ''),
            'tag_ar' => trim($_POST['tag_ar'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if (in_array('', [$data['image'], $data['title'], $data['tag_de'], $data['tag_en'], $data['tag_ar']], true)) {
            $flash = 'Please fill in every field for all three languages.';
            $flashType = 'error';
        } elseif ($id > 0) {
            $data['id'] = $id;
            $pdo->prepare('UPDATE portfolio_items SET image=:image, title=:title, tag_de=:tag_de, tag_en=:tag_en, tag_ar=:tag_ar, sort_order=:sort_order WHERE id=:id')->execute($data);
            $flash = 'Portfolio item updated.';
        } else {
            $pdo->prepare('INSERT INTO portfolio_items (image, title, tag_de, tag_en, tag_ar, sort_order) VALUES (:image, :title, :tag_de, :tag_en, :tag_ar, :sort_order)')->execute($data);
            $flash = 'Portfolio item created.';
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM portfolio_items WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$items = $pdo->query('SELECT * FROM portfolio_items ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'portfolio';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Portfolio</h1>
    <p>Manage the project showcase on the homepage and portfolio page.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Portfolio Item' : 'Add New Portfolio Item' ?></h2>
  <form method="post" action="/admin/portfolio.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

    <div class="admin-form-grid">
      <div class="form-row span-3"><label for="image">Image path (SVG/PNG/JPG under /assets/img/, or a full URL)</label><input type="text" id="image" name="image" value="<?= e($editing['image'] ?? '/assets/img/') ?>" required></div>

      <div class="form-row"><label for="title">Title (brand name — same in all languages)</label><input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required></div>
      <div class="form-row"><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? count($items)) ?>"></div>
      <div></div>

      <div class="form-row"><label for="tag_de">Tag (Deutsch)</label><input type="text" id="tag_de" name="tag_de" value="<?= e($editing['tag_de'] ?? '') ?>" required></div>
      <div class="form-row"><label for="tag_en">Tag (English)</label><input type="text" id="tag_en" name="tag_en" value="<?= e($editing['tag_en'] ?? '') ?>" required></div>
      <div class="form-row"><label for="tag_ar">Tag (العربية)</label><input type="text" id="tag_ar" name="tag_ar" dir="rtl" value="<?= e($editing['tag_ar'] ?? '') ?>" required></div>
    </div>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Item' ?></button>
      <?php if ($editing): ?><a href="/admin/portfolio.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Portfolio Items (<?= count($items) ?>)</h2>
  <?php if (empty($items)): ?>
    <div class="admin-empty">No portfolio items yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Image</th><th>Title</th><th>Tag (EN)</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td class="cell-muted"><?= (int) $it['sort_order'] ?></td>
              <td><img class="admin-img-preview" src="<?= e($it['image']) ?>" alt=""></td>
              <td class="cell-strong"><?= e($it['title']) ?></td>
              <td class="cell-muted"><?= e($it['tag_en']) ?></td>
              <td>
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="/admin/portfolio.php?edit=<?= (int) $it['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a>
                  <form method="post" action="/admin/portfolio.php" data-confirm="Delete this portfolio item?">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                    <button type="submit" class="admin-icon-btn is-danger" aria-label="Delete"><?= icon('trash') ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
