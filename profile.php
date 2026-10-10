<?php
/**
 * Customer Profile Management
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/User.php';

Auth::requireLogin();
$user = Auth::user();
$userData = User::findById($user['id']);

// Update Personal Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    CSRF::checkPost();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $income = (float)($_POST['monthly_income'] ?? 0);

    if (empty($name) || empty($phone)) {
        setFlash('danger', 'Name and phone number cannot be blank.');
    } else {
        User::updateProfile($user['id'], [
            'name'           => $name,
            'phone'          => $phone,
            'address'        => $address,
            'occupation'     => $occupation,
            'monthly_income' => $income
        ]);
        
        // Update session name
        $_SESSION['user']['name'] = $name;
        setFlash('success', 'Profile details updated successfully.');
        redirect('profile.php');
    }
}

// Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    CSRF::checkPost();

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    $dbUser = User::findByEmail($user['email']);

    if (!password_verify($currentPass, $dbUser['password_hash'])) {
        setFlash('danger', 'Current password entered is incorrect.');
    } elseif ($newPass !== $confirmPass) {
        setFlash('danger', 'New password and confirmation do not match.');
    } elseif (strlen($newPass) < 6) {
        setFlash('danger', 'New password must be at least 6 characters long.');
    } else {
        User::updatePassword($user['id'], $newPass);
        setFlash('success', 'Password updated successfully!');
        redirect('profile.php');
    }
}

include __DIR__ . '/views/layouts/header.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
  <!-- Profile Update Card -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">My Profile Information</div>
    </div>
    <form action="profile.php" method="POST" style="padding: 24px;">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="update_profile">

      <div class="form-group">
        <label class="form-label">Account Ref Number</label>
        <input type="text" class="form-control" value="<?= e($userData['account_number']) ?>" readonly style="background:#F3F4F6;">
      </div>

      <div class="form-group">
        <label class="form-label">Email Address (Primary)</label>
        <input type="email" class="form-control" value="<?= e($userData['email']) ?>" readonly style="background:#F3F4F6;">
      </div>

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" value="<?= e($userData['name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Phone Number *</label>
        <input type="text" name="phone" class="form-control" value="<?= e($userData['phone']) ?>" required>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Occupation</label>
          <input type="text" name="occupation" class="form-control" value="<?= e($userData['occupation']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Monthly Income ($)</label>
          <input type="number" step="0.01" name="monthly_income" class="form-control" value="<?= e($userData['monthly_income']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Residential Address</label>
        <textarea name="address" class="form-control" rows="2"><?= e($userData['address']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Profile Changes</button>
    </form>
  </div>

  <!-- Security Password Card -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">Account Password Security</div>
    </div>
    <form action="profile.php" method="POST" style="padding: 24px;">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="change_password">

      <div class="form-group">
        <label class="form-label">Current Password *</label>
        <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
      </div>

      <div class="form-group">
        <label class="form-label">New Password *</label>
        <input type="password" name="new_password" class="form-control" required placeholder="••••••••">
      </div>

      <div class="form-group">
        <label class="form-label">Confirm New Password *</label>
        <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-dark" style="width: 100%; justify-content: center; margin-top: 20px;">Update Security Password</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
