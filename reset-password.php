<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';

if (is_logged_in()) {
    header('Location: /account/index.php?lang=' . urlencode(current_lang()));
    exit;
}

$token = trim($_GET['token'] ?? '');
$valid = false;

if ($token !== '') {
    $pdo = get_db();
    if ($pdo) {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare('SELECT id FROM password_resets WHERE token_hash = :hash AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['hash' => $tokenHash]);
        $valid = (bool) $stmt->fetch();
    }
}

$errorMap = [
    'fields' => 'auth_error_fields',
    'password_mismatch' => 'auth_error_password_mismatch',
    'invalid' => 'auth_reset_invalid',
];
$errorKey = $errorMap[$_GET['error'] ?? ''] ?? null;

$current_page = 'login';
require __DIR__ . '/includes/header.php';
?>

<main>
  <div class="auth-page">
    <section class="auth-page__form">
      <div class="auth-page__form-inner">
        <h1><?= e(t('auth_reset_title')) ?></h1>
        <p><?= e(t('auth_reset_desc')) ?></p>

        <?php if ($errorKey): ?>
          <div class="form-status is-error" style="display:block; margin-block-end:20px;"><?= e(t($errorKey)) ?></div>
        <?php endif; ?>

        <?php if (!$valid): ?>
          <div class="form-status is-error" style="display:block; margin-block-end:20px;"><?= e(t('auth_reset_invalid')) ?></div>
          <p class="auth-footer-link">
            <a href="/forgot-password.php"><?= e(t('auth_reset_request_new')) ?></a>
          </p>
        <?php else: ?>
          <form method="post" action="/auth-reset-password.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="form-row auth-field-row">
              <label for="password"><?= e(t('auth_reset_new_password')) ?></label>
              <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
              <button type="button" class="toggle-password" data-target="password" aria-label="Toggle password visibility">
                <?= icon('eye') ?>
              </button>
            </div>

            <div class="form-row auth-field-row">
              <label for="password_confirm"><?= e(t('auth_reset_confirm_password')) ?></label>
              <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" minlength="8">
              <button type="button" class="toggle-password" data-target="password_confirm" aria-label="Toggle password visibility">
                <?= icon('eye') ?>
              </button>
            </div>

            <button type="submit" class="btn btn--primary" style="width:100%;"><?= e(t('auth_reset_btn')) ?></button>
          </form>
        <?php endif; ?>
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
