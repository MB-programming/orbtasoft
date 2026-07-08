<?php
require __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tools.php';
set_time_limit(30);

$current_page = 'tools';
$rawUrl = trim($_GET['url'] ?? '');
$result = null;
$errorCode = null;
$score = null;

function tool_uiux_meta_content(DOMXPath $xpath, string $attr, string $value): ?string
{
    $nodes = $xpath->query("//meta[@$attr='$value']");
    if ($nodes && $nodes->length > 0) {
        $content = $nodes->item(0)->getAttribute('content');
        return $content !== '' ? $content : null;
    }
    return null;
}

if ($rawUrl !== '') {
    $fetch = tools_fetch($rawUrl);
    if (!$fetch['ok']) {
        $errorCode = $fetch['error'];
    } else {
        $dom = tools_parse_html($fetch['body']);
        $xpath = new DOMXPath($dom);

        $viewport = tool_uiux_meta_content($xpath, 'name', 'viewport');
        $viewportStatus = $viewport ? 'good' : 'bad';

        $faviconNodes = $xpath->query("//link[contains(translate(@rel, 'ICON', 'icon'), 'icon')]");
        $hasFavicon = $faviconNodes && $faviconNodes->length > 0;
        $faviconStatus = $hasFavicon ? 'good' : 'warn';

        $touchIconNodes = $xpath->query("//link[contains(@rel, 'apple-touch-icon')]");
        $hasTouchIcon = $touchIconNodes && $touchIconNodes->length > 0;
        $touchIconStatus = $hasTouchIcon ? 'good' : 'warn';

        $fontLinks = 0;
        foreach ($dom->getElementsByTagName('link') as $link) {
            $href = strtolower($link->getAttribute('href'));
            if (str_contains($href, 'fonts.googleapis') || str_contains($href, 'fonts.gstatic') || preg_match('/\.(woff2?|ttf|otf)(\?|$)/', $href)) {
                $fontLinks++;
            }
        }
        $fontFaceCount = preg_match_all('/@font-face/i', $fetch['body']);
        $fontCount = $fontLinks + $fontFaceCount;
        $fontStatus = $fontCount <= 3 ? 'good' : ($fontCount <= 6 ? 'warn' : 'bad');

        $images = $dom->getElementsByTagName('img');
        $imgTotal = $images->length;
        $imgWithAlt = 0;
        foreach ($images as $img) {
            if (trim($img->getAttribute('alt')) !== '') {
                $imgWithAlt++;
            }
        }
        $altCoverage = $imgTotal > 0 ? (int) round(($imgWithAlt / $imgTotal) * 100) : 100;
        $altStatus = $imgTotal === 0 ? 'na' : ($altCoverage >= 90 ? 'good' : ($altCoverage >= 50 ? 'warn' : 'bad'));

        $labeledIds = [];
        foreach ($dom->getElementsByTagName('label') as $label) {
            $for = $label->getAttribute('for');
            if ($for !== '') {
                $labeledIds[$for] = true;
            }
        }
        $formFieldsTotal = 0;
        $formFieldsLabeled = 0;
        foreach (['input', 'select', 'textarea'] as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $field) {
                $type = strtolower($field->getAttribute('type'));
                if (in_array($type, ['hidden', 'submit', 'button', 'image'], true)) {
                    continue;
                }
                $formFieldsTotal++;
                $id = $field->getAttribute('id');
                if (($id !== '' && isset($labeledIds[$id])) || $field->getAttribute('aria-label') !== '' || $field->getAttribute('aria-labelledby') !== '') {
                    $formFieldsLabeled++;
                }
            }
        }
        $labelCoverage = $formFieldsTotal > 0 ? (int) round(($formFieldsLabeled / $formFieldsTotal) * 100) : 100;
        $labelStatus = $formFieldsTotal === 0 ? 'na' : ($labelCoverage >= 90 ? 'good' : ($labelCoverage >= 50 ? 'warn' : 'bad'));

        $ariaCount = preg_match_all('/\s(aria-[a-z]+|role)=/i', $fetch['body']);
        $ariaStatus = $ariaCount > 0 ? 'good' : 'warn';

        $ctaCount = $dom->getElementsByTagName('button')->length;
        foreach ($dom->getElementsByTagName('input') as $input) {
            if (in_array(strtolower($input->getAttribute('type')), ['submit', 'button'], true)) {
                $ctaCount++;
            }
        }
        foreach ($dom->getElementsByTagName('a') as $a) {
            if (preg_match('/\b(btn|button|cta)\b/i', $a->getAttribute('class'))) {
                $ctaCount++;
            }
        }
        $ctaStatus = $ctaCount > 0 ? 'good' : 'bad';

        $bodyNodes = $dom->getElementsByTagName('body');
        $bodyText = $bodyNodes->length > 0 ? $bodyNodes->item(0)->textContent : $dom->textContent;
        $textLen = mb_strlen(trim(preg_replace('/\s+/u', ' ', $bodyText)));
        $htmlLen = strlen($fetch['body']);
        $textRatio = $htmlLen > 0 ? round(($textLen / $htmlLen) * 100, 1) : 0;
        $textRatioStatus = $textRatio >= 15 ? 'good' : ($textRatio >= 5 ? 'warn' : 'bad');

        $h1Count = $dom->getElementsByTagName('h1')->length;
        $h1Status = $h1Count === 1 ? 'good' : ($h1Count === 0 ? 'bad' : 'warn');

        $score = tools_average_score([
            $viewportStatus, $faviconStatus, $touchIconStatus, $fontStatus, $altStatus,
            $labelStatus, $ariaStatus, $ctaStatus, $textRatioStatus, $h1Status,
        ]);

        $result = [
            'fetch' => $fetch,
            'viewport' => $viewport, 'viewportStatus' => $viewportStatus,
            'faviconStatus' => $faviconStatus, 'touchIconStatus' => $touchIconStatus,
            'fontCount' => $fontCount, 'fontStatus' => $fontStatus,
            'imgTotal' => $imgTotal, 'altCoverage' => $altCoverage, 'altStatus' => $altStatus,
            'formFieldsTotal' => $formFieldsTotal, 'labelCoverage' => $labelCoverage, 'labelStatus' => $labelStatus,
            'ariaCount' => $ariaCount, 'ariaStatus' => $ariaStatus,
            'ctaCount' => $ctaCount, 'ctaStatus' => $ctaStatus,
            'textRatio' => $textRatio, 'textRatioStatus' => $textRatioStatus,
            'h1Count' => $h1Count, 'h1Status' => $h1Status,
        ];
    }
}

require __DIR__ . '/../includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('tools_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('tool_uiux_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_uiux_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/pages/tool-uiux.php" class="tool-form reveal">
        <input type="text" name="url" value="<?= e($rawUrl) ?>" placeholder="<?= e(t('tools_url_placeholder')) ?>" aria-label="<?= e(t('tools_url_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('layout-panel-top') ?> <?= e(t('tools_run_btn')) ?></button>
      </form>

      <?php if ($errorCode): ?>
        <?= tools_render_error($errorCode) ?>
      <?php elseif ($result): ?>
        <div class="tool-result">
          <div class="tool-result__head">
            <div>
              <h2><?= e(t('tools_result_for')) ?></h2>
              <a href="<?= e($result['fetch']['final_url']) ?>" target="_blank" rel="noopener noreferrer" style="direction:ltr; display:inline-block;"><?= e($result['fetch']['final_url']) ?></a>
            </div>
          </div>

          <?= tools_render_score($score) ?>

          <div class="tool-checklist">
            <?= tools_render_check_item(t('uiux_viewport_label'), $result['viewportStatus'], $result['viewport'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('uiux_favicon_label'), $result['faviconStatus'], $result['faviconStatus'] === 'good' ? t('tools_present') : t('tools_missing')) ?>
            <?= tools_render_check_item(t('uiux_touch_icon_label'), $result['touchIconStatus'], $result['touchIconStatus'] === 'good' ? t('tools_present') : t('tools_missing')) ?>
            <?= tools_render_check_item(t('uiux_font_count_label'), $result['fontStatus'], (string) $result['fontCount']) ?>
            <?= tools_render_check_item(t('uiux_alt_coverage_label'), $result['altStatus'], $result['imgTotal'] > 0 ? $result['altCoverage'] . '%' : t('tools_status_na')) ?>
            <?= tools_render_check_item(t('uiux_form_labels_label'), $result['labelStatus'], $result['formFieldsTotal'] > 0 ? $result['labelCoverage'] . '%' : t('tools_status_na')) ?>
            <?= tools_render_check_item(t('uiux_aria_label'), $result['ariaStatus'], (string) $result['ariaCount']) ?>
            <?= tools_render_check_item(t('uiux_cta_label'), $result['ctaStatus'], (string) $result['ctaCount']) ?>
            <?= tools_render_check_item(t('uiux_text_ratio_label'), $result['textRatioStatus'], $result['textRatio'] . '%') ?>
            <?= tools_render_check_item(t('uiux_heading_label'), $result['h1Status'], (string) $result['h1Count']) ?>
          </div>

          <?= tools_render_cta() ?>

          <a href="/pages/tool-uiux.php" class="tool-check-another"><?= e(t('tools_check_another')) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
