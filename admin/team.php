<?php
require __DIR__ . '/includes/auth.php';
admin_require_login();
$pdo = get_db();

$palette = [
    'linear-gradient(145deg, #3b82f6, #162c6d)',
    'linear-gradient(145deg, #a855f7, #3b1e6d)',
    'linear-gradient(145deg, #2dd4bf, #0d4d4d)',
    'linear-gradient(145deg, #f472b6, #5a2a4d)',
    'linear-gradient(145deg, #22c55e, #1f5a35)',
    'linear-gradient(145deg, #fbbf24, #5a3a10)',
];

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM team_members WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Team member deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'role_de' => trim($_POST['role_de'] ?? ''),
            'role_en' => trim($_POST['role_en'] ?? ''),
            'role_ar' => trim($_POST['role_ar'] ?? ''),
            'color' => in_array($_POST['color'] ?? '', $palette, true) ? $_POST['color'] : $palette[0],
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if (in_array('', [$data['name'], $data['role_de'], $data['role_en'], $data['role_ar']], true)) {
            $flash = 'Please fill in every field for all three languages.';
            $flashType = 'error';
        } elseif ($id > 0) {
            $data['id'] = $id;
            $pdo->prepare('UPDATE team_members SET name=:name, role_de=:role_de, role_en=:role_en, role_ar=:role_ar, color=:color, sort_order=:sort_order WHERE id=:id')->execute($data);
            $flash = 'Team member updated.';
        } else {
            $pdo->prepare('INSERT INTO team_members (name, role_de, role_en, role_ar, color, sort_order) VALUES (:name, :role_de, :role_en, :role_ar, :color, :sort_order)')->execute($data);
            $flash = 'Team member created.';
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM team_members WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$members = $pdo->query('SELECT * FROM team_members ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'team';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Team</h1>
    <p>Manage the team marquee on the About page.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Team Member' : 'Add New Team Member' ?></h2>
  <form method="post" action="/admin/team.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

    <div class="admin-form-grid">
      <div class="form-row"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
      <div class="form-row">
        <label for="color">Avatar Color</label>
        <select id="color" name="color">
          <?php foreach ($palette as $i => $c): ?>
            <option value="<?= e($c) ?>" <?= ($editing['color'] ?? $palette[0]) === $c ? 'selected' : '' ?>>Palette <?= $i + 1 ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? count($members)) ?>"></div>

      <div class="form-row"><label for="role_de">Role (Deutsch)</label><input type="text" id="role_de" name="role_de" value="<?= e($editing['role_de'] ?? '') ?>" required></div>
      <div class="form-row"><label for="role_en">Role (English)</label><input type="text" id="role_en" name="role_en" value="<?= e($editing['role_en'] ?? '') ?>" required></div>
      <div class="form-row"><label for="role_ar">Role (العربية)</label><input type="text" id="role_ar" name="role_ar" dir="rtl" value="<?= e($editing['role_ar'] ?? '') ?>" required></div>
    </div>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Member' ?></button>
      <?php if ($editing): ?><a href="/admin/team.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Team Members (<?= count($members) ?>)</h2>
  <?php if (empty($members)): ?>
    <div class="admin-empty">No team members yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Avatar</th><th>Name</th><th>Role (EN)</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): ?>
            <tr>
              <td class="cell-muted"><?= (int) $m['sort_order'] ?></td>
              <td><div class="admin-avatar-preview" style="background: <?= e($m['color']) ?>;"><?= e(initials($m['name'])) ?></div></td>
              <td class="cell-strong"><?= e($m['name']) ?></td>
              <td class="cell-muted"><?= e($m['role_en']) ?></td>
              <td>
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="/admin/team.php?edit=<?= (int) $m['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a>
                  <form method="post" action="/admin/team.php" data-confirm="Delete this team member?">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
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
