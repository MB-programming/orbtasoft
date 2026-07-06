<?php
require __DIR__ . '/includes/functions.php';

$lang = current_lang();
unset($_SESSION['user_id']);
session_regenerate_id(true);
header('Location: /index.php?lang=' . urlencode($lang));
exit;
