<?php
$year = date('Y');
$socialLinks = [
    ['url' => site_setting('social_github'), 'label' => 'GitHub', 'icon' => 'code-2'],
    ['url' => site_setting('social_linkedin'), 'label' => 'LinkedIn', 'icon' => 'briefcase'],
    ['url' => site_setting('social_x'), 'label' => 'X', 'icon' => 'send'],
];
?>
<footer class="site-footer site-footer--pro">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <a href="/index.php" class="brand">
        <span class="brand__mark"><?= icon('brand-mark') ?></span>
        <span class="brand__name"><?= e(t('hero_brand')) ?></span>
      </a>
      <p><?= e(t('footer_tagline')) ?></p>
      <?php if (array_filter($socialLinks, fn($s) => $s['url'] !== '')): ?>
      <div class="site-footer__social">
        <?php foreach ($socialLinks as $social): ?>
          <?php if ($social['url'] !== ''): ?>
            <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($social['label']) ?>"><?= icon($social['icon']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="site-footer__col">
      <h3><?= e(t('footer_company_heading')) ?></h3>
      <a href="/about.php"><?= e(t('nav_about')) ?></a>
      <a href="/portfolio.php"><?= e(t('nav_portfolio')) ?></a>
      <a href="/blog.php"><?= e(t('nav_blog')) ?></a>
      <a href="/tools.php"><?= e(t('nav_tools')) ?></a>
      <a href="/contact.php"><?= e(t('nav_contact')) ?></a>
    </div>

    <div class="site-footer__col">
      <h3><?= e(t('footer_services_heading')) ?></h3>
      <?php foreach (services_data() as $footerService): ?>
        <a href="/service.php?slug=<?= e(urlencode($footerService['slug'])) ?>"><?= e($footerService['title']) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="site-footer__col site-footer__col--contact">
      <h3><?= e(t('footer_contact_heading')) ?></h3>
      <a href="mailto:<?= e(t('company_email')) ?>" class="site-footer__contact-item"><?= icon('mail') ?> <span><?= e(t('company_email')) ?></span></a>
      <a href="tel:<?= e(preg_replace('/\s+/', '', t('company_phone'))) ?>" class="site-footer__contact-item" dir="ltr"><?= icon('phone') ?> <span><?= e(t('company_phone')) ?></span></a>
      <span class="site-footer__contact-item"><?= icon('map-pin') ?> <span><?= e(t('company_location')) ?></span></span>
      <a href="/contact.php" class="btn btn--primary btn--sm site-footer__cta"><?= e(t('footer_newsletter_cta')) ?></a>
    </div>
  </div>
  <div class="container site-footer__bottom">
    <span>&copy; <?= e((string) $year) ?> <?= e(t('hero_brand')) ?>. <?= e(t('footer_rights')) ?></span>
  </div>
</footer>

<script src="/assets/vendor/gsap/gsap.min.js"></script>
<script src="/assets/vendor/gsap/ScrollTrigger.min.js"></script>
<script src="/assets/vendor/three/three.min.js"></script>
<script src="/assets/js/three-bg.js"></script>
<script src="/assets/js/main.js"></script>
</body>
</html>
