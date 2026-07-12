<?php
require __DIR__ . '/includes/functions.php';
$current_page = 'about';
require __DIR__ . '/includes/header.php';

$stats = [
    ['val' => t('stat_1_val'), 'label' => t('stat_1_label')],
    ['val' => t('stat_2_val'), 'label' => t('stat_2_label')],
    ['val' => t('stat_3_val'), 'label' => t('stat_3_label')],
    ['val' => t('stat_4_val'), 'label' => t('stat_4_label')],
];

$featuredQuote = testimonials_data()[2] ?? null;
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('about_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('about_heading')) ?></h1>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="about-grid">
        <div class="about-copy reveal">
          <h2><?= e(t('about_copy_heading')) ?></h2>
          <p><?= e(t('about_desc')) ?></p>
        </div>
        <div class="stats-grid">
          <?php foreach ($stats as $stat): ?>
            <div class="stat-card reveal">
              <div class="val"><?= e($stat['val']) ?></div>
              <div class="lbl"><?= e($stat['label']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ TEAM MARQUEE ============ -->
  <section class="section section--tight team-section reveal">
    <div class="container">
      <div class="team-section__head">
        <div class="team-section__icon"><?= icon('user-star') ?></div>
        <span class="kicker"><?= e(t('team_kicker')) ?></span>
        <h2><?= e(t('team_heading')) ?></h2>
        <p><?= e(t('team_desc')) ?></p>
      </div>
    </div>

    <div class="team-marquee">
      <div class="team-marquee__fade team-marquee__fade--start"></div>
      <div class="team-marquee__fade team-marquee__fade--end"></div>
      <div class="team-marquee__track">
        <?php foreach (array_merge(team_data(), team_data()) as $member): ?>
          <div class="team-card">
            <div class="team-card__avatar" style="<?= $member['image'] ? '' : 'background: ' . e($member['color']) . ';' ?>">
              <?php if ($member['image']): ?>
                <img src="<?= e($member['image']) ?>" alt="<?= e($member['name']) ?>" class="team-card__photo">
              <?php else: ?>
                <span class="team-card__initials"><?= e(initials($member['name'])) ?></span>
              <?php endif; ?>
              <div class="team-card__info">
                <h3><?= e($member['name']) ?></h3>
                <p><?= e($member['role']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($featuredQuote): ?>
    <div class="container">
      <div class="team-quote">
        <p class="team-quote__text">&ldquo;<?= e($featuredQuote['quote']) ?>&rdquo;</p>
        <div class="team-quote__author">
          <div class="team-quote__avatar" style="<?= $featuredQuote['image'] ? '' : 'background: ' . e($featuredQuote['color']) . ';' ?>">
            <?php if ($featuredQuote['image']): ?><img src="<?= e($featuredQuote['image']) ?>" alt="" class="team-quote__photo"><?php else: ?><?= e(initials($featuredQuote['name'])) ?><?php endif; ?>
          </div>
          <p><?= e($featuredQuote['name']) ?></p>
          <p><?= e($featuredQuote['role']) ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="section__head reveal">
        <span class="kicker"><?= e(t('stack_kicker')) ?></span>
        <h2><?= e(t('stack_heading')) ?></h2>
      </div>
    </div>
    <div class="stack-strip">
      <div class="stack-track">
        <?php $stack = array_merge(stack_data(), stack_data()); foreach ($stack as $tech): ?>
          <span class="stack-pill"><span class="dot"></span><?= e($tech) ?></span>
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
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
