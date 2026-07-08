<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/service-visuals.php';

$slug = $_GET['slug'] ?? '';
$service = $slug ? service_by_slug($slug) : null;

if (!$service) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$visualData = service_visual_data()[$service['slug']] ?? null;

$neighbors = service_neighbors((int) $service['id']);
$prevService = $neighbors['prev'] ? service_by_slug($neighbors['prev']) : null;
$nextService = $neighbors['next'] ? service_by_slug($neighbors['next']) : null;

$current_page = 'services';
$seo_entity_type = 'service';
$seo_entity_key = $service['slug'];
$seo_fallback_title = $service['title'] . ' — ' . t('hero_brand');
$seo_fallback_description = mb_substr(strip_tags($service['description']), 0, 200);
$seo_fallback_image = $service['image'] ?? '';
require __DIR__ . '/includes/header.php';
?>

<main>
  <article class="project-detail">
    <section class="page-hero page-hero--project">
      <div class="bg-grid" aria-hidden="true"></div>
      <div class="container">
        <a href="/services.php" class="back-link reveal"><?= icon('arrow-left') ?> <?= e(t('nav_services')) ?></a>
        <div class="service-detail__icon reveal"><?= icon($service['icon']) ?></div>
        <h1 class="reveal"><?= e($service['title']) ?></h1>
        <p class="reveal"><?= e($service['description']) ?></p>
      </div>
    </section>

    <section class="section section--tight">
      <div class="container container--narrow">
        <?php if ($visualData): ?>
          <div class="reveal"><?= render_service_visual($service['slug']) ?></div>
        <?php elseif (!empty($service['image'])): ?>
          <div class="project-detail__cover reveal">
            <img src="<?= e($service['image']) ?>" alt="<?= e($service['title']) ?>">
          </div>
        <?php endif; ?>
        <div class="project-detail__content reveal">
          <?php foreach (explode("\n\n", $service['content']) as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        </div>

        <?php if ($visualData): ?>
          <h2 class="service-section-title reveal"><?= e(t('service_whats_included')) ?></h2>
          <div class="service-feature-list reveal">
            <?php foreach ($visualData['features'] as $feature): ?>
              <div class="service-feature-item"><?= icon('check-circle') ?> <span><?= e($feature) ?></span></div>
            <?php endforeach; ?>
          </div>

          <h2 class="service-section-title reveal"><?= e(t('service_technologies')) ?></h2>
          <div class="project-tech-pills reveal">
            <?php foreach ($visualData['tech'] as $tech): ?><span class="project-tech-pill"><?= e($tech) ?></span><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($visualData): ?>
      <section class="section section--tight service-process-section">
        <div class="container container--narrow">
          <h2 class="service-section-title reveal"><?= e(t('service_our_process')) ?></h2>
          <div class="service-process">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <div class="service-process__step reveal">
                <span class="service-process__num"><?= $i ?></span>
                <h3><?= e(t('process_step_' . $i . '_title')) ?></h3>
                <p><?= e(t('process_step_' . $i . '_desc')) ?></p>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($prevService || $nextService): ?>
      <section class="section section--tight post-nav-section">
        <div class="container container--narrow">
          <div class="post-nav">
            <?php if ($prevService): ?>
              <a href="/service.php?slug=<?= e(urlencode($prevService['slug'])) ?>" class="post-nav__item post-nav__item--prev">
                <span class="post-nav__label"><?= icon('arrow-left') ?> <?= e(t('service_prev')) ?></span>
                <span class="post-nav__title"><?= e($prevService['title']) ?></span>
              </a>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($nextService): ?>
              <a href="/service.php?slug=<?= e(urlencode($nextService['slug'])) ?>" class="post-nav__item post-nav__item--next">
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
          <a href="/contact.php" class="btn btn--primary"><?= e(t('cta_btn_primary')) ?></a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
