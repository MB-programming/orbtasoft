<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/mailer.php';

$lang = current_lang();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /forgot-password.php?lang=' . urlencode($lang));
    exit;
}

// Always land on the same "check your inbox" screen — never reveal whether the email exists.
$redirect = '/forgot-password.php?lang=' . urlencode($lang) . '&sent=1';

if (!csrf_valid($_POST['csrf_token'] ?? '') || !empty($_POST['website'])) {
    header('Location: ' . $redirect);
    exit;
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $redirect);
    exit;
}

$pdo = get_db();
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:uid, :hash, :exp)')
            ->execute(['uid' => $user['id'], 'hash' => $tokenHash, 'exp' => $expiresAt]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $resetLink = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/reset-password.php?token=' . $token . '&lang=' . urlencode($lang);

        $subject = t('auth_email_reset_subject');
        $body = strtr(t('auth_email_reset_body'), ['{{name}}' => $user['name'], '{{reset_link}}' => $resetLink]);

        try {
            send_user_mail($email, $subject, $body);
        } catch (Throwable $e) {
            error_log('Password reset email failed: ' . $e->getMessage());
        }
    }
}

header('Location: ' . $redirect);
