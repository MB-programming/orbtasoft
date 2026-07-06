<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

function admin_current_user(): ?array
{
    static $admin = false;
    if ($admin !== false) {
        return $admin;
    }

    $admin = null;
    if (!empty($_SESSION['admin_id'])) {
        $pdo = get_db();
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT id, username FROM admin_users WHERE id = :id');
            $stmt->execute(['id' => $_SESSION['admin_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $admin = $row;
            }
        }
    }
    return $admin;
}

function admin_require_login(): array
{
    $admin = admin_current_user();
    if (!$admin) {
        header('Location: /admin/login.php');
        exit;
    }
    return $admin;
}
