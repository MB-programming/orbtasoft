<?php
require __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: /index.php?lang=' . urlencode(current_lang()));
    exit;
}

$errorMap = [
    'fields'            => 'auth_error_fields',
    'email_taken'       => 'auth_error_email_taken',
    'password_mismatch' => 'auth_error_password_mismatch',
    'generic'           => 'auth_error_generic',
];
$errorCode = $_GET['error'] ?? '';
$errorKey = $errorMap[$errorCode] ?? null;

$current_page = 'register';
require __DIR__ . '/../includes/header.php';
?>

<main>
  <div class="auth-page">
    <section class="auth-page__form">
      <div class="auth-page__form-inner">
        <h1><?= e(t('auth_register_title')) ?></h1>
        <p><?= e(t('auth_register_desc')) ?></p>

        <?php if ($errorKey): ?>
          <div class="form-status is-error" style="display:block; margin-block-end:20px;"><?= e(t($errorKey)) ?></div>
        <?php endif; ?>

        <form method="post" action="/auth-register.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px;" aria-hidden="true">

          <div class="form-row">
            <label for="name"><?= e(t('auth_name')) ?></label>
            <input type="text" id="name" name="name" required autocomplete="name">
          </div>

          <div class="form-row">
            <label for="email"><?= e(t('auth_email')) ?></label>
            <input type="email" id="email" name="email" required autocomplete="email">
          </div>

          <div class="form-row auth-field-row">
            <label for="password"><?= e(t('auth_password')) ?></label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
            <button type="button" class="toggle-password" data-target="password" aria-label="Toggle password visibility">
              <?= icon('eye') ?>
            </button>
          </div>

          <div class="form-row auth-field-row">
            <label for="password_confirm"><?= e(t('auth_confirm_password')) ?></label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
            <button type="button" class="toggle-password" data-target="password_confirm" aria-label="Toggle password visibility">
              <?= icon('eye') ?>
            </button>
          </div>

          <button type="submit" class="btn btn--primary" style="width:100%; margin-block-start:8px;"><?= e(t('auth_signup_btn')) ?></button>
        </form>

        <p class="auth-footer-link">
          <?= e(t('auth_have_account')) ?> <a href="/pages/login.php"><?= e(t('auth_login_link')) ?></a>
        </p>
      </div>
    </section>

    <aside class="auth-page__hero">
      <div class="auth-page__hero-bg">
        <div class="bg-grid" aria-hidden="true"></div>
        <div class="auth-page__hero-brand">
          <span>⚡</span> <?= e(t('hero_brand')) ?>
        </div>
        <div class="auth-testimonial-stack">
          <?php foreach (array_slice(testimonials_data(), 1, 2) as $tItem): ?>
            <div class="auth-testimonial-card">
              <div class="auth-testimonial-card__avatar" style="background: <?= e($tItem['color']) ?>;"><?= e(initials($tItem['name'])) ?></div>
              <p>
                <span class="name"><?= e($tItem['name']) ?></span><br>
                <span class="role"><?= e($tItem['role']) ?></span><br>
                <span class="text"><?= e($tItem['quote']) ?></span>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
