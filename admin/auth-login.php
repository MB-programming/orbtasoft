<?php
require __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    header('Location: /admin/login.php?error=generic');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: /admin/login.php?error=fields');
    exit;
}

$pdo = get_db();
if (!$pdo) {
    header('Location: /admin/login.php?error=generic');
    exit;
}

$stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = :username');
$stmt->execute(['username' => $username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    header('Location: /admin/login.php?error=bad_login');
    exit;
}

$_SESSION['admin_id'] = (int) $admin['id'];
session_regenerate_id(true);
header('Location: /admin/index.php');
exit;
