<?php
require __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tools.php';
set_time_limit(30);

$current_page = 'tools';
$rawUrl = trim($_GET['url'] ?? '');
$result = null;
$errorCode = null;
$score = null;

function tool_seo_meta_content(DOMXPath $xpath, string $attr, string $value): ?string
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
        $isHttps = parse_url($fetch['final_url'], PHP_URL_SCHEME) === 'https';

        $titleNodes = $dom->getElementsByTagName('title');
        $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
        $titleLen = mb_strlen($title);
        $titleStatus = $title === '' ? 'bad' : ($titleLen >= 10 && $titleLen <= 60 ? 'good' : 'warn');

        $metaDesc = tool_seo_meta_content($xpath, 'name', 'description');
        $metaDescLen = $metaDesc ? mb_strlen($metaDesc) : 0;
        $metaDescStatus = !$metaDesc ? 'bad' : ($metaDescLen >= 50 && $metaDescLen <= 160 ? 'good' : 'warn');

        $h1Count = $dom->getElementsByTagName('h1')->length;
        $h1Status = $h1Count === 1 ? 'good' : ($h1Count === 0 ? 'bad' : 'warn');

        $canonicalNodes = $xpath->query("//link[@rel='canonical']");
        $canonical = ($canonicalNodes && $canonicalNodes->length > 0) ? $canonicalNodes->item(0)->getAttribute('href') : null;
        $canonicalStatus = $canonical ? 'good' : 'warn';

        $robotsMeta = tool_seo_meta_content($xpath, 'name', 'robots');
        $robotsBlocksIndex = $robotsMeta && preg_match('/noindex/i', $robotsMeta) === 1;
        $robotsStatus = $robotsBlocksIndex ? 'bad' : 'good';

        $robotsTxt = tools_fetch_wellknown($fetch['final_url'], '/robots.txt');
        $robotsTxtStatus = ($robotsTxt && $robotsTxt['status'] === 200) ? 'good' : 'warn';

        $sitemap = tools_fetch_wellknown($fetch['final_url'], '/sitemap.xml');
        $sitemapStatus = ($sitemap && $sitemap['status'] === 200) ? 'good' : 'warn';

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

        $ogTitle = tool_seo_meta_content($xpath, 'property', 'og:title');
        $ogDesc = tool_seo_meta_content($xpath, 'property', 'og:description');
        $ogImage = tool_seo_meta_content($xpath, 'property', 'og:image');
        $ogCount = count(array_filter([$ogTitle, $ogDesc, $ogImage]));
        $ogStatus = $ogCount === 3 ? 'good' : ($ogCount > 0 ? 'warn' : 'bad');

        $ldJsonNodes = $xpath->query("//script[@type='application/ld+json']");
        $hasStructuredData = $ldJsonNodes && $ldJsonNodes->length > 0;
        $structuredStatus = $hasStructuredData ? 'good' : 'warn';

        $viewport = tool_seo_meta_content($xpath, 'name', 'viewport');
        $viewportStatus = $viewport ? 'good' : 'bad';

        $httpsStatus = $isHttps ? 'good' : 'bad';

        $bodyNodes = $dom->getElementsByTagName('body');
        $bodyText = $bodyNodes->length > 0 ? $bodyNodes->item(0)->textContent : $dom->textContent;
        $wordCount = str_word_count(preg_replace('/\s+/u', ' ', trim($bodyText)));
        $wordCountStatus = $wordCount >= 300 ? 'good' : ($wordCount >= 100 ? 'warn' : 'bad');

        $score = tools_average_score([
            $titleStatus, $metaDescStatus, $h1Status, $canonicalStatus, $robotsStatus,
            $robotsTxtStatus, $sitemapStatus, $altStatus, $ogStatus, $structuredStatus,
            $viewportStatus, $httpsStatus, $wordCountStatus,
        ]);

        $result = [
            'fetch' => $fetch,
            'title' => $title, 'titleLen' => $titleLen, 'titleStatus' => $titleStatus,
            'metaDesc' => $metaDesc, 'metaDescLen' => $metaDescLen, 'metaDescStatus' => $metaDescStatus,
            'h1Count' => $h1Count, 'h1Status' => $h1Status,
            'canonical' => $canonical, 'canonicalStatus' => $canonicalStatus,
            'robotsMeta' => $robotsMeta, 'robotsStatus' => $robotsStatus,
            'robotsTxtStatus' => $robotsTxtStatus, 'sitemapStatus' => $sitemapStatus,
            'imgTotal' => $imgTotal, 'altCoverage' => $altCoverage, 'altStatus' => $altStatus,
            'ogCount' => $ogCount, 'ogStatus' => $ogStatus,
            'structuredStatus' => $structuredStatus, 'viewport' => $viewport, 'viewportStatus' => $viewportStatus,
            'httpsStatus' => $httpsStatus, 'wordCount' => $wordCount, 'wordCountStatus' => $wordCountStatus,
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
      <h1 class="reveal"><?= e(t('tool_seo_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_seo_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/pages/tool-seo.php" class="tool-form reveal">
        <input type="text" name="url" value="<?= e($rawUrl) ?>" placeholder="<?= e(t('tools_url_placeholder')) ?>" aria-label="<?= e(t('tools_url_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('search') ?> <?= e(t('tools_run_btn')) ?></button>
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
            <?= tools_render_check_item(t('seo_title_tag_label'), $result['titleStatus'], $result['title'] !== '' ? $result['title'] . ' (' . $result['titleLen'] . ' ' . t('seo_chars_count') . ')' : t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('seo_meta_desc_label'), $result['metaDescStatus'], $result['metaDesc'] ? $result['metaDesc'] . ' (' . $result['metaDescLen'] . ' ' . t('seo_chars_count') . ')' : t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('seo_h1_label'), $result['h1Status'], (string) $result['h1Count']) ?>
            <?= tools_render_check_item(t('seo_canonical_label'), $result['canonicalStatus'], $result['canonical'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('seo_robots_meta_label'), $result['robotsStatus'], $result['robotsMeta'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('seo_robots_txt_label'), $result['robotsTxtStatus'], $result['robotsTxtStatus'] === 'good' ? t('tools_present') : t('tools_missing')) ?>
            <?= tools_render_check_item(t('seo_sitemap_label'), $result['sitemapStatus'], $result['sitemapStatus'] === 'good' ? t('tools_present') : t('tools_missing')) ?>
            <?= tools_render_check_item(t('seo_alt_coverage_label'), $result['altStatus'], $result['imgTotal'] > 0 ? $result['altCoverage'] . '%' : t('tools_status_na')) ?>
            <?= tools_render_check_item(t('seo_og_tags_label'), $result['ogStatus'], $result['ogCount'] . ' / 3') ?>
            <?= tools_render_check_item(t('seo_structured_data_label'), $result['structuredStatus'], $result['structuredStatus'] === 'good' ? t('tools_present') : t('tools_missing')) ?>
            <?= tools_render_check_item(t('seo_https_label'), $result['httpsStatus'], $result['httpsStatus'] === 'good' ? t('tools_yes') : t('tools_no')) ?>
            <?= tools_render_check_item(t('seo_viewport_label'), $result['viewportStatus'], $result['viewport'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('seo_word_count_label'), $result['wordCountStatus'], number_format($result['wordCount']) . ' ' . t('seo_words_count')) ?>
          </div>

          <a href="/pages/tool-seo.php" class="tool-check-another"><?= e(t('tools_check_another')) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
