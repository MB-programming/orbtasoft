<?php
require __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$post = $slug ? blog_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$neighbors = blog_neighbors((int) $post['id']);
$prevPost = $neighbors['prev'] ? blog_post_by_slug($neighbors['prev']) : null;
$nextPost = $neighbors['next'] ? blog_post_by_slug($neighbors['next']) : null;

$current_page = 'blog';
$seo_entity_type = 'blog_post';
$seo_entity_key = $post['slug'];
$seo_fallback_title = $post['title'] . ' — ' . t('hero_brand');
$seo_fallback_description = mb_substr(strip_tags($post['excerpt']), 0, 200);
$seo_fallback_image = $post['cover_image'];
require __DIR__ . '/includes/header.php';
?>

<main>
  <article class="blog-post">
    <section class="page-hero page-hero--blog">
      <div class="bg-grid" aria-hidden="true"></div>
      <div class="container">
        <a href="/blog.php" class="back-link reveal"><?= icon('arrow-left') ?> <?= e(t('blog_back_to_blog')) ?></a>
        <div class="blog-post__meta reveal">
          <span><?= e(format_date($post['published_at'])) ?></span>
          <span aria-hidden="true">&middot;</span>
          <span><?= e(t('blog_by')) ?> <?= e($post['author']) ?></span>
          <span aria-hidden="true">&middot;</span>
          <span><?= (int) reading_minutes($post['content']) ?> <?= e(t('blog_min_read')) ?></span>
        </div>
        <h1 class="reveal"><?= e($post['title']) ?></h1>
      </div>
    </section>

    <section class="section section--tight">
      <div class="container container--narrow">
        <div class="blog-post__cover reveal">
          <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>">
        </div>
        <div class="blog-post__content reveal">
          <?php foreach (explode("\n\n", $post['content']) as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ($prevPost || $nextPost): ?>
      <section class="section section--tight post-nav-section">
        <div class="container container--narrow">
          <div class="post-nav">
            <?php if ($prevPost): ?>
              <a href="/blog-post.php?slug=<?= e(urlencode($prevPost['slug'])) ?>" class="post-nav__item post-nav__item--prev">
                <span class="post-nav__label"><?= icon('arrow-left') ?> <?= e(t('blog_prev_post')) ?></span>
                <span class="post-nav__title"><?= e($prevPost['title']) ?></span>
              </a>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($nextPost): ?>
              <a href="/blog-post.php?slug=<?= e(urlencode($nextPost['slug'])) ?>" class="post-nav__item post-nav__item--next">
                <span class="post-nav__label"><?= e(t('blog_next_post')) ?> <?= icon('arrow-right') ?></span>
                <span class="post-nav__title"><?= e($nextPost['title']) ?></span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </article>

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
