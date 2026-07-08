<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/tools.php';

$current_page = 'tools';
$seed = trim($_GET['keyword'] ?? '');
$errorCode = null;
$groups = null;
$total = 0;

if ($seed !== '') {
    $groups = generate_keyword_ideas($seed, current_lang());
    foreach ($groups as $list) {
        $total += count($list);
    }
} elseif (isset($_GET['keyword'])) {
    $errorCode = 'empty';
}

require __DIR__ . '/includes/header.php';
?>

<main>
  <section class="page-hero">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="container">
      <span class="kicker reveal"><?= e(t('tools_kicker')) ?></span>
      <h1 class="reveal"><?= e(t('tool_keywords_title')) ?></h1>
      <p class="reveal"><?= e(t('tool_keywords_desc')) ?></p>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <form method="get" action="/tool-keywords.php" class="tool-form reveal no-print">
        <input type="text" name="keyword" value="<?= e($seed) ?>" placeholder="<?= e(t('keywords_seed_placeholder')) ?>" aria-label="<?= e(t('keywords_seed_label')) ?>" required>
        <button type="submit" class="btn btn--primary"><?= icon('search') ?> <?= e(t('keywords_generate_btn')) ?></button>
      </form>

      <?php if ($errorCode === 'empty'): ?>
        <div class="tool-error"><?= e(t('keywords_error_empty')) ?></div>
      <?php elseif ($groups): ?>
        <div class="tool-result">
          <?= tools_render_report_header(t('tool_keywords_title'), $seed, false) ?>
          <p class="kw-total"><?= (int) $total ?> <?= e(t('keywords_total_label')) ?></p>

          <?php
          $groupLabels = [
              'questions' => t('kw_group_questions'), 'comparisons' => t('kw_group_comparisons'),
              'commercial' => t('kw_group_commercial'), 'longtail' => t('kw_group_longtail'), 'local' => t('kw_group_local'),
          ];
          foreach ($groups as $key => $list): ?>
            <div class="kw-group">
              <div class="kw-group__head">
                <h3><?= e($groupLabels[$key] ?? $key) ?></h3>
                <button type="button" class="kw-group__copy-all" data-copy-group="<?= e($key) ?>"><?= e(t('keywords_copy_all')) ?></button>
              </div>
              <div class="kw-list" id="kwGroup-<?= e($key) ?>">
                <?php foreach ($list as $phrase): ?>
                  <button type="button" class="kw-chip" data-phrase="<?= e($phrase) ?>">
                    <span><?= e($phrase) ?></span>
                    <span class="kw-chip__copy"><?= e(t('keywords_copy')) ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <?= tools_render_tips(['tip_kw_long_tail', 'tip_kw_intent', 'tip_kw_no_stuffing', 'tip_kw_use_in_titles']) ?>

          <?= tools_render_cta() ?>

          <div class="tool-actions-row">
            <?= tools_render_pdf_button() ?>
            <a href="/tool-keywords.php" class="tool-check-another no-print"><?= e(t('tools_check_another')) ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<script>
(function () {
  function flashCopied(chip) {
    var label = chip.querySelector('.kw-chip__copy');
    var original = label.textContent;
    chip.classList.add('is-copied');
    label.textContent = <?= json_encode(t('keywords_copied')) ?>;
    setTimeout(function () { chip.classList.remove('is-copied'); label.textContent = original; }, 1500);
  }
  document.querySelectorAll('.kw-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var text = chip.getAttribute('data-phrase');
      if (navigator.clipboard) { navigator.clipboard.writeText(text).catch(function () {}); }
      flashCopied(chip);
    });
  });
  document.querySelectorAll('[data-copy-group]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = document.getElementById('kwGroup-' + btn.getAttribute('data-copy-group'));
      var phrases = Array.prototype.map.call(group.querySelectorAll('.kw-chip'), function (chip) { return chip.getAttribute('data-phrase'); });
      var text = phrases.join('\n');
      if (navigator.clipboard) { navigator.clipboard.writeText(text).catch(function () {}); }
    });
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
