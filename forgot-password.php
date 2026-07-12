<?php
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: /account/index.php?lang=' . urlencode(current_lang()));
    exit;
}

$sent = isset($_GET['sent']);

$current_page = 'login';
require __DIR__ . '/includes/header.php';
?>

<main>
  <div class="auth-page">
    <section class="auth-page__form">
      <div class="auth-page__form-inner">
        <h1><?= e(t('auth_forgot_title')) ?></h1>
        <p><?= e(t('auth_forgot_desc')) ?></p>

        <?php if ($sent): ?>
          <div class="form-status is-success" style="display:block; margin-block-end:20px;"><?= e(t('auth_forgot_sent')) ?></div>
        <?php else: ?>
          <form method="post" action="/auth-forgot-password.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-row" style="position:absolute; inset-inline-start:-9999px;" aria-hidden="true">
              <label for="website">Website</label>
              <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-row">
              <label for="email"><?= e(t('auth_email')) ?></label>
              <input type="email" id="email" name="email" required autocomplete="email">
            </div>

            <button type="submit" class="btn btn--primary" style="width:100%;"><?= e(t('auth_forgot_btn')) ?></button>
          </form>
        <?php endif; ?>

        <p class="auth-footer-link">
          <a href="/login.php"><?= e(t('auth_forgot_back_to_login')) ?></a>
        </p>
      </div>
    </section>

    <aside class="auth-page__hero">
      <div class="auth-page__hero-bg">
        <div class="bg-grid" aria-hidden="true"></div>
        <div class="auth-page__hero-brand">
          <span class="brand__mark"><?= icon('brand-mark') ?></span> <?= e(t('hero_brand')) ?>
        </div>
      </div>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
