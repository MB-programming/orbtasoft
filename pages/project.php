<?php
require __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$project = $slug ? portfolio_item_by_slug($slug) : null;

if (!$project) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$neighbors = portfolio_neighbors((int) $project['id']);
$prevProject = $neighbors['prev'] ? portfolio_item_by_slug($neighbors['prev']) : null;
$nextProject = $neighbors['next'] ? portfolio_item_by_slug($neighbors['next']) : null;

$current_page = 'portfolio';
require __DIR__ . '/../includes/header.php';
?>

<main>
  <article class="project-detail">
    <section class="page-hero page-hero--project">
      <div class="bg-grid" aria-hidden="true"></div>
      <div class="container">
        <a href="/pages/portfolio.php" class="back-link reveal"><?= icon('arrow-left') ?> <?= e(t('project_back_to_work')) ?></a>
        <span class="kicker reveal"><?= e($project['tag']) ?></span>
        <h1 class="reveal"><?= e($project['title']) ?></h1>
        <div class="project-detail__meta reveal">
          <div class="project-detail__meta-item">
            <span class="project-detail__meta-label"><?= e(t('project_client')) ?></span>
            <span class="project-detail__meta-value"><?= e($project['client']) ?></span>
          </div>
          <div class="project-detail__meta-item">
            <span class="project-detail__meta-label"><?= e(t('project_year')) ?></span>
            <span class="project-detail__meta-value"><?= e($project['year']) ?></span>
          </div>
          <?php if (!empty($project['project_url'])): ?>
            <a href="<?= e($project['project_url']) ?>" class="btn btn--outline btn--sm" target="_blank" rel="noopener noreferrer">
              <?= e(t('project_visit_site')) ?> <?= icon('arrow-right') ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section section--tight">
      <div class="container container--narrow">
        <div class="project-detail__cover reveal">
          <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>">
        </div>
        <div class="project-detail__content reveal">
          <h2><?= e(t('project_overview')) ?></h2>
          <?php foreach (explode("\n\n", $project['description']) as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ($prevProject || $nextProject): ?>
      <section class="section section--tight post-nav-section">
        <div class="container container--narrow">
          <div class="post-nav">
            <?php if ($prevProject): ?>
              <a href="/pages/project.php?slug=<?= e(urlencode($prevProject['slug'])) ?>" class="post-nav__item post-nav__item--prev">
                <span class="post-nav__label"><?= icon('arrow-left') ?> <?= e(t('project_prev')) ?></span>
                <span class="post-nav__title"><?= e($prevProject['title']) ?></span>
              </a>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($nextProject): ?>
              <a href="/pages/project.php?slug=<?= e(urlencode($nextProject['slug'])) ?>" class="post-nav__item post-nav__item--next">
                <span class="post-nav__label"><?= e(t('project_next')) ?> <?= icon('arrow-right') ?></span>
                <span class="post-nav__title"><?= e($nextProject['title']) ?></span>
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
