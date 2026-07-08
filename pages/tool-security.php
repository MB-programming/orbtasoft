<?php
require __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tools.php';
set_time_limit(30);

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
        $finalScheme = parse_url($fetch['final_url'], PHP_URL_SCHEME);
        $firstHopScheme = parse_url($fetch['hops'][0]['url'], PHP_URL_SCHEME);
        $isHttps = $finalScheme === 'https';

        $httpsStatus = $isHttps ? 'good' : 'bad';
        $httpsDetail = $isHttps
            ? ($firstHopScheme === 'http' ? t('tools_yes') . ' (' . t('perf_redirects_label') . ')' : t('tools_yes'))
            : t('tools_no');

        $hsts = tools_header($fetch['headers'], 'strict-transport-security');
        $csp = tools_header($fetch['headers'], 'content-security-policy');
        $xfo = tools_header($fetch['headers'], 'x-frame-options');
        $xcto = tools_header($fetch['headers'], 'x-content-type-options');
        $referrerPolicy = tools_header($fetch['headers'], 'referrer-policy');
        $permissionsPolicy = tools_header($fetch['headers'], 'permissions-policy');
        $server = tools_header($fetch['headers'], 'server');

        $hstsStatus = !$isHttps ? 'na' : ($hsts ? 'good' : 'bad');
        $cspStatus = $csp ? 'good' : 'warn';
        $xfoStatus = $xfo ? 'good' : 'warn';
        $xctoStatus = $xcto ? 'good' : 'warn';
        $referrerStatus = $referrerPolicy ? 'good' : 'warn';
        $permissionsStatus = $permissionsPolicy ? 'good' : 'warn';

        $serverDiscloses = $server !== null && preg_match('/\d+\.\d+/', $server) === 1;
        $serverStatus = $serverDiscloses ? 'warn' : 'good';

        $setCookies = $fetch['headers']['set-cookie'] ?? [];
        if (!is_array($setCookies)) {
            $setCookies = $setCookies !== null ? [$setCookies] : [];
        }
        $cookieIssues = 0;
        foreach ($setCookies as $cookie) {
            $lower = strtolower($cookie);
            if (!str_contains($lower, 'secure') || !str_contains($lower, 'httponly') || !str_contains($lower, 'samesite')) {
                $cookieIssues++;
            }
        }
        $cookieStatus = !$setCookies ? 'na' : ($cookieIssues === 0 ? 'good' : 'warn');
        $cookieDetail = !$setCookies ? t('sec_no_cookies') : sprintf('%d/%d', count($setCookies) - $cookieIssues, count($setCookies));

        $mixedContentCount = 0;
        if ($isHttps) {
            $mixedContentCount = preg_match_all('/(?:src|href)=["\']http:\/\/(?!localhost)/i', $fetch['body']);
        }
        $mixedStatus = !$isHttps ? 'na' : ($mixedContentCount > 0 ? 'warn' : 'good');

        $tlsInfo = null;
        $tlsStatus = 'na';
        $tlsDetail = '';
        if ($isHttps) {
            $urlCheck = tools_normalize_and_validate_url($fetch['final_url']);
            $tlsInfo = $urlCheck['ok'] ? tools_tls_certificate_info($urlCheck['host'], $urlCheck['ip']) : null;
            if ($tlsInfo && $tlsInfo['valid_to']) {
                $daysLeft = (int) floor(($tlsInfo['valid_to'] - time()) / 86400);
                $tlsStatus = $daysLeft > 14 ? 'good' : ($daysLeft > 0 ? 'warn' : 'bad');
                $tlsDetail = t('sec_tls_valid_until') . ': ' . date('Y-m-d', $tlsInfo['valid_to']) . ' — ' . t('sec_tls_issuer') . ': ' . ($tlsInfo['issuer'] ?? '?');
            } else {
                $tlsStatus = 'bad';
                $tlsDetail = t('sec_none_found');
            }
        }

        $score = tools_average_score([
            $httpsStatus, $hstsStatus, $cspStatus, $xfoStatus, $xctoStatus,
            $referrerStatus, $permissionsStatus, $serverStatus, $cookieStatus, $mixedStatus, $tlsStatus,
        ]);

        $result = [
            'fetch' => $fetch, 'httpsStatus' => $httpsStatus, 'httpsDetail' => $httpsDetail,
            'hstsStatus' => $hstsStatus, 'hsts' => $hsts,
            'cspStatus' => $cspStatus, 'csp' => $csp,
            'xfoStatus' => $xfoStatus, 'xfo' => $xfo,
            'xctoStatus' => $xctoStatus, 'xcto' => $xcto,
            'referrerStatus' => $referrerStatus, 'referrerPolicy' => $referrerPolicy,
            'permissionsStatus' => $permissionsStatus, 'permissionsPolicy' => $permissionsPolicy,
            'serverStatus' => $serverStatus, 'server' => $server,
            'cookieStatus' => $cookieStatus, 'cookieDetail' => $cookieDetail,
            'mixedStatus' => $mixedStatus, 'mixedContentCount' => $mixedContentCount,
            'tlsStatus' => $tlsStatus, 'tlsDetail' => $tlsDetail,
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
      <h1 class="reveal"><?= e(t('tool_security_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_security_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/pages/tool-security.php" class="tool-form reveal">
        <input type="text" name="url" value="<?= e($rawUrl) ?>" placeholder="<?= e(t('tools_url_placeholder')) ?>" aria-label="<?= e(t('tools_url_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('shield-alert') ?> <?= e(t('tools_run_btn')) ?></button>
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
            <?= tools_render_check_item(t('sec_https_label'), $result['httpsStatus'], $result['httpsDetail']) ?>
            <?= tools_render_check_item(t('sec_hsts_label'), $result['hstsStatus'], $result['hsts'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('sec_csp_label'), $result['cspStatus'], $result['csp'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('sec_xfo_label'), $result['xfoStatus'], $result['xfo'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('sec_xcto_label'), $result['xctoStatus'], $result['xcto'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('sec_referrer_label'), $result['referrerStatus'], $result['referrerPolicy'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('sec_permissions_label'), $result['permissionsStatus'], $result['permissionsPolicy'] ?: t('tools_missing')) ?>
            <?= tools_render_check_item(t('sec_server_disclosure_label'), $result['serverStatus'], $result['server'] ?: t('analysis_not_found')) ?>
            <?= tools_render_check_item(t('sec_cookies_label'), $result['cookieStatus'], $result['cookieDetail']) ?>
            <?= tools_render_check_item(t('sec_mixed_content_label'), $result['mixedStatus'], $result['mixedContentCount'] > 0 ? (string) $result['mixedContentCount'] : t('sec_none_found')) ?>
            <?= tools_render_check_item(t('sec_tls_cert_label'), $result['tlsStatus'], $result['tlsDetail']) ?>
          </div>

          <?= tools_render_cta() ?>

          <a href="/pages/tool-security.php" class="tool-check-another"><?= e(t('tools_check_another')) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
