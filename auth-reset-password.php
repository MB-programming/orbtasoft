<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';

$lang = current_lang();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /forgot-password.php?lang=' . urlencode($lang));
    exit;
}

$token = trim($_POST['token'] ?? '');
$redirectBase = '/reset-password.php?token=' . urlencode($token) . '&lang=' . urlencode($lang);

if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    header('Location: ' . $redirectBase . '&error=invalid');
    exit;
}

$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['password_confirm'] ?? '');

if (strlen($password) < 8) {
    header('Location: ' . $redirectBase . '&error=fields');
    exit;
}
if (!hash_equals($password, $confirm)) {
    header('Location: ' . $redirectBase . '&error=password_mismatch');
    exit;
}

$pdo = get_db();
if (!$pdo || $token === '') {
    header('Location: /forgot-password.php?lang=' . urlencode($lang));
    exit;
}

$tokenHash = hash('sha256', $token);
$stmt = $pdo->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = :hash AND used = 0 AND expires_at > NOW() LIMIT 1');
$stmt->execute(['hash' => $tokenHash]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    header('Location: /reset-password.php?token=' . urlencode($token) . '&lang=' . urlencode($lang));
    exit;
}

$pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
    ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $reset['user_id']]);

// Invalidate every outstanding reset token for this user, not just the one used.
$pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = :uid')->execute(['uid' => $reset['user_id']]);

header('Location: /login.php?lang=' . urlencode($lang) . '&reset=1');
