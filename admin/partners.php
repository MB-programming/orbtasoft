<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../includes/uploads.php';
admin_require_login();
$pdo = get_db();

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM partners WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Partner deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'logo' => handle_image_upload('logo_file', trim($_POST['existing_logo'] ?? '')),
            'weight' => max(400, min(900, (int) ($_POST['weight'] ?? 700))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if ($data['name'] === '') {
            $flash = 'Please enter a partner name.';
            $flashType = 'error';
        } elseif ($id > 0) {
            $data['id'] = $id;
            $pdo->prepare('UPDATE partners SET name=:name, logo=:logo, weight=:weight, sort_order=:sort_order WHERE id=:id')->execute($data);
            $flash = 'Partner updated.';
        } else {
            $pdo->prepare('INSERT INTO partners (name, logo, weight, sort_order) VALUES (:name, :logo, :weight, :sort_order)')->execute($data);
            $flash = 'Partner created.';
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM partners WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$partners = $pdo->query('SELECT * FROM partners ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'partners';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Partners</h1>
    <p>Manage the scrolling logo cloud. These are fictional client names/logos, not real companies.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Partner' : 'Add New Partner' ?></h2>
  <form method="post" action="/admin/partners.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <input type="hidden" name="existing_logo" value="<?= e($editing['logo'] ?? '') ?>">

    <div class="admin-form-grid">
      <div class="form-row span-3">
        <label for="logo_file">Logo</label>
        <?php if (!empty($editing['logo'])): ?>
          <img class="admin-img-preview" src="<?= e($editing['logo']) ?>" alt="" style="margin-block-end:10px; width:56px; height:56px; border-radius:12px; object-fit:cover;">
        <?php endif; ?>
        <input type="file" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
      </div>

      <div class="form-row"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
      <div class="form-row"><label for="weight">Font Weight (400–900)</label><input type="number" id="weight" name="weight" min="400" max="900" step="100" value="<?= (int) ($editing['weight'] ?? 700) ?>"></div>
      <div class="form-row"><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? count($partners)) ?>"></div>
    </div>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Partner' ?></button>
      <?php if ($editing): ?><a href="/admin/partners.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Partners (<?= count($partners) ?>)</h2>
  <?php if (empty($partners)): ?>
    <div class="admin-empty">No partners yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Logo</th><th>Name</th><th>Weight</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($partners as $p): ?>
            <tr>
              <td class="cell-muted"><?= (int) $p['sort_order'] ?></td>
              <td>
                <?php if (!empty($p['logo'])): ?>
                  <img src="<?= e($p['logo']) ?>" alt="" style="width:36px; height:36px; border-radius:8px; object-fit:cover;">
                <?php else: ?>
                  <span class="cell-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="cell-strong" style="font-weight: <?= (int) $p['weight'] ?>;"><?= e($p['name']) ?></td>
              <td class="cell-muted"><?= (int) $p['weight'] ?></td>
              <td>
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="/admin/partners.php?edit=<?= (int) $p['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a>
                  <form method="post" action="/admin/partners.php" data-confirm="Delete this partner?">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
