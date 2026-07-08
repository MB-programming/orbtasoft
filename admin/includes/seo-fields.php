<?php
/**
 * Reusable "SEO" fieldset for the portfolio/services/blog admin edit forms.
 * Expects $seoData (array from seo_meta_fetch(), or null) in scope. Field names
 * match seo_meta_save()'s expected $_POST keys exactly, so callers can pass
 * $_POST straight through.
 */
$seoData = $seoData ?? null;
if (!function_exists('sf')) {
    function sf(?array $d, string $key): string
    {
        return e($d[$key] ?? '');
    }
}
?>
<details class="admin-panel content-group">
  <summary class="content-group__summary">SEO <span class="content-group__count">Advanced</span></summary>
  <div class="content-group__body" style="padding: 18px;">
    <div class="admin-form-grid">
      <div class="form-row"><label for="seo_title_de">SEO Title (Deutsch)</label><input type="text" id="seo_title_de" name="seo_title_de" value="<?= sf($seoData, 'seo_title_de') ?>" placeholder="Leave blank to auto-generate"></div>
      <div class="form-row"><label for="seo_title_en">SEO Title (English)</label><input type="text" id="seo_title_en" name="seo_title_en" value="<?= sf($seoData, 'seo_title_en') ?>" placeholder="Leave blank to auto-generate"></div>
      <div class="form-row"><label for="seo_title_ar">SEO Title (العربية)</label><input type="text" id="seo_title_ar" name="seo_title_ar" dir="rtl" value="<?= sf($seoData, 'seo_title_ar') ?>" placeholder="اتركه فارغًا للتوليد التلقائي"></div>

      <div class="form-row span-3"><label for="seo_description_de">Meta Description (Deutsch)</label><textarea id="seo_description_de" name="seo_description_de" rows="2" maxlength="320"><?= sf($seoData, 'seo_description_de') ?></textarea></div>
      <div class="form-row span-3"><label for="seo_description_en">Meta Description (English)</label><textarea id="seo_description_en" name="seo_description_en" rows="2" maxlength="320"><?= sf($seoData, 'seo_description_en') ?></textarea></div>
      <div class="form-row span-3"><label for="seo_description_ar">Meta Description (العربية)</label><textarea id="seo_description_ar" name="seo_description_ar" dir="rtl" rows="2" maxlength="320"><?= sf($seoData, 'seo_description_ar') ?></textarea></div>

      <div class="form-row"><label for="og_image">Social Share Image (OG image URL)</label><input type="text" id="og_image" name="og_image" value="<?= sf($seoData, 'og_image') ?>" placeholder="Leave blank to use the cover image"></div>
      <div class="form-row"><label for="canonical_url">Canonical URL</label><input type="text" id="canonical_url" name="canonical_url" value="<?= sf($seoData, 'canonical_url') ?>" placeholder="Leave blank for default"></div>
      <div class="form-row">
        <label style="display:flex; align-items:center; gap:8px; margin-block-start:24px;">
          <input type="checkbox" name="noindex" value="1" <?= !empty($seoData['noindex']) ? 'checked' : '' ?>> Hide from search engines (noindex)
        </label>
      </div>
    </div>
  </div>
</details>
