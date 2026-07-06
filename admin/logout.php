<?php
require __DIR__ . '/includes/auth.php';

unset($_SESSION['admin_id']);
session_regenerate_id(true);
header('Location: /admin/login.php');
exit;
