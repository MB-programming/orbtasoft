<?php
require __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tools.php';
set_time_limit(30);

$current_page = 'tools';
$rawUrl = trim($_GET['url'] ?? '');
$result = null;
$errorCode = null;
$score = null;

function tool_ai_meta_content(DOMXPath $xpath, string $attr, string $value): ?string
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

        $checks = [];

        // Performance
        $ttfbStatus = tools_score_threshold($fetch['time_ttfb'], 0.3, 1.5) >= 70 ? 'good' : (tools_score_threshold($fetch['time_ttfb'], 0.3, 1.5) >= 40 ? 'warn' : 'bad');
        $checks[] = ['cat' => 'performance', 'status' => $ttfbStatus, 'rec' => 'ai_rec_slow_ttfb'];

        $htmlBytes = strlen($fetch['body']);
        $heavyStatus = $htmlBytes < 150000 ? 'good' : ($htmlBytes < 350000 ? 'warn' : 'bad');
        $checks[] = ['cat' => 'performance', 'status' => $heavyStatus, 'rec' => 'ai_rec_heavy_page'];

        $encoding = tools_header($fetch['headers'], 'content-encoding');
        $compressed = $encoding !== null && preg_match('/gzip|br|deflate/i', $encoding) === 1;
        $checks[] = ['cat' => 'performance', 'status' => $compressed ? 'good' : 'warn', 'rec' => 'ai_rec_no_compression'];

        // Security
        $checks[] = ['cat' => 'security', 'status' => $isHttps ? 'good' : 'bad', 'rec' => 'ai_rec_no_https'];
        $hsts = tools_header($fetch['headers'], 'strict-transport-security');
        $checks[] = ['cat' => 'security', 'status' => !$isHttps ? 'na' : ($hsts ? 'good' : 'warn'), 'rec' => 'ai_rec_no_hsts'];
        $csp = tools_header($fetch['headers'], 'content-security-policy');
        $checks[] = ['cat' => 'security', 'status' => $csp ? 'good' : 'warn', 'rec' => 'ai_rec_no_csp'];
        $xfo = tools_header($fetch['headers'], 'x-frame-options');
        $checks[] = ['cat' => 'security', 'status' => $xfo ? 'good' : 'warn', 'rec' => 'ai_rec_no_xfo'];

        // SEO
        $titleNodes = $dom->getElementsByTagName('title');
        $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
        $checks[] = ['cat' => 'seo', 'status' => $title !== '' ? 'good' : 'bad', 'rec' => 'ai_rec_no_title'];

        $metaDesc = tool_ai_meta_content($xpath, 'name', 'description');
        $checks[] = ['cat' => 'seo', 'status' => $metaDesc ? 'good' : 'warn', 'rec' => 'ai_rec_no_meta_desc'];

        $h1Count = $dom->getElementsByTagName('h1')->length;
        $checks[] = ['cat' => 'seo', 'status' => $h1Count === 1 ? 'good' : 'warn', 'rec' => 'ai_rec_bad_h1'];

        $viewport = tool_ai_meta_content($xpath, 'name', 'viewport');
        $checks[] = ['cat' => 'seo', 'status' => $viewport ? 'good' : 'bad', 'rec' => 'ai_rec_no_viewport'];

        // UI/UX
        $images = $dom->getElementsByTagName('img');
        $imgTotal = $images->length;
        $imgWithAlt = 0;
        foreach ($images as $img) {
            if (trim($img->getAttribute('alt')) !== '') {
                $imgWithAlt++;
            }
        }
        $altCoverage = $imgTotal > 0 ? ($imgWithAlt / $imgTotal) * 100 : 100;
        $checks[] = ['cat' => 'uiux', 'status' => $imgTotal === 0 ? 'na' : ($altCoverage >= 90 ? 'good' : ($altCoverage >= 50 ? 'warn' : 'bad')), 'rec' => 'ai_rec_missing_alt'];

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
        $labelCoverage = $formFieldsTotal > 0 ? ($formFieldsLabeled / $formFieldsTotal) * 100 : 100;
        $checks[] = ['cat' => 'uiux', 'status' => $formFieldsTotal === 0 ? 'na' : ($labelCoverage >= 90 ? 'good' : 'warn'), 'rec' => 'ai_rec_no_form_labels'];

        $ctaCount = $dom->getElementsByTagName('button')->length;
        foreach ($dom->getElementsByTagName('a') as $a) {
            if (preg_match('/\b(btn|button|cta)\b/i', $a->getAttribute('class'))) {
                $ctaCount++;
            }
        }
        $checks[] = ['cat' => 'uiux', 'status' => $ctaCount > 0 ? 'good' : 'bad', 'rec' => 'ai_rec_no_cta'];

        $statuses = array_column($checks, 'status');
        $score = tools_average_score($statuses);

        $severityOrder = ['bad' => 0, 'warn' => 1, 'good' => 2, 'na' => 3];
        $issues = array_filter($checks, fn($c) => in_array($c['status'], ['bad', 'warn'], true));
        usort($issues, fn($a, $b) => $severityOrder[$a['status']] <=> $severityOrder[$b['status']]);

        $result = ['fetch' => $fetch, 'issues' => array_values($issues)];
    }
}

require __DIR__ . '/../includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('tools_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('tool_ai_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_ai_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/pages/tool-ai-checker.php" class="tool-form reveal">
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

          <?= tools_render_score($score) ?>

          <h3 class="tool-section-title"><?= e(t('ai_priority_label')) ?></h3>
          <?php if (!$result['issues']): ?>
            <div class="tool-check-item tool-check-item--good"><span class="tool-check-item__dot"></span><div class="tool-check-item__label"><?= e(t('ai_no_issues')) ?></div></div>
          <?php else: ?>
            <div class="ai-priority-list">
              <?php $catLabels = ['performance' => t('ai_cat_performance'), 'security' => t('ai_cat_security'), 'seo' => t('ai_cat_seo'), 'uiux' => t('ai_cat_uiux')]; ?>
              <?php foreach ($result['issues'] as $i => $issue): ?>
                <div class="ai-priority-item ai-priority-item--<?= e($issue['status']) ?>">
                  <span class="ai-priority-item__badge"><?= $i + 1 ?></span>
                  <div>
                    <span class="ai-priority-item__cat"><?= e($catLabels[$issue['cat']] ?? $issue['cat']) ?></span>
                    <div class="ai-priority-item__text"><?= e(t($issue['rec'])) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?= tools_render_cta() ?>

          <a href="/pages/tool-ai-checker.php" class="tool-check-another"><?= e(t('tools_check_another')) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
