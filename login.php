<?php
/**
 * Authentication Login Page
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';

// Redirect if already authenticated
if (Auth::check()) {
    redirect('dashboard.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkPost();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('danger', 'Please provide both email address and password.');
    } else {
        if (Auth::login($email, $password)) {
            setFlash('success', 'Welcome back! You have logged in successfully.');
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">Apex<span>Finance</span></div>
      <p style="color:#6B7280; font-size:14px; margin-top:4px;">Smart Loan & Banking System</p>
    </div>

    <?php if (hasFlash('danger')): ?>
      <div class="alert alert-danger"><?= getFlash('danger') ?></div>
    <?php endif; ?>
    <?php if (hasFlash('success')): ?>
      <div class="alert alert-success"><?= getFlash('success') ?></div>
    <?php endif; ?>
    <?php if (hasFlash('warning')): ?>
      <div class="alert alert-warning"><?= getFlash('warning') ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <?= CSRF::field() ?>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="admin@apexfinance.com" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
        Sign In to Portal
      </button>
    </form>

    <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #6B7280;">
      Don't have a customer account? <a href="register.php" style="color:#DC2626; font-weight:700; text-decoration:none;">Register Here</a>
    </div>

    <div style="margin-top: 20px; padding: 12px; background: #F9FAFB; border-radius: 6px; font-size: 12px; color: #4B5563; border: 1px dashed #E5E7EB;">
      <strong>System Test Credentials:</strong><br>
      • Admin: <code>admin@apexfinance.com</code> / <code>Admin@123</code><br>
      • Officer: <code>officer@apexfinance.com</code> / <code>Officer@123</code><br>
      • Customer: <code>customer@apexfinance.com</code> / <code>Customer@123</code>
    </div>
  </div>
</body>
</html>
