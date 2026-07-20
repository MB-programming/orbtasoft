<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/downloader.php';

$current_admin_page = 'downloader-check';
$checks = downloader_diagnose();
$allOk = !in_array(false, array_column($checks, 'ok'), true);

require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Video Downloader — Server Check</h1>
    <p>Checks whether this server can actually run the YouTube/TikTok/Instagram/Facebook downloader tool. No SSH needed — this just runs the same checks yt-dlp itself needs.</p>
  </div>
</div>

<div class="admin-panel">
  <h2><?= $allOk ? 'All good — the downloader tool is ready to use.' : 'Something is missing — the downloader tool will show an error to visitors.' ?></h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
      <tbody>
        <?php foreach ($checks as $name => $check): ?>
          <tr>
            <td class="cell-strong"><?= e($name) ?></td>
            <td><span class="admin-badge <?= $check['ok'] ? 'is-on' : 'is-off' ?>"><?= $check['ok'] ? 'OK' : 'Missing' ?></span></td>
            <td class="cell-wrap cell-muted"><?= e($check['detail']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-panel">
  <h2>How to fix a missing check</h2>
  <p class="cell-muted">If <strong>proc_open</strong> is disabled, this is a hosting-level restriction — ask your host to enable it for this account, or run the downloader on a VPS instead of shared hosting.</p>
  <p class="cell-muted">If <strong>yt-dlp</strong> or <strong>ffmpeg</strong> is missing, upload their static (self-contained) binaries anywhere under your account, <code>chmod +x</code> them, then either add <code>YTDLP_BIN</code> / <code>FFMPEG_LOCATION</code> as environment variables in your hosting panel, or edit the two <code>define()</code> lines near the top of <code>includes/downloader.php</code> with the absolute paths.</p>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
