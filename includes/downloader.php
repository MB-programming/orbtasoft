<?php
/**
 * Video downloader backend, built on yt-dlp (MIT-licensed, actively maintained:
 * https://github.com/yt-dlp/yt-dlp) which already knows how to pull real download
 * URLs out of YouTube/TikTok/Instagram/Facebook — reimplementing that per-site
 * extraction logic here would be fragile and go stale within weeks.
 *
 * Requires the `yt-dlp` binary (and `ffmpeg` for quality merging / MP3 extraction)
 * installed on the server and reachable on PATH. Every call goes through proc_open
 * with an array command — no shell string is ever built, so there is no injection
 * surface — and every URL is checked against the calling platform's host allowlist
 * before it's handed to yt-dlp.
 */

define('DOWNLOADER_MAX_DURATION', 3600);   // seconds (60 min) — abuse/cost guard
define('DOWNLOADER_MAX_FILESIZE', '700M'); // enforced by yt-dlp itself during download
define('DOWNLOADER_INFO_TIMEOUT', 25);     // seconds
define('DOWNLOADER_PROCESS_TIMEOUT', 300); // seconds

/** Platforms this tool supports, and the hostnames a pasted URL is allowed to belong to. */
function downloader_platforms(): array
{
    return [
        'youtube' => ['hosts' => ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be'], 'label' => 'YouTube'],
        'tiktok' => ['hosts' => ['tiktok.com', 'm.tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com'], 'label' => 'TikTok'],
        'instagram' => ['hosts' => ['instagram.com'], 'label' => 'Instagram'],
        'facebook' => ['hosts' => ['facebook.com', 'm.facebook.com', 'fb.watch'], 'label' => 'Facebook'],
    ];
}

/** Validates that $raw is an http(s) URL whose host actually belongs to $platform. */
function downloader_validate_url(string $raw, string $platform): array
{
    $platforms = downloader_platforms();
    if (!isset($platforms[$platform])) {
        return ['ok' => false, 'error' => 'platform'];
    }

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
    if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
        return ['ok' => false, 'error' => 'scheme'];
    }

    $host = strtolower($parts['host']);
    $matches = false;
    foreach ($platforms[$platform]['hosts'] as $allowedHost) {
        if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
            $matches = true;
            break;
        }
    }
    if (!$matches) {
        return ['ok' => false, 'error' => 'wrong_platform'];
    }

    return ['ok' => true, 'url' => $raw];
}

/** Resolves a binary name to itself once `--version` proves it's runnable, caching per request. */
function downloader_binary_path(string $name): ?string
{
    static $cache = [];
    if (array_key_exists($name, $cache)) {
        return $cache[$name];
    }
    if (!function_exists('proc_open')) {
        return $cache[$name] = null;
    }
    $result = downloader_run_process([$name, '--version'], 8);
    return $cache[$name] = $result['ok'] ? $name : null;
}

/** Runs a command (array form — never a shell string) with a wall-clock timeout. */
function downloader_run_process(array $cmd, int $timeoutSeconds): array
{
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = @proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($process)) {
        return ['ok' => false, 'exit_code' => -1, 'stdout' => '', 'stderr' => '', 'timed_out' => false];
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $start = microtime(true);
    $timedOut = false;

    while (true) {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if ((microtime(true) - $start) > $timeoutSeconds) {
            $timedOut = true;
            proc_terminate($process, 9);
            break;
        }
        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'ok' => !$timedOut && $exitCode === 0,
        'exit_code' => $timedOut ? -1 : $exitCode,
        'stdout' => $stdout,
        'stderr' => $stderr,
        'timed_out' => $timedOut,
    ];
}

/** Fetches metadata (title, thumbnail, duration, max resolution) without downloading anything. */
function downloader_fetch_info(string $url): array
{
    $bin = downloader_binary_path('yt-dlp');
    if (!$bin) {
        return ['ok' => false, 'error' => 'binary_missing'];
    }

    $result = downloader_run_process(
        [$bin, '--no-warnings', '--no-playlist', '--skip-download', '-j', $url],
        DOWNLOADER_INFO_TIMEOUT
    );
    if (!$result['ok']) {
        return ['ok' => false, 'error' => $result['timed_out'] ? 'timeout' : 'fetch_failed'];
    }

    $lines = array_values(array_filter(explode("\n", trim($result['stdout']))));
    $data = $lines ? json_decode((string) end($lines), true) : null;
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'parse_failed'];
    }
    if (!empty($data['is_live'])) {
        return ['ok' => false, 'error' => 'live_unsupported'];
    }

    $duration = (int) ($data['duration'] ?? 0);
    if ($duration > DOWNLOADER_MAX_DURATION) {
        return ['ok' => false, 'error' => 'too_long'];
    }

    // Distinct real video heights this source actually offers (not a guessed ladder) — sites
    // like TikTok/Instagram/Facebook often expose only one or two heights, and asking yt-dlp
    // for a height nothing matches ("bestvideo[height<=360]") fails outright rather than
    // falling back, so every quality button must map to a height that truly exists.
    $heights = [];
    if (!empty($data['height'])) {
        $heights[(int) $data['height']] = true;
    }
    foreach ((array) ($data['formats'] ?? []) as $format) {
        $h = (int) ($format['height'] ?? 0);
        $vcodec = (string) ($format['vcodec'] ?? '');
        if ($h > 0 && $vcodec !== 'none') {
            $heights[$h] = true;
        }
    }
    $heights = array_keys($heights);
    rsort($heights);

    return [
        'ok' => true,
        'title' => (string) ($data['title'] ?? ''),
        'thumbnail' => (string) ($data['thumbnail'] ?? ''),
        'uploader' => (string) ($data['uploader'] ?? ($data['channel'] ?? '')),
        'duration' => $duration,
        'heights' => $heights,
    ];
}

/** Builds the quality tiers to offer: "Best available" plus one button per distinct real height, plus MP3. */
function downloader_available_qualities(array $heights): array
{
    $tiers = [['key' => 'best', 'label' => t('downloader_quality_best')]];
    if (count($heights) > 1) {
        foreach ($heights as $height) {
            $tiers[] = ['key' => (string) $height, 'label' => $height . 'p'];
        }
    }
    $tiers[] = ['key' => 'mp3', 'label' => t('downloader_quality_mp3')];
    return $tiers;
}

function downloader_quality_allowed(string $quality): bool
{
    if ($quality === 'best' || $quality === 'mp3') {
        return true;
    }
    return ctype_digit($quality) && (int) $quality > 0 && (int) $quality <= 8000;
}

/** Maps a quality tier to the yt-dlp CLI args that produce it. */
function downloader_format_args(string $quality): array
{
    if ($quality === 'mp3') {
        return ['-x', '--audio-format', 'mp3', '--audio-quality', '0'];
    }
    if ($quality === 'best' || !ctype_digit($quality)) {
        return ['-f', 'bestvideo*+bestaudio/best', '--merge-output-format', 'mp4'];
    }
    $height = (int) $quality;
    return ['-f', "bestvideo*[height<={$height}]+bestaudio/best[height<={$height}]", '--merge-output-format', 'mp4'];
}

/** Downloads the video into a fresh temp directory. Caller must downloader_rrmdir() the returned dir. */
function downloader_download(string $url, string $quality): array
{
    $bin = downloader_binary_path('yt-dlp');
    if (!$bin) {
        return ['ok' => false, 'error' => 'binary_missing'];
    }

    $dir = rtrim(sys_get_temp_dir(), '/') . '/orbtasoft-dl-' . bin2hex(random_bytes(8));
    if (!mkdir($dir, 0700, true)) {
        return ['ok' => false, 'error' => 'temp_dir'];
    }

    $cmd = array_merge(
        [$bin, '--no-warnings', '--no-playlist', '--max-filesize', DOWNLOADER_MAX_FILESIZE, '-o', $dir . '/%(title).100s.%(ext)s'],
        downloader_format_args($quality),
        [$url]
    );

    $result = downloader_run_process($cmd, DOWNLOADER_PROCESS_TIMEOUT);
    $files = array_values(array_filter(glob($dir . '/*') ?: [], 'is_file'));
    $file = $files[0] ?? null;

    if (!$result['ok'] || !$file) {
        downloader_rrmdir($dir);
        return ['ok' => false, 'error' => $result['timed_out'] ? 'timeout' : 'download_failed'];
    }

    return ['ok' => true, 'path' => $file, 'dir' => $dir];
}

function downloader_rrmdir(string $dir): void
{
    if ($dir === '' || !is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/*') ?: [] as $file) {
        @unlink($file);
    }
    @rmdir($dir);
}

function downloader_format_duration(int $seconds): string
{
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}
