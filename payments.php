<?php
/**
 * Payment Records & Installment Collection
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/Payment.php';
require_once __DIR__ . '/models/Loan.php';

Auth::requireLogin();
$user = Auth::user();
$role = Auth::role();

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    CSRF::checkPost();
    Auth::requireRole(['admin', 'officer']);

    $loanId = (int)($_POST['loan_id'] ?? 0);
    $installmentId = !empty($_POST['installment_id']) ? (int)$_POST['installment_id'] : null;
    $amount = (float)($_POST['amount'] ?? 0);
    $method = trim($_POST['payment_method'] ?? 'cash');
    $reference = trim($_POST['transaction_reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    try {
        $receiptNo = Payment::record($loanId, $installmentId, $amount, $method, $reference, $user['id'], $notes);
        setFlash('success', "Payment recorded successfully! Receipt Reference: {$receiptNo}");
        redirect("payments.php?highlight={$receiptNo}");
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }
}

// Fetch Payments
$searchFilter = $_GET['search'] ?? '';

if ($role === 'customer') {
    $paymentsList = Payment::getByUser($user['id']);
} else {
    $paymentsList = Payment::getAll($searchFilter);
    $activeLoans = Loan::getAll('approved', '');
    $disbursedLoans = Loan::getAll('disbursed', '');
    $eligibleLoans = array_merge($activeLoans, $disbursedLoans);
}

// Pre-fill fields if coming from Loan Details page
$preLoanId = (int)($_GET['loan_id'] ?? 0);
$preInstId = (int)($_GET['installment_id'] ?? 0);
$preAmount = (float)($_GET['amount'] ?? 0);

include __DIR__ . '/views/layouts/header.php';
?>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Payment Transaction Records</div>
    <?php if ($role === 'officer' || $role === 'admin'): ?>
      <button class="btn btn-primary" onclick="openModal('paymentModal')">+ Collect & Record Payment</button>
    <?php endif; ?>
  </div>

  <div style="padding: 16px 24px; background: #FAFAFA; border-bottom: 1px solid var(--border-light);">
    <input type="text" data-table-search="paymentsTable" class="form-control" placeholder="🔍 Search receipt number, loan reference, customer..." style="max-width: 360px;">
  </div>

  <div style="overflow-x: auto;">
    <table class="data-table" id="paymentsTable">
      <thead>
        <tr>
          <th>Receipt No</th>
          <th>Loan Ref</th>
          <?php if ($role !== 'customer'): ?><th>Customer</th><?php endif; ?>
          <th>Amount Paid</th>
          <th>Payment Method</th>
          <th>Date & Time</th>
          <?php if ($role !== 'customer'): ?><th>Recorded By</th><?php endif; ?>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($paymentsList)): ?>
          <tr><td colspan="8" style="text-align:center; padding:30px; color:#6B7280;">No payment receipts logged yet.</td></tr>
        <?php else: ?>
          <?php foreach ($paymentsList as $p): ?>
            <tr style="<?= (isset($_GET['highlight']) && $_GET['highlight'] === $p['receipt_number']) ? 'background-color:#FEF2F2;' : '' ?>">
              <td><strong style="color:var(--dark-black);"><?= e($p['receipt_number']) ?></strong></td>
              <td><strong><?= e($p['loan_number']) ?></strong></td>
              <?php if ($role !== 'customer'): ?>
                <td>
                  <strong><?= e($p['customer_name']) ?></strong><br>
                  <small style="color:#6B7280;"><?= e($p['account_number']) ?></small>
                </td>
              <?php endif; ?>
              <td style="color:#059669; font-weight:800; font-size:15px;"><?= formatMoney($p['amount']) ?></td>
              <td><span class="badge badge-disbursed"><?= strtoupper(e($p['payment_method'])) ?></span></td>
              <td><?= formatDate($p['payment_date'], 'M d, Y H:i') ?></td>
              <?php if ($role !== 'customer'): ?>
                <td><?= e($p['recorded_by_name'] ?? 'Staff') ?></td>
              <?php endif; ?>
              <td>
                <a href="receipt.php?receipt=<?= urlencode($p['receipt_number']) ?>" target="_blank" class="btn btn-outline btn-sm">📄 Download Receipt PDF</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Collect Payment Modal (Officer / Admin) -->
<?php if ($role === 'officer' || $role === 'admin'): ?>
  <div id="paymentModal" class="modal-overlay <?= ($preLoanId > 0) ? 'active' : '' ?>">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Record Installment / Loan Payment</div>
        <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
      </div>
      <form action="payments.php" method="POST" class="modal-body">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="record_payment">
        <?php if ($preInstId > 0): ?>
          <input type="hidden" name="installment_id" value="<?= $preInstId ?>">
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label">Select Active Loan *</label>
          <select name="loan_id" class="form-control" required>
            <option value="">-- Choose Loan Account --</option>
            <?php foreach ($eligibleLoans as $el): ?>
              <option value="<?= $el['id'] ?>" <?= ($preLoanId === (int)$el['id']) ? 'selected' : '' ?>>
                <?= e($el['loan_number']) ?> - <?= e($el['customer_name']) ?> (Outstanding: <?= formatMoney($el['outstanding_balance']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Amount Paid ($) *</label>
            <input type="number" step="0.01" name="amount" class="form-control" value="<?= ($preAmount > 0) ? $preAmount : '' ?>" required placeholder="0.00">
          </div>

          <div class="form-group">
            <label class="form-label">Payment Method *</label>
            <select name="payment_method" class="form-control" required>
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Wire Transfer</option>
              <option value="card">Credit/Debit Card</option>
              <option value="online">Online Banking</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Transaction Reference No (Optional)</label>
          <input type="text" name="transaction_reference" class="form-control" placeholder="TXN-9988123">
        </div>

        <div class="form-group">
          <label class="form-label">Notes / Remarks</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Payment collection notes..."></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('paymentModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Process & Generate Receipt</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
