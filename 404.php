<?php
require __DIR__ . '/includes/functions.php';
http_response_code(404);
require __DIR__ . '/includes/header.php';
?>

<main>
  <section class="error-404">
    <div class="bg-grid" aria-hidden="true"></div>

    <div class="error-404__inner">
      <div class="error-404__row error-404-fade">
        <span class="error-404__digit">4</span>
        <div class="error-404__globe-wrap">
          <div class="globe-canvas-host">
            <canvas id="globeCanvas"></canvas>
          </div>
        </div>
        <span class="error-404__digit">4</span>
      </div>

      <h1 class="error-404-fade"><?= e(t('error_404_title')) ?></h1>
      <p class="error-404-fade"><?= e(t('error_404_desc')) ?></p>

      <div class="error-404-fade">
        <a href="/index.php" class="btn btn--primary">
          <?= dir_attr() === 'rtl' ? icon('arrow-right') : icon('arrow-left') ?>
          <?= e(t('error_404_back')) ?>
        </a>
      </div>
    </div>
  </section>
</main>

<script src="/assets/vendor/cobe.min.js"></script>
<script src="/assets/js/globe.js"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
