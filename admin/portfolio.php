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
        $stmt = $pdo->prepare('DELETE FROM portfolio_items WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Portfolio item deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'image' => handle_image_upload('image_file', trim($_POST['existing_image'] ?? '')),
            'title' => trim($_POST['title'] ?? ''),
            'client' => trim($_POST['client'] ?? ''),
            'year' => trim($_POST['year'] ?? ''),
            'project_url' => trim($_POST['project_url'] ?? ''),
            'category' => in_array($_POST['category'] ?? '', ['web', 'uiux', 'mobile', 'business'], true) ? $_POST['category'] : 'web',
            'technologies' => trim($_POST['technologies'] ?? ''),
            'duration' => trim($_POST['duration'] ?? ''),
            'metric_1_label' => trim($_POST['metric_1_label'] ?? ''),
            'metric_1_value' => trim($_POST['metric_1_value'] ?? ''),
            'metric_2_label' => trim($_POST['metric_2_label'] ?? ''),
            'metric_2_value' => trim($_POST['metric_2_value'] ?? ''),
            'metric_3_label' => trim($_POST['metric_3_label'] ?? ''),
            'metric_3_value' => trim($_POST['metric_3_value'] ?? ''),
            'tag_de' => trim($_POST['tag_de'] ?? ''),
            'tag_en' => trim($_POST['tag_en'] ?? ''),
            'tag_ar' => trim($_POST['tag_ar'] ?? ''),
            'description_de' => trim($_POST['description_de'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'description_ar' => trim($_POST['description_ar'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $slugInput = trim($_POST['slug'] ?? '');
        $required = [$data['image'], $data['title'], $data['tag_de'], $data['tag_en'], $data['tag_ar']];
        if (in_array('', $required, true)) {
            $flash = 'Please fill in every required field for all three languages.';
            $flashType = 'error';
        } else {
            $data['slug'] = slugify($slugInput !== '' ? $slugInput : $data['title']);
            try {
                if ($id > 0) {
                    $data['id'] = $id;
                    $pdo->prepare('UPDATE portfolio_items SET slug=:slug, image=:image, title=:title, client=:client, year=:year, project_url=:project_url, category=:category, technologies=:technologies, duration=:duration, metric_1_label=:metric_1_label, metric_1_value=:metric_1_value, metric_2_label=:metric_2_label, metric_2_value=:metric_2_value, metric_3_label=:metric_3_label, metric_3_value=:metric_3_value, tag_de=:tag_de, tag_en=:tag_en, tag_ar=:tag_ar, description_de=:description_de, description_en=:description_en, description_ar=:description_ar, sort_order=:sort_order WHERE id=:id')->execute($data);
                    $flash = 'Portfolio item updated.';
                } else {
                    $pdo->prepare('INSERT INTO portfolio_items (slug, image, title, client, year, project_url, category, technologies, duration, metric_1_label, metric_1_value, metric_2_label, metric_2_value, metric_3_label, metric_3_value, tag_de, tag_en, tag_ar, description_de, description_en, description_ar, sort_order) VALUES (:slug, :image, :title, :client, :year, :project_url, :category, :technologies, :duration, :metric_1_label, :metric_1_value, :metric_2_label, :metric_2_value, :metric_3_label, :metric_3_value, :tag_de, :tag_en, :tag_ar, :description_de, :description_en, :description_ar, :sort_order)')->execute($data);
                    $id = (int) $pdo->lastInsertId();
                    $flash = 'Portfolio item created.';
                }
            } catch (PDOException $e) {
                $flash = str_contains($e->getMessage(), 'Duplicate') ? 'That slug is already in use — choose another.' : 'Something went wrong, please try again.';
                $flashType = 'error';
            }
        }
        if (!$flash || $flashType === 'success') {
            header('Location: /admin/portfolio.php?edit=' . $id . '&saved=1');
            exit;
        }
    } elseif (($_POST['action'] ?? '') === 'add_gallery_image') {
        $portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
        $image = handle_image_upload('gallery_image_file', '');
        if ($portfolioId && $image) {
            $order = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM portfolio_gallery WHERE portfolio_id = ' . $portfolioId)->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO portfolio_gallery (portfolio_id, image, sort_order) VALUES (:pid, :image, :order)');
            $stmt->execute(['pid' => $portfolioId, 'image' => $image, 'order' => $order]);
        }
        header('Location: /admin/portfolio.php?edit=' . $portfolioId . '&saved=1');
        exit;
    } elseif (($_POST['action'] ?? '') === 'delete_gallery_image') {
        $portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM portfolio_gallery WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['gallery_id'] ?? 0)]);
        header('Location: /admin/portfolio.php?edit=' . $portfolioId . '&saved=1');
        exit;
    }
}

$editing = null;
$galleryImages = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM portfolio_items WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($editing) {
        $stmt = $pdo->prepare('SELECT * FROM portfolio_gallery WHERE portfolio_id = :id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['id' => $editing['id']]);
        $galleryImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
  <form method="post" action="/admin/portfolio.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <input type="hidden" name="existing_image" value="<?= e($editing['image'] ?? '') ?>">

    <div class="admin-form-grid">
      <div class="form-row span-3">
        <label for="image_file">Project image</label>
        <?php if (!empty($editing['image'])): ?>
          <img class="admin-img-preview" src="<?= e($editing['image']) ?>" alt="" style="margin-block-end:10px;">
        <?php endif; ?>
        <input type="file" id="image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
      </div>

      <div class="form-row"><label for="title">Title (brand name — same in all languages)</label><input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required></div>
      <div class="form-row"><label for="slug">Slug (URL — leave blank to auto-generate from title)</label><input type="text" id="slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      <div class="form-row"><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? count($items)) ?>"></div>

      <div class="form-row"><label for="client">Client name</label><input type="text" id="client" name="client" value="<?= e($editing['client'] ?? '') ?>"></div>
      <div class="form-row"><label for="year">Year</label><input type="text" id="year" name="year" value="<?= e($editing['year'] ?? '') ?>"></div>
      <div class="form-row"><label for="project_url">Live project URL</label><input type="url" id="project_url" name="project_url" value="<?= e($editing['project_url'] ?? '') ?>" placeholder="https://"></div>

      <div class="form-row">
        <label for="category">Category (controls detail-page layout)</label>
        <select id="category" name="category">
          <?php foreach (['web' => 'Web', 'uiux' => 'UI/UX', 'mobile' => 'Mobile', 'business' => 'Business App'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($editing['category'] ?? 'web') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label for="duration">Duration (e.g. "10 weeks")</label><input type="text" id="duration" name="duration" value="<?= e($editing['duration'] ?? '') ?>"></div>
      <div class="form-row span-3"><label for="technologies">Technologies (comma-separated)</label><input type="text" id="technologies" name="technologies" value="<?= e($editing['technologies'] ?? '') ?>" placeholder="PHP, MySQL, GSAP"></div>

      <div class="form-row"><label for="metric_1_label">Metric 1 label</label><input type="text" id="metric_1_label" name="metric_1_label" value="<?= e($editing['metric_1_label'] ?? '') ?>" placeholder="Load Time"></div>
      <div class="form-row"><label for="metric_1_value">Metric 1 value</label><input type="text" id="metric_1_value" name="metric_1_value" value="<?= e($editing['metric_1_value'] ?? '') ?>" placeholder="-60%"></div>
      <div></div>
      <div class="form-row"><label for="metric_2_label">Metric 2 label</label><input type="text" id="metric_2_label" name="metric_2_label" value="<?= e($editing['metric_2_label'] ?? '') ?>"></div>
      <div class="form-row"><label for="metric_2_value">Metric 2 value</label><input type="text" id="metric_2_value" name="metric_2_value" value="<?= e($editing['metric_2_value'] ?? '') ?>"></div>
      <div></div>
      <div class="form-row"><label for="metric_3_label">Metric 3 label</label><input type="text" id="metric_3_label" name="metric_3_label" value="<?= e($editing['metric_3_label'] ?? '') ?>"></div>
      <div class="form-row"><label for="metric_3_value">Metric 3 value</label><input type="text" id="metric_3_value" name="metric_3_value" value="<?= e($editing['metric_3_value'] ?? '') ?>"></div>
      <div></div>

      <div class="form-row"><label for="tag_de">Tag (Deutsch)</label><input type="text" id="tag_de" name="tag_de" value="<?= e($editing['tag_de'] ?? '') ?>" required></div>
      <div class="form-row"><label for="tag_en">Tag (English)</label><input type="text" id="tag_en" name="tag_en" value="<?= e($editing['tag_en'] ?? '') ?>" required></div>
      <div class="form-row"><label for="tag_ar">Tag (العربية)</label><input type="text" id="tag_ar" name="tag_ar" dir="rtl" value="<?= e($editing['tag_ar'] ?? '') ?>" required></div>

      <div class="form-row span-3"><label for="description_de">Case study (Deutsch)</label><textarea id="description_de" name="description_de" rows="5"><?= e($editing['description_de'] ?? '') ?></textarea></div>
      <div class="form-row span-3"><label for="description_en">Case study (English)</label><textarea id="description_en" name="description_en" rows="5"><?= e($editing['description_en'] ?? '') ?></textarea></div>
      <div class="form-row span-3"><label for="description_ar">Case study (العربية)</label><textarea id="description_ar" name="description_ar" dir="rtl" rows="5"><?= e($editing['description_ar'] ?? '') ?></textarea></div>
    </div>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Item' ?></button>
      <?php if ($editing): ?><a href="/admin/portfolio.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<?php if ($editing): ?>
<div class="admin-panel">
  <h2>Design Gallery <span style="color:var(--color-muted); font-weight:400;">(shown for UI/UX-category projects)</span></h2>
  <?php if ($galleryImages): ?>
    <div class="admin-form-grid" style="margin-block-end:20px;">
      <?php foreach ($galleryImages as $g): ?>
        <div class="form-row">
          <img class="admin-img-preview" src="<?= e($g['image']) ?>" alt="" style="width:100%; height:100px; margin-block-end:8px;">
          <form method="post" action="/admin/portfolio.php" data-confirm="Remove this gallery image?">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_gallery_image">
            <input type="hidden" name="portfolio_id" value="<?= (int) $editing['id'] ?>">
            <input type="hidden" name="gallery_id" value="<?= (int) $g['id'] ?>">
            <button type="submit" class="btn btn--outline btn--sm is-danger">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="/admin/portfolio.php" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_gallery_image">
    <input type="hidden" name="portfolio_id" value="<?= (int) $editing['id'] ?>">
    <div class="form-row" style="flex:1; min-width:240px;">
      <label for="gallery_image_file">Add gallery image</label>
      <input type="file" id="gallery_image_file" name="gallery_image_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" required>
    </div>
    <button type="submit" class="btn btn--outline btn--sm">Add Image</button>
  </form>
</div>
<?php endif; ?>

<div class="admin-panel">
  <h2>All Portfolio Items (<?= count($items) ?>)</h2>
  <?php if (empty($items)): ?>
    <div class="admin-empty">No portfolio items yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Image</th><th>Title</th><th>Category</th><th>Client</th><th>Slug</th><th>Tag (EN)</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td class="cell-muted"><?= (int) $it['sort_order'] ?></td>
              <td><img class="admin-img-preview" src="<?= e($it['image']) ?>" alt=""></td>
              <td class="cell-strong"><?= e($it['title']) ?></td>
              <td class="cell-muted"><?= e(ucfirst($it['category'])) ?></td>
              <td class="cell-muted"><?= e($it['client']) ?></td>
              <td class="cell-muted">/pages/project.php?slug=<?= e($it['slug']) ?></td>
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
