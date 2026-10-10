<?php
/**
 * Customer Directory & Search Management
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Loan.php';

Auth::requireRole(['admin', 'officer']);

$searchQuery = $_GET['search'] ?? '';

// Handle Direct Customer Creation by Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer') {
    CSRF::checkPost();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $income = (float)($_POST['monthly_income'] ?? 0);
    $password = $_POST['password'] ?? 'Customer@123';

    if (empty($name) || empty($email) || empty($phone)) {
        setFlash('danger', 'Name, email, and phone number are required.');
    } elseif (User::findByEmail($email)) {
        setFlash('danger', 'Customer email already registered.');
    } else {
        try {
            User::create([
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
            setFlash('success', 'New customer profile registered successfully!');
            redirect('customers.php');
        } catch (Exception $e) {
            setFlash('danger', 'Failed to add customer: ' . $e->getMessage());
        }
    }
}

$customers = User::getAllCustomers($searchQuery);

include __DIR__ . '/views/layouts/header.php';
?>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Customer Directory & KYC Information</div>
    <button class="btn btn-primary" onclick="openModal('addCustomerModal')">+ Register New Customer</button>
  </div>

  <div style="padding: 16px 24px; background: #FAFAFA; border-bottom: 1px solid var(--border-light);">
    <form action="customers.php" method="GET" style="display:flex; gap:12px; align-items:center;">
      <input type="text" name="search" data-table-search="customersTable" class="form-control" placeholder="🔍 Search customer name, email, account no, ID..." value="<?= e($searchQuery) ?>" style="max-width: 380px;">
      <button type="submit" class="btn btn-outline btn-sm">Search</button>
      <?php if (!empty($searchQuery)): ?>
        <a href="customers.php" class="btn btn-outline btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div style="overflow-x: auto;">
    <table class="data-table" id="customersTable">
      <thead>
        <tr>
          <th>Account No</th>
          <th>Customer Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>National ID</th>
          <th>Status</th>
          <th>Registered Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr><td colspan="8" style="text-align:center; padding:30px; color:#6B7280;">No customer records matching search criteria.</td></tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td><strong style="color:var(--dark-black);"><?= e($c['account_number']) ?></strong></td>
              <td><strong><?= e($c['name']) ?></strong></td>
              <td><?= e($c['email']) ?></td>
              <td><?= e($c['phone'] ?? 'N/A') ?></td>
              <td><?= e($c['national_id'] ?? 'N/A') ?></td>
              <td><span class="badge badge-paid"><?= strtoupper(e($c['status'])) ?></span></td>
              <td><?= formatDate($c['created_at']) ?></td>
              <td>
                <a href="loans.php?action=apply&user_id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">+ Issue Loan</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 600px;">
    <div class="modal-header">
      <div class="modal-title">Register New Customer Profile</div>
      <button class="modal-close" onclick="closeModal('addCustomerModal')">&times;</button>
    </div>
    <form action="customers.php" method="POST" class="modal-body">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="add_customer">

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Michael Scott">
        </div>

        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="michael@example.com">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Phone Number *</label>
          <input type="text" name="phone" class="form-control" required placeholder="+1 555-0182">
        </div>

        <div class="form-group">
          <label class="form-label">National ID / SSN</label>
          <input type="text" name="national_id" class="form-control" placeholder="ID-882200">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Occupation</label>
          <input type="text" name="occupation" class="form-control" placeholder="Business Owner">
        </div>

        <div class="form-group">
          <label class="form-label">Monthly Income ($)</label>
          <input type="number" step="0.01" name="monthly_income" class="form-control" placeholder="5000.00">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Temporary Initial Password</label>
        <input type="password" name="password" class="form-control" value="Customer@123" required>
        <small style="color:#6B7280;">Default initial password: <code>Customer@123</code></small>
      </div>

      <div class="form-group">
        <label class="form-label">Full Address</label>
        <textarea name="address" class="form-control" rows="2" placeholder="Street Address, City"></textarea>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
        <button type="button" class="btn btn-outline" onclick="closeModal('addCustomerModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Customer Account</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
