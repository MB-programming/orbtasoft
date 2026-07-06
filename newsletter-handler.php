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
if (!empty($_POST['company'])) {
    echo json_encode(['success' => true, 'message' => t('newsletter_success')]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => t('form_error_validation')]);
    exit;
}

$pdo = get_db();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => t('form_error')]);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:email)');
    $stmt->execute(['email' => $email]);
    echo json_encode(['success' => true, 'message' => t('newsletter_success')]);
} catch (PDOException $e) {
    error_log('Newsletter insert failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => t('form_error')]);
}
