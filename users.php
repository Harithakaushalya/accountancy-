<?php
/**
 * System Staff & User Management (Admin Only)
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/User.php';

Auth::requireRole(['admin']);

// Create Staff User (Officer or Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    CSRF::checkPost();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'officer');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        setFlash('danger', 'Name, email, and password are required.');
    } elseif (User::findByEmail($email)) {
        setFlash('danger', 'Email address is already registered in the system.');
    } else {
        try {
            User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
                'role'     => $role,
                'phone'    => $phone,
                'status'   => 'active'
            ]);
            setFlash('success', "New staff member ({$name}) registered as {$role}.");
            redirect('users.php');
        } catch (Exception $e) {
            setFlash('danger', $e->getMessage());
        }
    }
}

$db = DatabaseConnection::getInstance();
$allUsers = $db->query("SELECT id, account_number, name, email, role, phone, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/views/layouts/header.php';
?>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">System Staff & Role-Based User Registry</div>
    <button class="btn btn-primary" onclick="openModal('addStaffModal')">+ Register Staff Member</button>
  </div>

  <div style="padding: 16px 24px; background: #FAFAFA; border-bottom: 1px solid var(--border-light);">
    <input type="text" data-table-search="usersTable" class="form-control" placeholder="🔍 Instant search staff/user by name, email, account no..." style="max-width: 380px;">
  </div>

  <div style="overflow-x: auto;">
    <table class="data-table" id="usersTable">
      <thead>
        <tr>
          <th>Account Ref</th>
          <th>Name</th>
          <th>Email Address</th>
          <th>System Role</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Created Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allUsers as $u): ?>
          <tr>
            <td><strong><?= e($u['account_number']) ?></strong></td>
            <td><strong><?= e($u['name']) ?></strong></td>
            <td><?= e($u['email']) ?></td>
            <td>
              <span class="badge badge-<?= match($u['role']) { 'admin' => 'overdue', 'officer' => 'disbursed', default => 'paid' } ?>">
                <?= strtoupper(e($u['role'])) ?>
              </span>
            </td>
            <td><?= e($u['phone'] ?? 'N/A') ?></td>
            <td><span class="badge badge-paid"><?= strtoupper(e($u['status'])) ?></span></td>
            <td><?= formatDate($u['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Staff Modal -->
<div id="addStaffModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Register System Staff / Officer</div>
      <button class="modal-close" onclick="closeModal('addStaffModal')">&times;</button>
    </div>
    <form action="users.php" method="POST" class="modal-body">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="add_staff">

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Sarah Jenkins">
      </div>

      <div class="form-group">
        <label class="form-label">Corporate Email Address *</label>
        <input type="email" name="email" class="form-control" required placeholder="officer@apexfinance.com">
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Assign Role *</label>
          <select name="role" class="form-control" required>
            <option value="officer">Loan Officer</option>
            <option value="admin">System Administrator</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="+1 555-0192">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Access Password *</label>
        <input type="password" name="password" class="form-control" required placeholder="••••••••">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
        <button type="button" class="btn btn-outline" onclick="closeModal('addStaffModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Staff Account</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
