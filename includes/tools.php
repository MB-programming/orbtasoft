<?php
/**
 * Shared SSRF-safe URL fetcher for the public "Tools" section (performance,
 * analysis, security, SEO). Every fetch resolves the hostname first and
 * refuses private/loopback/reserved IP ranges, then pins the TCP connection
 * to that exact validated IP via CURLOPT_RESOLVE so a DNS answer can't
 * change between the check and the connect (DNS rebinding). Redirects are
 * followed manually, one hop at a time, with the same validation re-run on
 * every hop.
 */

function tools_normalize_and_validate_url(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['ok' => false, 'error' => 'empty'];
    }
    if (!preg_match('~^https?://~i', $raw)) {
        $raw = 'https://' . $raw;
    }

    $parts = parse_url($raw);
    if ($parts === false || empty($parts['host'])) {
        return ['ok' => false, 'error' => 'invalid'];
    }

    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['ok' => false, 'error' => 'scheme'];
    }

    $host = strtolower($parts['host']);
    $defaultPort = $scheme === 'https' ? 443 : 80;
    $port = (int) ($parts['port'] ?? $defaultPort);
    if (!in_array($port, [80, 443], true)) {
        return ['ok' => false, 'error' => 'port'];
    }

    if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
        return ['ok' => false, 'error' => 'blocked_host'];
    }

    $ip = null;
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ip = $host;
    } else {
        $resolved = @gethostbyname($host);
        if ($resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP)) {
            $ip = $resolved;
        }
    }

    if (!$ip) {
        return ['ok' => false, 'error' => 'resolve_failed'];
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return ['ok' => false, 'error' => 'private_ip'];
    }

    $path = $parts['path'] ?? '/';
    if ($path === '') {
        $path = '/';
    }
    if (!empty($parts['query'])) {
        $path .= '?' . $parts['query'];
    }

    $portSuffix = $port !== $defaultPort ? ':' . $port : '';
    $normalized = $scheme . '://' . $host . $portSuffix . $path;

    return [
        'ok' => true, 'scheme' => $scheme, 'host' => $host, 'port' => $port,
        'ip' => $ip, 'path' => $path, 'url' => $normalized,
    ];
}

function tools_resolve_relative_url(string $base, string $location): string
{
    if (preg_match('~^https?://~i', $location)) {
        return $location;
    }
    $parts = parse_url($base);
    $scheme = $parts['scheme'] ?? 'https';
    $host = $parts['host'] ?? '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    if (str_starts_with($location, '//')) {
        return $scheme . ':' . $location;
    }
    if (str_starts_with($location, '/')) {
        return $scheme . '://' . $host . $port . $location;
    }
    $basePath = $parts['path'] ?? '/';
    $dir = rtrim(substr($basePath, 0, (int) strrpos($basePath, '/')), '/');
    return $scheme . '://' . $host . $port . $dir . '/' . $location;
}

function tools_parse_header_lines(array $lines): array
{
    $headers = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || stripos($line, 'HTTP/') === 0) {
            continue;
        }
        $pos = strpos($line, ':');
        if ($pos === false) {
            continue;
        }
        $key = strtolower(trim(substr($line, 0, $pos)));
        $val = trim(substr($line, $pos + 1));
        if (isset($headers[$key])) {
            $headers[$key] = is_array($headers[$key]) ? array_merge($headers[$key], [$val]) : [$headers[$key], $val];
        } else {
            $headers[$key] = $val;
        }
    }
    return $headers;
}

/**
 * Fetches a URL safely (SSRF-checked, redirect-pinned, size/time capped).
 * Returns ['ok'=>bool, 'error'=>?string, 'final_url','status','headers','body','time_total','time_ttfb','size_download','primary_ip','hops']
 */
function tools_fetch(string $rawUrl, int $maxRedirects = 4, int $maxBytes = 4194304, int $timeout = 12, int $connectTimeout = 6): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'curl_unavailable', 'hops' => []];
    }

    $current = $rawUrl;
    $hops = [];

    for ($i = 0; $i <= $maxRedirects; $i++) {
        $check = tools_normalize_and_validate_url($current);
        if (!$check['ok']) {
            return ['ok' => false, 'error' => $check['error'], 'hops' => $hops];
        }

        $headerLines = [];
        $bodyChunks = [];
        $downloaded = 0;
        $truncated = false;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $check['url'],
            CURLOPT_RESOLVE => [$check['host'] . ':' . $check['port'] . ':' . $check['ip']],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
            CURLOPT_NOSIGNAL => true,
            CURLOPT_USERAGENT => 'OrbtasoftTools/1.0 (+website audit tool)',
            CURLOPT_HEADERFUNCTION => function ($curlHandle, $line) use (&$headerLines) {
                $headerLines[] = $line;
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($curlHandle, $chunk) use (&$bodyChunks, &$downloaded, &$truncated, $maxBytes) {
                $downloaded += strlen($chunk);
                if ($downloaded > $maxBytes) {
                    $truncated = true;
                    return 0;
                }
                $bodyChunks[] = $chunk;
                return strlen($chunk);
            },
        ]);

        $success = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($success === false && !$truncated) {
            return ['ok' => false, 'error' => 'fetch_failed', 'detail' => $curlErr, 'hops' => $hops];
        }

        $headers = tools_parse_header_lines($headerLines);
        $body = implode('', $bodyChunks);
        $status = (int) ($info['http_code'] ?? 0);
        $hops[] = ['url' => $check['url'], 'status' => $status];

        if (in_array($status, [301, 302, 303, 307, 308], true) && !empty($headers['location'])) {
            $location = is_array($headers['location']) ? end($headers['location']) : $headers['location'];
            $current = tools_resolve_relative_url($check['url'], $location);
            continue;
        }

        return [
            'ok' => true,
            'final_url' => $check['url'],
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
            'truncated' => $truncated,
            'time_total' => (float) ($info['total_time'] ?? 0),
            'time_ttfb' => (float) ($info['starttransfer_time'] ?? 0),
            'time_connect' => (float) ($info['connect_time'] ?? 0),
            'size_download' => (int) ($info['size_download'] ?? strlen($body)),
            'content_type' => (string) ($info['content_type'] ?? ''),
            'primary_ip' => (string) ($info['primary_ip'] ?? $check['ip']),
            'hops' => $hops,
        ];
    }

    return ['ok' => false, 'error' => 'too_many_redirects', 'hops' => $hops];
}

function tools_header(array $headers, string $name): ?string
{
    $val = $headers[strtolower($name)] ?? null;
    if (is_array($val)) {
        return end($val);
    }
    return $val;
}

function tools_grade(int $score): string
{
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 65) return 'C';
    if ($score >= 50) return 'D';
    return 'F';
}

/** Fetches /robots.txt and /sitemap.xml (best-effort, same-origin, SSRF-checked). */
function tools_fetch_wellknown(string $baseUrl, string $path): ?array
{
    $parts = parse_url($baseUrl);
    if (!$parts || empty($parts['host'])) {
        return null;
    }
    $scheme = $parts['scheme'] ?? 'https';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $url = $scheme . '://' . $parts['host'] . $port . $path;
    $result = tools_fetch($url, 2, 262144);
    return $result['ok'] ? $result : null;
}

function tools_status_label(string $status): string
{
    return match ($status) {
        'good' => t('tools_status_good'),
        'warn' => t('tools_status_warn'),
        'bad' => t('tools_status_bad'),
        default => t('tools_status_na'),
    };
}

function tools_render_check_item(string $label, string $status, string $detail = ''): string
{
    $cls = in_array($status, ['good', 'warn', 'bad', 'na'], true) ? $status : 'na';
    $html = '<div class="tool-check-item tool-check-item--' . $cls . '">';
    $html .= '<span class="tool-check-item__dot"></span>';
    $html .= '<div><div class="tool-check-item__label">' . e($label) . '</div>';
    if ($detail !== '') {
        $html .= '<div class="tool-check-item__detail">' . e($detail) . '</div>';
    }
    $html .= '</div>';
    $html .= '<span class="tool-check-item__status">' . e(tools_status_label($cls)) . '</span>';
    $html .= '</div>';
    return $html;
}

function tools_render_score(int $score): string
{
    $grade = tools_grade($score);
    return '<div class="tool-score"><div class="tool-score__ring tool-score__ring--' . e($grade) . '">' . e($grade) . '</div>'
        . '<div><div class="tool-score__label">' . e(t('tools_score_label')) . '</div><div class="tool-score__value">' . (int) $score . ' / 100</div></div></div>';
}

function tools_render_error(string $errorCode): string
{
    $map = [
        'empty' => 'tools_error_empty', 'invalid' => 'tools_error_invalid', 'scheme' => 'tools_error_scheme',
        'port' => 'tools_error_port', 'blocked_host' => 'tools_error_blocked_host', 'resolve_failed' => 'tools_error_resolve_failed',
        'private_ip' => 'tools_error_private_ip', 'curl_unavailable' => 'tools_error_curl_unavailable',
        'fetch_failed' => 'tools_error_fetch_failed', 'too_many_redirects' => 'tools_error_too_many_redirects',
    ];
    $key = $map[$errorCode] ?? 'tools_error_generic';
    return '<div class="tool-error">' . e(t($key)) . '</div>';
}

function tools_format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/** Averages a list of 'good'|'warn'|'bad'|'na' statuses into a 0-100 score ('na' entries are excluded). */
function tools_average_score(array $statuses): int
{
    $points = ['good' => 100, 'warn' => 50, 'bad' => 0];
    $applicable = array_filter($statuses, fn($s) => isset($points[$s]));
    if (!$applicable) {
        return 100;
    }
    $sum = array_sum(array_map(fn($s) => $points[$s], $applicable));
    return (int) round($sum / count($applicable));
}

function tools_stat_card(string $label, string $value): string
{
    return '<div class="tool-stat-card"><div class="tool-stat-card__label">' . e($label) . '</div><div class="tool-stat-card__value">' . e($value) . '</div></div>';
}

/** Parses an HTML string into a DOMDocument, forcing UTF-8 interpretation regardless of declared charset quirks. */
function tools_parse_html(string $html): DOMDocument
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    return $dom;
}

/** Maps a value onto a 0-100 score: 100 at/below $good, 0 at/above $bad, linear between. */
function tools_score_threshold(float $value, float $good, float $bad): int
{
    if ($value <= $good) {
        return 100;
    }
    if ($value >= $bad) {
        return 0;
    }
    return (int) round((($bad - $value) / ($bad - $good)) * 100);
}

/**
 * Collects unique stylesheet/script/image asset URLs referenced by a parsed page (absolute, deduped, capped).
 */
function tools_collect_assets(DOMDocument $dom, string $baseUrl, int $limitPerType = 15): array
{
    $assets = ['css' => [], 'js' => [], 'img' => []];

    foreach ($dom->getElementsByTagName('link') as $node) {
        $rel = strtolower($node->getAttribute('rel'));
        $href = $node->getAttribute('href');
        if ($rel === 'stylesheet' && $href !== '' && count($assets['css']) < $limitPerType) {
            $assets['css'][] = tools_resolve_relative_url($baseUrl, $href);
        }
    }
    foreach ($dom->getElementsByTagName('script') as $node) {
        $src = $node->getAttribute('src');
        if ($src !== '' && count($assets['js']) < $limitPerType) {
            $assets['js'][] = tools_resolve_relative_url($baseUrl, $src);
        }
    }
    foreach ($dom->getElementsByTagName('img') as $node) {
        $src = $node->getAttribute('src');
        if ($src !== '' && count($assets['img']) < $limitPerType) {
            $assets['img'][] = tools_resolve_relative_url($baseUrl, $src);
        }
    }

    foreach ($assets as $type => $urls) {
        $assets[$type] = array_values(array_unique($urls));
    }

    return $assets;
}

/**
 * Reads the TLS certificate for a host, connecting directly to the already-validated IP
 * (SNI/hostname verification still targets the real hostname via the 'peer_name' context option).
 */
function tools_tls_certificate_info(string $host, string $ip): ?array
{
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ],
    ]);

    $client = @stream_socket_client(
        'ssl://' . $ip . ':443',
        $errno,
        $errstr,
        5,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$client) {
        return null;
    }

    $params = stream_context_get_params($client);
    fclose($client);

    if (empty($params['options']['ssl']['peer_certificate'])) {
        return null;
    }

    $certInfo = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
    if (!$certInfo) {
        return null;
    }

    return [
        'valid_from' => $certInfo['validFrom_time_t'] ?? null,
        'valid_to' => $certInfo['validTo_time_t'] ?? null,
        'issuer' => $certInfo['issuer']['O'] ?? ($certInfo['issuer']['CN'] ?? null),
        'common_name' => $certInfo['subject']['CN'] ?? null,
    ];
}

/** Probes an asset's size (bytes), preferring the Content-Length header, capped/fast. */
function tools_probe_asset_size(string $url): ?int
{
    $result = tools_fetch($url, 1, 131072, 4, 3);
    if (!$result['ok']) {
        return null;
    }
    $contentLength = tools_header($result['headers'], 'content-length');
    if ($contentLength !== null && ctype_digit($contentLength)) {
        return (int) $contentLength;
    }
    return $result['size_download'];
}

/**
 * Generates real keyword variations from a single seed word, grouped by search intent.
 * Purely template-based (no external keyword-volume API involved) — honestly a generator, not a data source.
 */
function generate_keyword_ideas(string $seed, string $lang): array
{
    $kw = trim($seed);
    $year = date('Y');

    $templates = [
        'en' => [
            'questions' => ['what is {kw}', 'how does {kw} work', 'why use {kw}', 'when to use {kw}', 'how to choose {kw}', 'is {kw} worth it', 'what does {kw} cost'],
            'comparisons' => ['{kw} vs competitors', 'best {kw}', '{kw} alternative', '{kw} or diy', '{kw} with support'],
            'commercial' => ['{kw} pricing', '{kw} cost', '{kw} packages', '{kw} services', 'affordable {kw}', 'professional {kw}', '{kw} agency', '{kw} company', "{kw} {$year}"],
            'longtail' => ['how to get started with {kw}', '{kw} for small business', '{kw} for beginners', '{kw} for startups', 'benefits of {kw}', '{kw} best practices', 'common {kw} mistakes'],
            'local' => ['{kw} near me', 'local {kw} service', '{kw} consultant', '{kw} freelancer', 'hire {kw} expert'],
        ],
        'de' => [
            'questions' => ['was ist {kw}', 'wie funktioniert {kw}', 'warum {kw} nutzen', 'wann {kw} einsetzen', 'wie wählt man {kw} aus', 'lohnt sich {kw}', 'was kostet {kw}'],
            'comparisons' => ['{kw} im Vergleich', 'bestes {kw}', '{kw} Alternative', '{kw} oder selbst machen', '{kw} mit Support'],
            'commercial' => ['{kw} Preise', '{kw} Kosten', '{kw} Pakete', '{kw} Dienstleistungen', 'günstiges {kw}', 'professionelles {kw}', '{kw} Agentur', '{kw} Firma', "{kw} {$year}"],
            'longtail' => ['wie startet man mit {kw}', '{kw} für kleine Unternehmen', '{kw} für Anfänger', '{kw} für Startups', 'Vorteile von {kw}', '{kw} Best Practices', 'häufige {kw}-Fehler'],
            'local' => ['{kw} in meiner Nähe', 'lokaler {kw}-Service', '{kw} Berater', '{kw} Freelancer', '{kw}-Experte beauftragen'],
        ],
        'ar' => [
            'questions' => ['ما هو {kw}', 'كيف يعمل {kw}', 'ليه تستخدم {kw}', 'إمتى تستخدم {kw}', 'إزاي تختار {kw}', 'هل {kw} يستاهل', 'كام سعر {kw}'],
            'comparisons' => ['{kw} مقابل المنافسين', 'أفضل {kw}', 'بديل {kw}', '{kw} أو تعمله بنفسك', '{kw} مع دعم فني'],
            'commercial' => ['أسعار {kw}', 'تكلفة {kw}', 'باقات {kw}', 'خدمات {kw}', '{kw} بسعر مناسب', '{kw} احترافي', 'شركة {kw}', "{kw} {$year}"],
            'longtail' => ['إزاي تبدأ مع {kw}', '{kw} للمشاريع الصغيرة', '{kw} للمبتدئين', '{kw} للستارت أب', 'فوائد {kw}', 'أفضل ممارسات {kw}', 'أخطاء شائعة في {kw}'],
            'local' => ['{kw} بالقرب مني', 'خدمة {kw} محلية', 'مستشار {kw}', 'فريلانسر {kw}', 'استعن بخبير {kw}'],
        ],
    ];

    $set = $templates[$lang] ?? $templates['en'];
    $result = [];
    foreach ($set as $group => $patterns) {
        $result[$group] = array_map(fn($tpl) => str_replace('{kw}', $kw, $tpl), $patterns);
    }
    return $result;
}

/** Shared "hire us to fix this" CTA shown at the bottom of every tool result. */
function tools_render_cta(): string
{
    return '<div class="cta-section no-print" style="padding-block:0; margin-block-start:48px;">'
        . '<div class="tool-cta-card reveal">'
        . '<h2>' . e(t('tools_cta_heading')) . '</h2>'
        . '<p>' . e(t('tools_cta_desc')) . '</p>'
        . '<a href="/contact.php" class="btn btn--primary">' . e(t('tools_cta_btn')) . '</a>'
        . '</div></div>';
}

/** Branded header (logo + site name + report title + checked URL + date) shown on-screen and in the printed PDF. */
function tools_render_report_header(string $reportTitle, string $checkedUrl, bool $forceLtr = true): string
{
    $urlStyle = $forceLtr ? ' style="direction:ltr; text-align:start;"' : '';
    return '<div class="report-header">'
        . '<div class="report-header__brand">' . icon('brand-mark') . '<span>' . e(t('hero_brand')) . '</span></div>'
        . '<div class="report-header__meta">'
        . '<h1>' . e($reportTitle) . '</h1>'
        . '<p class="report-header__url"' . $urlStyle . '>' . e(t('report_checked_url')) . ' ' . e($checkedUrl) . '</p>'
        . '<p class="report-header__date">' . e(t('report_generated_on')) . ' ' . e(format_date(date('Y-m-d H:i:s'))) . '</p>'
        . '</div></div>';
}

/** "Export as PDF" button — uses the browser's native print-to-PDF over a print-only stylesheet, no server-side PDF library needed. */
function tools_render_pdf_button(): string
{
    return '<button type="button" class="btn btn--outline btn--sm tool-pdf-btn no-print" onclick="window.print()">' . icon('file-text') . ' ' . e(t('tools_export_pdf')) . '</button>';
}

/** Renders a "How to Improve" tip list from a set of translation keys (already filtered to failed/warn checks by the caller). */
function tools_render_tips(array $tipKeys): string
{
    $tipKeys = array_values(array_unique(array_filter($tipKeys)));
    $html = '<h3 class="tool-section-title">' . e(t('tools_tips_heading')) . '</h3>';
    if (!$tipKeys) {
        return $html . '<div class="tool-check-item tool-check-item--good"><span class="tool-check-item__dot"></span><div class="tool-check-item__label">' . e(t('tools_no_tips')) . '</div></div>';
    }
    $html .= '<div class="tool-tips-list">';
    foreach ($tipKeys as $key) {
        $html .= '<div class="tool-tip-item">' . icon('star') . '<span>' . e(t($key)) . '</span></div>';
    }
    $html .= '</div>';
    return $html;
}
