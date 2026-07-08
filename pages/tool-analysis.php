<?php
require __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tools.php';
set_time_limit(30);

$current_page = 'tools';
$rawUrl = trim($_GET['url'] ?? '');
$result = null;
$errorCode = null;

function tool_analysis_meta_content(DOMXPath $xpath, string $attr, string $value): ?string
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
        $host = parse_url($fetch['final_url'], PHP_URL_HOST) ?: '';

        $titleNodes = $dom->getElementsByTagName('title');
        $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';

        $metaDesc = tool_analysis_meta_content($xpath, 'name', 'description');
        $viewport = tool_analysis_meta_content($xpath, 'name', 'viewport');
        $generator = tool_analysis_meta_content($xpath, 'name', 'generator');
        $ogTitle = tool_analysis_meta_content($xpath, 'property', 'og:title');
        $twitterCard = tool_analysis_meta_content($xpath, 'name', 'twitter:card');

        $charset = null;
        foreach ($dom->getElementsByTagName('meta') as $meta) {
            if ($meta->hasAttribute('charset')) {
                $charset = $meta->getAttribute('charset');
                break;
            }
            $httpEquiv = strtolower($meta->getAttribute('http-equiv'));
            if ($httpEquiv === 'content-type' && preg_match('/charset=([\w-]+)/i', $meta->getAttribute('content'), $m)) {
                $charset = $m[1];
                break;
            }
        }

        $bodyNodes = $dom->getElementsByTagName('body');
        $bodyText = $bodyNodes->length > 0 ? $bodyNodes->item(0)->textContent : $dom->textContent;
        $wordCount = str_word_count(preg_replace('/\s+/u', ' ', trim($bodyText)));

        $headingCounts = [];
        for ($i = 1; $i <= 6; $i++) {
            $headingCounts['h' . $i] = $dom->getElementsByTagName('h' . $i)->length;
        }

        $images = $dom->getElementsByTagName('img');
        $imgTotal = $images->length;
        $imgMissingAlt = 0;
        foreach ($images as $img) {
            if (trim($img->getAttribute('alt')) === '') {
                $imgMissingAlt++;
            }
        }

        $internalLinks = 0;
        $externalLinks = 0;
        foreach ($dom->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $linkHost = parse_url($href, PHP_URL_HOST);
            if ($linkHost === null || $linkHost === $host) {
                $internalLinks++;
            } else {
                $externalLinks++;
            }
        }

        $server = tools_header($fetch['headers'], 'server');
        $poweredBy = tools_header($fetch['headers'], 'x-powered-by');

        $tech = [];
        $bodyLower = strtolower($fetch['body']);
        $techSignatures = [
            'WordPress' => ['wp-content', 'wp-includes'],
            'jQuery' => ['jquery'],
            'React' => ['react-dom', '__next', 'data-reactroot'],
            'Vue.js' => ['__vue__', 'vue.js', 'vue.min.js'],
            'Bootstrap' => ['bootstrap.min.css', 'bootstrap.css'],
            'Tailwind CSS' => ['tailwind'],
            'GSAP' => ['gsap.min.js', 'gsap.js'],
            'Three.js' => ['three.min.js', 'three.js'],
            'Shopify' => ['cdn.shopify.com'],
            'Google Analytics' => ['google-analytics.com', 'gtag('],
            'Google Fonts' => ['fonts.googleapis.com'],
        ];
        foreach ($techSignatures as $name => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($bodyLower, strtolower($needle))) {
                    $tech[] = $name;
                    break;
                }
            }
        }
        if ($generator) {
            $tech[] = $generator;
        }
        $tech = array_values(array_unique($tech));

        $result = [
            'fetch' => $fetch, 'title' => $title, 'metaDesc' => $metaDesc, 'viewport' => $viewport,
            'charset' => $charset, 'wordCount' => $wordCount, 'headingCounts' => $headingCounts,
            'imgTotal' => $imgTotal, 'imgMissingAlt' => $imgMissingAlt,
            'internalLinks' => $internalLinks, 'externalLinks' => $externalLinks,
            'server' => $server, 'poweredBy' => $poweredBy, 'tech' => $tech,
            'social' => $ogTitle !== null || $twitterCard !== null,
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
      <h1 class="reveal"><?= e(t('tool_analysis_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_analysis_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/pages/tool-analysis.php" class="tool-form reveal">
        <input type="text" name="url" value="<?= e($rawUrl) ?>" placeholder="<?= e(t('tools_url_placeholder')) ?>" aria-label="<?= e(t('tools_url_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('bar-chart-3') ?> <?= e(t('tools_run_btn')) ?></button>
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

          <div class="tool-stat-grid">
            <?= tools_stat_card(t('analysis_word_count_label'), number_format($result['wordCount'])) ?>
            <?= tools_stat_card(t('analysis_images_label'), (string) $result['imgTotal']) ?>
            <?= tools_stat_card(t('analysis_images_alt_label'), (string) $result['imgMissingAlt']) ?>
            <?= tools_stat_card(t('analysis_links_label'), $result['internalLinks'] . ' / ' . $result['externalLinks']) ?>
          </div>

          <div class="tool-checklist">
            <?= tools_render_check_item(t('analysis_title_tag_label'), $result['title'] !== '' ? 'good' : 'bad', $result['title'] !== '' ? $result['title'] : t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('analysis_meta_desc_label'), $result['metaDesc'] ? 'good' : 'warn', $result['metaDesc'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('analysis_viewport_label'), $result['viewport'] ? 'good' : 'warn', $result['viewport'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('analysis_charset_label'), $result['charset'] ? 'good' : 'warn', $result['charset'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('analysis_headings_label'), $result['headingCounts']['h1'] === 1 ? 'good' : 'warn', implode('  ', array_map(fn($k, $v) => strtoupper($k) . ': ' . $v, array_keys($result['headingCounts']), $result['headingCounts']))) ?>
            <?= tools_render_check_item(t('analysis_server_label'), $result['server'] ? 'na' : 'na', $result['server'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('analysis_powered_by_label'), $result['poweredBy'] ? 'warn' : 'good', $result['poweredBy'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('analysis_social_label'), $result['social'] ? 'good' : 'warn', $result['social'] ? t('tools_present') : t('tools_missing')) ?>
          </div>

          <h3 class="tool-section-title"><?= e(t('analysis_tech_label')) ?></h3>
          <div class="project-tech-pills">
            <?php if ($result['tech']): ?>
              <?php foreach ($result['tech'] as $techName): ?><span class="project-tech-pill"><?= e($techName) ?></span><?php endforeach; ?>
            <?php else: ?>
              <span class="project-tech-pill"><?= e(t('analysis_none_detected')) ?></span>
            <?php endif; ?>
          </div>

          <?= tools_render_cta() ?>

          <a href="/pages/tool-analysis.php" class="tool-check-another"><?= e(t('tools_check_another')) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
