<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../includes/mailer.php';
admin_require_login();
$pdo = get_db();

$globalKeys = [
    'seo_title_suffix', 'seo_org_name', 'seo_org_logo', 'seo_schema_type',
    'seo_default_og_image', 'seo_twitter_handle',
    'seo_ga_id', 'seo_google_verification', 'seo_bing_verification',
    'seo_robots_extra',
];

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'save_global') {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2');
        foreach ($globalKeys as $key) {
            $value = trim($_POST[$key] ?? '');
            $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
        }
        $flash = 'Global SEO settings saved.';
    } elseif (($_POST['action'] ?? '') === 'save_page') {
        $pageKey = $_POST['page_key'] ?? '';
        if (array_key_exists($pageKey, seo_static_pages())) {
            seo_meta_save('page', $pageKey, $_POST);
            $flash = 'SEO settings saved for "' . seo_static_pages()[$pageKey] . '".';
        }
    }
}

$settings = get_settings();
function seo_sv(array $s, string $key): string
{
    return e($s[$key] ?? '');
}

$current_admin_page = 'seo';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>SEO Settings</h1>
    <p>Global defaults, analytics, and per-page overrides. Project, service, and blog-post SEO is edited on each item's own edit screen.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<form method="post" action="/admin/seo.php">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="save_global">

  <div class="admin-panel">
    <h2>Global Defaults</h2>
    <p style="color:var(--color-muted); font-size:.85rem; margin-block-end:16px;">Used as a fallback whenever a page, project, service, or post doesn't have its own SEO override.</p>
    <div class="admin-form-grid">
      <div class="form-row"><label for="seo_title_suffix">Title Suffix</label><input type="text" id="seo_title_suffix" name="seo_title_suffix" placeholder="— Orbtasoft" value="<?= seo_sv($settings, 'seo_title_suffix') ?>"></div>
      <div class="form-row"><label for="seo_default_og_image">Default Social Share Image (URL)</label><input type="text" id="seo_default_og_image" name="seo_default_og_image" value="<?= seo_sv($settings, 'seo_default_og_image') ?>"></div>
      <div class="form-row"><label for="seo_twitter_handle">Twitter/X Handle</label><input type="text" id="seo_twitter_handle" name="seo_twitter_handle" placeholder="@orbtasoft" value="<?= seo_sv($settings, 'seo_twitter_handle') ?>"></div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Organization Schema (JSON-LD)</h2>
    <p style="color:var(--color-muted); font-size:.85rem; margin-block-end:16px;">Structured data included on every page so search engines understand who you are.</p>
    <div class="admin-form-grid">
      <div class="form-row"><label for="seo_org_name">Organization Name</label><input type="text" id="seo_org_name" name="seo_org_name" value="<?= seo_sv($settings, 'seo_org_name') ?>"></div>
      <div class="form-row"><label for="seo_org_logo">Logo URL</label><input type="text" id="seo_org_logo" name="seo_org_logo" value="<?= seo_sv($settings, 'seo_org_logo') ?>"></div>
      <div class="form-row">
        <label for="seo_schema_type">Schema Type</label>
        <select id="seo_schema_type" name="seo_schema_type">
          <?php foreach (['Organization', 'LocalBusiness', 'ProfessionalService'] as $type): ?>
            <option value="<?= e($type) ?>" <?= ($settings['seo_schema_type'] ?? 'Organization') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Analytics &amp; Verification</h2>
    <div class="admin-form-grid">
      <div class="form-row"><label for="seo_ga_id">Google Analytics Measurement ID</label><input type="text" id="seo_ga_id" name="seo_ga_id" placeholder="G-XXXXXXXXXX" value="<?= seo_sv($settings, 'seo_ga_id') ?>"></div>
      <div class="form-row"><label for="seo_google_verification">Google Site Verification Code</label><input type="text" id="seo_google_verification" name="seo_google_verification" value="<?= seo_sv($settings, 'seo_google_verification') ?>"></div>
      <div class="form-row"><label for="seo_bing_verification">Bing Site Verification Code</label><input type="text" id="seo_bing_verification" name="seo_bing_verification" value="<?= seo_sv($settings, 'seo_bing_verification') ?>"></div>
      <div class="form-row span-3"><label for="seo_robots_extra">Extra robots.txt rules</label><textarea id="seo_robots_extra" name="seo_robots_extra" rows="3" placeholder="Disallow: /admin/"><?= seo_sv($settings, 'seo_robots_extra') ?></textarea></div>
    </div>
  </div>

  <div class="admin-form-actions" style="margin-block-end:28px;">
    <button type="submit" class="btn btn--primary btn--sm">Save Global Settings</button>
  </div>
</form>

<div class="admin-panel">
  <h2>Per-Page SEO Overrides</h2>
  <p style="color:var(--color-muted); font-size:.85rem; margin-block-end:16px;">Leave a field blank to fall back to the page's automatic title/description.</p>

  <?php foreach (seo_static_pages() as $pageKey => $pageLabel): $pageSeo = seo_meta_fetch('page', $pageKey); ?>
    <details class="admin-panel content-group">
      <summary class="content-group__summary"><?= e($pageLabel) ?> <span class="content-group__count"><?= e($pageKey) ?></span></summary>
      <div class="content-group__body" style="padding:18px;">
        <form method="post" action="/admin/seo.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_page">
          <input type="hidden" name="page_key" value="<?= e($pageKey) ?>">
          <div class="admin-form-grid">
            <div class="form-row"><label>SEO Title (Deutsch)</label><input type="text" name="seo_title_de" value="<?= e($pageSeo['seo_title_de'] ?? '') ?>"></div>
            <div class="form-row"><label>SEO Title (English)</label><input type="text" name="seo_title_en" value="<?= e($pageSeo['seo_title_en'] ?? '') ?>"></div>
            <div class="form-row"><label>SEO Title (العربية)</label><input type="text" name="seo_title_ar" dir="rtl" value="<?= e($pageSeo['seo_title_ar'] ?? '') ?>"></div>

            <div class="form-row span-3"><label>Meta Description (Deutsch)</label><textarea name="seo_description_de" rows="2" maxlength="320"><?= e($pageSeo['seo_description_de'] ?? '') ?></textarea></div>
            <div class="form-row span-3"><label>Meta Description (English)</label><textarea name="seo_description_en" rows="2" maxlength="320"><?= e($pageSeo['seo_description_en'] ?? '') ?></textarea></div>
            <div class="form-row span-3"><label>Meta Description (العربية)</label><textarea name="seo_description_ar" dir="rtl" rows="2" maxlength="320"><?= e($pageSeo['seo_description_ar'] ?? '') ?></textarea></div>

            <div class="form-row"><label>Social Share Image (URL)</label><input type="text" name="og_image" value="<?= e($pageSeo['og_image'] ?? '') ?>"></div>
            <div class="form-row"><label>Canonical URL</label><input type="text" name="canonical_url" value="<?= e($pageSeo['canonical_url'] ?? '') ?>"></div>
            <div class="form-row">
              <label style="display:flex; align-items:center; gap:8px; margin-block-start:24px;">
                <input type="checkbox" name="noindex" value="1" <?= !empty($pageSeo['noindex']) ? 'checked' : '' ?>> Hide from search engines (noindex)
              </label>
            </div>
          </div>
          <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary btn--sm">Save "<?= e($pageLabel) ?>" SEO</button>
          </div>
        </form>
      </div>
    </details>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
