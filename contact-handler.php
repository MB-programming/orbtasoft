<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => t('form_error')]);
    exit;
}

// Honeypot: bots fill hidden fields, humans don't.
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => t('form_success')]);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => t('form_error_validation')]);
    exit;
}

$pdo = get_db();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => t('form_error')]);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)');
    $stmt->execute(['name' => $name, 'email' => $email, 'message' => $message]);
    echo json_encode(['success' => true, 'message' => t('form_success')]);
} catch (PDOException $e) {
    error_log('Contact insert failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => t('form_error')]);
}
