<?php
require __DIR__ . '/includes/auth.php';
$current_admin_page = 'dashboard';
require __DIR__ . '/includes/layout-top.php';

$pdo = get_db();
function admin_count(?PDO $pdo, string $table): int
{
    if (!$pdo) return 0;
    return (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$stats = [
    ['label' => 'Contact Messages', 'href' => '/admin/messages.php', 'count' => admin_count($pdo, 'contact_messages')],
    ['label' => 'Newsletter Subscribers', 'href' => '/admin/newsletter.php', 'count' => admin_count($pdo, 'newsletter_subscribers')],
    ['label' => 'Registered Users', 'href' => '/admin/clients.php', 'count' => admin_count($pdo, 'users')],
    ['label' => 'Invoices', 'href' => '/admin/invoices.php', 'count' => admin_count($pdo, 'invoices')],
    ['label' => 'Blog Posts', 'href' => '/admin/blog.php', 'count' => admin_count($pdo, 'blog_posts')],
    ['label' => 'Services', 'href' => '/admin/services.php', 'count' => admin_count($pdo, 'services')],
    ['label' => 'Portfolio Items', 'href' => '/admin/portfolio.php', 'count' => admin_count($pdo, 'portfolio_items')],
    ['label' => 'Team Members', 'href' => '/admin/team.php', 'count' => admin_count($pdo, 'team_members')],
    ['label' => 'Testimonials', 'href' => '/admin/testimonials.php', 'count' => admin_count($pdo, 'testimonials')],
    ['label' => 'Partners', 'href' => '/admin/partners.php', 'count' => admin_count($pdo, 'partners')],
    ['label' => 'Tech Stack Items', 'href' => '/admin/stack.php', 'count' => admin_count($pdo, 'tech_stack')],
];

$recentMessages = $pdo ? $pdo->query('SELECT name, email, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="admin-page-head">
  <div>
    <h1>Dashboard</h1>
    <p>Overview of your site's content and activity.</p>
  </div>
</div>

<div class="admin-stats-grid">
  <?php foreach ($stats as $s): ?>
    <div class="admin-stat-card">
      <a href="<?= e($s['href']) ?>">
        <div class="val"><?= (int) $s['count'] ?></div>
        <div class="lbl"><?= e($s['label']) ?></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="admin-panel">
  <h2>Recent Contact Messages</h2>
  <?php if (empty($recentMessages)): ?>
    <div class="admin-empty">No messages yet.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Received</th></tr></thead>
        <tbody>
          <?php foreach ($recentMessages as $m): ?>
            <tr>
              <td class="cell-strong"><?= e($m['name']) ?></td>
              <td class="cell-muted"><?= e($m['email']) ?></td>
              <td class="cell-muted"><?= e($m['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-block-start:18px;"><a href="/admin/messages.php" class="btn btn--outline btn--sm">View all messages</a></div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
