<?php
/**
 * Role-Based Dashboard View
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/Report.php';
require_once __DIR__ . '/models/Loan.php';
require_once __DIR__ . '/models/Payment.php';

Auth::requireLogin();
$user = Auth::user();
$role = Auth::role();

// Load Metrics
if ($role === 'admin' || $role === 'officer') {
    $overview = Report::getOverview();
    $recentLoans = Loan::getAll('', '');
    $recentPayments = Payment::getAll('');
} else {
    // Customer Metrics
    $myLoans = Loan::getByUser($user['id']);
    $myPayments = Payment::getByUser($user['id']);

    $totalBorrowed = 0;
    $totalOutstanding = 0;
    $activeCount = 0;

    foreach ($myLoans as $l) {
        $totalBorrowed += (float)$l['amount'];
        $totalOutstanding += (float)$l['outstanding_balance'];
        if (in_array($l['status'], ['approved', 'disbursed', 'pending'])) {
            $activeCount++;
        }
    }

    $totalPaid = 0;
    foreach ($myPayments as $p) {
        $totalPaid += (float)$p['amount'];
    }
}

include __DIR__ . '/views/layouts/header.php';
?>

<!-- Header Welcome Banner -->
<div style="margin-bottom: 28px; background: var(--dark-black); color: #FFF; padding: 28px; border-radius: var(--radius-md); border-left: 6px solid var(--primary-red); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 4px;">Welcome back, <?= e($user['name']) ?>! 👋</h1>
    <p style="color: #9CA3AF; font-size: 14px;">Role: <strong style="color:#DC2626; text-transform:uppercase;"><?= e($role) ?></strong> | Account: <strong><?= e($user['account_number']) ?></strong></p>
  </div>
  <div>
    <?php if ($role === 'customer'): ?>
      <a href="loans.php?action=apply" class="btn btn-primary">+ Apply For Loan</a>
    <?php else: ?>
      <a href="payments.php" class="btn btn-primary">+ Record Payment</a>
      <a href="reports.php" class="btn btn-dark" style="margin-left: 8px;">📊 Financial Reports</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($role === 'admin' || $role === 'officer'): ?>
  <!-- Staff / Admin Metric Cards -->
  <div class="card-grid">
    <div class="metric-card">
      <div class="metric-label">Total Customers</div>
      <div class="metric-value"><?= number_format($overview['total_customers']) ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Pending Approval</div>
      <div class="metric-value" style="color:#D97706;"><?= number_format($overview['pending_loans']) ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Total Disbursed</div>
      <div class="metric-value"><?= formatMoney($overview['total_disbursed']) ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Total Collected</div>
      <div class="metric-value" style="color:#059669;"><?= formatMoney($overview['total_collected']) ?></div>
    </div>

    <div class="metric-card dark">
      <div class="metric-label">Outstanding Balance</div>
      <div class="metric-value"><?= formatMoney($overview['total_outstanding']) ?></div>
    </div>
  </div>

  <!-- Recent Loan Applications Panel -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">Recent Loan Applications</div>
      <a href="loans.php" class="btn btn-outline btn-sm">View All Applications &rarr;</a>
    </div>
    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Loan Ref</th>
            <th>Customer</th>
            <th>Package</th>
            <th>Amount</th>
            <th>Term</th>
            <th>Status</th>
            <th>Applied Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentLoans)): ?>
            <tr><td colspan="8" style="text-align:center; padding:20px; color:#6B7280;">No loan records found.</td></tr>
          <?php else: ?>
            <?php foreach (array_slice($recentLoans, 0, 5) as $loan): ?>
              <tr>
                <td><strong><?= e($loan['loan_number']) ?></strong></td>
                <td>
                  <strong><?= e($loan['customer_name']) ?></strong><br>
                  <small style="color:#6B7280;"><?= e($loan['account_number']) ?></small>
                </td>
                <td><?= e($loan['package_name']) ?></td>
                <td><strong><?= formatMoney($loan['amount']) ?></strong></td>
                <td><?= e($loan['term_months']) ?> Months</td>
                <td>
                  <span class="badge badge-<?= e($loan['status']) ?>"><?= e($loan['status']) ?></span>
                </td>
                <td><?= formatDate($loan['applied_at']) ?></td>
                <td>
                  <a href="loans.php?view=<?= $loan['id'] ?>" class="btn btn-outline btn-sm">Review</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>
  <!-- Customer Dashboard View -->
  <div class="card-grid">
    <div class="metric-card">
      <div class="metric-label">Active / Pending Loans</div>
      <div class="metric-value"><?= $activeCount ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Total Principal Borrowed</div>
      <div class="metric-value"><?= formatMoney($totalBorrowed) ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Total Paid So Far</div>
      <div class="metric-value" style="color:#059669;"><?= formatMoney($totalPaid) ?></div>
    </div>

    <div class="metric-card dark">
      <div class="metric-label">Current Outstanding Balance</div>
      <div class="metric-value"><?= formatMoney($totalOutstanding) ?></div>
    </div>
  </div>

  <!-- Customer Loan Summary -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">My Loan Applications & Schedule</div>
      <a href="loans.php?action=apply" class="btn btn-primary btn-sm">+ Apply For New Loan</a>
    </div>
    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Loan Ref</th>
            <th>Package</th>
            <th>Amount</th>
            <th>Monthly Installment</th>
            <th>Outstanding</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($myLoans)): ?>
            <tr><td colspan="7" style="text-align:center; padding:30px; color:#6B7280;">You have no active or previous loan applications.</td></tr>
          <?php else: ?>
            <?php foreach ($myLoans as $loan): ?>
              <tr>
                <td><strong><?= e($loan['loan_number']) ?></strong></td>
                <td><?= e($loan['package_name']) ?></td>
                <td><strong><?= formatMoney($loan['amount']) ?></strong></td>
                <td><?= formatMoney($loan['monthly_installment']) ?>/mo</td>
                <td style="color:#DC2626; font-weight:700;"><?= formatMoney($loan['outstanding_balance']) ?></td>
                <td><span class="badge badge-<?= e($loan['status']) ?>"><?= e($loan['status']) ?></span></td>
                <td>
                  <a href="loans.php?view=<?= $loan['id'] ?>" class="btn btn-outline btn-sm">Schedule & Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
