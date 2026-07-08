<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/tools.php';
set_time_limit(45);

$current_page = 'tools';
$rawUrl = trim($_GET['url'] ?? '');
$result = null;
$errorCode = null;
$score = null;

if ($rawUrl !== '') {
    $fetch = tools_fetch($rawUrl);
    if (!$fetch['ok']) {
        $errorCode = $fetch['error'];
    } else {
        $dom = tools_parse_html($fetch['body']);
        $assets = tools_collect_assets($dom, $fetch['final_url']);
        $assetCount = count($assets['css']) + count($assets['js']) + count($assets['img']);

        $totalAssetBytes = 0;
        $breakdown = [];
        foreach ($assets as $type => $urls) {
            $typeBytes = 0;
            foreach ($urls as $url) {
                $size = tools_probe_asset_size($url);
                if ($size !== null) {
                    $typeBytes += $size;
                }
            }
            $breakdown[$type] = ['count' => count($urls), 'bytes' => $typeBytes];
            $totalAssetBytes += $typeBytes;
        }

        $htmlBytes = strlen($fetch['body']);
        $totalWeight = $htmlBytes + $totalAssetBytes;

        $encoding = tools_header($fetch['headers'], 'content-encoding');
        $compressed = $encoding !== null && preg_match('/gzip|br|deflate/i', $encoding) === 1;
        $cacheControl = tools_header($fetch['headers'], 'cache-control');
        $expires = tools_header($fetch['headers'], 'expires');
        $cached = ($cacheControl !== null && preg_match('/max-age|public/i', $cacheControl) === 1) || $expires !== null;

        $ttfbScore = tools_score_threshold($fetch['time_ttfb'], 0.3, 1.5);
        $totalScore = tools_score_threshold($fetch['time_total'], 0.8, 3.0);
        $weightScore = tools_score_threshold($totalWeight, 500000, 3000000);
        $compressionScore = $compressed ? 100 : 40;
        $cachingScore = $cached ? 100 : 60;

        $score = (int) round($ttfbScore * 0.25 + $totalScore * 0.25 + $weightScore * 0.3 + $compressionScore * 0.1 + $cachingScore * 0.1);

        $tipKeys = [];
        if ($ttfbScore < 70) $tipKeys[] = 'ai_rec_slow_ttfb';
        if ($weightScore < 70) $tipKeys[] = 'ai_rec_heavy_page';
        if (!$compressed) $tipKeys[] = 'ai_rec_no_compression';
        if (!$cached) $tipKeys[] = 'tip_no_caching';

        $result = [
            'fetch' => $fetch, 'assetCount' => $assetCount, 'breakdown' => $breakdown,
            'htmlBytes' => $htmlBytes, 'totalWeight' => $totalWeight,
            'compressed' => $compressed, 'cached' => $cached, 'tipKeys' => $tipKeys,
        ];
    }
}

require __DIR__ . '/includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('tools_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('tool_performance_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_performance_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/tool-performance.php" class="tool-form reveal no-print">
        <input type="text" name="url" value="<?= e($rawUrl) ?>" placeholder="<?= e(t('tools_url_placeholder')) ?>" aria-label="<?= e(t('tools_url_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('gauge') ?> <?= e(t('tools_run_btn')) ?></button>
      </form>

      <?php if ($errorCode): ?>
        <?= tools_render_error($errorCode) ?>
      <?php elseif ($result): ?>
        <div class="tool-result">
          <?= tools_render_report_header(t('tool_performance_title'), $result['fetch']['final_url']) ?>

          <?= tools_render_score($score) ?>

          <div class="tool-stat-grid">
            <?= tools_stat_card(t('perf_ttfb_label'), number_format($result['fetch']['time_ttfb'] * 1000, 0) . ' ms') ?>
            <?= tools_stat_card(t('perf_total_time_label'), number_format($result['fetch']['time_total'] * 1000, 0) . ' ms') ?>
            <?= tools_stat_card(t('perf_page_size_label'), tools_format_bytes($result['htmlBytes'])) ?>
            <?= tools_stat_card(t('perf_total_weight_label'), tools_format_bytes($result['totalWeight'])) ?>
            <?= tools_stat_card(t('perf_requests_label'), (string) $result['assetCount']) ?>
            <?= tools_stat_card(t('perf_redirects_label'), (string) (count($result['fetch']['hops']) - 1)) ?>
          </div>

          <div class="tool-checklist">
            <?= tools_render_check_item(t('perf_compression_label'), $result['compressed'] ? 'good' : 'warn', $result['compressed'] ? t('tools_yes') : t('tools_no')) ?>
            <?= tools_render_check_item(t('perf_caching_label'), $result['cached'] ? 'good' : 'warn', $result['cached'] ? t('tools_yes') : t('tools_no')) ?>
          </div>

          <?php if ($result['assetCount'] > 0): ?>
            <h3 class="tool-section-title"><?= e(t('perf_breakdown_heading')) ?></h3>
            <div style="overflow-x:auto;">
              <table class="tool-table">
                <thead><tr><th><?= e(t('perf_breakdown_type')) ?></th><th><?= e(t('perf_breakdown_count')) ?></th><th><?= e(t('perf_breakdown_size')) ?></th></tr></thead>
                <tbody>
                  <?php foreach (['css' => 'CSS', 'js' => 'JavaScript', 'img' => 'Images'] as $key => $label): ?>
                    <tr>
                      <td><?= e($label) ?></td>
                      <td><?= (int) $result['breakdown'][$key]['count'] ?></td>
                      <td style="direction:ltr; text-align:start;"><?= e(tools_format_bytes($result['breakdown'][$key]['bytes'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <?= tools_render_tips($result['tipKeys']) ?>

          <?= tools_render_cta() ?>

          <div class="tool-actions-row">
            <?= tools_render_pdf_button() ?>
            <a href="/tool-performance.php" class="tool-check-another no-print"><?= e(t('tools_check_another')) ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
