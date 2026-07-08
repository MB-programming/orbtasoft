<?php
require __DIR__ . '/includes/functions.php';
$current_page = 'services';
require __DIR__ . '/includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('services_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('services_heading')) ?></h1>
      <p class="reveal"><?= e(t('services_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="services-grid">
        <?php foreach (services_data() as $service): ?>
          <a class="service-card reveal" href="/service.php?slug=<?= e(urlencode($service['slug'])) ?>">
            <div class="service-card__icon"><?= icon($service['icon']) ?></div>
            <h3><?= e($service['title']) ?></h3>
            <p><?= e($service['desc']) ?></p>
            <span class="service-card__link"><?= e(t('service_learn_more')) ?> →</span>
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
          <a href="/contact.php" class="btn btn--primary"><?= e(t('cta_btn_primary')) ?></a>
          <a href="/portfolio.php" class="btn btn--secondary"><?= e(t('cta_btn_secondary')) ?></a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
