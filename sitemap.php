<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
header('Content-Type: application/xml; charset=UTF-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

function sitemap_is_noindex(string $type, string $key): bool
{
    $row = seo_meta_fetch($type, $key);
    return !empty($row['noindex']);
}

$urls = [];

$staticPages = [
    'home' => '/index.php',
    'services' => '/services.php',
    'portfolio' => '/portfolio.php',
    'blog' => '/blog.php',
    'tools' => '/tools.php',
    'about' => '/about.php',
    'contact' => '/contact.php',
];
foreach ($staticPages as $key => $path) {
    if (!sitemap_is_noindex('page', $key)) {
        $urls[] = ['loc' => $base . $path, 'priority' => $key === 'home' ? '1.0' : '0.8'];
    }
}

foreach (portfolio_data() as $project) {
    if (!sitemap_is_noindex('project', $project['slug'])) {
        $urls[] = ['loc' => $base . '/project.php?slug=' . urlencode($project['slug']), 'priority' => '0.7'];
    }
}

foreach (services_data() as $service) {
    if (!sitemap_is_noindex('service', $service['slug'])) {
        $urls[] = ['loc' => $base . '/service.php?slug=' . urlencode($service['slug']), 'priority' => '0.7'];
    }
}

foreach (blog_posts_data() as $post) {
    if (!sitemap_is_noindex('blog_post', $post['slug'])) {
        $urls[] = ['loc' => $base . '/blog-post.php?slug=' . urlencode($post['slug']), 'priority' => '0.6'];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo '    <priority>' . $url['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
