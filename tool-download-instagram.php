<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/downloader.php';
require_once __DIR__ . '/includes/tools.php';

$current_page = 'tools';
$platform = 'instagram';
$videoUrl = trim($_GET['url'] ?? '');
$errorCode = null;
$info = null;

if ($videoUrl !== '') {
    $validated = downloader_validate_url($videoUrl, $platform);
    if (!$validated['ok']) {
        $errorCode = $validated['error'];
    } else {
        $videoUrl = $validated['url'];
        $info = downloader_fetch_info($videoUrl);
        if (!$info['ok']) {
            $errorCode = $info['error'];
        }
    }
} elseif (isset($_GET['url'])) {
    $errorCode = 'empty';
}

require __DIR__ . '/includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('tools_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('tool_download_instagram_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_download_instagram_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/tool-download-instagram.php" class="tool-form reveal no-print">
        <input type="text" name="url" value="<?= e($videoUrl) ?>" placeholder="<?= e(t('downloader_url_placeholder_instagram')) ?>" aria-label="<?= e(t('downloader_url_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('download') ?> <?= e(t('downloader_fetch_btn')) ?></button>
      </form>

      <?php if ($errorCode): ?>
        <div class="tool-error"><?= e(t('downloader_error_' . $errorCode)) ?></div>
      <?php elseif ($info): ?>
        <div class="tool-result">
          <?= tools_render_report_header(t('tool_download_instagram_title'), $videoUrl) ?>

          <div class="downloader-preview">
            <?php if ($info['thumbnail'] !== ''): ?>
              <div class="downloader-preview__thumb"><img src="<?= e($info['thumbnail']) ?>" alt="" loading="lazy"></div>
            <?php endif; ?>
            <div class="downloader-preview__body">
              <div class="downloader-preview__title"><?= e($info['title']) ?></div>
              <div class="downloader-preview__meta">
                <?php if ($info['uploader'] !== ''): ?><span><?= e(t('downloader_preview_uploader')) ?>: <strong><?= e($info['uploader']) ?></strong></span><?php endif; ?>
                <?php if ($info['duration'] > 0): ?><span><?= e(t('downloader_preview_duration')) ?>: <strong dir="ltr"><?= e(downloader_format_duration($info['duration'])) ?></strong></span><?php endif; ?>
              </div>
            </div>
          </div>

          <h3 class="tool-section-title"><?= e(t('downloader_choose_quality')) ?></h3>
          <div class="downloader-qualities">
            <?php foreach (downloader_available_qualities($info['heights']) as $quality):
              $downloadHref = '/download-video.php?' . http_build_query(['platform' => $platform, 'url' => $videoUrl, 'quality' => $quality['key']]);
            ?>
              <a class="btn btn--outline downloader-quality-btn" href="<?= e($downloadHref) ?>"><?= icon('download') ?> <?= e($quality['label']) ?></a>
            <?php endforeach; ?>
          </div>

          <p class="downloader-note"><?= e(t('downloader_note')) ?></p>

          <?= tools_render_cta() ?>

          <div class="tool-actions-row">
            <a href="/tool-download-instagram.php" class="tool-check-another no-print"><?= e(t('tools_check_another')) ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
