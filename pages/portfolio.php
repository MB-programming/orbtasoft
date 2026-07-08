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

  <section class="section section--tight work-gallery">
    <div class="container">
      <div class="work-filter" id="workFilter" role="tablist">
        <button type="button" class="work-filter__btn is-active" data-filter="all"><?= e(t('work_filter_all')) ?></button>
        <?php foreach (portfolio_categories() as $key => $label): ?>
          <button type="button" class="work-filter__btn" data-filter="<?= e($key) ?>"><?= e($label) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="work-gallery__hint reveal"><span class="arrow"></span> <?= e(t('work_drag_hint')) ?></div>
    </div>
    <div class="work-gallery__pin">
      <div class="work-gallery__track" id="workTrack">
        <?php foreach (portfolio_data() as $i => $project): ?>
          <a class="work-card" data-category="<?= e($project['category']) ?>" href="/pages/project.php?slug=<?= e(urlencode($project['slug'])) ?>">
            <span class="work-card__index">0<?= $i + 1 ?></span>
            <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" loading="lazy">
            <div class="work-card__overlay">
              <span class="work-card__tag"><?= e($project['tag']) ?></span>
              <h3 class="work-card__title"><?= e($project['title']) ?></h3>
              <span class="work-card__link"><?= e(t('work_view_project')) ?> →</span>
            </div>
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
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
