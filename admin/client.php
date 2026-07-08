<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/client.php';
$admin = admin_require_login();
$pdo = get_db();

$clientId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    http_response_code(404);
    $current_admin_page = 'clients';
    require __DIR__ . '/includes/layout-top.php';
    echo '<div class="admin-empty">Client not found.</div>';
    require __DIR__ . '/includes/layout-bottom.php';
    exit;
}

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_project') {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                $flash = 'Project title is required.';
                $flashType = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO client_projects (user_id, title, description, status, progress) VALUES (:uid, :title, :desc, :status, :progress)');
                $stmt->execute([
                    'uid' => $clientId, 'title' => $title, 'desc' => trim($_POST['description'] ?? ''),
                    'status' => $_POST['status'] ?? 'planning', 'progress' => max(0, min(100, (int) ($_POST['progress'] ?? 0))),
                ]);
                notify_user($clientId, 'project', 'A new project was added to your account', $title, '/account/index.php');
                $flash = 'Project added.';
            }
        } elseif ($action === 'update_project') {
            $projectId = (int) ($_POST['project_id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE client_projects SET title = :title, description = :desc, status = :status, progress = :progress WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                'title' => trim($_POST['title'] ?? ''), 'desc' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 'planning', 'progress' => max(0, min(100, (int) ($_POST['progress'] ?? 0))),
                'id' => $projectId, 'uid' => $clientId,
            ]);
            notify_user($clientId, 'project', 'Your project was updated', trim($_POST['title'] ?? ''), '/account/index.php');
            $flash = 'Project updated.';
        } elseif ($action === 'delete_project') {
            $stmt = $pdo->prepare('DELETE FROM client_projects WHERE id = :id AND user_id = :uid');
            $stmt->execute(['id' => (int) ($_POST['project_id'] ?? 0), 'uid' => $clientId]);
            $flash = 'Project deleted.';
        } elseif ($action === 'add_milestone') {
            $title = trim($_POST['milestone_title'] ?? '');
            $projectId = (int) ($_POST['project_id'] ?? 0);
            if ($title !== '') {
                $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM project_milestones WHERE project_id = :pid');
                $orderStmt->execute(['pid' => $projectId]);
                $order = (int) $orderStmt->fetchColumn();
                $stmt = $pdo->prepare('INSERT INTO project_milestones (project_id, title, sort_order) VALUES (:pid, :title, :order)');
                $stmt->execute(['pid' => $projectId, 'title' => $title, 'order' => $order]);
                $flash = 'Milestone added.';
            }
        } elseif ($action === 'toggle_milestone') {
            $milestoneId = (int) ($_POST['milestone_id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE project_milestones SET is_done = NOT is_done, completed_at = IF(is_done = 0, NOW(), NULL) WHERE id = :id');
            $stmt->execute(['id' => $milestoneId]);
            $flash = 'Milestone updated.';
        } elseif ($action === 'delete_milestone') {
            $stmt = $pdo->prepare('DELETE FROM project_milestones WHERE id = :id');
            $stmt->execute(['id' => (int) ($_POST['milestone_id'] ?? 0)]);
            $flash = 'Milestone removed.';
        } elseif ($action === 'send_message') {
            $body = trim($_POST['body'] ?? '');
            if ($body !== '') {
                $stmt = $pdo->prepare("INSERT INTO client_messages (user_id, sender, admin_id, body) VALUES (:uid, 'admin', :aid, :body)");
                $stmt->execute(['uid' => $clientId, 'aid' => $admin['id'], 'body' => $body]);
                notify_user($clientId, 'message', 'New message from your account manager', mb_substr($body, 0, 120), '/account/messages.php');
            }
        } elseif ($action === 'add_wallet_tx') {
            $amount = (float) ($_POST['amount'] ?? 0);
            $type = ($_POST['type'] ?? 'credit') === 'debit' ? 'debit' : 'credit';
            if ($amount > 0) {
                $stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, amount, note) VALUES (:uid, :type, :amount, :note)');
                $stmt->execute(['uid' => $clientId, 'type' => $type, 'amount' => $amount, 'note' => trim($_POST['note'] ?? '')]);
                notify_user($clientId, 'wallet', 'Your wallet was updated', ($type === 'credit' ? '+' : '-') . format_money($amount), '/account/wallet.php');
                $flash = 'Wallet transaction recorded.';
            }
        }

        if ($action !== 'send_message') {
            header('Location: /admin/client.php?id=' . $clientId . '&saved=1');
            exit;
        }
    }
}

mark_messages_read($clientId, 'client');
$projects = client_projects($clientId);
$messages = client_messages($clientId);
$transactions = wallet_transactions($clientId);
$balance = wallet_balance($clientId);
$invoices = client_invoices($clientId);

$current_admin_page = 'clients';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1><?= e($client['name']) ?></h1>
    <p><?= e($client['email']) ?> · Registered <?= e(substr($client['created_at'], 0, 10)) ?></p>
  </div>
  <a href="/admin/clients.php" class="btn btn--outline btn--sm">&larr; All Clients</a>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php elseif (isset($_GET['saved'])): ?><div class="admin-flash is-success">Saved.</div><?php endif; ?>

<div class="admin-panel">
  <h2>Projects</h2>
  <?php foreach ($projects as $project): ?>
    <div class="admin-subpanel">
      <form method="post" action="/admin/client.php?id=<?= $clientId ?>" class="admin-form-grid" style="margin-block-end:14px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update_project">
        <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
        <div class="form-row span-3"><label>Title</label><input type="text" name="title" value="<?= e($project['title']) ?>" required></div>
        <div class="form-row span-3"><label>Description</label><textarea name="description"><?= e($project['description']) ?></textarea></div>
        <div class="form-row">
          <label>Status</label>
          <select name="status">
            <?php foreach (['planning', 'in_progress', 'review', 'completed', 'on_hold'] as $s): ?>
              <option value="<?= $s ?>" <?= $project['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row"><label>Progress (%)</label><input type="number" name="progress" min="0" max="100" value="<?= (int) $project['progress'] ?>"></div>
        <div class="form-row" style="align-self:end;">
          <button type="submit" class="btn btn--primary btn--sm">Save</button>
        </div>
      </form>

      <div class="project-milestones" style="margin-block-end:12px;">
        <?php foreach ($project['milestones'] as $m): ?>
          <div style="display:flex; align-items:center; gap:10px; padding-block:4px;">
            <form method="post" action="/admin/client.php?id=<?= $clientId ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="toggle_milestone">
              <input type="hidden" name="milestone_id" value="<?= (int) $m['id'] ?>">
              <button type="submit" class="admin-icon-btn" aria-label="Toggle"><?= icon($m['is_done'] ? 'check-circle' : 'check') ?></button>
            </form>
            <span style="<?= $m['is_done'] ? 'text-decoration:line-through;color:var(--color-muted);' : '' ?>"><?= e($m['title']) ?></span>
            <form method="post" action="/admin/client.php?id=<?= $clientId ?>" style="display:inline; margin-inline-start:auto;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_milestone">
              <input type="hidden" name="milestone_id" value="<?= (int) $m['id'] ?>">
              <button type="submit" class="admin-icon-btn is-danger" aria-label="Delete"><?= icon('trash') ?></button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" action="/admin/client.php?id=<?= $clientId ?>" style="display:flex; gap:10px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_milestone">
        <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
        <input type="text" name="milestone_title" placeholder="New milestone…" style="flex:1;">
        <button type="submit" class="btn btn--outline btn--sm">Add Milestone</button>
      </form>

      <form method="post" action="/admin/client.php?id=<?= $clientId ?>" data-confirm="Delete this project?" style="margin-block-start:14px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_project">
        <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
        <button type="submit" class="btn btn--outline btn--sm is-danger">Delete Project</button>
      </form>
    </div>
    <hr class="admin-divider">
  <?php endforeach; ?>

  <h3 style="margin-block-end:14px;">Add New Project</h3>
  <form method="post" action="/admin/client.php?id=<?= $clientId ?>" class="admin-form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_project">
    <div class="form-row span-3"><label>Title</label><input type="text" name="title" required></div>
    <div class="form-row span-3"><label>Description</label><textarea name="description"></textarea></div>
    <div class="form-row">
      <label>Status</label>
      <select name="status">
        <option value="planning">Planning</option>
        <option value="in_progress">In Progress</option>
        <option value="review">Review</option>
        <option value="completed">Completed</option>
        <option value="on_hold">On Hold</option>
      </select>
    </div>
    <div class="form-row"><label>Progress (%)</label><input type="number" name="progress" min="0" max="100" value="0"></div>
    <div class="form-row" style="align-self:end;"><button type="submit" class="btn btn--primary btn--sm">Add Project</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>Messages</h2>
  <div class="chat-window" style="max-height:340px;">
    <?php if (!$messages): ?>
      <div class="account-empty">No messages yet.</div>
    <?php else: ?>
      <?php foreach ($messages as $msg): ?>
        <div class="chat-bubble chat-bubble--<?= $msg['sender'] === 'admin' ? 'client' : 'admin' ?>">
          <?= nl2br(e($msg['body'])) ?>
          <span class="chat-bubble__meta"><?= $msg['sender'] === 'admin' ? 'You' : e($client['name']) ?> · <?= e(format_date($msg['created_at'])) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <form method="post" action="/admin/client.php?id=<?= $clientId ?>" class="chat-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="send_message">
    <textarea name="body" placeholder="Reply to <?= e($client['name']) ?>…" required></textarea>
    <button type="submit" class="btn btn--primary btn--sm">Send</button>
  </form>
</div>

<div class="admin-panel">
  <h2>Wallet — Balance: <?= e(format_money($balance)) ?></h2>
  <?php if ($transactions): ?>
    <div class="admin-table-wrap" style="margin-block-end:20px;">
      <table class="admin-table">
        <thead><tr><th>Date</th><th>Type</th><th>Note</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td class="cell-muted"><?= e(format_date($tx['created_at'])) ?></td>
              <td><?= ucfirst($tx['type']) ?></td>
              <td class="cell-muted"><?= e($tx['note']) ?></td>
              <td class="<?= $tx['type'] === 'credit' ? 'money-credit' : 'money-debit' ?>"><?= $tx['type'] === 'credit' ? '+' : '-' ?><?= e(format_money((float) $tx['amount'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <form method="post" action="/admin/client.php?id=<?= $clientId ?>" class="admin-form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_wallet_tx">
    <div class="form-row">
      <label>Type</label>
      <select name="type"><option value="credit">Credit (add funds)</option><option value="debit">Debit (deduct funds)</option></select>
    </div>
    <div class="form-row"><label>Amount</label><input type="number" name="amount" step="0.01" min="0.01" required></div>
    <div class="form-row"><label>Note</label><input type="text" name="note" placeholder="Reason…"></div>
    <div class="form-row" style="align-self:end;"><button type="submit" class="btn btn--primary btn--sm">Add Transaction</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>Invoices</h2>
  <?php if ($invoices): ?>
    <div class="admin-table-wrap" style="margin-block-end:16px;">
      <table class="admin-table">
        <thead><tr><th>Number</th><th>Status</th><th>Total</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td class="cell-strong"><?= e($inv['invoice_number']) ?></td>
              <td><span class="admin-badge <?= $inv['status'] === 'paid' ? 'is-on' : 'is-off' ?>"><?= ucfirst($inv['status']) ?></span></td>
              <td class="cell-muted"><?= e(format_money((float) $inv['total'], $inv['currency'])) ?></td>
              <td><a class="admin-icon-btn" href="/admin/invoice-edit.php?id=<?= (int) $inv['id'] ?>" aria-label="Edit"><?= icon('pencil') ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <a href="/admin/invoice-edit.php?client_id=<?= $clientId ?>" class="btn btn--primary btn--sm">Create Invoice</a>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
