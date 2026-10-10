<?php
/**
 * Loan Management, Applications & Approval Workflow
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/Loan.php';
require_once __DIR__ . '/models/User.php';

Auth::requireLogin();
$user = Auth::user();
$role = Auth::role();

// Handle New Application Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_loan') {
    CSRF::checkPost();

    $packageId = (int)($_POST['package_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $termMonths = (int)($_POST['term_months'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');
    
    // Target customer ID (if officer applying on behalf of customer)
    $targetUserId = ($role !== 'customer' && !empty($_POST['target_user_id'])) ? (int)$_POST['target_user_id'] : $user['id'];

    try {
        $loanNo = Loan::apply($targetUserId, $packageId, $amount, $termMonths, $purpose);
        setFlash('success', "Loan application {$loanNo} submitted successfully!");
        redirect('loans.php');
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }
}

// Handle Loan Review (Approve / Reject / Disburse)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review_loan') {
    CSRF::checkPost();
    Auth::requireRole(['admin', 'officer']);

    $loanId = (int)($_POST['loan_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['decision_notes'] ?? '');

    try {
        Loan::reviewLoan($loanId, $status, $user['id'], $notes);
        setFlash('success', "Loan ID {$loanId} status updated to " . strtoupper($status) . ".");
        redirect("loans.php?view={$loanId}");
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }
}

// Filter or View Single Loan
$viewLoanId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$statusFilter = $_GET['status'] ?? '';
$searchFilter = $_GET['search'] ?? '';

$packages = Loan::getPackages();

if ($viewLoanId > 0) {
    $loanDetail = Loan::getById($viewLoanId);
    
    // RBAC check: Customers can only view their own loans
    if ($role === 'customer' && $loanDetail && (int)$loanDetail['user_id'] !== (int)$user['id']) {
        setFlash('danger', 'Unauthorized to view this loan file.');
        redirect('loans.php');
    }
    
    if ($loanDetail) {
        $installments = Loan::getInstallments($viewLoanId);
    }
} else {
    if ($role === 'customer') {
        $loansList = Loan::getByUser($user['id']);
    } else {
        $loansList = Loan::getAll($statusFilter, $searchFilter);
        $customersList = User::getAllCustomers();
    }
}

include __DIR__ . '/views/layouts/header.php';
?>

<?php if ($viewLoanId > 0 && isset($loanDetail)): ?>
  <!-- Single Loan Detail & Installment Schedule View -->
  <div style="margin-bottom: 20px;">
    <a href="loans.php" class="btn btn-outline">&larr; Back to Loans List</a>
  </div>

  <div class="panel">
    <div class="panel-header" style="background: var(--dark-black); color: #FFF;">
      <div>
        <div class="panel-title" style="color: #FFF; font-size: 20px;">
          Loan Reference: <?= e($loanDetail['loan_number']) ?>
        </div>
        <span class="badge badge-<?= e($loanDetail['status']) ?>" style="margin-top: 6px;">
          Status: <?= strtoupper(e($loanDetail['status'])) ?>
        </span>
      </div>
      
      <?php if (($role === 'officer' || $role === 'admin') && in_array($loanDetail['status'], ['pending', 'approved'])): ?>
        <button class="btn btn-primary btn-sm" onclick="openModal('reviewModal')">Review / Approve Loan</button>
      <?php endif; ?>
    </div>

    <div style="padding: 24px;">
      <div class="card-grid" style="margin-bottom: 20px;">
        <div class="metric-card">
          <div class="metric-label">Customer Name</div>
          <div class="metric-value" style="font-size: 18px;"><?= e($loanDetail['customer_name']) ?></div>
          <small style="color: #6B7280;"><?= e($loanDetail['account_number']) ?> | <?= e($loanDetail['phone'] ?? 'N/A') ?></small>
        </div>

        <div class="metric-card">
          <div class="metric-label">Principal Amount</div>
          <div class="metric-value"><?= formatMoney($loanDetail['amount']) ?></div>
          <small style="color: #6B7280;">Rate: <?= e($loanDetail['interest_rate']) ?>% p.a. (<?= e($loanDetail['term_months']) ?> Months)</small>
        </div>

        <div class="metric-card">
          <div class="metric-label">Monthly EMI</div>
          <div class="metric-value"><?= formatMoney($loanDetail['monthly_installment']) ?></div>
          <small style="color: #6B7280;">Total Payable: <?= formatMoney($loanDetail['total_payable']) ?></small>
        </div>

        <div class="metric-card dark">
          <div class="metric-label">Remaining Outstanding</div>
          <div class="metric-value"><?= formatMoney($loanDetail['outstanding_balance']) ?></div>
          <small style="color: #9CA3AF;">Total Interest: <?= formatMoney($loanDetail['total_interest']) ?></small>
        </div>
      </div>

      <div style="padding: 16px; background: #F9FAFB; border-radius: 8px; margin-bottom: 24px; border: 1px solid #E5E7EB;">
        <strong style="color: var(--dark-black);">Loan Purpose / Notes:</strong>
        <p style="margin-top: 4px; color: #4B5563; font-size: 14px;"><?= e($loanDetail['purpose']) ?></p>
        
        <?php if (!empty($loanDetail['decision_notes'])): ?>
          <hr style="margin: 12px 0; border: none; border-top: 1px solid #E5E7EB;">
          <strong style="color: var(--primary-red);">Officer Decision Notes:</strong>
          <p style="margin-top: 4px; color: #1F2937; font-size: 14px;"><?= e($loanDetail['decision_notes']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Installment Repayment Schedule -->
      <h3 style="margin-bottom: 14px; font-size: 18px; font-weight: 700; color: var(--dark-black);">Repayment Schedule</h3>
      <div style="overflow-x: auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Due Date</th>
              <th>Installment Amount</th>
              <th>Principal Portion</th>
              <th>Interest Portion</th>
              <th>Paid Amount</th>
              <th>Status</th>
              <?php if ($role === 'officer' || $role === 'admin'): ?>
                <th>Action</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($installments)): ?>
              <tr><td colspan="8" style="text-align:center; padding:20px; color:#6B7280;">Installment schedule will be generated once loan is approved.</td></tr>
            <?php else: ?>
              <?php foreach ($installments as $inst): ?>
                <tr>
                  <td><strong>Inst #<?= e($inst['installment_number']) ?></strong></td>
                  <td><?= formatDate($inst['due_date']) ?></td>
                  <td><strong><?= formatMoney($inst['amount_due']) ?></strong></td>
                  <td><?= formatMoney($inst['principal_amount']) ?></td>
                  <td><?= formatMoney($inst['interest_amount']) ?></td>
                  <td style="color:#059669; font-weight:700;"><?= formatMoney($inst['paid_amount']) ?></td>
                  <td><span class="badge badge-<?= e($inst['status']) ?>"><?= e($inst['status']) ?></span></td>
                  <?php if ($role === 'officer' || $role === 'admin'): ?>
                    <td>
                      <?php if ($inst['status'] !== 'paid'): ?>
                        <a href="payments.php?loan_id=<?= $loanDetail['id'] ?>&installment_id=<?= $inst['id'] ?>&amount=<?= $inst['amount_due'] - $inst['paid_amount'] ?>" class="btn btn-primary btn-sm">Collect Payment</a>
                      <?php else: ?>
                        <span style="color:#059669; font-weight:700;">✓ Settled</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Review Modal Dialog for Officer / Admin -->
  <?php if ($role === 'officer' || $role === 'admin'): ?>
    <div id="reviewModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <div class="modal-title">Review Loan Application: <?= e($loanDetail['loan_number']) ?></div>
          <button class="modal-close" onclick="closeModal('reviewModal')">&times;</button>
        </div>
        <form action="loans.php" method="POST" class="modal-body">
          <?= CSRF::field() ?>
          <input type="hidden" name="action" value="review_loan">
          <input type="hidden" name="loan_id" value="<?= $loanDetail['id'] ?>">

          <div class="form-group">
            <label class="form-label">Decision Action *</label>
            <select name="status" class="form-control" required>
              <option value="approved">Approve Application</option>
              <option value="disbursed">Disburse Loan Funds Now</option>
              <option value="rejected">Reject Application</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Officer Assessment Notes *</label>
            <textarea name="decision_notes" class="form-control" rows="3" required placeholder="Enter credit check notes, terms, or rejection reasons..."></textarea>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit Review Decision</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

<?php else: ?>
  <!-- Loans Directory List View -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">Loan Applications Registry</div>
      <button class="btn btn-primary" onclick="openModal('applyModal')">+ New Loan Application</button>
    </div>

    <div style="padding: 16px 24px; background: #FAFAFA; border-bottom: 1px solid var(--border-light); display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
      <input type="text" data-table-search="loansTable" class="form-control" placeholder="🔍 Instant search loan ref, customer name..." style="max-width: 320px;">
      
      <?php if ($role !== 'customer'): ?>
        <a href="loans.php" class="btn btn-outline btn-sm">All</a>
        <a href="loans.php?status=pending" class="btn btn-outline btn-sm">Pending</a>
        <a href="loans.php?status=approved" class="btn btn-outline btn-sm">Approved</a>
        <a href="loans.php?status=disbursed" class="btn btn-outline btn-sm">Disbursed</a>
      <?php endif; ?>
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table" id="loansTable">
        <thead>
          <tr>
            <th>Loan Ref</th>
            <?php if ($role !== 'customer'): ?><th>Customer</th><?php endif; ?>
            <th>Package</th>
            <th>Principal Amount</th>
            <th>Term</th>
            <th>Monthly EMI</th>
            <th>Outstanding</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($loansList)): ?>
            <tr><td colspan="9" style="text-align:center; padding:30px; color:#6B7280;">No loan records found.</td></tr>
          <?php else: ?>
            <?php foreach ($loansList as $loan): ?>
              <tr>
                <td><strong><?= e($loan['loan_number']) ?></strong></td>
                <?php if ($role !== 'customer'): ?>
                  <td>
                    <strong><?= e($loan['customer_name']) ?></strong><br>
                    <small style="color:#6B7280;"><?= e($loan['account_number']) ?></small>
                  </td>
                <?php endif; ?>
                <td><?= e($loan['package_name']) ?></td>
                <td><strong><?= formatMoney($loan['amount']) ?></strong></td>
                <td><?= e($loan['term_months']) ?> Mo</td>
                <td><?= formatMoney($loan['monthly_installment']) ?></td>
                <td style="color:#DC2626; font-weight:700;"><?= formatMoney($loan['outstanding_balance']) ?></td>
                <td><span class="badge badge-<?= e($loan['status']) ?>"><?= e($loan['status']) ?></span></td>
                <td>
                  <a href="loans.php?view=<?= $loan['id'] ?>" class="btn btn-outline btn-sm">Details & Schedule</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Loan Application Form Modal -->
  <div id="applyModal" class="modal-overlay <?= (isset($_GET['action']) && $_GET['action'] === 'apply') ? 'active' : '' ?>">
    <div class="modal-content" style="max-width: 650px;">
      <div class="modal-header">
        <div class="modal-title">Apply for Loan / Credit Facility</div>
        <button class="modal-close" onclick="closeModal('applyModal')">&times;</button>
      </div>
      <form action="loans.php" method="POST" class="modal-body">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="apply_loan">

        <?php if ($role !== 'customer' && isset($customersList)): ?>
          <div class="form-group">
            <label class="form-label">Select Customer Account *</label>
            <select name="target_user_id" class="form-control" required>
              <option value="">-- Choose Customer --</option>
              <?php foreach ($customersList as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['account_number']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Loan Product Package *</label>
            <select name="package_id" id="calc_package" class="form-control" required>
              <?php foreach ($packages as $pkg): ?>
                <option value="<?= $pkg['id'] ?>" data-rate="<?= $pkg['interest_rate'] ?>">
                  <?= e($pkg['name']) ?> (<?= e($pkg['interest_rate']) ?>% p.a.)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Loan Amount ($) *</label>
            <input type="number" step="100" id="calc_amount" name="amount" class="form-control" value="5000" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Repayment Tenure (Months) *</label>
          <select name="term_months" id="calc_term" class="form-control" required>
            <option value="6">6 Months</option>
            <option value="12" selected>12 Months</option>
            <option value="24">24 Months</option>
            <option value="36">36 Months</option>
            <option value="48">48 Months</option>
            <option value="60">60 Months</option>
          </select>
        </div>

        <!-- Dynamic Live Calculator Preview Box -->
        <div style="background: var(--dark-black); color: #FFF; padding: 18px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--primary-red);">
          <div style="font-size: 12px; color: #9CA3AF; text-transform: uppercase; font-weight: 700;">Live Installment Calculation Preview</div>
          <div style="display: flex; justify-content: space-between; margin-top: 10px;">
            <div>
              <div style="font-size: 12px; color: #9CA3AF;">Est. Monthly Payment</div>
              <div id="calc_monthly_out" style="font-size: 20px; font-weight: 800; color: #5EEAD4;">$ 0.00</div>
            </div>
            <div>
              <div style="font-size: 12px; color: #9CA3AF;">Total Interest</div>
              <div id="calc_interest_out" style="font-size: 16px; font-weight: 700; color: #FCA5A5;">$ 0.00</div>
            </div>
            <div>
              <div style="font-size: 12px; color: #9CA3AF;">Total Amount Payable</div>
              <div id="calc_total_out" style="font-size: 16px; font-weight: 700; color: #FFF;">$ 0.00</div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Purpose of Loan *</label>
          <textarea name="purpose" class="form-control" rows="2" required placeholder="Describe the reason for this loan request..."></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('applyModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Application</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
