<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/register.php');
    exit;
}

$lang = current_lang();
$redirectBase = '/pages/register.php?lang=' . urlencode($lang);

if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    header('Location: ' . $redirectBase . '&error=generic');
    exit;
}

// Honeypot
if (!empty($_POST['website'])) {
    header('Location: /index.php?lang=' . urlencode($lang));
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['password_confirm'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    header('Location: ' . $redirectBase . '&error=fields');
    exit;
}

if (!hash_equals($password, $confirm)) {
    header('Location: ' . $redirectBase . '&error=password_mismatch');
    exit;
}

$pdo = get_db();
if (!$pdo) {
    header('Location: ' . $redirectBase . '&error=generic');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        header('Location: ' . $redirectBase . '&error=email_taken');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    session_regenerate_id(true);
    header('Location: /account/index.php?lang=' . urlencode($lang) . '&welcome=1');
    exit;
} catch (PDOException $e) {
    error_log('Register failed: ' . $e->getMessage());
    header('Location: ' . $redirectBase . '&error=generic');
    exit;
}
