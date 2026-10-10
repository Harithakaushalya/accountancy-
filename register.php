<?php
/**
 * Customer Self-Registration Page
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/User.php';

if (Auth::check()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkPost();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $income = (float)($_POST['monthly_income'] ?? 0);
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        setFlash('danger', 'Please fill in all required fields.');
    } elseif ($password !== $passwordConfirm) {
        setFlash('danger', 'Password and confirmation do not match.');
    } elseif (strlen($password) < 6) {
        setFlash('danger', 'Password must be at least 6 characters long.');
    } elseif (User::findByEmail($email)) {
        setFlash('danger', 'An account with this email address already exists.');
    } else {
        try {
            $userId = User::create([
                'name'           => $name,
                'email'          => $email,
                'password'       => $password,
                'role'           => 'customer',
                'phone'          => $phone,
                'national_id'    => $nationalId,
                'address'        => $address,
                'occupation'     => $occupation,
                'monthly_income' => $income,
                'status'         => 'active'
            ]);

            setFlash('success', 'Registration successful! You may now sign in to apply for loans.');
            redirect('login.php');
        } catch (Exception $e) {
            setFlash('danger', 'Registration failed: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Registration - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="auth-wrapper" style="padding: 40px 20px;">
  <div class="auth-card" style="max-width: 600px;">
    <div class="auth-header">
      <div class="auth-logo">Apex<span>Finance</span></div>
      <p style="color:#6B7280; font-size:14px; margin-top:4px;">Create Customer Banking Account</p>
    </div>

    <?php if (hasFlash('danger')): ?>
      <div class="alert alert-danger"><?= getFlash('danger') ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
      <?= CSRF::field() ?>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
        </div>

        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="john@example.com">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Phone Number *</label>
          <input type="text" name="phone" class="form-control" required placeholder="+1 555-0199">
        </div>

        <div class="form-group">
          <label class="form-label">National ID / Passport No</label>
          <input type="text" name="national_id" class="form-control" placeholder="ID-992011">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Occupation</label>
          <input type="text" name="occupation" class="form-control" placeholder="Software Engineer">
        </div>

        <div class="form-group">
          <label class="form-label">Estimated Monthly Income ($)</label>
          <input type="number" step="0.01" name="monthly_income" class="form-control" placeholder="4500.00">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Residential Address</label>
        <textarea name="address" class="form-control" rows="2" placeholder="123 Financial Avenue, City"></textarea>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="password_confirm" class="form-control" required placeholder="••••••••">
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
        Complete Account Registration
      </button>
    </form>

    <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #6B7280;">
      Already registered? <a href="login.php" style="color:#DC2626; font-weight:700; text-decoration:none;">Sign In Here</a>
    </div>
  </div>
</body>
</html>
