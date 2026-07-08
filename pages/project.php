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
          <?php if (!empty($project['duration'])): ?>
            <div class="project-detail__meta-item">
              <span class="project-detail__meta-label"><?= e(t('project_duration')) ?></span>
              <span class="project-detail__meta-value"><?= e($project['duration']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($project['project_url'])): ?>
            <a href="<?= e($project['project_url']) ?>" class="btn btn--primary btn--sm" target="_blank" rel="noopener noreferrer">
              <?= e(t('project_preview')) ?> <?= icon('arrow-right') ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section section--tight">
      <div class="container container--narrow">
        <?php if ($project['category'] === 'uiux' && !empty($project['gallery'])): ?>
          <h2 class="reveal" style="margin-block-end:20px;"><?= e(t('project_gallery')) ?></h2>
          <div class="project-gallery reveal">
            <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>">
            <?php foreach ($project['gallery'] as $shot): ?>
              <img src="<?= e($shot['image']) ?>" alt="<?= e($project['title']) ?>">
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="project-preview-frame reveal">
            <div class="project-preview-frame__bar">
              <span></span><span></span><span></span>
              <?php if (!empty($project['project_url'])): ?>
                <span class="project-preview-frame__url"><?= e($project['project_url']) ?></span>
              <?php endif; ?>
            </div>
            <div class="project-preview-frame__media">
              <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>">
              <?php if (!empty($project['project_url'])): ?>
                <a href="<?= e($project['project_url']) ?>" class="project-preview-frame__cta" target="_blank" rel="noopener noreferrer">
                  <span class="btn btn--primary btn--sm"><?= e(t('project_preview')) ?> <?= icon('arrow-right') ?></span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($project['technologies'])): ?>
          <div class="project-tech-pills reveal" style="margin-block-start:28px;">
            <?php foreach (array_map('trim', explode(',', $project['technologies'])) as $tech): ?>
              <?php if ($tech !== ''): ?><span class="project-tech-pill"><?= e($tech) ?></span><?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php
          $metrics = array_filter([
              [$project['metric_1_label'], $project['metric_1_value']],
              [$project['metric_2_label'], $project['metric_2_value']],
              [$project['metric_3_label'], $project['metric_3_value']],
          ], fn($m) => $m[0] !== '' && $m[1] !== '');
        ?>
        <?php if ($metrics): ?>
          <h2 class="reveal" style="margin-block-start:36px; margin-block-end:16px;"><?= e(t('project_performance')) ?></h2>
          <div class="project-metrics reveal">
            <?php foreach ($metrics as $m): ?>
              <div class="project-metric-card">
                <div class="project-metric-card__value"><?= e($m[1]) ?></div>
                <div class="project-metric-card__label"><?= e($m[0]) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="project-detail__content reveal" style="margin-block-start:36px;">
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
