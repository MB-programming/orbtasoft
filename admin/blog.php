<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/seo.php';
admin_require_login();
$pdo = get_db();

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $flash = 'Blog post deleted.';
    } elseif (($_POST['action'] ?? '') === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'cover_image' => handle_image_upload('cover_image_file', trim($_POST['existing_cover_image'] ?? '')),
            'author' => trim($_POST['author'] ?? ''),
            'title_de' => trim($_POST['title_de'] ?? ''),
            'title_en' => trim($_POST['title_en'] ?? ''),
            'title_ar' => trim($_POST['title_ar'] ?? ''),
            'excerpt_de' => trim($_POST['excerpt_de'] ?? ''),
            'excerpt_en' => trim($_POST['excerpt_en'] ?? ''),
            'excerpt_ar' => trim($_POST['excerpt_ar'] ?? ''),
            'content_de' => trim($_POST['content_de'] ?? ''),
            'content_en' => trim($_POST['content_en'] ?? ''),
            'content_ar' => trim($_POST['content_ar'] ?? ''),
        ];
        $publishedAt = trim($_POST['published_at'] ?? '');
        $data['published_at'] = $publishedAt !== '' ? str_replace('T', ' ', $publishedAt) . ':00' : date('Y-m-d H:i:s');
        $slugInput = trim($_POST['slug'] ?? '');

        $required = [
            $data['cover_image'], $data['author'],
            $data['title_de'], $data['title_en'], $data['title_ar'],
            $data['excerpt_de'], $data['excerpt_en'], $data['excerpt_ar'],
            $data['content_de'], $data['content_en'], $data['content_ar'],
        ];
        if (in_array('', $required, true)) {
            $flash = 'Please fill in every field for all three languages.';
            $flashType = 'error';
        } else {
            $data['slug'] = slugify($slugInput !== '' ? $slugInput : $data['title_en']);
            try {
                if ($id > 0) {
                    $data['id'] = $id;
                    $pdo->prepare('UPDATE blog_posts SET slug=:slug, cover_image=:cover_image, author=:author, title_de=:title_de, title_en=:title_en, title_ar=:title_ar, excerpt_de=:excerpt_de, excerpt_en=:excerpt_en, excerpt_ar=:excerpt_ar, content_de=:content_de, content_en=:content_en, content_ar=:content_ar, published_at=:published_at WHERE id=:id')->execute($data);
                    $flash = 'Blog post updated.';
                } else {
                    $pdo->prepare('INSERT INTO blog_posts (slug, cover_image, author, title_de, title_en, title_ar, excerpt_de, excerpt_en, excerpt_ar, content_de, content_en, content_ar, published_at) VALUES (:slug, :cover_image, :author, :title_de, :title_en, :title_ar, :excerpt_de, :excerpt_en, :excerpt_ar, :content_de, :content_en, :content_ar, :published_at)')->execute($data);
                    $flash = 'Blog post created.';
                }
            } catch (PDOException $e) {
                $flash = str_contains($e->getMessage(), 'Duplicate') ? 'That slug is already in use — choose another.' : 'Something went wrong, please try again.';
                $flashType = 'error';
            }
            if ($flashType === 'success') {
                seo_meta_save('blog_post', $data['slug'], $_POST);
            }
        }
    }
}

$editing = null;
$seoData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($editing) {
        $seoData = seo_meta_fetch('blog_post', $editing['slug']);
    }
}

$posts = $pdo->query('SELECT * FROM blog_posts ORDER BY published_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);

$current_admin_page = 'blog';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Blog</h1>
    <p>Manage articles shown on the public Journal page.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Blog Post' : 'Add New Blog Post' ?></h2>
  <form method="post" action="/admin/blog.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <input type="hidden" name="existing_cover_image" value="<?= e($editing['cover_image'] ?? '') ?>">

    <div class="admin-form-grid">
      <div class="form-row span-3">
        <label for="cover_image_file">Cover image</label>
        <?php if (!empty($editing['cover_image'])): ?>
          <img class="admin-img-preview" src="<?= e($editing['cover_image']) ?>" alt="" style="margin-block-end:10px;">
        <?php endif; ?>
        <input type="file" id="cover_image_file" name="cover_image_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
      </div>

      <div class="form-row"><label for="author">Author</label><input type="text" id="author" name="author" value="<?= e($editing['author'] ?? '') ?>" required></div>
      <div class="form-row"><label for="slug">Slug (leave blank to auto-generate from EN title)</label><input type="text" id="slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      <div class="form-row"><label for="published_at">Published at</label><input type="datetime-local" id="published_at" name="published_at" value="<?= e($editing ? str_replace(' ', 'T', substr($editing['published_at'], 0, 16)) : date('Y-m-d\TH:i')) ?>"></div>

      <div class="form-row"><label for="title_de">Title (Deutsch)</label><input type="text" id="title_de" name="title_de" value="<?= e($editing['title_de'] ?? '') ?>" required></div>
      <div class="form-row"><label for="title_en">Title (English)</label><input type="text" id="title_en" name="title_en" value="<?= e($editing['title_en'] ?? '') ?>" required></div>
      <div class="form-row"><label for="title_ar">Title (العربية)</label><input type="text" id="title_ar" name="title_ar" dir="rtl" value="<?= e($editing['title_ar'] ?? '') ?>" required></div>

      <div class="form-row"><label for="excerpt_de">Excerpt (Deutsch)</label><textarea id="excerpt_de" name="excerpt_de" required><?= e($editing['excerpt_de'] ?? '') ?></textarea></div>
      <div class="form-row"><label for="excerpt_en">Excerpt (English)</label><textarea id="excerpt_en" name="excerpt_en" required><?= e($editing['excerpt_en'] ?? '') ?></textarea></div>
      <div class="form-row"><label for="excerpt_ar">Excerpt (العربية)</label><textarea id="excerpt_ar" name="excerpt_ar" dir="rtl" required><?= e($editing['excerpt_ar'] ?? '') ?></textarea></div>

      <div class="form-row span-3"><label for="content_de">Content (Deutsch — separate paragraphs with a blank line)</label><textarea id="content_de" name="content_de" rows="8" required><?= e($editing['content_de'] ?? '') ?></textarea></div>
      <div class="form-row span-3"><label for="content_en">Content (English — separate paragraphs with a blank line)</label><textarea id="content_en" name="content_en" rows="8" required><?= e($editing['content_en'] ?? '') ?></textarea></div>
      <div class="form-row span-3"><label for="content_ar">Content (العربية — افصل الفقرات بسطر فارغ)</label><textarea id="content_ar" name="content_ar" dir="rtl" rows="8" required><?= e($editing['content_ar'] ?? '') ?></textarea></div>
    </div>

    <?php require __DIR__ . '/includes/seo-fields.php'; ?>

    <div class="admin-form-actions">
      <button type="submit" class="btn btn--primary btn--sm"><?= $editing ? 'Save Changes' : 'Create Post' ?></button>
      <?php if ($editing): ?><a href="/admin/blog.php" class="btn btn--outline btn--sm">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Blog Posts (<?= count($posts) ?>)</h2>
  <?php if (empty($posts)): ?>
    <div class="admin-empty">No blog posts yet — add your first one above.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Cover</th><th>Title (EN)</th><th>Author</th><th>Published</th><th>Slug</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($posts as $p): ?>
            <tr>
              <td><img class="admin-img-preview" src="<?= e($p['cover_image']) ?>" alt=""></td>
              <td class="cell-strong"><?= e($p['title_en']) ?></td>
              <td class="cell-muted"><?= e($p['author']) ?></td>
              <td class="cell-muted"><?= e($p['published_at']) ?></td>
              <td class="cell-muted">/blog-post.php?slug=<?= e($p['slug']) ?></td>
              <td>
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="/admin/blog.php?edit=<?= (int) $p['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a>
                  <form method="post" action="/admin/blog.php" data-confirm="Delete this blog post?">
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
