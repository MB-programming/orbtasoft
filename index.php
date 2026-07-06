<?php
require __DIR__ . '/includes/functions.php';
$current_page = 'home';
require __DIR__ . '/includes/header.php';
?>

<main>

  <!-- ============ HERO ============ -->
  <section class="hero">
    <div id="heroCanvas" class="hero-canvas" aria-hidden="true"></div>
    <div class="bg-grid" aria-hidden="true"></div>

    <div class="hero__inner">
      <span class="hero__eyebrow hero-line"><span class="dot"></span> Orbtasoft Studio</span>
      <h1>
        <span class="hero-line text-track" style="display:block;"><?= e(t('hero_tagline_1')) ?></span>
        <span class="hero-line text-silver" style="display:block;"><?= e(t('hero_tagline_2')) ?></span>
      </h1>
      <p class="hero__desc hero-line"><?= t('meta_desc') ?></p>
      <div class="hero__actions hero-line">
        <a href="/pages/contact.php" class="btn btn--primary"><?= e(t('nav_cta')) ?></a>
        <a href="/pages/portfolio.php" class="btn btn--outline"><?= e(t('work_view_all')) ?></a>
      </div>
    </div>

    <div class="hero__scroll-cue">
      <span><?= e(t('hero_scroll_hint')) ?></span>
      <span class="stem"></span>
    </div>
  </section>

  <!-- ============ SHOWCASE CARD ============ -->
  <section class="showcase">
    <div class="container showcase__inner">
      <div class="premium-card reveal">
        <div class="premium-card__sheen" aria-hidden="true"></div>
        <div class="premium-card__grid">

          <div class="premium-card__copy">
            <h3><?= e(t('hero_card_heading')) ?></h3>
            <p><?= t('hero_card_desc') ?></p>
          </div>

          <div class="device">
            <div class="device__frame">
              <div class="device__topbar"><span></span><span></span><span></span></div>
              <div class="device__body">
                <div class="code-line"><span class="c5">01</span> <span class="c1">function</span> <span class="c3">launchProduct</span>() {</div>
                <div class="code-line"><span class="c5">02</span> &nbsp;&nbsp;<span class="c1">const</span> idea = <span class="c4">"yours"</span>;</div>
                <div class="code-line"><span class="c5">03</span> &nbsp;&nbsp;<span class="c1">const</span> team = orbtasoft.<span class="c3">assemble</span>();</div>
                <div class="code-line"><span class="c5">04</span> &nbsp;&nbsp;<span class="c1">return</span> team.<span class="c3">build</span>(idea)<span class="c2">.ship()</span>;</div>
                <div class="code-line"><span class="c5">05</span> }<span class="device__caret"></span></div>
              </div>
              <div class="device__metric" style="margin: 0 20px 20px;">
                <div>
                  <div class="val"><span class="counter-val" data-target="80">0</span>+</div>
                  <div class="lbl"><?= e(t('hero_metric_label')) ?></div>
                </div>
                <svg width="46" height="46" viewBox="0 0 46 46" aria-hidden="true">
                  <circle cx="23" cy="23" r="19" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"/>
                  <circle cx="23" cy="23" r="19" fill="none" stroke="#3b82f6" stroke-width="5" stroke-dasharray="100" stroke-dashoffset="15" stroke-linecap="round" transform="rotate(-90 23 23)"/>
                </svg>
              </div>
            </div>

            <div class="floating-badge badge-tl">
              <div class="floating-badge__icon">🚀</div>
              <div>
                <p class="floating-badge__title"><?= e(t('hero_badge_1_t')) ?></p>
                <p class="floating-badge__sub"><?= e(t('hero_badge_1_s')) ?></p>
              </div>
            </div>
            <div class="floating-badge badge-br">
              <div class="floating-badge__icon">✅</div>
              <div>
                <p class="floating-badge__title"><?= e(t('hero_badge_2_t')) ?></p>
                <p class="floating-badge__sub"><?= e(t('hero_badge_2_s')) ?></p>
              </div>
            </div>
          </div>

          <div class="premium-card__brand">
            <h2><?= e(t('hero_brand')) ?></h2>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- ============ SERVICES ============ -->
  <section class="section" id="services">
    <div class="container">
      <div class="section__head reveal">
        <span class="kicker"><?= e(t('services_kicker')) ?></span>
        <h2><?= e(t('services_heading')) ?></h2>
        <p><?= e(t('services_desc')) ?></p>
      </div>
      <div class="services-grid">
        <?php foreach (services_data() as $service): ?>
          <div class="service-card reveal">
            <div class="service-card__icon"><?= icon($service['icon']) ?></div>
            <h3><?= e($service['title']) ?></h3>
            <p><?= e($service['desc']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============ STACK MARQUEE ============ -->
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

  <!-- ============ PORTFOLIO — CREATIVE HORIZONTAL GALLERY ============ -->
  <section class="section work-gallery" id="work">
    <div class="container">
      <div class="section__head reveal" style="text-align: start; margin-inline: 0;">
        <span class="kicker"><?= e(t('work_kicker')) ?></span>
        <h2><?= e(t('work_heading')) ?></h2>
        <div class="work-gallery__hint"><span class="arrow"></span> <?= e(t('work_drag_hint')) ?></div>
      </div>
    </div>

    <div class="work-gallery__pin">
      <div class="work-gallery__track">
        <?php foreach (portfolio_data() as $i => $project): ?>
          <div class="work-card">
            <span class="work-card__index">0<?= $i + 1 ?></span>
            <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" loading="lazy">
            <div class="work-card__overlay">
              <span class="work-card__tag"><?= e($project['tag']) ?></span>
              <h3 class="work-card__title"><?= e($project['title']) ?></h3>
              <span class="work-card__link"><?= e(t('work_view_project')) ?> →</span>
            </div>
          </div>
        <?php endforeach; ?>
        <a href="/pages/portfolio.php" class="work-card work-card--cta">
          <h3><?= e(t('work_view_all')) ?></h3>
          <p><?= e(t('cta_desc')) ?></p>
          <span class="btn btn--outline btn--sm"><?= e(t('work_view_all')) ?> →</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ============ CTA ============ -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-card reveal">
        <h2><?= e(t('cta_heading')) ?></h2>
        <p><?= e(t('cta_desc')) ?></p>
        <div class="hero__actions">
          <a href="/pages/contact.php" class="btn btn--primary"><?= e(t('cta_btn_primary')) ?></a>
          <a href="/pages/portfolio.php" class="btn btn--secondary"><?= e(t('cta_btn_secondary')) ?></a>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
