<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Advanced SEO controls: a global settings layer (site-wide defaults, analytics,
 * verification codes, organization schema) plus a generic per-entity override
 * layer (seo_meta table) used for static pages, portfolio projects, services and
 * blog posts alike. includes/header.php calls seo_resolve() to build the <head>
 * for every page; nothing else needs to know the override system exists.
 */

function seo_meta_fetch(string $entityType, string $entityKey): ?array
{
    $sql = 'SELECT * FROM seo_meta WHERE entity_type = ' . quote_for_lookup($entityType)
        . ' AND entity_key = ' . quote_for_lookup($entityKey) . ' LIMIT 1';
    $rows = db_fetch_all($sql);
    return $rows[0] ?? null;
}

function seo_meta_save(string $entityType, string $entityKey, array $data): void
{
    $pdo = get_db();
    if (!$pdo) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO seo_meta
            (entity_type, entity_key, seo_title_de, seo_title_en, seo_title_ar,
             seo_description_de, seo_description_en, seo_description_ar, og_image, canonical_url, noindex)
         VALUES
            (:type, :key, :title_de, :title_en, :title_ar,
             :desc_de, :desc_en, :desc_ar, :og_image, :canonical, :noindex)
         ON DUPLICATE KEY UPDATE
            seo_title_de = :title_de2, seo_title_en = :title_en2, seo_title_ar = :title_ar2,
            seo_description_de = :desc_de2, seo_description_en = :desc_en2, seo_description_ar = :desc_ar2,
            og_image = :og_image2, canonical_url = :canonical2, noindex = :noindex2'
    );
    $titleDe = trim($data['seo_title_de'] ?? '');
    $titleEn = trim($data['seo_title_en'] ?? '');
    $titleAr = trim($data['seo_title_ar'] ?? '');
    $descDe = trim($data['seo_description_de'] ?? '');
    $descEn = trim($data['seo_description_en'] ?? '');
    $descAr = trim($data['seo_description_ar'] ?? '');
    $ogImage = trim($data['og_image'] ?? '');
    $canonical = trim($data['canonical_url'] ?? '');
    $noindex = !empty($data['noindex']) ? 1 : 0;

    $stmt->execute([
        'type' => $entityType, 'key' => $entityKey,
        'title_de' => $titleDe, 'title_en' => $titleEn, 'title_ar' => $titleAr,
        'desc_de' => $descDe, 'desc_en' => $descEn, 'desc_ar' => $descAr,
        'og_image' => $ogImage, 'canonical' => $canonical, 'noindex' => $noindex,
        'title_de2' => $titleDe, 'title_en2' => $titleEn, 'title_ar2' => $titleAr,
        'desc_de2' => $descDe, 'desc_en2' => $descEn, 'desc_ar2' => $descAr,
        'og_image2' => $ogImage, 'canonical2' => $canonical, 'noindex2' => $noindex,
    ]);
}

/**
 * Resolves the final title/description/og image/canonical/noindex for a page,
 * layering: seo_meta override (current language) -> caller-supplied fallback -> global site defaults.
 */
function seo_resolve(string $entityType, string $entityKey, array $fallback = []): array
{
    $lang = current_lang();
    $override = seo_meta_fetch($entityType, $entityKey);

    $title = trim($override['seo_title_' . $lang] ?? '') ?: ($fallback['title'] ?? t('meta_title'));
    $description = trim($override['seo_description_' . $lang] ?? '') ?: ($fallback['description'] ?? t('meta_desc'));
    $ogImage = trim($override['og_image'] ?? '') ?: ($fallback['image'] ?? site_setting('seo_default_og_image', ''));
    $canonical = trim($override['canonical_url'] ?? '') ?: ($fallback['canonical'] ?? current_full_url());
    $noindex = !empty($override['noindex']);

    $titleSuffix = site_setting('seo_title_suffix', '');
    if ($titleSuffix !== '' && !str_contains($title, $titleSuffix)) {
        $title .= ' ' . $titleSuffix;
    }

    return [
        'title' => $title,
        'description' => $description,
        'image' => $ogImage,
        'canonical' => $canonical,
        'noindex' => $noindex,
    ];
}

function current_full_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return $scheme . '://' . $host . $uri;
}

/** Renders the full dynamic <head> SEO block: title, meta, canonical, OG/Twitter tags, analytics, org schema. */
function seo_render_head(array $seo): void
{
    echo '<title>' . e($seo['title']) . "</title>\n";
    echo '<meta name="description" content="' . e($seo['description']) . "\">\n";
    echo '<meta name="robots" content="' . ($seo['noindex'] ? 'noindex,nofollow' : 'index,follow') . "\">\n";
    if ($seo['canonical']) {
        echo '<link rel="canonical" href="' . e($seo['canonical']) . "\">\n";
    }

    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . e($seo['title']) . "\">\n";
    echo '<meta property="og:description" content="' . e($seo['description']) . "\">\n";
    echo '<meta property="og:url" content="' . e($seo['canonical']) . "\">\n";
    if ($seo['image']) {
        echo '<meta property="og:image" content="' . e($seo['image']) . "\">\n";
    }
    echo '<meta name="twitter:card" content="' . ($seo['image'] ? 'summary_large_image' : 'summary') . "\">\n";
    $twitterHandle = site_setting('seo_twitter_handle', '');
    if ($twitterHandle !== '') {
        echo '<meta name="twitter:site" content="' . e($twitterHandle) . "\">\n";
    }

    $googleVerification = site_setting('seo_google_verification', '');
    if ($googleVerification !== '') {
        echo '<meta name="google-site-verification" content="' . e($googleVerification) . "\">\n";
    }
    $bingVerification = site_setting('seo_bing_verification', '');
    if ($bingVerification !== '') {
        echo '<meta name="msvalidate.01" content="' . e($bingVerification) . "\">\n";
    }

    $gaId = site_setting('seo_ga_id', '');
    if ($gaId !== '') {
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($gaId) . "\"></script>\n";
        echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","' . e($gaId) . "\");</script>\n";
    }

    seo_render_organization_schema();
}

function seo_render_organization_schema(): void
{
    $orgName = site_setting('seo_org_name', '') ?: t('hero_brand');
    $orgLogo = site_setting('seo_org_logo', '');
    $schemaType = site_setting('seo_schema_type', 'Organization') ?: 'Organization';
    $siteUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $schemaType,
        'name' => $orgName,
        'url' => $siteUrl,
    ];
    if ($orgLogo !== '') {
        $schema['logo'] = $orgLogo;
    }

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>\n";
}

/** Static-page keys available for per-page SEO overrides in the admin dashboard. */
function seo_static_pages(): array
{
    return [
        'home' => 'Homepage',
        'services' => 'Services (listing)',
        'portfolio' => 'Portfolio (listing)',
        'blog' => 'Blog (listing)',
        'tools' => 'Tools (listing)',
        'about' => 'About',
        'contact' => 'Contact',
    ];
}
