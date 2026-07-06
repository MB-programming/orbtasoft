<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/login.php');
    exit;
}

$lang = current_lang();
$redirectBase = '/pages/login.php?lang=' . urlencode($lang);

if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    header('Location: ' . $redirectBase . '&error=generic');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: ' . $redirectBase . '&error=fields');
    exit;
}

$pdo = get_db();
if (!$pdo) {
    header('Location: ' . $redirectBase . '&error=generic');
    exit;
}

$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: ' . $redirectBase . '&error=bad_login');
    exit;
}

$_SESSION['user_id'] = (int) $user['id'];
session_regenerate_id(true);
header('Location: /index.php?lang=' . urlencode($lang) . '&welcome=1');
exit;
