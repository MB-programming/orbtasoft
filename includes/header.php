<?php
/** Expects (optional): $current_page = 'home'|'services'|'portfolio'|'about'|'contact' */
$current_page = $current_page ?? 'home';
$lang = current_lang();
$is_rtl = $lang === 'ar';

$nav_links = [
    'home'      => ['label' => t('nav_home'),      'href' => '/index.php'],
    'services'  => ['label' => t('nav_services'),  'href' => '/pages/services.php'],
    'portfolio' => ['label' => t('nav_portfolio'), 'href' => '/pages/portfolio.php'],
    'about'     => ['label' => t('nav_about'),     'href' => '/pages/about.php'],
    'contact'   => ['label' => t('nav_contact'),   'href' => '/pages/contact.php'],
];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= dir_attr() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(t('meta_title')) ?></title>
<meta name="description" content="<?= e(t('meta_desc')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>&#9889;</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= $is_rtl ? 'is-rtl' : 'is-ltr' ?>">

<div class="site-grain" aria-hidden="true"></div>

<header class="site-header" id="siteHeader">
  <div class="container site-header__inner">
    <a href="/index.php" class="brand">
      <span class="brand__mark">⚡</span>
      <span class="brand__name"><?= e(t('hero_brand')) ?></span>
    </a>

    <nav class="main-nav" id="mainNav">
      <ul>
        <?php foreach ($nav_links as $key => $link): ?>
          <li>
            <a href="<?= e($link['href']) ?>" class="<?= $current_page === $key ? 'is-active' : '' ?>">
              <?= e($link['label']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-actions">
      <a href="?lang=<?= e(other_lang()) ?>" class="lang-switch" aria-label="Switch language">
        <?= $is_rtl ? 'EN' : 'AR' ?>
      </a>
      <a href="/pages/contact.php" class="btn btn--primary btn--sm header-cta"><?= e(t('nav_cta')) ?></a>
      <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
