<?php $year = date('Y'); ?>
<footer class="site-footer site-footer--pro">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <a href="/index.php" class="brand">
        <span class="brand__mark"><?= icon('brand-mark') ?></span>
        <span class="brand__name"><?= e(t('hero_brand')) ?></span>
      </a>
      <p><?= e(t('footer_tagline')) ?></p>
      <div class="site-footer__social">
        <a href="#" aria-label="GitHub"><?= icon('code-2') ?></a>
        <a href="#" aria-label="LinkedIn"><?= icon('briefcase') ?></a>
        <a href="#" aria-label="X"><?= icon('send') ?></a>
      </div>
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
