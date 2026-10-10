<?php
/**
 * Monthly Loan & Financial Payment Reports
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/models/Report.php';

Auth::requireRole(['admin', 'officer']);

$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

$reportData = Report::getMonthlyReport($year, $month);
$loans = $reportData['loans'];
$totals = $reportData['totals'];

include __DIR__ . '/views/layouts/header.php';
?>

<div class="panel">
  <div class="panel-header">
    <div>
      <div class="panel-title">Monthly Financial & Loan Portfolio Report</div>
      <small style="color:#6B7280;">Reporting Period: <strong><?= e($reportData['month_name']) ?></strong></small>
    </div>
    
    <div style="display:flex; gap:12px;">
      <a href="receipt.php?report=true&year=<?= $year ?>&month=<?= $month ?>" target="_blank" class="btn btn-primary btn-sm">
        📄 Download PDF Report
      </a>
    </div>
  </div>

  <div style="padding: 20px 24px; background: #FAFAFA; border-bottom: 1px solid var(--border-light);">
    <form action="reports.php" method="GET" style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
      <div>
        <label class="form-label" style="margin-bottom:4px;">Select Month</label>
        <select name="month" class="form-control" style="min-width:140px;">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($month === $m) ? 'selected' : '' ?>>
              <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <div>
        <label class="form-label" style="margin-bottom:4px;">Select Year</label>
        <select name="year" class="form-control" style="min-width:120px;">
          <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
            <option value="<?= $y ?>" <?= ($year === $y) ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div style="margin-top: auto;">
        <button type="submit" class="btn btn-dark">Generate Monthly Report</button>
      </div>
    </form>
  </div>

  <!-- Monthly Aggregate Metrics -->
  <div class="card-grid" style="padding: 24px; margin-bottom: 0;">
    <div class="metric-card">
      <div class="metric-label">Applications Issued</div>
      <div class="metric-value"><?= number_format($totals['count']) ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Disbursed Principal</div>
      <div class="metric-value"><?= formatMoney($totals['disbursed']) ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Collected Payments</div>
      <div class="metric-value" style="color:#059669;"><?= formatMoney($totals['collected']) ?></div>
    </div>

    <div class="metric-card dark">
      <div class="metric-label">Outstanding Balance</div>
      <div class="metric-value"><?= formatMoney($totals['outstanding']) ?></div>
    </div>
  </div>

  <!-- Table Breakdown -->
  <div style="overflow-x: auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Loan Ref</th>
          <th>Customer Name</th>
          <th>Account Ref</th>
          <th>Principal Amount</th>
          <th>Monthly EMI</th>
          <th>Outstanding</th>
          <th>Status</th>
          <th>Applied Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($loans)): ?>
          <tr><td colspan="8" style="text-align:center; padding:30px; color:#6B7280;">No loan activity recorded for <?= e($reportData['month_name']) ?>.</td></tr>
        <?php else: ?>
          <?php foreach ($loans as $l): ?>
            <tr>
              <td><strong><?= e($l['loan_number']) ?></strong></td>
              <td><strong><?= e($l['customer_name']) ?></strong></td>
              <td><?= e($l['account_number']) ?></td>
              <td><strong><?= formatMoney($l['amount']) ?></strong></td>
              <td><?= formatMoney($l['monthly_installment']) ?></td>
              <td style="color:#DC2626; font-weight:700;"><?= formatMoney($l['outstanding_balance']) ?></td>
              <td><span class="badge badge-<?= e($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td><?= formatDate($l['applied_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
