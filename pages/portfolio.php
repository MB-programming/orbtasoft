<?php
require __DIR__ . '/../includes/functions.php';
$current_page = 'portfolio';
require __DIR__ . '/../includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('work_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('work_heading')) ?></h1>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="portfolio-grid">
        <?php foreach (portfolio_data() as $project): ?>
          <div class="portfolio-card reveal">
            <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" loading="lazy">
            <div class="portfolio-card__overlay">
              <span class="portfolio-card__tag"><?= e($project['tag']) ?></span>
              <h3 class="portfolio-card__title"><?= e($project['title']) ?></h3>
              <span class="portfolio-card__link"><?= e(t('work_view_project')) ?> →</span>
            </div>
          </div>
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
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
