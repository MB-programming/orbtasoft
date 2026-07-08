<?php
/**
 * Expects (optional): $current_page = 'home'|'services'|'portfolio'|'about'|'contact'
 * Also expects (optional, for per-entity SEO overrides): $seo_entity_type, $seo_entity_key,
 * $seo_fallback_title, $seo_fallback_description, $seo_fallback_image, $seo_fallback_canonical
 */
require_once __DIR__ . '/seo.php';
$current_page = $current_page ?? 'home';
$lang = current_lang();
$is_rtl = $lang === 'ar';

$seo = seo_resolve($seo_entity_type ?? 'page', $seo_entity_key ?? $current_page, [
    'title' => $seo_fallback_title ?? t('meta_title'),
    'description' => $seo_fallback_description ?? t('meta_desc'),
    'image' => $seo_fallback_image ?? '',
    'canonical' => $seo_fallback_canonical ?? null,
]);

$nav_links = [
    'home'      => ['label' => t('nav_home'),      'href' => '/index.php'],
    'services'  => ['label' => t('nav_services'),  'href' => '/services.php'],
    'portfolio' => ['label' => t('nav_portfolio'), 'href' => '/portfolio.php'],
    'blog'      => ['label' => t('nav_blog'),      'href' => '/blog.php'],
    'tools'     => ['label' => t('nav_tools'),     'href' => '/tools.php'],
    'about'     => ['label' => t('nav_about'),     'href' => '/about.php'],
    'contact'   => ['label' => t('nav_contact'),   'href' => '/contact.php'],
];

function lang_url(string $code): string
{
    $params = $_GET;
    $params['lang'] = $code;
    return '?' . http_build_query($params);
}

$authUser = current_user();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attr() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php seo_render_head($seo); ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22><rect width=%2224%22 height=%2224%22 rx=%225%22 fill=%22%2305070d%22/><ellipse cx=%2212%22 cy=%2212%22 rx=%229%22 ry=%224%22 fill=%22none%22 stroke=%22%233b82f6%22 stroke-width=%221.6%22 transform=%22rotate(-30 12 12)%22/><circle cx=%2212%22 cy=%2212%22 r=%222.6%22 fill=%22%2360a5fa%22/><circle cx=%2220%22 cy=%227.2%22 r=%221.6%22 fill=%22%2360a5fa%22/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= $is_rtl ? 'is-rtl' : 'is-ltr' ?>">

<div class="site-grain" aria-hidden="true"></div>

<?php if (isset($_GET['welcome']) && $authUser): ?>
  <div class="welcome-toast" id="welcomeToast"><?= e(t('auth_success_login')) ?></div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
  <div class="container site-header__inner">
    <a href="/index.php" class="brand">
      <span class="brand__mark"><?= icon('brand-mark') ?></span>
      <span class="brand__name"><?= e(t('hero_brand')) ?></span>
    </a>

    <div class="header-actions">
      <div class="lang-switcher" id="langSwitcher">
        <button class="lang-switcher__trigger" id="langSwitcherTrigger" aria-haspopup="true" aria-expanded="false">
          <?= icon('globe') ?>
          <span><?= e(strtoupper($lang)) ?></span>
          <?= icon('chevron-down') ?>
        </button>
        <div class="lang-switcher__menu" id="langSwitcherMenu">
          <?php foreach (available_langs() as $code => $label): ?>
            <a href="<?= e(lang_url($code)) ?>" class="<?= $lang === $code ? 'is-active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="/contact.php" class="btn btn--primary btn--sm header-cta"><?= e(t('nav_cta')) ?></a>
      <button class="menu-trigger" id="menuTrigger" aria-haspopup="true" aria-expanded="false" aria-controls="popoutMenu">
        <span class="menu-trigger__bars"><span></span><span></span></span>
        <span class="menu-trigger__label"><?= e(t('nav_menu')) ?></span>
      </button>
    </div>
  </div>
</header>

<!-- ============ POPOUT MENU ============ -->
<div class="popout-menu" id="popoutMenu" aria-hidden="true">
  <div class="popout-menu__backdrop" id="popoutBackdrop"></div>
  <div class="popout-menu__panel">
    <div class="popout-menu__top">
      <a href="/index.php" class="brand">
        <span class="brand__mark"><?= icon('brand-mark') ?></span>
        <span class="brand__name"><?= e(t('hero_brand')) ?></span>
      </a>
      <button class="menu-close" id="menuClose" aria-label="<?= e(t('nav_close')) ?>">
        <span></span><span></span>
      </button>
    </div>

    <nav class="popout-menu__nav">
      <ul>
        <?php $i = 1; foreach ($nav_links as $key => $link): ?>
          <li class="popout-item">
            <a href="<?= e($link['href']) ?>" class="<?= $current_page === $key ? 'is-active' : '' ?>">
              <span class="popout-item__index">0<?= $i ?></span>
              <span class="popout-item__label"><?= e($link['label']) ?></span>
            </a>
          </li>
        <?php $i++; endforeach; ?>
      </ul>
    </nav>

    <div class="popout-menu__bottom">
      <?php if ($authUser): ?>
        <div class="popout-account">
          <span><?= e(t('auth_logged_in_as')) ?> <strong><?= e($authUser['name']) ?></strong></span>
          <a href="/account/index.php"><?= e(t('nav_account')) ?></a>
          <a href="/auth-logout.php"><?= e(t('nav_logout')) ?></a>
        </div>
      <?php else: ?>
        <a href="/login.php" class="popout-account-link"><?= e(t('nav_login')) ?></a>
      <?php endif; ?>
      <a href="/contact.php" class="btn btn--primary popout-cta"><?= e(t('nav_get_in_touch')) ?></a>
    </div>
  </div>
</div>
