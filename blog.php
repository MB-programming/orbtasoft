<?php
require __DIR__ . '/includes/functions.php';
$current_page = 'blog';
$posts = blog_posts_data();
require __DIR__ . '/includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('blog_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('blog_heading')) ?></h1>
      <p class="reveal"><?= e(t('blog_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <?php if (!$posts): ?>
        <p class="blog-empty reveal"><?= e(t('blog_empty')) ?></p>
      <?php else: ?>
        <div class="blog-grid">
          <?php foreach ($posts as $post): ?>
            <a class="blog-card reveal" href="/blog-post.php?slug=<?= e(urlencode($post['slug'])) ?>">
              <div class="blog-card__media">
                <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
              </div>
              <div class="blog-card__body">
                <div class="blog-card__meta">
                  <span><?= e(format_date($post['published_at'])) ?></span>
                  <span aria-hidden="true">&middot;</span>
                  <span><?= e($post['author']) ?></span>
                </div>
                <h3 class="blog-card__title"><?= e($post['title']) ?></h3>
                <p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p>
                <span class="blog-card__link"><?= e(t('blog_read_more')) ?> →</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
