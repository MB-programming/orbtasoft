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
        $stmt = $pdo->prepare('DELETE FROM tech_stack WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Tech stack item deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if ($data['name'] === '') {
            $flash = 'Please enter a technology name.';
            $flashType = 'error';
        } elseif ($id > 0) {
            $data['id'] = $id;
            $pdo->prepare('UPDATE tech_stack SET name=:name, sort_order=:sort_order WHERE id=:id')->execute($data);
            $flash = 'Tech stack item updated.';
        } else {
            $pdo->prepare('INSERT INTO tech_stack (name, sort_order) VALUES (:name, :sort_order)')->execute($data);
            $flash = 'Tech stack item created.';
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM tech_stack WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$stack = $pdo->query('SELECT * FROM tech_stack ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'stack';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Tech Stack</h1>
    <p>Manage the scrolling technology pill strip shown on the homepage and about page.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Tech Stack Item' : 'Add New Tech Stack Item' ?></h2>
  <form method="post" action="/admin/stack.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

    <div class="admin-form-grid">
      <div class="form-row"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
      <div class="form-row"><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? count($stack)) ?>"></div>
    </div>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Item' ?></button>
      <?php if ($editing): ?><a href="/admin/stack.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Tech Stack Items (<?= count($stack) ?>)</h2>
  <?php if (empty($stack)): ?>
    <div class="admin-empty">No tech stack items yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($stack as $s): ?>
            <tr>
              <td class="cell-muted"><?= (int) $s['sort_order'] ?></td>
              <td class="cell-strong"><?= e($s['name']) ?></td>
              <td>
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="/admin/stack.php?edit=<?= (int) $s['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a>
                  <form method="post" action="/admin/stack.php" data-confirm="Delete this tech stack item?">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
