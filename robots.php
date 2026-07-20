<?php
require __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=UTF-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /account/\n";
echo "Disallow: /download-video.php\n";

$extra = trim(site_setting('seo_robots_extra', ''));
if ($extra !== '') {
    echo $extra . "\n";
}

echo "\nSitemap: {$scheme}://{$host}/sitemap.xml\n";
