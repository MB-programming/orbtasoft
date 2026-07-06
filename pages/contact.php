<?php
require __DIR__ . '/../includes/functions.php';
$current_page = 'contact';
require __DIR__ . '/../includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('contact_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('contact_heading')) ?></h1>
      <p class="reveal"><?= e(t('contact_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="contact-grid">
        <div class="contact-info reveal">
          <h2><?= e(t('contact_heading')) ?></h2>
          <p><?= e(t('contact_desc')) ?></p>

          <div class="contact-detail">
            <span class="icon"><?= icon('mail') ?></span>
            <span>hello@orbtasoft.dev</span>
          </div>
          <div class="contact-detail">
            <span class="icon"><?= icon('phone') ?></span>
            <span dir="ltr">+20 100 000 0000</span>
          </div>
          <div class="contact-detail">
            <span class="icon"><?= icon('map-pin') ?></span>
            <span><?= current_lang() === 'ar' ? 'عن بُعد / عالميًا' : 'Remote / Worldwide' ?></span>
          </div>
        </div>

        <form id="contactForm" class="contact-form reveal" data-error-msg="<?= e(t('form_error')) ?>">
          <div class="form-row" style="position:absolute; left:-9999px;" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <label for="name"><?= e(t('form_name')) ?></label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="form-row">
            <label for="email"><?= e(t('form_email')) ?></label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-row">
            <label for="message"><?= e(t('form_message')) ?></label>
            <textarea id="message" name="message" required></textarea>
          </div>
          <button type="submit" class="btn btn--primary"><?= e(t('form_submit')) ?></button>
          <div id="formStatus" class="form-status"></div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
