<?php

require_once 'includes/config.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email']= $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — BookWorms</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-icon">BW</span>
      <h1>BookWorms</h1>
      <p>Library Management System</p>
    </div>

    <h2>Welcome back</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control"
               placeholder="your@email.com"
               value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="Your password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:.5rem">
        Sign In →
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php">Register</a>
    </div>
  </div>
</div>
</body>
</html>
