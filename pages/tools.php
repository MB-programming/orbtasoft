<?php
require __DIR__ . '/../includes/functions.php';
$current_page = 'tools';
require __DIR__ . '/../includes/header.php';

$tools = [
    ['slug' => 'performance',  'icon' => 'gauge',            'title' => t('tools_card_performance_title'), 'desc' => t('tools_card_performance_desc')],
    ['slug' => 'analysis',     'icon' => 'bar-chart-3',      'title' => t('tools_card_analysis_title'),    'desc' => t('tools_card_analysis_desc')],
    ['slug' => 'security',     'icon' => 'shield-alert',     'title' => t('tools_card_security_title'),    'desc' => t('tools_card_security_desc')],
    ['slug' => 'seo',          'icon' => 'search',           'title' => t('tools_card_seo_title'),         'desc' => t('tools_card_seo_desc')],
    ['slug' => 'keywords',     'icon' => 'send',             'title' => t('tools_card_keywords_title'),    'desc' => t('tools_card_keywords_desc')],
    ['slug' => 'uiux',         'icon' => 'layout-panel-top', 'title' => t('tools_card_uiux_title'),        'desc' => t('tools_card_uiux_desc')],
    ['slug' => 'ai-checker',   'icon' => 'star',             'title' => t('tools_card_ai_title'),          'desc' => t('tools_card_ai_desc')],
];
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('tools_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('tools_heading')) ?></h1>
      <p class="reveal"><?= e(t('tools_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="tools-grid">
        <?php foreach ($tools as $tool): ?>
          <a class="tool-card reveal" href="/pages/tool-<?= e($tool['slug']) ?>.php">
            <div class="tool-card__icon"><?= icon($tool['icon']) ?></div>
            <h3><?= e($tool['title']) ?></h3>
            <p><?= e($tool['desc']) ?></p>
            <span class="tool-card__link"><?= e(t('tools_open_tool')) ?> →</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="cta-section">
    <div class="container">
      <div class="cta-card reveal">
        <h2><?= e(t('cta_heading')) ?></h2>
        <p><?= e(t('cta_desc')) ?></p>
        <div class="hero__actions">
          <a href="/pages/contact.php" class="btn btn--primary"><?= e(t('cta_btn_primary')) ?></a>
          <a href="/pages/services.php" class="btn btn--secondary"><?= e(t('cta_btn_secondary')) ?></a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
