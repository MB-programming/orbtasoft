<?php $year = date('Y'); ?>
<footer class="site-footer">
  <div class="container site-footer__inner">
    <div class="site-footer__brand">
      <a href="/index.php" class="brand">
        <span class="brand__mark">⚡</span>
        <span class="brand__name"><?= e(t('hero_brand')) ?></span>
      </a>
      <p><?= e(t('footer_tagline')) ?></p>
    </div>

    <div class="site-footer__links">
      <a href="/pages/services.php"><?= e(t('nav_services')) ?></a>
      <a href="/pages/portfolio.php"><?= e(t('nav_portfolio')) ?></a>
      <a href="/pages/about.php"><?= e(t('nav_about')) ?></a>
      <a href="/pages/contact.php"><?= e(t('nav_contact')) ?></a>
    </div>

    <div class="site-footer__social">
      <a href="#" aria-label="GitHub">GitHub</a>
      <a href="#" aria-label="LinkedIn">LinkedIn</a>
      <a href="#" aria-label="X">X</a>
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
