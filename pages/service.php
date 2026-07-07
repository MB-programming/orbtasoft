<?php
require __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$service = $slug ? service_by_slug($slug) : null;

if (!$service) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$neighbors = service_neighbors((int) $service['id']);
$prevService = $neighbors['prev'] ? service_by_slug($neighbors['prev']) : null;
$nextService = $neighbors['next'] ? service_by_slug($neighbors['next']) : null;

$current_page = 'services';
require __DIR__ . '/../includes/header.php';
?>

<main>
  <article class="project-detail">
    <section class="page-hero page-hero--project">
      <div class="bg-grid" aria-hidden="true"></div>
      <div class="container">
        <a href="/pages/services.php" class="back-link reveal"><?= icon('arrow-left') ?> <?= e(t('nav_services')) ?></a>
        <div class="service-detail__icon reveal"><?= icon($service['icon']) ?></div>
        <h1 class="reveal"><?= e($service['title']) ?></h1>
        <p class="reveal"><?= e($service['description']) ?></p>
      </div>
    </section>

    <section class="section section--tight">
      <div class="container container--narrow">
        <?php if (!empty($service['image'])): ?>
          <div class="project-detail__cover reveal">
            <img src="<?= e($service['image']) ?>" alt="<?= e($service['title']) ?>">
          </div>
        <?php endif; ?>
        <div class="project-detail__content reveal">
          <?php foreach (explode("\n\n", $service['content']) as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ($prevService || $nextService): ?>
      <section class="section section--tight post-nav-section">
        <div class="container container--narrow">
          <div class="post-nav">
            <?php if ($prevService): ?>
              <a href="/pages/service.php?slug=<?= e(urlencode($prevService['slug'])) ?>" class="post-nav__item post-nav__item--prev">
                <span class="post-nav__label"><?= icon('arrow-left') ?> <?= e(t('service_prev')) ?></span>
                <span class="post-nav__title"><?= e($prevService['title']) ?></span>
              </a>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($nextService): ?>
              <a href="/pages/service.php?slug=<?= e(urlencode($nextService['slug'])) ?>" class="post-nav__item post-nav__item--next">
                <span class="post-nav__label"><?= e(t('service_next')) ?> <?= icon('arrow-right') ?></span>
                <span class="post-nav__title"><?= e($nextService['title']) ?></span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </article>

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
