<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/seo.php';
admin_require_login();
$pdo = get_db();

$iconOptions = ['code-2', 'database', 'box', 'layout-panel-top', 'layout-dashboard', 'shield-check', 'star', 'briefcase', 'globe', 'send', 'rocket', 'check-circle'];

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM services WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Service deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $existingImage = trim($_POST['existing_image'] ?? '');
        $data = [
            'icon' => in_array($_POST['icon'] ?? '', $iconOptions, true) ? $_POST['icon'] : 'box',
            'image' => handle_image_upload('image_file', $existingImage),
            'title_de' => trim($_POST['title_de'] ?? ''),
            'title_en' => trim($_POST['title_en'] ?? ''),
            'title_ar' => trim($_POST['title_ar'] ?? ''),
            'desc_de' => trim($_POST['desc_de'] ?? ''),
            'desc_en' => trim($_POST['desc_en'] ?? ''),
            'desc_ar' => trim($_POST['desc_ar'] ?? ''),
            'content_de' => trim($_POST['content_de'] ?? ''),
            'content_en' => trim($_POST['content_en'] ?? ''),
            'content_ar' => trim($_POST['content_ar'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $slugInput = trim($_POST['slug'] ?? '');
        if (in_array('', [$data['title_de'], $data['title_en'], $data['title_ar'], $data['desc_de'], $data['desc_en'], $data['desc_ar']], true)) {
            $flash = 'Please fill in every field for all three languages.';
            $flashType = 'error';
        } else {
            $data['slug'] = slugify($slugInput !== '' ? $slugInput : $data['title_en']);
            try {
                if ($id > 0) {
                    $data['id'] = $id;
                    $pdo->prepare('UPDATE services SET slug=:slug, icon=:icon, image=:image, title_de=:title_de, title_en=:title_en, title_ar=:title_ar, desc_de=:desc_de, desc_en=:desc_en, desc_ar=:desc_ar, content_de=:content_de, content_en=:content_en, content_ar=:content_ar, sort_order=:sort_order WHERE id=:id')->execute($data);
                    $flash = 'Service updated.';
                } else {
                    $pdo->prepare('INSERT INTO services (slug, icon, image, title_de, title_en, title_ar, desc_de, desc_en, desc_ar, content_de, content_en, content_ar, sort_order) VALUES (:slug, :icon, :image, :title_de, :title_en, :title_ar, :desc_de, :desc_en, :desc_ar, :content_de, :content_en, :content_ar, :sort_order)')->execute($data);
                    $flash = 'Service created.';
                }
            } catch (PDOException $e) {
                $flash = str_contains($e->getMessage(), 'Duplicate') ? 'That slug is already in use — choose another.' : 'Something went wrong, please try again.';
                $flashType = 'error';
            }
            if ($flashType === 'success') {
                seo_meta_save('service', $data['slug'], $_POST);
            }
        }
    }
}

$editing = null;
$seoData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($editing) {
        $seoData = seo_meta_fetch('service', $editing['slug']);
    }
}

$services = $pdo->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'services';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Services</h1>
    <p>Manage the services grid shown on the homepage and services page.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Service' : 'Add New Service' ?></h2>
  <form method="post" action="/admin/services.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <input type="hidden" name="existing_image" value="<?= e($editing['image'] ?? '') ?>">

    <div class="admin-form-grid">
      <div class="form-row">
        <label for="icon">Icon</label>
        <select id="icon" name="icon">
          <?php foreach ($iconOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= ($editing['icon'] ?? 'box') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label for="slug">Slug (leave blank to auto-generate from EN title)</label><input type="text" id="slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      <div class="form-row"><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? count($services)) ?>"></div>

      <div class="form-row span-3">
        <label for="image_file">Detail page image</label>
        <?php if (!empty($editing['image'])): ?>
          <img class="admin-img-preview" src="<?= e($editing['image']) ?>" alt="" style="margin-block-end:10px;">
        <?php endif; ?>
        <input type="file" id="image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
      </div>

      <div class="form-row"><label for="title_de">Title (Deutsch)</label><input type="text" id="title_de" name="title_de" value="<?= e($editing['title_de'] ?? '') ?>" required></div>
      <div class="form-row"><label for="title_en">Title (English)</label><input type="text" id="title_en" name="title_en" value="<?= e($editing['title_en'] ?? '') ?>" required></div>
      <div class="form-row"><label for="title_ar">Title (العربية)</label><input type="text" id="title_ar" name="title_ar" dir="rtl" value="<?= e($editing['title_ar'] ?? '') ?>" required></div>

      <div class="form-row"><label for="desc_de">Card description (Deutsch)</label><textarea id="desc_de" name="desc_de" required><?= e($editing['desc_de'] ?? '') ?></textarea></div>
      <div class="form-row"><label for="desc_en">Card description (English)</label><textarea id="desc_en" name="desc_en" required><?= e($editing['desc_en'] ?? '') ?></textarea></div>
      <div class="form-row"><label for="desc_ar">Card description (العربية)</label><textarea id="desc_ar" name="desc_ar" dir="rtl" required><?= e($editing['desc_ar'] ?? '') ?></textarea></div>

      <div class="form-row span-3"><label for="content_de">Detail page content (Deutsch — separate paragraphs with a blank line)</label><textarea id="content_de" name="content_de" rows="6"><?= e($editing['content_de'] ?? '') ?></textarea></div>
      <div class="form-row span-3"><label for="content_en">Detail page content (English — separate paragraphs with a blank line)</label><textarea id="content_en" name="content_en" rows="6"><?= e($editing['content_en'] ?? '') ?></textarea></div>
      <div class="form-row span-3"><label for="content_ar">Detail page content (العربية — افصل الفقرات بسطر فارغ)</label><textarea id="content_ar" name="content_ar" dir="rtl" rows="6"><?= e($editing['content_ar'] ?? '') ?></textarea></div>
    </div>

    <?php require __DIR__ . '/includes/seo-fields.php'; ?>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Service' ?></button>
      <?php if ($editing): ?><a href="/admin/services.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Services (<?= count($services) ?>)</h2>
  <?php if (empty($services)): ?>
    <div class="admin-empty">No services yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Icon</th><th>Title (EN)</th><th>Slug</th><th>Description (EN)</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($services as $s): ?>
            <tr>
              <td class="cell-muted"><?= (int) $s['sort_order'] ?></td>
              <td><?= icon($s['icon']) ?></td>
              <td class="cell-strong"><?= e($s['title_en']) ?></td>
              <td class="cell-muted">/service.php?slug=<?= e($s['slug']) ?></td>
              <td class="cell-muted cell-wrap"><?= e(mb_substr($s['desc_en'], 0, 80)) ?><?= mb_strlen($s['desc_en']) > 80 ? '…' : '' ?></td>
              <td>
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="/admin/services.php?edit=<?= (int) $s['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a>
                  <form method="post" action="/admin/services.php" data-confirm="Delete this service?">
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
