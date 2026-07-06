<?php
require __DIR__ . '/includes/auth.php';

if (admin_current_user()) {
    header('Location: /admin/index.php');
    exit;
}

$errorMap = [
    'fields'    => 'Please enter your username and password.',
    'bad_login' => 'Incorrect username or password.',
    'generic'   => 'Something went wrong, please try again.',
];
$errorCode = $_GET['error'] ?? '';
$errorMsg = $errorMap[$errorCode] ?? null;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Orbtasoft</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="is-ltr admin-body">

<div class="admin-login">
  <div class="admin-login__card">
    <div class="admin-login__brand"><?= icon('brand-mark') ?> <span>Orbtasoft Admin</span></div>
    <h1>Sign in to the dashboard</h1>

    <?php if ($errorMsg): ?>
      <div class="form-status is-error" style="display:block; margin-block-end:20px;"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/auth-login.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="form-row">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username" autofocus>
      </div>
      <div class="form-row">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn--primary" style="width:100%;">Sign In</button>
    </form>

    <p class="admin-login__back"><a href="/index.php">&larr; Back to the website</a></p>
  </div>
</div>

</body>
</html>
