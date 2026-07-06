<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../includes/mailer.php';
admin_require_login();
$pdo = get_db();

$settingKeys = [
    'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
    'smtp_from_email', 'smtp_from_name',
    'notify_email', 'notify_on_contact', 'notify_on_newsletter',
    'contact_email_subject', 'contact_email_body',
    'newsletter_email_subject', 'newsletter_email_body',
];

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed, please try again.';
        $flashType = 'error';
    } elseif (($_POST['action'] ?? '') === 'save_settings') {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2');
        foreach ($settingKeys as $key) {
            if (in_array($key, ['notify_on_contact', 'notify_on_newsletter'], true)) {
                $value = isset($_POST[$key]) ? '1' : '0';
            } else {
                $value = trim($_POST[$key] ?? '');
            }
            $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
        }
        $flash = 'Settings saved.';
    } elseif (($_POST['action'] ?? '') === 'send_test') {
        $testTo = trim($_POST['test_email'] ?? '');
        if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $flash = 'Enter a valid email address to send the test to.';
            $flashType = 'error';
        } else {
            $s = get_settings();
            $ok = send_smtp_mail([
                'host' => $s['smtp_host'] ?? '',
                'port' => (int) ($s['smtp_port'] ?? 587),
                'encryption' => $s['smtp_encryption'] ?? 'tls',
                'username' => $s['smtp_username'] ?? '',
                'password' => $s['smtp_password'] ?? '',
                'from_email' => $s['smtp_from_email'] ?? '',
                'from_name' => $s['smtp_from_name'] ?? '',
            ], $testTo, 'Orbtasoft test email', "This is a test email from your Orbtasoft admin dashboard.\n\nIf you received this, your SMTP settings are working correctly.");
            $flash = $ok ? "Test email sent to {$testTo}." : 'Could not send the test email — check the server error log and your SMTP settings.';
            $flashType = $ok ? 'success' : 'error';
        }
    }
}

$settings = get_settings();
function sv(array $s, string $key, string $default = ''): string
{
    return $s[$key] ?? $default;
}

$current_admin_page = 'settings';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-page-head">
  <div>
    <h1>Settings</h1>
    <p>SMTP configuration, submission notifications and email templates.</p>
  </div>
</div>

<?php if ($flash): ?><div class="admin-flash is-<?= $flashType ?>"><?= e($flash) ?></div><?php endif; ?>

<form method="post" action="/admin/settings.php">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="save_settings">

  <div class="admin-panel">
    <h2>SMTP Configuration</h2>
    <div class="admin-form-grid">
      <div class="form-row"><label for="smtp_host">SMTP Host</label><input type="text" id="smtp_host" name="smtp_host" placeholder="smtp.mailprovider.com" value="<?= e(sv($settings, 'smtp_host')) ?>"></div>
      <div class="form-row"><label for="smtp_port">Port</label><input type="number" id="smtp_port" name="smtp_port" value="<?= e(sv($settings, 'smtp_port', '587')) ?>"></div>
      <div class="form-row">
        <label for="smtp_encryption">Encryption</label>
        <select id="smtp_encryption" name="smtp_encryption">
          <option value="tls" <?= sv($settings, 'smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (587)</option>
          <option value="ssl" <?= sv($settings, 'smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL / Implicit TLS (465)</option>
          <option value="none" <?= sv($settings, 'smtp_encryption') === 'none' ? 'selected' : '' ?>>None</option>
        </select>
      </div>

      <div class="form-row"><label for="smtp_username">Username</label><input type="text" id="smtp_username" name="smtp_username" autocomplete="off" value="<?= e(sv($settings, 'smtp_username')) ?>"></div>
      <div class="form-row"><label for="smtp_password">Password</label><input type="password" id="smtp_password" name="smtp_password" autocomplete="new-password" value="<?= e(sv($settings, 'smtp_password')) ?>"></div>
      <div></div>

      <div class="form-row"><label for="smtp_from_email">From Email</label><input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= e(sv($settings, 'smtp_from_email')) ?>"></div>
      <div class="form-row"><label for="smtp_from_name">From Name</label><input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= e(sv($settings, 'smtp_from_name', 'Orbtasoft')) ?>"></div>
      <div></div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Submission Notifications</h2>
    <div class="admin-form-grid">
      <div class="form-row span-3"><label for="notify_email">Notify Email (where alerts are sent)</label><input type="email" id="notify_email" name="notify_email" value="<?= e(sv($settings, 'notify_email')) ?>"></div>
      <div class="form-row">
        <label><input type="checkbox" name="notify_on_contact" <?= sv($settings, 'notify_on_contact') === '1' ? 'checked' : '' ?>> Email me on new contact form submissions</label>
      </div>
      <div class="form-row">
        <label><input type="checkbox" name="notify_on_newsletter" <?= sv($settings, 'notify_on_newsletter') === '1' ? 'checked' : '' ?>> Email me on new newsletter subscribers</label>
      </div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Contact Notification Email</h2>
    <p style="color:var(--color-muted); font-size:.85rem; margin-block-end:16px;">Available placeholders: <code>{{name}}</code>, <code>{{email}}</code>, <code>{{message}}</code></p>
    <div class="admin-form-grid">
      <div class="form-row span-3"><label for="contact_email_subject">Subject</label><input type="text" id="contact_email_subject" name="contact_email_subject" value="<?= e(sv($settings, 'contact_email_subject')) ?>"></div>
      <div class="form-row span-3"><label for="contact_email_body">Body</label><textarea id="contact_email_body" name="contact_email_body" rows="5"><?= e(sv($settings, 'contact_email_body')) ?></textarea></div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Newsletter Notification Email</h2>
    <p style="color:var(--color-muted); font-size:.85rem; margin-block-end:16px;">Available placeholder: <code>{{email}}</code></p>
    <div class="admin-form-grid">
      <div class="form-row span-3"><label for="newsletter_email_subject">Subject</label><input type="text" id="newsletter_email_subject" name="newsletter_email_subject" value="<?= e(sv($settings, 'newsletter_email_subject')) ?>"></div>
      <div class="form-row span-3"><label for="newsletter_email_body">Body</label><textarea id="newsletter_email_body" name="newsletter_email_body" rows="4"><?= e(sv($settings, 'newsletter_email_body')) ?></textarea></div>
    </div>
  </div>

  <div class="admin-form-actions" style="margin-block-end:28px;">
    <button type="submit" class="btn btn--primary btn--sm">Save Settings</button>
  </div>
</form>

<div class="admin-panel">
  <h2>Send a Test Email</h2>
  <p style="color:var(--color-muted); font-size:.85rem; margin-block-end:16px;">Uses the SMTP settings currently saved above — save first if you just changed them.</p>
  <form method="post" action="/admin/settings.php" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="send_test">
    <div class="form-row" style="flex:1; min-width:240px;">
      <label for="test_email">Send test to</label>
      <input type="email" id="test_email" name="test_email" placeholder="you@example.com" required>
    </div>
    <button type="submit" class="btn btn--outline btn--sm">Send Test Email</button>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
