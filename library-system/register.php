<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!$full_name || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = $db->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt2->bind_param('sss', $full_name, $email, $hashed);
            if ($stmt2->execute()) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — BookWorms</title>
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

    <h2>Create Account</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success">✓ <?= sanitize($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control"
               placeholder="Jodel Solteo"
               value="<?= sanitize($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control"
               placeholder="j@gmail.com"
               value="<?= sanitize($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="Min. 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control"
               placeholder="Repeat your password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:.5rem">
        Create Account →
      </button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>
